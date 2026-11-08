<?php

namespace App\Jobs;

use App\Models\DocumentApproval;
use App\Models\EmpData;
use App\Models\EssNotificationLog;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Services\KirimiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SendEssApprovalNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan maksimal jika queue gagal.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param Model $approvable Dokumen ESS (LeaveRequest / OvertimeRequest)
     * @param string $event 'submitted' | 'next_level' | 'completed' | 'auto_completed' | 'rejected'
     * @param DocumentApproval|null $approvalStep Tahapan approval terkait (jika ada)
     * @param array $extra Data tambahan seperti catatan reject, dll.
     */
    public function __construct(
        public Model $approvable,
        public string $event,
        public ?DocumentApproval $approvalStep = null,
        public array $extra = []
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->approvable->loadMissing(['employee.jabatan', 'employee.user', 'user']);

            // 1. Tentukan deskripsi dokumen dan URL link
            $docInfo = $this->resolveDocumentInfo();

            // Identifikasi data akun User pemohon
            $applicantUser   = $this->approvable->user 
                ?? $this->approvable->employee?->user
                ?? ($this->approvable->user_id ? \App\Models\User::find($this->approvable->user_id) : null)
                ?? ($this->approvable->employee?->user_id ? \App\Models\User::find($this->approvable->employee->user_id) : null);
            $applicantUserId = $applicantUser?->id ?? $this->approvable->user_id ?? $this->approvable->employee?->user_id ?? '-';
            $applicantEmail  = $applicantUser?->email ?? $this->approvable->employee?->email ?? '-';

            // 2. Kumpulkan daftar penerima WhatsApp
            $receivers = $this->resolveReceivers();

            if ($receivers->isEmpty()) {
                $targetJabatan = $this->approvalStep?->jabatan_name ?? '-';
                $targetLevel   = $this->approvalStep?->level ?? 1;

                Log::info("[SendEssApprovalNotification] Tidak ada penerima dengan nomor WhatsApp untuk event '{$this->event}' pada dokumen #{$docInfo['number']} (Tahap Level {$targetLevel}: {$targetJabatan}) | Pemohon: [User ID: {$applicantUserId}, Email: {$applicantEmail}]", [
                    'event'             => $this->event,
                    'document_number'   => $docInfo['number'],
                    'target_level'      => $targetLevel,
                    'target_jabatan'    => $targetJabatan,
                    'applicant_user_id' => $applicantUserId,
                    'applicant_email'   => $applicantEmail,
                    'applicant_name'    => $docInfo['applicant_name'],
                ]);
                return;
            }

            // 3. Kirim notifikasi ke setiap penerima yang valid
            foreach ($receivers as $receiver) {
                $message = $this->buildMessage($receiver, $docInfo);

                $result = KirimiService::sendTextMessage($receiver['phone'], $message);

                $receiverUserId = $receiver['user_id'] ?? '-';
                $receiverEmail  = $receiver['email'] ?? '-';

                Log::info("[SendEssApprovalNotification] Notifikasi WhatsApp event '{$this->event}' pada dokumen #{$docInfo['number']} diproses ke: {$receiver['name']} | Penerima: [User ID: {$receiverUserId}, Email: {$receiverEmail}, Phone: {$receiver['phone']}] | Pemohon: [User ID: {$applicantUserId}, Email: {$applicantEmail}]", [
                    'event'             => $this->event,
                    'document_number'   => $docInfo['number'],
                    'receiver_user_id'  => $receiverUserId,
                    'receiver_email'    => $receiverEmail,
                    'receiver_phone'    => $receiver['phone'],
                    'applicant_user_id' => $applicantUserId,
                    'applicant_email'   => $applicantEmail,
                    'status'            => $result['success'] ? 'success' : 'failed',
                    'response'          => $result['response'] ?? $result['error'] ?? null,
                ]);

                // Simpan log ke tabel ess_notification_logs bila tabel sudah di-migrate
                if (Schema::hasTable('ess_notification_logs')) {
                    EssNotificationLog::create([
                        'approvable_type'  => get_class($this->approvable),
                        'approvable_id'    => $this->approvable->id,
                        'event'            => $this->event,
                        'receiver_name'    => $receiver['name'] ?? null,
                        'receiver_phone'   => $receiver['phone'],
                        'message'          => $message,
                        'status'           => $result['success'] ? 'success' : 'failed',
                        'response_payload' => json_encode([
                            'receiver_user_id'  => $receiverUserId,
                            'receiver_email'    => $receiverEmail,
                            'applicant_user_id' => $applicantUserId,
                            'applicant_email'   => $applicantEmail,
                            'api_response'      => $result['response'] ?? $result['error'] ?? null,
                        ]),
                    ]);
                }
            }

        } catch (\Throwable $e) {
            Log::error("[SendEssApprovalNotification] Gagal memproses job notifikasi: " . $e->getMessage(), [
                'event'             => $this->event,
                'approvable'        => get_class($this->approvable) . '#' . $this->approvable->id,
                'applicant_user_id' => $applicantUserId ?? null,
                'applicant_email'   => $applicantEmail ?? null,
                'trace'             => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Ekstrak informasi umum dokumen untuk isi pesan WhatsApp.
     */
    protected function resolveDocumentInfo(): array
    {
        $applicantName = $this->approvable->employee?->name ?? $this->approvable->user?->name ?? 'Karyawan';
        $applicantJabatan = $this->approvable->employee?->jabatan?->jabatan ?? 'Staf';
        $number = $this->approvable->request_number ?? ('#' . $this->approvable->id);

        if ($this->approvable instanceof LeaveRequest) {
            $typeLabel = match ($this->approvable->leave_type) {
                'annual' => 'Cuti Tahunan',
                'special' => 'Cuti Khusus (' . ($this->approvable->specialLeaveType?->name ?? 'Khusus') . ')',
                'permit' => 'Izin (' . ($this->approvable->permitType?->name ?? 'Izin Kerja') . ')',
                'sick' => 'Izin Sakit',
                default => 'Permohonan Cuti/Izin',
            };

            $startDate = $this->approvable->full_day_start_date?->format('d/m/Y')
                ?? $this->approvable->half_day_start_date?->format('d/m/Y')
                ?? '-';
            $endDate = $this->approvable->full_day_end_date?->format('d/m/Y')
                ?? $this->approvable->half_day_end_date?->format('d/m/Y')
                ?? $startDate;

            $dateRange = ($startDate === $endDate) ? $startDate : "{$startDate} s/d {$endDate}";
            $duration = ($this->approvable->total_days ?? 1) . ' Hari Kerja';
            $reason = $this->approvable->reason ?? '-';

            $path = match ($this->approvable->leave_type) {
                'special' => "/special-leave/{$this->approvable->id}",
                'permit'  => "/permit/{$this->approvable->id}",
                'sick'    => "/sick/{$this->approvable->id}",
                default   => "/leave/{$this->approvable->id}",
            };

            $link = "https://hrd.fincontrol.my.id" . $path;

        } elseif ($this->approvable instanceof OvertimeRequest) {
            $typeLabel = 'Lembur Kerja';
            $date = $this->approvable->date?->format('d/m/Y') ?? '-';
            $startTime = substr((string) $this->approvable->start_time, 0, 5);
            $endTime = substr((string) $this->approvable->end_time, 0, 5);
            $dateRange = "{$date} ({$startTime} - {$endTime})";
            $duration = ($this->approvable->total_hours ?? 0) . ' Jam';
            $reason = $this->approvable->reason ?? $this->approvable->task_description ?? '-';
            $link = "https://hrd.fincontrol.my.id/overtime/{$this->approvable->id}";

        } else {
            $typeLabel = 'Permohonan ESS';
            $dateRange = '-';
            $duration = '-';
            $reason = '-';
            $link = 'https://hrd.fincontrol.my.id';
        }

        return [
            'type_label' => $typeLabel,
            'number' => $number,
            'applicant_name' => $applicantName,
            'applicant_jabatan' => $applicantJabatan,
            'date_range' => $dateRange,
            'duration' => $duration,
            'reason' => $reason,
            'link' => $link,
        ];
    }

    /**
     * Tentukan siapa saja yang harus menerima notifikasi pada event ini.
     */
    protected function resolveReceivers(): \Illuminate\Support\Collection
    {
        $receivers = collect();

        // Kasus 1: Notifikasi ke Approver aktif (submitted / next_level)
        if (in_array($this->event, ['submitted', 'next_level'])) {
            $step = $this->approvalStep;

            if (!$step && method_exists($this->approvable, 'approvals')) {
                $step = $this->approvable->approvals()
                    ->where('status', 'pending')
                    ->orderBy('level')
                    ->first();
            }

            if (!$step) {
                return $receivers;
            }

            // Cari penerima persetujuan:
            // 1. Jika tahapan menunjuk spesifik karyawan (misal: pengganti level 1)
            // 2. Jika tidak, cari semua karyawan dengan jabatan_id yang sama dengan approval step ini
            if ($step->approver_emp_id) {
                $approverEmployees = EmpData::with('user')->where('id', $step->approver_emp_id)->get();
            } else {
                $approverEmployees = EmpData::with('user')
                    ->where('jabatan_id', $step->jabatan_id)
                    ->where('id', '!=', $this->approvable->emp_id)
                    ->get();
            }

            foreach ($approverEmployees as $emp) {
                $user = $emp->user ?? ($emp->email ? \App\Models\User::where('email', $emp->email)->first() : null);
                $phone = $user?->phone ?? null;
                $formattedPhone = KirimiService::formatPhoneNumber($phone);
                if ($formattedPhone) {
                    $receivers->push([
                        'role'             => 'approver',
                        'name'             => $emp->name,
                        'user_id'          => $emp->user_id ?? $user?->id,
                        'email'            => $user?->email ?? $emp->email,
                        'phone'            => $formattedPhone,
                        'level'            => $step->level,
                        'approver_jabatan' => $step->jabatan_name,
                    ]);
                }
            }

            return $receivers->unique('phone');
        }

        // Kasus 2: Notifikasi ke Pemohon (completed / auto_completed / rejected / step_approved)
        if (in_array($this->event, ['completed', 'auto_completed', 'rejected', 'step_approved'])) {
            $user = $this->approvable->user ?? $this->approvable->employee?->user;
            $applicantPhone = $user?->phone ?? null;
            $formattedPhone = KirimiService::formatPhoneNumber($applicantPhone);

            if ($formattedPhone) {
                $approverName = $this->approvalStep?->approver_name
                    ?? $this->approvable->approver?->name
                    ?? auth()->user()?->name
                    ?? 'Atasan Berwenang';

                $approverJabatan = $this->approvalStep?->jabatan_name ?? 'Atasan';

                $receivers->push([
                    'role'             => 'applicant',
                    'name'             => $this->approvable->employee?->name ?? $user?->name ?? 'Karyawan',
                    'user_id'          => $user?->id,
                    'email'            => $user?->email ?? $this->approvable->employee?->email,
                    'phone'            => $formattedPhone,
                    'approver_name'    => $approverName,
                    'approver_jabatan' => $approverJabatan,
                    'rejector_name'    => $approverName,
                    'rejector_jabatan' => $approverJabatan,
                    'level'            => $this->approvalStep?->level ?? 1,
                    'notes'            => $this->extra['notes']
                        ?? $this->approvalStep?->notes
                        ?? $this->approvable->rejection_reason
                        ?? '-',
                ]);
            }

            return $receivers;
        }

        return $receivers;
    }

    /**
     * Bangun teks pesan WhatsApp dengan format terstruktur dan ramah.
     */
    protected function buildMessage(array $receiver, array $doc): string
    {
        $role = $receiver['role'] ?? 'approver';

        if ($role === 'approver') {
            $levelText = "Level " . ($receiver['level'] ?? 1) . " (" . ($receiver['approver_jabatan'] ?? 'Atasan') . ")";
            $approverLink = "https://hrd.fincontrol.my.id/approval-inbox";

            return "📢 *HRD NOTIFIKASI - PERSETUJUAN PERMOHONAN*\n\n"
                . "Halo Bapak/Ibu *{$receiver['name']}*,\n"
                . "Terdapat permohonan baru yang membutuhkan persetujuan Anda:\n\n"
                . "📋 *Jenis:* {$doc['type_label']}\n"
                . "📄 *No. Pengajuan:* {$doc['number']}\n"
                . "👤 *Pemohon:* {$doc['applicant_name']} ({$doc['applicant_jabatan']})\n"
                . "📅 *Waktu/Tanggal:* {$doc['date_range']}\n"
                . "⏳ *Durasi:* {$doc['duration']}\n"
                . "📝 *Keperluan:* {$doc['reason']}\n"
                . "⚡ *Tahap Approval:* {$levelText}\n\n"
                . "Silakan klik link berikut untuk meninjau dan menyetujui pengajuan:\n\n"
                . "{$approverLink}";
        }

        if ($this->event === 'step_approved') {
            $approverName = $receiver['approver_name'] ?? 'Atasan';
            $approverJabatan = $receiver['approver_jabatan'] ?? 'Approver';
            $level = $receiver['level'] ?? 1;

            return "ℹ️ *HRD NOTIFIKASI - PERSETUJUAN TAHAP L{$level}*\n\n"
                . "Halo Kak *{$receiver['name']}*,\n"
                . "Pengajuan Anda telah *DISETUJUI* pada tahap *Level {$level}*:\n\n"
                . "📋 *Jenis:* {$doc['type_label']}\n"
                . "📄 *No. Pengajuan:* {$doc['number']}\n"
                . "👤 *Disetujui Oleh:* {$approverName} ({$approverJabatan})\n"
                . "📅 *Waktu/Tanggal:* {$doc['date_range']}\n"
                . "⏳ *Durasi:* {$doc['duration']}\n"
                . "⏳ *Status:* Sedang diproses oleh tahapan approval berikutnya.\n\n"
                . "Detail pengajuan dapat dilihat melalui tautan berikut:\n\n"
                . "{$doc['link']}";
        }

        if ($this->event === 'rejected') {
            $rejector = $receiver['rejector_name'] ?? 'Atasan';
            $rejectionNotes = $receiver['notes'] ?? '-';

            return "❌ *HRD NOTIFIKASI - PENGAJUAN DITOLAK*\n\n"
                . "Halo Kak *{$receiver['name']}*,\n"
                . "Mohon maaf, pengajuan Anda telah *DITOLAK*:\n\n"
                . "📋 *Jenis:* {$doc['type_label']}\n"
                . "📄 *No. Pengajuan:* {$doc['number']}\n"
                . "📅 *Waktu/Tanggal:* {$doc['date_range']}\n"
                . "⏳ *Durasi:* {$doc['duration']}\n"
                . "🚫 *Ditolak Oleh:* {$rejector}\n"
                . "💬 *Catatan Penolakan:* {$rejectionNotes}\n\n"
                . "Anda dapat memeriksa catatan penolakan dan mengajukan ulang melalui tautan berikut:\n\n"
                . "{$doc['link']}";
        }

        // completed atau auto_completed
        $isAuto = ($this->event === 'auto_completed');
        $statusTitle = $isAuto ? 'DISETUJUI OTOMATIS' : 'DISETUJUI LENGKAP';

        return "✅ *HRD NOTIFIKASI - PENGAJUAN {$statusTitle}*\n\n"
            . "Halo Kak *{$receiver['name']}*,\n"
            . "Kabar baik! Pengajuan Anda telah *DISETUJUI*:\n\n"
            . "📋 *Jenis:* {$doc['type_label']}\n"
            . "📄 *No. Pengajuan:* {$doc['number']}\n"
            . "📅 *Waktu/Tanggal:* {$doc['date_range']}\n"
            . "⏳ *Durasi:* {$doc['duration']}\n"
            . "📝 *Keperluan:* {$doc['reason']}\n"
            . "✨ *Status:* Disetujui (Completed)\n\n"
            . "Detail pengajuan dapat dilihat melalui tautan berikut:\n\n"
            . "{$doc['link']}";
    }
}

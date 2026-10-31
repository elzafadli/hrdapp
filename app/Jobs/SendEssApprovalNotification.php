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
            $this->approvable->loadMissing(['employee.jabatan', 'user']);

            // 1. Tentukan deskripsi dokumen dan URL link
            $docInfo = $this->resolveDocumentInfo();

            // 2. Kumpulkan daftar penerima WhatsApp
            $receivers = $this->resolveReceivers();

            if ($receivers->isEmpty()) {
                Log::info("[SendEssApprovalNotification] Tidak ada penerima dengan nomor WhatsApp untuk event '{$this->event}' pada dokumen #{$docInfo['number']}");
                return;
            }

            // 3. Kirim notifikasi ke setiap penerima yang valid
            foreach ($receivers as $receiver) {
                $message = $this->buildMessage($receiver, $docInfo);

                $result = KirimiService::sendTextMessage($receiver['phone'], $message);

                // Simpan log ke tabel ess_notification_logs bila tabel sudah di-migrate
                if (Schema::hasTable('ess_notification_logs')) {
                    EssNotificationLog::create([
                        'approvable_type' => get_class($this->approvable),
                        'approvable_id' => $this->approvable->id,
                        'event' => $this->event,
                        'receiver_name' => $receiver['name'] ?? null,
                        'receiver_phone' => "6282245833986",
                        // 'receiver_phone'   => $receiver['phone'],
                        'message' => $message,
                        'status' => $result['success'] ? 'success' : 'failed',
                        'response_payload' => json_encode($result['response'] ?? $result['error']),
                    ]);
                }
            }

        } catch (\Throwable $e) {
            Log::error("[SendEssApprovalNotification] Gagal memproses job notifikasi: " . $e->getMessage(), [
                'event' => $this->event,
                'approvable' => get_class($this->approvable) . '#' . $this->approvable->id,
                'trace' => $e->getTraceAsString(),
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
                'permit' => 'Izin Tidak Masuk',
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

            $routeName = match ($this->approvable->leave_type) {
                'special' => 'special-leave.show',
                'permit' => 'permit.show',
                'sick' => 'sick.show',
                default => 'leave.show',
            };

            $link = route($routeName, $this->approvable->id);

        } elseif ($this->approvable instanceof OvertimeRequest) {
            $typeLabel = 'Lembur Kerja';
            $date = $this->approvable->date?->format('d/m/Y') ?? '-';
            $startTime = substr((string) $this->approvable->start_time, 0, 5);
            $endTime = substr((string) $this->approvable->end_time, 0, 5);
            $dateRange = "{$date} ({$startTime} - {$endTime})";
            $duration = ($this->approvable->total_hours ?? 0) . ' Jam';
            $reason = $this->approvable->reason ?? $this->approvable->task_description ?? '-';
            $link = route('overtime.show', $this->approvable->id);

        } else {
            $typeLabel = 'Permohonan ESS';
            $dateRange = '-';
            $duration = '-';
            $reason = '-';
            $link = url('/');
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

            // Cari semua user dengan jabatan_id yang sama dengan approval step ini
            // (kecuali karyawan pemohon itu sendiri)
            $approverEmployees = EmpData::with('user')
                ->where('jabatan_id', $step->jabatan_id)
                ->where('id', '!=', $this->approvable->emp_id)
                ->get();

            foreach ($approverEmployees as $emp) {
                $phone = $emp->user?->phone ?? null;
                $formattedPhone = KirimiService::formatPhoneNumber($phone);
                if ($formattedPhone) {
                    $receivers->push([
                        'role' => 'approver',
                        'name' => $emp->name,
                        'phone' => $formattedPhone,
                        'level' => $step->level,
                        'approver_jabatan' => $step->jabatan_name,
                    ]);
                }
            }

            return $receivers->unique('phone');
        }

        // Kasus 2: Notifikasi ke Pemohon (completed / auto_completed / rejected)
        if (in_array($this->event, ['completed', 'auto_completed', 'rejected'])) {
            $user = $this->approvable->user ?? $this->approvable->employee?->user;
            $applicantPhone = $user?->phone ?? null;
            $formattedPhone = KirimiService::formatPhoneNumber($applicantPhone);

            if ($formattedPhone) {
                $rejectorName = $this->approvalStep?->approver_name
                    ?? $this->approvable->approver?->name
                    ?? 'Atasan Berwenang';

                $rejectorJabatan = $this->approvalStep?->jabatan_name ?? 'Atasan';

                $receivers->push([
                    'role' => 'applicant',
                    'name' => $this->approvable->employee?->name ?? $user?->name ?? 'Karyawan',
                    'phone' => $formattedPhone,
                    'rejector_name' => $rejectorName,
                    'rejector_jabatan' => $rejectorJabatan,
                    'notes' => $this->extra['notes']
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

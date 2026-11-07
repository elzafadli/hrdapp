<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat tabel master tipe izin
        if (!Schema::hasTable('permit_types')) {
            Schema::create('permit_types', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('code', 50)->unique();
                $table->decimal('max_days', 5, 1)->nullable()->comment('Batas maksimal hari kerja per pengajuan (null jika tidak dibatasi)');
                $table->boolean('is_paid')->default(true)->comment('True = Izin Dibayar/Dispensasi, False = Izin Tidak Dibayar (Potong Gaji/Cuti)');
                $table->boolean('requires_attachment')->default(false)->comment('True jika wajib mengunggah surat tugas / berkas pendukung');
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 2. Tambahkan relasi permit_type_id pada tabel leave_requests
        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('leave_requests', 'permit_type_id')) {
                    $table->foreignId('permit_type_id')
                        ->nullable()
                        ->after('special_leave_type_id')
                        ->constrained('permit_types')
                        ->nullOnDelete();
                }
            });
        }

        // 3. Seed data jenis izin standar HRIS (Talenta/GreatDay/Gadjian inspired)
        $defaultTypes = [
            [
                'name'                => 'Izin Keperluan Pribadi',
                'code'                => 'personal',
                'max_days'            => 2.0,
                'is_paid'             => false,
                'requires_attachment' => false,
                'is_active'           => true,
                'description'         => 'Izin tidak hadir karena keperluan keluarga, urusan administrasi, atau urusan mendesak pribadi lainnya.',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'name'                => 'Izin Perjalanan Dinas / Tugas Luar',
                'code'                => 'duty',
                'max_days'            => 14.0,
                'is_paid'             => true,
                'requires_attachment' => true,
                'is_active'           => true,
                'description'         => 'Penugasan pekerjaan, dinas luar kota/lapangan, kunjungan klien, meeting eksternal, atau pelatihan di luar kantor.',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'name'                => 'Izin Datang Terlambat',
                'code'                => 'late_arrival',
                'max_days'            => 1.0,
                'is_paid'             => true,
                'requires_attachment' => false,
                'is_active'           => true,
                'description'         => 'Izin dispensasi keterlambatan hadir masuk kantor karena kendala darurat di perjalanan atau keperluan pagi hari.',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'name'                => 'Izin Pulang Lebih Awal',
                'code'                => 'early_leave',
                'max_days'            => 1.0,
                'is_paid'             => true,
                'requires_attachment' => false,
                'is_active'           => true,
                'description'         => 'Izin meninggalkan kantor lebih cepat sebelum jam kerja berakhir karena keperluan keluarga atau hal penting.',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'name'                => 'Izin Keluar Kantor Sementara',
                'code'                => 'temporary_out',
                'max_days'            => 1.0,
                'is_paid'             => true,
                'requires_attachment' => false,
                'is_active'           => true,
                'description'         => 'Izin keluar area kantor sementara waktu di jam kerja untuk urusan tertentu dan kembali bekerja pada hari yang sama.',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'name'                => 'Izin Dispensasi Resmi Negara',
                'code'                => 'dispensation',
                'max_days'            => 3.0,
                'is_paid'             => true,
                'requires_attachment' => true,
                'is_active'           => true,
                'description'         => 'Dispensasi panggilan resmi instansi kepolisian, pengadilan, pemilu (KPPS/TPS), atau kegiatan kenegaraan lainnya.',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'name'                => 'Izin Ujian / Studi / Akademik',
                'code'                => 'exam_study',
                'max_days'            => 2.0,
                'is_paid'             => true,
                'requires_attachment' => true,
                'is_active'           => true,
                'description'         => 'Izin dispensasi mengikuti ujian semester, sidang skripsi/tesis, atau wisuda pendidikan formal karyawan.',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'name'                => 'Izin Takziah / Melayat',
                'code'                => 'condolence',
                'max_days'            => 1.0,
                'is_paid'             => true,
                'requires_attachment' => false,
                'is_active'           => true,
                'description'         => 'Izin melayat kerabat, tetangga, atau sanak saudara yang meninggal dunia di luar ketentuan cuti khusus kedukaan.',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'name'                => 'Izin Sakit Ringan (Tanpa Surat Dokter)',
                'code'                => 'sick_no_certificate',
                'max_days'            => 1.0,
                'is_paid'             => true,
                'requires_attachment' => false,
                'is_active'           => true,
                'description'         => 'Izin 1 hari kerja karena kondisi kesehatan menurun ringan tanpa berobat ke fasilitas kesehatan/dokter.',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
        ];

        foreach ($defaultTypes as $type) {
            DB::table('permit_types')->updateOrInsert(
                ['code' => $type['code']],
                $type
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('leave_requests') && Schema::hasColumn('leave_requests', 'permit_type_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropForeign(['permit_type_id']);
                $table->dropColumn('permit_type_id');
            });
        }

        Schema::dropIfExists('permit_types');
    }
};


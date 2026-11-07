<?php

namespace Database\Seeders;

use App\Models\PermitType;
use Illuminate\Database\Seeder;

class PermitTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name'                => 'Izin Keperluan Pribadi',
                'code'                => 'personal',
                'max_days'            => 2.0,
                'is_paid'             => false,
                'requires_attachment' => false,
                'is_active'           => true,
                'description'         => 'Izin tidak hadir karena keperluan keluarga, urusan administrasi, atau urusan mendesak pribadi lainnya.',
            ],
            [
                'name'                => 'Izin Perjalanan Dinas / Tugas Luar',
                'code'                => 'duty',
                'max_days'            => 14.0,
                'is_paid'             => true,
                'requires_attachment' => true,
                'is_active'           => true,
                'description'         => 'Penugasan pekerjaan, dinas luar kota/lapangan, kunjungan klien, meeting eksternal, atau pelatihan di luar kantor.',
            ],
            [
                'name'                => 'Izin Datang Terlambat',
                'code'                => 'late_arrival',
                'max_days'            => 1.0,
                'is_paid'             => true,
                'requires_attachment' => false,
                'is_active'           => true,
                'description'         => 'Izin dispensasi keterlambatan hadir masuk kantor karena kendala darurat di perjalanan atau keperluan pagi hari.',
            ],
            [
                'name'                => 'Izin Pulang Lebih Awal',
                'code'                => 'early_leave',
                'max_days'            => 1.0,
                'is_paid'             => true,
                'requires_attachment' => false,
                'is_active'           => true,
                'description'         => 'Izin meninggalkan kantor lebih cepat sebelum jam kerja berakhir karena keperluan keluarga atau hal penting.',
            ],
            [
                'name'                => 'Izin Keluar Kantor Sementara',
                'code'                => 'temporary_out',
                'max_days'            => 1.0,
                'is_paid'             => true,
                'requires_attachment' => false,
                'is_active'           => true,
                'description'         => 'Izin keluar area kantor sementara waktu di jam kerja untuk urusan tertentu dan kembali bekerja pada hari yang sama.',
            ],
            [
                'name'                => 'Izin Dispensasi Resmi Negara',
                'code'                => 'dispensation',
                'max_days'            => 3.0,
                'is_paid'             => true,
                'requires_attachment' => true,
                'is_active'           => true,
                'description'         => 'Dispensasi panggilan resmi instansi kepolisian, pengadilan, pemilu (KPPS/TPS), atau kegiatan kenegaraan lainnya.',
            ],
            [
                'name'                => 'Izin Ujian / Studi / Akademik',
                'code'                => 'exam_study',
                'max_days'            => 2.0,
                'is_paid'             => true,
                'requires_attachment' => true,
                'is_active'           => true,
                'description'         => 'Izin dispensasi mengikuti ujian semester, sidang skripsi/tesis, atau wisuda pendidikan formal karyawan.',
            ],
            [
                'name'                => 'Izin Takziah / Melayat',
                'code'                => 'condolence',
                'max_days'            => 1.0,
                'is_paid'             => true,
                'requires_attachment' => false,
                'is_active'           => true,
                'description'         => 'Izin melayat kerabat, tetangga, atau sanak saudara yang meninggal dunia di luar ketentuan cuti khusus kedukaan.',
            ],
            [
                'name'                => 'Izin Sakit Ringan (Tanpa Surat Dokter)',
                'code'                => 'sick_no_certificate',
                'max_days'            => 1.0,
                'is_paid'             => true,
                'requires_attachment' => false,
                'is_active'           => true,
                'description'         => 'Izin 1 hari kerja karena kondisi kesehatan menurun ringan tanpa berobat ke fasilitas kesehatan/dokter.',
            ],
        ];

        foreach ($types as $type) {
            PermitType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}


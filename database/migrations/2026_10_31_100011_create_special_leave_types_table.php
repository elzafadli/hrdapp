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
        // 1. Buat tabel master tipe cuti khusus
        Schema::create('special_leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 50)->unique();
            $table->decimal('days_allowed', 5, 1)->default(1.0)->comment('Maksimal hari cuti yang diberikan');
            $table->text('description')->nullable();
            $table->boolean('requires_attachment')->default(true);
            $table->enum('gender_restriction', ['male', 'female', 'all'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tambahkan relasi special_leave_type_id pada tabel leave_requests
        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('leave_requests', 'special_leave_type_id')) {
                    $table->foreignId('special_leave_type_id')
                        ->nullable()
                        ->after('leave_type')
                        ->constrained('special_leave_types')
                        ->nullOnDelete();
                }
            });
        }

        // 3. Seed data tipe cuti khusus berdasarkan ketentuan undang-undang dan kebijakan perusahaan
        $types = [
            [
                'name'               => 'Cuti Menikah',
                'code'               => 'marriage',
                'days_allowed'       => 3.0,
                'description'        => 'Pernikahan karyawan yang bersangkutan.',
                'requires_attachment'=> true,
                'gender_restriction' => 'all',
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Cuti Menikahkan Anak',
                'code'               => 'child_marriage',
                'days_allowed'       => 2.0,
                'description'        => 'Pernikahan anak sah dari karyawan.',
                'requires_attachment'=> true,
                'gender_restriction' => 'all',
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Cuti Mengkhitankan Anak',
                'code'               => 'child_circumcision',
                'days_allowed'       => 2.0,
                'description'        => 'Khitanan anak kandung karyawan.',
                'requires_attachment'=> true,
                'gender_restriction' => 'all',
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Cuti Membaptiskan Anak',
                'code'               => 'child_baptism',
                'days_allowed'       => 2.0,
                'description'        => 'Pembaptisan anak kandung karyawan.',
                'requires_attachment'=> true,
                'gender_restriction' => 'all',
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Cuti Melahirkan',
                'code'               => 'maternity',
                'days_allowed'       => 90.0,
                'description'        => 'Persalinan/melahirkan bagi karyawati (3 bulan).',
                'requires_attachment'=> true,
                'gender_restriction' => 'female',
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Cuti Istri Melahirkan',
                'code'               => 'paternity',
                'days_allowed'       => 2.0,
                'description'        => 'Istri karyawan melahirkan atau operasi sesar.',
                'requires_attachment'=> true,
                'gender_restriction' => 'male',
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Cuti Istri Keguguran Kandungan',
                'code'               => 'miscarriage_spouse',
                'days_allowed'       => 2.0,
                'description'        => 'Istri karyawan mengalami keguguran kandungan.',
                'requires_attachment'=> true,
                'gender_restriction' => 'male',
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Cuti Duka (Suami/Istri, Orangtua/Mertua, Anak/Menantu)',
                'code'               => 'bereavement_core',
                'days_allowed'       => 2.0,
                'description'        => 'Kematian suami/istri, orang tua/mertua, atau anak/menantu.',
                'requires_attachment'=> true,
                'gender_restriction' => 'all',
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Cuti Duka (Anggota keluarga lain dalam satu rumah)',
                'code'               => 'bereavement_family',
                'days_allowed'       => 1.0,
                'description'        => 'Kematian anggota keluarga lain yang tinggal satu rumah.',
                'requires_attachment'=> true,
                'gender_restriction' => 'all',
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Cuti Haid',
                'code'               => 'menstruation',
                'days_allowed'       => 2.0,
                'description'        => 'Hari pertama dan kedua haid bagi karyawati yang merasakan sakit.',
                'requires_attachment'=> false,
                'gender_restriction' => 'female',
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ];

        DB::table('special_leave_types')->insert($types);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('leave_requests') && Schema::hasColumn('leave_requests', 'special_leave_type_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropForeign(['special_leave_type_id']);
                $table->dropColumn('special_leave_type_id');
            });
        }

        Schema::dropIfExists('special_leave_types');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('emp_data', function (Blueprint $table) {
            if (!Schema::hasColumn('emp_data', 'nik_karyawan')) {
                $table->string('nik_karyawan', 50)->nullable()->after('id')->comment('ID Karyawan');
            }
            if (!Schema::hasColumn('emp_data', 'photo')) {
                $table->string('photo', 255)->nullable()->after('name')->comment('Foto Profil Karyawan');
            }
            if (!Schema::hasColumn('emp_data', 'citizenship')) {
                $table->string('citizenship', 20)->default('WNI')->after('gender')->comment('Kewarganegaraan: WNI/WNA');
            }
            if (!Schema::hasColumn('emp_data', 'nik_ktp')) {
                $table->string('nik_ktp', 50)->nullable()->after('citizenship')->comment('Nomor Identitas KTP');
            }
            if (!Schema::hasColumn('emp_data', 'birth_place')) {
                $table->string('birth_place', 100)->nullable()->after('nik_ktp')->comment('Tempat Lahir');
            }
            if (!Schema::hasColumn('emp_data', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('birth_place')->comment('Tanggal Lahir');
            }
            if (!Schema::hasColumn('emp_data', 'marital_status')) {
                $table->string('marital_status', 50)->nullable()->after('birth_date')->comment('Status Perkawinan');
            }
            if (!Schema::hasColumn('emp_data', 'religion')) {
                $table->string('religion', 50)->nullable()->after('marital_status')->comment('Agama');
            }
            if (!Schema::hasColumn('emp_data', 'blood_type')) {
                $table->string('blood_type', 10)->nullable()->after('religion')->comment('Golongan Darah');
            }
            if (!Schema::hasColumn('emp_data', 'last_education')) {
                $table->string('last_education', 50)->nullable()->after('blood_type')->comment('Jenjang Pendidikan Terakhir');
            }
            if (!Schema::hasColumn('emp_data', 'education_institution')) {
                $table->string('education_institution', 255)->nullable()->after('last_education')->comment('Nama Institusi Pendidikan');
            }
            if (!Schema::hasColumn('emp_data', 'join_date')) {
                $table->date('join_date')->nullable()->after('education_institution')->comment('Tanggal Mulai Kerja');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_data', function (Blueprint $table) {
            $columns = [
                'nik_karyawan',
                'photo',
                'citizenship',
                'nik_ktp',
                'birth_place',
                'birth_date',
                'marital_status',
                'religion',
                'blood_type',
                'last_education',
                'education_institution',
                'join_date',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('emp_data', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

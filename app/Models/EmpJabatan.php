<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpJabatan extends Model
{
    use HasFactory;

    protected $table = 'emp_jabatan';
    protected $fillable = ['jabatan'];

    public function employees()
    {
        return $this->hasMany(EmpData::class, 'jabatan_id');
    }

    /**
     * Cek apakah jabatan ini terdaftar sebagai approver aktif di aturan alur persetujuan (approval_settings)
     * atau memiliki hak supervisi/melihat kalender tim (seperti Cashier).
     */
    public function canApprove(): bool
    {
        if (in_array(strtolower(trim((string) $this->jabatan)), ['cashier'], true)) {
            return true;
        }

        return ApprovalSetting::isJabatanAnApprover((int) $this->id);
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DangKyKhuonMat extends Model
{
    protected $table = 'dangky_khuonmat';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'id_nhanvien',
        'ngay_dang_ky',
        'trang_thai'

    ];

    // Relationship với NguoiDungInternal (Nhân viên)
    public function nhanVien()
    {
        return $this->belongsTo(NguoiDungInternal::class, 'id_nhanvien', 'id');
    }
}

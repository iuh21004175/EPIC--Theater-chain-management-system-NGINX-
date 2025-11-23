<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LuongThuong extends Model
{
    protected $table = 'luong_thuong';
    protected $primaryKey = 'id';
    public $timestamps = true; 

    protected $fillable = [
        'id_nhanvien',
        'thang',
        'so_ngay_cong',
        'so_gio_cong',
        'tong_luong',
        'thuong',
        'tong_thu_nhap',
        'trang_thai' // 0: Chưa duyệt, 1: Đã duyệt
    ];

    public function nhanVien() {
         return $this->belongsTo(NguoiDungInternal::class, 'id_nhanvien', 'id');
    }
}

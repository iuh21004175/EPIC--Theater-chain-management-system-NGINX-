<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ve extends Model
{
    protected $table = 've';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'donhang_id',
        'suat_chieu_id',
        'ghe_id',
        'gia_ve',
        'khach_hang_id',
        'trang_thai', // 0: Đã hủy 2: Đã đặt 1: Giữ chỗ 3: Trống 
        'het_han_giu',
        'ngay_tao',
    ];

    public function donhang()
    {
        return $this->belongsTo(DonHang::class, 'donhang_id', 'id');
    }

    public function suatchieu()
    {
        return $this->belongsTo(SuatChieu::class, 'suat_chieu_id', 'id');
    }

    public function ghe()
    {
        return $this->belongsTo(SoDoGhe::class, 'ghe_id', 'id');
    }

    public function khachhang()
    {
        return $this->belongsTo(KhachHang::class, 'khach_hang_id', 'id');
    }
}

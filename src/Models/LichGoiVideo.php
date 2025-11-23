<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\NguoiDungInternal;

class LichGoiVideo extends Model
{
    protected $table = 'lich_goi_video';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'id_khachhang',
        'id_rapphim',
        'id_nhanvien',
        'chu_de',
        'mo_ta',
        'thoi_gian_dat',
        'room_id',
        'trang_thai',
        'thoi_gian_bat_dau',
        'thoi_gian_ket_thuc'
    ];

    protected $casts = [
        'thoi_gian_dat' => 'datetime',
        'thoi_gian_bat_dau' => 'datetime',
        'thoi_gian_ket_thuc' => 'datetime'
    ];

    // Quan hệ với KhachHang
    public function khachhang()
    {
        return $this->belongsTo(KhachHang::class, 'id_khachhang');
    }

    // Quan hệ với RapPhim
    public function rapphim()
    {
        return $this->belongsTo(RapPhim::class, 'id_rapphim');
    }

    // Quan hệ với NhanVien
    public function nhanvien()
    {
        return $this->belongsTo(NguoiDungInternal::class, 'id_nhanvien');
    }

    // Quan hệ với WebRTCSession
    public function webrtcSession()
    {
        return $this->hasOne(WebRTCSession::class, 'id_lich_goi_video');
    }

    // Các trạng thái
    const TRANG_THAI_CHO_NHAN_VIEN = 1;
    const TRANG_THAI_DA_CHON_NV = 2;
    const TRANG_THAI_DANG_GOI = 3;
    const TRANG_THAI_HOAN_THANH = 4;
    const TRANG_THAI_HUY = 5;

    // Scope để lấy lịch chờ nhân viên
    public function scopeChoNhanVien($query)
    {
        return $query->where('trang_thai', self::TRANG_THAI_CHO_NHAN_VIEN);
    }

    // Scope để lấy lịch đã được chọn
    public function scopeDaChonNhanVien($query)
    {
        return $query->where('trang_thai', self::TRANG_THAI_DA_CHON_NV);
    }

    // Scope để lấy lịch đang gọi
    public function scopeDangGoi($query)
    {
        return $query->where('trang_thai', self::TRANG_THAI_DANG_GOI);
    }
}

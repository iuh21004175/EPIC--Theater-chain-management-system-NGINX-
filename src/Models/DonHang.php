<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonHang extends Model
{
    protected $table = 'donhang';
    protected $primaryKey = 'id';
    public $timestamps = false; // Nếu muốn dùng created_at, updated_at thì để true

    protected $fillable = [
        'id',
        'user_id',
        'id_nhanvien',
        'suat_chieu_id',
        'thequatang_id',
        'rap_id', // Dành cho những KH chỉ mua sản phẩm
        'the_qua_tang_su_dung',
        'ma_ve',
        'qr_code',
        'tong_tien',
        'phuong_thuc_thanh_toan', //1: chuyển khoản 2: tiền mặt
        'trang_thai', //2: Đã thanh toán, 1: Chờ thanh toán 0: Đã hủy
        'ngay_dat',
        'phuong_thuc_mua' // 0: Khách hàng đặt online, 1: Mua vé gói xem phim trực tuyến, 2: Nhân viên bán vé 3: Chỉ mua sản phẩm
    ];

    public function user()
    {
        return $this->belongsTo(KhachHang::class, 'user_id', 'id');
    }

    public function suatChieu()
    {
        return $this->belongsTo(SuatChieu::class, 'suat_chieu_id', 'id');
    }

    public function chiTietDonHang()
    {
        return $this->hasMany(ChiTietDonHang::class, 'donhang_id', 'id');
    }

    public function ve()
    {
        return $this->hasMany(Ve::class, 'donhang_id', 'id');
    }

    public function theQuaTang()
    {
        return $this->belongsTo(TheQuaTang::class, 'thequatang_id', 'id');
    }

    public function phim()
    {
        return $this->belongsTo(Phim::class, 'phim_id', 'id');
    }

    public function nhanVien()
    {
        return $this->belongsTo(TaiKhoanInternal::class, 'id_nhanvien', 'id');
    }
}

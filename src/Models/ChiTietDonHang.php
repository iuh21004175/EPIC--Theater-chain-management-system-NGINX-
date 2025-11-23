<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietDonHang extends Model
{
    protected $table = 'chitiet_donhang';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'donhang_id',
        'sanpham_id',
        'so_luong',
        'don_gia',
        'thanh_tien',
        'created_at',
        'updated_at'
    ];

    public function donHang()
    {
        return $this->belongsTo(DonHang::class, 'donhang_id', 'id');
    }

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'sanpham_id', 'id');
    }
}

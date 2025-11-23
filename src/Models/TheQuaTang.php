<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TheQuaTang extends Model
{
    protected $table = 'the_qua_tang';
    protected $primaryKey = 'id';
    public $timestamps = false; 

    protected $fillable = [
        'id',
        'khach_hang_id',
        'ten',
        'gia_tri',
        'id_donhang',
        'ma_code',
        'trang_thai',
        'ngay_phat_hanh',
        'ngay_het_han',
        'ghi_chu',
    ];

    public function khachHang()
    {
        return $this->belongsTo(KhachHang::class, 'khach_hang_id', 'id');
    }

    public function donHang()
    {
        return $this->belongsTo(DonHang::class, 'id_donhang', 'id');
    }
}

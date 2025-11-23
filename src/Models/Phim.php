<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Phim extends Model
{
    protected $table = 'phim';
    protected $primaryKey = 'id';
    public $timestamps = true; 

    protected $fillable = [
        'id',
        'ten_phim',
        'dao_dien',
        'dien_vien',
        'thoi_luong',
        'quoc_gia',
        'ngay_cong_chieu',
        'do_tuoi',
        'mo_ta',
        'poster_url',
        'trailer_url',
        'video_url',
        'trang_thai', // 1: Đang chiếu, 0: Ngừng chiếu
        'trang_thai_video', // 1: Đã xử lý xong, 2: Đang xử lý
    ];

    public function TheLoai() {
         return $this->hasMany(Phim_TheLoai::class, 'phim_id', 'id');
    }
    public function suatchieu() {
        return $this->hasMany(SuatChieu::class, 'id_phim', 'id');
    }
    public function phanPhoiPhim() {
        return $this->hasMany(PhanPhoiPhim::class, 'phim_id', 'id');
    }
}

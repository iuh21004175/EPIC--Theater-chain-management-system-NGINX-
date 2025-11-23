<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanhGia extends Model
{
    protected $table = 'danhgia';
    protected $primaryKey = 'id';
    public $timestamps = true; // Nếu muốn dùng created_at, updated_at thì để true

    protected $fillable = [
        'id',
        'phim_id',
        'khachhang_id',
        'so_sao',
        'cmt',
    ];

    public function phim()
    {
        return $this->belongsTo(Phim::class, 'phim_id', 'id');
    }

    public function khachHang()
    {
        return $this->belongsTo(KhachHang::class, 'khachhang_id', 'id');
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KhachHang extends Model
{
    protected $table = 'khach_hang';
    protected $primaryKey = 'id';
    public $timestamps = true; 

    protected $fillable = [
        'id',
        'ho_ten',
        'email',
        'gioi_tinh',
        'ngay_sinh',
        'so_dien_thoai',
        'mat_khau',
        'google_id',
        'created_at',
        'updated_at'
    ];
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TinTuc extends Model
{
    protected $table = 'tintuc';          
    protected $primaryKey = 'id';      
    public $timestamps = false;       

    protected $fillable = [
        'id_tac_gia',
        'tieu_de',
        'noi_dung',
        'anh_tin_tuc',
        'tac_gia',
        'trang_thai', // 0: Tin tức, 1: Chờ duyệt, 2: Đã duyệt, 3: Từ chối
        'created_at',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    public function tacGia()
    {
        return $this->belongsTo(NguoiDungInternal::class, 'id_tac_gia', 'id');
    }
}
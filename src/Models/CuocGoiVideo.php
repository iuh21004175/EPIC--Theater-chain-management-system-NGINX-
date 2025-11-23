<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class CuocGoiVideo extends Model{
        protected $table = 'cuoc_goi_video';
        protected $fillable = [
            'id',
            'id_khachhang',
            'id_nhanvien',// có thể null
            'chude',
            'batdau',
            'ketthuc',
            'trang_thai', // 0: chờ duyệt, 1: đã duyệt, 2: từ chối
            'ly_do_tu_choi', // có thể null
            'created_at',
            'updated_at'

        ];
    }
?>
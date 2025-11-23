<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class SanPham extends Model
    {
        protected $table = 'san_pham';
        protected $primaryKey = 'id';
        protected $fillable = [
            'ten', 
            'mo_ta', 
            'gia', 
            'hinh_anh', 
            'id_rapphim',
            'danh_muc_id',
            'trang_thai', //1: đang bán, 0: ngừng bán
            'created_at',
            'updated_at'
        ];
        public function danhMuc()
        {
            return $this->belongsTo(DanhMuc::class, 'danh_muc_id', 'id');
        }
        public function rap()
        {
            return $this->belongsTo(RapPhim::class, 'id_rapphim', 'id');
        }

    }
?>
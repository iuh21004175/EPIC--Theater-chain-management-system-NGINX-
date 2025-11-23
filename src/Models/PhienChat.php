<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class PhienChat extends Model {
        protected $table = 'phien_chat';
        protected $fillable = [
            'id', 
            'chu_de', 
            'id_rapphim',
            'id_khachhang',
            'trang_thai', //0 - Chờ khách hàng trả lời, 1 - Đang chờ nhân viên trả lời, 2 - Đã kết thúc 
            'dang_chat', // 0 - Không, 1 - Có (Nhân viên đang chat)
            'created_at',
            'updated_at'
        ];
        public function khachhang() {
            return $this->belongsTo(KhachHang::class, 'id_khachhang');
        }
        public function rapphim() {
            return $this->belongsTo(RapPhim::class, 'id_rapphim');
        }
        public function tinNhan() {
            return $this->hasMany(TinNhan::class, 'id_phienchat');
        }
    }
?>
<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class TinNhan extends Model {
        protected $table = 'tin_nhan';
        protected $fillable = [
            'id', 
            'id_phienchat', 
            'noi_dung',
            'loai_noi_dung', // 1 - Text, 2 - Hình ảnh
            'nguoi_gui', // 1 - Khách hàng, 2 - Nhân viên, null - Hệ thống
            'id_nhanvien', // Nếu người gửi là nhân viên thì lưu id nhân viên
            'trang_thai', // 0 - Chưa xem, 1 - Đã xem
            'created_at',
            'updated_at'
        ];
        public function phienChat() {
            return $this->belongsTo(PhienChat::class, 'id_phienchat');
        }
        public function nhanVien() {
            return $this->belongsTo(NguoiDungInternal::class, 'id_nhanvien');
        }
    }
?>
<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class NguoiDungInternal extends Model {
        protected $table = 'nguoidung_noibo';
        protected $primaryKey = 'id';
        protected $fillable = [
            'id',
            'id_taikhoan',
            'id_rapphim',
            'ten',
            'email',
            'dien_thoai',
            'trang_thai', // 1: Đang hoạt động, 0: Đã khóa, -1: Đã nghỉ việc (nhân viên)
            'created_at',
            'updated_at'
        ];
        
        
        public function taiKhoan() {
            return $this->belongsTo(TaiKhoanInternal::class, 'id_taikhoan', 'id');
        }
        public function rapPhim() {
            return $this->belongsTo(RapPhim::class, 'id_rapphim', 'id');
        }
        public function phanCongs() {
            return $this->hasMany(PhanCong::class, 'id_nhanvien', 'id');
        }
    }
?>
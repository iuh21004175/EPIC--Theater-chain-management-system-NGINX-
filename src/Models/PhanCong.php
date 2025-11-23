<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class PhanCong extends Model{
        protected $table = 'phan_cong';
        protected $primaryKey = 'id';
        public $timestamps = false;
        protected $fillable = [
            'id',
            'id_nhanvien',
            'id_congviec',
            'ngay',
            'ca',
            'gio_vao',
            'gio_ra',
            'ly_do', 
            'trang_thai', // 0: Lịch làm, 1: Chờ duyệt (xin nghỉ), 2: Đã duyệt nghỉ, 3: Từ chối
            'created_at',
            'updated_at'
        ];
        public function nhanVien(){
            return $this->belongsTo(NguoiDungInternal::class, 'id_nhanvien', 'id');
        }
        public function congViec(){
            return $this->belongsTo(ViTriCongViec::class, 'id_congviec', 'id');
        }
    }
?>
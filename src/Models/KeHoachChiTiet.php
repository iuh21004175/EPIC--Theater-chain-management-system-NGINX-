<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    use App\Models\KeHoachSuatChieu;
    class KeHoachChiTiet extends Model {
        protected $table = 'kehoach_chitiet';
        protected $primaryKey = 'id';
        protected $fillable = [
            'id',
            'id_kehoach',
            'id_phim',
            'id_phongchieu',
            'batdau',
            'ketthuc',
            'tinh_trang', // 0 - Chờ duyệt, 1 - Đã duyệt, 2 - Từ chối
            'created_at',
            'updated_at'
        ];
        public function keHoach() {
            return $this->belongsTo(KeHoachSuatChieu::class, 'id_kehoach');
        }
        public function phim() {
            return $this->belongsTo(Phim::class, 'id_phim');
        }
        public function phongChieu() {
            return $this->belongsTo(PhongChieu::class, 'id_phongchieu');
        }   
    }
?>
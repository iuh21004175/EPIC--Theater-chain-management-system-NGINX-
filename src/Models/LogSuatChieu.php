<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class LogSuatChieu extends Model {
        protected $table = 'log_suatchieu';
        protected $primaryKey = 'id';
        protected $fillable = [
            'id',
            'id_suatchieu',
            'hanh_dong', // 0 - Tạo, 1 - Cập nhật, 2 - Xóa, 3 - Duyệt, 4 - Từ chối, 5 - Duyệt từ kế hoạch
            'batdau',
            'id_phim',
            'ten_phim',
            'da_xem', // 0 - Chưa xem, 1 - Đã xem (Quản lý chuỗi rạp)
            'rap_da_xem', // 0 - Chưa xem, 1 - Đã xem (Quản lý rạp) 
            'created_at',
            'updated_at'
        ];
        public function suatChieu() {
            return $this->belongsTo(SuatChieu::class, 'id_suatchieu');
        }
        public function phim() {
            return $this->belongsTo(Phim::class, 'id_phim');
        }
    }
?>
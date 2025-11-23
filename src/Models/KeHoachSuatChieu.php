<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class KeHoachSuatChieu extends Model {
        protected $table = 'kehoach_suatchieu';
        protected $primaryKey = 'id';
        protected $fillable = [
            'id',
            'batdau',
            'ketthuc',
            'created_at',
            'updated_at'
        ];
        // trạng thái: 0 - Chưa hoản thàn, 1 - Đã hoàn thành - không cần thiết, chúng ta sẽ xét đến thứ 7 thì kế hoạch của quản lý rạp mặc định là hoàn thành không được phép chỉnh sửa.
        public function keHoachChiTiet() {
            return $this->hasMany(KeHoachChiTiet::class, 'id_kehoach');
        }
    }
?>
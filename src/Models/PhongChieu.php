<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class PhongChieu extends Model {
        protected $table = 'phongchieu';
        protected $primaryKey = 'id';
        protected $fillable = [
            'id',
            'ten',
            'ma_phong',
            'mo_ta',
            'loai_phongchieu',
            'trang_thai',
            'sohang_ghe',
            'socot_ghe',
            'so_luong_ghe',
            'trang_thai',
            'id_rapphim'
        ];
        public function rapChieuPhim() {
            return $this->belongsTo(RapPhim::class, 'id_rapphim', 'id');
        }
        public function soDoGhe() {
            return $this->hasMany(SoDoGhe::class, 'phongchieu_id', 'id');
        }
        public function suatChieu() {
            return $this->hasMany(SuatChieu::class, 'id_phongchieu', 'id');
        }
        public function capNhatSoLuongGhe() {
            $this->so_luong_ghe = $this->soDoGhe()->whereNotNull('loaighe_id')->count();
            $this->save();
        }
    }
?>
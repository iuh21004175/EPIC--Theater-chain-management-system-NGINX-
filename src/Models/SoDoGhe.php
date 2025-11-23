<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class SoDoGhe extends Model {
        protected $table = 'sodo_ghe';
        protected $primaryKey = 'id';
        protected $fillable = [
            'id',
            'so_ghe',
            'loaighe_id',
            'phongchieu_id',
            'created_at',
            'updated_at'
        ];
        public function loaiGhe() {
            return $this->belongsTo(Ghe::class, 'loaighe_id', 'id');
        }
        public function phongChieu() {
            return $this->belongsTo(PhongChieu::class, 'phongchieu_id', 'id');
        }
    }
?>
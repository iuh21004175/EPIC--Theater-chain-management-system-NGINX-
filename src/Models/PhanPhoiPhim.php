<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class PhanPhoiPhim extends Model {
        protected $table = 'phanphoi_phim';
        protected $primaryKey = 'id';
        protected $fillable = [
            'id',
            'id_phim',
            'id_rapphim',
            'created_at',
            'updated_at'
        ];
        public function phim() {
            return $this->belongsTo(Phim::class, 'id_phim');
        }
        public function rapPhim() {
            return $this->belongsTo(RapPhim::class, 'id_rapphim');
        }
    }
?>
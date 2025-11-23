<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;

    class VaiTro extends Model {
        protected $table = 'vaitro';
        protected $primaryKey = 'id';
        public $timestamps = false;
        protected $fillable = [
            'id',
            'ten'
        ];
        public function taiKhoanInternals() {
            return $this->hasMany(TaiKhoanInternal::class, 'id_vaitro', 'id');
        }
    }
?>
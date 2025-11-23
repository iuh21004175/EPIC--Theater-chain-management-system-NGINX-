<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class Ghe extends Model{
        protected $table = 'loaighe';
        protected $fillable = 
        [
            'id',
            'ten',
            'mo_ta',
            'ma_mau',
            'created_at',
            'updated_at'
        ];
        public function soDoGhe() {
            return $this->hasMany(SoDoGhe::class, 'loaighe_id', 'id');
        }
    }
?>
<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;

    class DanhMuc extends Model{
        protected $table = 'danhmuc';
        protected $primaryKey = 'id';
        protected $fillable = [
            'id',
            'ten', 
            'soluong',
            'created_at',
            'updated_at'
        ];
        public function sanPhams()
        {
            return $this->hasMany(SanPham::class, 'danh_muc_id', 'id');
        }
    }
?>
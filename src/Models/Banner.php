<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class Banner extends Model {
        protected $table = 'banner';
        protected $primaryKey = 'id';
        protected $fillable = [
            'id',
            'anh_url',
            'thu_tu',
            'trang_thai', // 1: Hiển thị, 0: Ẩn
            'created_at',
            'updated_at'
        ];
    }
?>
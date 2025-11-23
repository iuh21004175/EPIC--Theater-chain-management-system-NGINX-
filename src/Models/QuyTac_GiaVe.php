<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class QuyTac_GiaVe extends Model {
        protected $table = 'quytac_giave';
        protected $primaryKey = 'id';
        protected $fillable = [
            'ten',
            'loai_hanhdong', // 'Thiết lập giá' hoặc 'Cộng thêm tiền'
            'gia_tri',
            'dieu_kien',
            'trang_thai',
            'do_uu_tien', // Độ ưu tiên từ 1 đến 5 với 1 là cao nhất
            'created_at',
            'updated_at'
        ];
    }
?>
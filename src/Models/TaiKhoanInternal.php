<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class TaiKhoanInternal extends Model {
        protected $table = 'taikhoan_noibo';
        protected $primaryKey = 'id';
        protected $fillable = [
            'id',
            'tendangnhap',
            'matkhau_bam',
            'id_vaitro',
            'created_at',
            'updated_at'
        ];
        
        /**
         * The attributes that are unique.
         *
         * @var array
         */
        protected $unique = ['tendangnhap', 'id_vaitro'];
        
        public function vaiTro() {
            return $this->belongsTo(VaiTro::class, 'id_vaitro', 'id');
        }
        public function nguoiDungInternals() {
            return $this->hasOne(NguoiDungInternal::class, 'id_taikhoan', 'id');
        }
    }
?>
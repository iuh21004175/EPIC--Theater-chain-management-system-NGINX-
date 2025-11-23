<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;
    class Ngay extends Model
    {
        protected $table = 'ngay_dacbiet';
        protected $primaryKey = 'id';
        protected $fillable = [
            'id',
            'ngay',
            'loai_ngay',// Ngày lễ, ngày tết
            'dac_biet',
            'created_at',
            'updated_at'
        ];

    }
?>
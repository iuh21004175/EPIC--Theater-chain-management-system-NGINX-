<?php
    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    class DinhVi extends Model
    {
        protected $table = 'wifi_dinhvi';
        protected $primaryKey = 'id';
        protected $fillable = [
            'id', 
            'id_rapphim', 
            'wifi_ip', 
            'wifi_ten'
        ];
    }
?>
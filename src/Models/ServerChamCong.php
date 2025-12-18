<?php
    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    class ServerChamCong extends Model
    {
        protected $table = 'server_chamcong';
        protected $primaryKey = 'id';
        protected $fillable = [
            'id', 
            'id_rapphim', 
            'wifi_ip', 
            'wifi_ten',
            'server_port'
        ];
        public $timestamps = false;
    }
?>
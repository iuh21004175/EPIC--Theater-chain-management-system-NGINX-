<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResetToken extends Model
{
    protected $table = 'reset_token';  
    protected $primaryKey = 'id';       
    public $timestamps = false;          

    protected $fillable = [
        'id',
        'khach_hang_id',
        'token',
        'expire_at',
        'created_at'
    ];

    public function KhachHang() {
        return $this->belongsTo(KhachHang::class, 'khach_hang_id', 'id');
    }
}

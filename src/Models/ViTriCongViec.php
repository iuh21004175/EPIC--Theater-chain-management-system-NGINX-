<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ViTriCongViec extends Model
{
    protected $table = 'vitri_congviec';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'ten',
        'id_rapphim',
        'created_at',
        'updated_at'
    ];
    public function rapPhim()
    {
        return $this->belongsTo(RapPhim::class, 'id_rapphim');
    }
    public function phanCongs()
    {
        return $this->hasMany(PhanCong::class, 'id_congviec', 'id');
    }
}

?>
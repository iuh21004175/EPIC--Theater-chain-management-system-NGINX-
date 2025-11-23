<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TheLoai extends Model
{
    protected $table = 'theloai';
    protected $primaryKey = 'id';
    public $timestamps = true; 

    protected $fillable = [
        'id',
        'ten',
        'so_phim',
        'created_at',
        'updated_at'
    ];

    public function Phim() {
         return $this->hasMany(Phim_TheLoai::class, 'theloai_id', 'id');
    }
}

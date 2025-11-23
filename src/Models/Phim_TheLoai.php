<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Phim_TheLoai extends Model
{
    protected $table = 'phim_theloai';
    protected $primaryKey = 'id';
    public $timestamps = true; 

    protected $fillable = [
        'id',
        'phim_id',
        'theloai_id'
    ];

    public function Phim() {
            return $this->belongsTo(Phim::class, 'phim_id', 'id');
        }
    public function TheLoai() {
         return $this->belongsTo(TheLoai::class, 'theloai_id', 'id');
    }

}

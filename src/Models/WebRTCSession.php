<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebRTCSession extends Model
{
    protected $table = 'webrtc_sessions';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'id_lich_goi_video',
        'room_id',
        'peer_id_khachhang',
        'peer_id_nhanvien',
        'trang_thai'
    ];

    // Quan hệ với LichGoiVideo
    public function lichGoiVideo()
    {
        return $this->belongsTo(LichGoiVideo::class, 'id_lich_goi_video');
    }

    // Các trạng thái
    const TRANG_THAI_CHO = 1;
    const TRANG_THAI_KET_NOI = 2;
    const TRANG_THAI_NGAT = 3;
}

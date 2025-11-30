<?php
namespace App\Services;

use App\Models\LichGoiVideo;
use App\Models\WebRTCSession;
use function App\Core\getRedisConnection;

class Sc_GoiVideo {
    private $redis;

    public function __construct() {
        $this->redis = getRedisConnection();
    }

    // Khách hàng đặt lịch gọi video
    public function khachHangDatLichGoiVideo() {
        $idKhachHang = $_SESSION['user']['id'];
        $idRapPhim = $_POST['id_rapphim'] ?? null;
        $chuDe = $_POST['chu_de'] ?? null;
        $moTa = $_POST['mo_ta'] ?? null;
        $thoiGianDat = $_POST['thoi_gian_dat'] ?? null;

        if (!$idRapPhim || !$chuDe || !$thoiGianDat) {
            throw new \Exception("Vui lòng điền đầy đủ thông tin");
        }

        try {
            $lichGoiVideo = LichGoiVideo::create([
                'id_khachhang' => $idKhachHang,
                'id_rapphim' => $idRapPhim,
                'chu_de' => $chuDe,
                'mo_ta' => $moTa,
                'thoi_gian_dat' => $thoiGianDat,
                'trang_thai' => LichGoiVideo::TRANG_THAI_CHO_NHAN_VIEN
            ]);

            // Publish sự kiện để thông báo cho nhân viên rạp đó
            $this->redis->publish('lichgoivideo:moi', json_encode([
                'id_lich' => $lichGoiVideo->id,
                'id_rapphim' => $idRapPhim,
                'id_khachhang' => $idKhachHang,
                'chu_de' => $chuDe,
                'thoi_gian_dat' => $thoiGianDat
            ]));

            return $lichGoiVideo;
        } catch (\Exception $e) {
            throw new \Exception("Không thể đặt lịch: " . $e->getMessage());
        }
    }

    // Nhân viên lấy danh sách lịch gọi video chờ tư vấn
    public function nhanVienLayDanhSachLichCho() {
        $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];

        $danhSach = LichGoiVideo::where('id_rapphim', $idRapPhim)
            ->whereIn('trang_thai', [
                LichGoiVideo::TRANG_THAI_CHO_NHAN_VIEN,
                LichGoiVideo::TRANG_THAI_DA_CHON_NV,
                LichGoiVideo::TRANG_THAI_DANG_GOI  // ✅ Thêm trạng thái ĐANG GỌI để nhân viên thấy và có thể tham gia
            ])
            ->with(['khachhang', 'nhanvien'])
            ->orderBy('thoi_gian_dat', 'asc')
            ->get();

        return $danhSach;
    }

    // Nhân viên chọn tư vấn cho khách hàng (claim phiên)
    public function nhanVienChonTuVan($idLich) {
        $idNhanVien = $_SESSION['UserInternal']['ID'];
        $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];

        try {
            $lich = LichGoiVideo::where('id', $idLich)
                ->where('id_rapphim', $idRapPhim)
                ->where('trang_thai', LichGoiVideo::TRANG_THAI_CHO_NHAN_VIEN)
                ->first();

            if (!$lich) {
                throw new \Exception("Lịch không tồn tại hoặc đã được nhân viên khác chọn");
            }

            // Tạo room ID duy nhất
            $roomId = 'video_' . $idLich . '_' . time();

            // Cập nhật lịch với nhân viên và room ID
            $lich->update([
                'id_nhanvien' => $idNhanVien,
                'room_id' => $roomId,
                'trang_thai' => LichGoiVideo::TRANG_THAI_DA_CHON_NV
            ]);

            // Tạo WebRTC session
            WebRTCSession::create([
                'id_lich_goi_video' => $idLich,
                'room_id' => $roomId,
                'trang_thai' => WebRTCSession::TRANG_THAI_CHO
            ]);

            // Lưu thông tin vào Redis để xác thực kết nối
            $this->redis->setex("videoroom:$roomId", 86400, json_encode([
                'id_lich' => $idLich,
                'id_nhanvien' => $idNhanVien,
                'id_khachhang' => $lich->id_khachhang,
                'created_at' => time()
            ]));

            // Publish sự kiện để thông báo cho khách hàng
            $this->redis->publish('lichgoivideo:dachon', json_encode([
                'id_lich' => $idLich,
                'id_khachhang' => $lich->id_khachhang,
                'id_nhanvien' => $idNhanVien,
                'room_id' => $roomId
            ]));

            return [
                'lich' => $lich,
                'room_id' => $roomId
            ];
        } catch (\Exception $e) {
            throw new \Exception("Không thể chọn tư vấn: " . $e->getMessage());
        }
    }

    // Kiểm tra quyền tham gia room (gọi từ Socket.IO)
    public function kiemTraQuyenThamGiaRoom($roomId, $userId, $userType) {
        // Lấy thông tin room từ Redis
        $roomData = $this->redis->get("videoroom:$roomId");
        
        if (!$roomData) {
            return [
                'allowed' => false,
                'reason' => 'Room không tồn tại hoặc đã hết hạn'
            ];
        }

        $roomInfo = json_decode($roomData, true);

        // Kiểm tra quyền dựa vào user type
        if ($userType === 'customer') {
            // Khách hàng: phải đúng khách hàng đặt lịch
            if ($userId != $roomInfo['id_khachhang']) {
                return [
                    'allowed' => false,
                    'reason' => 'Bạn không có quyền tham gia cuộc gọi này'
                ];
            }
        } elseif ($userType === 'staff') {
            // Nhân viên: phải đúng nhân viên được chọn
            if ($userId != $roomInfo['id_nhanvien']) {
                return [
                    'allowed' => false,
                    'reason' => 'Cuộc gọi này đã được nhân viên khác nhận'
                ];
            }
        } else {
            return [
                'allowed' => false,
                'reason' => 'Loại người dùng không hợp lệ'
            ];
        }

        return [
            'allowed' => true,
            'room_info' => $roomInfo
        ];
    }

    // Bắt đầu cuộc gọi
    public function batDauCuocGoi($roomId) {
        $roomData = $this->redis->get("videoroom:$roomId");
        
        if (!$roomData) {
            throw new \Exception("Room không tồn tại");
        }

        $roomInfo = json_decode($roomData, true);
        $idLich = $roomInfo['id_lich'];

        $lich = LichGoiVideo::find($idLich);
        if (!$lich) {
            throw new \Exception("Lịch không tồn tại");
        }

        // Cập nhật trạng thái
        $lich->update([
            'trang_thai' => LichGoiVideo::TRANG_THAI_DANG_GOI,
            'thoi_gian_bat_dau' => date('Y-m-d H:i:s')
        ]);

        // Cập nhật WebRTC session
        $session = WebRTCSession::where('room_id', $roomId)->first();
        if ($session) {
            $session->update([
                'trang_thai' => WebRTCSession::TRANG_THAI_KET_NOI
            ]);
        }

        return $lich;
    }

    // Kết thúc cuộc gọi
    public function ketThucCuocGoi($roomId) {
        $roomData = $this->redis->get("videoroom:$roomId");
        
        if (!$roomData) {
            throw new \Exception("Room không tồn tại");
        }

        $roomInfo = json_decode($roomData, true);
        $idLich = $roomInfo['id_lich'];

        $lich = LichGoiVideo::find($idLich);
        if (!$lich) {
            throw new \Exception("Lịch không tồn tại");
        }

        // Cập nhật trạng thái
        $lich->update([
            'trang_thai' => LichGoiVideo::TRANG_THAI_HOAN_THANH,
            'thoi_gian_ket_thuc' => date('Y-m-d H:i:s')
        ]);

        // Cập nhật WebRTC session
        $session = WebRTCSession::where('room_id', $roomId)->first();
        if ($session) {
            $session->update([
                'trang_thai' => WebRTCSession::TRANG_THAI_NGAT
            ]);
        }

        // Xóa room khỏi Redis
        $this->redis->del("videoroom:$roomId");

        return $lich;
    }

    // Khách hàng kiểm tra trạng thái lịch của mình
    public function khachHangKiemTraTrangThai($idLich) {
        $idKhachHang = $_SESSION['user']['id'];

        $lich = LichGoiVideo::where('id', $idLich)
            ->where('id_khachhang', $idKhachHang)
            ->with(['nhanvien', 'webrtcSession'])
            ->first();

        if (!$lich) {
            throw new \Exception("Lịch không tồn tại");
        }

        return $lich;
    }

    // Nhân viên từ chối hoặc hủy tư vấn
    public function nhanVienHuyTuVan($idLich) {
        $idNhanVien = $_SESSION['UserInternal']['ID'];

        $lich = LichGoiVideo::where('id', $idLich)
            ->where('id_nhanvien', $idNhanVien)
            ->first();

        if (!$lich) {
            throw new \Exception("Lịch không tồn tại hoặc bạn không có quyền hủy");
        }

        // Nếu đã có room_id, xóa khỏi Redis
        if ($lich->room_id) {
            $this->redis->del("videoroom:{$lich->room_id}");
        }

        // Reset trạng thái về chờ nhân viên
        $lich->update([
            'id_nhanvien' => null,
            'room_id' => null,
            'trang_thai' => LichGoiVideo::TRANG_THAI_CHO_NHAN_VIEN
        ]);

        // Publish sự kiện
        $this->redis->publish('lichgoivideo:huy', json_encode([
            'id_lich' => $idLich,
            'id_khachhang' => $lich->id_khachhang
        ]));

        return $lich;
    }

    // Khách hàng lấy danh sách lịch gọi video theo ngày
    public function khachHangLayLichTheoNgay($ngay) {
        $idKhachHang = $_SESSION['user']['id'];

        // Validate date format
        $dateObj = \DateTime::createFromFormat('Y-m-d', $ngay);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $ngay) {
            throw new \Exception("Định dạng ngày không hợp lệ. Vui lòng sử dụng YYYY-MM-DD");
        }

        // Lấy danh sách lịch của khách hàng trong ngày
        $danhSach = LichGoiVideo::where('id_khachhang', $idKhachHang)
            ->whereDate('thoi_gian_dat', $ngay)
            ->with(['rapphim', 'nhanvien'])
            ->orderBy('thoi_gian_dat', 'asc')
            ->get();

        // Format dữ liệu trả về
        $result = $danhSach->map(function($lich) {
            $trangThai = '';
            switch($lich->trang_thai) {
                case LichGoiVideo::TRANG_THAI_CHO_NHAN_VIEN:
                    $trangThai = 'Chờ xác nhận';
                    break;
                case LichGoiVideo::TRANG_THAI_DA_CHON_NV:
                    $trangThai = 'Đã xác nhận';
                    break;
                case LichGoiVideo::TRANG_THAI_DANG_GOI:
                    $trangThai = 'Đang gọi';
                    break;
                case LichGoiVideo::TRANG_THAI_HOAN_THANH:
                    $trangThai = 'Hoàn thành';
                    break;
                case LichGoiVideo::TRANG_THAI_HUY:
                    $trangThai = 'Đã hủy';
                    break;
            }

            return [
                'id' => $lich->id,
                'gio' => date('H:i', strtotime($lich->thoi_gian_dat)),
                'ten_rap' => $lich->rapphim ? $lich->rapphim->ten : 'Không xác định',
                'noi_dung' => $lich->chu_de,
                'mo_ta' => $lich->mo_ta,
                'trang_thai' => $trangThai,
                'trang_thai_code' => $lich->trang_thai,
                'nhan_vien' => $lich->nhanvien ? $lich->nhanvien->ten : null,
                'room_id' => $lich->room_id,
                'thoi_gian_dat' => $lich->thoi_gian_dat,
                'thoi_gian_bat_dau' => $lich->thoi_gian_bat_dau,
                'thoi_gian_ket_thuc' => $lich->thoi_gian_ket_thuc
            ];
        });

        return $result;
    }

    // Đặt lịch gọi video (endpoint mới với format đơn giản)
    public function datLichGoiVideo() {
        // Lấy user ID từ session
        if (!isset($_SESSION['user']['id'])) {
            throw new \Exception("Vui lòng đăng nhập để đặt lịch");
        }
        
        $idKhachHang = $_SESSION['user']['id'];
        
        // Lấy dữ liệu từ POST request
        $data = json_decode(file_get_contents('php://input'), true);
        
        $idRap = $data['id_rap'] ?? null;
        $ngay = $data['ngay'] ?? null;
        $gio = $data['gio'] ?? null;
        $noiDung = $data['noi_dung'] ?? null;
        $soDienThoai = $data['so_dien_thoai'] ?? null;

        // Validate dữ liệu
        if (!$idRap) {
            throw new \Exception("Vui lòng chọn rạp chiếu phim");
        }

        if (!$ngay) {
            throw new \Exception("Vui lòng chọn ngày");
        }

        if (!$gio) {
            throw new \Exception("Vui lòng chọn giờ");
        }

        if (!$noiDung) {
            throw new \Exception("Vui lòng nhập nội dung tư vấn");
        }

        if (!$soDienThoai) {
            throw new \Exception("Vui lòng nhập số điện thoại");
        }

        // Validate format ngày
        $dateObj = \DateTime::createFromFormat('Y-m-d', $ngay);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $ngay) {
            throw new \Exception("Định dạng ngày không hợp lệ. Vui lòng sử dụng YYYY-MM-DD");
        }

        // Validate format giờ
        $timeObj = \DateTime::createFromFormat('H:i', $gio);
        if (!$timeObj || $timeObj->format('H:i') !== $gio) {
            throw new \Exception("Định dạng giờ không hợp lệ. Vui lòng sử dụng HH:mm");
        }

        // Kết hợp ngày và giờ
        $thoiGianDat = $ngay . ' ' . $gio . ':00';

        // Kiểm tra thời gian đặt không được trong quá khứ
        if (strtotime($thoiGianDat) < time()) {
            throw new \Exception("Không thể đặt lịch trong quá khứ");
        }

        try {
            // Tạo lịch gọi video mới
            $lichGoiVideo = LichGoiVideo::create([
                'id_khachhang' => $idKhachHang,
                'id_rapphim' => $idRap,
                'chu_de' => $noiDung,
                'mo_ta' => 'Số điện thoại: ' . $soDienThoai,
                'thoi_gian_dat' => $thoiGianDat,
                'trang_thai' => LichGoiVideo::TRANG_THAI_CHO_NHAN_VIEN
            ]);

            // Publish sự kiện để thông báo cho nhân viên rạp đó
            $this->redis->publish('lichgoivideo:moi', json_encode([
                'id_lich' => $lichGoiVideo->id,
                'id_rapphim' => $idRap,
                'id_khachhang' => $idKhachHang,
                'chu_de' => $noiDung,
                'thoi_gian_dat' => $thoiGianDat,
                'so_dien_thoai' => $soDienThoai
            ]));

            return [
                'id' => $lichGoiVideo->id,
                'id_rap' => $idRap,
                'ngay' => $ngay,
                'gio' => $gio,
                'noi_dung' => $noiDung,
                'so_dien_thoai' => $soDienThoai,
                'thoi_gian_dat' => $thoiGianDat,
                'trang_thai' => 'Chờ xác nhận'
            ];
        } catch (\Exception $e) {
            throw new \Exception("Không thể đặt lịch: " . $e->getMessage());
        }
    }
    public function layThongTinPhongGoiVideo($roomId) {
        $roomData = LichGoiVideo::with('khachhang', 'nhanvien')
            ->where('room_id', $roomId)
            ->first();
        return $roomData;
    }
}

<?php
namespace App\Services;

use App\Models\PhanCong;
use App\Models\DangKyKhuonMat;
use App\Models\NguoiDungInternal;
use Exception;

class Sc_ChamCong
{
    /**
     * Kiểm tra trạng thái đăng ký khuôn mặt
     */
    public function kiemTraDangKy()
    {
        $idNhanVien = $_SESSION['UserInternal']['ID'] ?? null;
        if (!$idNhanVien) {
            throw new Exception('Không xác định được nhân viên');
        }

        $dangKy = DangKyKhuonMat::where('id_nhanvien', $idNhanVien)
            ->where('trang_thai', 'Đang hoạt động')
            ->latest()
            ->first();

        if (!$dangKy) {
            throw new Exception('Chưa đăng ký khuôn mặt');
        }
        return $dangKy;
    }
    public function lichSuChamCong(){
        $idNhanVien = $_SESSION['UserInternal']['ID'] ?? null;
        if (!$idNhanVien) {
            throw new Exception('Không xác định được nhân viên');
        }

        // Tính ngày bắt đầu và kết thúc
        $ngayKetThuc = date('Y-m-d'); // hôm nay
        $ngayBatDau = date('Y-m-d', strtotime('-7 days')); // 7 ngày trước

        // Lấy dữ liệu chấm công trong khoảng 7 ngày
        $lichSu = PhanCong::where('id_nhanvien', $idNhanVien)
            ->whereBetween('ngay', [$ngayBatDau, $ngayKetThuc])
            ->orderBy('ngay', 'desc')
            ->get();

        return $lichSu;
    }
    public function dangKyKhuonMat()
    {
        $idNhanVien = $_SESSION['UserInternal']['ID'] ?? null;
        if (!$idNhanVien) {
            throw new Exception('Thiếu id nhân viên');
        }
        
        $videoPath = $_FILES['video']['tmp_name'] ?? null;
        if (!$videoPath) {
            throw new Exception('Thiếu file video tải lên');
        }
        
        $envPython = $_ENV['PYTHON_PATH'] ?? 'python3';
        $filePython = __DIR__ . '/../../bin/python/face.py';
        $fileLog = __DIR__ . '/../../cache/log/face_register.log';
        
        $command = escapeshellcmd("$envPython $filePython $videoPath $idNhanVien register");
        
        // Thực thi lệnh và chuyển hướng đầu ra lỗi vào file log
        exec("$command 2>> $fileLog", $output, $returnVar);
        
        if ($returnVar != 0) {
            throw new Exception('Lỗi khi gọi Đăng ký khuôn mặt. Xem log để biết thêm chi tiết.');
        }
        
        // Ghi log đầu ra từ Python
        file_put_contents($fileLog, implode("\n", $output) . "\n", FILE_APPEND);
        
        // Phân tích kết quả trả về từ Python
        $result = implode("\n", $output);
        
        // Kiểm tra kết quả đăng ký thành công
        if (strpos($result, 'Face registration SUCCESSFUL') === false) {
            // Kiểm tra lỗi giả mạo khuôn mặt
            if (strpos($result, 'Liveness check failed') !== false || strpos($result, 'Liveness FAILED') !== false || strpos($result, 'Spoofing detected') !== false) {
                throw new Exception('⚠️ Phát hiện giả mạo khuôn mặt! Hệ thống nhận diện bạn đang sử dụng ảnh/video giả. Vui lòng sử dụng khuôn mặt thật để đăng ký.');
            } else {
                throw new Exception('Đăng ký khuôn mặt thất bại. Vui lòng kiểm tra chất lượng video và thử lại.');
            }
        }
        
        $dangKyKhuonMat = DangKyKhuonMat::where('id_nhanvien', $idNhanVien)->first();
        if ($dangKyKhuonMat) {
            $dangKyKhuonMat->update([
                'ngay_dang_ky' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $created = DangKyKhuonMat::create([
                'id_nhanvien' => $idNhanVien,
                'ngay_dang_ky' => date('Y-m-d H:i:s'),
                'trang_thai' => 'Đang hoạt động'
            ]);
            if (!$created) {
                throw new Exception('Lỗi lưu thông tin đăng ký khuôn mặt');
            }
        }
        
        return ['success' => true, 'message' => 'Đăng ký khuôn mặt thành công'];
    }
    public function decodeToken($token){
        // Tách token thành 3 phần: header.payload.signature
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return [
                'error' => true,
                'message' => 'Invalid token format'
            ];
        }
        
        list($headerB64, $payloadB64, $signatureB64) = $parts;
        
        // Giải mã header và payload
        $header = $this->base64UrlDecode($headerB64);
        $payload = $this->base64UrlDecode($payloadB64);
        
        // Parse JSON
        $headerData = json_decode($header, true);
        $payloadData = json_decode($payload, true);
        
        if (!$headerData || !$payloadData) {
            return [
                'error' => true,
                'message' => 'Invalid JSON in token'
            ];
        }
        
        // Kiểm tra algorithm
        if (!isset($headerData['alg']) || $headerData['alg'] !== 'HS256') {
            return [
                'error' => true,
                'message' => 'Unsupported algorithm: ' . ($headerData['alg'] ?? 'none')
            ];
        }
        
        // Xác thực chữ ký
        $signature = $this->base64UrlDecode($signatureB64);
        $expectedSignature = $this->sign($headerB64 . '.' . $payloadB64);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return [
                'error' => true,
                'message' => 'Invalid signature'
            ];
        }
        
        // Kiểm tra thời gian hết hạn
        if (isset($payloadData['exp'])) {
            if (time() > $payloadData['exp']) {
                return [
                    'error' => true,
                    'message' => 'Token expired',
                    'expired_at' => date('Y-m-d H:i:s', $payloadData['exp'])
                ];
            }
        }
        
        // Kiểm tra thời gian bắt đầu có hiệu lực
        if (isset($payloadData['nbf'])) {
            if (time() + 2 < $payloadData['nbf']) {
                return [
                    'error' => true,
                    'message' => 'Token not yet valid',
                    'valid_from' => date('Y-m-d H:i:s', $payloadData['nbf'])
                ];
            }
        }
        
        // Trả về payload đã được xác thực
        return [
            'error' => false,
            'data' => $payloadData,
            'header' => $headerData
        ];
    }
    /**
     * Tạo chữ ký HMAC-SHA256
     */
    private function sign($data)
    {
        return hash_hmac('sha256', $data, $_ENV['GPS_SECRET_KEY'], true);
    }
    
    /**
     * Giải mã Base64 URL-safe
     */
    private function base64UrlDecode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
    public function parseGPSData($payload)
    {
        // Kiểm tra dữ liệu GPS có thể nằm trong $payload['data'] hoặc trực tiếp trong $payload
        $data = null;
        if (isset($payload['data']) && is_array($payload['data'])) {
            // Trường hợp 1: Dữ liệu nằm trong payload['data']
            $data = $payload['data'];
        } elseif (isset($payload['status']) || isset($payload['latitude']) || isset($payload['longitude'])) {
            // Trường hợp 2: Dữ liệu nằm trực tiếp trong payload
            $data = $payload;
        }
        
        if (!$data) {
            return null;
        }
        
        return [
            'status' => $data['status'] ?? 'unknown',
            'latitude' => floatval($data['latitude'] ?? 0),
            'longitude' => floatval($data['longitude'] ?? 0),
            'accuracy' => floatval($data['accuracy'] ?? 0),
            'altitude' => floatval($data['altitude'] ?? 0),
            'timestamp' => $data['timestamp'] ?? null,
            'google_maps_url' => $data['google_maps_url'] ?? null
        ];
    }
    public function chamCongKhuonMat()
    {
        // 1. Validate dữ liệu đầu vào
        $idNhanVien = $_SESSION['UserInternal']['ID'] ?? null;
        if (!$idNhanVien) {
            throw new Exception('Thiếu id nhân viên');
        }
        
        $loai = $_POST['loai'] ?? null;
        if (!$loai || !in_array($loai, ['checkin', 'checkout'])) {
            throw new Exception('Loại chấm công không hợp lệ. Chỉ chấp nhận "checkin" hoặc "checkout"');
        }
        
        // Kiểm tra nhân viên đã đăng ký khuôn mặt chưa
        $dangKyKhuonMat = DangKyKhuonMat::where('id_nhanvien', $idNhanVien)
            ->where('trang_thai', 'Đang hoạt động')
            ->first();
        if (!$dangKyKhuonMat) {
            throw new Exception('Bạn chưa đăng ký khuôn mặt. Vui lòng đăng ký trước khi chấm công.');
        }
        $token = $_POST['token'] ?? null;
        if (!$token) {
            throw new Exception('Thiếu token');
        }
        $payload = $this->decodeToken($token);
        if ($payload['error']) {
            throw new Exception($payload['message']);
        }
        $gpsData = $this->parseGPSData($payload['data']);
        if (!$gpsData) {
            // Log để debug
            error_log('GPS Data parse failed. Payload: ' . json_encode($payload));
            throw new Exception('Không tìm thấy dữ liệu GPS. Payload: ' . json_encode($payload['data'] ?? []));
        }
        if ($gpsData['status'] != 'success') {
            throw new Exception('Hệ thống đang cập nhật wifi vui lòng thử lại sau.');
        }
        $kinhDoNhanVien = $gpsData['longitude'];
        $viDoNhanVien = $gpsData['latitude'];
        $scRapPhim = new Sc_RapPhim();
        $rapPhim = $scRapPhim->docTheoID($_SESSION['UserInternal']['ID_RapPhim']);
        // Khoản cách chấp nhận chấm công là 100m so với tọa độ rạp phim
        // Tính khoảng cách giữa tọa độ nhân viên và tọa độ rạp phim
        $khoangCach = $this->tinhKhoangCach($kinhDoNhanVien, $viDoNhanVien, $rapPhim->kinh_do, $rapPhim->vi_do);
        
        // Ghi log khoảng cách
        $fileLog = __DIR__ . '/../../cache/log/face_checkin.log';
        if($loai == 'checkout'){
            $fileLog = __DIR__ . '/../../cache/log/face_checkout.log';
        }
        $logKhoangCach = date('Y-m-d H:i:s') . " - ID: $idNhanVien - Loại: $loai\n";
        $logKhoangCach .= "Khoảng cách: " . number_format($khoangCach, 2) . " mét\n";
        $logKhoangCach .= "Tọa độ nhân viên: Kinh độ=$kinhDoNhanVien, Vĩ độ=$viDoNhanVien\n";
        $logKhoangCach .= "Tọa độ rạp phim: Kinh độ={$rapPhim->kinh_do}, Vĩ độ={$rapPhim->vi_do}\n";
        $logKhoangCach .= str_repeat("-", 80) . "\n";
        file_put_contents($fileLog, $logKhoangCach, FILE_APPEND);
        
        if ($khoangCach > 100) {
            throw new Exception('Kiểm tra kết nối wifi: '.$_POST['wifiTen'].'. Khoản cách không hợp lệ. Vui lòng liên hệ quản lý rạp để xử lý.');
        }
        // 2. Xử lý file video/image
        $videoPath = $_FILES['video']['tmp_name'] ?? null;
        if (!$videoPath) {
            throw new Exception('Thiếu file video tải lên');
        }
        
        
        // 3. Gọi Python script để xác thực khuôn mặt
        $envPython = $_ENV['PYTHON_PATH'] ?? 'python3';
        $filePython = __DIR__ . '/../../bin/python/face.py';
        $fileLog = __DIR__ . '/../../cache/log/face_checkin.log';
        if($loai == 'checkout'){
            $fileLog = __DIR__ . '/../../cache/log/face_checkout.log';
        }
        
        $command = escapeshellcmd("$envPython $filePython $videoPath $idNhanVien check");
        
        // Thực thi lệnh
        exec("$command 2>> $fileLog", $output, $returnVar);
        
        // Ghi log đầu ra từ Python
        $logContent = date('Y-m-d H:i:s') . " - ID: $idNhanVien - Loại: $loai\n";
        $logContent .= implode("\n", $output) . "\n";
        $logContent .= str_repeat("-", 80) . "\n";
        file_put_contents($fileLog, $logContent, FILE_APPEND);
        
        if ($returnVar != 0) {
            throw new Exception('Lỗi khi gọi xác thực khuôn mặt. Xem log để biết thêm chi tiết.');
        }
        
        // 4. Phân tích kết quả từ Python
        $result = implode("\n", $output);
        
        // Kiểm tra xác thực thành công
        if (strpos($result, 'Face verification SUCCESSFUL') === false) {
            // Lấy thông tin lỗi cụ thể
            if (strpos($result, 'Liveness check failed') !== false || strpos($result, 'Liveness FAILED') !== false || strpos($result, 'Spoofing detected') !== false) {
                throw new Exception('⚠️ Phát hiện giả mạo khuôn mặt! Hệ thống nhận diện bạn đang sử dụng ảnh/video giả. Vui lòng sử dụng khuôn mặt thật và thử lại.');
            } elseif (strpos($result, 'low quality') !== false) {
                throw new Exception('Chất lượng video không đạt yêu cầu. Vui lòng quay video ở nơi có ánh sáng tốt hơn.');
            } elseif (strpos($result, 'No match') !== false) {
                throw new Exception('Khuôn mặt không khớp. Vui lòng thử lại hoặc liên hệ quản trị viên.');
            } elseif (strpos($result, 'no stored embedding') !== false) {
                throw new Exception('Không tìm thấy thông tin khuôn mặt đã đăng ký. Vui lòng đăng ký lại.');
            } else {
                throw new Exception('Xác thực khuôn mặt thất bại. Vui lòng thử lại.');
            }
        }
        
        // Trích xuất similarity score (tùy chọn - để log/debug)
        preg_match('/SIMILARITY SCORE: ([\d.]+)/', $result, $matches);
        $similarityScore = $matches[1] ?? 'N/A';
        
        // 5. Lưu vào bảng chấm công
        $ngayHienTai = date('Y-m-d');
        $gioHienTai = date('Y-m-d H:i:s');
        if(!isset($_POST['id_phancong'])){
            throw new Exception('Nhận diện khuôn mặt thành công nhưng không tìm thấy bản ghi phân công hiện tại.');
        }
        $daChamCong = PhanCong::where('id_nhanvien', $idNhanVien)
            ->where('ngay', $ngayHienTai)
            ->where('id', $_POST['id_phancong'])
            ->first();
        
        if ($daChamCong) {
            // Đã có bản ghi - update
            if ($loai == 'checkin') {
                // Kiểm tra đã check-in chưa
                if ($daChamCong->gio_vao) {
                    throw new Exception('Bạn đã chấm công vào rồi. Thời gian: ' . $daChamCong->gio_vao);
                }
                $daChamCong->update([
                    'gio_vao' => $gioHienTai
                ]);
            } else if ($loai == 'checkout') {
                // Kiểm tra đã check-in chưa
                if (!$daChamCong->gio_vao) {
                    throw new Exception('Bạn chưa chấm công vào. Vui lòng chấm công vào trước.');
                }
                // Kiểm tra đã check-out chưa
                if ($daChamCong->gio_ra) {
                    throw new Exception('Bạn đã chấm công ra rồi. Thời gian: ' . $daChamCong->gio_ra);
                }
                $daChamCong->update([
                    'gio_ra' => $gioHienTai
                ]);
            }
        } else {
            throw new Exception('Nhận diện khuôn mặt thành công nhưng không tìm thấy bản ghi phân công hiện tại.');
        }
        
        // 6. Lấy thông tin nhân viên để trả về (optional)
        $nhanVien = NguoiDungInternal::find($idNhanVien);
        
        // 7. Trả về kết quả thành công
        return [
            'success' => true,
            'message' => 'Chấm công thành công',
            'loai' => $loai,
            'thoi_gian' => $gioHienTai,
            'nhan_vien' => [
                'id' => $nhanVien->ID,
                'ten' => $nhanVien->HoTen ?? 'N/A'
            ]
        ];
    }

    /**
     * Tính khoảng cách giữa tọa độ nhân viên và tọa độ rạp phim
     * Sử dụng công thức Haversine để tính khoảng cách trên bề mặt Trái Đất
     * 
     * @param float $kinhDoNhanVien Kinh độ của nhân viên
     * @param float $viDoNhanVien Vĩ độ của nhân viên
     * @return float Khoảng cách tính bằng mét
     * @throws Exception Nếu không lấy được tọa độ rạp phim
     */
    private function tinhKhoangCach($kinhDoNhanVien, $viDoNhanVien, $kinhDoRapPhim, $viDoRapPhim)
    {
        // Lấy tọa độ rạp phim từ biến môi trường hoặc config
        
        if (!$kinhDoRapPhim || !$viDoRapPhim) {
            throw new Exception('Chưa cấu hình tọa độ rạp phim. Vui lòng liên hệ quản trị viên.');
        }
        
        // Chuyển đổi sang float để đảm bảo tính toán chính xác
        $kinhDoNhanVien = (float) $kinhDoNhanVien;
        $viDoNhanVien = (float) $viDoNhanVien;
        $kinhDoRapPhim = (float) $kinhDoRapPhim;
        $viDoRapPhim = (float) $viDoRapPhim;
        
        // Bán kính Trái Đất tính bằng mét
        $banKinhTraiDat = 6371000; // 6371 km = 6371000 mét
        
        // Chuyển đổi độ sang radian
        $lat1Rad = deg2rad($viDoNhanVien);
        $lat2Rad = deg2rad($viDoRapPhim);
        $deltaLatRad = deg2rad($viDoRapPhim - $viDoNhanVien);
        $deltaLonRad = deg2rad($kinhDoRapPhim - $kinhDoNhanVien);
        
        // Công thức Haversine
        $a = sin($deltaLatRad / 2) * sin($deltaLatRad / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLonRad / 2) * sin($deltaLonRad / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        // Khoảng cách tính bằng mét
        $khoangCach = $banKinhTraiDat * $c;
        
        return $khoangCach;
    }

    // Helper function: Xóa file tạm sau khi xử lý (optional)
    private function cleanupTempFile($filePath)
    {
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
}
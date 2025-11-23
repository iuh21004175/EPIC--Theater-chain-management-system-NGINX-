<?php
namespace App\Controllers;
use function App\Core\view;
use App\Services\Sc_DonHang;
require __DIR__ . '/../../api/PHPMailer/src/Exception.php';
require __DIR__ . '/../../api/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../../api/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Ctrl_DonHang {
    public function index() {
        return view('customer.ve-cua-toi');
    }

    public function donHang() {
        return view('internal.don-hang');
    }

    public function themDonHang() {
        header('Content-Type: application/json'); 
        $service = new Sc_DonHang();
        try {
            $donhang = $service->them();
            if ($donhang) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Thêm đơn hàng thành công',
                    'data' => $donhang
                ]);
                exit;
            }
            echo json_encode([
                'success' => false, 
                'message' => 'Thêm đơn hàng thất bại'
            ]);
            exit;
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function themDonHangNV() {
        header('Content-Type: application/json'); 
        $service = new Sc_DonHang();
        try {
            $donhang = $service->themDonHang();
            if ($donhang) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Thêm đơn hàng thành công',
                    'data' => $donhang
                ]);
                exit;
            }
            echo json_encode([
                'success' => false, 
                'message' => 'Thêm đơn hàng thất bại'
            ]);
            exit;
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function capNhatTrangThaiDonHang(){
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        if(!$id) { echo json_encode(['success'=>false,'message'=>'Thiếu id']); exit; }

        $service = new Sc_DonHang();
        $result = $service->capNhat($id); // gán trang_thai = 0
        echo json_encode(['success'=>$result,'message'=>$result?'Cập nhật thành công':'Thất bại']);
        exit;
    }

    public function docDonHang() {
        $service = new Sc_DonHang();
        try {
            $donhang = $service->doc();
            if ($donhang) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Lấy đơn hàng thành công',
                    'data' => $donhang
                ]);
                exit;
            }
            echo json_encode([
                'success' => false, 
                'message' => 'Không tìm thấy đơn hàng'
            ]);
            exit;
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function docDonHangKH($idKhachHang) {
        header('Content-Type: application/json; charset=utf-8');
        $service = new Sc_DonHang();
        try {
            // Lấy các tham số lọc và phân trang từ query string
            $filters = [
                'trang_thai' => $_GET['trang_thai'] ?? 'all',
                'ngay_tu' => $_GET['ngay_tu'] ?? '',
                'ngay_den' => $_GET['ngay_den'] ?? '',
                'tong_tien_tu' => $_GET['tong_tien_tu'] ?? '',
                'tong_tien_den' => $_GET['tong_tien_den'] ?? '',
                'sort_by' => $_GET['sort_by'] ?? 'id',
                'sort_order' => $_GET['sort_order'] ?? 'desc',
                'page' => $_GET['page'] ?? 1,
                'per_page' => $_GET['per_page'] ?? 10
            ];

            $result = $service->docDonHang($idKhachHang, $filters);
            
            if ($result && isset($result['data']) && count($result['data']) > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Lấy đơn hàng thành công',
                    'data' => $result['data'],
                    'pagination' => [
                        'total' => $result['total'],
                        'page' => $result['page'],
                        'per_page' => $result['per_page'],
                        'total_pages' => $result['total_pages']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Không tìm thấy đơn hàng',
                    'data' => [],
                    'pagination' => [
                        'total' => 0,
                        'page' => 1,
                        'per_page' => 10,
                        'total_pages' => 0
                    ]
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    public function docDonHangOnline() {
        $service = new Sc_DonHang();
        try {
            $donhang = $service->docOnline();
            if ($donhang) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Lấy đơn hàng thành công',
                    'data' => $donhang
                ]);
                exit;
            }
            echo json_encode([
                'success' => false, 
                'message' => 'Không tìm thấy đơn hàng'
            ]);
            exit;
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function docDonHangTheoRap($idRap) {
        $service = new Sc_DonHang();
        try {
            $donhang = $service-> docDonHangTheoRap($idRap); // truyền id vào service
            if ($donhang && count($donhang) > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Lấy đơn hàng thành công',
                    'data' => $donhang
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Không tìm thấy đơn hàng'
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    public function guiDonHang() {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', 0);

        $data = json_decode(file_get_contents('php://input'), true);  
        if (!$data) {
            echo json_encode(['success'=>false,'message'=>'Dữ liệu không hợp lệ']);
            exit;
        }

        // Lấy thông tin người dùng từ session
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            echo json_encode(['success'=>false,'message'=>'Người dùng chưa đăng nhập']);
            exit;
        }

        $email = $user['email'];
        $ten = $user['ho_ten'] ?? 'Khách hàng';

        $don_hang = $data['don_hang'] ?? [];
        $phim = $data['phim'] ?? [];
        $ve = $data['ve'] ?? []; 
        $thuc_an = $data['thuc_an'] ?? [];

        // Danh sách ghế
        $so_ghe_text = '';
        $tong_tien = 0;
        foreach($ve as $v) {
            $so_ghe_text .= ($v['so_ghe'] ?? '') . ', ';
            $tong_tien += $v['gia'] ?? 0;
        }
        $so_ghe_text = rtrim($so_ghe_text, ', ');

        // Danh sách thức ăn kèm
        $thuc_an_text = '';
        foreach($thuc_an as $ta) {
            $thuc_an_text .= ($ta['ten'] ?? '') . ', ';
        }
        $thuc_an_text = $thuc_an ? implode(', ', array_column($thuc_an, 'ten')) : 'Không';

        // Tạo QR code URL từ QuickChart.io
        $ma_ve = $don_hang['ma_ve'];
        $qr_code = 'https://quickchart.io/qr?text=' . urlencode($ma_ve) . '&size=300';

        try {
            $PHPMAILER_KEY = $_ENV['PHPMAILER_KEY'];
            $mail = new PHPMailer(true);
            $mail->SMTPDebug = 0;
            $mail->CharSet = "utf-8";
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'tuandungnguyen800@gmail.com';
            $mail->Password   = $PHPMAILER_KEY;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('tuandungnguyen800@gmail.com', 'EPIC CINEMAS');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Xác nhận đặt vé xem phim thành công - EPIC CINEMAS";

            // Body email
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; background:#f9f9f9; padding:20px;">
                <div style="max-width:600px; margin:0 auto; background:#f5f5f5; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); overflow:hidden;">
                    <div style="text-align:center; margin:10px 20px;">
                        <img src="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1757905773/unnamed_kvd85z.png" 
                        alt="EPIC Cinema Banner" style="width:100%; max-width:100%; border-radius:12px; display:block; margin:0 auto;" />
                    </div>
                    <div style="padding:20px;">
                        <p>Xin chào <b>'.$ten.'</b>,</p>
                        <p>Cảm ơn bạn đã sử dụng dịch vụ của <b>EPIC CINEMAS</b>.</p>
                        <p>Chúng tôi xác nhận bạn đã đặt vé xem phim tại <b>'.($phim['rap'] ?? '').'</b> thành công.</p>

                        <div style="text-align:center; margin:20px auto; padding:20px; border:2px dashed #d32f2f; border-radius:12px; background:#fff7f7; max-width:350px;">
                            <p style="margin:5px 0; font-size:14px; color:#555;">Vui lòng xuất trình mã QR này tại quầy để nhận vé</p>
                            <div style="margin:15px 0;">
                                <img src="'.$qr_code.'" alt="QR Code" width="200" style="border:1px solid #ddd; padding:10px; border-radius:8px; background:#fff;" />
                            </div>
                            <p style="margin:0; font-size:16px; font-weight:bold; letter-spacing:2px; color:#333;">
                                Mã đặt vé: <span style="color:#d32f2f;">'.$ma_ve.'</span>
                            </p>
                        </div>
                        <!-- Thông tin vé -->
                        <h3 style="margin-top:20px; color:#d32f2f; border-bottom:2px solid #d32f2f; padding-bottom:5px;">Thông tin vé</h3>
                        <table cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:14px; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                            <tr style="background:#f9f9f9;">
                                <th align="left" style="padding:10px; width:35%; border-bottom:1px solid #eee;">Rạp</th>
                                <td style="padding:10px; border-bottom:1px solid #eee;">
                                    <div style="font-weight:bold; color:#333;">'.($phim['rap'] ?? '').'</div>
                                    <div style="font-size:13px; color:#777;">'.($phim['dia_chi'] ?? '').'</div>
                                </td>
                            </tr>
                            <tr>
                                <th align="left" style="padding:10px; border-bottom:1px solid #eee;">Phim</th>
                                <td style="padding:10px; border-bottom:1px solid #eee;">'.($phim['ten_phim'] ?? '').'</td>
                            </tr>
                            <tr style="background:#f9f9f9;">
                                <th align="left" style="padding:10px; border-bottom:1px solid #eee;">Phòng chiếu</th>
                                <td style="padding:10px; border-bottom:1px solid #eee;">'.($phim['phong'] ?? '').'</td>
                            </tr>
                            <tr>
                                <th align="left" style="padding:10px; border-bottom:1px solid #eee;">Suất chiếu</th>
                                <td style="padding:10px; border-bottom:1px solid #eee;">'.($phim['suat_chieu'] ?? '').'</td>
                            </tr>
                            <tr style="background:#f9f9f9;">
                                <th align="left" style="padding:10px; border-bottom:1px solid #eee;">Số ghế</th>
                                <td style="padding:10px; border-bottom:1px solid #eee;">'.$so_ghe_text.'</td>
                            </tr>
                            <tr>
                                <th align="left" style="padding:10px; border-bottom:1px solid #eee;">Thức ăn kèm</th>
                                <td style="padding:10px; border-bottom:1px solid #eee;">'.$thuc_an_text.'</td>
                            </tr>
                            <tr style="background:#f9f9f9;">
                                <th align="left" style="padding:10px; font-weight:bold; color:#333;">Tổng tiền</th>
                                <td style="padding:10px; font-weight:bold; color:#333;">'.number_format($tong_tien).' VNĐ</td>
                            </tr>
                        </table>

                        <!-- Thông tin người nhận -->
                        <h3 style="margin-top:20px; color:#d32f2f; border-bottom:2px solid #d32f2f; padding-bottom:5px;">Thông tin người nhận</h3>
                        <table cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:14px; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                            <tr style="background:#f9f9f9;">
                                <th align="left" style="padding:10px; width:35%; border-bottom:1px solid #eee;">Họ tên</th>
                                <td style="padding:10px; border-bottom:1px solid #eee;">'.$ten.'</td>
                            </tr>
                            <tr>
                                <th align="left" style="padding:10px; border-bottom:1px solid #eee;">Email</th>
                                <td style="padding:10px; border-bottom:1px solid #eee;">'.$email.'</td>
                            </tr>
                        </table>
                    </div>

                    <div style="background:#f2f2f2; text-align:center; padding:10px; font-size:12px; color:#666;">
                        EPIC CINEMAS © 2025 - Đây là email tự động, vui lòng không trả lời.
                    </div>
                </div>
            </div>';

            $mail->send();

            echo json_encode(['success' => true, 'message' => "Đã gửi email về $email"]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => "Không thể gửi mail. Lỗi: {$mail->ErrorInfo}"]);
        }

        exit;
    }

    public function inVe($data) {
        $data = json_decode(file_get_contents('php://input'), true);  
        $don_hang = $data['don_hang'] ?? [];
        $phim = $data['phim'] ?? [];
        $ve_list = $data['ve'] ?? [];
        $thuc_an = $data['thuc_an'] ?? [];

        if (empty($ve_list)) {
            die('Đơn hàng chưa có vé.');
        }

        $pdf = new tFPDF('P', 'mm', [105, 148]); 

        // ADD FONT UNICODE
        $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
        $pdf->AddFont('DejaVu','B','DejaVuSansCondensed-Bold.ttf',true);

        foreach ($ve_list as $ve) {
            $pdf->AddPage();

            // HEADER
            $pdf->SetFont('DejaVu','B',16);
            $pdf->SetTextColor(0,0,128);
            $pdf->Cell(0,10,'VÉ XEM PHIM',0,1,'C');
            $pdf->SetFont('DejaVu','',12);
            $pdf->SetTextColor(0,0,0);
            $pdf->Cell(0,8, $phim['rap'] ?? '', 0,1,'C');
            $pdf->Ln(5);

            // THÔNG TIN VÉ 
            $info = [
                'Mã vé' => $ve['ma_ve'] ?? '',
                'Tên phim' => $phim['ten_phim'] ?? '',
                'Suất chiếu' => $phim['suat_chieu'] ?? '',
                'Phòng chiếu' => $phim['phong'] ?? '',
                'Ghế' => $ve['so_ghe'] ?? ''
            ];
            $line_height = 7;
            $ticket_price = $ve['gia'] ?? 0;

            foreach($info as $k => $v){
                $pdf->SetX(10);
                $pdf->SetFont('DejaVu','B',10);
                $pdf->Cell(30, $line_height, $k, 0, 0, 'L');
                $pdf->SetFont('DejaVu','',10);
                $pdf->Cell(0, $line_height, $v, 0, 1, 'L');
            }

            // Giá vé
            $pdf->SetX(10);
            $pdf->SetFont('DejaVu','B',10);
            $pdf->Cell(30, $line_height, 'Giá vé', 0, 0, 'L');
            $pdf->SetFont('DejaVu','',10);
            $pdf->Cell(0, $line_height, number_format($ticket_price,0,",",".").'đ', 0, 1, 'L');

            $pdf->Ln(5);

            // QR CODE
            $qr_img_size = 40;
            $qr_url = 'https://quickchart.io/qr?text='.urlencode($ve['ma_ve'] ?? '').'&size=300';
            $qr_file = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
            file_put_contents($qr_file, file_get_contents($qr_url));

            // Căn giữa trang
            $x_center = ($pdf->GetPageWidth() - $qr_img_size)/2;
            $y_current = $pdf->GetY();
            $pdf->Image($qr_file, $x_center, $y_current, $qr_img_size, $qr_img_size);
            unlink($qr_file);

            $pdf->Ln($qr_img_size + 5);
        }

        $pdf->Output('I','ve_xem_phim.pdf');
    }
}

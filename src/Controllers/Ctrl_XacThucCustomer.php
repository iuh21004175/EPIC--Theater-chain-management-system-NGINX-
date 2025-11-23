<?php
namespace App\Controllers;

use function App\Core\view;
use App\Services\Sc_XacThucCustomer;
use App\Services\Sc_ResetToken;

require __DIR__ . '/../../api/PHPMailer/src/Exception.php';
require __DIR__ . '/../../api/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../../api/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Ctrl_XacThucCustomer
{
    public function index()
    {
        return view('customer.home');
    }

    public function dangKy()
    {
        try {
            $scXacThuc = new Sc_XacThucCustomer();
            if ($scXacThuc->scDangKy()) {
                return [
                    'status'   => 'success',
                    'message'  => 'Đăng ký thành công!'
                ];
            } else {
                return [
                    'status'  => 'error',
                    'message' => 'Email đã tồn tại. Vui lòng sử dụng email khác.'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status'  => 'error',
                'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.',
                'error'   => $e->getMessage()
            ];
        }
    }

    public function dangNhap() 
    {
        try {
            $scXacThuc = new Sc_XacThucCustomer();
            $result = $scXacThuc->scDangNhap();

            if ($result === true) {
                return [
                    'status'  => 'success',
                    'message' => 'Đăng nhập thành công!'
                ];
            } elseif ($result === 'disabled') {
                return [
                    'status'  => 'error',
                    'message' => 'Tài khoản của bạn bị vô hiệu hóa.'
                ];
            } else {
                return [
                    'status'  => 'error',
                    'message' => 'Email hoặc mật khẩu không đúng. Vui lòng thử lại.'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status'  => 'error',
                'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.',
                'error'   => $e->getMessage()
            ];
        }
    }

    public function checkLogin() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
                return [
                    'status'  => 'success',
                    'message' => 'Người dùng đã đăng nhập',
                    'user'    => $_SESSION['user']
                ];
            } else {
                return [
                    'status'  => 'error',
                    'message' => 'Người dùng chưa đăng nhập'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status'  => 'error',
                'message' => 'Lỗi kiểm tra đăng nhập',
                'error'   => $e->getMessage()
            ];
        }
    }

    public function dangXuat()
    {
        session_destroy();
        header('Location: ' . $_ENV['URL_WEB_BASE'] . '/');
        exit();
    }

    public function doiMatKhau()
    {
        return view('customer.doi-mat-khau');
    }

    public function xuLyDoiMatKhau()
    {
        // Đảm bảo không có output thừa
        ini_set('display_errors', 0);
        header('Content-Type: application/json; charset=utf-8');

        $scXacThuc = new Sc_XacThucCustomer();
        $user = $_SESSION['user'] ?? null;
        $userId = $user['id'] ?? null;

        $data = json_decode(file_get_contents('php://input'), true);

        try {
            if (!$userId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Người dùng chưa đăng nhập.'
                ]);
                exit;
            }

            if (!$data || !isset($data['currentPassword']) || !isset($data['newPassword'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Dữ liệu đổi mật khẩu không hợp lệ.'
                ]);
                exit;
            }

            $currentPassword = $data['currentPassword'];
            $newPassword = $data['newPassword'];

            if (!$scXacThuc->checkMatKhau($userId, $currentPassword)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Mật khẩu hiện tại không đúng.'
                ]);
                exit;
            }

            $result = $scXacThuc->scDoiMatKhau($userId, $newPassword);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Đổi mật khẩu thành công!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy khách hàng để đổi mật khẩu.'
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.'
            ]);
        }
        exit;
    }

    public function checkEmail()
    {
        ini_set('display_errors', 0);
        error_reporting(0);
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');

        if (empty($email)) {
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng nhập email.'
            ]);
            exit;
        }

        try {
            $scXacThuc = new Sc_XacThucCustomer();
            $exists = $scXacThuc->scCheckEmail($email);

            if ($exists) {
                // Email đã tồn tại → trả về false
                echo json_encode([
                    'success' => false,
                    'message' => 'Email đã tồn tại.'
                ]);
            } else {
                // Email không tồn tại → trả về true
                echo json_encode([
                    'success' => true,
                    'message' => 'Email không tồn tại.'
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi server'
            ]);
        }
        exit;
    }


   public function sendResetPassword()
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', 0); // tránh output lỗi ra JSON

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Truy cập không hợp lệ!']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');

        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập email.']);
            exit;
        }

        try {
            $scXacThuc = new Sc_XacThucCustomer();
            $khachHang = $scXacThuc->getCustomerByEmail($email);

            if (!$khachHang) {
                echo json_encode(['success' => false, 'message' => 'Email không tồn tại.']);
                exit;
            }

            // Tạo token và lưu vào DB
            $scReset = new Sc_ResetToken();
            $resetToken = $scReset->createToken($khachHang->id);

            $url = $_ENV['URL_WEB_BASE'];
            $linkPassword = "{$url}/reset-password?token={$resetToken->token}";

            $PHPMAILER_KEY = $_ENV['PHPMAILER_KEY'];
            // Gửi mail
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
            $mail->Subject = "Yêu cầu lấy lại mật khẩu - EPIC CINEMAS";
            $mail->Body = "
                <p>Xin chào,</p>
                <p>Bạn vừa gửi yêu cầu <b>lấy lại mật khẩu</b>.</p>
                <p>Vui lòng truy cập: <b>{$linkPassword}</b> để thực hiện cập nhật mật khẩu mới!</p>
            ";

            $mail->send();

            echo json_encode(['success' => true, 'message' => "Đã được gửi về email $email"]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => "Không thể gửi mail. Lỗi: {$e->getMessage()}"]);
        }

        exit;
    }

    public function resetPassword()
    {
        return view('customer.reset-password');
    }

    public function ResetPass()
    {
        $scXacThuc = new Sc_XacThucCustomer();
        $scXacThuc->scResetPass();
    }

    public function googleLogin()
    {
        try {
            $scXacThuc = new Sc_XacThucCustomer();
            $loginUrl = $scXacThuc->scGoogleLogin();

            // Redirect người dùng đến Google
            header("Location: " . $loginUrl);
            exit;
        } catch (\Exception $e) {
            echo "Lỗi khi tạo URL Google Login: " . $e->getMessage();
            exit;
        }
    }

    public function googleCallback(): void
    {
        try {
            $scXacThuc = new Sc_XacThucCustomer();

            // Gọi hàm callback trong service
            $result = $scXacThuc->scGoogleCallback();

            if ($result['success']) {
                // Redirect về trang chính sau khi login thành công
                header("Location: /"); // hoặc '/dashboard' nếu bạn có trang dashboard
                exit;
            } else {
                // Hiển thị lỗi nếu callback thất bại
                echo "<h3>Lỗi Google Login:</h3>";
                echo "<pre>" . htmlspecialchars($result['message']) . "</pre>";
                if (isset($result['response'])) {
                    echo "<pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "</pre>";
                }
                exit;
            }
        } catch (\Exception $e) {
            echo "<h3>Exception khi xử lý Google Callback:</h3>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
            exit;
        }
    }

}


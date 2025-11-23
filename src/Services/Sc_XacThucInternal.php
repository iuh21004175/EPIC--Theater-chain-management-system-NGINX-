<?php
    namespace App\Services;

    use App\Models\NguoiDungInternal;
    use App\Models\TaiKhoanInternal;
    require __DIR__ . '/../../api/PHPMailer/src/Exception.php';
    require __DIR__ . '/../../api/PHPMailer/src/PHPMailer.php';
    require __DIR__ . '/../../api/PHPMailer/src/SMTP.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    class Sc_XacThucInternal {
        public function scDangNhap(){
            $tenDangNhap = $_POST['TenDangNhap'] ?? '';
            $matKhau = $_POST['MatKhau'] ?? '';

            $taiKhoan = TaiKhoanInternal::where('tendangnhap', $tenDangNhap)->first();
            if ($taiKhoan) {
                if (password_verify($matKhau, $taiKhoan->matkhau_bam)) {
                    $_SESSION['UserInternal'] = [
                        'ID' => $taiKhoan->nguoiDungInternals->id ?? null,
                        'Ten' => $taiKhoan->nguoiDungInternals->ten ?? '',
                        'Email' => $taiKhoan->nguoiDungInternals->email ?? '',
                        'DienThoai' => $taiKhoan->nguoiDungInternals->dien_thoai ?? '',
                        'TenDangNhap' => $taiKhoan->tendangnhap,
                        'VaiTro' => $taiKhoan->vaiTro->ten,
                        'ID_RapPhim' => $taiKhoan->nguoiDungInternals->id_rapphim ?? null,
                    ];
                    return true;
                }
            }
            // Nếu đăng nhập không thành công
            return false;
        }
        public function scXacThucEmailLayLaiMatKhau() {
            $email = $_POST['email'] ?? '';
            $nguoiDung = NguoiDungInternal::with(['taiKhoan' => function($q) {
                $q->where('id_vaitro', 4);
            }])
            ->where('email', $email)
            ->first();

            if (!$nguoiDung || !$nguoiDung->taiKhoan) {
                throw new \Exception("Email không tồn tại trong hệ thống hoặc không phải tài khoản nhân viên.");
            }

            if ($nguoiDung) {
                // Tạo mật khẩu mới ngẫu nhiên gồm 8 ký tự
                $matKhauMoi = bin2hex(random_bytes(4)); // Tạo mật khẩu mới ngẫu nhiên
                // Cập nhật mật khẩu mới cho người dùng
                $nguoiDung->taiKhoan->matkhau_bam = password_hash($matKhauMoi, PASSWORD_ARGON2ID);
                $nguoiDung->taiKhoan->save();

                // Gửi email thông báo mật khẩu mới
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
                $mail->Subject = "Mật khẩu mới của bạn- EPIC CINEMAS";
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; background: #f6f6f6; padding: 30px;'>
                        <div style='max-width: 500px; margin: auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 32px;'>
                            <h2 style='color: #d32f2f; text-align: center; margin-bottom: 24px;'>EPIC CINEMAS</h2>
                            <p style='font-size: 16px; color: #333;'>Xin chào,</p>
                            <p style='font-size: 16px; color: #333;'>Bạn vừa gửi yêu cầu <b>lấy lại mật khẩu</b>.</p>
                            <p style='font-size: 16px; color: #333;'>
                                Mật khẩu mới của bạn là: 
                                <span style='display: inline-block; background: #f1f1f1; color: #d32f2f; font-weight: bold; padding: 8px 16px; border-radius: 6px; font-size: 18px; letter-spacing: 2px; margin: 8px 0;'>{$matKhauMoi}</span>
                            </p>
                            <p style='font-size: 16px; color: #333;'>Vui lòng đăng nhập và đổi lại mật khẩu ngay!</p>
                            <hr style='margin: 32px 0; border: none; border-top: 1px solid #eee;'>
                            <p style='font-size: 13px; color: #888; text-align: center;'>Nếu bạn không yêu cầu, vui lòng bỏ qua email này.</p>
                        </div>
                    </div>
                ";

                $mail->send();
                
            }
            else{
                throw new \Exception("Email không tồn tại trong hệ thống.");
            }
        }
        public function scDoiMatKhau() {
            $matKhauCu = $_POST['MatKhauCu'] ?? '';
            $matKhauMoi = $_POST['MatKhauMoi'] ?? '';
            $nguoiDungId = $_SESSION['UserInternal']['ID'];
            $nguoiDung = NguoiDungInternal::with('taiKhoan')->find($nguoiDungId);
            if (!$nguoiDung || !$nguoiDung->taiKhoan) {
                throw new \Exception("Người dùng không tồn tại hoặc không có tài khoản.");
            }
            if (password_verify($matKhauCu, $nguoiDung->taiKhoan->matkhau_bam)) {
                // Mật khẩu cũ đúng, cập nhật mật khẩu mới
                $nguoiDung->taiKhoan->matkhau_bam = password_hash($matKhauMoi, PASSWORD_ARGON2ID);
                $nguoiDung->taiKhoan->save();
                session_destroy(); // Hủy session hiện tại để người dùng đăng nhập lại
            } else {
                throw new \Exception("Mật khẩu cũ không đúng.");
            }
        }
    }
?>
<?php
    namespace App\Services;
    use App\Models\KhachHang;
    use App\Models\ResetToken;
    class Sc_XacThucCustomer {
        public function scDangKy() {
            $hoTen    = $_POST['registerName'];  
            $email    = $_POST['registerEmail'];
            $gioiTinh = $_POST['sex'];
            $ngaySinh = $_POST['txtNgaySinh'];
            $phone    = $_POST['registerPhone'];
            $matKhau  = $_POST['registerPassword'];

            // Kiểm tra email
            if (KhachHang::where('email', $email)->exists()) {
                return false;
            }

            // Tạo mới khách hàng
            $khachHang = new KhachHang();
            $khachHang->ho_ten = $hoTen;
            $khachHang->email = $email;
            $khachHang->gioi_tinh = $gioiTinh;
            $khachHang->ngay_sinh = $ngaySinh;
            $khachHang->so_dien_thoai = $phone;
            $khachHang->mat_khau = password_hash($matKhau, PASSWORD_DEFAULT);
            $khachHang->save();

            return true;
        }

        public function scDangNhap() {
            $email = $_POST['loginEmail'] ?? '';
            $matKhau = $_POST['loginPassword'] ?? '';

            $khachHang = KhachHang::where('email', $email)->first();

            if (!$khachHang) {
                return false; // không tìm thấy email
            }

            if ($khachHang->trang_thai === 0) {
                return 'disabled'; // tài khoản bị vô hiệu hóa
            }

            if (password_verify($matKhau, $khachHang->mat_khau)) {
                $_SESSION['user'] = [
                    'id'        => $khachHang->id,
                    'ho_ten'    => $khachHang->ho_ten,
                    'email'     => $khachHang->email,
                    'gioi_tinh' => $khachHang->gioi_tinh,
                    'ngay_sinh' => $khachHang->ngay_sinh
                ];
                return true; // đăng nhập thành công
            }

            return false; // mật khẩu sai
        }
        public function scDoiMatKhau($userId, $newPassword) {
            $khachHang = KhachHang::find($userId);
            if ($khachHang) {
                $khachHang->mat_khau = password_hash($newPassword, PASSWORD_DEFAULT);
                $khachHang->save();
                return true;
            }
            return false;
        }
        public function checkMatKhau($userId, $password)
        {
            $khachHang = KhachHang::find($userId);
            if ($khachHang && password_verify($password, $khachHang->mat_khau)) {
                return true;
            }
            return false;
        }

        public function scCheckEmail($email) {
            return KhachHang::where('email', $email)->exists();
        }
        public function getCustomerByEmail($email) {
            return KhachHang::where('email', $email)->first(); 
        }

        public function scResetPass()
        {
            header('Content-Type: application/json; charset=utf-8');

            // Lấy JSON từ fetch
            $data = json_decode(file_get_contents('php://input'), true);
            $token = $data['token'] ?? '';
            $password = $data['password'] ?? '';

            if (!$token || !$password) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ'
                ]);
                exit;
            }

            // Tìm token hợp lệ
            $reset = ResetToken::where('token', $token)
                ->where('expire_at', '>', date('Y-m-d H:i:s'))
                ->first();

            if (!$reset) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Token không hợp lệ hoặc đã hết hạn'
                ]);
                exit;
            }

            // Cập nhật mật khẩu
            $khachHang = KhachHang::find($reset->khach_hang_id);
            if (!$khachHang) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy khách hàng.'
                ]);
                exit;
            }

            $khachHang->mat_khau = password_hash($password, PASSWORD_DEFAULT);
            $khachHang->save();

            // Xóa token sau khi dùng
            $reset->delete();

            echo json_encode([
                'success' => true,
                'message' => 'Đặt lại mật khẩu thành công!',
                'redirect' => '/'
            ]);
            exit;
        }

        public function scGoogleLogin(): string
        {
            $client_id = $_ENV['GOOGLE_CLIENT_ID'];
            $baseUrl = $_ENV['URL_WEB_BASE'];
            $redirect_uri = $baseUrl . "/api/google-callback";
            $scope = "email profile https://www.googleapis.com/auth/user.birthday.read https://www.googleapis.com/auth/user.gender.read";

            $login_url = "https://accounts.google.com/o/oauth2/v2/auth"
                . "?response_type=code"
                . "&client_id=" . $client_id
                . "&redirect_uri=" . urlencode($redirect_uri)
                . "&scope=" . urlencode($scope)
                . "&access_type=offline"
                . "&prompt=consent select_account";

            return $login_url;
        }

        public function scGoogleCallback(): array
        {
            session_start();

            $client_id = $_ENV['GOOGLE_CLIENT_ID'];
            $client_secret = $_ENV['GOOGLE_CLIENT_SECRET'];
            $baseUrl = $_ENV['URL_WEB_BASE'];
            $redirect_uri = $baseUrl . "/api/google-callback";

            if (!isset($_GET['code'])) {
                return [
                    'success' => false,
                    'message' => 'Không nhận được code từ Google.'
                ];
            }

            $code = $_GET['code'];

            // Lấy Access Token
            $token_url = "https://oauth2.googleapis.com/token";
            $post_fields = [
                "code" => $code,
                "client_id" => $client_id,
                "client_secret" => $client_secret,
                "redirect_uri" => $redirect_uri,
                "grant_type" => "authorization_code"
            ];

            $ch = curl_init($token_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            curl_close($ch);

            $token_data = json_decode($response, true);
            if (!isset($token_data['access_token'])) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi lấy Access Token.',
                    'response' => $token_data
                ];
            }

            $access_token = $token_data['access_token'];

            // Lấy thông tin cơ bản của user
            $user_info_url = "https://www.googleapis.com/oauth2/v2/userinfo";
            $ch = curl_init($user_info_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $user_info_response = curl_exec($ch);
            curl_close($ch);

            $user = json_decode($user_info_response, true);
            if (!isset($user['email'])) {
                return [
                    'success' => false,
                    'message' => 'Không lấy được thông tin email từ Google.'
                ];
            }

            // Lấy thêm ngày sinh & giới tính từ People API (nếu có)
            $birthday = null;
            $gender = null;

            $people_api_url = "https://people.googleapis.com/v1/people/me?personFields=birthdays,genders";
            $ch = curl_init($people_api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $people_response = curl_exec($ch);
            curl_close($ch);

            $people = json_decode($people_response, true);

            if (isset($people['birthdays'][0]['date'])) {
                $date = $people['birthdays'][0]['date'];
                $day   = $date['day'] ?? '';
                $month = $date['month'] ?? '';
                $year  = $date['year'] ?? '';
                if ($day && $month && $year) {
                    $birthday = "$day/$month/$year";
                }
            }

            if (isset($people['genders'][0]['value'])) {
                $gender = match($people['genders'][0]['value']) {
                    'male' => 'Nam',
                    'female' => 'Nữ',
                    default => null,
                };
            }

            // Kiểm tra xem user đã tồn tại chưa
            $khachHang = KhachHang::where('email', $user['email'])->first();

            if (!$khachHang) {
                // Nếu chưa có thì tạo mới, chỉ lưu thông tin tồn tại
                $khachHang = new KhachHang();
                $khachHang->ho_ten = $user['name'] ?? '';
                $khachHang->email  = $user['email'];
                $khachHang->google_id  = 1;
                if (!empty($gender)) {
                    $khachHang->gioi_tinh = match($gender) {
                        'Nam' => 0,
                        'Nữ'  => 1
                    };
                }

                if (!empty($birthday)) {
                    $khachHang->ngay_sinh = $birthday;
                }

                $khachHang->mat_khau = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                $khachHang->save();
            }

            // Lưu session
            $_SESSION['user'] = [
                'id'        => $khachHang->id,
                'ho_ten'    => $khachHang->ho_ten,
                'email'     => $khachHang->email,
                'gioi_tinh' => $khachHang->gioi_tinh ?? '',
                'ngay_sinh' => $khachHang->ngay_sinh ?? ''
            ];
            header("Location: " . $_ENV['URL_WEB_BASE']);
            exit;
            
            return [
                'success' => true,
                'user' => $_SESSION['user']
            ];
        }
    }
?>
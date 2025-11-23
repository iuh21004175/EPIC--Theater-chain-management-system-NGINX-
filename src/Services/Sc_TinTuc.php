<?php
    namespace App\Services;
    use App\Models\TinTuc;
    use function App\Core\getS3Client;
    class Sc_TinTuc {
        public function doc($filters = []) {
            $query = TinTuc::whereIn('trang_thai', [0, 2]);

            // Lọc theo rạp (thông qua tác giả)
            if (isset($filters['rap_id']) && $filters['rap_id'] !== '' && $filters['rap_id'] !== null) {
                $query->whereHas('tacGia', function ($q) use ($filters) {
                    $q->where('id_rapphim', $filters['rap_id']);
                });
            }

            // Tìm kiếm theo tiêu đề hoặc nội dung
            if (isset($filters['search']) && $filters['search'] !== '') {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('tieu_de', 'like', "%{$search}%")
                      ->orWhere('noi_dung', 'like', "%{$search}%");
                });
            }

            // Sắp xếp
            $sortBy = $filters['sort_by'] ?? 'ngay_tao';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Phân trang
            $page = isset($filters['page']) ? (int)$filters['page'] : 1;
            $perPage = isset($filters['per_page']) ? (int)$filters['per_page'] : 10;
            
            $total = $query->count();
            $tinTuc = $query->with('tacGia')->skip(($page - 1) * $perPage)->take($perPage)->get();

            return [
                'data' => $tinTuc,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ];
        }

        public function findById($id){
            return TinTuc::find($id);
        }

        public function themTinTuc()
        {
            $bucket = 'anh-tin-tuc';
            $baiViet = null;

            try {
                // --- Kiểm tra đăng nhập ---
                if (empty($_SESSION['UserInternal']['ID'])) {
                    throw new \Exception('Bạn cần đăng nhập để thực hiện chức năng này.');
                }

                $tieuDe = trim($_POST['tieu_de'] ?? '');
                $noiDung = trim($_POST['noi_dung'] ?? '');

                if ($tieuDe === '' || $noiDung === '') {
                    throw new \Exception('Vui lòng nhập đầy đủ tiêu đề và nội dung.');
                }

                // --- Xử lý ảnh thumbnail ---
                $anhTinTucUrl = null;
                $keyName = null;

                if (!empty($_FILES['anh_tin_tuc']) && $_FILES['anh_tin_tuc']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['anh_tin_tuc'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                    // Chỉ cho phép ảnh
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    if (!in_array($ext, $allowed)) {
                        throw new \Exception('Chỉ chấp nhận file ảnh JPG, PNG hoặc WEBP.');
                    }

                    // Tạo key tên file
                    $keyName = 'tintuc_' . uniqid('', true) . '.' . $ext;

                    // Upload lên MinIO
                    getS3Client()->putObject([
                        'Bucket'      => $bucket,
                        'Key'         => $keyName,
                        'SourceFile'  => $file['tmp_name'],
                        'ACL'         => 'public-read',
                        'ContentType' => $file['type'],
                    ]);

                    // Lưu đường dẫn dạng bucket/keyName (VD: anh-tin-tuc/avatar-3.png)
                    $anhTinTucUrl = $bucket . '/' . $keyName;
                }

                // --- Lưu DB ---
                $baiViet = TinTuc::create([
                    'tieu_de'       => $tieuDe,
                    'noi_dung'      => $noiDung,
                    'anh_tin_tuc'   => $anhTinTucUrl, // VD: anh-tin-tuc/tintuc_abc.png
                    'tac_gia'       => $_SESSION['UserInternal']['Ten'] ?? 'Admin',
                    'id_tac_gia'    => $_SESSION['UserInternal']['ID'],
                    'ngay_tao'      => date('Y-m-d H:i:s'),
                    'ngay_cap_nhat' => date('Y-m-d H:i:s'),
                    'trang_thai'    => isset($_POST['trang_thai']) ? (int)$_POST['trang_thai'] : 1,
                ]);

                return [
                    'success' => true,
                    'message' => 'Đã thêm bài viết thành công!',
                    'data'    => $baiViet
                ];
            } catch (\Exception $e) {
                error_log('[themTinTuc] ' . $e->getMessage());

                // Rollback nếu cần
                if ($baiViet && method_exists($baiViet, 'delete')) {
                    $baiViet->delete();
                }

                return [
                    'success' => false,
                    'message' => 'Lỗi khi thêm tin tức: ' . $e->getMessage(),
                ];
            }
        }

        public function suaTinTuc($id)
        {
            $bucket = 'anh-tin-tuc';

            try {

                $baiViet = TinTuc::where('id', $id)->first();

                if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                    $rawData = file_get_contents('php://input');
                    $boundary = substr($rawData, 0, strpos($rawData, "\r\n"));
                    $parts = array_slice(explode($boundary, $rawData), 1);
                    foreach ($parts as $part) {
                        if ($part == "--\r\n") break;
                        if (strpos($part, 'Content-Disposition') !== false && strpos($part, 'filename=') !== false) {
                            preg_match('/name="([^"]+)"; filename="([^"]+)"/', $part, $matches);
                            $name = $matches[1] ?? null;
                            $filename = $matches[2] ?? null;
                            $tmpPath = sys_get_temp_dir() . '/' . uniqid('putfile_');

                            $fileBody = substr($part, strpos($part, "\r\n\r\n") + 4);
                            $fileBody = substr($fileBody, 0, strlen($fileBody) - 2); // bỏ \r\n cuối

                            file_put_contents($tmpPath, $fileBody);

                            $_FILES[$name] = [
                                'name' => $filename,
                                'type' => mime_content_type($tmpPath),
                                'tmp_name' => $tmpPath,
                                'error' => 0,
                                'size' => filesize($tmpPath)
                            ];
                        }
                        elseif (strpos($part, 'Content-Disposition') !== false) {
                            preg_match('/name="([^"]+)"/', $part, $matches);
                            $name = $matches[1] ?? null;
                            $value = trim(substr($part, strpos($part, "\r\n\r\n") + 4));
                            $value = substr($value, 0, strlen($value) - 2); // bỏ \r\n cuối
                            $_POST[$name] = $value;
                        }
                    }
                }

                $tieuDeMoi = trim($_POST['tieu_de'] ?? '');
                $noiDungMoi = trim($_POST['noi_dung'] ?? '');

                if ($tieuDeMoi !== '') $baiViet->tieu_de = $tieuDeMoi;
                if ($noiDungMoi !== '') $baiViet->noi_dung = $noiDungMoi;

                if (!empty($_FILES['anh_tin_tuc']) && $_FILES['anh_tin_tuc']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['anh_tin_tuc'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                    if (!in_array($ext, $allowed)) {
                        throw new \Exception('Chỉ chấp nhận JPG, PNG hoặc WEBP.');
                    }

                    $keyName = 'tintuc_' . uniqid('', true) . '.' . $ext;

                    getS3Client()->putObject([
                        'Bucket'      => $bucket,
                        'Key'         => $keyName,
                        'SourceFile'  => $file['tmp_name'],
                        'ACL'         => 'public-read',
                        'ContentType' => $file['type'],
                    ]);

                    $baiViet->anh_tin_tuc = $bucket . '/' . $keyName;
                }

                $baiViet->ngay_cap_nhat = date('Y-m-d H:i:s');
                $baiViet->save();

                return [
                    'success' => true,
                    'message' => 'Cập nhật bài viết thành công!',
                    'data' => $baiViet
                ];
            } catch (\Exception $e) {
                error_log('[suaTinTuc] ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Lỗi khi sửa bài viết: ' . $e->getMessage()
                ];
            }
        }

        public function docGuiYCBaiViet()
        {
            $idNhanVien = $_SESSION['UserInternal']['ID'] ?? null;

            return TinTuc::where('id_tac_gia', $idNhanVien)
                ->where('trang_thai', '!=', 0)
                ->get();
        }

        public function docChiTietBaiViet($id)
        {
            try {
                $idNV = $_SESSION['UserInternal']['ID'] ?? null;
                if (!$idNV) throw new \Exception('Bạn cần đăng nhập.');

                $baiViet = TinTuc::where('id', $id)
                    // ->where('id_tac_gia', $idNV)
                    ->first();

                // if (!$baiViet) throw new \Exception('Không tìm thấy hoặc không có quyền.');

                return ['success' => true, 'data' => $baiViet];
            } catch (\Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        public function docTinTucDaGui()
        {
            $idRap = $_SESSION['UserInternal']['ID_RapPhim'] ?? null;

            return TinTuc::whereHas('tacGia', function ($q) use ($idRap) {
                    $q->where('id_rapphim', $idRap);
                })
                ->where('trang_thai', '!=', 0)
                ->with(['tacGia']) 
                ->orderBy('ngay_tao', 'desc')
                ->get();
        }

        public function docTinTucTheoRap()
        {
            $idRap = $_SESSION['UserInternal']['ID_RapPhim'] ?? null;
            
            return TinTuc::whereHas('tacGia', function ($q) use ($idRap) {
                    $q->where('id_rapphim', $idRap);
                })
                ->whereIn('trang_thai', [0, 2])
                ->with(['tacGia'])
                ->orderBy('ngay_tao', 'desc')
                ->get();
        }

        public function duyetTinTuc($id)
        {
            $data = json_decode(file_get_contents('php://input'), true);

            $trang_thai = $data['trang_thai'] ?? '1'; 

            $baiViet = TinTuc::find($id);

            if (!$baiViet) {
                throw new \Exception("Bài viết không tồn tại.");
            }

            $baiViet->trang_thai = $trang_thai;

            $baiViet->save();

            return $baiViet;
        }

    }
<?php
    namespace App\Services;
    use App\Models\ViTriCongViec;
    use App\Models\PhanCong;
    use App\Models\Ngay;
    class Sc_PhanCong {
        public function docViTri(){
            $viTri = ViTriCongViec::with('rapPhim')
                ->where('id_rapphim', $_SESSION['UserInternal']['ID_RapPhim'])
                ->get();
            return $viTri;
        }
        public function themViTri(){
            $ten = $_POST['ten'] ?? '';
            $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];

            if ($ten && $idRapPhim) {
                ViTriCongViec::create([
                    'ten' => $ten,
                    'id_rapphim' => $idRapPhim,
                ]);
                
            }
            else {
                throw new \Exception("Tên vị trí và ID rạp phim không được để trống.");
            }
        }
        public function suaViTri($id){
            $data = json_decode(file_get_contents('php://input'), true);
            $ten = $data['ten'] ?? '';

            $viTri = ViTriCongViec::find($id);
            if ($viTri) {
                if ($ten) {
                    $viTri->ten = $ten;
                    $viTri->save();
                } else {
                    throw new \Exception("Tên vị trí không được để trống.");
                }
            } else {
                throw new \Exception("Vị trí công việc không tồn tại.");
            }
        }
        public function phanCong1NhanVien(){
            $idNhanVien = $_POST['id_nhanvien'];
            $idCongViec = $_POST['id_congviec'];
            $ngay = $_POST['ngay'];
            $ca = $_POST['ca'];

            $phanCong = PhanCong::create([
                'id_nhanvien' => $idNhanVien,
                'id_congviec' => $idCongViec,
                'ngay' => $ngay,
                'ca' => $ca,
            ]);
            return $phanCong;
        }
        public function xoa1PhanCong($id){
            $phanCong = PhanCong::find($id);
            if ($phanCong) {
                $phanCong->delete();
            } else {
                throw new \Exception("Phân công không tồn tại.");
            }
        }
        public function docPhanCong($batdau, $ketthuc){
            $phanCong = PhanCong::with(['nhanVien', 'congViec'])
                ->whereHas('nhanVien', function($query) {
                    $query->where('id_rapphim', $_SESSION['UserInternal']['ID_RapPhim']);
                })
                ->whereBetween('ngay', [$batdau, $ketthuc])
                ->orderBy('ngay', 'asc')
                ->orderBy('ca', 'asc')
                ->get();
            return $phanCong;
        }

        public function docPhanCongTheoNV($batdau, $ketthuc)
        {
            $phanCong = PhanCong::with(['nhanVien', 'congViec'])
                ->where('id_nhanvien', $_SESSION['UserInternal']['ID'])
                ->where('trang_thai', '!=', 2)
                ->whereBetween('ngay', [$batdau, $ketthuc])
                ->orderBy('ngay', 'asc')
                ->orderBy('ca', 'asc')
                ->get();

            return $phanCong;
        }

        public function docLichLamViec()
        {
            return PhanCong::where('id_nhanvien', $_SESSION['UserInternal']['ID'])
                ->where('trang_thai', 0)
                ->get();
        }

        public function docChamCong($thang = null)
        {
            if ($thang === null) {
                $thang = date('Y-m'); // tự động lấy tháng hiện tại
            }

            list($nam, $thangSo) = explode('-', $thang);

            // Lấy toàn bộ danh sách ngày đặc biệt (mảng các ngày)
            $dsNgayDacBiet = Ngay::pluck('ngay')->toArray();

            // Lấy danh sách chấm công của nhân viên theo tháng
            $records = PhanCong::where('id_nhanvien', $_SESSION['UserInternal']['ID'])
                ->where('trang_thai', 0)
                ->whereNotNull('gio_vao')
                ->whereNotNull('gio_ra')
                ->where('gio_vao', '<>', '')
                ->where('gio_ra', '<>', '')
                ->whereYear('ngay', $nam)
                ->whereMonth('ngay', $thangSo)
                ->get();

            // Thêm trường hệ số lương (he_so)
            foreach ($records as $rec) {
                $heSo = 1.0; // Hệ số mặc định

                // Nếu ngày có trong danh sách ngày đặc biệt thì hệ số = 4.0
                if (in_array($rec->ngay, $dsNgayDacBiet)) {
                    $heSo = 4.0;
                }

                // Gán vào đối tượng để trả về JSON
                $rec->he_so = $heSo;
            }

            return $records;
        }

        public function docChamCongToanRap($thang = null)
        {
            // Nếu không truyền tham số => mặc định lấy tháng hiện tại
            if ($thang === null) {
                $thang = date('Y-m');
            }

            [$nam, $thangSo] = explode('-', $thang);

            // Lấy danh sách ngày đặc biệt (VD: lễ, Tết, ...), dạng mảng các ngày 'YYYY-MM-DD'
            $dsNgayDacBiet = Ngay::pluck('ngay')->toArray();

            // Lấy danh sách chấm công theo tháng cho toàn bộ nhân viên trong rạp hiện tại
            $records = PhanCong::with(['nhanVien', 'congViec'])
                ->whereHas('nhanVien', function ($query) {
                    $query->where('id_rapphim', $_SESSION['UserInternal']['ID_RapPhim']);
                })
                ->where('trang_thai', 0)
                ->whereYear('ngay', $nam)
                ->whereMonth('ngay', $thangSo)
                ->orderBy('ngay', 'asc')
                ->orderBy('ca', 'asc')
                ->get();

            // Tính hệ số lương cho từng bản ghi
            foreach ($records as $rec) {
                $rec->he_so = in_array($rec->ngay, $dsNgayDacBiet) ? 4.0 : 1.0;
            }

            return $records;
        }

        public function docGuiYCLich()
        {
            $idNhanVien = $_SESSION['UserInternal']['ID'] ?? null;

            return PhanCong::where('id_nhanvien', $idNhanVien)
                ->whereNotNull('ly_do')
                ->whereNotNull('trang_thai')
                ->get();
        }
        public function sua1PhanCong($id)
        {
            $data = json_decode(file_get_contents('php://input'), true);

            $ly_do = $data['ly_do'] ?? null;
            $trang_thai = $data['trang_thai'] ?? '0'; 
            $gio_vao = $data['gio_vao'] ?? null;
            $gio_ra = $data['gio_ra'] ?? null;

            $phanCong = PhanCong::find($id);

            if (!$phanCong) {
                throw new \Exception("Phân công không tồn tại.");
            }

            if (!empty($ly_do)) {
                $phanCong->ly_do = $ly_do;
            }
            if (!empty($trang_thai)) {
                $phanCong->trang_thai = $trang_thai;
            }

            if (!empty($gio_vao)) {
                $phanCong->gio_vao = $gio_vao;
            }

            if (!empty($gio_ra)) {
                $phanCong->gio_ra = $gio_ra;
            }

            $phanCong->save();

            return $phanCong;
        }

        public function docYCDaGui()
        {
            $idRap = $_SESSION['UserInternal']['ID_RapPhim'] ?? null;

            return PhanCong::whereHas('nhanVien', function ($q) use ($idRap) {
                    $q->where('id_rapphim', $idRap);
                })
                ->where('trang_thai', '!=', 0)
                ->with(['nhanVien', 'congViec'])
                ->get();
        }
    }
?>
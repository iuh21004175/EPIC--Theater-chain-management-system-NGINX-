<?php
    namespace App\Services;
    use App\Models\DonHang;
    use App\Models\Phim;
    use App\Models\SuatChieu;
    use App\Models\RapPhim;
    use App\Models\PhongChieu;
    use App\Models\Ve;
    use App\Models\SanPham;
    use App\Models\ChiTietDonHang;
    use App\Models\PhanPhoiPhim;
    use App\Models\Ngay; // Thêm model Ngay vào danh sách import
    use App\Models\MuaPhim; // Doanh thu mua phim trực tuyến

    class Sc_ThongKe{
        // Dùng hiển thị biểu đồ Phân tích doanh thu, Phân bổ doanh thu
        public function phanTichDoanhThuTheoRap($idRap, $tuNgay, $denNgay){
            // Tính Doanh thu vé theo từng ngày của rạp
            // Tính Doanh thu sản phẩm theo từng ngày của rạp
            
            // Tính khoảng thời gian
            $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
            $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
            
            // Tạo mảng các ngày trong khoảng thời gian
            $danhSachNgay = [];
            $ngayHienTai = clone $tuNgayDate;
            while ($ngayHienTai <= $denNgayDate) {
                $danhSachNgay[] = $ngayHienTai->format('Y-m-d');
                $ngayHienTai->add(new \DateInterval('P1D'));
            }
            
            // Khởi tạo kết quả
            $ketQua = [];
            
            foreach ($danhSachNgay as $ngay) {
                $batDauNgay = $ngay . ' 00:00:00';
                $ketThucNgay = $ngay . ' 23:59:59';
                
                // -------------- TÍNH DOANH THU VÉ THEO NGÀY --------------
                
                // Doanh thu vé = Tổng giá vé của các vé đã bán (trang_thai = 2) trong ngày
                $doanhThuVe = Ve::whereHas('suatchieu', function($query) use ($idRap) {
                        $query->whereHas('phongChieu', function($subQuery) use ($idRap) {
                            $subQuery->where('id_rapphim', $idRap);
                        });
                    })
                    ->where('trang_thai', 2) // Vé đã đặt
                    ->whereHas('donhang', function($query) use ($batDauNgay, $ketThucNgay) {
                        $query->where('trang_thai', 2) // Đơn hàng đã thanh toán
                              ->whereBetween('ngay_dat', [$batDauNgay, $ketThucNgay]);
                    })
                    ->sum('gia_ve');
               
                    
                // -------------- TÍNH DOANH THU SẢN PHẨM THEO NGÀY --------------
                
                // Doanh thu sản phẩm = Tổng thành tiền của chi tiết đơn hàng (sản phẩm đồ ăn/nước uống)
                $doanhThuSanPham = ChiTietDonHang::whereHas('donHang', function($query) use ($idRap, $batDauNgay, $ketThucNgay) {
                        $query->whereHas('suatChieu', function($subQuery) use ($idRap) {
                            $subQuery->whereHas('phongChieu', function($thirdQuery) use ($idRap) {
                                $thirdQuery->where('id_rapphim', $idRap);
                            });
                        })
                        ->where('trang_thai', 2) // Đơn hàng đã thanh toán
                        ->whereBetween('ngay_dat', [$batDauNgay, $ketThucNgay]);
                    })
                    ->whereHas('sanPham', function($query) use ($idRap) {
                        $query->where('id_rapphim', $idRap); // Chỉ lấy sản phẩm của rạp này
                    })
                    ->sum('thanh_tien');
                    
              
                    
                // Tổng doanh thu trong ngày
                $tongDoanhThu = $doanhThuVe + $doanhThuSanPham;
                
                // Thêm kết quả vào mảng
                $ketQua[] = [
                    'ngay' => $ngay,
                    'ngay_formatted' => (new \DateTime($ngay))->format('d/m/Y'),
                    'thu_trong_tuan' => (new \DateTime($ngay))->format('l'), // Thứ trong tuần
                    'doanh_thu_ve' => $doanhThuVe,
                    'doanh_thu_san_pham' => $doanhThuSanPham,
                    'tong_doanh_thu' => $tongDoanhThu,
                ];
            }
            
            // Tính tổng kết cho cả khoảng thời gian
            $tongDoanhThuVe = array_sum(array_column($ketQua, 'doanh_thu_ve'));
            $tongDoanhThuSanPham = array_sum(array_column($ketQua, 'doanh_thu_san_pham'));

            return [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
                'chi_tiet_theo_ngay' => $ketQua,
                'tong_ket' => [
                    'tong_doanh_thu_ve' => $tongDoanhThuVe,
                    'tong_doanh_thu_san_pham' => $tongDoanhThuSanPham
                ]
            ];
        }
        // Lấy ra 10 phim có doanh thu cao nhất trong khoảng thời gian
        public function top10PhimCoDoanhThuCaoNhatTheoRap($idRap, $tuNgay, $denNgay) {
            // Định dạng thời gian
            $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
            $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
            $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
            $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
            
            // Lấy doanh thu từ vé cho từng phim
            $doanhThuVeCollection = Ve::selectRaw('
                    suatchieu.id_phim,
                    SUM(ve.gia_ve) as doanh_thu_ve,
                    COUNT(ve.id) as so_ve_ban
                ')
                ->join('suatchieu', 've.suat_chieu_id', '=', 'suatchieu.id')
                ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
                ->join('donhang', 've.donhang_id', '=', 'donhang.id')
                ->where('phongchieu.id_rapphim', $idRap)
                ->where('ve.trang_thai', 2) // Vé đã đặt
                ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
                ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery])
                ->groupBy('suatchieu.id_phim')
                ->get();
            
            // Chuyển đổi thành mảng
            $doanhThuVeTheoPhim = [];
            foreach ($doanhThuVeCollection as $item) {
                $doanhThuVeTheoPhim[$item->id_phim] = [
                    'doanh_thu_ve' => $item->doanh_thu_ve,
                    'so_ve_ban' => $item->so_ve_ban
                ];
            }
            
            // Sắp xếp phim có doanh thu theo thứ tự giảm dần
            $danhSachPhimCoDoanhThu = [];
            foreach ($doanhThuVeTheoPhim as $phimId => $data) {
                $danhSachPhimCoDoanhThu[$phimId] = [
                    'id_phim' => $phimId,
                    'doanh_thu_ve' => $data['doanh_thu_ve'],
                    'so_ve_ban' => $data['so_ve_ban']
                ];
            }
            
            uasort($danhSachPhimCoDoanhThu, function($a, $b) {
                return $b['doanh_thu_ve'] <=> $a['doanh_thu_ve'];
            });
            
            // Lấy danh sách tất cả phim được phân phối cho rạp
            $danhSachPhimDuocPhanPhoi = PhanPhoiPhim::where('id_rapphim', $idRap)
                ->pluck('id_phim')
                ->toArray();
            
            // Lấy phim có doanh thu
            $phimCoDoanhThuIds = array_keys($danhSachPhimCoDoanhThu);
            
            // Lấy phim không có doanh thu (phim được phân phối nhưng không có doanh thu)
            $phimKhongCoDoanhThuIds = array_diff($danhSachPhimDuocPhanPhoi, $phimCoDoanhThuIds);
            
            // Thêm phim không có doanh thu vào danh sách
            $danhSachPhimKhongCoDoanhThu = [];
            foreach ($phimKhongCoDoanhThuIds as $phimId) {
                $danhSachPhimKhongCoDoanhThu[$phimId] = [
                    'id_phim' => $phimId,
                    'doanh_thu_ve' => 0,
                    'so_ve_ban' => 0
                ];
            }
            
            // Kết hợp 2 danh sách: phim có doanh thu + phim không có doanh thu
            $danhSachPhim = $danhSachPhimCoDoanhThu + $danhSachPhimKhongCoDoanhThu;
            
            // Giới hạn lấy 10 phim
            $top10Phim = array_slice($danhSachPhim, 0, 10, true);
            
            // Lấy thông tin chi tiết của phim
            if (!empty($phimIds = array_keys($top10Phim))) {
                $thongTinPhimCollection = Phim::whereIn('id', $phimIds)
                    ->with(['TheLoai.TheLoai'])
                    ->get();
                
                // Chuyển đổi thành mảng với key là id
                $thongTinPhim = [];
                foreach ($thongTinPhimCollection as $phim) {
                    $thongTinPhim[$phim->id] = $phim;
                }
                
                // Kết hợp thông tin phim với doanh thu
                $ketQua = [];
                foreach ($top10Phim as $phimId => $doanhThuData) {
                    $phim = isset($thongTinPhim[$phimId]) ? $thongTinPhim[$phimId] : null;
                    if ($phim) {
                        // Lấy danh sách thể loại
                        $theLoaiArray = [];
                        if ($phim->TheLoai) {
                            foreach ($phim->TheLoai as $item) {
                                if ($item->TheLoai && $item->TheLoai->ten) {
                                    $theLoaiArray[] = $item->TheLoai->ten;
                                }
                            }
                        }
                        $theLoai = implode(', ', $theLoaiArray);
                        
                        $ketQua[] = [
                            'id' => $phim->id,
                            'ten_phim' => $phim->ten_phim,
                            'dao_dien' => $phim->dao_dien,
                            'dien_vien' => $phim->dien_vien,
                            'thoi_luong' => $phim->thoi_luong,
                            'ngay_cong_chieu' => $phim->ngay_cong_chieu,
                            'do_tuoi' => $phim->do_tuoi,
                            'poster_url' => $phim->poster_url,
                            'the_loai' => $theLoai,
                            'doanh_thu_ve' => $doanhThuData['doanh_thu_ve'] ?? 0,
                            'so_ve_ban' => $doanhThuData['so_ve_ban'] ?? 0
                        ];
                    }
                }
            } else {
                $ketQua = [];
            }
            
            // Nếu vẫn chưa đủ 10 phim, lấy thêm phim mới/phim sắp chiếu
            if (count($ketQua) < 10) {
                $soPhimCanThem = 10 - count($ketQua);
                $phimIds = array_column($ketQua, 'id');
                
                $phimBoSung = Phim::whereNotIn('id', $phimIds)
                    ->orderBy('ngay_cong_chieu', 'desc')
                    ->limit($soPhimCanThem)
                    ->with(['TheLoai.TheLoai'])
                    ->get();
                    
                foreach ($phimBoSung as $phim) {
                    // Lấy danh sách thể loại
                    $theLoaiArray = [];
                    if ($phim->TheLoai) {
                        foreach ($phim->TheLoai as $item) {
                            if ($item->TheLoai && $item->TheLoai->ten) {
                                $theLoaiArray[] = $item->TheLoai->ten;
                            }
                        }
                    }
                    $theLoai = implode(', ', $theLoaiArray);
                    
                    $ketQua[] = [
                        'id' => $phim->id,
                        'ten_phim' => $phim->ten_phim,
                        'dao_dien' => $phim->dao_dien,
                        'dien_vien' => $phim->dien_vien,
                        'thoi_luong' => $phim->thoi_luong,
                        'ngay_cong_chieu' => $phim->ngay_cong_chieu,
                        'do_tuoi' => $phim->do_tuoi,
                        'poster_url' => $phim->poster_url,
                        'the_loai' => $theLoai,
                        'doanh_thu_ve' => 0, // Phim bổ sung không có doanh thu
                        'so_ve_ban' => 0
                    ];
                }
            }
            
            return [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
                'top_10_phim' => $ketQua
            ];
        }
        // Lấy ra 10 sản phẩm có doanh thu cao nhất trong khoảng thời gian
        public function top10SanPhamCoDoanhThuCaoNhatTheoRap($idRap, $tuNgay, $denNgay) {
            // Định dạng thời gian
            $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
            $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
            $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
            $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
            
            // Lấy doanh thu từng sản phẩm
            $doanhThuSanPhamCollection = ChiTietDonHang::selectRaw('
                    chitiet_donhang.sanpham_id,
                    SUM(chitiet_donhang.thanh_tien) as doanh_thu,
                    SUM(chitiet_donhang.so_luong) as so_luong_ban
                ')
                ->join('donhang', 'chitiet_donhang.donhang_id', '=', 'donhang.id')
                ->join('suatchieu', 'donhang.suat_chieu_id', '=', 'suatchieu.id')
                ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
                ->join('san_pham', 'chitiet_donhang.sanpham_id', '=', 'san_pham.id')
                ->where('phongchieu.id_rapphim', $idRap)
                ->where('san_pham.id_rapphim', $idRap) // Chỉ lấy sản phẩm của rạp này
                ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
                ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery])
                ->groupBy('chitiet_donhang.sanpham_id')
                ->get();
            
            // Chuyển đổi thành mảng
            $danhSachSanPhamCoDoanhThu = [];
            foreach ($doanhThuSanPhamCollection as $item) {
                $danhSachSanPhamCoDoanhThu[$item->sanpham_id] = [
                    'id_san_pham' => $item->sanpham_id,
                    'doanh_thu' => $item->doanh_thu,
                    'so_luong_ban' => $item->so_luong_ban
                ];
            }
            
            // Sắp xếp theo doanh thu giảm dần
            uasort($danhSachSanPhamCoDoanhThu, function($a, $b) {
                return $b['doanh_thu'] <=> $a['doanh_thu'];
            });
            
            // Lấy tất cả sản phẩm của rạp
            $tatCaSanPhamCuaRapCollection = SanPham::where('id_rapphim', $idRap)
                ->where('trang_thai', 1) // Chỉ lấy sản phẩm đang bán
                ->select('id')
                ->get();

            $tatCaSanPhamCuaRap = [];
            foreach ($tatCaSanPhamCuaRapCollection as $sanPham) {
                $tatCaSanPhamCuaRap[] = $sanPham->id;
            }
            
            // Lấy sản phẩm có doanh thu
            $sanPhamCoDoanhThuIds = array_keys($danhSachSanPhamCoDoanhThu);
            
            // Lấy sản phẩm không có doanh thu (sản phẩm của rạp nhưng chưa bán được)
            $sanPhamKhongCoDoanhThuIds = array_diff($tatCaSanPhamCuaRap, $sanPhamCoDoanhThuIds);
            
            // Thêm sản phẩm không có doanh thu vào danh sách
            $danhSachSanPhamKhongCoDoanhThu = [];
            foreach ($sanPhamKhongCoDoanhThuIds as $sanPhamId) {
                $danhSachSanPhamKhongCoDoanhThu[$sanPhamId] = [
                    'id_san_pham' => $sanPhamId,
                    'doanh_thu' => 0,
                    'so_luong_ban' => 0
                ];
            }
            
            // Kết hợp 2 danh sách: sản phẩm có doanh thu + sản phẩm không có doanh thu
            $danhSachSanPham = $danhSachSanPhamCoDoanhThu + $danhSachSanPhamKhongCoDoanhThu;
            
            // Giới hạn lấy 10 sản phẩm
            $top10SanPham = array_slice($danhSachSanPham, 0, 10, true);
            
            // Lấy thông tin chi tiết của sản phẩm
            if (!empty($sanPhamIds = array_keys($top10SanPham))) {
                $thongTinSanPhamCollection = SanPham::whereIn('id', $sanPhamIds)
                    ->with(['danhMuc'])
                    ->get();
        
                // Chuyển đổi thành mảng với key là id
                $thongTinSanPham = [];
                foreach ($thongTinSanPhamCollection as $sanPham) {
                    $thongTinSanPham[$sanPham->id] = $sanPham;
                }
        
                // Kết hợp thông tin sản phẩm với doanh thu
                $ketQua = [];
                foreach ($top10SanPham as $sanPhamId => $doanhThu) {
                    $sanPham = isset($thongTinSanPham[$sanPhamId]) ? $thongTinSanPham[$sanPhamId] : null;
                    if ($sanPham) {
                        $ketQua[] = [
                            'id' => $sanPham->id,
                            'ten' => $sanPham->ten,
                            'mo_ta' => $sanPham->mo_ta,
                            'gia' => $sanPham->gia,
                            'hinh_anh' => $sanPham->hinh_anh,
                            'danh_muc' => $sanPham->danhMuc ? $sanPham->danhMuc->ten : 'Không xác định',
                            'danh_muc_id' => $sanPham->danh_muc_id,
                            'trang_thai' => $sanPham->trang_thai,
                            'doanh_thu' => $doanhThu['doanh_thu']
                        ];
                    }
                }
            } else {
                $ketQua = [];
            }
            
            // Nếu vẫn chưa đủ 10 sản phẩm, lấy thêm sản phẩm mới nhất
            if (count($ketQua) < 10) {
                $soSanPhamCanThem = 10 - count($ketQua);
                $sanPhamIds = array_column($ketQua, 'id');
        
                $sanPhamBoSung = SanPham::where('id_rapphim', $idRap)
                    ->whereNotIn('id', $sanPhamIds)
                    ->orderBy('created_at', 'desc')
                    ->limit($soSanPhamCanThem)
                    ->with(['danhMuc'])
                    ->get();
                    
                foreach ($sanPhamBoSung as $sanPham) {
                    $ketQua[] = [
                        'id' => $sanPham->id,
                        'ten' => $sanPham->ten,
                        'mo_ta' => $sanPham->mo_ta,
                        'gia' => $sanPham->gia,
                        'hinh_anh' => $sanPham->hinh_anh,
                        'danh_muc' => $sanPham->danhMuc ? $sanPham->danhMuc->ten : 'Không xác định',
                        'danh_muc_id' => $sanPham->danh_muc_id,
                        'trang_thai' => $sanPham->trang_thai,
                        'doanh_thu' => 0,
                    ];
                }
            }
            
            // Tính tổng doanh thu và số lượng sản phẩm đã bán
            $tongDoanhThu = array_sum(array_column($ketQua, 'doanh_thu'));
            
            return [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
                'top_10_san_pham' => $ketQua,
                'tong_ket' => [
                    'tong_doanh_thu' => $tongDoanhThu
                ]
            ];
        }
        public function hieuQuaTheoKhungGioTheoRap($idRap, $tuNgay, $denNgay) {
            // Định dạng thời gian
            $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
            $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
            $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
            $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
            
            // Định nghĩa các khung giờ từ 8:00 đến 24:00
            $khungGio = [
                ['batDau' => '08:00:00', 'ketThuc' => '10:00:00', 'label' => '8:00 - 10:00'],
                ['batDau' => '10:00:00', 'ketThuc' => '12:00:00', 'label' => '10:00 - 12:00'],
                ['batDau' => '12:00:00', 'ketThuc' => '14:00:00', 'label' => '12:00 - 14:00'],
                ['batDau' => '14:00:00', 'ketThuc' => '16:00:00', 'label' => '14:00 - 16:00'],
                ['batDau' => '16:00:00', 'ketThuc' => '18:00:00', 'label' => '16:00 - 18:00'],
                ['batDau' => '18:00:00', 'ketThuc' => '20:00:00', 'label' => '18:00 - 20:00'],
                ['batDau' => '20:00:00', 'ketThuc' => '22:00:00', 'label' => '20:00 - 22:00'],
                ['batDau' => '22:00:00', 'ketThuc' => '24:00:00', 'label' => '22:00 - 24:00']
            ];
            
            $ketQua = [];
            
            // Lấy ra dữ liệu cho từng khung giờ
            foreach ($khungGio as $kg) {
                $batDauGio = $kg['batDau'];
                $ketThucGio = $kg['ketThuc'];
                
                // Lấy danh sách suất chiếu trong khung giờ và khoảng thời gian
                $suatChieuCollection = SuatChieu::whereHas('phongChieu', function($query) use ($idRap) {
                        $query->where('id_rapphim', $idRap);
                    })
                    ->whereBetween('batdau', [$tuNgayQuery, $denNgayQuery])
                    ->whereRaw("TIME(batdau) >= ?", [$batDauGio])
                    ->whereRaw("TIME(batdau) < ?", [$ketThucGio])
                    ->get();
                
                // Khởi tạo các biến thống kê
                $soLuongSuatChieu = $suatChieuCollection->count();
                $tongSoGhe = 0;
                $tongSoVeDaBan = 0;
                $tongDoanhThuVe = 0;
                $tongDoanhThuSanPham = 0;
                
                // Nếu không có suất chiếu nào trong khung giờ này
                if ($soLuongSuatChieu == 0) {
                    $ketQua[] = [
                        'khung_gio' => $kg['label'],
                        'so_luong_suat_chieu' => 0,
                        'ty_le_lap_day' => 0,
                        'doanh_thu_ve' => 0,
                        'doanh_thu_san_pham' => 0,
                        'tong_doanh_thu' => 0
                    ];
                    continue;
                }
                
                // Duyệt qua từng suất chiếu để tính số ghế và doanh thu
                foreach ($suatChieuCollection as $suatChieu) {
                    $idSuatChieu = $suatChieu->id;
                    $soGhePhong = $suatChieu->phongChieu->so_luong_ghe;
                    $tongSoGhe += $soGhePhong;
                    
                    // Đếm số vé đã bán cho suất chiếu này
                    $soVeDaBan = Ve::where('suat_chieu_id', $idSuatChieu)
                        ->where('trang_thai', 2) // Vé đã đặt
                        ->count();
                    $tongSoVeDaBan += $soVeDaBan;
                    
                    // Tính doanh thu vé của suất chiếu này
                    $doanhThuVeSuatChieu = Ve::where('suat_chieu_id', $idSuatChieu)
                        ->where('trang_thai', 2) // Vé đã đặt
                        ->sum('gia_ve');
                    $tongDoanhThuVe += $doanhThuVeSuatChieu;
                    
                    // Tính doanh thu sản phẩm từ các đơn hàng của suất chiếu này
                    $doanhThuSanPhamSuatChieu = ChiTietDonHang::whereHas('donHang', function($query) use ($idSuatChieu) {
                            $query->where('suat_chieu_id', $idSuatChieu)
                                  ->where('trang_thai', 2); // Đơn hàng đã thanh toán
                        })
                        ->sum('thanh_tien');
                    $tongDoanhThuSanPham += $doanhThuSanPhamSuatChieu;
                }
                
                // Tính tỷ lệ lấp đầy ghế
                $tyLeLapDay = $tongSoGhe > 0 ? ($tongSoVeDaBan / $tongSoGhe) * 100 : 0;
                $tyLeLapDay = round($tyLeLapDay, 2);
                
                // Tính tổng doanh thu
                $tongDoanhThu = $tongDoanhThuVe + $tongDoanhThuSanPham;
                
                // Thêm kết quả vào mảng
                $ketQua[] = [
                    'khung_gio' => $kg['label'],
                    'so_luong_suat_chieu' => $soLuongSuatChieu,
                    'so_luong_ghe' => $tongSoGhe,
                    'so_ve_ban' => $tongSoVeDaBan,
                    'ty_le_lap_day' => $tyLeLapDay,
                    'doanh_thu_ve' => $tongDoanhThuVe,
                    'doanh_thu_san_pham' => $tongDoanhThuSanPham,
                    'tong_doanh_thu' => $tongDoanhThu
                ];
            }
            
            // Tính khung giờ có hiệu quả nhất (tỷ lệ lấp đầy cao nhất và doanh thu cao nhất)
            $khungGioTyLeLapDayMax = array_reduce($ketQua, function($carry, $item) {
                return ($carry === null || $item['ty_le_lap_day'] > $carry['ty_le_lap_day']) ? $item : $carry;
            });
            
            $khungGioDoanhThuMax = array_reduce($ketQua, function($carry, $item) {
                return ($carry === null || $item['tong_doanh_thu'] > $carry['tong_doanh_thu']) ? $item : $carry;
            });
            
            // Tính trung bình tỷ lệ lấp đầy và doanh thu
            $totalEntries = count(array_filter($ketQua, function($item) { 
                return $item['so_luong_suat_chieu'] > 0; 
            }));
            
            $avgTyLeLapDay = $totalEntries > 0 ? 
                array_sum(array_column($ketQua, 'ty_le_lap_day')) / $totalEntries : 0;
            
            $avgDoanhThu = $totalEntries > 0 ? 
                array_sum(array_column($ketQua, 'tong_doanh_thu')) / $totalEntries : 0;
            
            // Định dạng tiền tệ cho doanh thu
            foreach ($ketQua as &$kg) {
                $kg['doanh_thu_ve_formatted'] = number_format($kg['doanh_thu_ve'], 0, ',', '.') . ' đ';
                $kg['doanh_thu_san_pham_formatted'] = number_format($kg['doanh_thu_san_pham'], 0, ',', '.') . ' đ';
                $kg['tong_doanh_thu_formatted'] = number_format($kg['tong_doanh_thu'], 0, ',', '.') . ' đ';
            }
            
            return [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
                'chi_tiet_theo_khung_gio' => $ketQua,
                'khung_gio_hieu_qua_nhat' => [
                    'ty_le_lap_day' => $khungGioTyLeLapDayMax['khung_gio'] ?? 'Không có dữ liệu',
                    'doanh_thu' => $khungGioDoanhThuMax['khung_gio'] ?? 'Không có dữ liệu'
                ],
                'trung_binh' => [
                    'ty_le_lap_day' => round($avgTyLeLapDay, 2),
                    'doanh_thu' => round($avgDoanhThu, 2),
                    'doanh_thu_formatted' => number_format(round($avgDoanhThu, 2), 0, ',', '.') . ' đ'
                ]
            ];
        }
        // Thống kê xu hướng khách hàng theo thời gian
        public function xuHuongKhachHangTheoThoiGianTheoRap($idRap, $tuNgay, $denNgay) {
            // Định dạng thời gian
            $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
            $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
            
            // Tạo mảng các ngày trong khoảng thời gian
            $danhSachNgay = [];
            $ngayHienTai = clone $tuNgayDate;
            while ($ngayHienTai <= $denNgayDate) {
                $danhSachNgay[] = $ngayHienTai->format('Y-m-d');
                $ngayHienTai->add(new \DateInterval('P1D'));
            }
            
            // Lấy thông tin về các ngày đặc biệt (lễ, tết) trong khoảng thời gian
            $ngayDacBietCollection = Ngay::whereBetween('ngay', [$tuNgay, $denNgay])
                ->get();
            
            $ngayDacBiet = [];
            $ngayLe = [];
            $ngayTet = [];
            foreach ($ngayDacBietCollection as $ngay) {
                $ngayDacBiet[$ngay->ngay] = [
                    'loai_ngay' => $ngay->loai_ngay,
                    'dac_biet' => $ngay->dac_biet
                ];
                
                // Phân loại ngày lễ và ngày tết
                if (stripos($ngay->loai_ngay, 'tết') !== false || stripos($ngay->dac_biet, 'tết') !== false) {
                    $ngayTet[$ngay->ngay] = true;
                } else {
                    $ngayLe[$ngay->ngay] = true;
                }
            }
            
            // Khởi tạo mảng kết quả
            $ketQua = [];
            $tongKhach = [];
            $khachCuoiTuan = [];
            $khachNgayThuong = [];
            $khachNgayLe = [];
            $khachNgayTet = []; // Thêm mảng riêng cho ngày Tết
            
            // Phân tích dữ liệu theo từng ngày
            foreach ($danhSachNgay as $ngay) {
                $batDauNgay = $ngay . ' 00:00:00';
                $ketThucNgay = $ngay . ' 23:59:59';
                
                // Kiểm tra ngày trong tuần
                $ngayDateTime = new \DateTime($ngay);
                $thuTrongTuan = (int)$ngayDateTime->format('N'); // 1 (Thứ 2) đến 7 (Chủ Nhật)
                $laCuoiTuan = ($thuTrongTuan >= 6); // Thứ 7 và Chủ Nhật
                
                // Kiểm tra ngày lễ/tết
                $laNgayLe = isset($ngayLe[$ngay]);
                $laNgayTet = isset($ngayTet[$ngay]);
                $laNgayDacBiet = isset($ngayDacBiet[$ngay]);
                
                // Tính số lượng khách hàng trong ngày
                // Số lượng khách hàng có tài khoản
                $soLuongKhachHangCoTaiKhoan = DonHang::whereHas('suatChieu', function($query) use ($idRap) {
                        $query->whereHas('phongChieu', function($subQuery) use ($idRap) {
                            $subQuery->where('id_rapphim', $idRap);
                        });
                    })
                    ->where('trang_thai', 2) // Đã thanh toán
                    ->whereBetween('ngay_dat', [$batDauNgay, $ketThucNgay])
                    ->whereNotNull('user_id')
                    ->distinct('user_id')
                    ->count('user_id');
                    
                // Số lượng khách hàng không tài khoản
                $soLuongKhachHangKhongTaiKhoan = DonHang::whereHas('suatChieu', function($query) use ($idRap) {
                        $query->whereHas('phongChieu', function($subQuery) use ($idRap) {
                            $subQuery->where('id_rapphim', $idRap);
                        });
                    })
                    ->where('trang_thai', 2) // Đã thanh toán
                    ->whereBetween('ngay_dat', [$batDauNgay, $ketThucNgay])
                    ->whereNull('user_id')
                    ->count();
                
                // Tổng số khách hàng trong ngày
                $soLuongKhachHang = $soLuongKhachHangCoTaiKhoan + $soLuongKhachHangKhongTaiKhoan;
                
                // Phân loại khách hàng theo loại ngày - ưu tiên thứ tự: Tết > Lễ > Cuối tuần > Ngày thường
                if ($laNgayTet) {
                    $khachNgayTet[$ngay] = $soLuongKhachHang;
                } elseif ($laNgayLe) {
                    $khachNgayLe[$ngay] = $soLuongKhachHang;
                } elseif ($laCuoiTuan) {
                    $khachCuoiTuan[$ngay] = $soLuongKhachHang;
                } else {
                    $khachNgayThuong[$ngay] = $soLuongKhachHang;
                }
                
                // Lưu tổng số khách hàng
                $tongKhach[$ngay] = $soLuongKhachHang;
                
                // Lấy tên ngày đặc biệt nếu có
                $tenNgayDacBiet = null;
                if ($laNgayDacBiet) {
                    $tenNgayDacBiet = $ngayDacBiet[$ngay]['loai_ngay'];
                }
                
                // Thêm thông tin vào kết quả
                $ketQua[] = [
                    'ngay' => $ngay,
                    'ngay_formatted' => $ngayDateTime->format('d/m'),
                    'thu_trong_tuan' => $this->getTenThuTrongTuan($thuTrongTuan),
                    'la_cuoi_tuan' => $laCuoiTuan,
                    'la_ngay_le' => $laNgayLe,
                    'la_ngay_tet' => $laNgayTet,  // Thêm trường này
                    'ten_ngay_dac_biet' => $tenNgayDacBiet,
                    'so_luong_khach' => $soLuongKhachHang,
                    'so_luong_khach_co_tai_khoan' => $soLuongKhachHangCoTaiKhoan,
                    'so_luong_khach_khong_tai_khoan' => $soLuongKhachHangKhongTaiKhoan
                ];
            }
            
            // Tính trung bình khách hàng theo loại ngày
            $trungBinhKhachNgayThuong = !empty($khachNgayThuong) ? round(array_sum($khachNgayThuong) / count($khachNgayThuong)) : 0;
            $trungBinhKhachCuoiTuan = !empty($khachCuoiTuan) ? round(array_sum($khachCuoiTuan) / count($khachCuoiTuan)) : 0;
            $trungBinhKhachNgayLe = !empty($khachNgayLe) ? round(array_sum($khachNgayLe) / count($khachNgayLe)) : 0;
            $trungBinhKhachNgayTet = !empty($khachNgayTet) ? round(array_sum($khachNgayTet) / count($khachNgayTet)) : 0;
            
            // Tạo dữ liệu tổng hợp theo loại ngày cho biểu đồ
            $duLieuBieuDo = [
                [
                    'ten' => 'Tổng khách',
                    'color' => '#8b5cf6', // Màu tím
                    'data' => $tongKhach
                ],
                [
                    'ten' => 'Cuối tuần',
                    'color' => '#f59e0b', // Màu cam
                    'data' => $khachCuoiTuan
                ],
                [
                    'ten' => 'Ngày thường',
                    'color' => '#10b981', // Màu xanh lá
                    'data' => $khachNgayThuong
                ]
            ];
            
            // Nếu có ngày lễ, thêm dữ liệu ngày lễ vào biểu đồ
            if (!empty($khachNgayLe)) {
                $duLieuBieuDo[] = [
                    'ten' => 'Ngày lễ',
                    'color' => '#ef4444', // Màu đỏ
                    'data' => $khachNgayLe
                ];
            }
            
            // Nếu có ngày Tết, thêm dữ liệu ngày Tết vào biểu đồ
            if (!empty($khachNgayTet)) {
                $duLieuBieuDo[] = [
                    'ten' => 'Ngày Tết',
                    'color' => '#f97316', // Màu cam đậm
                    'data' => $khachNgayTet
                ];
            }
            
            // Tính tổng số khách trong toàn bộ khoảng thời gian
            $tongSoKhach = array_sum($tongKhach);
            
            // Tìm ngày có nhiều khách nhất và ít khách nhất
            $ngayCoNhieuKhachNhat = array_search(max($tongKhach), $tongKhach);
            $ngayCoItKhachNhat = array_search(min($tongKhach), $tongKhach);
            
            // Định dạng lại ngày để hiển thị đẹp hơn
            $formatNgayCoNhieuKhachNhat = (new \DateTime($ngayCoNhieuKhachNhat))->format('d/m/Y');
            $formatNgayCoItKhachNhat = (new \DateTime($ngayCoItKhachNhat))->format('d/m/Y');
            
            // Kiểm tra xem ngày có nhiều/ít khách nhất có phải ngày đặc biệt không
            $loaiNgayCoNhieuKhachNhat = $this->getLoaiNgay($ngayCoNhieuKhachNhat, $ngayDacBiet, $ngayLe, $ngayTet);
            $loaiNgayCoItKhachNhat = $this->getLoaiNgay($ngayCoItKhachNhat, $ngayDacBiet, $ngayLe, $ngayTet);
            
            // Tính tỷ lệ tăng trưởng khách hàng
            $tyLeTangTruong = 0;
            $soNgay = count($danhSachNgay);
            
            // if ($soNgay > 1) {
            //     // Chia thời gian thành 2 nửa để so sánh
            //     $giuaKhoangThoiGian = floor($soNgay / 2);
                
            //     $nua1 = array_slice($tongKhach, 0, $giuaKhoangThoiGian);
            //     $nua2 = array_slice($tongKhach, $giuaKhoangThoiGian);
                
            //     $tbNua1 = array_sum($nua1) / count($nua1);
            //     $tbNua2 = array_sum($nua2) / count($nua2);
                
            //     if ($tbNua1 > 0) {
            //         $tyLeTangTruong = (($tbNua2 - $tbNua1) / $tbNua1) * 100;
            //     }
            // }
            // Fix lỗi
            if ($soNgay > 1) {
                $giuaKhoangThoiGian = floor($soNgay / 2);
                $nua1 = array_slice($tongKhach, 0, $giuaKhoangThoiGian);
                $nua2 = array_slice($tongKhach, $giuaKhoangThoiGian);

                if (!empty($nua1) && !empty($nua2)) {
                    $tbNua1 = array_sum($nua1) / count($nua1);
                    $tbNua2 = array_sum($nua2) / count($nua2);

                    if ($tbNua1 > 0) {
                        $tyLeTangTruong = (($tbNua2 - $tbNua1) / $tbNua1) * 100;
                    }
                }
            }
            
            return [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
                'chi_tiet_theo_ngay' => $ketQua,
                'du_lieu_bieu_do' => $duLieuBieuDo,
                'tong_ket' => [
                    'tong_so_khach' => $tongSoKhach,
                    'ngay_co_nhieu_khach_nhat' => [
                        'ngay' => $ngayCoNhieuKhachNhat,
                        'ngay_formatted' => $formatNgayCoNhieuKhachNhat,
                        'so_luong_khach' => $tongKhach[$ngayCoNhieuKhachNhat] ?? 0,
                        'loai_ngay' => $loaiNgayCoNhieuKhachNhat
                    ],
                    'ngay_co_it_khach_nhat' => [
                        'ngay' => $ngayCoItKhachNhat,
                        'ngay_formatted' => $formatNgayCoItKhachNhat,
                        'so_luong_khach' => $tongKhach[$ngayCoItKhachNhat] ?? 0,
                        'loai_ngay' => $loaiNgayCoItKhachNhat
                    ],
                    'trung_binh_khach_hang' => [
                        'ngay_thuong' => $trungBinhKhachNgayThuong,
                        'cuoi_tuan' => $trungBinhKhachCuoiTuan,
                        'ngay_le' => $trungBinhKhachNgayLe,
                        'ngay_tet' => $trungBinhKhachNgayTet
                    ],
                    'ty_le_tang_truong' => round($tyLeTangTruong, 2),
                    'ty_le_theo_loai_ngay' => [
                        'ngay_thuong' => (!empty($khachNgayThuong) && $tongSoKhach > 0) ? 
                            round((array_sum($khachNgayThuong) / $tongSoKhach) * 100, 2) : 0,
                        'cuoi_tuan' => (!empty($khachCuoiTuan) && $tongSoKhach > 0) ?
                            round((array_sum($khachCuoiTuan) / $tongSoKhach) * 100, 2) : 0,
                        'ngay_le' => (!empty($khachNgayLe) && $tongSoKhach > 0) ?
                            round((array_sum($khachNgayLe) / $tongSoKhach) * 100, 2) : 0,
                        'ngay_tet' => (!empty($khachNgayTet) && $tongSoKhach > 0) ?
                            round((array_sum($khachNgayTet) / $tongSoKhach) * 100, 2) : 0
                    ]
                ]
            ];
        }

        /**
         * Lấy tên thứ trong tuần từ số thứ tự
         * @param int $thuTrongTuan Số thứ tự trong tuần (1-7)
         * @return string Tên thứ trong tuần
         */
        private function getTenThuTrongTuan($thuTrongTuan) {
            $tenThu = [
                1 => 'Thứ hai',
                2 => 'Thứ ba',
                3 => 'Thứ tư',
                4 => 'Thứ năm',
                5 => 'Thứ sáu',
                6 => 'Thứ bảy',
                7 => 'Chủ nhật'
            ];
            
            return $tenThu[$thuTrongTuan] ?? '';
        }
        /**
         * Xác định loại ngày (ngày thường, cuối tuần, lễ hoặc tết)
         * @param string $ngay Ngày cần kiểm tra
         * @param array $ngayDacBiet Mảng ngày đặc biệt
         * @param array $ngayLe Mảng ngày lễ
         * @param array $ngayTet Mảng ngày tết
         * @return string Loại ngày
         */
        private function getLoaiNgay($ngay, $ngayDacBiet, $ngayLe, $ngayTet) {
            if (isset($ngayTet[$ngay])) {
                return 'Ngày Tết' . (isset($ngayDacBiet[$ngay]) ? ': ' . $ngayDacBiet[$ngay]['loai_ngay'] : '');
            } elseif (isset($ngayLe[$ngay])) {
                return 'Ngày Lễ' . (isset($ngayDacBiet[$ngay]) ? ': ' . $ngayDacBiet[$ngay]['loai_ngay'] : '');
            } else {
                $ngayDateTime = new \DateTime($ngay);
                $thuTrongTuan = (int)$ngayDateTime->format('N');
                if ($thuTrongTuan >= 6) {
                    return 'Cuối tuần: ' . $this->getTenThuTrongTuan($thuTrongTuan);
                } else {
                    return 'Ngày thường: ' . $this->getTenThuTrongTuan($thuTrongTuan);
                }
            }
        }
    /**
     * Phân tích chi tiết về phim, đồ ăn và suất chiếu của rạp trong khoảng thời gian
     * 
     * @param int $idRap ID của rạp cần phân tích
     * @param string $tuNgay Ngày bắt đầu (format: Y-m-d)
     * @param string $denNgay Ngày kết thúc (format: Y-m-d)
     * @return array Dữ liệu phân tích chi tiết
     */
    public function phanTichChiTiet($idRap, $tuNgay, $denNgay) {
        // Định dạng thời gian
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
        
        // Lấy khoảng thời gian cho kỳ trước để so sánh
        $soNgayTrongKhoang = (int)$tuNgayDate->diff($denNgayDate)->format('%a') + 1;
        $tuNgayKyTruocDate = clone $tuNgayDate;
        $denNgayKyTruocDate = clone $tuNgayDate;
        $tuNgayKyTruocDate->modify('-' . $soNgayTrongKhoang . ' days');
        $denNgayKyTruocDate->modify('-1 day');
        $tuNgayKyTruocQuery = $tuNgayKyTruocDate->format('Y-m-d H:i:s');
        $denNgayKyTruocQuery = $denNgayKyTruocDate->format('Y-m-d H:i:s');
        
        // -------------------- PHÂN TÍCH PHIM --------------------
        $danhSachPhim = $this->phanTichPhim($idRap, $tuNgayQuery, $denNgayQuery, $tuNgayKyTruocQuery, $denNgayKyTruocQuery);
        
        // -------------------- PHÂN TÍCH ĐỒ ĂN --------------------
        $danhSachDoAn = $this->phanTichDoAn($idRap, $tuNgayQuery, $denNgayQuery, $tuNgayKyTruocQuery, $denNgayKyTruocQuery);
        
        // -------------------- PHÂN TÍCH SUẤT CHIẾU --------------------
        $danhSachSuatChieu = $this->phanTichSuatChieu($idRap, $tuNgayQuery, $denNgayQuery, $tuNgayKyTruocQuery, $denNgayKyTruocQuery);
        
        return [
            'tu_ngay' => $tuNgay,
            'den_ngay' => $denNgay,
            'ky_truoc' => [
                'tu_ngay' => $tuNgayKyTruocDate->format('Y-m-d'),
                'den_ngay' => $denNgayKyTruocDate->format('Y-m-d'),
            ],
            'phan_tich_phim' => $danhSachPhim,
            'phan_tich_do_an' => $danhSachDoAn,
            'phan_tich_suat_chieu' => $danhSachSuatChieu
        ];
    }

    /**
     * Phân tích chi tiết các phim của rạp
     */
    private function phanTichPhim($idRap, $tuNgayQuery, $denNgayQuery, $tuNgayKyTruocQuery, $denNgayKyTruocQuery) {
        // Lấy danh sách phim được phân phối cho rạp
        $danhSachPhimIdsCollection = PhanPhoiPhim::where('id_rapphim', $idRap)
            ->select('id_phim')
            ->get();

        // Chuyển từ collection thành array
        $danhSachPhimIds = [];
        foreach ($danhSachPhimIdsCollection as $item) {
            $danhSachPhimIds[] = $item->id_phim;
        }
        
        // Lấy doanh thu từ vé cho từng phim trong kỳ hiện tại
        $doanhThuVeCollection = Ve::selectRaw('
                suatchieu.id_phim,
                SUM(ve.gia_ve) as doanh_thu,
                COUNT(ve.id) as so_luot
            ')
            ->join('suatchieu', 've.suat_chieu_id', '=', 'suatchieu.id')
            ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->join('donhang', 've.donhang_id', '=', 'donhang.id')
            ->where('phongchieu.id_rapphim', $idRap)
            ->where('ve.trang_thai', 2) // Vé đã đặt
            ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery])
            ->whereIn('suatchieu.id_phim', $danhSachPhimIds)
            ->groupBy('suatchieu.id_phim')
            ->get();
        
        // Chuyển đổi thành mảng
        $doanhThuPhim = [];
        $soLuotPhim = [];
        foreach ($doanhThuVeCollection as $item) {
            $doanhThuPhim[$item->id_phim] = $item->doanh_thu;
            $soLuotPhim[$item->id_phim] = $item->so_luot;
        }
        
        // Lấy doanh thu từ vé cho từng phim trong kỳ trước
        $doanhThuVeKyTruocCollection = Ve::selectRaw('
                suatchieu.id_phim,
                SUM(ve.gia_ve) as doanh_thu
            ')
            ->join('suatchieu', 've.suat_chieu_id', '=', 'suatchieu.id')
            ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->join('donhang', 've.donhang_id', '=', 'donhang.id')
            ->where('phongchieu.id_rapphim', $idRap)
            ->where('ve.trang_thai', 2) // Vé đã đặt
            ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
            ->whereBetween('donhang.ngay_dat', [$tuNgayKyTruocQuery, $denNgayKyTruocQuery])
            ->whereIn('suatchieu.id_phim', $danhSachPhimIds)
            ->groupBy('suatchieu.id_phim')
            ->get();
        
        // Chuyển đổi thành mảng
        $doanhThuPhimKyTruoc = [];
        foreach ($doanhThuVeKyTruocCollection as $item) {
            $doanhThuPhimKyTruoc[$item->id_phim] = $item->doanh_thu;
        }
        
        // Lấy tổng doanh thu phim để tính tỷ lệ đóng góp
        $tongDoanhThuPhim = array_sum($doanhThuPhim);
        
        // Lấy thông tin chi tiết của tất cả phim
        $thongTinPhimCollection = Phim::whereIn('id', $danhSachPhimIds)
            ->get();
        
        // Tạo kết quả phân tích phim
        $ketQua = [];
        foreach ($thongTinPhimCollection as $phim) {
            $phimId = $phim->id;
            $doanhThu = isset($doanhThuPhim[$phimId]) ? $doanhThuPhim[$phimId] : 0;
            $soLuot = isset($soLuotPhim[$phimId]) ? $soLuotPhim[$phimId] : 0;
            $doanhThuKyTruoc = isset($doanhThuPhimKyTruoc[$phimId]) ? $doanhThuPhimKyTruoc[$phimId] : 0;
            
            // Tính tỷ lệ đóng góp
            $tyLeDongGop = $tongDoanhThuPhim > 0 ? round(($doanhThu / $tongDoanhThuPhim) * 100, 1) : 0;
            
            // Tính tỷ lệ thay đổi so với kỳ trước
            $tyLeThayDoi = 0;
            $isIncreased = true;
            if ($doanhThuKyTruoc > 0) {
                $tyLeThayDoi = round((($doanhThu - $doanhThuKyTruoc) / $doanhThuKyTruoc) * 100, 1);
                $isIncreased = $tyLeThayDoi >= 0;
            }
            
            $ketQua[] = [
                'id' => $phimId,
                'ten_phim' => $phim->ten_phim,
                'doanh_thu' => $doanhThu,
                'doanh_thu_formatted' => number_format($doanhThu, 0, ',', '.') . ' đ',
                'so_luot' => $soLuot,
                'ty_le_dong_gop' => $tyLeDongGop,
                'ty_le_dong_gop_formatted' => $tyLeDongGop . '%',
                'so_voi_ky_truoc' => [
                    'ty_le' => abs($tyLeThayDoi),
                    'ty_le_formatted' => abs($tyLeThayDoi) . '%',
                    'tang' => $isIncreased
                ]
            ];
        }
        
        // Sắp xếp theo doanh thu giảm dần
        usort($ketQua, function($a, $b) {
            return $b['doanh_thu'] <=> $a['doanh_thu'];
        });
        
        return $ketQua;
    }

    /**
     * Phân tích chi tiết các đồ ăn của rạp
     */
    private function phanTichDoAn($idRap, $tuNgayQuery, $denNgayQuery, $tuNgayKyTruocQuery, $denNgayKyTruocQuery) {
        // Lấy danh sách sản phẩm của rạp
        $danhSachSanPhamIds = SanPham::where('id_rapphim', $idRap)
            ->where('trang_thai', 1) // Chỉ lấy sản phẩm đang bán
            ->pluck('id')
            ->toArray();
        
        // Lấy doanh thu từng sản phẩm trong kỳ hiện tại
        $doanhThuSanPhamCollection = ChiTietDonHang::selectRaw('
                chitiet_donhang.sanpham_id,
                SUM(chitiet_donhang.thanh_tien) as doanh_thu,
                SUM(chitiet_donhang.so_luong) as so_luot
            ')
            ->join('donhang', 'chitiet_donhang.donhang_id', '=', 'donhang.id')
            ->join('suatchieu', 'donhang.suat_chieu_id', '=', 'suatchieu.id')
            ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->where('phongchieu.id_rapphim', $idRap)
            ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery])
            ->whereIn('chitiet_donhang.sanpham_id', $danhSachSanPhamIds)
            ->groupBy('chitiet_donhang.sanpham_id')
            ->get();
        
        // Chuyển đổi thành mảng
        $doanhThuSanPham = [];
        $soLuotSanPham = [];
        foreach ($doanhThuSanPhamCollection as $item) {
            $doanhThuSanPham[$item->sanpham_id] = $item->doanh_thu;
            $soLuotSanPham[$item->sanpham_id] = $item->so_luot;
        }
        
        // Lấy doanh thu từng sản phẩm trong kỳ trước
        $doanhThuSanPhamKyTruocCollection = ChiTietDonHang::selectRaw('
                chitiet_donhang.sanpham_id,
                SUM(chitiet_donhang.thanh_tien) as doanh_thu
            ')
            ->join('donhang', 'chitiet_donhang.donhang_id', '=', 'donhang.id')
            ->join('suatchieu', 'donhang.suat_chieu_id', '=', 'suatchieu.id')
            ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->where('phongchieu.id_rapphim', $idRap)
            ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
            ->whereBetween('donhang.ngay_dat', [$tuNgayKyTruocQuery, $denNgayKyTruocQuery])
            ->whereIn('chitiet_donhang.sanpham_id', $danhSachSanPhamIds)
            ->groupBy('chitiet_donhang.sanpham_id')
            ->get();
        
        // Chuyển đổi thành mảng
        $doanhThuSanPhamKyTruoc = [];
        foreach ($doanhThuSanPhamKyTruocCollection as $item) {
            $doanhThuSanPhamKyTruoc[$item->sanpham_id] = $item->doanh_thu;
        }
        
        // Lấy tổng doanh thu sản phẩm để tính tỷ lệ đóng góp
        $tongDoanhThuSanPham = array_sum($doanhThuSanPham);
        
        // Lấy thông tin chi tiết của tất cả sản phẩm
        $thongTinSanPhamCollection = SanPham::whereIn('id', $danhSachSanPhamIds)
            ->with(['danhMuc'])
            ->get();
        
        // Tạo kết quả phân tích sản phẩm
        $ketQua = [];
        foreach ($thongTinSanPhamCollection as $sanPham) {
            $sanPhamId = $sanPham->id;
            $doanhThu = isset($doanhThuSanPham[$sanPhamId]) ? $doanhThuSanPham[$sanPhamId] : 0;
            $soLuot = isset($soLuotSanPham[$sanPhamId]) ? $soLuotSanPham[$sanPhamId] : 0;
            $doanhThuKyTruoc = isset($doanhThuSanPhamKyTruoc[$sanPhamId]) ? $doanhThuSanPhamKyTruoc[$sanPhamId] : 0;
            
            // Tính tỷ lệ đóng góp
            $tyLeDongGop = $tongDoanhThuSanPham > 0 ? round(($doanhThu / $tongDoanhThuSanPham) * 100, 1) : 0;
            
            // Tính tỷ lệ thay đổi so với kỳ trước
            $tyLeThayDoi = 0;
            $isIncreased = true;
            if ($doanhThuKyTruoc > 0) {
                $tyLeThayDoi = round((($doanhThu - $doanhThuKyTruoc) / $doanhThuKyTruoc) * 100, 1);
                $isIncreased = $tyLeThayDoi >= 0;
            }
            
            // Tên hiển thị với kích thước
            $tenHienThi = $sanPham->ten;
            if ($sanPham->danhMuc && ($sanPham->danhMuc->ten == 'Đồ uống' || $sanPham->danhMuc->ten == 'Nước')) {
                $tenHienThi .= ' (lớn)';
            }
            
            $ketQua[] = [
                'id' => $sanPhamId,
                'ten_san_pham' => $tenHienThi,
                'doanh_thu' => $doanhThu,
                'doanh_thu_formatted' => number_format($doanhThu, 0, ',', '.') . ' đ',
                'so_luot' => $soLuot,
                'ty_le_dong_gop' => $tyLeDongGop,
                'ty_le_dong_gop_formatted' => $tyLeDongGop . '%',
                'so_voi_ky_truoc' => [
                    'ty_le' => abs($tyLeThayDoi),
                    'ty_le_formatted' => abs($tyLeThayDoi) . '%',
                    'tang' => $isIncreased
                ]
            ];
        }
        
        // Sắp xếp theo doanh thu giảm dần
        usort($ketQua, function($a, $b) {
            return $b['doanh_thu'] <=> $a['doanh_thu'];
        });
        
        return $ketQua;
    }

    /**
     * Phân tích chi tiết các suất chiếu theo khung giờ
     */
    private function phanTichSuatChieu($idRap, $tuNgayQuery, $denNgayQuery, $tuNgayKyTruocQuery, $denNgayKyTruocQuery) {
        // Định nghĩa các khung giờ từ 8:00 đến 24:00
        $khungGio = [
            ['batDau' => '08:00:00', 'ketThuc' => '10:00:00', 'label' => '8:00 - 10:00'],
            ['batDau' => '10:00:00', 'ketThuc' => '12:00:00', 'label' => '10:00 - 12:00'],
            ['batDau' => '12:00:00', 'ketThuc' => '14:00:00', 'label' => '12:00 - 14:00'],
            ['batDau' => '14:00:00', 'ketThuc' => '16:00:00', 'label' => '14:00 - 16:00'],
            ['batDau' => '16:00:00', 'ketThuc' => '18:00:00', 'label' => '16:00 - 18:00'],
            ['batDau' => '18:00:00', 'ketThuc' => '20:00:00', 'label' => '18:00 - 20:00'],
            ['batDau' => '20:00:00', 'ketThuc' => '22:00:00', 'label' => '20:00 - 22:00'],
            ['batDau' => '22:00:00', 'ketThuc' => '24:00:00', 'label' => '22:00 - 24:00']
        ];
        
        $ketQua = [];
        
        // Lấy ra dữ liệu cho từng khung giờ trong kỳ hiện tại
        foreach ($khungGio as $kg) {
            $batDauGio = $kg['batDau'];
            $ketThucGio = $kg['ketThuc'];
            
            // Dữ liệu suất chiếu trong kỳ hiện tại
            $thongKeHienTai = $this->thongKeSuatChieuTheoKhungGio(
                $idRap, $tuNgayQuery, $denNgayQuery, $batDauGio, $ketThucGio
            );
            
            // Dữ liệu suất chiếu trong kỳ trước
            $thongKeKyTruoc = $this->thongKeSuatChieuTheoKhungGio(
                $idRap, $tuNgayKyTruocQuery, $denNgayKyTruocQuery, $batDauGio, $ketThucGio
            );
            
            // Tính tỷ lệ thay đổi
            $tyLeThayDoi = 0;
            $isIncreased = true;
            if ($thongKeKyTruoc['doanh_thu'] > 0) {
                $tyLeThayDoi = round((($thongKeHienTai['doanh_thu'] - $thongKeKyTruoc['doanh_thu']) / $thongKeKyTruoc['doanh_thu']) * 100, 1);
                $isIncreased = $tyLeThayDoi >= 0;
            }
            
            $ketQua[] = [
                'khung_gio' => $kg['label'],
                'doanh_thu' => $thongKeHienTai['doanh_thu'],
                'doanh_thu_formatted' => number_format($thongKeHienTai['doanh_thu'], 0, ',', '.') . ' đ',
                'ty_le_lap_day' => $thongKeHienTai['ty_le_lap_day'],
                'ty_le_lap_day_formatted' => $thongKeHienTai['ty_le_lap_day'] . '%',
                'ty_le_dong_gop' => $thongKeHienTai['ty_le_dong_gop'],
                'ty_le_dong_gop_formatted' => $thongKeHienTai['ty_le_dong_gop'] . '%',
                'so_voi_ky_truoc' => [
                    'ty_le' => abs($tyLeThayDoi),
                    'ty_le_formatted' => abs($tyLeThayDoi) . '%',
                    'tang' => $isIncreased
                ]
            ];
        }
        
        return $ketQua;
    }

    /**
     * Thống kê suất chiếu theo khung giờ
     */
    private function thongKeSuatChieuTheoKhungGio($idRap, $tuNgay, $denNgay, $batDauGio, $ketThucGio) {
        // Lấy danh sách suất chiếu trong khung giờ và khoảng thời gian
        $suatChieuCollection = SuatChieu::whereHas('phongChieu', function($query) use ($idRap) {
                $query->where('id_rapphim', $idRap);
            })
            ->whereBetween('batdau', [$tuNgay, $denNgay])
            ->whereRaw("TIME(batdau) >= ?", [$batDauGio])
            ->whereRaw("TIME(batdau) < ?", [$ketThucGio])
            ->get();
        
        // Khởi tạo các biến thống kê
        $soLuongSuatChieu = $suatChieuCollection->count();
        $tongSoGhe = 0;
        $tongSoVeDaBan = 0;
        $tongDoanhThuVe = 0;
        $tongDoanhThuSanPham = 0;
        
        // Nếu không có suất chiếu nào trong khung giờ này
        if ($soLuongSuatChieu == 0) {
            return [
                'doanh_thu' => 0,
                'ty_le_lap_day' => 0,
                'ty_le_dong_gop' => 0
            ];
        }
        
        // Duyệt qua từng suất chiếu để tính số ghế và doanh thu
        foreach ($suatChieuCollection as $suatChieu) {
            $idSuatChieu = $suatChieu->id;
            $soGhePhong = $suatChieu->phongChieu->so_luong_ghe;
            $tongSoGhe += $soGhePhong;
            
            // Đếm số vé đã bán cho suất chiếu này
            $soVeDaBan = Ve::where('suat_chieu_id', $idSuatChieu)
                ->where('trang_thai', 2) // Vé đã đặt
                ->count();
            $tongSoVeDaBan += $soVeDaBan;
            
            // Tính doanh thu vé của suất chiếu này
            $doanhThuVeSuatChieu = Ve::where('suat_chieu_id', $idSuatChieu)
                ->where('trang_thai', 2) // Vé đã đặt
                ->sum('gia_ve');
            $tongDoanhThuVe += $doanhThuVeSuatChieu;
            
            // Tính doanh thu sản phẩm từ các đơn hàng của suất chiếu này
            $doanhThuSanPhamSuatChieu = ChiTietDonHang::whereHas('donHang', function($query) use ($idSuatChieu) {
                    $query->where('suat_chieu_id', $idSuatChieu)
                        ->where('trang_thai', 2); // Đơn hàng đã thanh toán
                })
                ->sum('thanh_tien');
            $tongDoanhThuSanPham += $doanhThuSanPhamSuatChieu;
        }
        
        // Tính tỷ lệ lấp đầy ghế
        $tyLeLapDay = $tongSoGhe > 0 ? round(($tongSoVeDaBan / $tongSoGhe) * 100) : 0;
        
        // Tính tổng doanh thu
        $tongDoanhThu = $tongDoanhThuVe + $tongDoanhThuSanPham;
        
        // Tính tỷ lệ đóng góp (sẽ được cập nhật sau khi có tổng doanh thu)
        $tyLeDongGop = 0; // Cần tính tổng doanh thu của tất cả khung giờ
        
        return [
            'doanh_thu' => $tongDoanhThu,
            'ty_le_lap_day' => $tyLeLapDay,
            'ty_le_dong_gop' => $tyLeDongGop, // Sẽ cập nhật sau
            'so_luong_suat_chieu' => $soLuongSuatChieu,
            'so_ve_ban' => $tongSoVeDaBan,
            'tong_so_ghe' => $tongSoGhe
        ];
    }

    /**
     * Thống kê toàn rạp - Hiển thị các chỉ số tổng quan
     * 
     * @param string $tuNgay Ngày bắt đầu (format: Y-m-d)
     * @param string $denNgay Ngày kết thúc (format: Y-m-d)
     * @param mixed $idRap ID của rạp cụ thể hoặc 'all' cho tất cả rạp
     * @param bool $soSanhVoiKyTruoc Có so sánh với kỳ trước không
     * @return array Dữ liệu thống kê tổng quan
     */
    public function thongKeTongQuanToanRap($tuNgay, $denNgay, $idRap = 'all', $soSanhVoiKyTruoc = false) {
        // Định dạng thời gian
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
        
        // Tính khoảng thời gian kỳ trước (để so sánh)
        $soNgayTrongKhoang = (int)$tuNgayDate->diff($denNgayDate)->format('%a') + 1;
        $tuNgayKyTruocDate = clone $tuNgayDate;
        $denNgayKyTruocDate = clone $tuNgayDate;
        $tuNgayKyTruocDate->modify('-' . $soNgayTrongKhoang . ' days');
        $denNgayKyTruocDate->modify('-1 day');
        $tuNgayKyTruocQuery = $tuNgayKyTruocDate->format('Y-m-d H:i:s');
        $denNgayKyTruocQuery = $denNgayKyTruocDate->format('Y-m-d H:i:s');
        
        // -------------------- KỲ HIỆN TẠI --------------------
        
        // 1. TỔNG DOANH THU (Vé + F&B)
        $doanhThuVe = $this->tinhDoanhThuVe($idRap, $tuNgayQuery, $denNgayQuery);
        $doanhThuFnB = $this->tinhDoanhThuFnB($idRap, $tuNgayQuery, $denNgayQuery);
        $tongDoanhThu = $doanhThuVe + $doanhThuFnB;
        
        // 2. TỔNG VÉ BÁN
        $tongVeBan = $this->tinhTongVeBan($idRap, $tuNgayQuery, $denNgayQuery);
        
        // 3. TỈ LỆ LẤP ĐẦY
        $tyLeLapDay = $this->tinhTyLeLapDay($idRap, $tuNgayQuery, $denNgayQuery);
        
        // -------------------- KỲ TRƯỚC (Nếu cần so sánh) --------------------
        $phanTramThayDoiDoanhThu = 0;
        $phanTramThayDoiVeBan = 0;
        $phanTramThayDoiLapDay = 0;
        $phanTramThayDoiFnB = 0;
        
        if ($soSanhVoiKyTruoc) {
            // Tính các chỉ số kỳ trước
            $doanhThuVeKyTruoc = $this->tinhDoanhThuVe($idRap, $tuNgayKyTruocQuery, $denNgayKyTruocQuery);
            $doanhThuFnBKyTruoc = $this->tinhDoanhThuFnB($idRap, $tuNgayKyTruocQuery, $denNgayKyTruocQuery);
            $tongDoanhThuKyTruoc = $doanhThuVeKyTruoc + $doanhThuFnBKyTruoc;
            
            $tongVeBanKyTruoc = $this->tinhTongVeBan($idRap, $tuNgayKyTruocQuery, $denNgayKyTruocQuery);
            $tyLeLapDayKyTruoc = $this->tinhTyLeLapDay($idRap, $tuNgayKyTruocQuery, $denNgayKyTruocQuery);
            
            // Tính phần trăm thay đổi
            $phanTramThayDoiDoanhThu = $tongDoanhThuKyTruoc > 0 
                ? (($tongDoanhThu - $tongDoanhThuKyTruoc) / $tongDoanhThuKyTruoc) * 100 
                : 0;
            
            $phanTramThayDoiVeBan = $tongVeBanKyTruoc > 0 
                ? (($tongVeBan - $tongVeBanKyTruoc) / $tongVeBanKyTruoc) * 100 
                : 0;
            
            $phanTramThayDoiLapDay = $tyLeLapDayKyTruoc > 0 
                ? (($tyLeLapDay - $tyLeLapDayKyTruoc) / $tyLeLapDayKyTruoc) * 100 
                : 0;
            
            $phanTramThayDoiFnB = $doanhThuFnBKyTruoc > 0 
                ? (($doanhThuFnB - $doanhThuFnBKyTruoc) / $doanhThuFnBKyTruoc) * 100 
                : 0;
        }
        
        return [
            'tong_doanh_thu' => round($tongDoanhThu, 0),
            'tong_ve_ban' => $tongVeBan,
            'ty_le_lap_day' => round($tyLeLapDay, 2),
            'doanh_thu_fnb' => round($doanhThuFnB, 0),
            'so_sanh' => $soSanhVoiKyTruoc ? [
                'phan_tram_thay_doi_doanh_thu' => round($phanTramThayDoiDoanhThu, 2),
                'phan_tram_thay_doi_ve_ban' => round($phanTramThayDoiVeBan, 2),
                'phan_tram_thay_doi_lap_day' => round($phanTramThayDoiLapDay, 2),
                'phan_tram_thay_doi_fnb' => round($phanTramThayDoiFnB, 2),
            ] : null,
            'thong_tin_khoang_thoi_gian' => [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
                'so_ngay' => $soNgayTrongKhoang,
            ]
        ];
    }
    
    /**
     * Tính tổng doanh thu từ vé
     */
    private function tinhDoanhThuVe($idRap, $tuNgay, $denNgay) {
        $query = Ve::whereBetween('ngay_tao', [$tuNgay, $denNgay])
            ->where('trang_thai', 2); // Vé đã thanh toán
        
        if ($idRap !== 'all') {
            $query->whereHas('suatchieu', function($q) use ($idRap) {
                $q->whereHas('phongChieu', function($subQuery) use ($idRap) {
                    $subQuery->where('id_rapphim', $idRap);
                });
            });
        }
        
        return $query->sum('gia_ve');
    }
    
    /**
     * Tính tổng doanh thu từ F&B
     */
    private function tinhDoanhThuFnB($idRap, $tuNgay, $denNgay) {
        $query = ChiTietDonHang::join('donhang', 'chitiet_donhang.donhang_id', '=', 'donhang.id')
            ->join('san_pham', 'chitiet_donhang.sanpham_id', '=', 'san_pham.id')
            ->whereBetween('donhang.ngay_dat', [$tuNgay, $denNgay])
            ->where('donhang.trang_thai', 2); // Đơn hàng đã thanh toán
        
        if ($idRap !== 'all') {
            $query->where('san_pham.id_rapphim', $idRap);
        }
        
        // Tính tổng doanh thu = sum(so_luong * don_gia)
        $chiTiet = $query->get(['chitiet_donhang.so_luong', 'chitiet_donhang.don_gia']);
        $tongDoanhThu = 0;
        
        foreach ($chiTiet as $item) {
            $tongDoanhThu += ($item->so_luong * $item->don_gia);
        }
        
        return $tongDoanhThu;
    }
    
    /**
     * Tính tổng số vé đã bán
     */
    private function tinhTongVeBan($idRap, $tuNgay, $denNgay) {
        $query = Ve::whereBetween('ngay_tao', [$tuNgay, $denNgay])
            ->where('trang_thai', 2); // Vé đã thanh toán
        
        if ($idRap !== 'all') {
            $query->whereHas('suatchieu', function($q) use ($idRap) {
                $q->whereHas('phongChieu', function($subQuery) use ($idRap) {
                    $subQuery->where('id_rapphim', $idRap);
                });
            });
        }
        
        return $query->count();
    }
    
    /**
     * Tính tỉ lệ lấp đầy trung bình (%)
     */
    private function tinhTyLeLapDay($idRap, $tuNgay, $denNgay) {
        // Lấy danh sách suất chiếu trong khoảng thời gian
        $query = SuatChieu::whereBetween('batdau', [$tuNgay, $denNgay]);
        
        if ($idRap !== 'all') {
            $query->whereHas('phongChieu', function($q) use ($idRap) {
                $q->where('id_rapphim', $idRap);
            });
        }
        
        $danhSachSuatChieu = $query->get();
        
        if ($danhSachSuatChieu->count() === 0) {
            return 0;
        }
        
        $tongSoGhe = 0;
        $tongSoVeDaBan = 0;
        
        foreach ($danhSachSuatChieu as $suatChieu) {
            // Lấy số ghế từ phòng chiếu
            $soGhe = $suatChieu->phongChieu->so_luong_ghe ?? 0;
            $tongSoGhe += $soGhe;
            
            // Đếm số vé đã bán cho suất chiếu này
            $soVeDaBan = Ve::where('suat_chieu_id', $suatChieu->id)
                ->where('trang_thai', 2) // Vé đã thanh toán
                ->count();
            $tongSoVeDaBan += $soVeDaBan;
        }
        
        if ($tongSoGhe === 0) {
            return 0;
        }
        
        return ($tongSoVeDaBan / $tongSoGhe) * 100;
    }

    /**
     * Xu hướng doanh thu toàn rạp theo thời gian
     * Trả về dữ liệu cho biểu đồ line chart
     * 
     * @param string $tuNgay Ngày bắt đầu
     * @param string $denNgay Ngày kết thúc
     * @param mixed $idRap ID rạp hoặc 'all'
     * @param string $loaiXuHuong 'daily', 'weekly', 'monthly'
     * @return array Dữ liệu xu hướng doanh thu
     */
    public function xuHuongDoanhThuToanRap($tuNgay, $denNgay, $idRap = 'all', $loaiXuHuong = 'daily') {
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        
        $danhSachNgay = [];
        $danhSachDoanhThu = [];
        
        if ($loaiXuHuong === 'daily') {
            // Xu hướng theo ngày
            $currentDate = clone $tuNgayDate;
            while ($currentDate <= $denNgayDate) {
                $ngayBatDau = $currentDate->format('Y-m-d 00:00:00');
                $ngayKetThuc = $currentDate->format('Y-m-d 23:59:59');
                
                $doanhThuVe = $this->tinhDoanhThuVe($idRap, $ngayBatDau, $ngayKetThuc);
                $doanhThuFnB = $this->tinhDoanhThuFnB($idRap, $ngayBatDau, $ngayKetThuc);
                
                $danhSachNgay[] = $currentDate->format('d/m');
                $danhSachDoanhThu[] = [
                    'ngay' => $currentDate->format('Y-m-d'),
                    'ngay_hien_thi' => $currentDate->format('d/m'),
                    'doanh_thu_ve' => $doanhThuVe,
                    'doanh_thu_fnb' => $doanhThuFnB,
                    'tong_doanh_thu' => $doanhThuVe + $doanhThuFnB
                ];
                
                $currentDate->modify('+1 day');
            }
        } elseif ($loaiXuHuong === 'weekly') {
            // Xu hướng theo tuần
            $currentDate = clone $tuNgayDate;
            $weekStart = clone $currentDate;
            $weekNumber = 1;
            
            while ($currentDate <= $denNgayDate) {
                $weekEnd = clone $weekStart;
                $weekEnd->modify('+6 days');
                
                if ($weekEnd > $denNgayDate) {
                    $weekEnd = clone $denNgayDate;
                }
                
                $ngayBatDau = $weekStart->format('Y-m-d 00:00:00');
                $ngayKetThuc = $weekEnd->format('Y-m-d 23:59:59');
                
                $doanhThuVe = $this->tinhDoanhThuVe($idRap, $ngayBatDau, $ngayKetThuc);
                $doanhThuFnB = $this->tinhDoanhThuFnB($idRap, $ngayBatDau, $ngayKetThuc);
                
                $danhSachNgay[] = 'Tuần ' . $weekNumber;
                $danhSachDoanhThu[] = [
                    'tuan' => $weekNumber,
                    'tu_ngay' => $weekStart->format('d/m'),
                    'den_ngay' => $weekEnd->format('d/m'),
                    'ngay_hien_thi' => $weekStart->format('d/m') . ' - ' . $weekEnd->format('d/m'),
                    'doanh_thu_ve' => $doanhThuVe,
                    'doanh_thu_fnb' => $doanhThuFnB,
                    'tong_doanh_thu' => $doanhThuVe + $doanhThuFnB
                ];
                
                $weekStart->modify('+7 days');
                $currentDate = clone $weekStart;
                $weekNumber++;
            }
        } elseif ($loaiXuHuong === 'monthly') {
            // Xu hướng theo tháng
            $currentDate = clone $tuNgayDate;
            
            while ($currentDate <= $denNgayDate) {
                $monthStart = clone $currentDate;
                $monthStart->modify('first day of this month');
                $monthEnd = clone $currentDate;
                $monthEnd->modify('last day of this month');
                
                // Giới hạn trong khoảng tuNgay - denNgay
                if ($monthStart < $tuNgayDate) {
                    $monthStart = clone $tuNgayDate;
                }
                if ($monthEnd > $denNgayDate) {
                    $monthEnd = clone $denNgayDate;
                }
                
                $ngayBatDau = $monthStart->format('Y-m-d 00:00:00');
                $ngayKetThuc = $monthEnd->format('Y-m-d 23:59:59');
                
                $doanhThuVe = $this->tinhDoanhThuVe($idRap, $ngayBatDau, $ngayKetThuc);
                $doanhThuFnB = $this->tinhDoanhThuFnB($idRap, $ngayBatDau, $ngayKetThuc);
                
                $danhSachNgay[] = $currentDate->format('m/Y');
                $danhSachDoanhThu[] = [
                    'thang' => $currentDate->format('m'),
                    'nam' => $currentDate->format('Y'),
                    'ngay_hien_thi' => $currentDate->format('m/Y'),
                    'doanh_thu_ve' => $doanhThuVe,
                    'doanh_thu_fnb' => $doanhThuFnB,
                    'tong_doanh_thu' => $doanhThuVe + $doanhThuFnB
                ];
                
                $currentDate->modify('first day of next month');
            }
        }
        
        return [
            'loai_xu_huong' => $loaiXuHuong,
            'danh_sach_nhan' => $danhSachNgay,
            'chi_tiet' => $danhSachDoanhThu
        ];
    }

    /**
     * Xu hướng vé bán toàn rạp theo thời gian
     * Trả về dữ liệu cho biểu đồ line chart
     * 
     * @param string $tuNgay Ngày bắt đầu
     * @param string $denNgay Ngày kết thúc
     * @param mixed $idRap ID rạp hoặc 'all'
     * @param string $loaiXuHuong 'daily', 'weekly', 'monthly'
     * @return array Dữ liệu xu hướng vé bán
     */
    public function xuHuongVeBanToanRap($tuNgay, $denNgay, $idRap = 'all', $loaiXuHuong = 'daily') {
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        
        $danhSachNgay = [];
        $danhSachVe = [];
        
        if ($loaiXuHuong === 'daily') {
            // Xu hướng theo ngày
            $currentDate = clone $tuNgayDate;
            while ($currentDate <= $denNgayDate) {
                $ngayBatDau = $currentDate->format('Y-m-d 00:00:00');
                $ngayKetThuc = $currentDate->format('Y-m-d 23:59:59');
                
                $soVeBan = $this->tinhTongVeBan($idRap, $ngayBatDau, $ngayKetThuc);
                
                $danhSachNgay[] = $currentDate->format('d/m');
                $danhSachVe[] = [
                    'ngay' => $currentDate->format('Y-m-d'),
                    'ngay_hien_thi' => $currentDate->format('d/m'),
                    'so_ve_ban' => $soVeBan
                ];
                
                $currentDate->modify('+1 day');
            }
        } elseif ($loaiXuHuong === 'weekly') {
            // Xu hướng theo tuần
            $currentDate = clone $tuNgayDate;
            $weekStart = clone $currentDate;
            $weekNumber = 1;
            
            while ($currentDate <= $denNgayDate) {
                $weekEnd = clone $weekStart;
                $weekEnd->modify('+6 days');
                
                if ($weekEnd > $denNgayDate) {
                    $weekEnd = clone $denNgayDate;
                }
                
                $ngayBatDau = $weekStart->format('Y-m-d 00:00:00');
                $ngayKetThuc = $weekEnd->format('Y-m-d 23:59:59');
                
                $soVeBan = $this->tinhTongVeBan($idRap, $ngayBatDau, $ngayKetThuc);
                
                $danhSachNgay[] = 'Tuần ' . $weekNumber;
                $danhSachVe[] = [
                    'tuan' => $weekNumber,
                    'tu_ngay' => $weekStart->format('d/m'),
                    'den_ngay' => $weekEnd->format('d/m'),
                    'ngay_hien_thi' => $weekStart->format('d/m') . ' - ' . $weekEnd->format('d/m'),
                    'so_ve_ban' => $soVeBan
                ];
                
                $weekStart->modify('+7 days');
                $currentDate = clone $weekStart;
                $weekNumber++;
            }
        } elseif ($loaiXuHuong === 'monthly') {
            // Xu hướng theo tháng
            $currentDate = clone $tuNgayDate;
            
            while ($currentDate <= $denNgayDate) {
                $monthStart = clone $currentDate;
                $monthStart->modify('first day of this month');
                $monthEnd = clone $currentDate;
                $monthEnd->modify('last day of this month');
                
                // Giới hạn trong khoảng tuNgay - denNgay
                if ($monthStart < $tuNgayDate) {
                    $monthStart = clone $tuNgayDate;
                }
                if ($monthEnd > $denNgayDate) {
                    $monthEnd = clone $denNgayDate;
                }
                
                $ngayBatDau = $monthStart->format('Y-m-d 00:00:00');
                $ngayKetThuc = $monthEnd->format('Y-m-d 23:59:59');
                
                $soVeBan = $this->tinhTongVeBan($idRap, $ngayBatDau, $ngayKetThuc);
                
                $danhSachNgay[] = $currentDate->format('m/Y');
                $danhSachVe[] = [
                    'thang' => $currentDate->format('m'),
                    'nam' => $currentDate->format('Y'),
                    'ngay_hien_thi' => $currentDate->format('m/Y'),
                    'so_ve_ban' => $soVeBan
                ];
                
                $currentDate->modify('first day of next month');
            }
        }
        
        return [
            'loai_xu_huong' => $loaiXuHuong,
            'danh_sach_nhan' => $danhSachNgay,
            'chi_tiet' => $danhSachVe
        ];
    }

    /**
     * Top 10 phim có doanh thu cao nhất toàn rạp
     * 
     * @param string $tuNgay Ngày bắt đầu (format: Y-m-d)
     * @param string $denNgay Ngày kết thúc (format: Y-m-d)
     * @param mixed $idRap ID của rạp cụ thể hoặc 'all' cho tất cả rạp
     * @return array Danh sách 10 phim có doanh thu cao nhất
     */
    public function top10PhimToanRap($tuNgay, $denNgay, $idRap = 'all') {
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');

        // Query doanh thu vé theo phim
        $query = Ve::selectRaw('
                phim.id as id_phim,
                phim.ten_phim,
                phim.poster_url,
                SUM(ve.gia_ve) as doanh_thu_ve,
                COUNT(ve.id) as so_ve_ban
            ')
            ->join('suatchieu', 've.suat_chieu_id', '=', 'suatchieu.id')
            ->join('phim', 'suatchieu.id_phim', '=', 'phim.id')
            ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->whereBetween('ve.ngay_tao', [$tuNgayQuery, $denNgayQuery])
            ->where('ve.trang_thai', 2); // Trạng thái 2: Đã đặt

        // Filter theo rạp nếu cần
        if ($idRap !== 'all') {
            $query->where('phongchieu.id_rapphim', $idRap);
        }

        $result = $query->groupBy('phim.id', 'phim.ten_phim', 'phim.poster_url')
            ->orderByRaw('SUM(ve.gia_ve) DESC')
            ->limit(10)
            ->get();

        $danhSach = [];
        foreach ($result as $item) {
            $danhSach[] = [
                'id_phim' => $item->id_phim,
                'ten_phim' => $item->ten_phim,
                'poster_url' => $item->poster_url,
                'doanh_thu' => (float)$item->doanh_thu_ve,
                'so_ve_ban' => (int)$item->so_ve_ban
            ];
        }

        return [
            'danh_sach' => $danhSach,
            'tong_so' => count($danhSach)
        ];
    }

    /**
     * Top 10 sản phẩm F&B có doanh thu cao nhất toàn rạp
     * 
     * @param string $tuNgay Ngày bắt đầu (format: Y-m-d)
     * @param string $denNgay Ngày kết thúc (format: Y-m-d)
     * @param mixed $idRap ID của rạp cụ thể hoặc 'all' cho tất cả rạp
     * @return array Danh sách 10 sản phẩm có doanh thu cao nhất
     */
    public function top10SanPhamToanRap($tuNgay, $denNgay, $idRap = 'all') {
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');

        // Query doanh thu sản phẩm
        $query = ChiTietDonHang::selectRaw('
                san_pham.id as id_san_pham,
                san_pham.ten_san_pham,
                san_pham.hinh_anh,
                SUM(chi_tiet_don_hang.so_luong * chi_tiet_don_hang.gia_ban) as doanh_thu,
                SUM(chi_tiet_don_hang.so_luong) as so_luong_ban
            ')
            ->join('san_pham', 'chi_tiet_don_hang.id_sanpham', '=', 'san_pham.id')
            ->join('don_hang', 'chi_tiet_don_hang.id_donhang', '=', 'don_hang.id')
            ->whereBetween('don_hang.ngay_tao', [$tuNgayQuery, $denNgayQuery])
            ->where('don_hang.trang_thai', 'Đã thanh toán');

        // Filter theo rạp nếu cần
        if ($idRap !== 'all') {
            $query->where('san_pham.id_rapphim', $idRap);
        }

        $result = $query->groupBy('san_pham.id', 'san_pham.ten_san_pham', 'san_pham.hinh_anh')
            ->orderByRaw('SUM(chi_tiet_don_hang.so_luong * chi_tiet_don_hang.gia_ban) DESC')
            ->limit(10)
            ->get();

        $danhSach = [];
        foreach ($result as $item) {
            $danhSach[] = [
                'id_san_pham' => $item->id_san_pham,
                'ten_san_pham' => $item->ten_san_pham,
                'hinh_anh' => $item->hinh_anh,
                'doanh_thu' => (float)$item->doanh_thu,
                'so_luong_ban' => (int)$item->so_luong_ban
            ];
        }

        return [
            'danh_sach' => $danhSach,
            'tong_so' => count($danhSach)
        ];
    }

    /**
     * API Top 10 sản phẩm F&B bán chay nhất (theo số lượng)
     * Trả về danh sách 10 sản phẩm có SỐ LƯỢNG bán cao nhất (không phải doanh thu)
     * 
     * @param string $tuNgay Ngày bắt đầu (format: Y-m-d)
     * @param string $denNgay Ngày kết thúc (format: Y-m-d)
     * @param mixed $idRap ID của rạp cụ thể hoặc 'all' cho tất cả rạp
     * @return array Danh sách 10 sản phẩm bán chạy nhất
     */
    public function top10SanPhamBanChayNhat($tuNgay, $denNgay, $idRap = 'all') {
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');

        // Query theo SỐ LƯỢNG bán (không phải doanh thu)
        $query = ChiTietDonHang::selectRaw('
                san_pham.id as id_san_pham,
                san_pham.ten as ten_san_pham,
                san_pham.hinh_anh,
                SUM(chitiet_donhang.so_luong) as so_luong,
                SUM(chitiet_donhang.so_luong * chitiet_donhang.don_gia) as doanh_thu
             ')
            ->join('san_pham', 'chitiet_donhang.sanpham_id', '=', 'san_pham.id')
            ->join('donhang', 'chitiet_donhang.donhang_id', '=', 'donhang.id')
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery])
            ->where('donhang.trang_thai', 2);        // Filter theo rạp nếu cần
        if ($idRap !== 'all') {
            $query->where('san_pham.id_rapphim', $idRap);
        }

        // Sắp xếp theo SỐ LƯỢNG (quantity), không phải doanh thu
        $result = $query->groupBy('san_pham.id', 'san_pham.ten', 'san_pham.hinh_anh')
            ->orderByRaw('SUM(chitiet_donhang.so_luong) DESC')
            ->limit(10)
            ->get();

        $danhSach = [];
        foreach ($result as $item) {
            $danhSach[] = [
                'id' => $item->id_san_pham,
                'ten_san_pham' => $item->ten_san_pham,
                'hinh_anh' => $item->hinh_anh,
                'so_luong' => (int)$item->so_luong,
                'doanh_thu' => (float)$item->doanh_thu
            ];
        }

        return [
            'danh_sach' => $danhSach,
            'thoi_gian' => [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay
            ]
        ];
    }

    /**
     * API Hiệu suất theo rạp - So sánh doanh thu giữa các rạp trong chuỗi
     * Trả về dữ liệu cho biểu đồ cột so sánh doanh thu theo rạp
     * 
     * @param string $tuNgay Ngày bắt đầu (format: Y-m-d)
     * @param string $denNgay Ngày kết thúc (format: Y-m-d)
     * @param mixed $idRap ID của rạp cụ thể hoặc 'all' cho tất cả rạp
     * @return array Dữ liệu hiệu suất theo rạp
     */
    public function hieuSuatTheoRap($tuNgay, $denNgay, $idRap = 'all') {
        // Định dạng thời gian
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
        
        // Lấy tất cả rạp phim (hoặc rạp cụ thể nếu có filter)
        $queryRap = RapPhim::select('id', 'ten');
        
        if ($idRap !== 'all') {
            $queryRap->where('id', $idRap);
        }
        
        $danhSachRap = $queryRap->get();
        
        // Xây dựng query để lấy doanh thu từng rạp
        $doanhThuTheoRap = DonHang::selectRaw('
                rapphim.id as id_rap,
                SUM(donhang.tong_tien) as tong_doanh_thu,
                COUNT(DISTINCT donhang.id) as so_don_hang,
                COUNT(DISTINCT donhang.user_id) as so_khach_hang
            ')
            ->join('suatchieu', 'donhang.suat_chieu_id', '=', 'suatchieu.id')
            ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->join('rapphim', 'phongchieu.id_rapphim', '=', 'rapphim.id')
            ->where('donhang.trang_thai', 2)
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery])
            ->groupBy('rapphim.id')
            ->get()
            ->keyBy('id_rap'); // Chuyển thành array indexed by id_rap để dễ lookup
        
        // Kết hợp dữ liệu: tất cả rạp + doanh thu (nếu có)
        $result = collect();
        // Kết hợp dữ liệu: tất cả rạp + doanh thu (nếu có)
        $result = collect();
        
        foreach ($danhSachRap as $rap) {
            $doanhThu = $doanhThuTheoRap->get($rap->id);
            
            $result->push((object)[
                'id_rap' => $rap->id,
                'ten_rap' => $rap->ten,
                'tong_doanh_thu' => $doanhThu ? (float)$doanhThu->tong_doanh_thu : 0,
                'so_don_hang' => $doanhThu ? (int)$doanhThu->so_don_hang : 0,
                'so_khach_hang' => $doanhThu ? (int)$doanhThu->so_khach_hang : 0
            ]);
        }
        
        // Sắp xếp theo doanh thu giảm dần
        $result = $result->sortByDesc('tong_doanh_thu')->values();
        
        // Tính tổng doanh thu của tất cả rạp để tính phần trăm
        $tongDoanhThuTatCa = $result->sum('tong_doanh_thu');
        
        // Format dữ liệu trả về
        $danhSachRap = [];
        foreach ($result as $item) {
            $phanTramDongGop = $tongDoanhThuTatCa > 0 ? 
                round(($item->tong_doanh_thu / $tongDoanhThuTatCa) * 100, 2) : 0;
            
            $danhSachRap[] = [
                'id_rap' => (int)$item->id_rap,
                'ten_rap' => $item->ten_rap,
                'doanh_thu' => (float)$item->tong_doanh_thu,
                'so_don_hang' => (int)$item->so_don_hang,
                'so_khach_hang' => (int)$item->so_khach_hang,
                'phan_tram_dong_gop' => (float)$phanTramDongGop
            ];
        }
        
        return [
            'danh_sach_rap' => $danhSachRap,
            'tong_doanh_thu' => (float)$tongDoanhThuTatCa,
            'so_rap' => count($danhSachRap),
            'thoi_gian' => [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay
            ]
        ];
    }

    /**
     * API Cơ cấu doanh thu - Phân tích nguồn doanh thu
     * Trả về dữ liệu cho biểu đồ donut chart về cơ cấu doanh thu
     * Chỉ bao gồm 2 nguồn: Vé phim và Đồ ăn & Thức uống
     * 
     * @param string $tuNgay Ngày bắt đầu (format: Y-m-d)
     * @param string $denNgay Ngày kết thúc (format: Y-m-d)
     * @param mixed $idRap ID của rạp cụ thể hoặc 'all' cho tất cả rạp
     * @return array Dữ liệu cơ cấu doanh thu
     */
    public function coCauDoanhThuToanRap($tuNgay, $denNgay, $idRap = 'all') {
        // Định dạng thời gian
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
        
        // 1. DOANH THU VÉ PHIM
        $doanhThuVe = $this->tinhDoanhThuVe($idRap, $tuNgayQuery, $denNgayQuery);
        
        // 2. DOANH THU ĐỒ ĂN & THỨC UỐNG
        $doanhThuFnB = $this->tinhDoanhThuFnB($idRap, $tuNgayQuery, $denNgayQuery);

        // Tính tổng doanh thu (Vé + F&B)
        $tongDoanhThu = $doanhThuVe + $doanhThuFnB;

        // Tính phần trăm từng loại
        $phanTramVe = $tongDoanhThu > 0 ? round(($doanhThuVe / $tongDoanhThu) * 100, 1) : 0;
        $phanTramFnB = $tongDoanhThu > 0 ? round(($doanhThuFnB / $tongDoanhThu) * 100, 1) : 0;

        return [
            'chi_tiet' => [
                [
                    'loai' => 'Vé phim',
                    'doanh_thu' => (float)$doanhThuVe,
                    'phan_tram' => (float)$phanTramVe,
                    'mau_sac' => '#EF4444' // Màu đỏ
                ],
                [
                    'loai' => 'Đồ ăn & Thức uống',
                    'doanh_thu' => (float)$doanhThuFnB,
                    'phan_tram' => (float)$phanTramFnB,
                    'mau_sac' => '#F59E0B' // Màu cam
                ]
            ],
            'tong_doanh_thu' => (float)$tongDoanhThu,
            'thoi_gian' => [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay
            ]
        ];
    }

    /**
     * API Hiệu suất theo ngày trong tuần
     * Trả về dữ liệu cho biểu đồ line chart về vé bán và tỷ lệ lấp đầy theo từng ngày trong tuần
     * 
     * @param string $tuNgay Ngày bắt đầu (format: Y-m-d)
     * @param string $denNgay Ngày kết thúc (format: Y-m-d)
     * @param mixed $idRap ID của rạp cụ thể hoặc 'all' cho tất cả rạp
     * @return array Dữ liệu hiệu suất theo ngày trong tuần
     */
    public function hieuSuatTheoNgayTrongTuan($tuNgay, $denNgay, $idRap = 'all') {
        // Định dạng thời gian
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
        
        // Khởi tạo mảng thống kê theo ngày trong tuần (2=Thứ Hai, 3=Thứ Ba,..., 8=Chủ Nhật)
        $thongKeTheoThu = [
            2 => ['ten' => 'Thứ Hai', 'so_ve_ban' => 0, 'tong_so_ghe' => 0, 'so_ghe_da_ban' => 0, 'doanh_thu' => 0],
            3 => ['ten' => 'Thứ Ba', 'so_ve_ban' => 0, 'tong_so_ghe' => 0, 'so_ghe_da_ban' => 0, 'doanh_thu' => 0],
            4 => ['ten' => 'Thứ Tư', 'so_ve_ban' => 0, 'tong_so_ghe' => 0, 'so_ghe_da_ban' => 0, 'doanh_thu' => 0],
            5 => ['ten' => 'Thứ Năm', 'so_ve_ban' => 0, 'tong_so_ghe' => 0, 'so_ghe_da_ban' => 0, 'doanh_thu' => 0],
            6 => ['ten' => 'Thứ Sáu', 'so_ve_ban' => 0, 'tong_so_ghe' => 0, 'so_ghe_da_ban' => 0, 'doanh_thu' => 0],
            7 => ['ten' => 'Thứ Bảy', 'so_ve_ban' => 0, 'tong_so_ghe' => 0, 'so_ghe_da_ban' => 0, 'doanh_thu' => 0],
            8 => ['ten' => 'Chủ Nhật', 'so_ve_ban' => 0, 'tong_so_ghe' => 0, 'so_ghe_da_ban' => 0, 'doanh_thu' => 0]
        ];
        
        // Lấy danh sách suất chiếu trong khoảng thời gian
        $querySuatChieu = SuatChieu::whereBetween('batdau', [$tuNgayQuery, $denNgayQuery]);
        
        if ($idRap !== 'all') {
            $querySuatChieu->whereHas('phongChieu', function($q) use ($idRap) {
                $q->where('id_rapphim', $idRap);
            });
        }
        
        $danhSachSuatChieu = $querySuatChieu->get();
        
        // Debug: Log số lượng suất chiếu tìm được
        error_log("=== DEBUG hieuSuatTheoNgayTrongTuan ===");
        error_log("So luong suat chieu: " . $danhSachSuatChieu->count());
        error_log("Tu ngay: $tuNgayQuery");
        error_log("Den ngay: $denNgayQuery");
        
        // Duyệt qua từng suất chiếu để thống kê
        foreach ($danhSachSuatChieu as $suatChieu) {
            $ngayBatDau = new \DateTime($suatChieu->batdau);
            $thuTrongTuan = (int)$ngayBatDau->format('N'); // 1=Monday, 7=Sunday
            
            // Chuyển đổi: 1-6 giữ nguyên (Monday-Saturday), 7 (Sunday) -> 8
            // Nhưng ta cần map: 1->2, 2->3, 3->4, 4->5, 5->6, 6->7, 7->8
            $thuTrongTuan = $thuTrongTuan < 7 ? $thuTrongTuan + 1 : 8;
            
            // Debug: Log chi tiết suất chiếu
            error_log("Suat chieu ID: {$suatChieu->id}, Ngay: {$suatChieu->batdau}, Thu: $thuTrongTuan");
            
            // Lấy số ghế của phòng chiếu
            $soGhePhong = $suatChieu->phongChieu->so_luong_ghe ?? 0;
            $thongKeTheoThu[$thuTrongTuan]['tong_so_ghe'] += $soGhePhong;
            
            // Đếm số vé đã bán cho suất chiếu này
            $soVeDaBan = Ve::where('suat_chieu_id', $suatChieu->id)
                ->where('trang_thai', 2) // Vé đã thanh toán
                ->count();
            
            // Debug: Log số vé
            error_log("  -> So ve ban (trang_thai=2): $soVeDaBan");
            
            $thongKeTheoThu[$thuTrongTuan]['so_ve_ban'] += $soVeDaBan;
            $thongKeTheoThu[$thuTrongTuan]['so_ghe_da_ban'] += $soVeDaBan;
            
            // Tính doanh thu từ đơn hàng liên quan đến suất chiếu này
            $doanhThuSuatChieu = DonHang::where('suat_chieu_id', $suatChieu->id)
                ->where('trang_thai', 2) // Đã thanh toán
                ->sum('tong_tien');
            
            // Debug: Log doanh thu
            error_log("  -> Doanh thu: $doanhThuSuatChieu");
            
            $thongKeTheoThu[$thuTrongTuan]['doanh_thu'] += $doanhThuSuatChieu;
        }
        
        // Tính tỷ lệ lấp đầy cho từng ngày
        $ketQua = [];
        foreach ($thongKeTheoThu as $thu => $data) {
            $tyLeLapDay = $data['tong_so_ghe'] > 0 
                ? round(($data['so_ghe_da_ban'] / $data['tong_so_ghe']) * 100, 1) 
                : 0;
            
            $ketQua[] = [
                'ngay' => $data['ten'],
                'so_ve_ban' => (int)$data['so_ve_ban'],
                'ty_le_lap_day' => (float)$tyLeLapDay,
                'tong_so_ghe' => (int)$data['tong_so_ghe'],
                'so_ghe_da_ban' => (int)$data['so_ghe_da_ban'],
                'doanh_thu' => (float)$data['doanh_thu']
            ];
        }
        
        return [
            'danh_sach' => $ketQua,
            'thoi_gian' => [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay
            ]
        ];
    }

    /**
     * API Hiệu suất theo giờ trong ngày
     * Trả về dữ liệu cho biểu đồ area chart về tỷ lệ lấp đầy theo khung giờ
     * 
     * @param string $tuNgay Ngày bắt đầu (format: Y-m-d)
     * @param string $denNgay Ngày kết thúc (format: Y-m-d)
     * @param mixed $idRap ID của rạp cụ thể hoặc 'all' cho tất cả rạp
     * @return array Dữ liệu hiệu suất theo giờ
     */
    public function hieuSuatTheoGioTrongNgay($tuNgay, $denNgay, $idRap = 'all') {
        // Định dạng thời gian
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
        
        // Định nghĩa các khung giờ (từ 8:00 đến 23:00)
        $khungGio = [];
        for ($gio = 8; $gio <= 23; $gio++) {
            $khungGio[] = [
                'gio' => sprintf('%02d:00', $gio),
                'gio_bat_dau' => $gio,
                'gio_ket_thuc' => $gio + 1
            ];
        }
        
        // Khởi tạo mảng thống kê theo giờ
        $thongKeTheoGio = [];
        foreach ($khungGio as $kg) {
            $thongKeTheoGio[$kg['gio']] = [
                'gio' => $kg['gio'],
                'so_suat_chieu' => 0,
                'tong_so_ghe' => 0,
                'so_ghe_da_ban' => 0,
                'so_ve_ban' => 0,
                'doanh_thu' => 0
            ];
        }
        
        // Lấy danh sách suất chiếu trong khoảng thời gian
        $querySuatChieu = SuatChieu::whereBetween('batdau', [$tuNgayQuery, $denNgayQuery]);
        
        if ($idRap !== 'all') {
            $querySuatChieu->whereHas('phongChieu', function($q) use ($idRap) {
                $q->where('id_rapphim', $idRap);
            });
        }
        
        $danhSachSuatChieu = $querySuatChieu->get();
        
        // Duyệt qua từng suất chiếu để thống kê theo giờ
        foreach ($danhSachSuatChieu as $suatChieu) {
            $ngayGioBatDau = new \DateTime($suatChieu->batdau);
            $gioBatDau = (int)$ngayGioBatDau->format('H'); // Lấy giờ (0-23)
            
            // Chỉ tính các suất chiếu trong khung giờ 8-23
            if ($gioBatDau < 8 || $gioBatDau >= 23) {
                continue;
            }
            
            $khungGioKey = sprintf('%02d:00', $gioBatDau);
            
            // Đếm số suất chiếu
            $thongKeTheoGio[$khungGioKey]['so_suat_chieu']++;
            
            // Lấy số ghế của phòng chiếu
            $soGhePhong = $suatChieu->phongChieu->so_luong_ghe ?? 0;
            $thongKeTheoGio[$khungGioKey]['tong_so_ghe'] += $soGhePhong;
            
            // Đếm số vé đã bán cho suất chiếu này
            $soVeDaBan = Ve::where('suat_chieu_id', $suatChieu->id)
                ->where('trang_thai', 2) // Vé đã thanh toán
                ->count();
            
            $thongKeTheoGio[$khungGioKey]['so_ve_ban'] += $soVeDaBan;
            $thongKeTheoGio[$khungGioKey]['so_ghe_da_ban'] += $soVeDaBan;
            
            // Tính doanh thu từ đơn hàng liên quan đến suất chiếu này
            $doanhThuSuatChieu = DonHang::where('suat_chieu_id', $suatChieu->id)
                ->where('trang_thai', 2) // Đã thanh toán
                ->sum('tong_tien');
            
            $thongKeTheoGio[$khungGioKey]['doanh_thu'] += $doanhThuSuatChieu;
        }
        
        // Tính tỷ lệ lấp đầy cho từng khung giờ
        $ketQua = [];
        foreach ($thongKeTheoGio as $data) {
            $tyLeLapDay = $data['tong_so_ghe'] > 0 
                ? round(($data['so_ghe_da_ban'] / $data['tong_so_ghe']) * 100, 1) 
                : 0;
            
            $ketQua[] = [
                'gio' => $data['gio'],
                'so_suat_chieu' => (int)$data['so_suat_chieu'],
                'so_ve_ban' => (int)$data['so_ve_ban'],
                'ty_le_lap_day' => (float)$tyLeLapDay,
                'tong_so_ghe' => (int)$data['tong_so_ghe'],
                'so_ghe_da_ban' => (int)$data['so_ghe_da_ban'],
                'doanh_thu' => (float)$data['doanh_thu']
            ];
        }
        
        return [
            'danh_sach' => $ketQua,
            'thoi_gian' => [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay
            ]
        ];
    }

    /**
     * API Tỉ lệ doanh thu F&B trên mỗi đơn hàng
     * Trả về dữ liệu cho biểu đồ cột về doanh thu F&B trung bình trên mỗi đơn hàng theo ngày
     * 
     * @param string $tuNgay Ngày bắt đầu (format: Y-m-d)
     * @param string $denNgay Ngày kết thúc (format: Y-m-d)
     * @param mixed $idRap ID của rạp cụ thể hoặc 'all' cho tất cả rạp
     * @return array Dữ liệu tỉ lệ doanh thu F&B trên mỗi đơn hàng
     */
    public function tiLeDoanhThuFnBTrenDonHang($tuNgay, $denNgay, $idRap = 'all') {
        // Định dạng thời gian
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
        
        // Lấy dữ liệu doanh thu F&B theo ngày
        $queryFnB = ChiTietDonHang::selectRaw('
                DATE(donhang.ngay_dat) as ngay,
                SUM(chitiet_donhang.so_luong * chitiet_donhang.don_gia) as tong_doanh_thu_fnb,
                COUNT(DISTINCT donhang.id) as so_don_hang
            ')
            ->join('donhang', 'chitiet_donhang.donhang_id', '=', 'donhang.id')
            ->join('san_pham', 'chitiet_donhang.sanpham_id', '=', 'san_pham.id')
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery])
            ->where('donhang.trang_thai', 2); // Đã thanh toán
        
        // Filter theo rạp nếu cần
        if ($idRap !== 'all') {
            $queryFnB->where('san_pham.id_rapphim', $idRap);
        }
        
        $resultFnB = $queryFnB->groupBy('ngay')
            ->orderBy('ngay', 'ASC')
            ->get();
        
        // Xây dựng mảng kết quả với tất cả các ngày trong khoảng
        $ketQua = [];
        $currentDate = clone $tuNgayDate;
        
        // Chuyển result thành array indexed by date để dễ lookup
        $fnbByDate = [];
        foreach ($resultFnB as $item) {
            $fnbByDate[$item->ngay] = [
                'tong_doanh_thu_fnb' => (float)$item->tong_doanh_thu_fnb,
                'so_don_hang' => (int)$item->so_don_hang
            ];
        }
        
        // Duyệt qua từng ngày trong khoảng thời gian
        while ($currentDate <= $denNgayDate) {
            $ngayStr = $currentDate->format('Y-m-d');
            $ngayHienThi = $currentDate->format('d/m');
            
            // Lấy dữ liệu F&B nếu có
            $tongDoanhThuFnB = 0;
            $soDonHang = 0;
            $trungBinhFnBTrenDonHang = 0;
            
            if (isset($fnbByDate[$ngayStr])) {
                $tongDoanhThuFnB = $fnbByDate[$ngayStr]['tong_doanh_thu_fnb'];
                $soDonHang = $fnbByDate[$ngayStr]['so_don_hang'];
                
                // Tính trung bình F&B trên mỗi đơn hàng
                if ($soDonHang > 0) {
                    $trungBinhFnBTrenDonHang = round($tongDoanhThuFnB / $soDonHang);
                }
            }
            
            $ketQua[] = [
                'ngay' => $ngayHienThi,
                'ngay_day_du' => $ngayStr,
                'tong_doanh_thu_fnb' => (float)$tongDoanhThuFnB,
                'so_don_hang' => (int)$soDonHang,
                'trung_binh_fnb_tren_don_hang' => (float)$trungBinhFnBTrenDonHang
            ];
            
            $currentDate->modify('+1 day');
        }
        
        return [
            'danh_sach' => $ketQua,
            'thoi_gian' => [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay
            ]
        ];
    }

    /**
     * Thống kê doanh thu mua phim xem online (MuaPhim) theo ngày trong khoảng
     * @param string $tuNgay (Y-m-d)
     * @param string $denNgay (Y-m-d)
     * @return array
     */
    public function doanhThuPhim($tuNgay, $denNgay) {
        // Định dạng thời gian
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');

        // 1) Doanh thu vé theo phim (dựa trên ve -> suatchieu.id_phim)
        $veRows = Ve::selectRaw('suatchieu.id_phim as phim_id, SUM(ve.gia_ve) as doanh_thu_ve, COUNT(ve.id) as so_ve')
            ->join('suatchieu', 've.suat_chieu_id', '=', 'suatchieu.id')
            ->join('donhang', 've.donhang_id', '=', 'donhang.id')
            ->where('ve.trang_thai', 2)
            ->where('donhang.trang_thai', 2)
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery])
            ->groupBy('suatchieu.id_phim')
            ->get();

        $veByFilm = [];
        foreach ($veRows as $r) {
            $id = $r->phim_id;
            if (!$id) continue;
            $veByFilm[(int)$id] = [
                'doanh_thu_ve' => (float)$r->doanh_thu_ve,
                'so_ve' => (int)$r->so_ve
            ];
        }

        // 2) Doanh thu mua phim theo phim
        // Note: `donhang.phim_id` column may not exist in the schema. Derive film id from the linked `suatchieu` record.
        // We join through donhang -> suatchieu and group by suatchieu.id_phim. Rows without a linked suatchieu will be ignored
        // (they cannot be confidently mapped to a film).
        $mpRows = MuaPhim::selectRaw('suatchieu.id_phim as phim_id, SUM(mua_phim.so_tien) as doanh_thu_mua, COUNT(mua_phim.id) as so_giao_dich')
            ->join('donhang', 'mua_phim.don_hang_id', '=', 'donhang.id')
            ->join('suatchieu', 'donhang.suat_chieu_id', '=', 'suatchieu.id')
            ->where('mua_phim.trang_thai', 2)
            ->where('donhang.trang_thai', 2)
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery])
            ->groupBy('suatchieu.id_phim')
            ->get();

        $mpByFilm = [];
        foreach ($mpRows as $r) {
            $id = $r->phim_id;
            if (!$id) continue;
            $mpByFilm[(int)$id] = [
                'doanh_thu_mua' => (float)$r->doanh_thu_mua,
                'so_giao_dich' => (int)$r->so_giao_dich
            ];
        }

        // Tính tổng các giao dịch mua phim không có suat_chieu liên kết (không thể map tới phim)
        $unmappedSum = 0.0;
        $unmappedCnt = 0;
        $unmappedRow = MuaPhim::join('donhang', 'mua_phim.don_hang_id', '=', 'donhang.id')
            ->whereNull('donhang.suat_chieu_id')
            ->where('mua_phim.trang_thai', 2)
            ->where('donhang.trang_thai', 2)
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery])
            ->selectRaw('SUM(mua_phim.so_tien) as sum_mua, COUNT(mua_phim.id) as cnt')
            ->first();

        if ($unmappedRow) {
            $unmappedSum = (float)($unmappedRow->sum_mua ?? 0);
            $unmappedCnt = (int)($unmappedRow->cnt ?? 0);
        }

        // 3) Use the full film list so the returned danh_sach contains ALL films
        // (films with no revenue will show 0 for both sources). This guarantees a
        // consistent list regardless of whether a film had transactions.
        $filmIds = Phim::select('id')->get()->map(function ($phim) {
            return $phim->id;
        })->toArray();

        // If there are no films in the DB, return empty result
        if (empty($filmIds)) {
            return [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
                'danh_sach' => [],
                'tong_ket' => [
                    'tong_doanh_thu_ve' => 0.0,
                    'tong_doanh_thu_mua' => 0.0,
                    'tong_doanh_thu' => 0.0
                ]
            ];
        }

        // 4) Lấy thông tin phim
        $phims = Phim::whereIn('id', $filmIds)->get()->keyBy('id');

        $list = [];
        $totalVe = 0.0;
        $totalMua = 0.0;

        foreach ($filmIds as $fid) {
            $fid = (int)$fid;
            $ph = isset($phims[$fid]) ? $phims[$fid] : null;
            $ten = $ph ? $ph->ten_phim : ('Phim #' . $fid);
            $poster = $ph ? ($ph->poster_url ?? null) : null;

            $dv = isset($veByFilm[$fid]) ? $veByFilm[$fid]['doanh_thu_ve'] : 0.0;
            $dm = isset($mpByFilm[$fid]) ? $mpByFilm[$fid]['doanh_thu_mua'] : 0.0;

            $totalVe += $dv;
            $totalMua += $dm;

            $total = $dv + $dm;

            $list[] = [
                'id' => $fid,
                'ten_phim' => $ten,
                'poster_url' => $poster,
                'doanh_thu_ve' => (float)$dv,
                'doanh_thu_mua' => (float)$dm,
                'tong_doanh_thu' => (float)$total
            ];
        }

        // Nếu có giao dịch mua phim không thể map tới suatchieu/phim, thêm một mục tổng hợp riêng
        if ($unmappedCnt > 0) {
            $list[] = [
                'id' => null,
                'ten_phim' => 'Chưa xác định (Mua phim trực tuyến không gắn suất chiếu)',
                'poster_url' => null,
                'doanh_thu_ve' => 0.0,
                'doanh_thu_mua' => (float)$unmappedSum,
                'tong_doanh_thu' => (float)$unmappedSum
            ];
            $totalMua += $unmappedSum;
        }

        // sort by total desc
        usort($list, function($a, $b) {
            return $b['tong_doanh_thu'] <=> $a['tong_doanh_thu'];
        });

        $grandTotal = $totalVe + $totalMua;

        return [
            'tu_ngay' => $tuNgay,
            'den_ngay' => $denNgay,
            'danh_sach' => $list,
            'tong_ket' => [
                'tong_doanh_thu_ve' => (float)$totalVe,
                'tong_doanh_thu_mua' => (float)$totalMua,
                'tong_doanh_thu' => (float)$grandTotal
            ]
        ];
    }

    /**
     * Hàm duy nhất lấy tất cả dữ liệu thô thống kê toàn rạp
     * Trả về dữ liệu thô từ database, không format, không xử lý
     * JavaScript sẽ nhận dữ liệu này và xử lý format/tổng hợp ở client side
     * 
     * LƯU Ý: Hàm này LUÔN lấy dữ liệu cho TẤT CẢ rạp, bất kể tham số $idRap
     * Filter theo rạp sẽ được xử lý ở client side (JavaScript)
     * 
     * @param string $tuNgay Ngày bắt đầu (Y-m-d)
     * @param string $denNgay Ngày kết thúc (Y-m-d)
     * @param string|int $idRap Tham số này không được sử dụng, luôn lấy tất cả rạp
     * @return array Dữ liệu thô từ database
     */
    public function layDuLieuThoThongKeToanRap($tuNgay, $denNgay) {
        // Định dạng thời gian
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
        
        // Tính khoảng thời gian kỳ trước (để so sánh)
        $soNgayTrongKhoang = (int)$tuNgayDate->diff($denNgayDate)->format('%a') + 1;
        $tuNgayKyTruocDate = clone $tuNgayDate;
        $denNgayKyTruocDate = clone $tuNgayDate;
        $tuNgayKyTruocDate->modify('-' . $soNgayTrongKhoang . ' days');
        $denNgayKyTruocDate->modify('-1 day');
        $tuNgayKyTruocQuery = $tuNgayKyTruocDate->format('Y-m-d H:i:s');
        $denNgayKyTruocQuery = $denNgayKyTruocDate->format('Y-m-d H:i:s');
        
        // ========== 1. DỮ LIỆU VÉ (Ve) ==========
        // Join với donhang để đảm bảo đơn hàng đã thanh toán
        $queryVe = Ve::selectRaw('
                ve.id,
                ve.gia_ve,
                ve.ngay_tao,
                ve.suat_chieu_id,
                ve.donhang_id,
                suatchieu.id_phim,
                suatchieu.id_phongchieu,
                phongchieu.id_rapphim,
                phim.ten_phim,
                phim.poster_url
            ')
            ->join('donhang', 've.donhang_id', '=', 'donhang.id')
            ->join('suatchieu', 've.suat_chieu_id', '=', 'suatchieu.id')
            ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->join('phim', 'suatchieu.id_phim', '=', 'phim.id')
            ->where('ve.trang_thai', 2) // Vé đã đặt
            ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery]);
        
        // Luôn lấy tất cả rạp, không filter theo $idRap
        $duLieuVe = $queryVe->get()->map(function($item) {
            return [
                'id' => $item->id,
                'gia_ve' => (float)$item->gia_ve,
                'ngay_tao' => $item->ngay_tao,
                'suat_chieu_id' => $item->suat_chieu_id,
                'id_phim' => $item->id_phim,
                'id_phongchieu' => $item->id_phongchieu,
                'id_rapphim' => $item->id_rapphim,
                'ten_phim' => $item->ten_phim,
                'poster_url' => $item->poster_url
            ];
        })->toArray();
        
        // Dữ liệu vé kỳ trước
        $queryVeKyTruoc = Ve::selectRaw('
                ve.id,
                ve.gia_ve,
                ve.ngay_tao,
                ve.suat_chieu_id,
                ve.donhang_id,
                suatchieu.id_phim,
                suatchieu.id_phongchieu,
                phongchieu.id_rapphim
            ')
            ->join('donhang', 've.donhang_id', '=', 'donhang.id')
            ->join('suatchieu', 've.suat_chieu_id', '=', 'suatchieu.id')
            ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->where('ve.trang_thai', 2) // Vé đã đặt
            ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
            ->whereBetween('donhang.ngay_dat', [$tuNgayKyTruocQuery, $denNgayKyTruocQuery]);
        
        // Luôn lấy tất cả rạp, không filter theo $idRap
        $duLieuVeKyTruoc = $queryVeKyTruoc->get()->map(function($item) {
            return [
                'id' => $item->id,
                'gia_ve' => (float)$item->gia_ve,
                'ngay_tao' => $item->ngay_tao
            ];
        })->toArray();
        
        // ========== 2. DỮ LIỆU ĐƠN HÀNG (DonHang) ==========
        // Lấy tất cả đơn hàng đã thanh toán (bao gồm cả đơn hàng chỉ mua sản phẩm, không có suất chiếu)
        $queryDonHang = DonHang::selectRaw('
                donhang.id,
                donhang.tong_tien,
                donhang.ngay_dat,
                donhang.trang_thai,
                donhang.suat_chieu_id,
                donhang.user_id,
                donhang.rap_id,
                suatchieu.id_phim,
                suatchieu.id_phongchieu,
                phongchieu.id_rapphim
            ')
            ->leftJoin('suatchieu', 'donhang.suat_chieu_id', '=', 'suatchieu.id')
            ->leftJoin('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->where('donhang.trang_thai', 2) // Đã thanh toán
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery]);
        
        // Luôn lấy tất cả rạp, không filter theo $idRap
        $duLieuDonHang = $queryDonHang->get()->map(function($item) {
            return [
                'id' => $item->id,
                'tong_tien' => (float)$item->tong_tien,
                'ngay_dat' => $item->ngay_dat,
                'trang_thai' => $item->trang_thai,
                'suat_chieu_id' => $item->suat_chieu_id,
                'user_id' => $item->user_id,
                'id_phim' => $item->id_phim,
                'id_phongchieu' => $item->id_phongchieu,
                'id_rapphim' => $item->id_rapphim ?? $item->rap_id, // Nếu không có suất chiếu, dùng rap_id
                'rap_id' => $item->rap_id
            ];
        })->toArray();
        
        // ========== 3. DỮ LIỆU CHI TIẾT ĐƠN HÀNG F&B (ChiTietDonHang) ==========
        $queryChiTietDonHang = ChiTietDonHang::selectRaw('
                chitiet_donhang.id,
                chitiet_donhang.donhang_id,
                chitiet_donhang.sanpham_id,
                chitiet_donhang.so_luong,
                chitiet_donhang.don_gia,
                chitiet_donhang.thanh_tien,
                san_pham.ten,
                san_pham.hinh_anh,
                san_pham.id_rapphim,
                donhang.ngay_dat,
                donhang.trang_thai
            ')
            ->join('donhang', 'chitiet_donhang.donhang_id', '=', 'donhang.id')
            ->join('san_pham', 'chitiet_donhang.sanpham_id', '=', 'san_pham.id')
            ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery]);
        
        // Luôn lấy tất cả rạp, không filter theo $idRap
        $duLieuChiTietDonHang = $queryChiTietDonHang->get()->map(function($item) {
            return [
                'id' => $item->id,
                'id_donhang' => $item->donhang_id,
                'id_sanpham' => $item->sanpham_id,
                'so_luong' => (int)($item->so_luong ?? 0),
                'gia_ban' => (float)($item->don_gia ?? 0),
                'thanh_tien' => (float)($item->thanh_tien ?? 0),
                'ten_san_pham' => $item->ten ?? '',
                'hinh_anh' => $item->hinh_anh ?? '',
                'id_rapphim' => $item->id_rapphim ?? null,
                'ngay_dat' => $item->ngay_dat ?? ''
            ];
        })->toArray();
        
        // ========== 4. DỮ LIỆU MUA PHIM (MuaPhim) ==========
        // Mua phim trực tuyến - có thể không có suất chiếu
        $queryMuaPhim = MuaPhim::selectRaw('
                mua_phim.id,
                mua_phim.so_tien,
                mua_phim.trang_thai,
                mua_phim.don_hang_id,
                donhang.ngay_dat,
                donhang.suat_chieu_id,
                donhang.phim_id,
                suatchieu.id_phim as suat_chieu_phim_id,
                COALESCE(phim_suat.ten_phim, phim_don.ten_phim) as ten_phim,
                COALESCE(phim_suat.poster_url, phim_don.poster_url) as poster_url
            ')
            ->join('donhang', 'mua_phim.don_hang_id', '=', 'donhang.id')
            ->leftJoin('suatchieu', 'donhang.suat_chieu_id', '=', 'suatchieu.id')
            ->leftJoin('phim as phim_suat', 'suatchieu.id_phim', '=', 'phim_suat.id')
            ->leftJoin('phim as phim_don', 'donhang.phim_id', '=', 'phim_don.id')
            ->where('mua_phim.trang_thai', 2) // Đã mua
            ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery]);
        
        $duLieuMuaPhim = $queryMuaPhim->get()->map(function($item) {
            return [
                'id' => $item->id,
                'so_tien' => (float)$item->so_tien,
                'trang_thai' => $item->trang_thai,
                'don_hang_id' => $item->don_hang_id,
                'ngay_dat' => $item->ngay_dat,
                'suat_chieu_id' => $item->suat_chieu_id,
                'id_phim' => $item->suat_chieu_phim_id ?? $item->phim_id, // Ưu tiên phim từ suất chiếu, nếu không có thì từ donhang
                'ten_phim' => $item->ten_phim,
                'poster_url' => $item->poster_url
            ];
        })->toArray();
        
        // ========== 5. DỮ LIỆU SUẤT CHIẾU (SuatChieu) - để tính tỷ lệ lấp đầy ==========
        $querySuatChieu = SuatChieu::selectRaw('
                suatchieu.id,
                suatchieu.id_phongchieu,
                suatchieu.id_phim,
                suatchieu.batdau,
                suatchieu.ketthuc,
                phongchieu.id_rapphim,
                phongchieu.so_luong_ghe
            ')
            ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->whereBetween('suatchieu.batdau', [$tuNgayQuery, $denNgayQuery]);
        
        // Luôn lấy tất cả rạp, không filter theo $idRap
        $duLieuSuatChieu = $querySuatChieu->get()->map(function($item) {
            // Extract ngày từ batdau (datetime)
            $batdauDate = $item->batdau ? date('Y-m-d', strtotime($item->batdau)) : null;
            $batdauTime = $item->batdau ? date('H:i:s', strtotime($item->batdau)) : null;
            
            return [
                'id' => $item->id,
                'id_phongchieu' => $item->id_phongchieu,
                'id_phim' => $item->id_phim,
                'ngay_chieu' => $batdauDate,
                'gio_bat_dau' => $batdauTime,
                'batdau' => $item->batdau,
                'ketthuc' => $item->ketthuc,
                'id_rapphim' => $item->id_rapphim,
                'so_ghe' => (int)($item->so_luong_ghe ?? 0)
            ];
        })->toArray();
        
        // ========== 6. DỮ LIỆU RẠP PHIM (RapPhim) ==========
        // Luôn lấy tất cả rạp, không filter theo $idRap
        $duLieuRapPhim = RapPhim::select('id', 'ten')
            ->get()
            ->map(function($item) {
            return [
                'id' => $item->id,
                'ten' => $item->ten
            ];
        })->toArray();
        
        // Trả về dữ liệu thô
        return [
            'thoi_gian' => [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
                'tu_ngay_query' => $tuNgayQuery,
                'den_ngay_query' => $denNgayQuery,
                'tu_ngay_ky_truoc' => $tuNgayKyTruocDate->format('Y-m-d'),
                'den_ngay_ky_truoc' => $denNgayKyTruocDate->format('Y-m-d'),
                'tu_ngay_ky_truoc_query' => $tuNgayKyTruocQuery,
                'den_ngay_ky_truoc_query' => $denNgayKyTruocQuery,
                'so_ngay' => $soNgayTrongKhoang
            ],
            'filter' => [
                'id_rap' => 'all' // Luôn là 'all' vì luôn lấy tất cả rạp
            ],
            'du_lieu_ve' => $duLieuVe,
            'du_lieu_ve_ky_truoc' => $duLieuVeKyTruoc,
            'du_lieu_don_hang' => $duLieuDonHang,
            'du_lieu_chi_tiet_don_hang' => $duLieuChiTietDonHang,
            'du_lieu_mua_phim' => $duLieuMuaPhim,
            'du_lieu_suat_chieu' => $duLieuSuatChieu,
            'du_lieu_rap_phim' => $duLieuRapPhim
        ];
    }

    /**
     * Thống kê doanh thu theo suất chiếu
     * Tính doanh thu từ vé và F&B cho từng suất chiếu trong khoảng thời gian
     * 
     * @param string $tuNgay Ngày bắt đầu (Y-m-d)
     * @param string $denNgay Ngày kết thúc (Y-m-d)
     * @param mixed $idRap ID rạp hoặc 'all' cho tất cả rạp (không dùng, luôn lấy tất cả)
     * @return array Danh sách suất chiếu với doanh thu
     */
    public function thongKeDoanhThuTheoSuatChieu($tuNgay, $denNgay) {
        // Định dạng thời gian
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
        
        // Lấy tất cả suất chiếu trong khoảng thời gian
        $querySuatChieu = SuatChieu::selectRaw('
                suatchieu.id,
                suatchieu.id_phim,
                suatchieu.id_phongchieu,
                suatchieu.batdau,
                suatchieu.ketthuc,
                phim.ten_phim,
                phim.poster_url,
                phongchieu.ten as ten_phong_chieu,
                phongchieu.id_rapphim,
                rapphim.ten as ten_rap
            ')
            ->join('phim', 'suatchieu.id_phim', '=', 'phim.id')
            ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->join('rapphim', 'phongchieu.id_rapphim', '=', 'rapphim.id')
            ->whereBetween('suatchieu.batdau', [$tuNgayQuery, $denNgayQuery])
            ->orderBy('suatchieu.batdau', 'desc');
        
        $danhSachSuatChieu = $querySuatChieu->get();
        
        // Tính doanh thu cho từng suất chiếu
        $ketQua = [];
        
        foreach ($danhSachSuatChieu as $suatChieu) {
            $idSuatChieu = $suatChieu->id;
            
            // 1. Doanh thu từ vé của suất chiếu này
            $doanhThuVe = Ve::where('suat_chieu_id', $idSuatChieu)
                ->whereHas('donhang', function($query) use ($tuNgayQuery, $denNgayQuery) {
                    $query->where('trang_thai', 2) // Đơn hàng đã thanh toán
                          ->whereBetween('ngay_dat', [$tuNgayQuery, $denNgayQuery]);
                })
                ->where('trang_thai', 2) // Vé đã đặt
                ->sum('gia_ve');
            
            // 2. Doanh thu từ F&B của các đơn hàng liên quan đến suất chiếu này
            $doanhThuFnB = ChiTietDonHang::whereHas('donHang', function($query) use ($idSuatChieu, $tuNgayQuery, $denNgayQuery) {
                    $query->where('suat_chieu_id', $idSuatChieu)
                          ->where('trang_thai', 2) // Đơn hàng đã thanh toán
                          ->whereBetween('ngay_dat', [$tuNgayQuery, $denNgayQuery]);
                })
                ->sum('thanh_tien');
            
            // 3. Số vé đã bán
            $soVeBan = Ve::where('suat_chieu_id', $idSuatChieu)
                ->whereHas('donhang', function($query) use ($tuNgayQuery, $denNgayQuery) {
                    $query->where('trang_thai', 2)
                          ->whereBetween('ngay_dat', [$tuNgayQuery, $denNgayQuery]);
                })
                ->where('trang_thai', 2)
                ->count();
            
            // 4. Số ghế của phòng chiếu
            $soGhe = $suatChieu->phongChieu->so_luong_ghe ?? 0;
            
            // 5. Tỷ lệ lấp đầy
            $tyLeLapDay = $soGhe > 0 ? round(($soVeBan / $soGhe) * 100, 2) : 0;
            
            // 6. Tổng doanh thu
            $tongDoanhThu = $doanhThuVe + $doanhThuFnB;
            
            // Format ngày giờ
            $batdauDate = $suatChieu->batdau ? new \DateTime($suatChieu->batdau) : null;
            $ketthucDate = $suatChieu->ketthuc ? new \DateTime($suatChieu->ketthuc) : null;
            
            $ketQua[] = [
                'id' => $suatChieu->id,
                'id_phim' => $suatChieu->id_phim,
                'ten_phim' => $suatChieu->ten_phim,
                'poster_url' => $suatChieu->poster_url,
                'id_phong_chieu' => $suatChieu->id_phongchieu,
                'ten_phong_chieu' => $suatChieu->ten_phong_chieu,
                'id_rap' => $suatChieu->id_rapphim,
                'ten_rap' => $suatChieu->ten_rap,
                'batdau' => $suatChieu->batdau,
                'ketthuc' => $suatChieu->ketthuc,
                'ngay_chieu' => $batdauDate ? $batdauDate->format('Y-m-d') : null,
                'gio_bat_dau' => $batdauDate ? $batdauDate->format('H:i') : null,
                'gio_ket_thuc' => $ketthucDate ? $ketthucDate->format('H:i') : null,
                'doanh_thu_ve' => (float)$doanhThuVe,
                'doanh_thu_fnb' => (float)$doanhThuFnB,
                'tong_doanh_thu' => (float)$tongDoanhThu,
                'so_ve_ban' => (int)$soVeBan,
                'so_ghe' => (int)$soGhe,
                'ty_le_lap_day' => (float)$tyLeLapDay
            ];
        }
        
        // Sắp xếp theo tổng doanh thu giảm dần
        usort($ketQua, function($a, $b) {
            return $b['tong_doanh_thu'] <=> $a['tong_doanh_thu'];
        });
        
        return [
            'tu_ngay' => $tuNgay,
            'den_ngay' => $denNgay,
            'danh_sach' => $ketQua,
            'tong_ket' => [
                'tong_so_suat_chieu' => count($ketQua),
                'tong_doanh_thu_ve' => array_sum(array_column($ketQua, 'doanh_thu_ve')),
                'tong_doanh_thu_fnb' => array_sum(array_column($ketQua, 'doanh_thu_fnb')),
                'tong_doanh_thu' => array_sum(array_column($ketQua, 'tong_doanh_thu')),
                'tong_ve_ban' => array_sum(array_column($ketQua, 'so_ve_ban'))
            ]
        ];
    }

    /**
     * Hàm duy nhất lấy tất cả dữ liệu thô thống kê theo rạp
     * Trả về dữ liệu thô từ database, không format, không xử lý
     * JavaScript sẽ nhận dữ liệu này và xử lý format/tổng hợp ở client side
     * 
     * @param string $tuNgay Ngày bắt đầu (Y-m-d)
     * @param string $denNgay Ngày kết thúc (Y-m-d)
     * @param int $idRap ID của rạp cần lấy dữ liệu
     * @return array Dữ liệu thô từ database
     */
    public function layDuLieuThoThongKeTheoRap($tuNgay, $denNgay, $idRap) {
        // Định dạng thời gian
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
        
        // Tính khoảng thời gian kỳ trước (để so sánh)
        $soNgayTrongKhoang = (int)$tuNgayDate->diff($denNgayDate)->format('%a') + 1;
        $tuNgayKyTruocDate = clone $tuNgayDate;
        $denNgayKyTruocDate = clone $tuNgayDate;
        $tuNgayKyTruocDate->modify('-' . $soNgayTrongKhoang . ' days');
        $denNgayKyTruocDate->modify('-1 day');
        $tuNgayKyTruocQuery = $tuNgayKyTruocDate->format('Y-m-d H:i:s');
        $denNgayKyTruocQuery = $denNgayKyTruocDate->format('Y-m-d H:i:s');
        
        // ========== 1. DỮ LIỆU VÉ (Ve) ==========
        $queryVe = Ve::selectRaw('
                ve.id,
                ve.gia_ve,
                ve.ngay_tao,
                ve.suat_chieu_id,
                ve.donhang_id,
                suatchieu.id_phim,
                suatchieu.id_phongchieu,
                phongchieu.id_rapphim,
                phim.ten_phim,
                phim.poster_url
            ')
            ->join('donhang', 've.donhang_id', '=', 'donhang.id')
            ->join('suatchieu', 've.suat_chieu_id', '=', 'suatchieu.id')
            ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->join('phim', 'suatchieu.id_phim', '=', 'phim.id')
            ->where('phongchieu.id_rapphim', $idRap)
            ->where('ve.trang_thai', 2) // Vé đã đặt
            ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery]);
        
        $duLieuVe = $queryVe->get()->map(function($item) {
            return [
                'id' => $item->id,
                'gia_ve' => (float)$item->gia_ve,
                'ngay_tao' => $item->ngay_tao,
                'suat_chieu_id' => $item->suat_chieu_id,
                'id_phim' => $item->id_phim,
                'id_phongchieu' => $item->id_phongchieu,
                'id_rapphim' => $item->id_rapphim,
                'ten_phim' => $item->ten_phim,
                'poster_url' => $item->poster_url
            ];
        })->toArray();
        
        // Dữ liệu vé kỳ trước
        $queryVeKyTruoc = Ve::selectRaw('
                ve.id,
                ve.gia_ve,
                ve.ngay_tao,
                ve.suat_chieu_id,
                ve.donhang_id,
                suatchieu.id_phim,
                suatchieu.id_phongchieu,
                phongchieu.id_rapphim
            ')
            ->join('donhang', 've.donhang_id', '=', 'donhang.id')
            ->join('suatchieu', 've.suat_chieu_id', '=', 'suatchieu.id')
            ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->where('phongchieu.id_rapphim', $idRap)
            ->where('ve.trang_thai', 2) // Vé đã đặt
            ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
            ->whereBetween('donhang.ngay_dat', [$tuNgayKyTruocQuery, $denNgayKyTruocQuery]);
        
        $duLieuVeKyTruoc = $queryVeKyTruoc->get()->map(function($item) {
            return [
                'id' => $item->id,
                'gia_ve' => (float)$item->gia_ve,
                'ngay_tao' => $item->ngay_tao
            ];
        })->toArray();
        
        // ========== 2. DỮ LIỆU ĐƠN HÀNG (DonHang) ==========
        $queryDonHang = DonHang::selectRaw('
                donhang.id,
                donhang.tong_tien,
                donhang.ngay_dat,
                donhang.trang_thai,
                donhang.suat_chieu_id,
                donhang.user_id,
                donhang.rap_id,
                suatchieu.id_phim,
                suatchieu.id_phongchieu,
                phongchieu.id_rapphim
            ')
            ->leftJoin('suatchieu', 'donhang.suat_chieu_id', '=', 'suatchieu.id')
            ->leftJoin('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->where('donhang.trang_thai', 2) // Đã thanh toán
            ->where(function($query) use ($idRap) {
                $query->where('phongchieu.id_rapphim', $idRap)
                      ->orWhere('donhang.rap_id', $idRap);
            })
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery]);
        
        $duLieuDonHang = $queryDonHang->get()->map(function($item) {
            return [
                'id' => $item->id,
                'tong_tien' => (float)$item->tong_tien,
                'ngay_dat' => $item->ngay_dat,
                'trang_thai' => $item->trang_thai,
                'suat_chieu_id' => $item->suat_chieu_id,
                'user_id' => $item->user_id,
                'id_phim' => $item->id_phim,
                'id_phongchieu' => $item->id_phongchieu,
                'id_rapphim' => $item->id_rapphim ?? $item->rap_id,
                'rap_id' => $item->rap_id
            ];
        })->toArray();
        
        // ========== 3. DỮ LIỆU CHI TIẾT ĐƠN HÀNG F&B (ChiTietDonHang) ==========
        $queryChiTietDonHang = ChiTietDonHang::selectRaw('
                chitiet_donhang.id,
                chitiet_donhang.donhang_id,
                chitiet_donhang.sanpham_id,
                chitiet_donhang.so_luong,
                chitiet_donhang.don_gia,
                chitiet_donhang.thanh_tien,
                san_pham.ten,
                san_pham.hinh_anh,
                san_pham.id_rapphim,
                donhang.ngay_dat,
                donhang.trang_thai
            ')
            ->join('donhang', 'chitiet_donhang.donhang_id', '=', 'donhang.id')
            ->join('san_pham', 'chitiet_donhang.sanpham_id', '=', 'san_pham.id')
            ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
            ->where('san_pham.id_rapphim', $idRap)
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery]);
        
        $duLieuChiTietDonHang = $queryChiTietDonHang->get()->map(function($item) {
            return [
                'id' => $item->id,
                'id_donhang' => $item->donhang_id,
                'id_sanpham' => $item->sanpham_id,
                'so_luong' => (int)($item->so_luong ?? 0),
                'gia_ban' => (float)($item->don_gia ?? 0),
                'thanh_tien' => (float)($item->thanh_tien ?? 0),
                'ten_san_pham' => $item->ten ?? '',
                'hinh_anh' => $item->hinh_anh ?? '',
                'id_rapphim' => $item->id_rapphim ?? null,
                'ngay_dat' => $item->ngay_dat ?? ''
            ];
        })->toArray();
        
        // ========== 4. DỮ LIỆU MUA PHIM (MuaPhim) ==========
        $queryMuaPhim = MuaPhim::selectRaw('
                mua_phim.id,
                mua_phim.so_tien,
                mua_phim.trang_thai,
                mua_phim.don_hang_id,
                donhang.ngay_dat,
                donhang.suat_chieu_id,
                donhang.phim_id,
                suatchieu.id_phim as suat_chieu_phim_id,
                phongchieu.id_rapphim,
                COALESCE(phim_suat.ten_phim, phim_don.ten_phim) as ten_phim,
                COALESCE(phim_suat.poster_url, phim_don.poster_url) as poster_url
            ')
            ->join('donhang', 'mua_phim.don_hang_id', '=', 'donhang.id')
            ->leftJoin('suatchieu', 'donhang.suat_chieu_id', '=', 'suatchieu.id')
            ->leftJoin('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->leftJoin('phim as phim_suat', 'suatchieu.id_phim', '=', 'phim_suat.id')
            ->leftJoin('phim as phim_don', 'donhang.phim_id', '=', 'phim_don.id')
            ->where('mua_phim.trang_thai', 2) // Đã mua
            ->where('donhang.trang_thai', 2) // Đơn hàng đã thanh toán
            ->where(function($query) use ($idRap) {
                $query->where('phongchieu.id_rapphim', $idRap)
                      ->orWhere('donhang.rap_id', $idRap);
            })
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery]);
        
        $duLieuMuaPhim = $queryMuaPhim->get()->map(function($item) {
            return [
                'id' => $item->id,
                'so_tien' => (float)$item->so_tien,
                'trang_thai' => $item->trang_thai,
                'don_hang_id' => $item->don_hang_id,
                'ngay_dat' => $item->ngay_dat,
                'suat_chieu_id' => $item->suat_chieu_id,
                'id_phim' => $item->suat_chieu_phim_id ?? $item->phim_id,
                'ten_phim' => $item->ten_phim,
                'poster_url' => $item->poster_url
            ];
        })->toArray();
        
        // ========== 5. DỮ LIỆU SUẤT CHIẾU (SuatChieu) ==========
        $querySuatChieu = SuatChieu::selectRaw('
                suatchieu.id,
                suatchieu.id_phongchieu,
                suatchieu.id_phim,
                suatchieu.batdau,
                suatchieu.ketthuc,
                phongchieu.id_rapphim,
                phongchieu.so_luong_ghe
            ')
            ->join('phongchieu', 'suatchieu.id_phongchieu', '=', 'phongchieu.id')
            ->where('phongchieu.id_rapphim', $idRap)
            ->whereBetween('suatchieu.batdau', [$tuNgayQuery, $denNgayQuery]);
        
        $duLieuSuatChieu = $querySuatChieu->get()->map(function($item) {
            $batdauDate = $item->batdau ? date('Y-m-d', strtotime($item->batdau)) : null;
            $batdauTime = $item->batdau ? date('H:i:s', strtotime($item->batdau)) : null;
            
            return [
                'id' => $item->id,
                'id_phongchieu' => $item->id_phongchieu,
                'id_phim' => $item->id_phim,
                'ngay_chieu' => $batdauDate,
                'gio_bat_dau' => $batdauTime,
                'batdau' => $item->batdau,
                'ketthuc' => $item->ketthuc,
                'id_rapphim' => $item->id_rapphim,
                'so_ghe' => (int)($item->so_luong_ghe ?? 0)
            ];
        })->toArray();
        
        // ========== 6. DỮ LIỆU RẠP PHIM (RapPhim) ==========
        $duLieuRapPhim = RapPhim::where('id', $idRap)
            ->select('id', 'ten')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'ten' => $item->ten
                ];
            })->toArray();
        
        // Trả về dữ liệu thô
        return [
            'thoi_gian' => [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
                'tu_ngay_query' => $tuNgayQuery,
                'den_ngay_query' => $denNgayQuery,
                'tu_ngay_ky_truoc' => $tuNgayKyTruocDate->format('Y-m-d'),
                'den_ngay_ky_truoc' => $denNgayKyTruocDate->format('Y-m-d'),
                'tu_ngay_ky_truoc_query' => $tuNgayKyTruocQuery,
                'den_ngay_ky_truoc_query' => $denNgayKyTruocQuery,
                'so_ngay' => $soNgayTrongKhoang
            ],
            'filter' => [
                'id_rap' => $idRap
            ],
            'du_lieu_ve' => $duLieuVe,
            'du_lieu_ve_ky_truoc' => $duLieuVeKyTruoc,
            'du_lieu_don_hang' => $duLieuDonHang,
            'du_lieu_chi_tiet_don_hang' => $duLieuChiTietDonHang,
            'du_lieu_mua_phim' => $duLieuMuaPhim,
            'du_lieu_suat_chieu' => $duLieuSuatChieu,
            'du_lieu_rap_phim' => $duLieuRapPhim
        ];
    }
    
    /**
     * Thống kê tổng quan cho nhân viên
     * 
     * @param int $idNhanVien ID nhân viên
     * @param string $tuNgay Ngày bắt đầu (Y-m-d)
     * @param string $denNgay Ngày kết thúc (Y-m-d)
     * @param bool $soSanhVoiKyTruoc Có so sánh với kỳ trước không
     * @return array Thống kê tổng quan
     */
    public function thongKeTongQuanNhanVien($idNhanVien, $tuNgay, $denNgay, $soSanhVoiKyTruoc = false) {
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
        
        // Tính số ngày trong kỳ
        $soNgayTrongKy = $tuNgayDate->diff($denNgayDate)->days + 1;
        
        // Tính kỳ trước nếu cần
        $tongDoanhThuKyTruoc = 0;
        $tongVeBanKyTruoc = 0;
        $tongDonHangKyTruoc = 0;
        
        if ($soSanhVoiKyTruoc) {
            $soNgayKyTruoc = $soNgayTrongKy;
            $denNgayKyTruoc = clone $tuNgayDate;
            $denNgayKyTruoc->modify('-1 day');
            $tuNgayKyTruoc = clone $denNgayKyTruoc;
            $tuNgayKyTruoc->modify('-' . ($soNgayKyTruoc - 1) . ' days');
            
            $tuNgayKyTruocQuery = $tuNgayKyTruoc->format('Y-m-d H:i:s');
            $denNgayKyTruocQuery = $denNgayKyTruoc->format('Y-m-d 23:59:59');
            
            // Doanh thu kỳ trước
            $tongDoanhThuKyTruoc = DonHang::where('id_nhanvien', $idNhanVien)
                ->where('trang_thai', 2)
                ->whereBetween('ngay_dat', [$tuNgayKyTruocQuery, $denNgayKyTruocQuery])
                ->sum('tong_tien');
            
            // Số vé bán kỳ trước
            $tongVeBanKyTruoc = Ve::whereHas('donhang', function($q) use ($idNhanVien, $tuNgayKyTruocQuery, $denNgayKyTruocQuery) {
                    $q->where('id_nhanvien', $idNhanVien)
                      ->where('trang_thai', 2)
                      ->whereBetween('ngay_dat', [$tuNgayKyTruocQuery, $denNgayKyTruocQuery]);
                })
                ->where('trang_thai', 2)
                ->count();
            
            // Số đơn hàng kỳ trước
            $tongDonHangKyTruoc = DonHang::where('id_nhanvien', $idNhanVien)
                ->where('trang_thai', 2)
                ->whereBetween('ngay_dat', [$tuNgayKyTruocQuery, $denNgayKyTruocQuery])
                ->count();
        }
        
        // Doanh thu hiện tại
        $tongDoanhThu = DonHang::where('id_nhanvien', $idNhanVien)
            ->where('trang_thai', 2)
            ->whereBetween('ngay_dat', [$tuNgayQuery, $denNgayQuery])
            ->sum('tong_tien');
        
        // Số vé bán hiện tại
        $tongVeBan = Ve::whereHas('donhang', function($q) use ($idNhanVien, $tuNgayQuery, $denNgayQuery) {
                $q->where('id_nhanvien', $idNhanVien)
                  ->where('trang_thai', 2)
                  ->whereBetween('ngay_dat', [$tuNgayQuery, $denNgayQuery]);
            })
            ->where('trang_thai', 2)
            ->count();
        
        // Số đơn hàng hiện tại
        $tongDonHang = DonHang::where('id_nhanvien', $idNhanVien)
            ->where('trang_thai', 2)
            ->whereBetween('ngay_dat', [$tuNgayQuery, $denNgayQuery])
            ->count();
        
        // Tính phần trăm thay đổi
        $phanTramDoanhThu = $tongDoanhThuKyTruoc > 0 
            ? (($tongDoanhThu - $tongDoanhThuKyTruoc) / $tongDoanhThuKyTruoc) * 100 
            : ($tongDoanhThu > 0 ? 100 : 0);
        
        $phanTramVeBan = $tongVeBanKyTruoc > 0 
            ? (($tongVeBan - $tongVeBanKyTruoc) / $tongVeBanKyTruoc) * 100 
            : ($tongVeBan > 0 ? 100 : 0);
        
        $phanTramDonHang = $tongDonHangKyTruoc > 0 
            ? (($tongDonHang - $tongDonHangKyTruoc) / $tongDonHangKyTruoc) * 100 
            : ($tongDonHang > 0 ? 100 : 0);
        
        return [
            'tong_doanh_thu' => $tongDoanhThu,
            'tong_ve_ban' => $tongVeBan,
            'tong_don_hang' => $tongDonHang,
            'so_ngay_trong_ky' => $soNgayTrongKy,
            'so_sanh_ky_truoc' => $soSanhVoiKyTruoc ? [
                'tong_doanh_thu' => $tongDoanhThuKyTruoc,
                'tong_ve_ban' => $tongVeBanKyTruoc,
                'tong_don_hang' => $tongDonHangKyTruoc,
                'phan_tram_doanh_thu' => round($phanTramDoanhThu, 2),
                'phan_tram_ve_ban' => round($phanTramVeBan, 2),
                'phan_tram_don_hang' => round($phanTramDonHang, 2)
            ] : null
        ];
    }
    
    /**
     * Xu hướng doanh thu theo ngày cho nhân viên
     * 
     * @param int $idNhanVien ID nhân viên
     * @param string $tuNgay Ngày bắt đầu (Y-m-d)
     * @param string $denNgay Ngày kết thúc (Y-m-d)
     * @return array Dữ liệu xu hướng
     */
    public function xuHuongDoanhThuNhanVien($idNhanVien, $tuNgay, $denNgay) {
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        
        $chiTietTheoNgay = [];
        $currentDate = clone $tuNgayDate;
        
        while ($currentDate <= $denNgayDate) {
            $ngayBatDau = $currentDate->format('Y-m-d 00:00:00');
            $ngayKetThuc = $currentDate->format('Y-m-d 23:59:59');
            
            // Doanh thu trong ngày
            $doanhThuNgay = DonHang::where('id_nhanvien', $idNhanVien)
                ->where('trang_thai', 2)
                ->whereBetween('ngay_dat', [$ngayBatDau, $ngayKetThuc])
                ->sum('tong_tien');
            
            // Số vé bán trong ngày
            $soVeBanNgay = Ve::whereHas('donhang', function($q) use ($idNhanVien, $ngayBatDau, $ngayKetThuc) {
                    $q->where('id_nhanvien', $idNhanVien)
                      ->where('trang_thai', 2)
                      ->whereBetween('ngay_dat', [$ngayBatDau, $ngayKetThuc]);
                })
                ->where('trang_thai', 2)
                ->count();
            
            // Số đơn hàng trong ngày
            $soDonHangNgay = DonHang::where('id_nhanvien', $idNhanVien)
                ->where('trang_thai', 2)
                ->whereBetween('ngay_dat', [$ngayBatDau, $ngayKetThuc])
                ->count();
            
            $chiTietTheoNgay[] = [
                'ngay' => $currentDate->format('Y-m-d'),
                'ngay_formatted' => $currentDate->format('d/m/Y'),
                'tong_doanh_thu' => $doanhThuNgay,
                'so_ve_ban' => $soVeBanNgay,
                'so_don_hang' => $soDonHangNgay
            ];
            
            $currentDate->modify('+1 day');
        }
        
        return [
            'tu_ngay' => $tuNgay,
            'den_ngay' => $denNgay,
            'chi_tiet_theo_ngay' => $chiTietTheoNgay
        ];
    }
    
    /**
     * Top 5 phim bán chạy nhất của nhân viên
     * 
     * @param int $idNhanVien ID nhân viên
     * @param string $tuNgay Ngày bắt đầu (Y-m-d)
     * @param string $denNgay Ngày kết thúc (Y-m-d)
     * @return array Danh sách phim
     */
    public function top5PhimBanChayNhanVien($idNhanVien, $tuNgay, $denNgay) {
        $tuNgayDate = new \DateTime($tuNgay . ' 00:00:00');
        $denNgayDate = new \DateTime($denNgay . ' 23:59:59');
        $tuNgayQuery = $tuNgayDate->format('Y-m-d H:i:s');
        $denNgayQuery = $denNgayDate->format('Y-m-d H:i:s');
        
        // Lấy doanh thu từ vé cho từng phim
        $doanhThuVeCollection = Ve::selectRaw('
                suatchieu.id_phim,
                SUM(ve.gia_ve) as doanh_thu_ve,
                COUNT(ve.id) as so_ve_ban
            ')
            ->join('suatchieu', 've.suat_chieu_id', '=', 'suatchieu.id')
            ->join('donhang', 've.donhang_id', '=', 'donhang.id')
            ->where('donhang.id_nhanvien', $idNhanVien)
            ->where('ve.trang_thai', 2)
            ->where('donhang.trang_thai', 2)
            ->whereBetween('donhang.ngay_dat', [$tuNgayQuery, $denNgayQuery])
            ->groupBy('suatchieu.id_phim')
            ->orderBy('doanh_thu_ve', 'desc')
            ->limit(5)
            ->get();
        
        $ketQua = [];
        foreach ($doanhThuVeCollection as $item) {
            $phim = Phim::find($item->id_phim);
            if ($phim) {
                $ketQua[] = [
                    'id' => $phim->id,
                    'ten_phim' => $phim->ten_phim,
                    'poster_url' => $phim->poster_url,
                    'doanh_thu_ve' => $item->doanh_thu_ve,
                    'so_ve_ban' => $item->so_ve_ban
                ];
            }
        }
        
        return $ketQua;
    }
    
}
    
?>
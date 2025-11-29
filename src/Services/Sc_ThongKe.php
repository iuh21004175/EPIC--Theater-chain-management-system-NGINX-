<?php
    namespace App\Services;
    use App\Models\DonHang;
    use App\Models\Phim;
    use App\Models\SuatChieu;
    use App\Models\RapPhim;
    use App\Models\Ve;
    use App\Models\ChiTietDonHang;
    use App\Models\PhanPhoiPhim;
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
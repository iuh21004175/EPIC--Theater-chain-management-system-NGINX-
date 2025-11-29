import Spinner from "./util/spinner.js";
document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra ApexCharts đã load chưa
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts not loaded');
        alert('Thư viện biểu đồ chưa được tải. Vui lòng tải lại trang.');
        return;
    }
    
    // Xử lý bộ lọc thời gian
    const dateRangeSelect = document.getElementById('date-range');
    const customDateRange = document.getElementById('custom-date-range');

    // Xử lý date range selector để hỗ trợ future dates (cho suất chiếu)
    if (dateRangeSelect) {
        dateRangeSelect.addEventListener('change', function() {
            const value = this.value;
            if (value === 'custom') {
                customDateRange.classList.remove('hidden');
            } else {
                customDateRange.classList.add('hidden');
                fetchData(value);
            }
        });
    }

    document.getElementById('apply-date-range').addEventListener('click', function() {
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        if (startDate && endDate) {
            fetchData('custom', { startDate, endDate });
        } else {
            alert('Vui lòng chọn ngày bắt đầu và ngày kết thúc');
        }
    });

    // Xử lý các nút phân tích
    document.getElementById('btn-movie-analysis').addEventListener('click', function() {
        switchAnalysisTab(this, 'movie');
    });
    
    document.getElementById('btn-food-analysis').addEventListener('click', function() {
        switchAnalysisTab(this, 'food');
    });
    
    document.getElementById('btn-showtime-analysis').addEventListener('click', function() {
        switchAnalysisTab(this, 'showtime');
    });

    // Xử lý xuất dữ liệu
    document.getElementById('btn-export-data').addEventListener('click', function() {
        exportData();
    });

    // Toggle buttons for Order and Showtime statistics
    const toggleDonHangBtn = document.getElementById('toggle-don-hang');
    const toggleSuatChieuBtn = document.getElementById('toggle-suat-chieu');
    
    // Function to activate a button and deactivate the other
    const activeClasses = ['bg-blue-500', 'border-blue-500', 'text-white', 'shadow-lg'];
    const inactiveClasses = ['bg-gray-100', 'border-gray-400', 'text-gray-700', 'shadow-md'];
    
    function activateButton(activeBtn, inactiveBtn) {
        // Activate the clicked button
        activeBtn.classList.add('active');
        activeBtn.classList.remove(...inactiveClasses);
        activeBtn.classList.add(...activeClasses);
        
        // Deactivate the other button
        if (inactiveBtn) {
            inactiveBtn.classList.remove('active');
            inactiveBtn.classList.remove(...activeClasses);
            inactiveBtn.classList.add(...inactiveClasses);
        }
    }
    
    // Function to toggle sections visibility
    function toggleSections(selector, isActive) {
        const sections = document.querySelectorAll(selector);
        sections.forEach(section => {
            if (isActive) {
                section.classList.remove('hidden');
            } else {
                section.classList.add('hidden');
            }
        });
    }
    
    // Function to sync toggle states
    function syncToggleStates() {
        const donHangActive = toggleDonHangBtn && toggleDonHangBtn.classList.contains('active');
        const suatChieuActive = toggleSuatChieuBtn && toggleSuatChieuBtn.classList.contains('active');
        
        // Xử lý phần đơn hàng
        if (donHangActive) {
            toggleSections('.stat-section-don-hang', true);
        } else {
            toggleSections('.stat-section-don-hang', false);
        }
        
        // Xử lý phần suất chiếu
        if (suatChieuActive) {
            toggleSections('.stat-section-suat-chieu', true);
        } else {
            toggleSections('.stat-section-suat-chieu', false);
        }
    }
    
    if (toggleDonHangBtn) {
        // Initialize: mặc định active nút "Đơn hàng" khi tải trang
        if (!toggleDonHangBtn.classList.contains('active')) {
            toggleDonHangBtn.classList.add('active');
        }
        activateButton(toggleDonHangBtn, toggleSuatChieuBtn);
        updateDateRangeFilter(false);
        
        toggleDonHangBtn.addEventListener('click', function() {
            if (this.classList.contains('active')) {
                return;
            }
            
            activateButton(this, toggleSuatChieuBtn);
            syncToggleStates();
            updateDateRangeFilter(false);
            
            // Tự động fetch API với dữ liệu 7 ngày qua
            setTimeout(() => {
                fetchData('7');
            }, 100);
        });
    }
    
    if (toggleSuatChieuBtn) {
        toggleSuatChieuBtn.addEventListener('click', function() {
            if (this.classList.contains('active')) {
                return;
            }
            
            activateButton(this, toggleDonHangBtn);
            syncToggleStates();
            updateDateRangeFilter(true);
            
            // Tự động fetch API với dữ liệu 7 ngày tới
            setTimeout(() => {
                fetchData('7f');
            }, 100);
        });
    }
    
    // Initialize visibility on page load
    syncToggleStates();

    // Khởi tạo biểu đồ suất chiếu
    setTimeout(function() {
        initializeRevenueShowtimeChart();
        initializeTicketsShowtimeChart();
        initializeTheaterPerformanceShowtimeChart();
        initializeRevenueBreakdownShowtimeChart();
        
        // Event listeners cho các nút time filter suất chiếu
        document.querySelectorAll('.time-filter-suat-chieu').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.time-filter-suat-chieu').forEach(btn => {
                    btn.classList.remove('filter-active', 'bg-blue-500', 'text-white', 'border-blue-500');
                    btn.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
                });
                
                // Add active class to clicked button
                this.classList.add('filter-active', 'bg-blue-500', 'text-white', 'border-blue-500');
                this.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');
                
                // Update current time period
                currentTimePeriodShowtime = this.dataset.period || 'daily';
                
                // Re-process and update charts if we have cached data
                if (cachedShowtimeData && cachedShowtimeData.length > 0) {
                    const startDateInput = document.getElementById('start-date');
                    const endDateInput = document.getElementById('end-date');
                    const tuNgay = startDateInput ? startDateInput.value : new Date().toISOString().split('T')[0];
                    const denNgay = endDateInput ? endDateInput.value : new Date().toISOString().split('T')[0];
                    
                    const processedData = xuLyDuLieuSuatChieuChoBieuDo(cachedShowtimeData, tuNgay, denNgay, currentTimePeriodShowtime);
                    const hieuSuatTheoRap = tinhHieuSuatTheoRapTuSuatChieu(cachedShowtimeData);
                    const coCauDoanhThu = tinhCoCauDoanhThuTuSuatChieu(cachedShowtimeData);
                    
                    // Update revenue chart
                    if (revenueShowtimeChart) {
                        revenueShowtimeChart.updateOptions({
                            xaxis: { categories: processedData.xu_huong_doanh_thu.danh_sach_nhan }
                        });
                        revenueShowtimeChart.updateSeries([{
                            name: 'Doanh thu',
                            data: processedData.xu_huong_doanh_thu.chi_tiet
                        }]);
                    }
                    
                    // Update tickets chart
                    if (ticketsShowtimeChart) {
                        ticketsShowtimeChart.updateOptions({
                            xaxis: { categories: processedData.xu_huong_ve_ban.danh_sach_nhan }
                        });
                        ticketsShowtimeChart.updateSeries([{
                            name: 'Số vé bán',
                            data: processedData.xu_huong_ve_ban.chi_tiet
                        }]);
                    }
                    
                    // Update theater performance chart
                    if (theaterPerformanceShowtimeChart) {
                        if (hieuSuatTheoRap.danh_sach_rap && hieuSuatTheoRap.danh_sach_rap.length > 0) {
                            theaterPerformanceShowtimeChart.updateOptions({
                                xaxis: { categories: hieuSuatTheoRap.danh_sach_rap.map(r => r.ten_rap) },
                                noData: { 
                                    text: '',
                                    style: { fontSize: '0px', color: 'transparent' }
                                }
                            });
                            theaterPerformanceShowtimeChart.updateSeries([{
                                name: 'Doanh thu',
                                data: hieuSuatTheoRap.danh_sach_rap.map(r => r.doanh_thu)
                            }]);
                        } else {
                            theaterPerformanceShowtimeChart.updateOptions({
                                xaxis: { categories: [] },
                                noData: { 
                                    text: '',
                                    style: { fontSize: '0px', color: 'transparent' }
                                }
                            });
                            theaterPerformanceShowtimeChart.updateSeries([{
                                name: 'Doanh thu',
                                data: []
                            }]);
                        }
                    }
                    
                    // Update revenue breakdown chart
                    if (revenueBreakdownShowtimeChart) {
                        revenueBreakdownShowtimeChart.updateOptions({
                            labels: coCauDoanhThu.chi_tiet.map(item => item.loai),
                            colors: coCauDoanhThu.chi_tiet.map(item => item.mau_sac)
                        });
                        revenueBreakdownShowtimeChart.updateSeries(coCauDoanhThu.chi_tiet.map(item => item.phan_tram));
                    }
                }
            });
        });
    }, 500);

    // Hiển thị spinner ngay khi trang tải
    preShowAllSpinners();
    
    // Khởi tạo dữ liệu mẫu và biểu đồ khi trang được tải
    initializeDashboard();
    fetchData('7');
});

// Biến global để lưu loại phân tích hiện tại và dữ liệu
let currentAnalysisType = 'movie';
let cachedMoviesData = [];
let cachedFoodsData = [];
let cachedShowtimesData = [];

// Lưu dữ liệu thô và filter hiện tại để tránh fetch lại không cần thiết
let cachedRawData = null;
let cachedFilters = {
    tuNgay: null,
    denNgay: null
};

// Lưu dữ liệu suất chiếu để cập nhật biểu đồ
let cachedShowtimeData = null;

// Time period for showtime charts
let currentTimePeriodShowtime = 'daily';

// Charts for showtime statistics
let revenueShowtimeChart = null;
let ticketsShowtimeChart = null;
let theaterPerformanceShowtimeChart = null;
let revenueBreakdownShowtimeChart = null;

// Khởi tạo dashboard với dữ liệu mẫu
function initializeDashboard() {
    try {
        // Kiểm tra ApexCharts đã load
        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts not loaded');
            return;
        }
        
        // Xóa tất cả biểu đồ hiện tại để tránh duplicate
        destroyAllCharts();
        
        // Đảm bảo dữ liệu mẫu có đủ cả đồ ăn
        const sampleData = generateSampleDataWithFoods();
        
        // Cập nhật dữ liệu tổng quan
        updateOverviewData(sampleData.overview);
        
        // Không khởi tạo biểu đồ trống vì chúng ta sẽ hiển thị spinner
        // và chờ dữ liệu thực từ API
        
        // Cập nhật bảng phân tích
        updateAnalysisTable(sampleData.movies, 'movie');
        
        // Hiển thị đề xuất kinh doanh
        updateBusinessRecommendations(sampleData.recommendations);
    } catch (error) {
        console.error('Error initializing dashboard:', error);
    }
}

// Cập nhật hàm generateSampleDataWithFoods
function generateSampleDataWithFoods() {
    const data = generateSampleData();
    
    // Đảm bảo dữ liệu mẫu cho đồ ăn luôn có giá trị giống với phim (trống khi mới load)
    data.foods = [];
    
    return data;
}

// Hàm tạo dữ liệu mẫu cho việc demo
// Thay thế hàm generateSampleData để sử dụng dữ liệu từ API
function generateSampleData() {
    return {
        overview: {
            totalRevenue: 0,
            revenueTrend: 0,
            totalCustomers: 0,
            customerTrend: 0,
            occupancyRate: 0,
            occupancyTrend: 0,
            foodPerCustomer: 0,
            foodTrend: 0
        },
        revenueByDate: [],
        revenueDistribution: [],
        movies: [],
        foods: [],
        showtimes: [],
        customerTrends: [],
        recommendations: []
    };
}


// Hàm chuyển đổi dữ liệu tổng quan
function transformOverviewData(tongQuatData) {
    if (!tongQuatData || !tongQuatData.success || !tongQuatData.data) {
        return {
            totalRevenue: 0,
            revenueTrend: 0,
            totalCustomers: 0,
            customerTrend: 0,
            occupancyRate: 0,
            occupancyTrend: 0,
            foodPerCustomer: 0,
            foodTrend: 0
        };
    }
    
    const data = tongQuatData.data;
    
    return {
        totalRevenue: parseFloat(data.kyHienTai.tongDoanhThu),
        revenueTrend: calculateTrend(data.kyHienTai.tongDoanhThu, data.kyTruoc.tongDoanhThu),
        totalCustomers: data.kyHienTai.soLuongKhachHang,
        customerTrend: calculateTrend(data.kyHienTai.soLuongKhachHang, data.kyTruoc.soLuongKhachHang),
        occupancyRate: data.kyHienTai.tiLeLapDayGhe,
        occupancyTrend: calculateTrend(data.kyHienTai.tiLeLapDayGhe, data.kyTruoc.tiLeLapDayGhe),
        foodPerCustomer: data.kyHienTai.doanhThuDoAnUongBinhQuan,
        foodTrend: calculateTrend(data.kyHienTai.doanhThuDoAnUongBinhQuan, data.kyTruoc.doanhThuDoAnUongBinhQuan)
    };
}

// Hàm tính phần trăm thay đổi
function calculateTrend(current, previous) {
    if (!previous || previous === 0) return 0;
    return ((current - previous) / previous) * 100;
}

// Hàm để chuyển đổi dữ liệu doanh thu theo ngày
function transformRevenueByDateData(phanTichData) {
    if (!phanTichData || !phanTichData.success || !phanTichData.data || !phanTichData.data.chi_tiet_theo_ngay) {
        return [];
    }
    
    return phanTichData.data.chi_tiet_theo_ngay.map(item => ({
        date: item.ngay_formatted,
        total: item.tong_doanh_thu,
        ticket: item.doanh_thu_ve,
        food: item.doanh_thu_san_pham
    }));
}

// Hàm để chuyển đổi dữ liệu phân bổ doanh thu
function transformRevenueDistributionData(phanTichData) {
    if (!phanTichData || !phanTichData.success || !phanTichData.data || !phanTichData.data.tong_ket) {

        return [
            { name: 'Vé phim', value: 75 },
            { name: 'Đồ ăn & Đồ uống', value: 20 }
        ];
    }
    
    const tongDoanhThu = phanTichData.data.tong_ket.tong_doanh_thu_ve + phanTichData.data.tong_ket.tong_doanh_thu_san_pham;
    
    // if (tongDoanhThu === 0) {
    //     return [
    //         { name: 'Vé phim', value: 75 },
    //         { name: 'Đồ ăn & Đồ uống', value: 20 }
    //     ];
    // }
    
    const phanTramVe = Math.round((phanTichData.data.tong_ket.tong_doanh_thu_ve / tongDoanhThu) * 100);
    const phanTramDoAn = Math.round((phanTichData.data.tong_ket.tong_doanh_thu_san_pham / tongDoanhThu) * 100);
    console.log('Revenue Distribution:', { phanTramVe, phanTramDoAn });
    //const phanTramKhac = 100 - phanTramVe - phanTramDoAn;
    
    return [
        { name: 'Vé phim', value: phanTramVe },
        { name: 'Đồ ăn & Đồ uống', value: phanTramDoAn }
    ];
}

// Hàm chuyển đổi dữ liệu top 10 phim
function transformMoviesData(top10PhimData) {
    if (!top10PhimData || !top10PhimData.success || !top10PhimData.data || !top10PhimData.data.top_10_phim) {
        return [];
    }
    
    return top10PhimData.data.top_10_phim.map(phim => ({
        name: phim.ten_phim,
        revenue: phim.doanh_thu_ve,
        tickets: phim.so_luot || 0,
        contribution: phim.ty_le_dong_gop || 0,
        trend: phim.so_voi_ky_truoc?.ty_le || 0
    }));
}

// Hàm chuyển đổi dữ liệu top 10 sản phẩm
function transformFoodsData(top10SanPhamData) {
    if (!top10SanPhamData || !top10SanPhamData.success || !top10SanPhamData.data) {
        console.error('Invalid API response structure:', top10SanPhamData);
        return [];
    }
    
    // Kiểm tra cấu trúc dữ liệu
    console.log('API data structure:', top10SanPhamData.data);
    
    // Lấy mảng top_10_san_pham từ dữ liệu API
    // Đảm bảo cấu trúc dữ liệu phù hợp với API thực tế
    const sanPhamList = top10SanPhamData.data.top_10_san_pham || [];
    
    if (sanPhamList.length === 0) {
        console.warn('No products found in API data');
        return [];
    }
    
    return sanPhamList.map(sanPham => {
        // Tính tỷ lệ thay đổi so với kỳ trước (nếu có)
        let trend = 0;
        if (sanPham.so_voi_ky_truoc && typeof sanPham.so_voi_ky_truoc.ty_le !== 'undefined') {
            trend = sanPham.so_voi_ky_truoc.ty_le;
            if (sanPham.so_voi_ky_truoc.tang === false) {
                trend = -trend; // Đổi dấu nếu là giảm
            }
        }
        
        // Đảm bảo thuộc tính được lấy đúng từ API response
        return {
            id: sanPham.id || sanPham.id_san_pham,
            name: sanPham.ten_san_pham || sanPham.ten || 'Không có tên',
            revenue: parseFloat(sanPham.doanh_thu || 0),
            quantity: parseInt(sanPham.so_luot || sanPham.so_luong_ban || 0, 10),
            contribution: parseFloat(sanPham.ty_le_dong_gop || 0),
            trend: trend
        };
    });
}

// Hàm chuyển đổi dữ liệu hiệu quả theo khung giờ
function transformShowtimesData(hieuQuaKhungGioData) {
    if (!hieuQuaKhungGioData || !hieuQuaKhungGioData.success || !hieuQuaKhungGioData.data || !hieuQuaKhungGioData.data.chi_tiet_theo_khung_gio) {
        return [];
    }
    
    return hieuQuaKhungGioData.data.chi_tiet_theo_khung_gio.map(item => ({
        time: item.khung_gio,
        occupancy: item.ty_le_lap_day,
        revenue: item.tong_doanh_thu,
        contribution: item.ty_le_dong_gop || 0,
        trend: item.so_voi_ky_truoc?.ty_le || 0
    }));
}

// Hàm tạo đề xuất kinh doanh dựa trên dữ liệu
function generateRecommendations(overview, movies, foods, showtimes, customerTrends) {
    const recommendations = [];
    
    // Đề xuất về khung giờ hiệu quả nhất
    if (showtimes && showtimes.length > 0) {
        // Tìm khung giờ có tỷ lệ lấp đầy cao nhất
        const bestOccupancyShowtime = [...showtimes].sort((a, b) => b.occupancy - a.occupancy)[0];
        
        if (bestOccupancyShowtime && bestOccupancyShowtime.occupancy > 75) {
            recommendations.push({
                title: "Tối ưu giờ chiếu phim",
                content: `Khung giờ ${bestOccupancyShowtime.time} đạt hiệu suất cao nhất với tỷ lệ lấp đầy ${bestOccupancyShowtime.occupancy}%. Nên tăng số lượng suất chiếu các bộ phim được ưa chuộng trong khung giờ này.`,
                type: "success"
            });
        }
        
        // Tìm khung giờ có tỷ lệ lấp đầy thấp nhất
        const worstOccupancyShowtime = [...showtimes].sort((a, b) => a.occupancy - b.occupancy)[0];
        
        if (worstOccupancyShowtime && worstOccupancyShowtime.occupancy < 50) {
            recommendations.push({
                title: "Cảnh báo doanh thu",
                content: `Doanh thu các suất chiếu ${worstOccupancyShowtime.time} có tỷ lệ lấp đầy thấp (${worstOccupancyShowtime.occupancy}%). Cân nhắc giảm giá vé hoặc tạo chương trình khuyến mãi để thu hút khách hàng.`,
                type: "warning"
            });
        }
    }
    
    // Đề xuất về sản phẩm bán chạy
    if (foods && foods.length > 0) {
        // Tìm sản phẩm có xu hướng tăng mạnh nhất
        const bestTrendFood = [...foods].sort((a, b) => b.trend - a.trend)[0];
        
        if (bestTrendFood && bestTrendFood.trend > 10) {
            recommendations.push({
                title: "Khuyến mãi đồ ăn",
                content: `${bestTrendFood.name} có xu hướng tăng ${bestTrendFood.trend}%. Nên tạo thêm các combo mới kết hợp với sản phẩm này để tăng doanh thu F&B.`,
                type: "info"
            });
        }
    }
    
    // Đề xuất về phim
    if (movies && movies.length > 0) {
        // Tìm phim có doanh thu cao nhất
        const bestMovie = movies[0];
        
        if (bestMovie) {
            recommendations.push({
                title: "Phim có doanh thu cao",
                content: `"${bestMovie.name}" đang là phim có doanh thu cao nhất (${formatCurrency(bestMovie.revenue)}). Nên tăng số lượng suất chiếu và quảng bá mạnh hơn.`,
                type: "success"
            });
        }
    }
    
    // Nếu không có đề xuất nào
    if (recommendations.length === 0) {
        recommendations.push({
            title: "Chưa có đủ dữ liệu để đưa ra đề xuất",
            content: "Hãy chọn khoảng thời gian khác hoặc đợi có thêm dữ liệu để nhận đề xuất cụ thể hơn.",
            type: "info"
        });
    }
    
    return recommendations;
}

// Dữ liệu mẫu dự phòng khi API không hoạt động
function generateSampleDataFallback() {
    return {
        overview: {
            totalRevenue: 340000,
            revenueTrend: 0,
            totalCustomers: 4,
            customerTrend: 0,
            occupancyRate: 0.6,
            occupancyTrend: 0,
            foodPerCustomer: 7500,
            foodTrend: 0
        },
        revenueByDate: [
            { date: '22/09', total: 50000, ticket: 40000, food: 10000 },
            { date: '23/09', total: 45000, ticket: 35000, food: 10000 },
            { date: '24/09', total: 60000, ticket: 50000, food: 10000 },
            { date: '25/09', total: 55000, ticket: 45000, food: 10000 },
            { date: '26/09', total: 40000, ticket: 30000, food: 10000 },
            { date: '27/09', total: 50000, ticket: 40000, food: 10000 },
            { date: '28/09', total: 40000, ticket: 30000, food: 10000 }
        ],
        revenueDistribution: [
            { name: 'Vé phim', value: 80 },
            { name: 'Đồ ăn & Đồ uống', value: 20 }
        ],
        movies: [
            { name: 'Đứa Con Của Thời Tiết', revenue: 310000, tickets: 3, contribution: 91, trend: 0 },
        ],
        foods: [
            { name: 'Bắp rang bơ (lớn)', revenue: 30000, quantity: 3, contribution: 100, trend: 0 },
        ],
        showtimes: [
            { time: '10:00 - 12:00', occupancy: 45, revenue: 85000, contribution: 25, trend: 0 },
            { time: '12:00 - 14:00', occupancy: 62, revenue: 120000, contribution: 35, trend: 0 },
            { time: '14:00 - 16:00', occupancy: 58, revenue: 110000, contribution: 32, trend: 0 },
            { time: '18:00 - 20:00', occupancy: 25, revenue: 25000, contribution: 8, trend: 0 },
        ],
        customerTrends: [
            { date: '22/09', total: 1, weekend: 0, weekday: 1 },
            { date: '23/09', total: 0, weekend: 0, weekday: 0 },
            { date: '24/09', total: 2, weekend: 0, weekday: 2 },
            { date: '25/09', total: 0, weekend: 0, weekday: 0 },
            { date: '26/09', total: 0, weekend: 0, weekday: 0 },
            { date: '27/09', total: 0, weekend: 0, weekday: 0 },
            { date: '28/09', total: 1, weekend: 1, weekday: 0 }
        ],
        recommendations: [
            {
                title: "Đề xuất dựa trên dữ liệu hiện có",
                content: "Nên tập trung vào các suất chiếu từ 10:00 - 14:00 vì có tỷ lệ lấp đầy cao nhất.",
                type: "success"
            },
            {
                title: "Doanh thu đồ ăn",
                content: "Bắp rang bơ là sản phẩm bán chạy nhất. Nên tạo thêm các combo kết hợp với sản phẩm này.",
                type: "info"
            }
        ]
    };
}

// Hàm cập nhật date range filter dựa trên toggle type
function updateDateRangeFilter(isSuatChieu) {
    const dateRangeSelect = document.getElementById('date-range');
    if (!dateRangeSelect) return;
    
    const currentValue = dateRangeSelect.value;
    dateRangeSelect.innerHTML = '';
    
    if (isSuatChieu) {
        // For showtime: include future dates
        dateRangeSelect.setAttribute('data-filter-type', 'suat-chieu');
        dateRangeSelect.innerHTML = `
            <option value="7">7 ngày qua</option>
            <option value="30">30 ngày qua</option>
            <option value="90">90 ngày qua</option>
            <option value="365">365 ngày qua</option>
            <option value="7f" selected>7 ngày tới</option>
            <option value="30f">30 ngày tới</option>
            <option value="90f">90 ngày tới</option>
            <option value="365f">365 ngày tới</option>
            <option value="custom">Tùy chỉnh</option>
        `;
        const startDateInput = document.getElementById('start-date');
        const endDateInput = document.getElementById('end-date');
        if (startDateInput) startDateInput.removeAttribute('max');
        if (endDateInput) endDateInput.removeAttribute('max');
    } else {
        // For orders: only past dates
        dateRangeSelect.setAttribute('data-filter-type', 'don-hang');
        dateRangeSelect.innerHTML = `
            <option value="7" selected>7 ngày qua</option>
            <option value="30">30 ngày qua</option>
            <option value="90">90 ngày qua</option>
            <option value="365">365 ngày qua</option>
            <option value="custom">Tùy chỉnh</option>
        `;
        const todayStr = new Date().toISOString().split('T')[0];
        const startDateInput = document.getElementById('start-date');
        const endDateInput = document.getElementById('end-date');
        if (startDateInput) startDateInput.setAttribute('max', todayStr);
        if (endDateInput) endDateInput.setAttribute('max', todayStr);
    }
    
    dateRangeSelect.value = dateRangeSelect.querySelector('option[selected]')?.value || dateRangeSelect.options[0].value;
    
    if (dateRangeSelect.value !== currentValue || currentValue === '') {
        dateRangeSelect.dispatchEvent(new Event('change'));
    }
}

// Thay thế hàm fetchData hiện tại với phiên bản được cải tiến - sử dụng 1 API duy nhất
async function fetchData(dateRange, params = {}) {
    console.log('Fetching data for:', dateRange, params);
    
    try {
        // Xác định ngày bắt đầu và kết thúc dựa trên dateRange
        let startDate, endDate;
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];
        
        if (dateRange === 'custom' && params.startDate && params.endDate) {
            startDate = params.startDate;
            endDate = params.endDate;
        } else {
            const isFuture = typeof dateRange === 'string' && dateRange.endsWith('f');
            const days = parseInt(dateRange.replace('f', '') || dateRange);
            
            if (isFuture) {
                // Future dates: start from today, end in future
                startDate = todayStr;
                const endDateObj = new Date(today);
                endDateObj.setDate(endDateObj.getDate() + days);
                endDate = endDateObj.toISOString().split('T')[0];
            } else {
                // Past dates: start from past, end today
                endDate = todayStr;
                const startDateObj = new Date(today);
                startDateObj.setDate(startDateObj.getDate() - days);
                startDate = startDateObj.toISOString().split('T')[0];
            }
        }

        // Cập nhật date inputs
        const startDateInput = document.getElementById('start-date');
        const endDateInput = document.getElementById('end-date');
        if (startDateInput) startDateInput.value = startDate;
        if (endDateInput) endDateInput.value = endDate;

        // Gọi hàm updateAllData để fetch và xử lý dữ liệu
        await updateAllData(true);
    } catch (error) {
        console.error('Error in fetchData:', error);
    }
}

// Hàm chính để cập nhật tất cả dữ liệu - sử dụng API dữ liệu thô duy nhất
async function updateAllData(needFetch = true) {
    try {
        const urlBase = document.getElementById('thong-ke-app').dataset.url;
        const startDateInput = document.getElementById('start-date');
        const endDateInput = document.getElementById('end-date');
        
        const tuNgay = startDateInput ? startDateInput.value : new Date().toISOString().split('T')[0];
        const denNgay = endDateInput ? endDateInput.value : new Date().toISOString().split('T')[0];
        
        if (!tuNgay || !denNgay) {
            console.error('Missing date range');
            return;
        }
        
        let rawData = null;
        
        if (needFetch) {
            // Build API URL cho API dữ liệu thô
            const params = new URLSearchParams({
                tuNgay: tuNgay,
                denNgay: denNgay
            });
            const apiUrl = `${urlBase}/api/thong-ke/du-lieu-tho?${params.toString()}`;
            
            console.log('Fetching raw data from:', apiUrl);
            
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success && result.data) {
                rawData = result.data;
                cachedRawData = rawData;
                cachedFilters = {
                    tuNgay: tuNgay,
                    denNgay: denNgay
                };
            } else {
                throw new Error(result.message || 'Không thể tải dữ liệu');
            }
        } else {
            rawData = cachedRawData;
            if (!rawData) {
                return updateAllData(true);
            }
        }
        
        // Xử lý và cập nhật UI
        xuLyVaCapNhatUI(rawData);
        
        // Fetch và hiển thị thống kê doanh thu theo suất chiếu
        await capNhatThongKeDoanhThuTheoSuatChieu(tuNgay, denNgay);
    } catch (error) {
        console.error('Error updating all data:', error);
        alert('Lỗi khi tải dữ liệu: ' + error.message);
    }
}

/**
 * Xử lý dữ liệu thô và cập nhật UI
 */
function xuLyVaCapNhatUI(rawData) {
    // Xử lý và tổng hợp dữ liệu thô thành các format cần thiết
    const processedData = xuLyDuLieuTho(rawData, false, 'daily');
    
    // Cập nhật UI với dữ liệu đã xử lý
    capNhatUI(processedData);
}

/**
 * Xử lý dữ liệu thô từ API và tổng hợp thành các format cần thiết
 */
function xuLyDuLieuTho(rawData, soSanhVoiKyTruoc, loaiXuHuong) {
    const { du_lieu_ve, du_lieu_ve_ky_truoc, du_lieu_don_hang, du_lieu_chi_tiet_don_hang, 
            du_lieu_mua_phim, du_lieu_suat_chieu, du_lieu_rap_phim, thoi_gian } = rawData;
    
    return {
        // 1. Tổng quan KPI
        tong_quat: tinhTongQuat(du_lieu_ve, du_lieu_ve_ky_truoc, du_lieu_chi_tiet_don_hang, 
                                du_lieu_suat_chieu, soSanhVoiKyTruoc),
        
        // 2. Xu hướng doanh thu
        xu_huong_doanh_thu: tinhXuHuongDoanhThu(du_lieu_ve, du_lieu_chi_tiet_don_hang, 
                                                  thoi_gian, loaiXuHuong),
        
        // 3. Xu hướng vé bán
        xu_huong_ve_ban: tinhXuHuongVeBan(du_lieu_ve, thoi_gian, loaiXuHuong),
        
        // 4. Top 10 phim
        top_10_phim: tinhTop10Phim(du_lieu_ve),
        
        // 5. Top 10 sản phẩm F&B bán chạy
        top_10_san_pham_ban_chay: tinhTop10SanPhamBanChay(du_lieu_chi_tiet_don_hang),
        
        // 6. Hiệu suất theo rạp
        hieu_suat_theo_rap: tinhHieuSuatTheoRap(du_lieu_ve, du_lieu_chi_tiet_don_hang, 
                                                 du_lieu_rap_phim),
        
        // 7. Cơ cấu doanh thu
        co_cau_doanh_thu: tinhCoCauDoanhThu(du_lieu_ve, du_lieu_chi_tiet_don_hang),
        
        // 8. Tỉ lệ F&B trên đơn hàng
        ti_le_fnb_tren_don_hang: tinhTiLeFnBTrenDonHang(du_lieu_chi_tiet_don_hang, 
                                                         du_lieu_don_hang, thoi_gian, loaiXuHuong),
        
        // 9. Doanh thu phim (tất cả phim)
        doanh_thu_phim: tinhDoanhThuPhim(du_lieu_ve, du_lieu_mua_phim),
        
        // 10. Hiệu quả theo khung giờ chiếu
        hieu_qua_theo_khung_gio: tinhHieuQuaTheoKhungGio(du_lieu_suat_chieu, du_lieu_ve, du_lieu_chi_tiet_don_hang, du_lieu_don_hang)
    };
}

/**
 * Tính tổng quan KPI
 */
function tinhTongQuat(duLieuVe, duLieuVeKyTruoc, duLieuChiTietDonHang, duLieuSuatChieu, soSanh) {
    const tongDoanhThuVe = duLieuVe.reduce((sum, ve) => sum + ve.gia_ve, 0);
    const tongDoanhThuFnB = duLieuChiTietDonHang.reduce((sum, item) => 
        sum + (item.thanh_tien || (item.so_luong * item.gia_ban)), 0);
    const tongDoanhThu = tongDoanhThuVe + tongDoanhThuFnB;
    const tongVeBan = duLieuVe.length;
    const tongGhe = duLieuSuatChieu.reduce((sum, sc) => sum + sc.so_ghe, 0);
    const tyLeLapDay = tongGhe > 0 ? (tongVeBan / tongGhe) * 100 : 0;
    
    let soSanhData = null;
    if (soSanh && duLieuVeKyTruoc) {
        const tongDoanhThuVeKyTruoc = duLieuVeKyTruoc.reduce((sum, ve) => sum + ve.gia_ve, 0);
        const tongVeBanKyTruoc = duLieuVeKyTruoc.length;
        const phanTramThayDoiDoanhThu = tongDoanhThuVeKyTruoc > 0 
            ? ((tongDoanhThu - tongDoanhThuVeKyTruoc) / tongDoanhThuVeKyTruoc) * 100 : 0;
        const phanTramThayDoiVeBan = tongVeBanKyTruoc > 0 
            ? ((tongVeBan - tongVeBanKyTruoc) / tongVeBanKyTruoc) * 100 : 0;
        
        soSanhData = {
            doanh_thu_phan_tram_thay_doi: phanTramThayDoiDoanhThu,
            ve_phan_tram_thay_doi: phanTramThayDoiVeBan,
            ty_le_lap_day_phan_tram_thay_doi: 0,
            fnb_phan_tram_thay_doi: 0
        };
    }
    
    return {
        tong_doanh_thu: Math.round(tongDoanhThu),
        tong_ve_ban: tongVeBan,
        ty_le_lap_day: Math.round(tyLeLapDay * 100) / 100,
        doanh_thu_fnb: Math.round(tongDoanhThuFnB),
        so_sanh: soSanhData
    };
}

/**
 * Tính xu hướng doanh thu với chi tiết vé và F&B
 */
function tinhXuHuongDoanhThuChiTiet(rawData, danhSachNhan) {
    if (!rawData) {
        return danhSachNhan.map(() => ({ doanh_thu_ve: 0, doanh_thu_fnb: 0, tong_doanh_thu: 0 }));
    }
    
    const { du_lieu_ve, du_lieu_chi_tiet_don_hang, thoi_gian } = rawData;
    const dataByDate = {};
    
    du_lieu_ve.forEach(ve => {
        const ngayTao = ve.ngay_tao ? new Date(ve.ngay_tao) : null;
        if (ngayTao && !isNaN(ngayTao.getTime())) {
            const ngay = ngayTao.toISOString().split('T')[0];
            if (!dataByDate[ngay]) {
                dataByDate[ngay] = { doanh_thu_ve: 0, doanh_thu_fnb: 0 };
            }
            dataByDate[ngay].doanh_thu_ve += ve.gia_ve || 0;
        }
    });
    
    du_lieu_chi_tiet_don_hang.forEach(item => {
        const ngayDat = item.ngay_dat ? new Date(item.ngay_dat) : null;
        if (ngayDat && !isNaN(ngayDat.getTime())) {
            const ngay = ngayDat.toISOString().split('T')[0];
            if (!dataByDate[ngay]) {
                dataByDate[ngay] = { doanh_thu_ve: 0, doanh_thu_fnb: 0 };
            }
            dataByDate[ngay].doanh_thu_fnb += item.thanh_tien || (item.so_luong * item.gia_ban) || 0;
        }
    });
    
    // Tính theo từng ngày trong khoảng thời gian
    const tuNgay = new Date(thoi_gian.tu_ngay + 'T00:00:00');
    const denNgay = new Date(thoi_gian.den_ngay + 'T23:59:59');
    const result = [];
    const currentDate = new Date(tuNgay);
    let index = 0;
    
    while (currentDate <= denNgay && index < danhSachNhan.length) {
        const ngayStr = currentDate.toISOString().split('T')[0];
        const data = dataByDate[ngayStr] || { doanh_thu_ve: 0, doanh_thu_fnb: 0 };
        result.push({
            doanh_thu_ve: data.doanh_thu_ve,
            doanh_thu_fnb: data.doanh_thu_fnb,
            tong_doanh_thu: data.doanh_thu_ve + data.doanh_thu_fnb
        });
        currentDate.setDate(currentDate.getDate() + 1);
        index++;
    }
    
    // Nếu thiếu, thêm các giá trị 0
    while (result.length < danhSachNhan.length) {
        result.push({ doanh_thu_ve: 0, doanh_thu_fnb: 0, tong_doanh_thu: 0 });
    }
    
    return result;
}

/**
 * Tính xu hướng doanh thu
 */
function tinhXuHuongDoanhThu(duLieuVe, duLieuChiTietDonHang, thoiGian, loaiXuHuong) {
    const tuNgay = new Date(thoiGian.tu_ngay + 'T00:00:00');
    const denNgay = new Date(thoiGian.den_ngay + 'T23:59:59');
    const dataByDate = {};
    
    duLieuVe.forEach(ve => {
        const ngayTao = ve.ngay_tao ? new Date(ve.ngay_tao) : null;
        if (ngayTao && !isNaN(ngayTao.getTime())) {
            const ngay = ngayTao.toISOString().split('T')[0];
            if (!dataByDate[ngay]) {
                dataByDate[ngay] = { doanh_thu_ve: 0, doanh_thu_fnb: 0 };
            }
            dataByDate[ngay].doanh_thu_ve += ve.gia_ve || 0;
        }
    });
    
    duLieuChiTietDonHang.forEach(item => {
        const ngayDat = item.ngay_dat ? new Date(item.ngay_dat) : null;
        if (ngayDat && !isNaN(ngayDat.getTime())) {
            const ngay = ngayDat.toISOString().split('T')[0];
            if (!dataByDate[ngay]) {
                dataByDate[ngay] = { doanh_thu_ve: 0, doanh_thu_fnb: 0 };
            }
            dataByDate[ngay].doanh_thu_fnb += item.thanh_tien || (item.so_luong * item.gia_ban) || 0;
        }
    });
    
    let danhSachNhan = [];
    let chiTiet = [];
    
    if (loaiXuHuong === 'daily') {
        const currentDate = new Date(tuNgay);
        while (currentDate <= denNgay) {
            const ngayStr = currentDate.toISOString().split('T')[0];
            const data = dataByDate[ngayStr] || { doanh_thu_ve: 0, doanh_thu_fnb: 0 };
            const tongDoanhThu = data.doanh_thu_ve + data.doanh_thu_fnb;
            
            danhSachNhan.push(currentDate.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }));
            chiTiet.push({
                ngay: ngayStr,
                tong_doanh_thu: tongDoanhThu
            });
            
            currentDate.setDate(currentDate.getDate() + 1);
        }
    } else if (loaiXuHuong === 'weekly') {
        const currentDate = new Date(tuNgay);
        let weekNumber = 1;
        while (currentDate <= denNgay) {
            let weekEnd = new Date(currentDate);
            weekEnd.setDate(weekEnd.getDate() + 6);
            if (weekEnd > denNgay) weekEnd = new Date(denNgay);
            
            let tongDoanhThu = 0;
            const tempDate = new Date(currentDate);
            while (tempDate <= weekEnd) {
                const ngayStr = tempDate.toISOString().split('T')[0];
                const data = dataByDate[ngayStr] || { doanh_thu_ve: 0, doanh_thu_fnb: 0 };
                tongDoanhThu += data.doanh_thu_ve + data.doanh_thu_fnb;
                tempDate.setDate(tempDate.getDate() + 1);
            }
            
            danhSachNhan.push('Tuần ' + weekNumber);
            chiTiet.push({ tong_doanh_thu: tongDoanhThu });
            
            currentDate.setDate(currentDate.getDate() + 7);
            weekNumber++;
        }
    } else {
        // Monthly
        const currentDate = new Date(tuNgay);
        while (currentDate <= denNgay) {
            const monthStart = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const monthEnd = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
            const actualStart = monthStart < tuNgay ? new Date(tuNgay) : monthStart;
            const actualEnd = monthEnd > denNgay ? new Date(denNgay) : monthEnd;
            
            let tongDoanhThu = 0;
            const tempDate = new Date(actualStart);
            while (tempDate <= actualEnd) {
                const ngayStr = tempDate.toISOString().split('T')[0];
                const data = dataByDate[ngayStr] || { doanh_thu_ve: 0, doanh_thu_fnb: 0 };
                tongDoanhThu += data.doanh_thu_ve + data.doanh_thu_fnb;
                tempDate.setDate(tempDate.getDate() + 1);
            }
            
            danhSachNhan.push(currentDate.toLocaleDateString('vi-VN', { month: '2-digit', year: 'numeric' }));
            chiTiet.push({ tong_doanh_thu: tongDoanhThu });
            
            currentDate.setMonth(currentDate.getMonth() + 1);
            currentDate.setDate(1);
        }
    }
    
    return {
        danh_sach_nhan: danhSachNhan,
        chi_tiet: chiTiet
    };
}

/**
 * Tính xu hướng vé bán
 */
function tinhXuHuongVeBan(duLieuVe, thoiGian, loaiXuHuong) {
    const tuNgay = new Date(thoiGian.tu_ngay + 'T00:00:00');
    const denNgay = new Date(thoiGian.den_ngay + 'T23:59:59');
    const dataByDate = {};
    
    duLieuVe.forEach(ve => {
        const ngayTao = ve.ngay_tao ? new Date(ve.ngay_tao) : null;
        if (ngayTao && !isNaN(ngayTao.getTime())) {
            const ngay = ngayTao.toISOString().split('T')[0];
            dataByDate[ngay] = (dataByDate[ngay] || 0) + 1;
        }
    });
    
    let danhSachNhan = [];
    let chiTiet = [];
    
    if (loaiXuHuong === 'daily') {
        const currentDate = new Date(tuNgay);
        while (currentDate <= denNgay) {
            const ngayStr = currentDate.toISOString().split('T')[0];
            danhSachNhan.push(currentDate.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }));
            chiTiet.push({
                so_ve_ban: dataByDate[ngayStr] || 0
            });
            currentDate.setDate(currentDate.getDate() + 1);
        }
    } else if (loaiXuHuong === 'weekly') {
        const currentDate = new Date(tuNgay);
        let weekNumber = 1;
        while (currentDate <= denNgay) {
            let weekEnd = new Date(currentDate);
            weekEnd.setDate(weekEnd.getDate() + 6);
            if (weekEnd > denNgay) weekEnd = new Date(denNgay);
            
            let tongVeBan = 0;
            const tempDate = new Date(currentDate);
            while (tempDate <= weekEnd) {
                const ngayStr = tempDate.toISOString().split('T')[0];
                tongVeBan += dataByDate[ngayStr] || 0;
                tempDate.setDate(tempDate.getDate() + 1);
            }
            
            danhSachNhan.push('Tuần ' + weekNumber);
            chiTiet.push({ so_ve_ban: tongVeBan });
            
            currentDate.setDate(currentDate.getDate() + 7);
            weekNumber++;
        }
    } else {
        // Monthly
        const currentDate = new Date(tuNgay);
        while (currentDate <= denNgay) {
            const monthStart = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const monthEnd = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
            const actualStart = monthStart < tuNgay ? new Date(tuNgay) : monthStart;
            const actualEnd = monthEnd > denNgay ? new Date(denNgay) : monthEnd;
            
            let tongVeBan = 0;
            const tempDate = new Date(actualStart);
            while (tempDate <= actualEnd) {
                const ngayStr = tempDate.toISOString().split('T')[0];
                tongVeBan += dataByDate[ngayStr] || 0;
                tempDate.setDate(tempDate.getDate() + 1);
            }
            
            danhSachNhan.push(currentDate.toLocaleDateString('vi-VN', { month: '2-digit', year: 'numeric' }));
            chiTiet.push({ so_ve_ban: tongVeBan });
            
            currentDate.setMonth(currentDate.getMonth() + 1);
            currentDate.setDate(1);
        }
    }
    
    return {
        danh_sach_nhan: danhSachNhan,
        chi_tiet: chiTiet
    };
}

/**
 * Tính Top 10 phim
 */
function tinhTop10Phim(duLieuVe) {
    const phimMap = {};
    
    duLieuVe.forEach(ve => {
        if (!ve.id_phim) return;
        if (!phimMap[ve.id_phim]) {
            phimMap[ve.id_phim] = {
                id_phim: ve.id_phim,
                ten_phim: ve.ten_phim,
                poster_url: ve.poster_url,
                doanh_thu: 0,
                so_ve_ban: 0
            };
        }
        phimMap[ve.id_phim].doanh_thu += ve.gia_ve;
        phimMap[ve.id_phim].so_ve_ban += 1;
    });
    
    const danhSach = Object.values(phimMap)
        .sort((a, b) => b.doanh_thu - a.doanh_thu)
        .slice(0, 10);
    
    return { danh_sach: danhSach };
}

/**
 * Tính Top 10 sản phẩm F&B bán chạy
 */
function tinhTop10SanPhamBanChay(duLieuChiTietDonHang) {
    const sanPhamMap = {};
    
    duLieuChiTietDonHang.forEach(item => {
        if (!item.id_sanpham) return;
        if (!sanPhamMap[item.id_sanpham]) {
            sanPhamMap[item.id_sanpham] = {
                id_san_pham: item.id_sanpham,
                ten_san_pham: item.ten_san_pham,
                so_luong: 0,
                doanh_thu: 0
            };
        }
        sanPhamMap[item.id_sanpham].so_luong += item.so_luong;
        sanPhamMap[item.id_sanpham].doanh_thu += item.thanh_tien || (item.so_luong * item.gia_ban);
    });
    
    const danhSach = Object.values(sanPhamMap)
        .sort((a, b) => b.so_luong - a.so_luong)
        .slice(0, 10);
    
    return { danh_sach: danhSach };
}

/**
 * Tính hiệu suất theo rạp
 */
function tinhHieuSuatTheoRap(duLieuVe, duLieuChiTietDonHang, duLieuRapPhim) {
    const rapMap = {};
    
    duLieuRapPhim.forEach(rap => {
        rapMap[rap.id] = {
            id_rap: rap.id,
            ten_rap: rap.ten,
            doanh_thu: 0
        };
    });
    
    duLieuVe.forEach(ve => {
        if (ve.id_rapphim && rapMap[ve.id_rapphim]) {
            rapMap[ve.id_rapphim].doanh_thu += ve.gia_ve;
        }
    });
    
    duLieuChiTietDonHang.forEach(item => {
        if (item.id_rapphim && rapMap[item.id_rapphim]) {
            rapMap[item.id_rapphim].doanh_thu += item.thanh_tien || (item.so_luong * item.gia_ban);
        }
    });
    
    const danhSachRap = Object.values(rapMap)
        .sort((a, b) => b.doanh_thu - a.doanh_thu);
    
    return { danh_sach_rap: danhSachRap };
}

/**
 * Tính cơ cấu doanh thu
 */
function tinhCoCauDoanhThu(duLieuVe, duLieuChiTietDonHang) {
    const doanhThuVe = duLieuVe.reduce((sum, ve) => sum + ve.gia_ve, 0);
    const doanhThuFnB = duLieuChiTietDonHang.reduce((sum, item) => 
        sum + (item.thanh_tien || (item.so_luong * item.gia_ban)), 0);
    const tongDoanhThu = doanhThuVe + doanhThuFnB;
    
    return {
        chi_tiet: [
            {
                loai: 'Vé phim',
                phan_tram: tongDoanhThu > 0 ? (doanhThuVe / tongDoanhThu) * 100 : 0,
                mau_sac: '#EF4444'
            },
            {
                loai: 'F&B',
                phan_tram: tongDoanhThu > 0 ? (doanhThuFnB / tongDoanhThu) * 100 : 0,
                mau_sac: '#F59E0B'
            }
        ]
    };
}

/**
 * Tính tỉ lệ F&B trên đơn hàng
 */
function tinhTiLeFnBTrenDonHang(duLieuChiTietDonHang, duLieuDonHang, thoiGian, loaiXuHuong) {
    const dataByDate = {};
    
    duLieuChiTietDonHang.forEach(item => {
        const ngay = item.ngay_dat ? new Date(item.ngay_dat).toISOString().split('T')[0] : null;
        if (ngay) {
            if (!dataByDate[ngay]) {
                dataByDate[ngay] = { tong_fnb: 0, so_don_hang: 0 };
            }
            dataByDate[ngay].tong_fnb += item.thanh_tien || (item.so_luong * item.gia_ban);
        }
    });
    
    const donHangByDate = {};
    duLieuDonHang.forEach(dh => {
        const ngay = dh.ngay_dat ? new Date(dh.ngay_dat).toISOString().split('T')[0] : null;
        if (ngay) {
            donHangByDate[ngay] = (donHangByDate[ngay] || 0) + 1;
        }
    });
    
    const danhSach = [];
    const tuNgay = new Date(thoiGian.tu_ngay);
    const denNgay = new Date(thoiGian.den_ngay);
    const currentDate = new Date(tuNgay);
    
    while (currentDate <= denNgay) {
        const ngayStr = currentDate.toISOString().split('T')[0];
        const data = dataByDate[ngayStr] || { tong_fnb: 0 };
        const soDonHang = donHangByDate[ngayStr] || 0;
        const trungBinh = soDonHang > 0 ? data.tong_fnb / soDonHang : 0;
        
        danhSach.push({
            ngay: currentDate.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }),
            trung_binh_fnb_tren_don_hang: trungBinh
        });
        
        currentDate.setDate(currentDate.getDate() + 1);
    }
    
    return { danh_sach: danhSach };
}

/**
 * Tính hiệu quả theo khung giờ chiếu
 */
function tinhHieuQuaTheoKhungGio(duLieuSuatChieu, duLieuVe, duLieuChiTietDonHang, duLieuDonHang) {
    if (!duLieuSuatChieu || duLieuSuatChieu.length === 0) {
        return [];
    }
    
    // Định nghĩa các khung giờ
    const khungGio = [
        { batDau: 0, ketThuc: 6, ten: '00:00 - 06:00' },
        { batDau: 6, ketThuc: 10, ten: '06:00 - 10:00' },
        { batDau: 10, ketThuc: 12, ten: '10:00 - 12:00' },
        { batDau: 12, ketThuc: 14, ten: '12:00 - 14:00' },
        { batDau: 14, ketThuc: 16, ten: '14:00 - 16:00' },
        { batDau: 16, ketThuc: 18, ten: '16:00 - 18:00' },
        { batDau: 18, ketThuc: 20, ten: '18:00 - 20:00' },
        { batDau: 20, ketThuc: 22, ten: '20:00 - 22:00' },
        { batDau: 22, ketThuc: 24, ten: '22:00 - 24:00' }
    ];
    
    // Tạo map để lưu dữ liệu theo suất chiếu
    const suatChieuMap = {};
    duLieuSuatChieu.forEach(sc => {
        suatChieuMap[sc.id] = {
            id: sc.id,
            batdau: sc.batdau,
            so_ghe: sc.so_ghe || 0,
            so_ve_ban: 0,
            doanh_thu_ve: 0,
            doanh_thu_fnb: 0
        };
    });
    
    // Tính số vé bán và doanh thu vé cho mỗi suất chiếu
    duLieuVe.forEach(ve => {
        if (ve.suat_chieu_id && suatChieuMap[ve.suat_chieu_id]) {
            suatChieuMap[ve.suat_chieu_id].so_ve_ban += 1;
            suatChieuMap[ve.suat_chieu_id].doanh_thu_ve += ve.gia_ve || 0;
        }
    });
    
    // Tính doanh thu F&B cho mỗi suất chiếu (thông qua đơn hàng)
    // Tạo map từ đơn hàng ID đến suất chiếu ID
    const donHangToSuatChieu = {};
    if (duLieuDonHang) {
        duLieuDonHang.forEach(dh => {
            if (dh.suat_chieu_id) {
                donHangToSuatChieu[dh.id] = dh.suat_chieu_id;
            }
        });
    }
    
    // Tính doanh thu F&B theo suất chiếu
    if (duLieuChiTietDonHang) {
        duLieuChiTietDonHang.forEach(item => {
            const suatChieuId = donHangToSuatChieu[item.id_donhang];
            if (suatChieuId && suatChieuMap[suatChieuId]) {
                suatChieuMap[suatChieuId].doanh_thu_fnb += item.thanh_tien || (item.so_luong * item.gia_ban) || 0;
            }
        });
    }
    
    // Nhóm theo khung giờ
    const ketQua = khungGio.map(khung => {
        let tongGhe = 0;
        let tongVeBan = 0;
        let tongDoanhThuVe = 0;
        let tongDoanhThuFnB = 0;
        
        Object.values(suatChieuMap).forEach(sc => {
            if (!sc.batdau) return;
            
            try {
                const batDauDate = new Date(sc.batdau);
                if (isNaN(batDauDate.getTime())) {
                    console.warn('Invalid date:', sc.batdau);
                    return;
                }
                
                const gio = batDauDate.getHours();
                
                if (gio >= khung.batDau && gio < khung.ketThuc) {
                    tongGhe += sc.so_ghe;
                    tongVeBan += sc.so_ve_ban;
                    tongDoanhThuVe += sc.doanh_thu_ve;
                    tongDoanhThuFnB += sc.doanh_thu_fnb;
                }
            } catch (error) {
                console.warn('Error parsing date:', sc.batdau, error);
            }
        });
        
        const tyLeLapDay = tongGhe > 0 ? (tongVeBan / tongGhe) * 100 : 0;
        const tongDoanhThu = tongDoanhThuVe + tongDoanhThuFnB;
        
        return {
            time: khung.ten,
            occupancy: Math.round(tyLeLapDay * 100) / 100,
            revenue: Math.round(tongDoanhThu),
            contribution: 0, // Có thể tính nếu cần
            trend: 0
        };
    }); // Hiển thị tất cả khung giờ, kể cả khi không có dữ liệu
    
    return ketQua;
}

/**
 * Tính doanh thu phim (tất cả phim)
 */
function tinhDoanhThuPhim(duLieuVe, duLieuMuaPhim) {
    const phimMap = {};
    
    duLieuVe.forEach(ve => {
        if (!ve.id_phim) return;
        if (!phimMap[ve.id_phim]) {
            phimMap[ve.id_phim] = {
                id: ve.id_phim,
                ten_phim: ve.ten_phim,
                poster_url: ve.poster_url,
                doanh_thu_ve: 0,
                doanh_thu_mua: 0
            };
        }
        phimMap[ve.id_phim].doanh_thu_ve += ve.gia_ve;
    });
    
    duLieuMuaPhim.forEach(mp => {
        if (mp.id_phim && phimMap[mp.id_phim]) {
            phimMap[mp.id_phim].doanh_thu_mua += mp.so_tien;
        }
    });
    
    const danhSach = Object.values(phimMap).map(phim => ({
        ...phim,
        tong_doanh_thu: phim.doanh_thu_ve + phim.doanh_thu_mua
    })).sort((a, b) => b.tong_doanh_thu - a.tong_doanh_thu);
    
    return { danh_sach: danhSach };
}

/**
 * Cập nhật UI với dữ liệu đã xử lý
 */
function capNhatUI(processedData) {
    // Cập nhật KPI cards
    const kpi = processedData.tong_quat;
    const totalRevenueEl = document.getElementById('total-revenue');
    const totalCustomersEl = document.getElementById('total-customers');
    const occupancyRateEl = document.getElementById('occupancy-rate');
    const foodPerCustomerEl = document.getElementById('food-per-customer');
    
    if (totalRevenueEl) totalRevenueEl.textContent = formatCurrency(kpi.tong_doanh_thu);
    if (totalCustomersEl) totalCustomersEl.textContent = formatNumber(kpi.tong_ve_ban);
    if (occupancyRateEl) occupancyRateEl.textContent = formatPercent(kpi.ty_le_lap_day);
    if (foodPerCustomerEl) foodPerCustomerEl.textContent = formatCurrency(kpi.doanh_thu_fnb);
    
    // Cập nhật trends
    if (kpi.so_sanh) {
        const revenueTrendEl = document.getElementById('revenue-trend');
        const customerTrendEl = document.getElementById('customer-trend');
        const occupancyTrendEl = document.getElementById('occupancy-trend');
        const foodTrendEl = document.getElementById('food-trend');
        
        if (revenueTrendEl) revenueTrendEl.innerHTML = formatTrend(kpi.so_sanh.doanh_thu_phan_tram_thay_doi);
        if (customerTrendEl) customerTrendEl.innerHTML = formatTrend(kpi.so_sanh.ve_phan_tram_thay_doi);
        if (occupancyTrendEl) occupancyTrendEl.innerHTML = formatTrend(kpi.so_sanh.ty_le_lap_day_phan_tram_thay_doi);
        if (foodTrendEl) foodTrendEl.innerHTML = formatTrend(kpi.so_sanh.fnb_phan_tram_thay_doi);
    }
    
    // Tính lại xu hướng doanh thu với chi tiết vé và F&B
    const xuHuongDoanhThuChiTiet = cachedRawData ? tinhXuHuongDoanhThuChiTiet(cachedRawData, processedData.xu_huong_doanh_thu.danh_sach_nhan) : processedData.xu_huong_doanh_thu.chi_tiet.map(() => ({ doanh_thu_ve: 0, doanh_thu_fnb: 0, tong_doanh_thu: 0 }));
    
    // Chuyển đổi dữ liệu để tương thích với các hàm khởi tạo biểu đồ hiện có
    const chartData = {
        revenueByDate: xuHuongDoanhThuChiTiet.map((item, index) => ({
            date: processedData.xu_huong_doanh_thu.danh_sach_nhan[index],
            total: item.tong_doanh_thu || 0,
            ticket: item.doanh_thu_ve || 0,
            food: item.doanh_thu_fnb || 0
        })),
        revenueDistribution: processedData.co_cau_doanh_thu.chi_tiet.map(item => ({
            name: item.loai,
            value: item.phan_tram
        })),
        movies: processedData.top_10_phim.danh_sach.map(item => ({
            name: item.ten_phim,
            revenue: item.doanh_thu,
            tickets: item.so_ve_ban,
            contribution: 0, // Có thể tính nếu cần
            trend: 0
        })),
        foods: processedData.top_10_san_pham_ban_chay.danh_sach.map(item => ({
            name: item.ten_san_pham,
            revenue: item.doanh_thu,
            quantity: item.so_luong,
            contribution: 0, // Có thể tính nếu cần
            trend: 0
        })),
        showtimes: processedData.hieu_qua_theo_khung_gio || [],
        customerTrends: processedData.xu_huong_ve_ban.chi_tiet.map((item, index) => ({
            date: processedData.xu_huong_ve_ban.danh_sach_nhan[index],
            total: item.so_ve_ban || 0,
            weekend: 0,
            weekday: item.so_ve_ban || 0
        }))
    };
    
    // Khởi tạo/cập nhật các biểu đồ
    initializeCharts(chartData);
    
    // Cập nhật cache dữ liệu cho bảng phân tích
    cachedMoviesData = chartData.movies || [];
    cachedFoodsData = chartData.foods || [];
    cachedShowtimesData = chartData.showtimes || [];
    
    // Cập nhật bảng phân tích với dữ liệu mới
    if (currentAnalysisType === 'movie' && cachedMoviesData.length > 0) {
        updateAnalysisTable(cachedMoviesData, 'movie');
    } else if (currentAnalysisType === 'food' && cachedFoodsData.length > 0) {
        updateAnalysisTable(cachedFoodsData, 'food');
    } else if (currentAnalysisType === 'showtime' && cachedShowtimesData.length > 0) {
        updateAnalysisTable(cachedShowtimesData, 'showtime');
    } else {
        // Nếu không có dữ liệu, hiển thị bảng trống
        const tableBody = document.getElementById('analysis-table-body');
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                        Không có dữ liệu phân tích
                    </td>
                </tr>
            `;
        }
    }
    
    // Tạo và cập nhật đề xuất kinh doanh
    const recommendations = generateRecommendations(
        processedData.tong_quat,
        chartData.movies,
        chartData.foods,
        chartData.showtimes,
        chartData.customerTrends
    );
    updateBusinessRecommendations(recommendations);
    
    // Ẩn spinner cho bảng phân tích và đề xuất
    setTimeout(() => {
        // Ẩn spinner cho bảng phân tích
        const analysisTable = document.getElementById('analysis-table-body');
        if (analysisTable) {
            const spinner = analysisTable.closest('table')?.parentElement?.querySelector('.epic-spinner-container');
            if (spinner) spinner.remove();
        }
        
        // Ẩn spinner cho đề xuất kinh doanh
        const recommendationsContainer = document.getElementById('business-recommendations');
        if (recommendationsContainer) {
            const spinner = recommendationsContainer.querySelector('.epic-spinner-container');
            if (spinner) spinner.remove();
        }
    }, 100);
}

/**
 * Cập nhật thống kê doanh thu theo suất chiếu
 */
async function capNhatThongKeDoanhThuTheoSuatChieu(tuNgay, denNgay) {
    try {
        const urlBase = document.getElementById('thong-ke-app').dataset.url;
        const params = new URLSearchParams({
            tuNgay: tuNgay,
            denNgay: denNgay
        });
        const apiUrl = `${urlBase}/api/thong-ke/doanh-thu-theo-suat-chieu?${params.toString()}`;

        const response = await fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success && result.data) {
            const data = result.data;
            const tableBody = document.getElementById('revenue-by-showtime-body');
            const urlMinio = tableBody ? tableBody.dataset.urlminio || '' : '';
            
            // Cache dữ liệu suất chiếu gốc
            cachedShowtimeData = data.danh_sach || [];
            
            // Cập nhật bảng
            if (tableBody) {
                if (data.danh_sach.length === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500">
                                Không có dữ liệu suất chiếu trong khoảng thời gian này
                            </td>
                        </tr>
                    `;
                } else {
                    tableBody.innerHTML = data.danh_sach.map(item => {
                        const ngayChieu = item.ngay_chieu ? new Date(item.ngay_chieu).toLocaleDateString('vi-VN') : '-';
                        const gioChieu = item.gio_bat_dau && item.gio_ket_thuc 
                            ? `${item.gio_bat_dau} - ${item.gio_ket_thuc}` 
                            : '-';
                        
                        return `
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">#${item.id}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        ${item.poster_url ? `<img src="${urlMinio}/${item.poster_url}" alt="" class="h-10 w-7 object-cover rounded mr-2">` : ''}
                                        <span class="text-sm text-gray-800">${item.ten_phim || '-'}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <div>${item.ten_rap || '-'}</div>
                                    <div class="text-xs text-gray-500">${item.ten_phong_chieu || '-'}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <div>${ngayChieu}</div>
                                    <div class="text-xs text-gray-500">${gioChieu}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-800 text-right">${formatCurrency(item.doanh_thu_ve || 0)}</td>
                                <td class="px-4 py-3 text-sm text-gray-800 text-right">${formatCurrency(item.doanh_thu_fnb || 0)}</td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">${formatCurrency(item.tong_doanh_thu || 0)}</td>
                                <td class="px-4 py-3 text-sm text-gray-800 text-right">${formatNumber(item.so_ve_ban || 0)}</td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${item.ty_le_lap_day >= 80 ? 'bg-green-100 text-green-800' : item.ty_le_lap_day >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'}">
                                        ${formatPercent(item.ty_le_lap_day || 0)}
                                    </span>
                                </td>
                            </tr>
                        `;
                    }).join('');
                }
            }
            
            // Xử lý và cập nhật biểu đồ suất chiếu
            const processedData = xuLyDuLieuSuatChieuChoBieuDo(data.danh_sach, tuNgay, denNgay, currentTimePeriodShowtime);
            const hieuSuatTheoRap = tinhHieuSuatTheoRapTuSuatChieu(data.danh_sach);
            const coCauDoanhThu = tinhCoCauDoanhThuTuSuatChieu(data.danh_sach);
            
            // Cập nhật biểu đồ xu hướng doanh thu
            if (revenueShowtimeChart) {
                revenueShowtimeChart.updateOptions({
                    xaxis: { categories: processedData.xu_huong_doanh_thu.danh_sach_nhan }
                });
                revenueShowtimeChart.updateSeries([{
                    name: 'Doanh thu',
                    data: processedData.xu_huong_doanh_thu.chi_tiet
                }]);
            }
            
            // Cập nhật biểu đồ xu hướng vé bán
            if (ticketsShowtimeChart) {
                ticketsShowtimeChart.updateOptions({
                    xaxis: { categories: processedData.xu_huong_ve_ban.danh_sach_nhan }
                });
                ticketsShowtimeChart.updateSeries([{
                    name: 'Số vé bán',
                    data: processedData.xu_huong_ve_ban.chi_tiet
                }]);
            }
            
            // Cập nhật biểu đồ hiệu suất theo rạp
            if (theaterPerformanceShowtimeChart) {
                if (hieuSuatTheoRap.danh_sach_rap && hieuSuatTheoRap.danh_sach_rap.length > 0) {
                    theaterPerformanceShowtimeChart.updateOptions({
                        xaxis: { categories: hieuSuatTheoRap.danh_sach_rap.map(r => r.ten_rap) },
                        noData: { 
                            text: '',
                            style: { fontSize: '0px', color: 'transparent' }
                        }
                    });
                    theaterPerformanceShowtimeChart.updateSeries([{
                        name: 'Doanh thu',
                        data: hieuSuatTheoRap.danh_sach_rap.map(r => r.doanh_thu)
                    }]);
                } else {
                    theaterPerformanceShowtimeChart.updateOptions({
                        xaxis: { categories: [] },
                        noData: { 
                            text: '',
                            style: { fontSize: '0px', color: 'transparent' }
                        }
                    });
                    theaterPerformanceShowtimeChart.updateSeries([{
                        name: 'Doanh thu',
                        data: []
                    }]);
                }
            }
            
            // Cập nhật biểu đồ cơ cấu doanh thu
            if (revenueBreakdownShowtimeChart) {
                revenueBreakdownShowtimeChart.updateOptions({
                    labels: coCauDoanhThu.chi_tiet.map(item => item.loai),
                    colors: coCauDoanhThu.chi_tiet.map(item => item.mau_sac)
                });
                revenueBreakdownShowtimeChart.updateSeries(coCauDoanhThu.chi_tiet.map(item => item.phan_tram));
            }
        } else {
            throw new Error(result.message || 'Không thể tải dữ liệu thống kê suất chiếu');
        }
    } catch (error) {
        console.error('Error loading revenue by showtime:', error);
        const tableBody = document.getElementById('revenue-by-showtime-body');
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="9" class="px-4 py-6 text-center text-sm text-red-500">
                        Lỗi khi tải dữ liệu: ${error.message}
                    </td>
                </tr>
            `;
        }
    }
}

/**
 * Xử lý dữ liệu suất chiếu để tạo biểu đồ
 */
function xuLyDuLieuSuatChieuChoBieuDo(danhSachSuatChieu, tuNgay, denNgay, loaiXuHuong) {
    const tuNgayDate = new Date(tuNgay + 'T00:00:00');
    const denNgayDate = new Date(denNgay + 'T23:59:59');
    const dataByDate = {};
    
    danhSachSuatChieu.forEach(item => {
        if (!item.ngay_chieu) return;
        const ngay = item.ngay_chieu;
        if (!dataByDate[ngay]) {
            dataByDate[ngay] = { 
                doanh_thu_ve: 0, 
                doanh_thu_fnb: 0, 
                tong_doanh_thu: 0,
                so_ve_ban: 0 
            };
        }
        dataByDate[ngay].doanh_thu_ve += item.doanh_thu_ve || 0;
        dataByDate[ngay].doanh_thu_fnb += item.doanh_thu_fnb || 0;
        dataByDate[ngay].tong_doanh_thu += item.tong_doanh_thu || 0;
        dataByDate[ngay].so_ve_ban += item.so_ve_ban || 0;
    });
    
    let danhSachNhan = [];
    let chiTietDoanhThu = [];
    let chiTietVeBan = [];
    
    if (loaiXuHuong === 'daily') {
        const currentDate = new Date(tuNgayDate);
        while (currentDate <= denNgayDate) {
            const ngayStr = currentDate.toISOString().split('T')[0];
            const data = dataByDate[ngayStr] || { tong_doanh_thu: 0, so_ve_ban: 0 };
            
            danhSachNhan.push(currentDate.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }));
            chiTietDoanhThu.push(data.tong_doanh_thu);
            chiTietVeBan.push(data.so_ve_ban);
            
            currentDate.setDate(currentDate.getDate() + 1);
        }
    } else if (loaiXuHuong === 'weekly') {
        const currentDate = new Date(tuNgayDate);
        let weekNumber = 1;
        while (currentDate <= denNgayDate) {
            let weekEnd = new Date(currentDate);
            weekEnd.setDate(weekEnd.getDate() + 6);
            if (weekEnd > denNgayDate) weekEnd = new Date(denNgayDate);
            
            let tongDoanhThu = 0;
            let tongVeBan = 0;
            const tempDate = new Date(currentDate);
            while (tempDate <= weekEnd) {
                const ngayStr = tempDate.toISOString().split('T')[0];
                const data = dataByDate[ngayStr] || { tong_doanh_thu: 0, so_ve_ban: 0 };
                tongDoanhThu += data.tong_doanh_thu;
                tongVeBan += data.so_ve_ban;
                tempDate.setDate(tempDate.getDate() + 1);
            }
            
            danhSachNhan.push('Tuần ' + weekNumber);
            chiTietDoanhThu.push(tongDoanhThu);
            chiTietVeBan.push(tongVeBan);
            
            currentDate.setDate(currentDate.getDate() + 7);
            weekNumber++;
        }
    } else {
        // Monthly
        const currentDate = new Date(tuNgayDate);
        while (currentDate <= denNgayDate) {
            const monthStart = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const monthEnd = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
            const actualStart = monthStart < tuNgayDate ? new Date(tuNgayDate) : monthStart;
            const actualEnd = monthEnd > denNgayDate ? new Date(denNgayDate) : monthEnd;
            
            let tongDoanhThu = 0;
            let tongVeBan = 0;
            const tempDate = new Date(actualStart);
            while (tempDate <= actualEnd) {
                const ngayStr = tempDate.toISOString().split('T')[0];
                const data = dataByDate[ngayStr] || { tong_doanh_thu: 0, so_ve_ban: 0 };
                tongDoanhThu += data.tong_doanh_thu;
                tongVeBan += data.so_ve_ban;
                tempDate.setDate(tempDate.getDate() + 1);
            }
            
            danhSachNhan.push(currentDate.toLocaleDateString('vi-VN', { month: '2-digit', year: 'numeric' }));
            chiTietDoanhThu.push(tongDoanhThu);
            chiTietVeBan.push(tongVeBan);
            
            currentDate.setMonth(currentDate.getMonth() + 1);
            currentDate.setDate(1);
        }
    }
    
    return {
        xu_huong_doanh_thu: {
            danh_sach_nhan: danhSachNhan,
            chi_tiet: chiTietDoanhThu
        },
        xu_huong_ve_ban: {
            danh_sach_nhan: danhSachNhan,
            chi_tiet: chiTietVeBan
        }
    };
}

/**
 * Tính hiệu suất theo rạp từ dữ liệu suất chiếu
 */
function tinhHieuSuatTheoRapTuSuatChieu(danhSachSuatChieu) {
    const rapMap = {};
    
    danhSachSuatChieu.forEach(item => {
        if (!item.id_rap || !item.ten_rap) return;
        if (!rapMap[item.id_rap]) {
            rapMap[item.id_rap] = {
                id_rap: item.id_rap,
                ten_rap: item.ten_rap,
                doanh_thu: 0
            };
        }
        rapMap[item.id_rap].doanh_thu += item.tong_doanh_thu || 0;
    });
    
    const danhSachRap = Object.values(rapMap)
        .sort((a, b) => b.doanh_thu - a.doanh_thu);
    
    return { danh_sach_rap: danhSachRap };
}

/**
 * Tính cơ cấu doanh thu từ dữ liệu suất chiếu
 */
function tinhCoCauDoanhThuTuSuatChieu(danhSachSuatChieu) {
    const doanhThuVe = danhSachSuatChieu.reduce((sum, item) => sum + (item.doanh_thu_ve || 0), 0);
    const doanhThuFnB = danhSachSuatChieu.reduce((sum, item) => sum + (item.doanh_thu_fnb || 0), 0);
    const tongDoanhThu = doanhThuVe + doanhThuFnB;
    
    return {
        chi_tiet: [
            {
                loai: 'Vé phim',
                phan_tram: tongDoanhThu > 0 ? (doanhThuVe / tongDoanhThu) * 100 : 0,
                mau_sac: '#EF4444'
            },
            {
                loai: 'F&B',
                phan_tram: tongDoanhThu > 0 ? (doanhThuFnB / tongDoanhThu) * 100 : 0,
                mau_sac: '#F59E0B'
            }
        ]
    };
}

// Khởi tạo tất cả các biểu đồ
function initializeCharts(data) {
    initializeRevenueChart(data.revenueByDate);
    initializeRevenueDistributionChart(data.revenueDistribution);
    initializeTopMoviesChart(data.movies);
    initializeTopFoodsChart(data.foods);
    initializeShowtimeEffectivenessChart(data.showtimes);
    initializeCustomerTrendsChart(data.customerTrends);
}

// Biểu đồ doanh thu
function initializeRevenueChart(data) {
    const chartElement = document.querySelector("#revenue-chart");
    if (!chartElement) {
        console.error("Revenue chart container not found");
        return;
    }
    
    // Destroy previous chart instance if exists
    if (charts.revenueChart) {
        charts.revenueChart.destroy();
        charts.revenueChart = null;
    }
    
    // Xóa nội dung hiện tại của container, bao gồm cả spinner
    chartElement.innerHTML = '';
    
    const options = {
        series: [
            {
                name: 'Tổng doanh thu',
                type: 'line',
                data: (data || []).map(item => item.total)
            },
            {
                name: 'Doanh thu vé',
                type: 'column',
                data: (data || []).map(item => item.ticket)
            },
            {
                name: 'Doanh thu đồ ăn',
                type: 'column',
                data: (data || []).map(item => item.food)
            }
        ],
        chart: {
            height: 320,
            type: 'line',
            stacked: false,
            toolbar: {
                show: true
            },
            zoom: {
                enabled: true
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            },
            fontFamily: 'inherit',
            background: 'transparent',
            parentHeightOffset: 0
        },
        plotOptions: {
            bar: {
                columnWidth: '50%'
            }
        },
        stroke: {
            width: [4, 0, 0],
            curve: 'smooth'
        },
        xaxis: {
            categories: (data || []).map(item => item.date)
        },
        yaxis: {
            title: {
                text: 'Doanh thu (VNĐ)'
            },
            labels: {
                formatter: function(val) {
                    return formatCurrencyShort(val);
                }
            }
        },
        legend: {
            position: 'top'
        },
        fill: {
            opacity: 1
        },
        colors: ['#1E40AF', '#3B82F6', '#93C5FD'],
        tooltip: {
            y: {
                formatter: function(val) {
                    return formatCurrency(val);
                }
            }
        }
    };

    try {
        // Tạo biểu đồ mới và lưu tham chiếu
        charts.revenueChart = new ApexCharts(chartElement, options);
        charts.revenueChart.render().then(() => {
            // Xóa spinner sau khi biểu đồ đã render
            const spinner = chartElement.querySelector('.epic-spinner-container');
            if (spinner) spinner.remove();
        });
    } catch (error) {
        console.error("Error rendering revenue chart:", error);
        showChartError('revenue-chart', 'Lỗi khi hiển thị biểu đồ doanh thu');
    }
}

// Biểu đồ phân bổ doanh thu
function initializeRevenueDistributionChart(data) {
    console.log('Initializing revenue distribution chart with data:', data);
    const chartElement = document.querySelector("#revenue-distribution-chart");
    if (!chartElement) {
        console.error("Revenue distribution chart container not found");
        return;
    }
    
    // Destroy previous chart instance if exists
    if (charts.revenueDistributionChart) {
        charts.revenueDistributionChart.destroy();
        charts.revenueDistributionChart = null;
    }
    
    // Xóa nội dung hiện tại của container, bao gồm cả spinner
    chartElement.innerHTML = '';
    
    const options = {
        series: data.map(item => item.value),
        chart: {
            height: 320,
            type: 'pie',
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            },
            fontFamily: 'inherit',
            background: 'transparent',
            parentHeightOffset: 0
        },
        labels: data.map(item => item.name),
        colors: ['#3B82F6', '#10B981', '#F59E0B'],
        legend: {
            position: 'bottom'
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 300
                },
                legend: {
                    position: 'bottom'
                }
            }
        }],
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + '%';
                }
            }
        }
    };
    
    try {
        // Tạo biểu đồ mới và lưu tham chiếu
        charts.revenueDistributionChart = new ApexCharts(chartElement, options);
        charts.revenueDistributionChart.render().then(() => {
            // Xóa spinner sau khi biểu đồ đã render
            const spinner = chartElement.querySelector('.epic-spinner-container');
            if (spinner) spinner.remove();
        });
    } catch (error) {
        console.error("Error rendering revenue distribution chart:", error);
        showChartError('revenue-distribution-chart', 'Lỗi khi hiển thị biểu đồ phân bổ doanh thu');
    }
}

// Biểu đồ top 10 phim
function initializeTopMoviesChart(data) {
    const chartElement = document.querySelector("#top-movies-chart");
    if (!chartElement) {
        console.error("Top movies chart container not found");
        return;
    }
    
    // Destroy previous chart instance if exists
    if (charts.topMoviesChart) {
        charts.topMoviesChart.destroy();
        charts.topMoviesChart = null;
    }
    
    // Xóa nội dung hiện tại của container, bao gồm cả spinner
    chartElement.innerHTML = '';
    
    // Sắp xếp theo doanh thu giảm dần (hoặc mảng rỗng nếu không có dữ liệu)
    const sortedData = (!data || data.length === 0) 
        ? [] 
        : [...data].sort((a, b) => b.revenue - a.revenue).slice(0, 10);
    
    const options = {
        series: [{
            name: 'Doanh thu',
            data: sortedData.map(movie => movie.revenue)
        }],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: {
                show: true
            }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: '70%',
                distributed: true,
                dataLabels: {
                    position: 'top'
                }
            }
        },
        colors: ['#3B82F6', '#60A5FA', '#93C5FD', '#BFDBFE', '#DBEAFE', 
                 '#2563EB', '#1D4ED8', '#1E40AF', '#1E3A8A', '#172554'],
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return formatCurrencyShort(val);
            },
            offsetX: 30,
            style: {
                fontSize: '12px',
                colors: ['#304758']
            }
        },
        stroke: {
            width: 1,
            colors: ['#fff']
        },
        xaxis: {
            categories: sortedData.map(movie => movie.name),
            labels: {
                formatter: function(val) {
                    return formatCurrencyShort(val);
                }
            }
        },
        yaxis: {
            labels: {
                show: true
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return formatCurrency(val);
                }
            }
        }
    };

    try {
        // Tạo biểu đồ mới và lưu tham chiếu
        charts.topMoviesChart = new ApexCharts(chartElement, options);
        charts.topMoviesChart.render().then(() => {
            // Xóa spinner sau khi biểu đồ đã render
            const spinner = chartElement.querySelector('.epic-spinner-container');
            if (spinner) spinner.remove();
        });
    } catch (error) {
        console.error("Error rendering top movies chart:", error);
        showChartError('top-movies-chart', 'Lỗi khi hiển thị biểu đồ top phim');
    }
}

// Biểu đồ top 10 sản phẩm
function initializeTopFoodsChart(data) {
    const chartElement = document.querySelector("#top-foods-chart");
    if (!chartElement) {
        console.error("Top foods chart container not found");
        return;
    }
    
    // Destroy previous chart instance if exists
    if (charts.topFoodsChart) {
        charts.topFoodsChart.destroy();
        charts.topFoodsChart = null;
    }
    
    // Xóa nội dung hiện tại của container, bao gồm cả spinner
    chartElement.innerHTML = '';
    
    // Sắp xếp theo doanh thu giảm dần (hoặc mảng rỗng nếu không có dữ liệu)
    const sortedData = (!data || data.length === 0) 
        ? [] 
        : [...data].sort((a, b) => b.revenue - a.revenue).slice(0, 10);
    
    const options = {
        series: [{
            name: 'Doanh thu',
            data: sortedData.map(food => food.revenue)
        }],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: {
                show: true
            }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: '70%',
                distributed: true,
                dataLabels: {
                    position: 'top'
                }
            }
        },
        colors: ['#10B981', '#34D399', '#6EE7B7', '#A7F3D0', '#D1FAE5', 
                 '#059669', '#047857', '#065F46', '#064E3B', '#022C22'],
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return formatCurrencyShort(val);
            },
            offsetX: 30,
            style: {
                fontSize: '12px',
                colors: ['#304758']
            }
        },
        stroke: {
            width: 1,
            colors: ['#fff']
        },
        xaxis: {
            categories: sortedData.map(food => food.name),
            labels: {
                formatter: function(val) {
                    return formatCurrencyShort(val);
                }
            }
        },
        yaxis: {
            labels: {
                show: true
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return formatCurrency(val);
                }
            }
        }
    };

    try {
        // Tạo biểu đồ mới và lưu tham chiếu
        charts.topFoodsChart = new ApexCharts(chartElement, options);
        charts.topFoodsChart.render().then(() => {
            // Xóa spinner sau khi biểu đồ đã render
            const spinner = chartElement.querySelector('.epic-spinner-container');
            if (spinner) spinner.remove();
        });
    } catch (error) {
        console.error("Error rendering top foods chart:", error);
        showChartError('top-foods-chart', 'Lỗi khi hiển thị biểu đồ top sản phẩm');
    }
}

// Hàm hiển thị lỗi trong biểu đồ được cập nhật
function showChartError(containerId, message, showRetryButton = true) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Xóa spinner trước khi hiển thị lỗi
    const existingSpinner = container.querySelector('.epic-spinner-container');
    if (existingSpinner) {
        existingSpinner.remove();
    }
    
    // Xóa nội dung hiện tại ngoài spinner
    const children = [...container.children];
    for (const child of children) {
        if (!child.classList.contains('epic-spinner-container')) {
            container.removeChild(child);
        }
    }
    
    // Tạo thông báo lỗi
    const errorDiv = document.createElement('div');
    errorDiv.className = 'flex flex-col items-center justify-center h-full';
    
    let errorHTML = `
        <svg class="h-12 w-12 text-red-500 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <p class="text-gray-700">${message}</p>
    `;
    
    if (showRetryButton) {
        errorHTML += `
            <button class="mt-3 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 focus:outline-none" onclick="retryLoadData()">
                Thử lại
            </button>
        `;
    }
    
    errorDiv.innerHTML = errorHTML;
    container.appendChild(errorDiv);
}

// Biểu đồ hiệu quả theo khung giờ chiếu
function initializeShowtimeEffectivenessChart(data) {
    const chartElement = document.querySelector("#showtime-effectiveness-chart");
    if (!chartElement) {
        console.error("Showtime effectiveness chart container not found");
        return;
    }
    
    // Destroy previous chart instance if exists
    if (charts.showtimeEffectivenessChart) {
        charts.showtimeEffectivenessChart.destroy();
        charts.showtimeEffectivenessChart = null;
    }
    
    // Xóa nội dung hiện tại của container, bao gồm cả spinner
    chartElement.innerHTML = '';
    
    // Xử lý dữ liệu (có thể là mảng rỗng)
    const chartData = (!data || data.length === 0) ? [] : data;
    
    const options = {
        series: [{
            name: 'Tỷ lệ lấp đầy',
            type: 'column',
            data: chartData.map(item => item.occupancy)
        }, {
            name: 'Doanh thu',
            type: 'line',
            data: chartData.map(item => item.revenue)
        }],
        chart: {
            height: 350,
            type: 'line',
            stacked: false
        },
        stroke: {
            width: [0, 4],
            curve: 'smooth'
        },
        plotOptions: {
            bar: {
                columnWidth: '50%'
            }
        },
        fill: {
            opacity: [0.85, 1],
            gradient: {
                inverseColors: false,
                shade: 'light',
                type: "vertical",
                opacityFrom: 0.85,
                opacityTo: 0.55,
                stops: [0, 100, 100, 100]
            }
        },
        markers: {
            size: 0
        },
        xaxis: {
            categories: chartData.map(item => item.time)
        },
        yaxis: [{
            title: {
                text: 'Tỷ lệ lấp đầy (%)',
            },
            min: 0,
            max: 100
        }, {
            opposite: true,
            title: {
                text: 'Doanh thu (VNĐ)'
            },
            labels: {
                formatter: function(val) {
                    return formatCurrencyShort(val);
                }
            }
        }],
        tooltip: {
            shared: true,
            intersect: false,
            y: [{
                formatter: function (y) {
                    if(typeof y !== "undefined") {
                        return y.toFixed(0) + "%";
                    }
                    return y;
                }
            }, {
                formatter: function (y) {
                    if(typeof y !== "undefined") {
                        return formatCurrency(y);
                    }
                    return y;
                }
            }]
        },
        colors: ['#F59E0B', '#7C3AED']
    };

    try {
        // Tạo biểu đồ mới và lưu tham chiếu
        charts.showtimeEffectivenessChart = new ApexCharts(chartElement, options);
        charts.showtimeEffectivenessChart.render().then(() => {
            // Xóa spinner sau khi biểu đồ đã render
            const spinner = chartElement.querySelector('.epic-spinner-container');
            if (spinner) spinner.remove();
        });
    } catch (error) {
        console.error("Error rendering showtime effectiveness chart:", error);
        showChartError('showtime-effectiveness-chart', 'Lỗi khi hiển thị biểu đồ hiệu quả khung giờ');
    }
}

// Biểu đồ xu hướng khách hàng
function initializeCustomerTrendsChart(data) {
    const chartElement = document.querySelector("#customer-trends-chart");
    if (!chartElement) {
        console.error("Customer trends chart container not found");
        return;
    }
    
    // Destroy previous chart instance if exists
    if (charts.customerTrendsChart) {
        charts.customerTrendsChart.destroy();
        charts.customerTrendsChart = null;
    }
    
    // Xóa nội dung hiện tại của container, bao gồm cả spinner
    chartElement.innerHTML = '';
    
    const options = {
        series: [{
            name: 'Tổng khách',
            data: (data || []).map(item => item.total)
        }, {
            name: 'Cuối tuần',
            data: (data || []).map(item => item.weekend)
        }, {
            name: 'Ngày thường',
            data: (data || []).map(item => item.weekday)
        }],
        chart: {
            type: 'area',
            height: 320, // Adjusted height to match other charts
            stacked: false,
            toolbar: {
                show: true
            },
            zoom: {
                enabled: true
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            },
            fontFamily: 'inherit',
            background: 'transparent',
            parentHeightOffset: 0
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: [3, 2, 2]
        },
        xaxis: {
            categories: (data || []).map(item => item.date),
        },
        yaxis: {
            title: {
                text: 'Số lượng khách'
            },
        },
        tooltip: {
            shared: true,
            intersect: false,
        },
        fill: {
            type: 'gradient',
            gradient: {
                opacityFrom: 0.6,
                opacityTo: 0.1,
            }
        },
        colors: ['#7C3AED', '#F59E0B', '#10B981']
    };

    try {
        // Tạo biểu đồ mới và lưu tham chiếu
        charts.customerTrendsChart = new ApexCharts(chartElement, options);
        charts.customerTrendsChart.render().then(() => {
            // Xóa spinner sau khi biểu đồ đã render
            const spinner = chartElement.querySelector('.epic-spinner-container');
            if (spinner) spinner.remove();
        });
    } catch (error) {
        console.error("Error rendering customer trends chart:", error);
        showChartError('customer-trends-chart', 'Lỗi khi hiển thị biểu đồ xu hướng khách hàng');
    }
}

// Hàm cập nhật bảng phân tích
function updateAnalysisTable(data, type) {
    const tableBody = document.getElementById('analysis-table-body');
    const tableHeader = document.getElementById('analysis-table-header');
    
    if (!tableBody || !tableHeader) return;
    
    // Ẩn spinner nếu có
    const spinner = tableBody.closest('table')?.parentElement?.querySelector('.epic-spinner-container');
    if (spinner) spinner.remove();
    
    // Cập nhật header của bảng theo loại phân tích
    let headerHTML = `
        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            ${type === 'movie' ? 'Tên phim' : type === 'food' ? 'Tên đồ ăn/đồ uống' : 'Khung giờ'}
        </th>
        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Doanh thu
        </th>
        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            ${type === 'movie' || type === 'food' ? 'Số lượt' : 'Tỷ lệ lấp đầy'}
        </th>
        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Tỷ lệ đóng góp
        </th>
        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            So với kỳ trước
        </th>
    `;
    tableHeader.innerHTML = headerHTML;
    
    // Xóa dữ liệu cũ
    tableBody.innerHTML = '';
    
    // Nếu không có dữ liệu, hiển thị bảng trống
    if (!data || data.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                    Không có dữ liệu phân tích
                </td>
            </tr>
        `;
        return;
    }
    
    // Thêm dữ liệu mới
    data.forEach(item => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        
        const quantityOrOccupancy = type === 'showtime' 
            ? `${item.occupancy}%` 
            : formatNumber(type === 'movie' ? item.tickets : item.quantity);
            
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                ${item.name || item.time}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${formatCurrency(item.revenue)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${quantityOrOccupancy}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${item.contribution}%
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                ${getTrendBadge(item.trend)}
            </td>
        `;
        
        tableBody.appendChild(row);
    });
}

// Hàm cập nhật đề xuất kinh doanh
function updateBusinessRecommendations(recommendations) {
    const container = document.getElementById('business-recommendations');
    if (!container) return;
    
    // Ẩn spinner nếu có
    const spinner = container.querySelector('.epic-spinner-container');
    if (spinner) spinner.remove();
    
    container.innerHTML = '';
    
    recommendations.forEach(rec => {
        const colorClass = rec.type === 'success' 
            ? 'bg-green-50 text-green-800 text-green-700' 
            : rec.type === 'warning' 
                ? 'bg-yellow-50 text-yellow-800 text-yellow-700' 
                : 'bg-blue-50 text-blue-800 text-blue-700';
        
        const iconClass = rec.type === 'success' 
            ? 'text-green-400' 
            : rec.type === 'warning' 
                ? 'text-yellow-400' 
                : 'text-blue-400';
                
        const iconSvg = rec.type === 'success' 
            ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />' 
            : rec.type === 'warning' 
                ? '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />' 
                : '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9z" clip-rule="evenodd" />';
        
        const recHTML = `
        <div class="${colorClass} p-4 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 ${iconClass}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        ${iconSvg}
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium">${rec.title}</h3>
                    <div class="mt-2 text-sm">
                        <p>${rec.content}</p>
                    </div>
                </div>
            </div>
        </div>
        `;
        
        container.innerHTML += recHTML;
    });
}

// Hàm chuyển đổi tab phân tích
function switchAnalysisTab(button, type) {
    console.log('🔄 Switching to tab:', type);
    console.log('📦 Cache status:', {
        movies: cachedMoviesData.length,
        foods: cachedFoodsData.length,
        showtimes: cachedShowtimesData.length
    });
    
    // Reset all buttons to default style
    document.getElementById('btn-movie-analysis').className = 'px-3 py-2 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500';
    document.getElementById('btn-food-analysis').className = 'px-3 py-2 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500';
    document.getElementById('btn-showtime-analysis').className = 'px-3 py-2 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500';
    
    // Set active button style
    button.className = 'px-3 py-2 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500';
    
    // Lưu loại phân tích hiện tại
    currentAnalysisType = type;
    
    // Cập nhật bảng phân tích với dữ liệu đã cache
    if (type === 'movie') {
        console.log('✅ Updating table with movies data');
        updateAnalysisTable(cachedMoviesData || [], 'movie');
    } else if (type === 'food') {
        console.log('✅ Updating table with foods data');
        updateAnalysisTable(cachedFoodsData || [], 'food');
    } else if (type === 'showtime') {
        console.log('✅ Updating table with showtimes data');
        updateAnalysisTable(cachedShowtimesData || [], 'showtime');
    }
}

// Xuất dữ liệu (mô phỏng)
function exportData() {
    alert('Đang xuất dữ liệu ra file Excel...');
    // Trong thực tế, đây sẽ gọi một API để tải về file Excel
}

// Các hàm định dạng dữ liệu
function formatCurrency(value) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
}

function formatCurrencyShort(value) {
    if (value >= 1000000000) {
        return (value / 1000000000).toFixed(1) + ' tỷ';
    } else if (value >= 1000000) {
        return (value / 1000000).toFixed(1) + ' tr';
    } else if (value >= 1000) {
        return (value / 1000).toFixed(1) + ' k';
    }
    return value;
}

function formatNumber(value) {
    return new Intl.NumberFormat('vi-VN').format(value);
}

function formatPercent(value) {
    return value.toFixed(2) + '%';
}

// Sửa hàm formatTrend để làm nổi bật số phần trăm
function formatTrend(value) {
    const isPositive = value >= 0;
    const arrow = isPositive ? '↑' : '↓';
    const colorClass = isPositive ? 'text-white font-bold' : 'text-white font-bold';
    const bgColorClass = isPositive ? 'bg-green-600 px-2 py-1 rounded' : 'bg-red-600 px-2 py-1 rounded';
    
    const span = document.createElement('span');
    span.className = `${colorClass} ${bgColorClass}`;
    span.textContent = `${arrow} ${Math.abs(value).toFixed(1)}%`;
    
    return span.outerHTML;
}

// Cập nhật hàm getTrendBadge để có cùng phong cách
function getTrendBadge(value) {
    const isPositive = value >= 0;
    const arrow = isPositive ? '↑' : '↓';
    const colorClass = isPositive ? 'bg-green-600 text-white' : 'bg-red-600 text-white';
    
    const span = document.createElement('span');
    span.className = `${colorClass} px-2 py-1 text-xs font-bold rounded-full`;
    span.textContent = `${arrow} ${Math.abs(value).toFixed(1)}%`;
    
    return span.outerHTML;
}

// Hiển thị spinner trong box thống kê
function showBoxSpinner(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return null;
    
    // Lưu giá trị hiện tại
    const currentValue = element.textContent;
    
    // Sử dụng Spinner module với tùy chọn kích thước nhỏ và không có overlay
    const spinner = Spinner.show({
        target: element,
        size: 'sm',
        overlay: false,
        text: 'Đang tải...'
    });
    
    return {
        element,
        currentValue,
        spinner
    };
}

// Ẩn spinner trong box thống kê
function hideBoxSpinner(spinnerInfo) {
    if (!spinnerInfo || !spinnerInfo.spinner) return;
    
    // Ẩn spinner
    Spinner.hide(spinnerInfo.spinner);
}

// Hiển thị spinner trong biểu đồ
function showChartSpinner(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return null;
    
    // Đảm bảo container có position để định vị spinner
    if (getComputedStyle(container).position === 'static') {
        container.style.position = 'relative';
    }
    
    // Đảm bảo container có chiều cao đủ để hiển thị spinner
    if (container.clientHeight < 100) {
        container.style.minHeight = '320px';
    }
    
    // Xóa nội dung hiện tại ngoài spinner
    const children = [...container.children];
    for (const child of children) {
        if (!child.classList.contains('epic-spinner-container') && 
            !child.classList.contains('chart-error')) {
            container.removeChild(child);
        }
    }
    
    // Xóa spinner hiện tại nếu có
    const existingSpinner = container.querySelector('.epic-spinner-container');
    if (existingSpinner) {
        existingSpinner.remove();
    }
    
    // Tạo phần tử container trống trước khi thêm spinner
    container.innerHTML = '';
    
    // Sử dụng Spinner module với z-index cao để đảm bảo hiển thị trên cùng
    return Spinner.show({
        target: container,
        size: 'md',
        overlay: true,
        text: 'Đang tải biểu đồ...',
        zIndex: 1000 // Đặt z-index cao hơn các phần tử khác
    });
}

// Đảm bảo spinner hiển thị đủ lâu
const spinnerShowTimes = {};

// Ẩn spinner trong biểu đồ
function hideChartSpinner(spinner) {
    if (!spinner) return;
    
    try {
        // Lấy ID của spinner (thường là một timestamp)
        const spinnerId = spinner.id;
        if (!spinnerId) {
            Spinner.hide(spinner);
            return;
        }
        
        // Ghi lại thời điểm spinner xuất hiện nếu chưa có
        if (!spinnerShowTimes[spinnerId]) {
            spinnerShowTimes[spinnerId] = Date.now();
        }
        
        // Tính toán thời gian đã hiển thị
        const showTime = Date.now() - spinnerShowTimes[spinnerId];
        const minShowTime = 800; // Thời gian tối thiểu spinner hiển thị (0.8 giây)
        
        if (showTime < minShowTime) {
            // Nếu spinner chưa hiển thị đủ lâu, đợi thêm một lúc
            setTimeout(() => {
                Spinner.hide(spinner);
                // Xóa khỏi object theo dõi sau khi đã ẩn
                delete spinnerShowTimes[spinnerId];
            }, minShowTime - showTime);
        } else {
            // Nếu đã hiển thị đủ lâu, ẩn spinner ngay lập tức
            Spinner.hide(spinner);
            // Xóa khỏi object theo dõi
            delete spinnerShowTimes[spinnerId];
        }
    } catch (error) {
        console.error('Error hiding spinner:', error);
        
        // Trong trường hợp có lỗi, vẫn cố gắng ẩn spinner
        try {
            Spinner.hide(spinner);
        } catch {}
    }
}

// Hiển thị spinner trong bảng
function showTableSpinner(tableBodyId) {
    const tableBody = document.getElementById(tableBodyId);
    if (!tableBody) return null;
    
    // Thay vì thay thế nội dung, thêm spinner trên bảng
    const parentElement = tableBody.parentElement;
    
    // Đảm bảo parentElement có position để định vị spinner
    if (getComputedStyle(parentElement).position === 'static') {
        parentElement.style.position = 'relative';
    }
    
    return Spinner.show({
        target: parentElement,
        text: 'Đang tải dữ liệu...',
        overlay: true
    });
}

// Ẩn spinner trong bảng
function hideTableSpinner(spinner) {
    Spinner.hide(spinner);
}

// Hiển thị spinner trong một section
function showSectionSpinner(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return null;
    
    // Đảm bảo section có position để định vị spinner
    if (getComputedStyle(section).position === 'static') {
        section.style.position = 'relative';
    }
    
    // Xóa spinner hiện tại nếu có
    const existingSpinner = section.querySelector('.epic-spinner-container');
    if (existingSpinner) {
        existingSpinner.remove();
    }
    
    // Xác định nội dung dựa trên sectionId
    let spinnerText = 'Đang tải dữ liệu...';
    if (sectionId === 'business-recommendations') {
        spinnerText = 'Đang phân tích dữ liệu để tạo đề xuất...';
    }
    
    return Spinner.show({
        target: section,
        text: spinnerText,
        overlay: true,
        zIndex: 1000,
        color: '#3B82F6' // Màu xanh để nổi bật hơn
    });
}

// Ẩn spinner trong một section với hiệu ứng trễ tối thiểu
function hideSpinner(spinner) {
    if (!spinner) return;
    
    try {
        // Lấy ID của spinner
        const spinnerId = spinner.id;
        if (!spinnerId) {
            Spinner.hide(spinner);
            return;
        }
        
        // Ghi lại thời điểm spinner xuất hiện nếu chưa có
        if (!spinnerShowTimes[spinnerId]) {
            spinnerShowTimes[spinnerId] = Date.now();
        }
        
        // Tính toán thời gian đã hiển thị
        const showTime = Date.now() - spinnerShowTimes[spinnerId];
        const minShowTime = 1000; // Thời gian tối thiểu spinner hiển thị (1 giây)
        
        if (showTime < minShowTime) {
            // Nếu spinner chưa hiển thị đủ lâu, đợi thêm một lúc
            setTimeout(() => {
                Spinner.hide(spinner);
                // Xóa khỏi object theo dõi sau khi đã ẩn
                delete spinnerShowTimes[spinnerId];
            }, minShowTime - showTime);
        } else {
            // Nếu đã hiển thị đủ lâu, ẩn spinner ngay lập tức
            Spinner.hide(spinner);
            // Xóa khỏi object theo dõi
            delete spinnerShowTimes[spinnerId];
        }
    } catch (error) {
        console.error('Error hiding spinner:', error);
        
        // Trong trường hợp có lỗi, vẫn cố gắng ẩn spinner
        try {
            Spinner.hide(spinner);
        } catch {}
    }
}


// Hiển thị lỗi trong bảng
function showTableError(tableBodyId, message) {
    const tableBody = document.getElementById(tableBodyId);
    if (!tableBody) return;
    
    tableBody.innerHTML = `
        <tr>
            <td colspan="5" class="px-6 py-8 text-center">
                <div class="flex flex-col items-center justify-center">
                    <svg class="h-8 w-8 text-red-500 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-gray-700">${message}</p>
                    <button class="mt-3 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 focus:outline-none" onclick="retryLoadData()">
                        Thử lại
                    </button>
                </div>
            </td>
        </tr>
    `;
}

// Hiển thị thông báo bên dưới biểu đồ
function addChartMessage(containerId, message) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Kiểm tra xem đã có thông báo nào chưa
    const existingMessage = container.querySelector('.chart-message');
    if (existingMessage) {
        existingMessage.textContent = message;
        return;
    }
    
    // Tạo thông báo mới
    const messageElement = document.createElement('div');
    messageElement.className = 'chart-message text-center text-sm text-gray-500 mt-2';
    messageElement.textContent = message;
    
    // Thêm vào cuối container
    container.appendChild(messageElement);
}

// Hàm thử lại tải dữ liệu
function retryLoadData() {
    const dateRange = document.getElementById('date-range').value;
    if (dateRange === 'custom') {
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        if (startDate && endDate) {
            fetchData('custom', { startDate, endDate });
        }
    } else {
        fetchData(dateRange);
    }
}

// Cập nhật hàm updateOverviewData để hiển thị dữ liệu tổng quan
function updateOverviewData(overview) {
    // Cập nhật tổng doanh thu
    document.getElementById('total-revenue').textContent = formatCurrency(overview.totalRevenue);
    
    // Cập nhật xu hướng doanh thu
    const revenueTrendElement = document.getElementById('revenue-trend');
    revenueTrendElement.innerHTML = formatTrend(overview.revenueTrend);
    
    // Cập nhật số lượng khách hàng
    document.getElementById('total-customers').textContent = formatNumber(overview.totalCustomers);
    
    // Cập nhật xu hướng khách hàng
    const customerTrendElement = document.getElementById('customer-trend');
    customerTrendElement.innerHTML = formatTrend(overview.customerTrend);
    
    // Cập nhật tỷ lệ lấp đầy
    document.getElementById('occupancy-rate').textContent = overview.occupancyRate.toFixed(1) + '%';
    
    // Cập nhật xu hướng tỷ lệ lấp đầy
    const occupancyTrendElement = document.getElementById('occupancy-trend');
    occupancyTrendElement.innerHTML = formatTrend(overview.occupancyTrend);
    
    
    // Cập nhật doanh thu đồ ăn/khách
    document.getElementById('food-per-customer').textContent = formatCurrency(overview.foodPerCustomer);
    
    // Cập nhật xu hướng doanh thu đồ ăn/khách
    const foodTrendElement = document.getElementById('food-trend');
    foodTrendElement.innerHTML = formatTrend(overview.foodTrend);
}

// Biến để lưu trữ tham chiếu tới các biểu đồ
const charts = {
    revenueChart: null,
    revenueDistributionChart: null,
    topMoviesChart: null,
    topFoodsChart: null,
    showtimeEffectivenessChart: null,
    customerTrendsChart: null
};

// Hàm để xóa tất cả biểu đồ hiện tại
function destroyAllCharts() {
    Object.values(charts).forEach(chart => {
        if (chart) {
            try {
                chart.destroy();
            } catch (error) {
                console.error('Error destroying chart:', error);
            }
        }
    });
    
    // Reset các tham chiếu
    for (let key in charts) {
        charts[key] = null;
    }
    
    // Clean up any orphaned ApexCharts elements
    document.querySelectorAll('.apexcharts-canvas').forEach(element => {
        if (element && element.parentNode) {
            element.parentNode.removeChild(element);
        }
    });
}

// Thêm hàm để tạo dữ liệu mẫu cho đồ ăn
function getSampleFoodsData() {
    return [
        { name: 'Bắp rang bơ (lớn)', revenue: 0, quantity: 0, contribution: 0, trend: 0 },
        { name: 'Coca Cola (lớn)', revenue: 0, quantity: 0, contribution: 0, trend: 0 },
        { name: 'Combo bắp + nước', revenue: 0, quantity: 0, contribution: 0, trend: 0 },
        { name: 'Khoai tây chiên', revenue: 0, quantity: 0, contribution: 0, trend: 0 },
        { name: 'Nachos', revenue: 0, quantity: 0, contribution: 0, trend: 0 }
    ];
}

// Hiển thị spinner trên tất cả biểu đồ ngay khi trang tải
function preShowAllSpinners() {
    // Xóa các biểu đồ hiện tại để tránh xung đột
    destroyAllCharts();
    
    // Thêm một chút trễ để đảm bảo DOM đã được cập nhật
    setTimeout(() => {
        // Hiển thị spinner cho các chỉ số tổng quan
        const overviewElements = [
            'total-revenue', 
            'total-customers', 
            'occupancy-rate', 
            'food-per-customer'
        ];
        
        overviewElements.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                // Thêm hiệu ứng nhấp nháy để biểu thị đang tải
                element.classList.add('animate-pulse', 'text-gray-300');
            }
        });
    
        // Danh sách các container cần hiển thị spinner
        const chartContainers = [
            'revenue-chart',
            'revenue-distribution-chart',
            'top-movies-chart',
            'top-foods-chart',
            'showtime-effectiveness-chart',
            'customer-trends-chart'
        ];
        
        // Hiển thị spinner cho mỗi container biểu đồ
        chartContainers.forEach(containerId => {
            const container = document.getElementById(containerId);
            if (container) {
                // Xóa nội dung hiện tại để tránh xung đột
                container.innerHTML = '';
                
                // Đảm bảo container có position để định vị spinner
                container.style.position = 'relative';
                
                // Đảm bảo container có chiều cao đủ để hiển thị spinner
                container.style.minHeight = '320px';
                
                // Hiển thị spinner với văn bản tùy chỉnh theo từng biểu đồ
                const spinnerText = containerId === 'revenue-chart' ? 'Đang tải dữ liệu doanh thu...' :
                                  containerId === 'revenue-distribution-chart' ? 'Đang tải dữ liệu phân bổ doanh thu...' :
                                  containerId === 'top-movies-chart' ? 'Đang tải dữ liệu top phim...' :
                                  containerId === 'top-foods-chart' ? 'Đang tải dữ liệu top sản phẩm...' :
                                  containerId === 'showtime-effectiveness-chart' ? 'Đang tải dữ liệu suất chiếu...' :
                                  containerId === 'customer-trends-chart' ? 'Đang tải dữ liệu xu hướng khách hàng...' :
                                  'Đang tải dữ liệu...';
                                  
                const spinner = Spinner.show({
                    target: container,
                    size: 'md',
                    overlay: true,
                    text: spinnerText,
                    color: '#3B82F6' // Màu xanh để nổi bật hơn
                });
            }
        });
        
        // Hiển thị spinner cho khu vực đề xuất
        const recommendationsContainer = document.getElementById('business-recommendations');
        if (recommendationsContainer) {
            // Xóa nội dung hiện tại để tránh xung đột
            recommendationsContainer.innerHTML = '';
            
            showSectionSpinner('business-recommendations');
        }
        
        // Hiển thị spinner cho bảng phân tích
        const analysisTable = document.getElementById('analysis-table-body');
        if (analysisTable) {
            // Hiển thị một hàng spinner trong bảng
            analysisTable.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-600 border-r-transparent"></div>
                            <p class="mt-2 text-gray-700">Đang tải dữ liệu phân tích...</p>
                        </div>
                    </td>
                </tr>
            `;
        }
    }, 100);
}

/**
 * Khởi tạo biểu đồ xu hướng doanh thu theo suất chiếu
 */
function initializeRevenueShowtimeChart() {
    const chartElement = document.getElementById('revenue-showtime-chart');
    if (!chartElement) return;
    
    if (revenueShowtimeChart) {
        revenueShowtimeChart.destroy();
    }
    
    revenueShowtimeChart = new ApexCharts(chartElement, {
        series: [{
            name: 'Doanh thu',
            data: []
        }],
        chart: {
            type: 'line',
            height: 320,
            toolbar: { show: true },
            zoom: { enabled: true }
        },
        xaxis: {
            categories: []
        },
        yaxis: {
            labels: {
                formatter: function(val) {
                    return formatCurrencyShort(val);
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return formatCurrency(val);
                }
            }
        },
        noData: {
            text: 'Đang tải dữ liệu...',
            align: 'center',
            verticalAlign: 'middle'
        }
    });
    
    revenueShowtimeChart.render();
}

/**
 * Khởi tạo biểu đồ xu hướng vé bán theo suất chiếu
 */
function initializeTicketsShowtimeChart() {
    const chartElement = document.getElementById('tickets-showtime-chart');
    if (!chartElement) return;
    
    if (ticketsShowtimeChart) {
        ticketsShowtimeChart.destroy();
    }
    
    ticketsShowtimeChart = new ApexCharts(chartElement, {
        series: [{
            name: 'Số vé bán',
            data: []
        }],
        chart: {
            type: 'line',
            height: 320,
            toolbar: { show: true },
            zoom: { enabled: true }
        },
        xaxis: {
            categories: []
        },
        yaxis: {
            labels: {
                formatter: function(val) {
                    return formatNumber(val);
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return formatNumber(val);
                }
            }
        },
        noData: {
            text: 'Đang tải dữ liệu...',
            align: 'center',
            verticalAlign: 'middle'
        }
    });
    
    ticketsShowtimeChart.render();
}

/**
 * Khởi tạo biểu đồ hiệu suất theo rạp (suất chiếu)
 */
function initializeTheaterPerformanceShowtimeChart() {
    const chartElement = document.getElementById('theater-performance-showtime-chart');
    if (!chartElement) return;
    
    if (theaterPerformanceShowtimeChart) {
        theaterPerformanceShowtimeChart.destroy();
    }
    
    theaterPerformanceShowtimeChart = new ApexCharts(chartElement, {
        series: [{
            name: 'Doanh thu',
            data: []
        }],
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: true }
        },
        xaxis: {
            categories: []
        },
        yaxis: {
            labels: {
                formatter: function(val) {
                    return formatCurrencyShort(val);
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return formatCurrency(val);
                }
            }
        },
        noData: {
            text: '',
            align: 'center',
            verticalAlign: 'middle',
            style: {
                fontSize: '0px',
                color: 'transparent'
            }
        }
    });
    
    theaterPerformanceShowtimeChart.render();
}

/**
 * Khởi tạo biểu đồ cơ cấu doanh thu (suất chiếu)
 */
function initializeRevenueBreakdownShowtimeChart() {
    const chartElement = document.getElementById('revenue-breakdown-showtime-chart');
    if (!chartElement) return;
    
    if (revenueBreakdownShowtimeChart) {
        revenueBreakdownShowtimeChart.destroy();
    }
    
    revenueBreakdownShowtimeChart = new ApexCharts(chartElement, {
        series: [],
        chart: {
            type: 'donut',
            height: 320,
            toolbar: { show: true }
        },
        labels: [],
        colors: ['#EF4444', '#F59E0B'],
        legend: {
            position: 'bottom'
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val.toFixed(1) + '%';
                }
            }
        },
        noData: {
            text: 'Đang tải dữ liệu...',
            align: 'center',
            verticalAlign: 'middle'
        }
    });
    
    revenueBreakdownShowtimeChart.render();
}


//# sourceMappingURL=main.js.map
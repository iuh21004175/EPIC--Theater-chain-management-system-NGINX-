document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra ApexCharts đã load chưa
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts not loaded');
        return;
    }

    // Khởi tạo biểu đồ
    let revenueChart = null;
    let revenueBreakdownChart = null;

    // Lấy URL base từ window location
    const baseUrl = window.location.origin;

    // Tính toán ngày tháng hiện tại (tháng này)
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    const tuNgay = firstDayOfMonth.toISOString().split('T')[0];
    const denNgay = lastDayOfMonth.toISOString().split('T')[0];

    // Khởi tạo biểu đồ
    initializeCharts();

    // Tải dữ liệu
    loadStatistics();

    // Khởi tạo các biểu đồ
    function initializeCharts() {
        // Biểu đồ xu hướng doanh thu
        const revenueChartElement = document.getElementById('chart-doanh-thu-rap');
        if (revenueChartElement) {
            revenueChart = new ApexCharts(revenueChartElement, {
                series: [{
                    name: 'Doanh thu',
                    data: []
                }],
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                colors: ['#3B82F6'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3,
                        stops: [0, 90, 100]
                    }
                },
                xaxis: {
                    categories: [],
                    labels: {
                        rotate: -45,
                        rotateAlways: false,
                        hideOverlappingLabels: true,
                        showDuplicates: false,
                        maxHeight: 80,
                        style: {
                            fontSize: '11px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(value) {
                            return formatCurrency(value);
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            return formatCurrency(value);
                        }
                    }
                },
                noData: {
                    text: 'Đang tải dữ liệu...',
                    align: 'center',
                    verticalAlign: 'middle'
                }
            });
            revenueChart.render();
        }

        // Biểu đồ phân bổ doanh thu (donut chart)
        const revenueBreakdownChartElement = document.getElementById('chart-phan-bo-doanh-thu-rap');
        if (revenueBreakdownChartElement) {
            revenueBreakdownChart = new ApexCharts(revenueBreakdownChartElement, {
                series: [],
                chart: {
                    type: 'donut',
                    height: 300
                },
                labels: [],
                colors: ['#3B82F6', '#8B5CF6'],
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            return formatCurrency(value);
                        }
                    }
                },
                noData: {
                    text: 'Đang tải dữ liệu...',
                    align: 'center',
                    verticalAlign: 'middle'
                }
            });
            revenueBreakdownChart.render();
        }
    }

    // Tải dữ liệu thống kê
    async function loadStatistics() {
        try {
            // Tải thống kê tổng quan
            await loadTongQuan();

            // Tải phân tích doanh thu
            await loadPhanTichDoanhThu();

            // Tải top phim
            await loadTopPhim();

            // Tải top sản phẩm
            await loadTopSanPham();
        } catch (error) {
            console.error('Lỗi tải dữ liệu thống kê:', error);
        }
    }

    // Tải thống kê tổng quan
    async function loadTongQuan() {
        try {
            const params = new URLSearchParams({
                tuNgay: tuNgay,
                denNgay: denNgay,
                soSanh: 'true'
            });

            const response = await fetch(`${baseUrl}/api/thong-ke-theo-rap/tong-quan?${params.toString()}`, {
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
                
                // Cập nhật KPI cards
                updateKPICard('tong-doanh-thu-rap', data.tong_doanh_thu, data.so_sanh?.phan_tram_thay_doi_doanh_thu);
                updateKPICard('tong-ve-ban-rap', data.tong_ve_ban, data.so_sanh?.phan_tram_thay_doi_ve_ban);
                updateKPICard('ty-le-lap-day-rap', data.ty_le_lap_day + '%', data.so_sanh?.phan_tram_thay_doi_lap_day);
                updateKPICard('doanh-thu-fnb-rap', data.doanh_thu_fnb, data.so_sanh?.phan_tram_thay_doi_fnb);
            }
        } catch (error) {
            console.error('Lỗi tải thống kê tổng quan:', error);
        }
    }

    // Tải phân tích doanh thu
    async function loadPhanTichDoanhThu() {
        try {
            const params = new URLSearchParams({
                tuNgay: tuNgay,
                denNgay: denNgay
            });

            const response = await fetch(`${baseUrl}/api/thong-ke-theo-rap/phan-tich-doanh-thu?${params.toString()}`, {
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
                
                // Cập nhật biểu đồ xu hướng doanh thu
                if (data.chi_tiet_theo_ngay && Array.isArray(data.chi_tiet_theo_ngay) && revenueChart) {
                    const categories = data.chi_tiet_theo_ngay.map(item => item.ngay_formatted);
                    const doanhThuData = data.chi_tiet_theo_ngay.map(item => item.tong_doanh_thu || 0);
                    
                    revenueChart.updateOptions({
                        xaxis: {
                            categories: categories,
                            labels: {
                                rotate: -45,
                                rotateAlways: false,
                                hideOverlappingLabels: true,
                                showDuplicates: false,
                                maxHeight: 80,
                                style: {
                                    fontSize: '11px'
                                }
                            },
                            tickAmount: categories.length > 15 ? 15 : undefined
                        },
                        noData: {
                            text: ''
                        }
                    });
                    revenueChart.updateSeries([{
                        name: 'Doanh thu',
                        data: doanhThuData
                    }]);
                }

                // Cập nhật biểu đồ phân bổ doanh thu
                if (data.tong_ket && revenueBreakdownChart) {
                    const doanhThuVe = data.tong_ket.tong_doanh_thu_ve || 0;
                    const doanhThuSanPham = data.tong_ket.tong_doanh_thu_san_pham || 0;
                    
                    if (doanhThuVe > 0 || doanhThuSanPham > 0) {
                        revenueBreakdownChart.updateOptions({
                            labels: ['Doanh thu vé', 'Doanh thu F&B'],
                            colors: ['#3B82F6', '#8B5CF6']
                        });
                        revenueBreakdownChart.updateSeries([doanhThuVe, doanhThuSanPham]);
                    }
                }
            }
        } catch (error) {
            console.error('Lỗi tải phân tích doanh thu:', error);
        }
    }

    // Tải top phim
    async function loadTopPhim() {
        try {
            const params = new URLSearchParams({
                tuNgay: tuNgay,
                denNgay: denNgay
            });

            const response = await fetch(`${baseUrl}/api/thong-ke-theo-rap/top10-phim?${params.toString()}`, {
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
                const topPhimList = document.getElementById('top-phim-list-rap');
                
                if (topPhimList && data.danh_sach && Array.isArray(data.danh_sach)) {
                    // Lấy top 5
                    const top5 = data.danh_sach.slice(0, 5);
                    
                    topPhimList.innerHTML = top5.map((phim, index) => `
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center flex-1">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-sm mr-3">
                                    ${index + 1}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">${phim.ten_phim || 'N/A'}</p>
                                    <p class="text-xs text-gray-500">${phim.so_ve_ban || 0} vé đã bán</p>
                                </div>
                            </div>
                            <div class="text-right ml-4">
                                <p class="text-sm font-bold text-blue-600">${formatCurrency(phim.doanh_thu || 0)}</p>
                            </div>
                        </div>
                    `).join('');
                } else if (topPhimList) {
                    topPhimList.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Không có dữ liệu</p>';
                }
            }
        } catch (error) {
            console.error('Lỗi tải top phim:', error);
            const topPhimList = document.getElementById('top-phim-list-rap');
            if (topPhimList) {
                topPhimList.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Không có dữ liệu</p>';
            }
        }
    }

    // Tải top sản phẩm
    async function loadTopSanPham() {
        try {
            const params = new URLSearchParams({
                tuNgay: tuNgay,
                denNgay: denNgay
            });

            const response = await fetch(`${baseUrl}/api/thong-ke-theo-rap/top10-san-pham?${params.toString()}`, {
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
                const topSanPhamList = document.getElementById('top-san-pham-list-rap');
                
                if (topSanPhamList && data.danh_sach && Array.isArray(data.danh_sach)) {
                    // Lấy top 5
                    const top5 = data.danh_sach.slice(0, 5);
                    
                    topSanPhamList.innerHTML = top5.map((sp, index) => `
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center flex-1">
                                <div class="flex-shrink-0 w-8 h-8 bg-violet-500 text-white rounded-full flex items-center justify-center font-bold text-sm mr-3">
                                    ${index + 1}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">${sp.ten_san_pham || 'N/A'}</p>
                                    <p class="text-xs text-gray-500">Đã bán: ${formatNumber(sp.so_luong || 0)}</p>
                                </div>
                            </div>
                            <div class="text-right ml-4">
                                <p class="text-sm font-bold text-violet-600">${formatCurrency(sp.doanh_thu || 0)}</p>
                            </div>
                        </div>
                    `).join('');
                } else if (topSanPhamList) {
                    topSanPhamList.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Không có dữ liệu</p>';
                }
            }
        } catch (error) {
            console.error('Lỗi tải top sản phẩm:', error);
            const topSanPhamList = document.getElementById('top-san-pham-list-rap');
            if (topSanPhamList) {
                topSanPhamList.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Không có dữ liệu</p>';
            }
        }
    }

    // Cập nhật KPI card
    function updateKPICard(elementId, value, changePercent) {
        const element = document.getElementById(elementId);
        if (element) {
            if (elementId === 'tong-doanh-thu-rap' || elementId === 'doanh-thu-fnb-rap') {
                element.textContent = formatCurrency(value);
            } else if (elementId === 'ty-le-lap-day-rap') {
                element.textContent = value;
            } else {
                element.textContent = formatNumber(value);
            }
        }

        // Cập nhật phần trăm thay đổi
        const changeElement = document.getElementById(elementId + '-change');
        if (changeElement && changePercent !== undefined && changePercent !== null) {
            const percent = parseFloat(changePercent);
            if (percent > 0) {
                changeElement.textContent = `↑ ${percent.toFixed(1)}% so với kỳ trước`;
                changeElement.className = 'text-xs text-green-600 mt-2 font-medium';
            } else if (percent < 0) {
                changeElement.textContent = `↓ ${Math.abs(percent).toFixed(1)}% so với kỳ trước`;
                changeElement.className = 'text-xs text-red-600 mt-2 font-medium';
            } else {
                changeElement.textContent = 'Không thay đổi';
                changeElement.className = 'text-xs text-gray-500 mt-2';
            }
        }
    }

    // Format currency
    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN', { 
            style: 'currency', 
            currency: 'VND',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    }

    // Format number
    function formatNumber(value) {
        return new Intl.NumberFormat('vi-VN').format(value);
    }
});


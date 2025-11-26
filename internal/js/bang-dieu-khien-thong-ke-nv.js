// Thống kê cho nhân viên
const baseUrl = window.location.origin;

// Format tiền tệ
function formatCurrency(value) {
    if (value === null || value === undefined || isNaN(value)) {
        return '0 ₫';
    }
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(value);
}
let revenueChart = null;

// Ngày mặc định: tháng hiện tại
const tuNgay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
const denNgay = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().split('T')[0];

// Khởi tạo biểu đồ
function initializeCharts() {
    // Biểu đồ xu hướng doanh thu
    const revenueChartElement = document.getElementById('chart-doanh-thu-nv');
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
            colors: ['#10B981'],
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
                },
                tickAmount: 15
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
}

// Tải dữ liệu thống kê
async function loadStatistics() {
    try {
        // Tải thống kê tổng quan
        await loadTongQuan();

        // Tải xu hướng doanh thu
        await loadXuHuongDoanhThu();

        // Tải top phim
        await loadTopPhim();
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

        const response = await fetch(`${baseUrl}/api/thong-ke-nhan-vien/tong-quan?${params.toString()}`, {
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

        if (!result.success) {
            // Hiển thị thông báo lỗi
            const errorMessage = result.message || 'Không thể tải dữ liệu thống kê';
            showError('tong-doanh-thu-nv', errorMessage);
            showError('tong-ve-ban-nv', errorMessage);
            showError('tong-don-hang-nv', errorMessage);
            return;
        }

        if (result.data) {
            const data = result.data;
            
            // Cập nhật KPI cards
            document.getElementById('tong-doanh-thu-nv').textContent = formatCurrency(data.tong_doanh_thu || 0);
            document.getElementById('tong-ve-ban-nv').textContent = (data.tong_ve_ban || 0).toLocaleString('vi-VN');
            document.getElementById('tong-don-hang-nv').textContent = (data.tong_don_hang || 0).toLocaleString('vi-VN');
            
            // Cập nhật phần trăm thay đổi
            if (data.so_sanh_ky_truoc) {
                const changeDoanhThu = data.so_sanh_ky_truoc.phan_tram_doanh_thu || 0;
                const changeVeBan = data.so_sanh_ky_truoc.phan_tram_ve_ban || 0;
                const changeDonHang = data.so_sanh_ky_truoc.phan_tram_don_hang || 0;
                
                updateChangeIndicator('tong-doanh-thu-nv-change', changeDoanhThu);
                updateChangeIndicator('tong-ve-ban-nv-change', changeVeBan);
                updateChangeIndicator('tong-don-hang-nv-change', changeDonHang);
            }
        }
    } catch (error) {
        console.error('Lỗi tải thống kê tổng quan:', error);
    }
}

// Tải xu hướng doanh thu
async function loadXuHuongDoanhThu() {
    try {
        const params = new URLSearchParams({
            tuNgay: tuNgay,
            denNgay: denNgay
        });

        const response = await fetch(`${baseUrl}/api/thong-ke-nhan-vien/xu-huong-doanh-thu?${params.toString()}`, {
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

        if (!result.success) {
            // Hiển thị thông báo lỗi trong biểu đồ
            if (revenueChart) {
                revenueChart.updateOptions({
                    noData: {
                        text: result.message || 'Không thể tải dữ liệu',
                        align: 'center',
                        verticalAlign: 'middle'
                    }
                });
            }
            return;
        }

        if (result.data && revenueChart) {
            const data = result.data;
            
            // Cập nhật biểu đồ xu hướng doanh thu
            if (data.chi_tiet_theo_ngay && Array.isArray(data.chi_tiet_theo_ngay)) {
                const categories = data.chi_tiet_theo_ngay.map(item => item.ngay_formatted);
                const doanhThuData = data.chi_tiet_theo_ngay.map(item => item.tong_doanh_thu || 0);
                
                revenueChart.updateOptions({
                    xaxis: {
                        categories: categories,
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
        }
    } catch (error) {
        console.error('Lỗi tải xu hướng doanh thu:', error);
        if (revenueChart) {
            revenueChart.updateOptions({
                noData: {
                    text: 'Không có dữ liệu',
                    align: 'center',
                    verticalAlign: 'middle'
                }
            });
        }
    }
}

// Tải top phim
async function loadTopPhim() {
    try {
        const params = new URLSearchParams({
            tuNgay: tuNgay,
            denNgay: denNgay
        });

        const response = await fetch(`${baseUrl}/api/thong-ke-nhan-vien/top5-phim?${params.toString()}`, {
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

        const topPhimList = document.getElementById('top-phim-list-nv');
        if (!topPhimList) return;

        if (!result.success) {
            topPhimList.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <p>${result.message || 'Không thể tải dữ liệu'}</p>
                </div>
            `;
            return;
        }

        if (result.data && result.data.danh_sach && result.data.danh_sach.length > 0) {
            const danhSachPhim = result.data.danh_sach;
            
            topPhimList.innerHTML = danhSachPhim.map((phim, index) => `
                <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex-shrink-0">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-green-500 text-white font-bold text-sm">
                            ${index + 1}
                        </span>
                    </div>
                    <div class="flex-shrink-0">
                        <img src="${window.config.urlServerMinio}/${phim.poster_url}" 
                             alt="${phim.ten_phim}" 
                             class="w-16 h-24 object-cover rounded"
                             >
                    </div>
                    <div class="flex-grow min-w-0">
                        <h4 class="font-semibold text-gray-800 truncate">${phim.ten_phim || 'N/A'}</h4>
                        <p class="text-sm text-gray-600 mt-1">
                            <span class="font-medium">${(phim.so_ve_ban || 0).toLocaleString('vi-VN')}</span> vé bán
                        </p>
                        <p class="text-sm text-green-600 font-semibold mt-1">
                            ${formatCurrency(phim.doanh_thu_ve || 0)}
                        </p>
                    </div>
                </div>
            `).join('');
        } else {
            topPhimList.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <p>Không có dữ liệu</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Lỗi tải top phim:', error);
        const topPhimList = document.getElementById('top-phim-list-nv');
        if (topPhimList) {
            topPhimList.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <p>Lỗi tải dữ liệu</p>
                </div>
            `;
        }
    }
}

// Hiển thị lỗi
function showError(elementId, message) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = 'N/A';
        const changeElement = document.getElementById(elementId + '-change');
        if (changeElement) {
            changeElement.textContent = message;
            changeElement.className = 'text-xs text-red-600 mt-2';
        }
    }
}

// Cập nhật chỉ báo thay đổi
function updateChangeIndicator(elementId, changePercent) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    if (changePercent === 0) {
        element.textContent = 'Không thay đổi';
        element.className = 'text-xs text-gray-500 mt-2';
    } else if (changePercent > 0) {
        element.textContent = `↑ ${Math.abs(changePercent).toFixed(1)}% so với kỳ trước`;
        element.className = 'text-xs text-green-600 mt-2';
    } else {
        element.textContent = `↓ ${Math.abs(changePercent).toFixed(1)}% so với kỳ trước`;
        element.className = 'text-xs text-red-600 mt-2';
    }
}

// Khởi tạo khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra ApexCharts đã load chưa
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts not loaded');
        return;
    }
    
    initializeCharts();
    loadStatistics();
});


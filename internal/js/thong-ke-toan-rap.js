// Import Spinner
import Spinner from './util/spinner.js';

document.addEventListener('DOMContentLoaded', function() {
    // Khai báo biến charts trong phạm vi hàm để tránh lỗi tham chiếu
    let revenueChart = null;
    let ticketsChart = null;
    let theaterPerformanceChart = null;
    let revenueBreakdownChart = null;
    let weeklyPerformanceChart = null;
    let hourlyPerformanceChart = null;
    let fnbPerTicketChart = null;
    
    // Charts for showtime statistics
    let revenueShowtimeChart = null;
    let ticketsShowtimeChart = null;
    let theaterPerformanceShowtimeChart = null;
    let revenueBreakdownShowtimeChart = null;
    
    // Time period for showtime charts
    let currentTimePeriodShowtime = 'daily';

    // Variables for date range filter
    const dateRangeSelector = document.getElementById('date-range');
    const dateStartInput = document.getElementById('date-start');
    const dateEndInput = document.getElementById('date-end');
    const customDateContainer = document.querySelector('.date-range-custom');
    const applyFilterBtn = document.getElementById('btn-apply-filter');
    const compareToggle = document.getElementById('toggle-compare');
    const cinemaFilter = document.getElementById('cinema-filter');
    
    // Initialize state variables BEFORE using them
    let currentDateRange = 7;
    let compareWithPrevious = false;
    let selectedCinema = 'all';
    let currentTimePeriod = 'daily';

    // Lưu dữ liệu thô và filter hiện tại để tránh fetch lại không cần thiết
    let cachedRawData = null;
    let cachedFilters = {
        tuNgay: null,
        denNgay: null,
        idRap: null
    };
    
    // Lưu dữ liệu suất chiếu để cập nhật biểu đồ
    let cachedShowtimeData = null;

    // Set up initial dates FIRST before fetching data
    const today = new Date();
    const sevenDaysAgo = new Date(today);
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
    
    // Format dates as YYYY-MM-DD
    const todayStr = today.toISOString().split('T')[0];
    const sevenDaysAgoStr = sevenDaysAgo.toISOString().split('T')[0];
    
    dateStartInput.value = sevenDaysAgoStr;
    dateEndInput.value = todayStr;

    console.log('Initial dates set:', { start: sevenDaysAgoStr, end: todayStr });

    // Bắt đầu khởi tạo dữ liệu khi trang đã tải xong
    try {
        initializeCharts();
        updateLastUpdateTime();
        // Sử dụng API dữ liệu thô duy nhất để tải tất cả dữ liệu
        updateAllData();
    } catch (error) {
        console.error('Lỗi khởi tạo:', error);
    }
    
    // Update last update time
    function updateLastUpdateTime() {
        const lastUpdateElement = document.getElementById('last-update');
        if (lastUpdateElement) {
            const now = new Date();
            const timeString = now.toLocaleTimeString('vi-VN', { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            });
            lastUpdateElement.textContent = timeString;
        }
    }
    
    // Update time every minute
    setInterval(updateLastUpdateTime, 60000);

    // Function to update date range filter options based on filter type
    function updateDateRangeFilter(isSuatChieu) {
        const dateRangeSelect = document.getElementById('date-range');
        if (!dateRangeSelect) return;
        
        // Store current value to restore if needed
        const currentValue = dateRangeSelect.value;
        
        // Clear existing options
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
            // Remove max date restriction for future dates
            if (dateStartInput) dateStartInput.removeAttribute('max');
            if (dateEndInput) dateEndInput.removeAttribute('max');
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
            // Set max date to today for past dates only
            const today = new Date().toISOString().split('T')[0];
            if (dateStartInput) dateStartInput.setAttribute('max', today);
            if (dateEndInput) dateEndInput.setAttribute('max', today);
        }
        
        // Set the selected value
        dateRangeSelect.value = dateRangeSelect.querySelector('option[selected]')?.value || dateRangeSelect.options[0].value;
        
        // Trigger change event to update dates (only if not initializing)
        if (dateRangeSelect.value !== currentValue || currentValue === '') {
            dateRangeSelect.dispatchEvent(new Event('change'));
        }
    }

    // Date range selector event
    dateRangeSelector.addEventListener('change', function() {
        if (this.value === 'custom') {
            customDateContainer.classList.remove('hidden');
        } else {
            customDateContainer.classList.add('hidden');
            const isFuture = this.value.endsWith('f');
            const days = parseInt(this.value.replace('f', ''));
            currentDateRange = days;
            
            // Tự động cập nhật ngày bắt đầu và kết thúc dựa trên số ngày được chọn
            const today = new Date();
            const endDate = new Date(today);
            const startDate = new Date(today);
            
            if (isFuture) {
                // Future dates: start from today, end in future
                startDate.setDate(startDate.getDate());
                endDate.setDate(endDate.getDate() + days);
            } else {
                // Past dates: start from past, end today
                startDate.setDate(startDate.getDate() - days);
                endDate.setDate(endDate.getDate());
            }
            
            // Format dates as YYYY-MM-DD
            dateStartInput.value = startDate.toISOString().split('T')[0];
            dateEndInput.value = endDate.toISOString().split('T')[0];
            
            // Tự động điều chỉnh time period dựa trên số ngày
            if (currentDateRange <= 7) {
                currentTimePeriod = 'daily';
            } else if (currentDateRange <= 60) {
                currentTimePeriod = 'weekly';
            } else {
                currentTimePeriod = 'monthly';
            }
            
            // Update active state cho time filter buttons
            updateTimeFilterButtons();
        }
    });

    // Apply filter event
    applyFilterBtn.addEventListener('click', function() {
        const oldTuNgay = dateStartInput.value;
        const oldDenNgay = dateEndInput.value;
        const oldCinema = selectedCinema;
        
        compareWithPrevious = compareToggle.checked;
        selectedCinema = cinemaFilter.value;

        if (dateRangeSelector.value === 'custom') {
            // Custom date range logic
            const startDate = new Date(dateStartInput.value);
            const endDate = new Date(dateEndInput.value);
            // Calculate difference in days
            const diffTime = Math.abs(endDate - startDate);
            currentDateRange = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            // Tự động điều chỉnh time period dựa trên số ngày
            if (currentDateRange <= 7) {
                currentTimePeriod = 'daily';
            } else if (currentDateRange <= 60) {
                currentTimePeriod = 'weekly';
            } else {
                currentTimePeriod = 'monthly';
            }
            
            // Update active state cho time filter buttons
            updateTimeFilterButtons();
        }
        
        // Kiểm tra xem có thay đổi thời gian không
        const thoiGianThayDoi = (oldTuNgay !== dateStartInput.value || oldDenNgay !== dateEndInput.value);
        const chiThayDoiRap = !thoiGianThayDoi && (oldCinema !== selectedCinema);
        
        // Chỉ fetch API nếu thời gian thay đổi, nếu chỉ thay đổi rạp thì xử lý lại dữ liệu đã có
        if (thoiGianThayDoi) {
            updateAllData(true); // true = cần fetch API
        } else if (chiThayDoiRap && cachedRawData) {
            // Chỉ thay đổi rạp, xử lý lại dữ liệu đã cache
            xuLyVaCapNhatUI(cachedRawData);
        } else {
            // Trường hợp khác vẫn fetch
            updateAllData(true);
        }
    });

    // Time period filter for charts
    const timeFilters = document.querySelectorAll('.time-filter');
    timeFilters.forEach(filter => {
        filter.addEventListener('click', function() {
            // Update active filter
            timeFilters.forEach(btn => {
                btn.classList.remove('filter-active');
                btn.classList.remove('bg-red-500', 'text-white', 'border-red-500');
                btn.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
            });
            this.classList.add('filter-active');
            this.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');
            this.classList.add('bg-red-500', 'text-white', 'border-red-500');
            
            // Update time period
            currentTimePeriod = this.getAttribute('data-period');
            
            // Update charts
            updateChartsByTimePeriod();
        });
    });
    
    // Time period filter for showtime charts
    const timeFiltersShowtime = document.querySelectorAll('.time-filter-suat-chieu');
    timeFiltersShowtime.forEach(filter => {
        filter.addEventListener('click', function() {
            // Update active filter
            timeFiltersShowtime.forEach(btn => {
                btn.classList.remove('filter-active');
                btn.classList.remove('bg-red-500', 'text-white', 'border-red-500');
                btn.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
            });
            this.classList.add('filter-active');
            this.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');
            this.classList.add('bg-red-500', 'text-white', 'border-red-500');
            
            // Update time period for showtime
            currentTimePeriodShowtime = this.getAttribute('data-period');
            
            // Update showtime charts with cached data if available
            if (cachedShowtimeData) {
                const tuNgay = dateStartInput.value;
                const denNgay = dateEndInput.value;
                
                // Filter theo rạp nếu cần
                let danhSachFiltered = cachedShowtimeData;
                if (selectedCinema !== 'all') {
                    const idRapFilter = parseInt(selectedCinema);
                    danhSachFiltered = cachedShowtimeData.filter(item => item.id_rap === idRapFilter);
                }
                
                const processedData = xuLyDuLieuSuatChieuChoBieuDo(danhSachFiltered, tuNgay, denNgay, currentTimePeriodShowtime);
                const hieuSuatTheoRap = tinhHieuSuatTheoRapTuSuatChieu(danhSachFiltered);
                const coCauDoanhThu = tinhCoCauDoanhThuTuSuatChieu(danhSachFiltered);
                
                // Cập nhật biểu đồ
                if (revenueShowtimeChart && processedData.xu_huong_doanh_thu) {
                    revenueShowtimeChart.updateOptions({
                        xaxis: { categories: processedData.xu_huong_doanh_thu.danh_sach_nhan }
                    });
                    revenueShowtimeChart.updateSeries([{
                        name: 'Doanh thu',
                        data: processedData.xu_huong_doanh_thu.chi_tiet
                    }]);
                }
                
                if (ticketsShowtimeChart && processedData.xu_huong_ve_ban) {
                    ticketsShowtimeChart.updateOptions({
                        xaxis: { categories: processedData.xu_huong_ve_ban.danh_sach_nhan }
                    });
                    ticketsShowtimeChart.updateSeries([{
                        name: 'Số vé bán',
                        data: processedData.xu_huong_ve_ban.chi_tiet
                    }]);
                }
                
                if (theaterPerformanceShowtimeChart) {
                    if (hieuSuatTheoRap && hieuSuatTheoRap.danh_sach_rap && hieuSuatTheoRap.danh_sach_rap.length > 0) {
                        theaterPerformanceShowtimeChart.updateOptions({
                            xaxis: { categories: hieuSuatTheoRap.danh_sach_rap.map(r => r.ten_rap) },
                            noData: { text: 'Đang tải dữ liệu...', align: 'center', verticalAlign: 'middle' }
                        });
                        theaterPerformanceShowtimeChart.updateSeries([{
                            name: 'Doanh thu',
                            data: hieuSuatTheoRap.danh_sach_rap.map(r => r.doanh_thu)
                        }]);
                    } else {
                        // Hiển thị biểu đồ trống khi không có dữ liệu (tắt noData message)
                        theaterPerformanceShowtimeChart.updateOptions({
                            xaxis: { categories: [] },
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
                        theaterPerformanceShowtimeChart.updateSeries([{
                            name: 'Doanh thu',
                            data: []
                        }]);
                    }
                }
                
                if (revenueBreakdownShowtimeChart && coCauDoanhThu) {
                    revenueBreakdownShowtimeChart.updateOptions({
                        labels: coCauDoanhThu.chi_tiet.map(item => item.loai),
                        colors: coCauDoanhThu.chi_tiet.map(item => item.mau_sac)
                    });
                    revenueBreakdownShowtimeChart.updateSeries(coCauDoanhThu.chi_tiet.map(item => item.phan_tram));
                }
            } else {
                // Nếu chưa có cache, fetch lại
                const tuNgay = dateStartInput.value;
                const denNgay = dateEndInput.value;
                capNhatThongKeDoanhThuTheoSuatChieu(tuNgay, denNgay);
            }
        });
    });

    // Function to update time filter buttons active state
    function updateTimeFilterButtons() {
        timeFilters.forEach(btn => {
            const period = btn.getAttribute('data-period');
            btn.classList.remove('filter-active');
            btn.classList.remove('bg-red-500', 'text-white', 'border-red-500');
            btn.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
            
            if (period === currentTimePeriod) {
                btn.classList.add('filter-active');
                btn.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');
                btn.classList.add('bg-red-500', 'text-white', 'border-red-500');
            }
        });
    }

    // Initialize time filter buttons on page load
    updateTimeFilterButtons();

    // Utility functions
    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('vi-VN').format(value);
    }

    function formatPercent(value) {
        return new Intl.NumberFormat('vi-VN', { style: 'percent', minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(value / 100);
    }

    function getRandomData(min, max, length) {
        return Array.from({ length }, () => Math.floor(Math.random() * (max - min + 1)) + min);
    }

    function getDates(days) {
        const dates = [];
        const today = new Date();
        
        for (let i = days; i >= 0; i--) {
            const date = new Date(today);
            date.setDate(date.getDate() - i);
            dates.push(date.toISOString().split('T')[0]);
        }
        
        return dates;
    }

    function showToast(message) {
        // Create toast element if it doesn't exist
        let toast = document.getElementById('toast-notification');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast-notification';
            toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg transform transition-all duration-300 translate-y-20 opacity-0';
            document.body.appendChild(toast);
        }
        
        // Set message and show
        toast.textContent = message;
        toast.classList.remove('translate-y-20', 'opacity-0');
        
        // Hide after 3 seconds
        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0');
        }, 3000);
    }

    // KPI Cards Functions - đã được xử lý trong capNhatUI()

    function hideTrendIndicators() {
        const trendElements = ['revenue-trend', 'tickets-trend', 'occupancy-trend', 'fnb-trend'];
        trendElements.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.style.display = 'none';
            }
        });
    }

    function updateTrendIndicator(elementId, changePercent) {
        const element = document.getElementById(elementId);
        if (!element) return;

        // Show the element
        element.style.display = 'inline-flex';

        const iconElement = element.querySelector('svg');
        const textElement = element.querySelector('span');
        
        const percentValue = parseFloat(changePercent);
        textElement.textContent = `${percentValue > 0 ? '+' : ''}${percentValue.toFixed(1)}%`;
        
        // Remove all existing classes
        element.className = 'inline-flex items-center text-xs font-bold px-3 py-1.5 rounded-full border';
        
        if (percentValue > 0) {
            // Positive trend - green
            element.classList.add('bg-green-100', 'text-green-700', 'border-green-300');
            iconElement.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />';
        } else if (percentValue < 0) {
            // Negative trend - red
            element.classList.add('bg-red-100', 'text-red-700', 'border-red-300');
            iconElement.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />';
        } else {
            // No change - gray
            element.classList.add('bg-gray-100', 'text-gray-700', 'border-gray-300');
            iconElement.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14" />';
        }
    }

    // Charts Initialization Functions
    function initializeRevenueChart() {
        const chartElement = document.querySelector("#revenue-chart");
        if (!chartElement) {
            console.error("Revenue chart container not found");
            return;
        }

        // Khởi tạo biểu đồ với dữ liệu trống
        const options = {
            series: [{
                name: 'Doanh thu',
                data: []
            }],
            chart: {
                type: 'area',
                height: 350,
                zoom: {
                    enabled: true
                },
                toolbar: {
                    show: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            colors: ['#EF4444'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 100]
                }
            },
            xaxis: {
                categories: [],
                labels: {
                    formatter: function(value) {
                        return value; // Đã format từ API
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function(value) {
                        // Tự động chọn đơn vị phù hợp
                        if (value >= 1000000) {
                            return (value / 1000000).toFixed(1) + ' tr';
                        } else if (value >= 1000) {
                            return (value / 1000).toFixed(0) + ' k';
                        } else {
                            return value.toFixed(0);
                        }
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
                verticalAlign: 'middle',
                style: {
                    fontSize: '16px',
                    color: '#999'
                }
            }
        };
        
        // Khởi tạo biểu đồ
        revenueChart = new ApexCharts(chartElement, options);
        revenueChart.render();
    }

    function initializeTicketsChart() {
        const chartElement = document.querySelector("#tickets-chart");
        if (!chartElement) {
            console.error("Tickets chart container not found");
            return;
        }

        // Khởi tạo biểu đồ với dữ liệu trống
        const options = {
            series: [{
                name: 'Số vé bán',
                data: []
            }],
            chart: {
                type: 'area',
                height: 350,
                zoom: {
                    enabled: true
                },
                toolbar: {
                    show: false
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
                    opacityTo: 0.2,
                    stops: [0, 100]
                }
            },
            xaxis: {
                categories: [],
                labels: {
                    formatter: function(value) {
                        return value; // Đã format từ API
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return formatNumber(value) + ' vé';
                    }
                }
            },
            noData: {
                text: 'Đang tải dữ liệu...',
                align: 'center',
                verticalAlign: 'middle',
                style: {
                    fontSize: '16px',
                    color: '#999'
                }
            }
        };
        
        // Khởi tạo biểu đồ
        ticketsChart = new ApexCharts(chartElement, options);
        ticketsChart.render();
    }

    function initializeTheaterPerformanceChart() {
        const chartElement = document.querySelector("#theater-performance-chart");
        if (!chartElement) {
            console.error("Theater performance chart container not found");
            return;
        }

        // Khởi tạo biểu đồ với dữ liệu trống
        const options = {
            series: [{
                name: 'Doanh thu',
                data: []
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            colors: ['#10B981'],
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                },
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: [],
            },
            yaxis: {
                title: {
                    text: 'Doanh thu (tỷ đồng)'
                },
                labels: {
                    formatter: function(value) {
                        return (value / 1000000000).toFixed(1);
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
                verticalAlign: 'middle',
                style: {
                    fontSize: '16px',
                    color: '#999'
                }
            }
        };
        
        theaterPerformanceChart = new ApexCharts(chartElement, options);
        theaterPerformanceChart.render();
    }

    function initializeRevenueBreakdownChart() {
        const chartElement = document.querySelector("#revenue-breakdown-chart");
        if (!chartElement) {
            console.error("Revenue breakdown chart container not found");
            return;
        }

        const options = {
            series: [],
            chart: {
                type: 'donut',
                height: 350
            },
            labels: [],
            colors: ['#EF4444', '#F59E0B'], // Chỉ 2 màu: Đỏ (Vé phim) và Cam (F&B)
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }],
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value.toFixed(1) + '%';
                    }
                }
            },
            noData: {
                text: 'Đang tải dữ liệu...',
                align: 'center',
                verticalAlign: 'middle',
                style: {
                    fontSize: '16px',
                    color: '#999'
                }
            }
        };
        
        revenueBreakdownChart = new ApexCharts(chartElement, options);
        revenueBreakdownChart.render();
    }

    function initializeWeeklyPerformanceChart() {
        const chartElement = document.querySelector("#weekly-performance-chart");
        if (!chartElement) {
            console.error("Weekly performance chart container not found");
            return;
        }

        const options = {
            series: [{
                name: 'Vé bán',
                type: 'column',
                data: []
            }, {
                name: 'Tỷ lệ lấp đầy',
                type: 'line',
                data: []
            }],
            chart: {
                height: 350,
                type: 'line',
                stacked: false,
                toolbar: {
                    show: false
                }
            },
            stroke: {
                width: [0, 3],
                curve: 'smooth'
            },
            plotOptions: {
                bar: {
                    columnWidth: '50%'
                }
            },
            colors: ['#10B981', '#F59E0B'],
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
                categories: ['Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy', 'Chủ Nhật'],
            },
            yaxis: [{
                title: {
                    text: 'Vé bán',
                },
                seriesName: 'Vé bán'
            }, {
                opposite: true,
                title: {
                    text: 'Tỷ lệ lấp đầy (%)'
                },
                seriesName: 'Tỷ lệ lấp đầy',
                min: 0,
                max: 100
            }],
            tooltip: {
                shared: true,
                intersect: false,
                y: [{
                    formatter: function(y) {
                        return formatNumber(y) + " vé";
                    }
                }, {
                    formatter: function(y) {
                        return y + "%";
                    }
                }]
            },
            noData: {
                text: 'Đang tải dữ liệu...',
                align: 'center',
                verticalAlign: 'middle',
                style: {
                    fontSize: '16px',
                    color: '#999'
                }
            }
        };
        
        weeklyPerformanceChart = new ApexCharts(chartElement, options);
        weeklyPerformanceChart.render();
        
        // Load dữ liệu ngay lập tức
        fetchWeeklyPerformanceData();
    }

    function initializeHourlyPerformanceChart() {
        const chartElement = document.querySelector("#hourly-performance-chart");
        if (!chartElement) {
            console.error("Hourly performance chart container not found");
            return;
        }

        const options = {
            series: [{
                name: 'Tỷ lệ lấp đầy',
                data: []
            }],
            chart: {
                type: 'area',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            colors: ['#8B5CF6'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 100]
                }
            },
            xaxis: {
                categories: []
            },
            yaxis: {
                title: {
                    text: 'Tỷ lệ lấp đầy (%)'
                },
                min: 0,
                max: 100
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value + '%';
                    }
                }
            },
            noData: {
                text: 'Đang tải dữ liệu...',
                align: 'center',
                verticalAlign: 'middle',
                style: {
                    fontSize: '16px',
                    color: '#999'
                }
            }
        };
        
        hourlyPerformanceChart = new ApexCharts(chartElement, options);
        hourlyPerformanceChart.render();
        
        // Load dữ liệu ngay lập tức
        fetchHourlyPerformanceData();
    }

    function initializeFnBPerTicketChart() {
        const chartElement = document.querySelector("#fnb-per-ticket-chart");
        if (!chartElement) {
            console.error("F&B per ticket chart container not found");
            return;
        }

        // Khởi tạo biểu đồ với dữ liệu trống
        const options = {
            series: [{
                name: 'F&B/Đơn hàng',
                data: []
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '70%',
                }
            },
            colors: ['#F59E0B'],
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: [],
                labels: {
                    rotate: -45,
                    rotateAlways: false,
                    hideOverlappingLabels: true,
                    trim: true,
                    style: {
                        fontSize: '11px'
                    }
                },
                tickPlacement: 'on'
            },
            yaxis: {
                title: {
                    text: 'VNĐ/đơn hàng'
                },
                labels: {
                    formatter: function(value) {
                        if (value >= 1000000) {
                            return (value / 1000000).toFixed(1) + 'M';
                        } else if (value >= 1000) {
                            return (value / 1000).toFixed(0) + 'K';
                        }
                        return value.toFixed(0);
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return formatCurrency(value) + '/đơn hàng';
                    }
                }
            },
            noData: {
                text: 'Đang tải dữ liệu...',
                align: 'center',
                verticalAlign: 'middle',
                style: {
                    fontSize: '16px',
                    color: '#999'
                }
            }
        };
        
        fnbPerTicketChart = new ApexCharts(chartElement, options);
        fnbPerTicketChart.render();
    }

    // Initialize charts for showtime statistics
    function initializeRevenueShowtimeChart() {
        const chartElement = document.querySelector("#revenue-showtime-chart");
        if (!chartElement) return;

        const options = {
            series: [{
                name: 'Doanh thu',
                data: []
            }],
            chart: {
                type: 'area',
                height: 350,
                zoom: { enabled: true },
                toolbar: { show: false }
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            colors: ['#EF4444'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 100]
                }
            },
            xaxis: { categories: [] },
            yaxis: {
                labels: {
                    formatter: function(value) {
                        if (value >= 1000000) return (value / 1000000).toFixed(1) + ' tr';
                        if (value >= 1000) return (value / 1000).toFixed(0) + ' k';
                        return value.toFixed(0);
                    }
                }
            },
            tooltip: {
                y: { formatter: function(value) { return formatCurrency(value); } }
            },
            noData: { text: 'Đang tải dữ liệu...', align: 'center', verticalAlign: 'middle' }
        };
        
        revenueShowtimeChart = new ApexCharts(chartElement, options);
        revenueShowtimeChart.render();
    }

    function initializeTicketsShowtimeChart() {
        const chartElement = document.querySelector("#tickets-showtime-chart");
        if (!chartElement) return;

        const options = {
            series: [{
                name: 'Số vé bán',
                data: []
            }],
            chart: {
                type: 'area',
                height: 350,
                zoom: { enabled: true },
                toolbar: { show: false }
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            colors: ['#3B82F6'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 100]
                }
            },
            xaxis: { categories: [] },
            tooltip: {
                y: { formatter: function(value) { return formatNumber(value) + ' vé'; } }
            },
            noData: { text: 'Đang tải dữ liệu...', align: 'center', verticalAlign: 'middle' }
        };
        
        ticketsShowtimeChart = new ApexCharts(chartElement, options);
        ticketsShowtimeChart.render();
    }

    function initializeTheaterPerformanceShowtimeChart() {
        const chartElement = document.querySelector("#theater-performance-showtime-chart");
        if (!chartElement) return;

        const options = {
            series: [{
                name: 'Doanh thu',
                data: []
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false }
            },
            colors: ['#10B981'],
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                }
            },
            dataLabels: { enabled: false },
            xaxis: { categories: [] },
            yaxis: {
                title: { text: 'Doanh thu (tỷ đồng)' },
                labels: {
                    formatter: function(value) {
                        return (value / 1000000000).toFixed(1);
                    }
                }
            },
            tooltip: {
                y: { formatter: function(value) { return formatCurrency(value); } }
            },
            noData: { text: 'Đang tải dữ liệu...', align: 'center', verticalAlign: 'middle' }
        };
        
        theaterPerformanceShowtimeChart = new ApexCharts(chartElement, options);
        theaterPerformanceShowtimeChart.render();
    }

    function initializeRevenueBreakdownShowtimeChart() {
        const chartElement = document.querySelector("#revenue-breakdown-showtime-chart");
        if (!chartElement) return;

        const options = {
            series: [],
            chart: { type: 'donut', height: 350 },
            labels: [],
            colors: ['#EF4444', '#F59E0B'],
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: { width: 200 },
                    legend: { position: 'bottom' }
                }
            }],
            tooltip: {
                y: { formatter: function(value) { return value.toFixed(1) + '%'; } }
            },
            noData: { text: 'Đang tải dữ liệu...', align: 'center', verticalAlign: 'middle' }
        };
        
        revenueBreakdownShowtimeChart = new ApexCharts(chartElement, options);
        revenueBreakdownShowtimeChart.render();
    }

    // Master initialization function
    function initializeCharts() {
        // Wrap each initialization in try-catch to prevent errors from stopping initialization of other charts
        try { initializeRevenueChart(); } catch (e) { console.error("Error initializing revenue chart:", e); }
        try { initializeTicketsChart(); } catch (e) { console.error("Error initializing tickets chart:", e); }
        try { initializeTheaterPerformanceChart(); } catch (e) { console.error("Error initializing theater performance chart:", e); }
        try { initializeRevenueBreakdownChart(); } catch (e) { console.error("Error initializing revenue breakdown chart:", e); }
        // try { initializeWeeklyPerformanceChart(); } catch (e) { console.error("Error initializing weekly performance chart:", e); }
        // try { initializeHourlyPerformanceChart(); } catch (e) { console.error("Error initializing hourly performance chart:", e); }
        try { initializeFnBPerTicketChart(); } catch (e) { console.error("Error initializing F&B per ticket chart:", e); }
        
        // Initialize showtime charts
        try { initializeRevenueShowtimeChart(); } catch (e) { console.error("Error initializing revenue showtime chart:", e); }
        try { initializeTicketsShowtimeChart(); } catch (e) { console.error("Error initializing tickets showtime chart:", e); }
        try { initializeTheaterPerformanceShowtimeChart(); } catch (e) { console.error("Error initializing theater performance showtime chart:", e); }
        try { initializeRevenueBreakdownShowtimeChart(); } catch (e) { console.error("Error initializing revenue breakdown showtime chart:", e); }
    }

    // Update all data - sử dụng API dữ liệu thô duy nhất
    async function updateAllData(needFetch = true) {
        try {
            // Hiển thị spinner toàn trang
            const mainContainer = document.querySelector('.bg-gradient-to-r.from-red-500');
            const spinner = mainContainer ? Spinner.show({
                target: mainContainer.parentElement,
                text: needFetch ? 'Đang tải dữ liệu...' : 'Đang xử lý dữ liệu...',
                size: 'lg',
                overlay: true
            }) : null;

            // Lấy tham số filter
            const tuNgay = dateStartInput.value;
            const denNgay = dateEndInput.value;
            const idRap = selectedCinema;
            
            let rawData = null;
            
            // Chỉ fetch API nếu cần thiết (thời gian thay đổi)
            if (needFetch) {
                // Build API URL cho API dữ liệu thô
            const params = new URLSearchParams({
                tuNgay: tuNgay,
                    denNgay: denNgay
            });

            const baseUrl = document.getElementById('btn-apply-filter').dataset.url || '';
                const apiUrl = `${baseUrl}/api/thong-ke-toan-rap/du-lieu-tho?${params.toString()}`;

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
                    
                    // Cache dữ liệu và filter hiện tại
                    cachedRawData = rawData;
                    cachedFilters = {
                        tuNgay: tuNgay,
                        denNgay: denNgay,
                        idRap: 'all'
                    };
                    } else {
                    throw new Error(result.message || 'Không thể tải dữ liệu');
                }
                                } else {
                // Sử dụng dữ liệu đã cache
                rawData = cachedRawData;
                if (!rawData) {
                    // Nếu không có cache, vẫn phải fetch
                    if (spinner) Spinner.hide(spinner);
                    return updateAllData(true);
                }
            }
            
            // Xử lý và cập nhật UI
            xuLyVaCapNhatUI(rawData);
            
            // Fetch và hiển thị thống kê doanh thu theo suất chiếu
            await capNhatThongKeDoanhThuTheoSuatChieu(tuNgay, denNgay);

            if (spinner) {
            Spinner.hide(spinner);
        }
        } catch (error) {
            console.error('Error updating all data:', error);
            showToast('Lỗi khi tải dữ liệu: ' + error.message);
        }
    }
    
    /**
     * Xử lý dữ liệu thô và cập nhật UI (có thể filter theo rạp)
     */
    function xuLyVaCapNhatUI(rawData) {
        // Filter dữ liệu theo rạp nếu cần (nếu selectedCinema !== 'all')
        let filteredData = rawData;
        
        if (selectedCinema !== 'all') {
            const idRapFilter = parseInt(selectedCinema);
            
            // Filter vé theo rạp
            filteredData = {
                ...rawData,
                du_lieu_ve: rawData.du_lieu_ve.filter(ve => ve.id_rapphim === idRapFilter),
                du_lieu_ve_ky_truoc: rawData.du_lieu_ve_ky_truoc ? 
                    rawData.du_lieu_ve_ky_truoc.filter(ve => ve.id_rapphim === idRapFilter) : [],
                du_lieu_chi_tiet_don_hang: rawData.du_lieu_chi_tiet_don_hang.filter(item => item.id_rapphim === idRapFilter),
                du_lieu_don_hang: rawData.du_lieu_don_hang ? 
                    rawData.du_lieu_don_hang.filter(dh => {
                        // Filter đơn hàng có vé thuộc rạp này
                        return rawData.du_lieu_ve.some(ve => 
                            ve.donhang_id === dh.id && ve.id_rapphim === idRapFilter
                        );
                    }) : [],
                du_lieu_suat_chieu: rawData.du_lieu_suat_chieu.filter(sc => sc.id_rapphim === idRapFilter)
            };
        }
        
        // Xử lý và tổng hợp dữ liệu thô thành các format cần thiết
        const processedData = xuLyDuLieuTho(filteredData, compareWithPrevious, currentTimePeriod);
        
        // Cập nhật UI với dữ liệu đã xử lý
        capNhatUI(processedData);
        
        // Show a toast notification
        showToast('Dữ liệu đã được cập nhật');
    }

    function updateChartsByTimePeriod() {
        // Update charts based on time period selection - không cần fetch lại, chỉ xử lý lại dữ liệu đã có
        if (cachedRawData) {
            xuLyVaCapNhatUI(cachedRawData);
                    } else {
            // Nếu chưa có cache, fetch lại
            updateAllData(true);
        }
    }

    // ========== XỬ LÝ DỮ LIỆU THÔ ==========
    // Các hàm fetch và update chart cũ đã được thay thế bởi capNhatUI() trong updateAllData()
    
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
            doanh_thu_phim: tinhDoanhThuPhim(du_lieu_ve, du_lieu_mua_phim)
        };
    }
    
    /**
     * Tính tổng quan KPI
     */
    function tinhTongQuat(duLieuVe, duLieuVeKyTruoc, duLieuChiTietDonHang, duLieuSuatChieu, soSanh) {
        // Tổng doanh thu vé
        const tongDoanhThuVe = duLieuVe.reduce((sum, ve) => sum + ve.gia_ve, 0);
        
        // Tổng doanh thu F&B (sử dụng thanh_tien nếu có, nếu không thì tính từ so_luong * gia_ban)
        const tongDoanhThuFnB = duLieuChiTietDonHang.reduce((sum, item) => 
            sum + (item.thanh_tien || (item.so_luong * item.gia_ban)), 0);
        
        const tongDoanhThu = tongDoanhThuVe + tongDoanhThuFnB;
        
        // Tổng vé bán
        const tongVeBan = duLieuVe.length;
        
        // Tỉ lệ lấp đầy
        const tongGhe = duLieuSuatChieu.reduce((sum, sc) => sum + sc.so_ghe, 0);
        const tyLeLapDay = tongGhe > 0 ? (tongVeBan / tongGhe) * 100 : 0;
        
        // So sánh với kỳ trước
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
                ty_le_lap_day_phan_tram_thay_doi: 0, // Cần tính thêm
                fnb_phan_tram_thay_doi: 0 // Cần tính thêm
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
     * Tính xu hướng doanh thu
     */
    function tinhXuHuongDoanhThu(duLieuVe, duLieuChiTietDonHang, thoiGian, loaiXuHuong) {
        const tuNgay = new Date(thoiGian.tu_ngay + 'T00:00:00');
        const denNgay = new Date(thoiGian.den_ngay + 'T23:59:59');
        
        // Nhóm dữ liệu theo ngày
        const dataByDate = {};
        
        duLieuVe.forEach(ve => {
            // Xử lý ngay_tao có thể là datetime string hoặc date string
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
            // Xử lý ngay_dat có thể là datetime string hoặc date string
            const ngayDat = item.ngay_dat ? new Date(item.ngay_dat) : null;
            if (ngayDat && !isNaN(ngayDat.getTime())) {
                const ngay = ngayDat.toISOString().split('T')[0];
                if (!dataByDate[ngay]) {
                    dataByDate[ngay] = { doanh_thu_ve: 0, doanh_thu_fnb: 0 };
                }
                dataByDate[ngay].doanh_thu_fnb += item.thanh_tien || (item.so_luong * item.gia_ban) || 0;
            }
        });
        
        // Xử lý theo loại xu hướng
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
            // Xử lý theo tuần
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
            // Monthly - tương tự
            // Implementation tương tự weekly
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
        // Tương tự xu hướng doanh thu nhưng đếm số vé
        const tuNgay = new Date(thoiGian.tu_ngay + 'T00:00:00');
        const denNgay = new Date(thoiGian.den_ngay + 'T23:59:59');
        
        const dataByDate = {};
        duLieuVe.forEach(ve => {
            // Xử lý ngay_tao có thể là datetime string hoặc date string
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
            // Xử lý theo tuần
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
                chiTiet.push({ 
                    so_ve_ban: tongVeBan 
                });
                
                currentDate.setDate(currentDate.getDate() + 7);
                weekNumber++;
            }
        } else {
            // Monthly - xử lý theo tháng
            const currentDate = new Date(tuNgay);
            while (currentDate <= denNgay) {
                const monthStart = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
                const monthEnd = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
                
                // Giới hạn trong khoảng tuNgay - denNgay
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
                chiTiet.push({ 
                    so_ve_ban: tongVeBan 
                });
                
                // Chuyển sang tháng tiếp theo
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
        // Nhóm theo ngày và tính trung bình F&B trên mỗi đơn hàng
        const dataByDate = {};
        
        duLieuChiTietDonHang.forEach(item => {
            const ngay = new Date(item.ngay_dat).toISOString().split('T')[0];
            if (!dataByDate[ngay]) {
                dataByDate[ngay] = { tong_fnb: 0, so_don_hang: 0 };
            }
            dataByDate[ngay].tong_fnb += item.thanh_tien || (item.so_luong * item.gia_ban);
        });
        
        // Đếm số đơn hàng theo ngày
        const donHangByDate = {};
        duLieuDonHang.forEach(dh => {
            const ngay = new Date(dh.ngay_dat).toISOString().split('T')[0];
            donHangByDate[ngay] = (donHangByDate[ngay] || 0) + 1;
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
        document.getElementById('total-revenue').textContent = formatCurrency(kpi.tong_doanh_thu);
        document.getElementById('total-tickets').textContent = formatNumber(kpi.tong_ve_ban);
        document.getElementById('avg-occupancy').textContent = formatPercent(kpi.ty_le_lap_day);
        document.getElementById('fnb-revenue').textContent = formatCurrency(kpi.doanh_thu_fnb);
        
        if (kpi.so_sanh) {
            updateTrendIndicator('revenue-trend', kpi.so_sanh.doanh_thu_phan_tram_thay_doi);
            updateTrendIndicator('tickets-trend', kpi.so_sanh.ve_phan_tram_thay_doi);
            updateTrendIndicator('occupancy-trend', kpi.so_sanh.ty_le_lap_day_phan_tram_thay_doi);
            updateTrendIndicator('fnb-trend', kpi.so_sanh.fnb_phan_tram_thay_doi);
                    } else {
            hideTrendIndicators();
        }
        
        // Cập nhật charts
        if (revenueChart) {
            const revenueData = processedData.xu_huong_doanh_thu;
            if (revenueData && revenueData.danh_sach_nhan && revenueData.danh_sach_nhan.length > 0) {
                revenueChart.updateOptions({
                    xaxis: { categories: revenueData.danh_sach_nhan }
                });
                revenueChart.updateSeries([{
                    name: 'Doanh thu',
                    data: revenueData.chi_tiet.map(item => item.tong_doanh_thu || 0)
                }]);
            } else {
                // Nếu không có dữ liệu, hiển thị biểu đồ trống
                revenueChart.updateOptions({
                    xaxis: { categories: [] }
                });
                revenueChart.updateSeries([{
                    name: 'Doanh thu',
                    data: []
                }]);
            }
        }
        
        if (ticketsChart) {
            const ticketsData = processedData.xu_huong_ve_ban;
            if (ticketsData && ticketsData.danh_sach_nhan && ticketsData.danh_sach_nhan.length > 0) {
                ticketsChart.updateOptions({
                    xaxis: { categories: ticketsData.danh_sach_nhan }
                });
                ticketsChart.updateSeries([{
                    name: 'Số vé bán',
                    data: ticketsData.chi_tiet.map(item => item.so_ve_ban || 0)
                }]);
            } else {
                // Nếu không có dữ liệu, hiển thị biểu đồ trống
                ticketsChart.updateOptions({
                    xaxis: { categories: [] }
                });
                ticketsChart.updateSeries([{
                    name: 'Số vé bán',
                    data: []
                }]);
            }
        }
        
        if (theaterPerformanceChart) {
            const theaterData = processedData.hieu_suat_theo_rap;
            theaterPerformanceChart.updateOptions({
                xaxis: { categories: theaterData.danh_sach_rap.map(r => r.ten_rap) }
            });
            theaterPerformanceChart.updateSeries([{
                name: 'Doanh thu',
                data: theaterData.danh_sach_rap.map(r => r.doanh_thu)
            }]);
        }
        
        if (revenueBreakdownChart) {
            const breakdownData = processedData.co_cau_doanh_thu;
            revenueBreakdownChart.updateOptions({
                labels: breakdownData.chi_tiet.map(item => item.loai),
                colors: breakdownData.chi_tiet.map(item => item.mau_sac)
            });
            revenueBreakdownChart.updateSeries(breakdownData.chi_tiet.map(item => item.phan_tram));
        }
        
        if (fnbPerTicketChart) {
            const fnbData = processedData.ti_le_fnb_tren_don_hang;
            fnbPerTicketChart.updateOptions({
                xaxis: { categories: fnbData.danh_sach.map(item => item.ngay) }
            });
            fnbPerTicketChart.updateSeries([{
                name: 'F&B/Đơn hàng',
                data: fnbData.danh_sach.map(item => item.trung_binh_fnb_tren_don_hang)
            }]);
        }
        
        // Cập nhật tables
        const topFilmsTable = document.getElementById('top-films-table');
        if (topFilmsTable) {
            const topFilms = processedData.top_10_phim.danh_sach;
            topFilmsTable.innerHTML = topFilms.map(film => `
                <tr>
                        <td class="px-3 py-2">
                            <div class="text-sm font-medium text-gray-900">${film.ten_phim}</div>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="text-sm text-gray-900">${formatCurrency(film.doanh_thu)}</div>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="text-sm text-gray-900">${formatNumber(film.so_ve_ban)}</div>
                        </td>
                </tr>
            `).join('');
        }
        
        const topFnBTable = document.getElementById('top-fnb-table');
        if (topFnBTable) {
            const topFnB = processedData.top_10_san_pham_ban_chay.danh_sach;
            topFnBTable.innerHTML = topFnB.map((item, index) => `
                <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-3 py-2">
                            <div class="flex items-center">
                                <span class="text-sm font-bold text-gray-500 mr-3">${index + 1}</span>
                                <div class="text-sm font-medium text-gray-900">${item.ten_san_pham}</div>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="text-sm text-gray-900">${formatNumber(item.so_luong)}</div>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="text-sm font-semibold text-gray-900">${formatCurrency(item.doanh_thu)}</div>
                        </td>
                </tr>
            `).join('');
        }
        
        const allFilmsTable = document.getElementById('all-film-revenue-body');
        if (allFilmsTable) {
            const allFilms = processedData.doanh_thu_phim.danh_sach;
            const urlMinio = allFilmsTable.dataset.urlminio || '';
            allFilmsTable.innerHTML = allFilms.map(phim => `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-700">${phim.id || ''}</td>
                    <td class="px-4 py-3">
                        ${phim.poster_url ? `<img src="${urlMinio}/${phim.poster_url}" alt="" class="h-12 w-8 object-cover rounded">` : ''}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-800">${phim.ten_phim}</td>
                    <td class="px-4 py-3 text-sm text-gray-800 text-right">${formatCurrency(phim.doanh_thu_ve || 0)}</td>
                    <td class="px-4 py-3 text-sm text-gray-900 font-bold text-right">${formatCurrency(phim.tong_doanh_thu || 0)}</td>
                </tr>
            `).join('');
        }
    }

    /**
     * Xử lý dữ liệu suất chiếu để tạo biểu đồ
     */
    function xuLyDuLieuSuatChieuChoBieuDo(danhSachSuatChieu, tuNgay, denNgay, loaiXuHuong) {
        const tuNgayDate = new Date(tuNgay + 'T00:00:00');
        const denNgayDate = new Date(denNgay + 'T23:59:59');
        
        // Nhóm dữ liệu theo ngày bắt đầu suất chiếu
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
        
        // Xử lý theo loại xu hướng
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

    /**
     * Cập nhật thống kê doanh thu theo suất chiếu
     */
    async function capNhatThongKeDoanhThuTheoSuatChieu(tuNgay, denNgay) {
        try {
            const baseUrl = document.getElementById('btn-apply-filter').dataset.url || '';
            const params = new URLSearchParams({
                tuNgay: tuNgay,
                denNgay: denNgay
            });
            const apiUrl = `${baseUrl}/api/thong-ke-toan-rap/doanh-thu-theo-suat-chieu?${params.toString()}`;

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
                
                // Cache dữ liệu suất chiếu gốc (trước khi filter theo rạp)
                cachedShowtimeData = data.danh_sach || [];
                
                // Filter theo rạp nếu cần
                let danhSach = data.danh_sach || [];
                if (selectedCinema !== 'all') {
                    const idRapFilter = parseInt(selectedCinema);
                    danhSach = danhSach.filter(item => item.id_rap === idRapFilter);
                }

                // Cập nhật bảng
                if (tableBody) {
                    if (danhSach.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500">
                                    Không có dữ liệu suất chiếu trong khoảng thời gian này
                                </td>
                            </tr>
                        `;
                    } else {
                        tableBody.innerHTML = danhSach.map(item => {
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
                
                // Xử lý và cập nhật biểu đồ (sử dụng danhSach đã filter theo rạp)
                const processedData = xuLyDuLieuSuatChieuChoBieuDo(danhSach, tuNgay, denNgay, currentTimePeriodShowtime);
                const hieuSuatTheoRap = tinhHieuSuatTheoRapTuSuatChieu(danhSach);
                const coCauDoanhThu = tinhCoCauDoanhThuTuSuatChieu(danhSach);
                
                // Cập nhật biểu đồ doanh thu
                if (revenueShowtimeChart && processedData.xu_huong_doanh_thu) {
                    revenueShowtimeChart.updateOptions({
                        xaxis: { categories: processedData.xu_huong_doanh_thu.danh_sach_nhan }
                    });
                    revenueShowtimeChart.updateSeries([{
                        name: 'Doanh thu',
                        data: processedData.xu_huong_doanh_thu.chi_tiet
                    }]);
                }
                
                // Cập nhật biểu đồ vé bán
                if (ticketsShowtimeChart && processedData.xu_huong_ve_ban) {
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
                    if (hieuSuatTheoRap && hieuSuatTheoRap.danh_sach_rap && hieuSuatTheoRap.danh_sach_rap.length > 0) {
                        theaterPerformanceShowtimeChart.updateOptions({
                            xaxis: { categories: hieuSuatTheoRap.danh_sach_rap.map(r => r.ten_rap) },
                            noData: { text: 'Đang tải dữ liệu...', align: 'center', verticalAlign: 'middle' }
                        });
                        theaterPerformanceShowtimeChart.updateSeries([{
                            name: 'Doanh thu',
                            data: hieuSuatTheoRap.danh_sach_rap.map(r => r.doanh_thu)
                        }]);
                    } else {
                        // Hiển thị biểu đồ trống khi không có dữ liệu (tắt noData message)
                        theaterPerformanceShowtimeChart.updateOptions({
                            xaxis: { categories: [] },
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
                        theaterPerformanceShowtimeChart.updateSeries([{
                            name: 'Doanh thu',
                            data: []
                        }]);
                    }
                }
                
                // Cập nhật biểu đồ cơ cấu doanh thu
                if (revenueBreakdownShowtimeChart && coCauDoanhThu) {
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

    // Toggle buttons for Order and Showtime statistics
    const toggleDonHangBtn = document.getElementById('toggle-don-hang');
    const toggleSuatChieuBtn = document.getElementById('toggle-suat-chieu');
    
    // Function to update toggle button state
    function updateToggleButton(button, isActive, activeClasses, inactiveClasses) {
        // Remove all classes first
        button.classList.remove(...activeClasses, ...inactiveClasses);
        
        if (isActive) {
            button.classList.add(...activeClasses);
        } else {
            button.classList.add(...inactiveClasses);
        }
    }
    
    // Function to toggle sections visibility
    function toggleSections(selector, isActive) {
        const sections = document.querySelectorAll(selector);
        const affectedRows = new Set(); // Track rows that need mb-10 adjustment
        
        sections.forEach(section => {
            if (isActive) {
                section.classList.remove('hidden');
            } else {
                section.classList.add('hidden');
            }
            
            // Tìm parent element có class mb-10 (thường là div chứa grid)
            let parent = section.parentElement;
            while (parent && parent !== document.body) {
                if (parent.classList.contains('mb-10')) {
                    affectedRows.add(parent);
                    break;
                }
                parent = parent.parentElement;
            }
        });
        
        // Xử lý class mb-10 cho các hàng bị ảnh hưởng
        affectedRows.forEach(row => {
            if (isActive) {
                // Khi hiện lại, kiểm tra xem có section nào trong hàng còn visible không
                const hasVisibleChild = Array.from(row.children).some(child => 
                    !child.classList.contains('hidden')
                );
                if (hasVisibleChild && !row.classList.contains('mb-10')) {
                    row.classList.add('mb-10');
                }
            } else {
                // Khi ẩn, kiểm tra xem tất cả children có bị ẩn không
                const allChildrenHidden = Array.from(row.children).every(child => 
                    child.classList.contains('hidden')
                );
                if (allChildrenHidden && row.classList.contains('mb-10')) {
                    row.classList.remove('mb-10');
                }
            }
        });
    }
    
    // Function to sync toggle states
    function syncToggleStates() {
        const donHangActive = toggleDonHangBtn && toggleDonHangBtn.classList.contains('active');
        const suatChieuActive = toggleSuatChieuBtn && toggleSuatChieuBtn.classList.contains('active');
        
        // Logic: Chỉ 1 nút active tại một thời điểm (như navigation)
        // - Nút "Đơn hàng" active: hiển thị các phần đơn hàng (Top 10 phim, Top 10 F&B, F&B chart), ẩn suất chiếu
        // - Nút "Suất chiếu" active: ẩn các phần đơn hàng, chỉ hiển thị các phần suất chiếu
        
        // Xử lý phần đơn hàng
        if (donHangActive) {
            // Nếu "Đơn hàng" active, hiển thị các phần đơn hàng
            toggleSections('.stat-section-don-hang', true);
        } else {
            // Nếu "Đơn hàng" không active, ẩn các phần đơn hàng
            toggleSections('.stat-section-don-hang', false);
        }
        
        // Xử lý phần suất chiếu
        if (suatChieuActive) {
            // Nếu "Suất chiếu" active, hiển thị các phần suất chiếu
            toggleSections('.stat-section-suat-chieu', true);
        } else {
            // Nếu "Suất chiếu" không active, ẩn các phần suất chiếu
            toggleSections('.stat-section-suat-chieu', false);
        }
    }
    
    // Function to activate a button and deactivate the other
    // Cả hai nút khi active đều dùng cùng màu đỏ, inactive dùng màu xám
    const activeClasses = ['bg-red-500', 'border-red-500', 'text-white', 'shadow-lg'];
    const inactiveClasses = ['bg-gray-100', 'border-gray-400', 'text-gray-700', 'shadow-md'];
    
    function activateButton(activeBtn, inactiveBtn) {
        // Activate the clicked button
        activeBtn.classList.add('active');
        // Remove inactive classes và add active classes
        activeBtn.classList.remove(...inactiveClasses);
        activeBtn.classList.add(...activeClasses);
        
        // Deactivate the other button
        if (inactiveBtn) {
            inactiveBtn.classList.remove('active');
            // Remove active classes và add inactive classes
            inactiveBtn.classList.remove(...activeClasses);
            inactiveBtn.classList.add(...inactiveClasses);
        }
    }
    
    if (toggleDonHangBtn) {
        // Initialize: mặc định active nút "Đơn hàng" khi tải trang
        const isInitiallyActive = toggleDonHangBtn.classList.contains('active');
        if (!isInitiallyActive) {
            toggleDonHangBtn.classList.add('active');
        }
        updateToggleButton(
            toggleDonHangBtn,
            true,
            activeClasses,
            inactiveClasses
        );
        
        toggleDonHangBtn.addEventListener('click', function() {
            // Nếu đã active, không làm gì (giữ nguyên)
            if (this.classList.contains('active')) {
                return;
            }
            
            // Activate this button and deactivate the other
            activateButton(this, toggleSuatChieuBtn);
            
            // Update date range filter for orders (past dates only)
            updateDateRangeFilter(false);
            
            // Sync visibility states
            syncToggleStates();
            
            // Tự động fetch API với dữ liệu 7 ngày qua sau khi cập nhật bộ lọc
            // Sử dụng setTimeout để đảm bảo date inputs đã được cập nhật
            setTimeout(() => {
                updateAllData(true);
            }, 100);
        });
    }
    
    if (toggleSuatChieuBtn) {
        // Initialize: mặc định inactive nút "Suất chiếu" khi tải trang
        const isInitiallyActive = toggleSuatChieuBtn.classList.contains('active');
        if (isInitiallyActive) {
            toggleSuatChieuBtn.classList.remove('active');
        }
        updateToggleButton(
            toggleSuatChieuBtn,
            false,
            activeClasses,
            inactiveClasses
        );
        
        toggleSuatChieuBtn.addEventListener('click', function() {
            // Nếu đã active, không làm gì (giữ nguyên)
            if (this.classList.contains('active')) {
                return;
            }
            
            // Activate this button and deactivate the other
            activateButton(this, toggleDonHangBtn);
            
            // Update date range filter for showtimes (include future dates)
            updateDateRangeFilter(true);
            
            // Sync visibility states
            syncToggleStates();
            
            // Tự động fetch API với dữ liệu 7 ngày tới sau khi cập nhật bộ lọc
            // Sử dụng setTimeout để đảm bảo date inputs đã được cập nhật
            setTimeout(() => {
                updateAllData(true);
            }, 100);
        });
    }
    
    // Initialize date range filter for orders (default) - set max dates first
    if (dateStartInput) dateStartInput.setAttribute('max', todayStr);
    if (dateEndInput) dateEndInput.setAttribute('max', todayStr);
    
    // Initialize visibility on page load
    syncToggleStates();

});
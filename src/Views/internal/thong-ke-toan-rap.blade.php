@extends('internal.layout')

@section('title', 'Thống kê toàn rạp')

@section('head')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/thong-ke-toan-rap.js"></script>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-1 text-gray-500 hover:text-gray-700 text-sm font-medium">Thống kê toàn rạp</span>
    </div>
</li>
@endsection

@section('content')
    <!-- Page header -->
    <div class="bg-gradient-to-r from-red-500 via-red-600 to-red-700 rounded-2xl shadow-2xl p-8 mb-10 text-white border border-red-400">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold flex items-center drop-shadow-lg">
                    <svg class="w-10 h-10 mr-4 drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Thống kê toàn rạp
                </h1>
                <p class="mt-3 text-red-50 flex items-center text-base">
                    <svg class="w-5 h-5 mr-2 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Phân tích dữ liệu kinh doanh toàn chuỗi rạp EPIC Cinema
                </p>
            </div>
            <div class="hidden lg:flex items-center space-x-4">
                <div class="bg-white bg-opacity-25 rounded-xl px-5 py-3 backdrop-blur-md border border-white border-opacity-30 shadow-lg">
                    <div class="text-xs text-red-50 font-medium uppercase tracking-wide">Cập nhật lúc</div>
                    <div class="text-lg font-bold mt-1" id="last-update">--:--</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Date range filter -->
    <div class="mb-12 bg-gradient-to-br from-white via-gray-50 to-white rounded-2xl shadow-xl border-2 border-gray-200 p-8">
        <div class="flex items-center mb-6 pb-4 border-b-2 border-gray-200">
            <div class="bg-red-100 rounded-lg p-2 mr-3">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800">Bộ lọc dữ liệu</h3>
        </div>
        <!-- Toggle buttons for Order and Showtime statistics -->
        <div class="flex flex-wrap gap-4">
            <button id="toggle-don-hang" class="toggle-stat-section inline-flex items-center px-6 py-3 border-2 border-red-500 rounded-lg shadow-md text-sm font-semibold text-white bg-red-500 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 transform hover:scale-105 active">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Đơn hàng
            </button>
            <button id="toggle-suat-chieu" class="toggle-stat-section inline-flex items-center px-6 py-3 border-2 border-gray-400 rounded-lg shadow-md text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 hover:border-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 transform hover:scale-105">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Suất chiếu
            </button>
        </div>
        <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 items-end mt-8">
            <div class="w-full md:w-auto">
                <label for="date-range" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                    <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Khoảng thời gian
                </label>
                <select id="date-range" class="form-input rounded-lg w-full md:w-52 border-gray-300 focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-all" data-filter-type="don-hang">
                    <option value="7" selected>7 ngày qua</option>
                    <option value="30">30 ngày qua</option>
                    <option value="90">90 ngày qua</option>
                    <option value="365">365 ngày qua</option>
                    <option value="custom">Tùy chỉnh</option>
                </select>
            </div>
            
            <div class="date-range-custom hidden flex-grow md:flex-grow-0 space-x-4">
                <div>
                    <label for="date-start" class="block text-sm font-semibold text-gray-700 mb-2">Từ ngày</label>
                    <input type="date" id="date-start" class="form-input rounded-lg w-full border-gray-300 focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-all">
                </div>
                <div>
                    <label for="date-end" class="block text-sm font-semibold text-gray-700 mb-2">Đến ngày</label>
                    <input type="date" id="date-end" class="form-input rounded-lg w-full border-gray-300 focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-all">
                </div>
            </div>
            
            <div class="w-full md:w-auto">
                <label for="cinema-filter" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                    <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    Rạp phim
                </label>
                <select id="cinema-filter" class="form-input rounded-lg w-full md:w-52 border-gray-300 focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-all">
                    <option value="all" selected>Tất cả rạp</option>
                    <!-- Options sẽ được load từ API -->
                     @foreach ($rapPhim as $item)
                        <option value="{{$item->id}}">{{$item->ten}}</option>
                     @endforeach
                </select>
            </div>
            
            <div>
                <button id="btn-apply-filter" class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-lg text-sm font-semibold text-white bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 transform hover:scale-105" data-url="{{$_ENV['URL_WEB_BASE']}}">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Áp dụng
                </button>
            </div>
        </div>
        
        <!-- Comparison toggle -->
        <div class="mt-8 flex items-center bg-gradient-to-r from-blue-50 to-blue-100 border-2 border-blue-300 rounded-xl p-4 shadow-md hover:shadow-lg transition-all duration-300">
            <input id="toggle-compare" type="checkbox" class="h-6 w-6 text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 border-gray-300 rounded-md transition-all">
            <label for="toggle-compare" class="ml-4 flex items-center text-sm font-semibold text-blue-900 cursor-pointer">
                <div class="bg-blue-200 rounded-lg p-1.5 mr-3">
                    <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                So sánh với kỳ trước để xem xu hướng thay đổi
            </label>
        </div>

        
    </div>

    <!-- KPI Summary Cards -->
    <div id="kpi-summary-cards" class="stat-section-don-hang grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
        <!-- Total Revenue Card -->
        <div class="relative bg-gradient-to-br from-white via-red-50 to-white rounded-2xl shadow-2xl p-8 border-2 border-red-200 overflow-hidden group hover:shadow-3xl hover:-translate-y-2 transition-all duration-300">
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-red-500 via-red-600 to-orange-500"></div>
            <div class="absolute right-4 top-6 opacity-10 group-hover:opacity-20 transition-opacity duration-300 transform rotate-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="text-xs font-bold text-gray-600 uppercase tracking-widest mb-3">Tổng doanh thu</div>
            <div class="text-4xl font-extrabold text-gray-900 mb-4" id="total-revenue">---</div>
            <div class="inline-flex items-center text-xs font-bold px-3 py-1.5 rounded-full bg-green-100 text-green-700 border border-green-300" id="revenue-trend">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
                <span>---</span>
            </div>
        </div>
        
        <!-- Total Tickets Card -->
        <div class="relative bg-gradient-to-br from-white via-blue-50 to-white rounded-2xl shadow-2xl p-8 border-2 border-blue-200 overflow-hidden group hover:shadow-3xl hover:-translate-y-2 transition-all duration-300">
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-blue-500 via-blue-600 to-cyan-500"></div>
            <div class="absolute right-4 top-6 opacity-10 group-hover:opacity-20 transition-opacity duration-300 transform rotate-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                </svg>
            </div>
            <div class="text-xs font-bold text-gray-600 uppercase tracking-widest mb-3">Tổng vé bán</div>
            <div class="text-4xl font-extrabold text-gray-900 mb-4" id="total-tickets">---</div>
            <div class="inline-flex items-center text-xs font-bold px-3 py-1.5 rounded-full bg-green-100 text-green-700 border border-green-300" id="tickets-trend">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
                <span>---</span>
            </div>
        </div>
        
        <!-- Average Occupancy Card -->
        <div class="relative bg-gradient-to-br from-white via-purple-50 to-white rounded-2xl shadow-2xl p-8 border-2 border-purple-200 overflow-hidden group hover:shadow-3xl hover:-translate-y-2 transition-all duration-300">
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-purple-500 via-purple-600 to-pink-500"></div>
            <div class="absolute right-4 top-6 opacity-10 group-hover:opacity-20 transition-opacity duration-300 transform rotate-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div class="text-xs font-bold text-gray-600 uppercase tracking-widest mb-3">Tỉ lệ lấp đầy</div>
            <div class="text-4xl font-extrabold text-gray-900 mb-4" id="avg-occupancy">---</div>
            <div class="inline-flex items-center text-xs font-bold px-3 py-1.5 rounded-full bg-red-100 text-red-700 border border-red-300" id="occupancy-trend">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
                <span>---</span>
            </div>
        </div>
        
        <!-- F&B Revenue Card -->
        <div class="relative bg-gradient-to-br from-white via-amber-50 to-white rounded-2xl shadow-2xl p-8 border-2 border-amber-200 overflow-hidden group hover:shadow-3xl hover:-translate-y-2 transition-all duration-300">
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-amber-500 via-orange-500 to-yellow-500"></div>
            <div class="absolute right-4 top-6 opacity-10 group-hover:opacity-20 transition-opacity duration-300 transform rotate-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                </svg>
            </div>
            <div class="text-xs font-bold text-gray-600 uppercase tracking-widest mb-3">Doanh thu F&B</div>
            <div class="text-4xl font-extrabold text-gray-900 mb-4" id="fnb-revenue">---</div>
            <div class="inline-flex items-center text-xs font-bold px-3 py-1.5 rounded-full bg-green-100 text-green-700 border border-green-300" id="fnb-trend">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
                <span>---</span>
            </div>
        </div>
    </div>

    <!-- Main charts -->
    <div class="stat-section-don-hang grid grid-cols-1 lg:grid-cols-2 gap-10 mb-12">
        <!-- Revenue Trend Chart -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 hover:shadow-3xl hover:border-gray-300 transition-all duration-300">
            <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-gray-200 flex items-center">
                <div class="w-1.5 h-7 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                <span>Xu hướng doanh thu</span>
            </h2>
            <div class="flex space-x-2 mb-4">
                <button class="time-filter px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg filter-active transition-all" data-period="daily">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Theo ngày
                </button>
                <button class="time-filter px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg transition-all" data-period="weekly">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Theo tuần
                </button>
                <button class="time-filter px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg transition-all" data-period="monthly">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                    Theo tháng
                </button>
            </div>
            <div class="h-80">
                <div id="revenue-chart"></div>
            </div>
        </div>

        <!-- Ticket Sales Trend Chart -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 hover:shadow-3xl hover:border-gray-300 transition-all duration-300">
            <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-gray-200 flex items-center">
                <div class="w-1.5 h-7 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                <span>Xu hướng lượng vé</span>
            </h2>
            <div class="flex space-x-2 mb-4">
                <button class="time-filter px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg filter-active transition-all" data-period="daily">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Theo ngày
                </button>
                <button class="time-filter px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg transition-all" data-period="weekly">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Theo tuần
                </button>
                <button class="time-filter px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg transition-all" data-period="monthly">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                    Theo tháng
                </button>
            </div>
            <div class="h-80">
                <div id="tickets-chart"></div>
            </div>
        </div>
    </div>

    <!-- Second row of charts -->
    <div class="stat-section-don-hang grid grid-cols-1 lg:grid-cols-2 gap-10 mb-12">
        <!-- Theater Performance -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 hover:shadow-3xl hover:border-gray-300 transition-all duration-300">
            <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-gray-200 flex items-center">
                <div class="w-1.5 h-7 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                <span>Hiệu suất theo rạp</span>
            </h2>
            <div class="h-80">
                <div id="theater-performance-chart"></div>
            </div>
        </div>

        <!-- Revenue Breakdown -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 hover:shadow-3xl hover:border-gray-300 transition-all duration-300">
            <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-gray-200 flex items-center">
                <div class="w-1.5 h-7 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                <span>Cơ cấu doanh thu</span>
            </h2>
            <div class="h-80">
                <div id="revenue-breakdown-chart"></div>
            </div>
        </div>
    </div>

    <!-- Third row - Top F&B and F&B Chart -->
    <div class="stat-section-don-hang grid grid-cols-1 lg:grid-cols-2 gap-10 mb-12">
        <!-- Top F&B Items -->
        <div id="fnb-analysis" class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 hover:shadow-3xl hover:border-gray-300 transition-all duration-300">
            <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-gray-200 flex items-center">
                <div class="w-1.5 h-7 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                <span>Top 10 sản phẩm F&B bán chạy nhất</span>
            </h2>
            <div class="overflow-y-auto max-h-80 rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="sticky top-0 bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                                    </svg>
                                    Sản phẩm
                                </div>
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center justify-end">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                    </svg>
                                    Số lượng
                                </div>
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center justify-end">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Doanh thu
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100" id="top-fnb-table">
                        <!-- Data will be populated by JavaScript -->
                        <tr><td colspan="3" class="text-center py-8 text-gray-500">
                            <svg class="animate-spin h-8 w-8 mx-auto mb-2 text-amber-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Đang tải dữ liệu...
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- F&B vs Ticket Revenue Trend -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 hover:shadow-3xl hover:border-gray-300 transition-all duration-300">
            <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-gray-200 flex items-center">
                <div class="w-1.5 h-7 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                <span>Tỉ lệ doanh thu F&B trên mỗi đơn hàng</span>
            </h2>
            <div class="h-80">
                <div id="fnb-per-ticket-chart"></div>
            </div>
        </div>
    </div>

    <!-- Fourth row - Top Films and All Films Revenue -->
    <div class="stat-section-don-hang grid grid-cols-1 lg:grid-cols-2 gap-10 mb-12">
        <!-- Top Films -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 hover:shadow-3xl hover:border-gray-300 transition-all duration-300">
            <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-gray-200 flex items-center">
                <div class="w-1.5 h-7 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                <span>Top 10 phim có doanh thu cao nhất</span>
            </h2>
            <div class="overflow-y-auto max-h-80 rounded-lg border border-gray-200" style="min-height: 200px;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="sticky top-0 bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"></path>
                                    </svg>
                                    Phim
                                </div>
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center justify-end">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Doanh thu
                                </div>
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center justify-end">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                                    </svg>
                                    Vé bán
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100" id="top-films-table">
                        <!-- Data will be populated by JavaScript -->
                        <tr><td colspan="3" class="text-center py-8 text-gray-500">
                            <svg class="animate-spin h-8 w-8 mx-auto mb-2 text-red-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- All Films Revenue Table -->
        <div id="all-films-revenue" class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 hover:shadow-3xl hover:border-gray-300 transition-all duration-300">
            <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-gray-200 flex items-center">
                <div class="w-1.5 h-7 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                <span>Doanh thu phim (Toàn bộ phim)</span>
            </h2>

            <div class="overflow-y-auto max-h-80 rounded-lg border border-gray-200" style="min-height: 200px;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Poster</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tên phim</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Doanh thu bán vé</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Tổng</th>
                        </tr>
                    </thead>
                    <tbody id="all-film-revenue-body" class="bg-white divide-y divide-gray-100" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}">
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">Đang tải dữ liệu...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Statistics by Showtime -->
    <div class="stat-section-suat-chieu">
        <!-- Charts for Showtime Statistics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mb-12">
            <!-- Revenue Trend by Showtime -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 hover:shadow-3xl hover:border-gray-300 transition-all duration-300">
                <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-gray-200 flex items-center">
                    <div class="w-1.5 h-7 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                    <span>Xu hướng doanh thu theo suất chiếu</span>
                </h2>
                <div class="flex space-x-2 mb-4">
                    <button class="time-filter-suat-chieu px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg filter-active transition-all" data-period="daily">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Theo ngày
                    </button>
                    <button class="time-filter-suat-chieu px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg transition-all" data-period="weekly">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Theo tuần
                    </button>
                    <button class="time-filter-suat-chieu px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg transition-all" data-period="monthly">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                        </svg>
                        Theo tháng
                    </button>
                </div>
                <div class="h-80">
                    <div id="revenue-showtime-chart"></div>
                </div>
            </div>

            <!-- Ticket Sales Trend by Showtime -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 hover:shadow-3xl hover:border-gray-300 transition-all duration-300">
                <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-gray-200 flex items-center">
                    <div class="w-1.5 h-7 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                    <span>Xu hướng vé bán theo suất chiếu</span>
                </h2>
                <div class="flex space-x-2 mb-4">
                    <button class="time-filter-suat-chieu px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg filter-active transition-all" data-period="daily">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Theo ngày
                    </button>
                    <button class="time-filter-suat-chieu px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg transition-all" data-period="weekly">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Theo tuần
                    </button>
                    <button class="time-filter-suat-chieu px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg transition-all" data-period="monthly">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                        </svg>
                        Theo tháng
                    </button>
                </div>
                <div class="h-80">
                    <div id="tickets-showtime-chart"></div>
                </div>
            </div>
        </div>

        <!-- Second row of charts for Showtime -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mb-12">
            <!-- Theater Performance by Showtime -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 hover:shadow-3xl hover:border-gray-300 transition-all duration-300">
                <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-gray-200 flex items-center">
                    <div class="w-1.5 h-7 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                    <span>Hiệu suất theo rạp (Suất chiếu)</span>
                </h2>
                <div class="h-80">
                    <div id="theater-performance-showtime-chart"></div>
                </div>
            </div>

            <!-- Revenue Breakdown by Showtime -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 hover:shadow-3xl hover:border-gray-300 transition-all duration-300">
                <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-gray-200 flex items-center">
                    <div class="w-1.5 h-7 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                    <span>Cơ cấu doanh thu (Suất chiếu)</span>
                </h2>
                <div class="h-80">
                    <div id="revenue-breakdown-showtime-chart"></div>
                </div>
            </div>
        </div>

        <!-- Revenue by Showtime Table -->
        <div class="grid grid-cols-1 gap-10 mb-12">
            <div id="revenue-by-showtime" class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 hover:shadow-3xl hover:border-gray-300 transition-all duration-300">
                <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-gray-200 flex items-center">
                    <div class="w-1.5 h-7 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                    <span>Thống kê doanh thu theo suất chiếu</span>
                </h2>

                <div class="overflow-y-auto max-h-96 rounded-lg border border-gray-200" style="min-height: 200px;">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="sticky top-0 bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Suất chiếu</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Phim</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Rạp/Phòng</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Thời gian</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Doanh thu vé</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Doanh thu F&B</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Tổng doanh thu</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Vé bán</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Tỷ lệ lấp đầy</th>
                            </tr>
                        </thead>
                        <tbody id="revenue-by-showtime-body" class="bg-white divide-y divide-gray-100" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}">
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500">
                                    <svg class="animate-spin h-8 w-8 mx-auto mb-2 text-red-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Đang tải dữ liệu...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
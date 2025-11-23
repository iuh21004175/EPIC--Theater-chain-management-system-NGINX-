@extends('internal.layout')

@section('title', 'Thống kê')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/thong-ke.js"></script>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">Thống kê</span>
    </div>
</li>
@endsection

@section('content')
<div class="bg-white shadow-md rounded-lg" id="thong-ke-app" data-url="{{$_ENV['URL_WEB_BASE']}}">
    <div class="px-4 py-5 sm:px-6">
        <h2 class="text-xl font-semibold text-gray-800">Thống kê và phân tích kinh doanh</h2>
        <p class="mt-1 text-sm text-gray-600">Dữ liệu phân tích để tối ưu hoạt động kinh doanh</p>
    </div>

    <!-- Bộ lọc thời gian -->
    <div class="px-4 py-3 bg-gray-50 border-t border-b border-gray-200">
        <!-- Toggle buttons for Order and Showtime statistics -->
        <div class="flex flex-wrap gap-4 mb-4">
            <button id="toggle-don-hang" class="toggle-stat-section inline-flex items-center px-6 py-3 border-2 border-blue-500 rounded-lg shadow-md text-sm font-semibold text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-105 active">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Đơn hàng
            </button>
            <button id="toggle-suat-chieu" class="toggle-stat-section inline-flex items-center px-6 py-3 border-2 border-gray-400 rounded-lg shadow-md text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 hover:border-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-105">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Suất chiếu
            </button>
        </div>
        <div class="flex flex-wrap items-center justify-between">
            <div class="flex space-x-4 mb-2 md:mb-0">
                <div>
                    <label for="date-range" class="block text-sm font-medium text-gray-700">Khoảng thời gian</label>
                    <select id="date-range" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" data-filter-type="don-hang">
                        <option value="7" selected>7 ngày qua</option>
                        <option value="30">30 ngày qua</option>
                        <option value="90">90 ngày qua</option>
                        <option value="365">365 ngày qua</option>
                        <option value="7f">7 ngày tới</option>
                        <option value="30f">30 ngày tới</option>
                        <option value="90f">90 ngày tới</option>
                        <option value="365f">365 ngày tới</option>
                        <option value="custom">Tùy chỉnh</option>
                    </select>
                </div>
            </div>
            <div id="custom-date-range" class="hidden flex space-x-4">
                <div>
                    <label for="start-date" class="block text-sm font-medium text-gray-700">Từ ngày</label>
                    <input type="date" id="start-date" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                </div>
                <div>
                    <label for="end-date" class="block text-sm font-medium text-gray-700">Đến ngày</label>
                    <input type="date" id="end-date" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                </div>
                <div class="self-end">
                    <button id="apply-date-range" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Áp dụng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tổng quan -->
    <div class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white shadow">
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium">Tổng doanh thu</p>
                    <svg class="h-8 w-8 text-blue-100" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-2xl font-bold mt-2" id="total-revenue">0 đ</p>
                <div class="flex items-center mt-2">
                    <span id="revenue-trend" class="text-sm"></span>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white shadow">
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium">Số lượng khách</p>
                    <svg class="h-8 w-8 text-green-100" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <p class="text-2xl font-bold mt-2" id="total-customers">0</p>
                <div class="flex items-center mt-2">
                    <span id="customer-trend" class="text-sm"></span>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 text-white shadow">
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium">Tỷ lệ lấp đầy</p>
                    <svg class="h-8 w-8 text-purple-100" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <p class="text-2xl font-bold mt-2" id="occupancy-rate">0%</p>
                <div class="flex items-center mt-2">
                    <span id="occupancy-trend" class="text-sm"></span>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg p-4 text-white shadow">
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium">Doanh thu đồ ăn/khách</p>
                    <svg class="h-8 w-8 text-red-100" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                    </svg>
                </div>
                <p class="text-2xl font-bold mt-2" id="food-per-customer">0 đ</p>
                <div class="flex items-center mt-2">
                    <span id="food-trend" class="text-sm"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics by Order -->
    <div class="stat-section-don-hang">
        <!-- Biểu đồ -->
        <div class="p-4 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Biểu đồ doanh thu -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-medium text-gray-800">Phân tích doanh thu</h3>
                <div id="revenue-chart" class="mt-4 h-80"></div>
            </div>

            <!-- Biểu đồ phân bổ doanh thu -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-medium text-gray-800">Phân bổ doanh thu</h3>
                <div id="revenue-distribution-chart" class="mt-4 h-80"></div>
            </div>

            <!-- Biểu đồ top phim -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-medium text-gray-800">Top 10 phim có doanh thu cao nhất</h3>
                <div id="top-movies-chart" class="mt-4 h-80"></div>
            </div>

            <!-- Biểu đồ top đồ ăn -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-medium text-gray-800">Top 10 đồ ăn/đồ uống bán chạy nhất</h3>
                <div id="top-foods-chart" class="mt-4 h-80"></div>
            </div>

            <!-- Biểu đồ giờ chiếu hiệu quả -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-medium text-gray-800">Hiệu quả theo khung giờ chiếu</h3>
                <div id="showtime-effectiveness-chart" class="mt-4 h-80"></div>
            </div>

            <!-- Biểu đồ xu hướng -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-medium text-gray-800">Xu hướng khách hàng theo thời gian</h3>
                <div id="customer-trends-chart" class="mt-4 h-80"></div>
            </div>
        </div>
    </div>

    <!-- Statistics by Showtime -->
    <div class="stat-section-suat-chieu hidden">
        <!-- Charts for Showtime Statistics -->
        <div class="p-4 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue Trend by Showtime -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Xu hướng doanh thu theo suất chiếu</h3>
                <div class="flex space-x-2 mb-4">
                    <button class="time-filter-suat-chieu px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg filter-active transition-all" data-period="daily">
                        Theo ngày
                    </button>
                    <button class="time-filter-suat-chieu px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg transition-all" data-period="weekly">
                        Theo tuần
                    </button>
                    <button class="time-filter-suat-chieu px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg transition-all" data-period="monthly">
                        Theo tháng
                    </button>
                </div>
                <div id="revenue-showtime-chart" class="h-80"></div>
            </div>

            <!-- Ticket Sales Trend by Showtime -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Xu hướng vé bán theo suất chiếu</h3>
                <div class="flex space-x-2 mb-4">
                    <button class="time-filter-suat-chieu px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg filter-active transition-all" data-period="daily">
                        Theo ngày
                    </button>
                    <button class="time-filter-suat-chieu px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg transition-all" data-period="weekly">
                        Theo tuần
                    </button>
                    <button class="time-filter-suat-chieu px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg transition-all" data-period="monthly">
                        Theo tháng
                    </button>
                </div>
                <div id="tickets-showtime-chart" class="h-80"></div>
            </div>

            <!-- Theater Performance by Showtime -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Hiệu suất theo rạp (Suất chiếu)</h3>
                <div id="theater-performance-showtime-chart" class="h-80"></div>
            </div>

            <!-- Revenue Breakdown by Showtime -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Cơ cấu doanh thu (Suất chiếu)</h3>
                <div id="revenue-breakdown-showtime-chart" class="h-80"></div>
            </div>
        </div>

        <!-- Revenue by Showtime Table -->
        <div class="p-4">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Thống kê doanh thu theo suất chiếu
                    </h3>
                </div>
                <div class="overflow-y-auto max-h-96">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
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
                                    <svg class="animate-spin h-8 w-8 mx-auto mb-2 text-blue-500" fill="none" viewBox="0 0 24 24">
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

    <!-- Bảng phân tích chi tiết -->
    <div class="p-4">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Chi tiết phân tích
                </h3>
            </div>
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <div class="flex flex-wrap items-center justify-between">
                    <div class="flex space-x-2">
                        <button id="btn-movie-analysis" class="px-3 py-2 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Phân tích phim
                        </button>
                        <button id="btn-food-analysis" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Phân tích đồ ăn
                        </button>
                        <button id="btn-showtime-analysis" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Phân tích suất chiếu
                        </button>
                    </div>
                    <div class="mt-2 sm:mt-0">
                        <button id="btn-export-data" class="flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="h-5 w-5 mr-2 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Xuất dữ liệu
                        </button>
                    </div>
                </div>
            </div>
            <div class="max-h-96 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200" id="analysis-table">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr id="analysis-table-header">
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tên
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Doanh thu
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Số lượt
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tỷ lệ đóng góp
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                So với kỳ trước
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="analysis-table-body">
                        <!-- Dữ liệu sẽ được thêm vào bằng JavaScript -->
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                Đang tải dữ liệu...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Đề xuất kinh doanh -->
    <div class="p-4">
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Đề xuất tối ưu kinh doanh</h3>
            <div id="business-recommendations" class="space-y-4">
                <div class="bg-blue-50 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Đề xuất sẽ được hiển thị dựa trên phân tích dữ liệu</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>Vui lòng chọn khoảng thời gian và loại thống kê để nhận đề xuất phù hợp.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
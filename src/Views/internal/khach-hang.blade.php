@extends('internal.layout')

@section('title', 'Quản lý khách hàng')

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-1 text-gray-500 hover:text-gray-700 text-sm font-medium">Quản lý khách hàng</span>
    </div>
</li>
@endsection

@section('content')
<div class="px-4 py-6 max-w-7xl mx-auto">

    <!-- Thống kê -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Tổng khách hàng</p>
                    <p class="text-3xl font-bold mt-1" id="total-customers">0</p>
                </div>
                <svg class="w-12 h-12 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        </div>
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Đang hoạt động</p>
                    <p class="text-3xl font-bold mt-1" id="active-customers">0</p>
                </div>
                <svg class="w-12 h-12 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
        <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">Ngừng hoạt động</p>
                    <p class="text-3xl font-bold mt-1" id="inactive-customers">0</p>
                </div>
                <svg class="w-12 h-12 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Bộ lọc và tìm kiếm -->
    <div class="bg-white rounded-lg shadow-md p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Tìm kiếm -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Tìm kiếm
                </label>
                <input type="text" id="search-customer" placeholder="Tìm theo tên, email hoặc SĐT..." 
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            
            <!-- Lọc theo trạng thái -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Trạng thái
                </label>
                <select id="filter-status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="all">Tất cả</option>
                    <option value="1">Hoạt động</option>
                    <option value="0">Ngừng hoạt động</option>
                </select>
            </div>

            <!-- Sắp xếp -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path>
                    </svg>
                    Sắp xếp
                </label>
                <select id="sort-by" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="name-asc">Tên A-Z</option>
                    <option value="name-desc">Tên Z-A</option>
                    <option value="email-asc">Email A-Z</option>
                    <option value="email-desc">Email Z-A</option>
                </select>
            </div>
        </div>

        <!-- Hiển thị số dòng và reset -->
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700">Hiển thị:</label>
                <select id="rows-per-page" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-purple-500">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
                <span class="text-sm text-gray-600">dòng/trang</span>
            </div>
            <button id="reset-filters" class="text-sm text-purple-600 hover:text-purple-800 font-medium flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Đặt lại bộ lọc
            </button>
        </div>
    </div>

    <!-- Bảng dữ liệu -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-purple-500 to-purple-600 text-white">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Khách hàng</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Liên hệ</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Trạng thái</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold uppercase tracking-wider">Hành động</th>
                    </tr>
                </thead>
                <tbody id="customer-list" class="bg-white divide-y divide-gray-200">
                    <!-- Dữ liệu sẽ render JS -->
                </tbody>
            </table>
        </div>
        
        <!-- Thông tin phân trang -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-sm text-gray-700">
                Hiển thị <span id="showing-from" class="font-semibold">0</span> - <span id="showing-to" class="font-semibold">0</span> 
                trong tổng số <span id="showing-total" class="font-semibold">0</span> khách hàng
            </div>
            <div id="pagination" class="flex gap-2"></div>
        </div>
    </div>
</div>

<!-- Modal cập nhật khách hàng -->
<div id="customer-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md relative transform transition-all">
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4 rounded-t-xl">
            <h3 class="text-xl font-semibold text-white flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Cập nhật khách hàng
            </h3>
        </div>
        <button id="close-modal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tên khách hàng</label>
                <input type="text" id="customer-name" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-50 cursor-not-allowed text-gray-600" readonly>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" id="customer-email" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-50 cursor-not-allowed text-gray-600" readonly>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại</label>
                <input type="text" id="customer-phone" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-50 cursor-not-allowed text-gray-600" readonly>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                <select id="customer-status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="1">Hoạt động</option>
                    <option value="0">Ngừng hoạt động</option>
                </select>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 rounded-b-xl flex justify-end gap-3">
            <button id="close-modal-btn" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                Hủy
            </button>
            <button id="save-customer" class="px-4 py-2 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg hover:from-purple-600 hover:to-purple-700 transition-all shadow-md">
                Lưu thay đổi
            </button>
        </div>
    </div>
</div>

<!-- Modal Lịch sử giao dịch -->
<div id="history-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl relative max-h-[95vh] flex flex-col">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4 rounded-t-xl">
            <h3 class="text-xl font-semibold text-white flex items-center" id="history-title">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Lịch sử khách hàng
            </h3>
        </div>
        <button id="close-history-modal" class="absolute top-4 right-4 text-white hover:text-gray-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <!-- Bộ lọc -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                <!-- Lọc theo trạng thái -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Trạng thái</label>
                    <select id="history-filter-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="all">Tất cả</option>
                        <option value="2">Đã thanh toán</option>
                        <option value="1">Chờ thanh toán</option>
                        <option value="0">Đã hủy</option>
                    </select>
                </div>

                <!-- Lọc theo ngày từ -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Từ ngày</label>
                    <input type="date" id="history-filter-date-from" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Lọc theo ngày đến -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Đến ngày</label>
                    <input type="date" id="history-filter-date-to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Sắp xếp -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Sắp xếp</label>
                    <select id="history-sort" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="id-desc">Mới nhất</option>
                        <option value="id-asc">Cũ nhất</option>
                        <option value="tong_tien-desc">Giá cao → thấp</option>
                        <option value="tong_tien-asc">Giá thấp → cao</option>
                        <option value="ngay_dat-desc">Ngày mới → cũ</option>
                        <option value="ngay_dat-asc">Ngày cũ → mới</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-medium text-gray-700">Hiển thị:</label>
                    <select id="history-rows-per-page" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-500">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                    <span class="text-xs text-gray-600">dòng/trang</span>
                </div>
                <button id="history-reset-filters" class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Đặt lại
                </button>
            </div>
        </div>

        <!-- Bảng dữ liệu -->
        <div class="overflow-y-auto flex-1 p-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Mã đơn</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Ngày</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Số tiền</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Trạng thái</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody id="history-list" class="bg-white divide-y divide-gray-200">
                        <!-- Dữ liệu sẽ render JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Phân trang -->
        <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-gray-700">
                Hiển thị <span id="history-showing-from" class="font-semibold">0</span> - <span id="history-showing-to" class="font-semibold">0</span> 
                trong tổng số <span id="history-showing-total" class="font-semibold">0</span> đơn hàng
            </div>
            <div id="history-pagination" class="flex gap-1"></div>
        </div>
    </div>
</div>

<!-- Modal Chi tiết giao dịch -->
<div id="transaction-detail-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
  <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl relative max-h-[90vh] flex flex-col">
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4 rounded-t-xl">
        <h3 class="text-xl font-semibold text-white flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Chi tiết giao dịch
        </h3>
    </div>
    <button id="close-transaction-detail" class="absolute top-4 right-4 text-white hover:text-gray-200 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
    <div id="transaction-detail-content" class="overflow-y-auto flex-1 p-6">
        <!-- Render JS -->
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}";
    const closeModalBtn = document.getElementById('close-modal');
    const modal = document.getElementById('customer-modal');

    const nameInput = document.getElementById('customer-name');
    const phoneInput = document.getElementById('customer-phone');
    const emailInput = document.getElementById('customer-email');
    const statusInput = document.getElementById('customer-status');
    const saveBtn = document.getElementById('save-customer');
    const customerList = document.getElementById('customer-list');
    const searchInput = document.getElementById('search-customer');
    const pagination = document.getElementById('pagination');
    const filterStatus = document.getElementById('filter-status');
    const sortBy = document.getElementById('sort-by');
    const rowsPerPageSelect = document.getElementById('rows-per-page');
    const resetFiltersBtn = document.getElementById('reset-filters');

    // Thống kê
    const totalCustomersEl = document.getElementById('total-customers');
    const activeCustomersEl = document.getElementById('active-customers');
    const inactiveCustomersEl = document.getElementById('inactive-customers');
    const showingFromEl = document.getElementById('showing-from');
    const showingToEl = document.getElementById('showing-to');
    const showingTotalEl = document.getElementById('showing-total');

    const historyModal = document.getElementById('history-modal');
    const closeHistoryBtn = document.getElementById('close-history-modal');
    const historyList = document.getElementById('history-list');
    const historyTitle = document.getElementById('history-title');
    const detailModal = document.getElementById('transaction-detail-modal');
    const closeDetailBtn = document.getElementById('close-transaction-detail');
    const detailContent = document.getElementById('transaction-detail-content');

    // Data chính
    let customers = [];
    let filteredCustomers = [];
    let rowsPerPage = 10;
    let currentPage = 1;
    let currentSort = 'name-asc';

    // Data lịch sử
    let currentHistoryCustomerId = null;
    let historyCurrentPage = 1;
    let historyRowsPerPage = 10;
    let historyPagination = null;

    // Fetch khách hàng
    fetch(baseUrl + '/api/doc-khach-hang')
        .then(res => res.json())
        .then(data => {
            if (data.success && Array.isArray(data.data)) {
                customers = [...data.data];
                applyFilters();
            } else {
                customerList.innerHTML = `
                    <tr><td colspan="4" class="text-center text-gray-500 py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <p class="mt-2">Không có khách hàng nào.</p>
                    </td></tr>`;
                updateStatistics();
            }
        })
        .catch(err => {
            console.error('Fetch Error:', err);
            customerList.innerHTML = `
                <tr><td colspan="4" class="text-center text-red-500 py-8">
                    <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="mt-2">Lỗi kết nối máy chủ.</p>
                </td></tr>`;
        });

    // Áp dụng bộ lọc và sắp xếp
    function applyFilters() {
        let filtered = [...customers];
        
        // Lọc theo tìm kiếm
        const searchQuery = searchInput.value.toLowerCase().trim();
        if (searchQuery) {
            filtered = filtered.filter(c =>
                c.ho_ten.toLowerCase().includes(searchQuery) ||
                c.email.toLowerCase().includes(searchQuery) ||
                c.so_dien_thoai.includes(searchQuery)
            );
        }
        
        // Lọc theo trạng thái
        const statusFilter = filterStatus.value;
        if (statusFilter !== 'all') {
            filtered = filtered.filter(c => Number(c.trang_thai) === Number(statusFilter));
        }
        
        // Sắp xếp
        const sortValue = sortBy.value;
        filtered.sort((a, b) => {
            switch(sortValue) {
                case 'name-asc':
                    return a.ho_ten.localeCompare(b.ho_ten, 'vi');
                case 'name-desc':
                    return b.ho_ten.localeCompare(a.ho_ten, 'vi');
                case 'email-asc':
                    return (a.email || '').localeCompare(b.email || '', 'vi');
                case 'email-desc':
                    return (b.email || '').localeCompare(a.email || '', 'vi');
                default:
                    return 0;
            }
        });
        
        filteredCustomers = filtered;
        currentPage = 1; // Reset về trang đầu
        renderCustomers();
        updateStatistics();
    }

    // Cập nhật thống kê
    function updateStatistics() {
        const total = customers.length;
        const active = customers.filter(c => Number(c.trang_thai) === 1).length;
        const inactive = customers.filter(c => Number(c.trang_thai) === 0).length;
        
        totalCustomersEl.textContent = total;
        activeCustomersEl.textContent = active;
        inactiveCustomersEl.textContent = inactive;
    }

    // Render khách hàng
    function renderCustomers(list = filteredCustomers, page = currentPage) {
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const pageItems = list.slice(start, end);

        customerList.innerHTML = '';
        
        if (pageItems.length === 0) {
            customerList.innerHTML = `
                <tr><td colspan="4" class="text-center text-gray-500 py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <p class="mt-2">Không tìm thấy khách hàng nào.</p>
                </td></tr>`;
            updatePaginationInfo(list, 0, 0);
            return;
        }

        pageItems.forEach((c, index) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 transition-colors duration-150';
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                            ${(c.ho_ten || '').charAt(0).toUpperCase()}
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${c.ho_ten || 'Chưa có tên'}</div>
                            <div class="text-sm text-gray-500">ID: ${c.id}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                        <div class="flex items-center mb-1">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            ${c.email || 'Chưa có email'}
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            ${c.so_dien_thoai || 'Chưa có SĐT'}
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${Number(c.trang_thai) === 1
                        ? `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Hoạt động
                        </span>`
                        : `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            Ngừng hoạt động
                        </span>`
                    }
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <div class="flex items-center justify-center gap-2">
                        <button class="edit inline-flex items-center px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded-lg transition-colors duration-150" data-id="${c.id}">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Sửa
                        </button>
                        <button class="history inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors duration-150" data-id="${c.id}">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Lịch sử
                        </button>
                    </div>
                </td>
            `;
            customerList.appendChild(tr);
        });

        updatePaginationInfo(list, start + 1, Math.min(end, list.length));
        renderPagination(list);
    }

    function updatePaginationInfo(list, from, to) {
        showingFromEl.textContent = from;
        showingToEl.textContent = to;
        showingTotalEl.textContent = list.length;
    }

    function renderPagination(list) {
        const totalPages = Math.ceil(list.length / rowsPerPage);
        pagination.innerHTML = '';
        
        if (totalPages <= 1) return;
        
        // Nút Previous
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        `;
        prevBtn.className = `px-3 py-2 border border-gray-300 rounded-l-lg ${currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}`;
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderCustomers(list, currentPage);
            }
        });
        pagination.appendChild(prevBtn);
        
        // Các nút số trang
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            const firstBtn = document.createElement('button');
            firstBtn.textContent = '1';
            firstBtn.className = `px-3 py-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50`;
            firstBtn.addEventListener('click', () => {
                currentPage = 1;
                renderCustomers(list, currentPage);
            });
            pagination.appendChild(firstBtn);
            
            if (startPage > 2) {
                const dots = document.createElement('span');
                dots.textContent = '...';
                dots.className = 'px-2 py-2 text-gray-500';
                pagination.appendChild(dots);
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = `px-3 py-2 border border-gray-300 ${i === currentPage ? 'bg-purple-500 text-white border-purple-500' : 'bg-white text-gray-700 hover:bg-gray-50'}`;
            btn.addEventListener('click', () => {
                currentPage = i;
                renderCustomers(list, currentPage);
            });
            pagination.appendChild(btn);
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const dots = document.createElement('span');
                dots.textContent = '...';
                dots.className = 'px-2 py-2 text-gray-500';
                pagination.appendChild(dots);
            }
            
            const lastBtn = document.createElement('button');
            lastBtn.textContent = totalPages;
            lastBtn.className = `px-3 py-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50`;
            lastBtn.addEventListener('click', () => {
                currentPage = totalPages;
                renderCustomers(list, currentPage);
            });
            pagination.appendChild(lastBtn);
        }
        
        // Nút Next
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        `;
        nextBtn.className = `px-3 py-2 border border-gray-300 rounded-r-lg ${currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}`;
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderCustomers(list, currentPage);
            }
        });
        pagination.appendChild(nextBtn);
    }

    // Event listeners cho modal
    closeModalBtn.addEventListener('click', () => modal.classList.add('hidden'));
    document.getElementById('close-modal-btn')?.addEventListener('click', () => modal.classList.add('hidden'));
    closeHistoryBtn.addEventListener('click', () => historyModal.classList.add('hidden'));
    closeDetailBtn.addEventListener('click', () => detailModal.classList.add('hidden'));
    
    // Đóng modal khi click bên ngoài
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.add('hidden');
    });
    historyModal.addEventListener('click', (e) => {
        if (e.target === historyModal) historyModal.classList.add('hidden');
    });
    detailModal.addEventListener('click', (e) => {
        if (e.target === detailModal) detailModal.classList.add('hidden');
    });

    // Lưu ID khách hàng đang chỉnh sửa
    let currentEditId = null;

    // Click trong bảng
    customerList.addEventListener('click', e => {
        const editBtn = e.target.closest('.edit');
        const historyBtn = e.target.closest('.history');
        
        if (editBtn) {
            const id = parseInt(editBtn.dataset.id);
            const c = customers.find(c => c.id === id);
            if (!c) return;
            currentEditId = id;
            nameInput.value = c.ho_ten || '';
            phoneInput.value = c.so_dien_thoai || '';
            emailInput.value = c.email || '';
            statusInput.value = c.trang_thai; 
            modal.classList.remove('hidden');
        }
        
        if (historyBtn) {
            const id = parseInt(historyBtn.dataset.id);
            const c = customers.find(c => c.id === id);
            if (!c) return;
            historyTitle.textContent = `Lịch sử của ${c.ho_ten}`;
            currentHistoryCustomerId = id;
            historyCurrentPage = 1;
            loadHistoryData();
            historyModal.classList.remove('hidden');
        }
    });

    historyList.addEventListener('click', e => {
        if (e.target.classList.contains('view-detail')) {
            const donhangId = e.target.dataset.id;

            fetch(`${baseUrl}/api/doc-chi-tiet-don-hang/${donhangId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const ve = Array.isArray(data.data) ? data.data[0] : data.data;

                        let startTime = ve.ve?.[0]?.suat_chieu?.batdau 
                            ? new Date(ve.ve[0].suat_chieu.batdau) 
                            : null;

                        const isCancelled = Number(ve.trang_thai) === 0;
                        const canCancel = Number(ve.trang_thai) === 2; // chỉ hoàn vé khi đã thanh toán

                        let html = `
                        <div class="relative ${isCancelled ? 'modal-cancelled' : ''} space-y-2 p-2 max-h-[80vh] overflow-y-auto">
                            ${isCancelled ? `<div class="modal-cancelled-overlay"><span>Đã hoàn vé</span></div>` : ''}

                            <!-- Thông tin phim -->
                            <div class="p-3 bg-white rounded shadow">
                                <h5 class="font-bold text-lg flex items-center gap-2">
                                    ${ve.ve?.[0]?.suat_chieu?.phim?.ten_phim || 'Không xác định'}
                                    <span class="inline-block px-2 py-0.5 text-xs font-semibold text-white bg-red-500 rounded">
                                        ${ve.ve?.[0]?.suat_chieu?.phim?.do_tuoi || 'C'}
                                    </span>
                                </h5>
                            </div>

                            <!-- Thông tin rạp -->
                            <div class="p-3 bg-white rounded shadow text-sm text-gray-700 grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <p><span class="font-semibold">Rạp:</span> ${ve.ve?.[0]?.suat_chieu?.phong_chieu?.rap_chieu_phim?.ten || '-'}</p>
                                    <p><span class="font-semibold">Phòng:</span> ${ve.ve?.[0]?.suat_chieu?.phong_chieu?.ten || '-'}</p>
                                    <p><span class="font-semibold">Loại phòng:</span> ${(ve.ve?.[0]?.suat_chieu?.phong_chieu?.loai_phongchieu || '-').toUpperCase()}</p>
                                </div>
                                <div class="space-y-1">
                                    <p><span class="font-semibold">Ngày chiếu:</span> ${startTime ? startTime.toLocaleDateString('vi-VN',{ weekday:'long', day:'2-digit', month:'2-digit', year:'numeric' }) : '-'}</p>
                                    <p><span class="font-semibold">Thời gian:</span> 
                                        ${startTime ? startTime.toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'}) : '-'} - 
                                        ${ve.ve?.[0]?.suat_chieu?.ketthuc ? new Date(ve.ve[0].suat_chieu.ketthuc).toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'}) : '-'}
                                    </p>
                                    <p><span class="font-semibold">Tổng tiền:</span> ${Number(ve.tong_tien || 0).toLocaleString()} ₫</p>
                                </div>
                            </div>

                            <!-- Ghế -->
                            <div class="p-2 bg-white rounded shadow text-sm">
                                <span class="font-semibold">Ghế:</span>
                                <span>${ve.ve?.map(v=>v.ghe?.so_ghe).filter(Boolean).join(', ') || '-'}</span>
                            </div>

                            <!-- Thức ăn + Mã vé -->
                            <div class="p-3 bg-white rounded shadow text-sm text-gray-700 grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <div>
                                        <h4 class="font-semibold mb-1">Thức ăn kèm:</h4>
                                        ${
                                            ve.ve?.flatMap(v=>v.don_hang?.chi_tiet_don_hang||[])
                                                .map(item=> `
                                                    <div class="flex justify-between border-b border-gray-100 py-1">
                                                        <span>${item.san_pham?.ten || '-'} x ${item.so_luong || 0}</span>
                                                        <span class="font-semibold">${Number(item.thanh_tien || 0).toLocaleString()} ₫</span>
                                                    </div>
                                                `).join('') || '<p>Không có</p>'
                                        }
                                    </div>

                                    <div>
                                        <h4 class="font-semibold mb-1">Thẻ quà tặng:</h4>
                                        <div class="flex justify-between border-b border-gray-100 py-1">
                                            ${ve.the_qua_tang_su_dung > 0 
                                                ? `<span>${Number(ve.the_qua_tang_su_dung || 0).toLocaleString()} ₫</span>` 
                                                : '<span>Không có</span>'
                                            }
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col items-center gap-1">
                                    <span class="font-semibold text-sm">Mã vé</span>
                                    <span class="text-blue-600 font-mono text-base">${ve.ma_ve || '-'}</span>
                                    <img src="${ve.qr_code || ''}" alt="QR Code" class="w-24 h-24 ${ve.qr_code ? '' : 'hidden'}">
                                </div>
                            </div>
                        </div>
                        `;

                        detailContent.innerHTML = html;
                    } else {
                        detailContent.innerHTML = '<p class="text-center text-gray-500">Không có dữ liệu chi tiết.</p>';
                    }
                    detailModal.classList.remove('hidden');
                })
                .catch(err => {
                    console.error(err);
                    detailContent.innerHTML = '<p class="text-center text-red-500">Lỗi khi tải chi tiết.</p>';
                    detailModal.classList.remove('hidden');
                });
        }
    });

    function renderTrangThai(trang_thai) {
        switch (Number(trang_thai)) {
            case 2: 
                return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Đã thanh toán
                </span>`;
            case 1: 
                return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                    Chờ thanh toán
                </span>`;
            case 0: 
                return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    Đã hủy
                </span>`;
            default: 
                return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    Không xác định
                </span>`;
        }
    }

    // Hàm tải dữ liệu lịch sử
    function loadHistoryData() {
        if (!currentHistoryCustomerId) return;

        const filterStatus = document.getElementById('history-filter-status').value;
        const dateFrom = document.getElementById('history-filter-date-from').value;
        const dateTo = document.getElementById('history-filter-date-to').value;
        const sortValue = document.getElementById('history-sort').value;
        const [sortBy, sortOrder] = sortValue.split('-');
        const perPage = parseInt(document.getElementById('history-rows-per-page').value);

        const params = new URLSearchParams({
            trang_thai: filterStatus,
            page: historyCurrentPage,
            per_page: perPage,
            sort_by: sortBy,
            sort_order: sortOrder
        });

        if (dateFrom) params.append('ngay_tu', dateFrom);
        if (dateTo) params.append('ngay_den', dateTo);

        fetch(`${baseUrl}/api/doc-giao-dich/${currentHistoryCustomerId}?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                historyList.innerHTML = '';
                if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                    data.data.forEach(t => {
                        const tr = document.createElement('tr');
                        tr.className = 'hover:bg-gray-50 transition-colors';
                        const date = new Date(t.ngay_dat);
                        tr.innerHTML = `
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 font-mono">${t.ma_ve || '-'}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900">${date.toLocaleDateString('vi-VN')}</div>
                                <div class="text-xs text-gray-500">${date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'})}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">${parseFloat(t.tong_tien || 0).toLocaleString('vi-VN')} ₫</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                ${renderTrangThai(t.trang_thai)}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <button class="view-detail inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors" data-id="${t.id}">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Chi tiết
                                </button>
                            </td>
                        `;
                        historyList.appendChild(tr);
                    });

                    // Cập nhật phân trang
                    if (data.pagination) {
                        historyPagination = data.pagination;
                        renderHistoryPagination();
                        updateHistoryPaginationInfo();
                    }
                } else {
                    historyList.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-lg font-medium">Chưa có giao dịch</p>
                                <p class="text-sm text-gray-400 mt-1">Không tìm thấy đơn hàng phù hợp với bộ lọc</p>
                            </td>
                        </tr>`;
                    document.getElementById('history-pagination').innerHTML = '';
                    updateHistoryPaginationInfo(0, 0, 0);
                }
            })
            .catch(err => {
                console.error('Fetch Error:', err);
                historyList.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-red-500 py-12">
                            <svg class="mx-auto h-12 w-12 text-red-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-lg font-medium">Lỗi khi tải dữ liệu</p>
                            <p class="text-sm text-gray-400 mt-1">Vui lòng thử lại sau</p>
                        </td>
                    </tr>`;
            });
    }

    // Render phân trang lịch sử
    function renderHistoryPagination() {
        if (!historyPagination) return;
        const paginationEl = document.getElementById('history-pagination');
        const totalPages = historyPagination.total_pages;
        paginationEl.innerHTML = '';
        
        if (totalPages <= 1) return;
        
        // Nút Previous
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>`;
        prevBtn.className = `px-2 py-1.5 border border-gray-300 rounded-l-lg text-xs ${historyCurrentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}`;
        prevBtn.disabled = historyCurrentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (historyCurrentPage > 1) {
                historyCurrentPage--;
                loadHistoryData();
            }
        });
        paginationEl.appendChild(prevBtn);
        
        // Các nút số trang
        let startPage = Math.max(1, historyCurrentPage - 1);
        let endPage = Math.min(totalPages, historyCurrentPage + 1);
        
        for (let i = startPage; i <= endPage; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = `px-2 py-1.5 border border-gray-300 text-xs ${i === historyCurrentPage ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 hover:bg-gray-50'}`;
            btn.addEventListener('click', () => {
                historyCurrentPage = i;
                loadHistoryData();
            });
            paginationEl.appendChild(btn);
        }
        
        // Nút Next
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>`;
        nextBtn.className = `px-2 py-1.5 border border-gray-300 rounded-r-lg text-xs ${historyCurrentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}`;
        nextBtn.disabled = historyCurrentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (historyCurrentPage < totalPages) {
                historyCurrentPage++;
                loadHistoryData();
            }
        });
        paginationEl.appendChild(nextBtn);
    }

    // Cập nhật thông tin phân trang lịch sử
    function updateHistoryPaginationInfo(from = null, to = null, total = null) {
        if (historyPagination) {
            const start = (historyPagination.page - 1) * historyPagination.per_page + 1;
            const end = Math.min(historyPagination.page * historyPagination.per_page, historyPagination.total);
            document.getElementById('history-showing-from').textContent = from !== null ? from : start;
            document.getElementById('history-showing-to').textContent = to !== null ? to : end;
            document.getElementById('history-showing-total').textContent = total !== null ? total : historyPagination.total;
        } else {
            document.getElementById('history-showing-from').textContent = '0';
            document.getElementById('history-showing-to').textContent = '0';
            document.getElementById('history-showing-total').textContent = '0';
        }
    }

    // Event listeners cho bộ lọc lịch sử
    document.getElementById('history-filter-status')?.addEventListener('change', () => {
        historyCurrentPage = 1;
        loadHistoryData();
    });

    document.getElementById('history-filter-date-from')?.addEventListener('change', () => {
        historyCurrentPage = 1;
        loadHistoryData();
    });

    document.getElementById('history-filter-date-to')?.addEventListener('change', () => {
        historyCurrentPage = 1;
        loadHistoryData();
    });

    document.getElementById('history-sort')?.addEventListener('change', () => {
        historyCurrentPage = 1;
        loadHistoryData();
    });

    document.getElementById('history-rows-per-page')?.addEventListener('change', () => {
        historyRowsPerPage = parseInt(document.getElementById('history-rows-per-page').value);
        historyCurrentPage = 1;
        loadHistoryData();
    });

    document.getElementById('history-reset-filters')?.addEventListener('click', () => {
        document.getElementById('history-filter-status').value = 'all';
        document.getElementById('history-filter-date-from').value = '';
        document.getElementById('history-filter-date-to').value = '';
        document.getElementById('history-sort').value = 'id-desc';
        document.getElementById('history-rows-per-page').value = '10';
        historyRowsPerPage = 10;
        historyCurrentPage = 1;
        loadHistoryData();
    });

    // Event listeners cho bộ lọc
    searchInput.addEventListener('input', () => {
        applyFilters();
    });

    filterStatus.addEventListener('change', () => {
        applyFilters();
    });

    sortBy.addEventListener('change', () => {
        currentSort = sortBy.value;
        applyFilters();
    });

    rowsPerPageSelect.addEventListener('change', () => {
        rowsPerPage = parseInt(rowsPerPageSelect.value);
        currentPage = 1;
        renderCustomers();
    });

    resetFiltersBtn.addEventListener('click', () => {
        searchInput.value = '';
        filterStatus.value = 'all';
        sortBy.value = 'name-asc';
        rowsPerPageSelect.value = '10';
        rowsPerPage = 10;
        currentPage = 1;
        applyFilters();
    });

    saveBtn.addEventListener('click', () => {
        if (!currentEditId) return;

        const trangThaiMoi = statusInput.value; // 0 hoặc 1

        fetch(`${baseUrl}/api/trang-thai-khach-hang/${currentEditId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ trang_thai: trangThaiMoi })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Cập nhật mảng customers để render lại
                const idx = customers.findIndex(c => c.id === currentEditId);
                if (idx !== -1) {
                    customers[idx].trang_thai = Number(trangThaiMoi);
                }
                applyFilters();
                modal.classList.add('hidden');
                currentEditId = null;
                
                // Hiển thị thông báo thành công
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
                notification.innerHTML = `
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Cập nhật trạng thái thành công</span>
                `;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            } else {
                alert(data.message || 'Cập nhật thất bại');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Có lỗi xảy ra khi cập nhật trạng thái');
        });
    });
});
</script>
@endsection

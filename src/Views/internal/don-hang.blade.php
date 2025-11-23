@extends('internal.layout')

@section('title', 'Quản lý đơn hàng')

@section('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
<style>
    .order-card {
        transition: all 0.3s ease;
    }
    .order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.375rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
    }
    .filter-chip {
        transition: all 0.2s;
    }
    .filter-chip.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .stat-card.success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    .stat-card.warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .stat-card.danger {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }
</style>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-1 text-gray-500 hover:text-gray-700 text-sm font-medium">Quản lý đơn hàng</span>
    </div>
</li>
@endsection

@section('content')
<?php $idRap = $_SESSION['UserInternal']['ID_RapPhim'] ?? null; ?>

<div class="px-4 py-6 max-w-7xl mx-auto space-y-6">

    <!-- Thống kê tổng quan -->
    <div class="grid grid-cols-4 gap-4" id="stats-container">
        <div class="stat-card success rounded-lg p-4 text-white shadow-lg min-w-0">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Tổng đơn hàng</p>
                    <p class="text-2xl font-bold mt-1" id="stat-total">0</p>
                </div>
                <svg class="w-10 h-10 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
        </div>
        <div class="stat-card rounded-lg p-4 text-white shadow-lg min-w-0">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Đã thanh toán</p>
                    <p class="text-2xl font-bold mt-1" id="stat-paid">0</p>
                </div>
                <svg class="w-10 h-10 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
        <div class="stat-card warning rounded-lg p-4 text-white shadow-lg min-w-0">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Chờ thanh toán</p>
                    <p class="text-2xl font-bold mt-1" id="stat-pending">0</p>
                </div>
                <svg class="w-10 h-10 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
        <div class="stat-card danger rounded-lg p-4 text-white shadow-lg min-w-0">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Đã hủy</p>
                    <p class="text-2xl font-bold mt-1" id="stat-cancelled">0</p>
                </div>
                <svg class="w-10 h-10 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Bộ lọc và tìm kiếm -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Tìm kiếm -->
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm</label>
                <div class="relative">
                    <input type="text" id="search-order" placeholder="Tìm theo mã đơn, tên khách hàng..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <!-- Lọc theo trạng thái -->
            <div class="lg:w-64">
                <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                <select id="filter-status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Tất cả</option>
                    <option value="2">Đã thanh toán</option>
                    <option value="1">Chờ thanh toán</option>
                    <option value="0">Đã hủy</option>
                </select>
            </div>

            <!-- Lọc theo ngày -->
            <div class="lg:w-64">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ngày đặt</label>
                <input type="text" id="filter-date" placeholder="Chọn ngày" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Sắp xếp -->
            <div class="lg:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">Sắp xếp</label>
                <select id="sort-orders" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="newest">Mới nhất</option>
                    <option value="oldest">Cũ nhất</option>
                    <option value="price-high">Giá cao → thấp</option>
                    <option value="price-low">Giá thấp → cao</option>
                </select>
            </div>
        </div>

        <!-- Chips lọc nhanh -->
        <div class="mt-4 flex flex-wrap gap-2">
            <button class="filter-chip px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium hover:bg-gray-200" data-filter="all">
                Tất cả
            </button>
            <button class="filter-chip px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium hover:bg-gray-200" data-filter="today">
                Hôm nay
            </button>
            <button class="filter-chip px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium hover:bg-gray-200" data-filter="week">
                Tuần này
            </button>
            <button class="filter-chip px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium hover:bg-gray-200" data-filter="month">
                Tháng này
            </button>
        </div>
    </div>

    <!-- Danh sách đơn hàng -->
    <div id="order-list" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Sẽ được render bởi JavaScript -->
    </div>

    <!-- Empty state -->
    <div id="empty-state" class="hidden text-center py-12 bg-white rounded-lg shadow">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Không có đơn hàng</h3>
        <p class="mt-1 text-sm text-gray-500">Không tìm thấy đơn hàng nào phù hợp với bộ lọc của bạn.</p>
    </div>

    <!-- Pagination -->
    <div id="pagination" class="flex justify-center items-center gap-2"></div>
</div>

<!-- Modal Chi tiết đơn hàng -->
<div id="order-detail-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-white">Chi tiết đơn hàng</h3>
            <button id="close-order-detail" class="text-white hover:text-gray-200 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="order-detail-content" class="flex-1 overflow-y-auto p-6">
            <!-- Render JS -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const idRap = <?php echo $idRap !== null ? (int)$idRap : 'null'; ?>;
    const baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}";
    const orderList = document.getElementById('order-list');
    const searchInput = document.getElementById('search-order');
    const filterStatus = document.getElementById('filter-status');
    const filterDate = document.getElementById('filter-date');
    const sortOrders = document.getElementById('sort-orders');
    const pagination = document.getElementById('pagination');
    const detailModal = document.getElementById('order-detail-modal');
    const closeDetailBtn = document.getElementById('close-order-detail');
    const detailContent = document.getElementById('order-detail-content');
    const emptyState = document.getElementById('empty-state');
    const filterChips = document.querySelectorAll('.filter-chip');

    let orders = [];
    let filteredOrders = [];
    const rowsPerPage = 12;
    let currentPage = 1;

    // Khởi tạo date picker
    flatpickr('#filter-date', {
        dateFormat: 'd/m/Y',
        locale: 'vn',
        mode: 'range',
        placeholder: 'Chọn khoảng ngày'
    });

    // Fetch đơn hàng
    fetch(`${baseUrl}/api/doc-don-hang-theo-rap/${idRap}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && Array.isArray(data.data)) {
                orders = [...data.data];
                filteredOrders = [...orders];
                updateStats();
                renderOrders();
            }
        })
        .catch(err => {
            console.error('Lỗi khi tải đơn hàng:', err);
        });

    function updateStats() {
        const total = orders.length;
        const paid = orders.filter(o => Number(o.trang_thai) === 2).length;
        const pending = orders.filter(o => Number(o.trang_thai) === 1).length;
        const cancelled = orders.filter(o => Number(o.trang_thai) === 0).length;

        document.getElementById('stat-total').textContent = total;
        document.getElementById('stat-paid').textContent = paid;
        document.getElementById('stat-pending').textContent = pending;
        document.getElementById('stat-cancelled').textContent = cancelled;
    }

    function renderOrders(list = filteredOrders, page = currentPage) {
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const pageItems = list.slice(start, end);

        if (pageItems.length === 0) {
            orderList.innerHTML = '';
            emptyState.classList.remove('hidden');
            pagination.innerHTML = '';
            return;
        }

        emptyState.classList.add('hidden');
        orderList.innerHTML = '';

        pageItems.forEach(o => {
            const card = document.createElement('div');
            card.className = 'order-card bg-white rounded-lg shadow-md p-5 border border-gray-200';
            card.innerHTML = `
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-lg font-bold text-blue-600">${o.ma_ve || 'N/A'}</span>
                        </div>
                        <p class="text-sm text-gray-500">${formatDate(o.ngay_dat)}</p>
                    </div>
                    ${renderTrangThai(o.trang_thai)}
                </div>
                
                <div class="space-y-2 mb-4">
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="text-gray-600">${o.user?.ho_ten || 'Khách vãng lai'}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-gray-800 font-semibold text-lg">${Number(o.tong_tien || 0).toLocaleString()} ₫</span>
                    </div>
                </div>

                <button class="view-detail w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors" data-id="${o.id}">
                    Xem chi tiết
                </button>
            `;
            orderList.appendChild(card);
        });

        renderPagination(list);
    }

    function renderPagination(list) {
        const totalPages = Math.ceil(list.length / rowsPerPage);
        pagination.innerHTML = '';

        if (totalPages <= 1) return;

        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        `;
        prevBtn.className = `px-3 py-2 border rounded-lg ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'}`;
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderOrders(list, currentPage);
            }
        });
        pagination.appendChild(prevBtn);

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = `px-4 py-2 border rounded-lg ${i === currentPage ? 'bg-blue-600 text-white border-blue-600' : 'hover:bg-gray-100'}`;
                btn.addEventListener('click', () => {
                    currentPage = i;
                    renderOrders(list, currentPage);
                });
                pagination.appendChild(btn);
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                const span = document.createElement('span');
                span.textContent = '...';
                span.className = 'px-2 py-2';
                pagination.appendChild(span);
            }
        }

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        `;
        nextBtn.className = `px-3 py-2 border rounded-lg ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'}`;
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderOrders(list, currentPage);
            }
        });
        pagination.appendChild(nextBtn);
    }

    function renderTrangThai(trang_thai) {
        const status = Number(trang_thai);
        switch(status) {
            case 2:
                return `<span class="status-badge bg-green-100 text-green-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Đã thanh toán
                </span>`;
            case 1:
                return `<span class="status-badge bg-orange-100 text-orange-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                    Chờ thanh toán
                </span>`;
            case 0:
                return `<span class="status-badge bg-red-100 text-red-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    Đã hủy
                </span>`;
            default:
                return `<span class="status-badge bg-gray-100 text-gray-800">Không xác định</span>`;
        }
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function applyFilters() {
        const searchQuery = searchInput.value.toLowerCase();
        const statusFilter = filterStatus.value;
        const dateFilter = filterDate.value;
        const sortValue = sortOrders.value;

        filteredOrders = orders.filter(o => {
            // Tìm kiếm
            const matchesSearch = !searchQuery || 
                (o.ma_ve || '').toLowerCase().includes(searchQuery) ||
                (o.user?.ho_ten || '').toLowerCase().includes(searchQuery);

            // Lọc trạng thái
            const matchesStatus = !statusFilter || String(o.trang_thai) === statusFilter;

            // Lọc ngày
            let matchesDate = true;
            if (dateFilter) {
                const dates = dateFilter.split(' to ');
                if (dates.length === 2) {
                    const startDate = new Date(dates[0].split('/').reverse().join('-'));
                    const endDate = new Date(dates[1].split('/').reverse().join('-'));
                    const orderDate = new Date(o.ngay_dat);
                    matchesDate = orderDate >= startDate && orderDate <= endDate;
                } else if (dates.length === 1) {
                    const filterDate = new Date(dates[0].split('/').reverse().join('-'));
                    const orderDate = new Date(o.ngay_dat);
                    matchesDate = orderDate.toDateString() === filterDate.toDateString();
                }
            }

            return matchesSearch && matchesStatus && matchesDate;
        });

        // Sắp xếp
        switch(sortValue) {
            case 'newest':
                filteredOrders.sort((a, b) => new Date(b.ngay_dat) - new Date(a.ngay_dat));
                break;
            case 'oldest':
                filteredOrders.sort((a, b) => new Date(a.ngay_dat) - new Date(b.ngay_dat));
                break;
            case 'price-high':
                filteredOrders.sort((a, b) => Number(b.tong_tien) - Number(a.tong_tien));
                break;
            case 'price-low':
                filteredOrders.sort((a, b) => Number(a.tong_tien) - Number(b.tong_tien));
                break;
        }

        currentPage = 1;
        renderOrders();
    }

    // Event listeners
    searchInput.addEventListener('input', applyFilters);
    filterStatus.addEventListener('change', applyFilters);
    filterDate.addEventListener('change', applyFilters);
    sortOrders.addEventListener('change', applyFilters);

    // Filter chips
    filterChips.forEach(chip => {
        chip.addEventListener('click', () => {
            filterChips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');

            const filter = chip.dataset.filter;
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            switch(filter) {
                case 'all':
                    filterDate.value = '';
                    break;
                case 'today':
                    filterDate.value = today.toLocaleDateString('vi-VN').replace(/\//g, '/');
                    break;
                case 'week':
                    const weekStart = new Date(today);
                    weekStart.setDate(today.getDate() - today.getDay() + 1);
                    filterDate.value = `${weekStart.toLocaleDateString('vi-VN').replace(/\//g, '/')} to ${today.toLocaleDateString('vi-VN').replace(/\//g, '/')}`;
                    break;
                case 'month':
                    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                    filterDate.value = `${monthStart.toLocaleDateString('vi-VN').replace(/\//g, '/')} to ${today.toLocaleDateString('vi-VN').replace(/\//g, '/')}`;
                    break;
            }
            applyFilters();
        });
    });

    // Xem chi tiết
    orderList.addEventListener('click', e => {
        if (!e.target.classList.contains('view-detail')) return;

        const id = e.target.dataset.id;
        fetch(`${baseUrl}/api/doc-chi-tiet-don-hang/${id}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    detailContent.innerHTML = `<p class="text-center text-gray-500 py-8">Không có dữ liệu.</p>`;
                    detailModal.classList.remove('hidden');
                    return;
                }

                const dh = Array.isArray(data.data) ? data.data[0] : data.data;
                const isCancelled = Number(dh.trang_thai) === 0;

                const startTime = dh.ve?.[0]?.suat_chieu?.batdau ? new Date(dh.ve[0].suat_chieu.batdau) : null;
                const now = new Date();
                const canCancel = !isCancelled && startTime && (now < new Date(startTime.getTime() - 15 * 60 * 1000));

                let html = `
                    <div class="space-y-4 ${isCancelled ? 'opacity-75' : ''}">
                        ${isCancelled ? `
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-red-700 font-semibold">Đơn hàng đã được hoàn vé</span>
                                </div>
                            </div>
                        ` : ''}

                        <!-- Header với mã vé -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-200">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-gray-600">Mã đơn hàng</p>
                                    <p class="text-2xl font-bold text-blue-600 font-mono">${dh.ma_ve || '-'}</p>
                                </div>
                                ${dh.qr_code ? `<img src="${dh.qr_code}" alt="QR Code" class="w-24 h-24">` : ''}
                            </div>
                        </div>

                        <!-- Thông tin khách hàng và nhân viên -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                <h4 class="font-bold text-lg mb-3 text-blue-600 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Khách hàng
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <p><span class="font-semibold text-gray-700">Họ tên:</span> <span class="text-gray-600">${dh.ve?.[0]?.khachhang?.ho_ten || '-'}</span></p>
                                    <p><span class="font-semibold text-gray-700">Email:</span> <span class="text-gray-600">${dh.ve?.[0]?.khachhang?.email || '-'}</span></p>
                                    <p><span class="font-semibold text-gray-700">SĐT:</span> <span class="text-gray-600">${dh.ve?.[0]?.khachhang?.so_dien_thoai || '-'}</span></p>
                                </div>
                            </div>
                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                <h4 class="font-bold text-lg mb-3 text-green-600 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    Nhân viên
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <p><span class="font-semibold text-gray-700">Họ tên:</span> <span class="text-gray-600">${dh.ve?.[0]?.don_hang?.nhan_vien?.nguoi_dung_internals?.ten || '-'}</span></p>
                                    <p><span class="font-semibold text-gray-700">Email:</span> <span class="text-gray-600">${dh.ve?.[0]?.don_hang?.nhan_vien?.nguoi_dung_internals?.email || '-'}</span></p>
                                    <p><span class="font-semibold text-gray-700">SĐT:</span> <span class="text-gray-600">${dh.ve?.[0]?.don_hang?.nhan_vien?.nguoi_dung_internals?.dien_thoai || '-'}</span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin phim -->
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h5 class="font-bold text-xl flex items-center gap-2 mb-2">
                                ${dh.ve?.[0]?.suat_chieu?.phim?.ten_phim || 'Không xác định'}
                                <span class="inline-block px-2 py-1 text-xs font-semibold text-white bg-red-500 rounded">
                                    ${dh.ve?.[0]?.suat_chieu?.phim?.do_tuoi || 'C'}
                                </span>
                            </h5>
                        </div>

                        <!-- Thông tin rạp và suất chiếu -->
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h5 class="font-bold text-lg mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Thông tin suất chiếu
                            </h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div class="space-y-2">
                                    <p><span class="font-semibold text-gray-700">Rạp:</span> <span class="text-gray-600">${dh.ve?.[0]?.suat_chieu?.phong_chieu?.rap_chieu_phim?.ten || '-'}</span></p>
                                    <p><span class="font-semibold text-gray-700">Phòng:</span> <span class="text-gray-600">${dh.ve?.[0]?.suat_chieu?.phong_chieu?.ten || '-'}</span></p>
                                    <p><span class="font-semibold text-gray-700">Loại phòng:</span> <span class="text-gray-600">${(dh.ve?.[0]?.suat_chieu?.phong_chieu?.loai_phongchieu || '-').toUpperCase()}</span></p>
                                </div>
                                <div class="space-y-2">
                                    <p><span class="font-semibold text-gray-700">Ngày chiếu:</span> <span class="text-gray-600">${startTime ? startTime.toLocaleDateString('vi-VN', { weekday:'long', day:'2-digit', month:'2-digit', year:'numeric' }) : '-'}</span></p>
                                    <p><span class="font-semibold text-gray-700">Thời gian:</span> <span class="text-gray-600">${startTime ? startTime.toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'}) : '-'} - ${dh.ve?.[0]?.suat_chieu?.ketthuc ? new Date(dh.ve[0].suat_chieu.ketthuc).toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'}) : '-'}</span></p>
                                    <p><span class="font-semibold text-gray-700">Ghế:</span> <span class="text-gray-600 font-mono">${dh.ve?.map(v=>v.ghe?.so_ghe).filter(Boolean).join(', ') || '-'}</span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Chi tiết thanh toán -->
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h5 class="font-bold text-lg mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Chi tiết thanh toán
                            </h5>
                            <div class="space-y-3">
                                ${dh.ve?.flatMap(v=>v.don_hang?.chi_tiet_don_hang||[]).length > 0 ? `
                                    <div>
                                        <p class="font-semibold text-sm text-gray-700 mb-2">Thức ăn kèm:</p>
                                        ${dh.ve.flatMap(v=>v.don_hang?.chi_tiet_don_hang||[]).map(item=> `
                                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                                <span class="text-gray-600">${item.san_pham?.ten || '-'} x ${item.so_luong || 0}</span>
                                                <span class="font-semibold text-gray-800">${Number(item.thanh_tien || 0).toLocaleString()} ₫</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                ` : ''}
                                
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Thẻ quà tặng sử dụng:</span>
                                    <span class="font-semibold text-gray-800">${dh.the_qua_tang_su_dung > 0 ? `-${Number(dh.the_qua_tang_su_dung || 0).toLocaleString()} ₫` : '0 ₫'}</span>
                                </div>
                                
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Phương thức thanh toán:</span>
                                    <span class="font-semibold text-gray-800">${dh.phuong_thuc_thanh_toan===1?'Chuyển khoản':dh.phuong_thuc_thanh_toan===2?'Tiền mặt':'Không xác định'}</span>
                                </div>
                                
                                <div class="flex justify-between items-center pt-2">
                                    <span class="text-lg font-bold text-gray-800">Tổng tiền:</span>
                                    <span class="text-xl font-bold text-blue-600">${Number(dh.tong_tien || 0).toLocaleString()} ₫</span>
                                </div>
                            </div>
                        </div>

                        ${canCancel ? `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <button id="btnCancelTicket" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Hoàn vé
                            </button>
                        </div>` : ''}
                    </div>
                `;

                detailContent.innerHTML = html;
                detailModal.classList.remove('hidden');

                // Gắn event listener cho nút Hoàn vé nếu có
                const btnCancelTicket = document.getElementById('btnCancelTicket');
                btnCancelTicket?.addEventListener('click', async () => {
                    if(!confirm("LƯU Ý: Số tiền đã thanh toán sẽ được hoàn lại vào Thẻ quà tặng EPIC.\nBạn có chắc muốn hoàn vé này?")) return;

                    try {
                        await fetch(`${baseUrl}/api/cap-nhat-trang-thai-don-hang`, {
                            method:'PUT',
                            headers:{'Content-Type':'application/json'},
                            body: JSON.stringify({id: dh.id})
                        });

                        for(const v of dh.ve){
                            const donHangId = v.don_hang?.id;
                            if(donHangId){
                                await fetch(`${baseUrl}/api/cap-nhat-trang-thai-ve`, {
                                    method:'PUT',
                                    headers:{'Content-Type':'application/json'},
                                    body: JSON.stringify({donhang_id: donHangId})
                                });
                            }
                        }

                        alert(`Vé ${dh.ma_ve || dh.id} đã được hủy. Số tiền hoàn lại đã vào thẻ quà tặng.`);
                        detailModal.classList.add('hidden');

                        orders = orders.map(o => o.id === dh.id ? {...o, trang_thai:0} : o);
                        filteredOrders = filteredOrders.map(o => o.id === dh.id ? {...o, trang_thai:0} : o);
                        updateStats();
                        renderOrders();
                    } catch(err){
                        console.error(err);
                        alert("Lỗi khi hủy vé: "+err.message);
                    }
                });
            })
            .catch(err => {
                console.error('Lỗi khi tải chi tiết đơn hàng:', err);
                detailContent.innerHTML = `<p class="text-center text-red-500 py-8">Lỗi khi tải dữ liệu. Vui lòng thử lại.</p>`;
                detailModal.classList.remove('hidden');
            });
    });

    closeDetailBtn.addEventListener('click', () => detailModal.classList.add('hidden'));
    detailModal.addEventListener('click', (e) => {
        if (e.target === detailModal) detailModal.classList.add('hidden');
    });
});
</script>
@endsection

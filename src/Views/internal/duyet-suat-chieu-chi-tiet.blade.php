@extends('internal.layout')

@section('title', 'Duyệt suất chiếu - Chi tiết rạp')

@section('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/duyet-suat-chieu-chi-tiet.js"></script>
<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/duyet-ke-hoach.js"></script>
<style>
    .flatpickr-calendar { z-index: 9999 !important; }
    .time-slot { cursor: pointer; transition: all 0.2s; }
    .time-slot:hover { background-color: #f3f4f6; }
    
    /* Tab styling */
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
</style>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-1 text-gray-500 hover:text-gray-700 text-sm font-medium">Danh sách rạp</span>
    </div>
</li>
<li>
    <div class="flex items-center ml-4 space-x-2">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <div class="flex rounded-md shadow-sm">
            <button id="tab-btn-suatchieu" class="tab-btn px-4 py-2 text-sm font-medium rounded-l-md bg-red-600 text-white" aria-current="page">
                Suất chiếu
            </button>
            <button id="tab-btn-kehoach" class="tab-btn px-4 py-2 text-sm font-medium rounded-r-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
                Kế hoạch
            </button>
        </div>
    </div>
</li>
@endsection

@section('content')
<!-- Tab Container -->
<div class="tab-container">
    <!-- Tab: Suất chiếu -->
    <div id="tab-suatchieu" class="tab-content active">
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-900">Duyệt suất chiếu</h1>
        <button id="btn-log" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-md flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-6 4h6a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Nhật ký
            <span id="log-badge" class="ml-2 inline-block min-w-[20px] px-1.5 py-0.5 rounded-full bg-red-600 text-white text-xs font-bold align-middle hidden"></span>
        </button>
    </div>
    <p id="cinema-name" class="text-sm text-gray-600 mt-1" data-soSuatChuaXem="{{$rapPhim['so_suat_chua_xem'] ?? 0}}">
        Rạp: <span class="font-medium">{{$rapPhim['ten']}}</span>
    </p>

    <div class="mb-6">
        <div class="flex justify-between items-center mb-2">
            <h2 class="text-sm font-medium text-gray-700">Chọn ngày chiếu</h2>
            <div class="flex items-center space-x-2">
                <button id="prev-week" class="p-1 rounded-md hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
                <span id="week-range" class="text-sm font-medium">—</span>
                <button id="next-week" class="p-1 rounded-md hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
        <div id="date-nav-container" class="grid grid-cols-7 gap-1"></div>
        <input type="hidden" id="date-picker" value="">
        <div class="mt-6 flex justify-between items-center">
            <div id="week-status" class="text-sm text-gray-600"></div>
        </div>
    </div>
    <div id="showtime-listing" class="space-y-6 overflow-auto max-h-[60vh]" data-url="{{$_ENV['URL_WEB_BASE']}}" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}" data-rap="{{$rapPhim['id'] ?? ''}}">
    </div>

    <!-- Modal nhập lý do từ chối -->
    <div id="reject-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-gray-900">Nhập lý do từ chối</h2>
            </div>
            <div class="p-6">
                <textarea id="reject-reason" rows="4" class="w-full border rounded-md p-2" placeholder="Lý do từ chối..."></textarea>
                <div class="flex justify-end space-x-3 mt-4">
                    <button id="btn-cancel-reject" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md">Hủy</button>
                    <button id="btn-confirm-reject" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">Xác nhận</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Overlay modal for log -->
    <div id="log-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="p-6 border-b flex justify-between items-center bg-gradient-to-r from-blue-600 to-blue-700">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-6 4h6a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Nhật ký suất chiếu
                </h2>
                <button id="btn-close-log" class="text-white hover:text-gray-200 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="overflow-y-auto px-6 py-4 flex-1 max-h-[70vh] bg-gray-50" id="log-content">
                <div class="text-gray-500 text-center py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>Đang tải nhật ký...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="fixed bottom-4 right-4 px-4 py-2 bg-green-500 text-white rounded-md shadow-lg transform transition-transform duration-300 translate-y-20 opacity-0">
        <span id="toast-message">Thao tác thành công</span>
    </div>
</div>
    </div>

    <!-- Tab: Kế hoạch -->
    <div id="tab-kehoach" class="tab-content">
        <div class="bg-white rounded-lg shadow-md p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Duyệt kế hoạch suất chiếu tuần</h1>
                    <p class="text-sm text-gray-500 mt-1">Xem và duyệt kế hoạch chiếu phim từng tuần (Thứ Hai đến Chủ Nhật)</p>
                    <p class="text-sm text-gray-600 mt-1">Rạp: <span class="font-medium">{{$rapPhim['ten']}}</span></p>
                </div>
            </div>

            <!-- Thông tin tuần kế hoạch hiện tại với navigation -->
            <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button id="plan-btn-prev-week" class="p-2 rounded-md hover:bg-blue-100 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="text-center">
                            <p class="text-sm text-gray-600 mb-1">Tuần kế hoạch</p>
                            <p id="plan-week-range" class="text-lg font-bold text-blue-900">13/10 - 19/10/2025</p>
                            <p id="plan-week-offset-label" class="text-xs text-gray-500 mt-1">Tuần kế tiếp</p>
                        </div>
                        <button id="plan-btn-next-week" class="p-2 rounded-md hover:bg-blue-100 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <div class="text-right">
                        <p id="plan-total-showtimes-badge" class="inline-block px-4 py-2 bg-blue-600 text-white font-bold rounded-full text-sm">0 suất chiếu</p>
                    </div>
                </div>
            </div>

            <!-- Danh sách kế hoạch tuần -->
            <div id="plan-approval-listing" class="space-y-6" data-url="{{$_ENV['URL_WEB_BASE']}}" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}" data-rap="{{$rapPhim['id'] ?? ''}}">
                <!-- State: Chưa có kế hoạch -->
                <div id="plan-empty-state" class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Chưa có kế hoạch cho tuần này</h3>
                    <p class="text-gray-500">Rạp chưa tạo kế hoạch cho tuần này</p>
                </div>

                <!-- State: Có kế hoạch - Sẽ được render bởi JavaScript -->
                <div id="plan-content" class="hidden">
                    <!-- Header kế hoạch -->
                    <div class="flex items-center justify-between mb-6 pb-4 border-b">
                        <div class="flex items-center space-x-4">
                            <h2 class="text-lg font-semibold text-gray-900">Danh sách suất chiếu</h2>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button id="btn-approve-all-plan" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md shadow-sm transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Duyệt toàn bộ tuần
                            </button>
                        </div>
                    </div>

                    <!-- Danh sách suất chiếu theo ngày -->
                    <div id="plan-showtimes-by-day" class="space-y-4">
                        <!-- Sẽ được render bởi JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal xem chi tiết suất chiếu -->
        <div id="plan-detail-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
                <div class="p-6 border-b flex justify-between items-center bg-gradient-to-r from-blue-600 to-indigo-600">
                    <h2 class="text-xl font-bold text-white">Chi tiết suất chiếu</h2>
                    <button id="btn-close-plan-detail" class="text-white hover:text-gray-200 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="plan-detail-content" class="p-6">
                    <!-- Nội dung chi tiết sẽ được render bởi JavaScript -->
                </div>
                <div class="flex justify-end space-x-3 px-6 py-4 border-t bg-gray-50">
                    <button id="btn-reject-plan-showtime" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">Từ chối</button>
                    <button id="btn-approve-plan-showtime" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">Duyệt</button>
                </div>
            </div>
        </div>

        <!-- Toast notification -->
        <div id="plan-toast" class="fixed bottom-4 right-4 px-4 py-2 bg-green-500 text-white rounded-md shadow-lg transform transition-transform duration-300 translate-y-20 opacity-0 z-50">
            <span id="plan-toast-message">Thao tác thành công</span>
        </div>
    </div>
</div>

<!-- JavaScript xử lý tab -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => {
                btn.classList.remove('bg-red-600', 'text-white');
                btn.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-200');
                btn.removeAttribute('aria-current');
            });
            
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Add active class to clicked button
            button.classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-200');
            button.classList.add('bg-red-600', 'text-white');
            button.setAttribute('aria-current', 'page');
            
            // Show corresponding content
            const tabId = button.id.replace('tab-btn-', 'tab-');
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>

@endsection



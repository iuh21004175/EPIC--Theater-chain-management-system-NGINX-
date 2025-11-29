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
        animation: fadeIn 0.4s ease-in-out;
    }
    .tab-content.active {
        display: block;
    }
    
    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    /* Modal animation */
    #plan-detail-modal-inner {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Smooth transitions for all interactive elements */
    button, .cursor-pointer {
        transition: all 0.2s ease-in-out;
    }
    
    /* Custom scrollbar */
    #plan-approval-listing::-webkit-scrollbar {
        width: 8px;
    }
    
    #plan-approval-listing::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }
    
    #plan-approval-listing::-webkit-scrollbar-thumb {
        background: linear-gradient(to bottom, #3b82f6, #6366f1);
        border-radius: 10px;
    }
    
    #plan-approval-listing::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(to bottom, #2563eb, #4f46e5);
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
        <div class="bg-gradient-to-br from-gray-50 to-blue-50 rounded-2xl shadow-xl p-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-1.5 h-10 bg-gradient-to-b from-blue-500 to-indigo-600 rounded-full"></div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                            Duyệt kế hoạch suất chiếu tuần
                        </h1>
                    </div>
                    <p class="text-sm text-gray-600 ml-5 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Xem và duyệt kế hoạch chiếu phim từng tuần (Thứ Hai đến Chủ Nhật)
                    </p>
                    <p class="text-sm text-gray-700 mt-2 ml-5 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Rạp: <span class="font-semibold text-blue-700">{{$rapPhim['ten']}}</span>
                    </p>
                </div>
            </div>

            <!-- Thông tin tuần kế hoạch hiện tại với navigation -->
            <div class="mb-8 p-6 bg-white rounded-2xl shadow-lg border border-blue-100 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button id="plan-btn-prev-week" class="group p-3 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-sm hover:shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 group-hover:text-blue-700 transition-colors" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="text-center px-4">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Tuần kế hoạch</p>
                            <p id="plan-week-range" class="text-xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">13/10 - 19/10/2025</p>
                            <p id="plan-week-offset-label" class="text-xs font-medium text-blue-600 mt-2 px-3 py-1 bg-blue-50 rounded-full inline-block">Tuần kế tiếp</p>
                        </div>
                        <button id="plan-btn-next-week" class="group p-3 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-sm hover:shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 group-hover:text-blue-700 transition-colors" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <div class="text-right">
                        <p id="plan-total-showtimes-badge" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-2xl text-sm shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                            </svg>
                            0 suất chiếu
                        </p>
                    </div>
                </div>
            </div>

            <!-- Danh sách kế hoạch tuần -->
            <div id="plan-approval-listing" class="space-y-6" data-url="{{$_ENV['URL_WEB_BASE']}}" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}" data-rap="{{$rapPhim['id'] ?? ''}}">
                <!-- State: Chưa có kế hoạch -->
                <div id="plan-empty-state" class="text-center py-20">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-3xl mb-6 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Chưa có kế hoạch cho tuần này</h3>
                    <p class="text-gray-600 mb-6">Rạp chưa tạo kế hoạch cho tuần này</p>
                    <div class="inline-flex items-center px-4 py-2 bg-blue-50 text-blue-700 rounded-xl text-sm font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Vui lòng chọn tuần khác hoặc đợi rạp tạo kế hoạch
                    </div>
                </div>

                <!-- State: Có kế hoạch - Sẽ được render bởi JavaScript -->
                <div id="plan-content" class="hidden">
                    <!-- Header kế hoạch -->
                    <div class="flex items-center justify-between mb-6 pb-6 border-b-2 border-gray-200">
                        <div class="flex items-center space-x-4">
                            <div class="w-1 h-8 bg-gradient-to-b from-green-500 to-emerald-600 rounded-full"></div>
                            <h2 class="text-xl font-bold text-gray-900">Danh sách suất chiếu</h2>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button id="btn-approve-all-plan" class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 group-hover:animate-pulse" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Duyệt toàn bộ tuần
                            </button>
                        </div>
                    </div>

                    <!-- Danh sách suất chiếu theo ngày -->
                    <div id="plan-showtimes-by-day" class="space-y-6">
                        <!-- Sẽ được render bởi JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal xem chi tiết suất chiếu -->
        <div id="plan-detail-modal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center hidden z-50 transition-all duration-300">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl transform transition-all duration-300 scale-95 opacity-0" id="plan-detail-modal-inner">
                <div class="p-8 border-b flex justify-between items-center bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-t-2xl">
                    <div class="flex items-center space-x-3">
                        <div class="w-1.5 h-8 bg-white rounded-full opacity-80"></div>
                        <h2 class="text-2xl font-bold text-white">Chi tiết suất chiếu</h2>
                    </div>
                    <button id="btn-close-plan-detail" class="group p-2 rounded-xl hover:bg-white hover:bg-opacity-20 transition-all duration-300 transform hover:scale-110 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white group-hover:rotate-90 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="plan-detail-content" class="p-8 min-h-[200px]">
                    <!-- Nội dung chi tiết sẽ được render bởi JavaScript -->
                </div>
                <div class="modal-footer flex justify-end space-x-3 px-8 py-6 border-t bg-gradient-to-br from-gray-50 to-blue-50 rounded-b-2xl">
                    <button id="btn-reject-plan-showtime" class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 group-hover:animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Từ chối
                    </button>
                    <button id="btn-approve-plan-showtime" class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 group-hover:animate-pulse" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Duyệt
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast notification -->
        <div id="plan-toast" class="fixed bottom-8 right-8 px-6 py-4 bg-white border-l-4 rounded-xl shadow-2xl transform transition-all duration-300 translate-y-32 opacity-0 z-50 min-w-[300px]">
            <div class="flex items-center space-x-3">
                <div id="plan-toast-icon" class="flex-shrink-0">
                    <!-- Icon sẽ được thay đổi theo type -->
                </div>
                <span id="plan-toast-message" class="font-medium text-gray-800">Thao tác thành công</span>
            </div>
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



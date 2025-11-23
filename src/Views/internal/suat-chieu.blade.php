@extends('internal.layout')

@section('title', 'Quản lý suất chiếu')

@section('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/suat-chieu.js"></script>
<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/ke-hoach.js"></script>
<script defer src="{{$_ENV['URL_INTERNAL_BASE']}}/js/colorful-day-buttons.js"></script>
<style>
    .flatpickr-calendar {
        z-index: 9999 !important;
    }
    .time-slot {
        cursor: pointer;
        transition: all 0.2s;
    }
    .time-slot:hover {
        background-color: #f3f4f6;
    }
    .time-slot.selected {
        background-color: #2563eb !important;
        color: #fff !important;
        border-color: #2563eb !important;
        font-weight: bold;
    }
    
    /* Tab styling */
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
    
    /* Calendar Day Button Styling */
    .plan-day-btn {
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    
    /* Normal state - Làm nổi bật viền và tạo màu nền nhẹ */
    .plan-day-btn:not(.bg-green-600):not(.active) {
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
    }
    
    /* Hover state - Hiệu ứng hover mạnh hơn */
    .plan-day-btn:hover:not(.bg-green-600):not(.active) {
        background-color: #f3f4f6;
        border-color: #d1d5db;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Selected state - Làm nổi bật button đang chọn */
    .plan-day-btn.bg-green-600, .plan-day-btn.active {
        box-shadow: 0 2px 4px rgba(22, 163, 74, 0.3);
        transform: translateY(-1px);
        font-weight: 600;
    }
    
    /* Calendar trong tab suất chiếu */
    #date-nav-container button {
        position: relative;
        overflow: hidden;
        transition: all 0.2s;
    }
    
    #date-nav-container button:not(.bg-blue-600):hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    #date-nav-container button.bg-blue-600 {
        box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);
    }
    
    /* Làm nổi bật các button trong ngày trong tuần */
    #date-nav-container button:nth-child(1) { border-left: 3px solid #3b82f6; }
    #date-nav-container button:nth-child(2) { border-left: 3px solid #10b981; }
    #date-nav-container button:nth-child(3) { border-left: 3px solid #8b5cf6; }
    #date-nav-container button:nth-child(4) { border-left: 3px solid #f59e0b; }
    #date-nav-container button:nth-child(5) { border-left: 3px solid #ef4444; }
    #date-nav-container button:nth-child(6) { border-left: 3px solid #6366f1; }
    #date-nav-container button:nth-child(7) { border-left: 3px solid #ec4899; }
</style>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-1 text-gray-500 hover:text-gray-700 text-sm font-medium">Quản lý suất chiếu</span>
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
            <div class="mb-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                <div class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div class="text-sm text-blue-700">
                        <p class="font-semibold mb-1">Xem và quản lý suất chiếu thực tế</p>
                        <p>Tab này dùng để xem các suất chiếu đang hoạt động (từ kế hoạch đã áp dụng). Để thêm suất chiếu mới, vui lòng sử dụng tab <strong>Kế hoạch</strong> để lập kế hoạch, sau đó áp dụng vào suất chiếu thực tế.</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-xl font-bold text-gray-900">Quản lý suất chiếu</h1>
                <button id="btn-log" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-md flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-6 4h6a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Nhật ký
                    <span id="log-badge" class="ml-2 inline-block min-w-[20px] px-1.5 py-0.5 rounded-full bg-red-600 text-white text-xs font-bold align-middle hidden"></span>
                </button>
            </div>

    <!-- Overlay modal for log -->
    <div id="log-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="p-6 border-b flex justify-between items-center bg-gradient-to-r from-blue-600 to-blue-700">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
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
                <!-- Nội dung nhật ký sẽ được cập nhật bằng JavaScript -->
                <div class="text-gray-500 text-center py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <p>Đang tải nhật ký...</p>
                </div>
            </div>
        </div>
    </div>    <!-- Bộ lọc và chọn ngày - Phương án 1: Navigation theo tuần -->
    <div class="mb-6">
        <div class="flex justify-between items-center mb-2">
            <h2 class="text-sm font-medium text-gray-700">Chọn ngày chiếu</h2>
            <div class="flex items-center space-x-2">
                <button id="prev-week" class="p-1 rounded-md hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
                <span id="week-range" class="text-sm font-medium">03/09 - 09/09/2025</span>
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

    <!-- Danh sách phim và suất chiếu -->
    <div id="showtime-listing" class="space-y-6 overflow-auto max-h-[60vh]" data-url="{{$_ENV['URL_WEB_BASE']}}" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}">
        <!-- Nội dung sẽ được cập nhật bằng JavaScript -->
        
    </div>

    <!-- Modal cập nhật suất chiếu (chỉ dùng để sửa, không thêm mới) -->
    <div id="showtime-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
            <div class="p-6 border-b flex justify-between items-center">
                <h2 id="modal-title" class="text-xl font-bold text-gray-900">Cập nhật suất chiếu</h2>
                <button id="btn-close-modal" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="overflow-y-auto px-6 py-4 flex-1 max-h-[60vh]">
                <form id="showtime-form">
                    <input type="hidden" id="showtime-id" name="id" value="">
                    <input type="hidden" id="showtime-date" name="date" value="">

                    <!-- Chọn phim -->
                    <div class="mb-4">
                        <label for="movie-search" class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm phim</label>
                        <div class="relative">
                            <input type="text" id="movie-search" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm" placeholder="Nhập tên phim..." autocomplete="off">
                            <input type="hidden" id="selected-movie-id" name="movie_id">
                            <div id="movie-search-results" class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-md hidden max-h-60 overflow-auto"></div>
                        </div>
                        <div id="selected-movie-info" class="mt-2 hidden">
                            <div class="flex items-center p-2 border rounded-md bg-gray-50">
                                <img id="selected-movie-poster" src="" alt="Movie poster" class="w-12 h-16 object-cover mr-3">
                                <div>
                                    <h4 id="selected-movie-title" class="font-medium"></h4>
                                    <p id="selected-movie-duration" class="text-sm text-gray-600"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chọn phòng chiếu -->
                    <div class="mb-4">
                        <label for="room-select" class="block text-sm font-medium text-gray-700 mb-1">Phòng chiếu</label>
                        <select id="room-select" name="room_id" multiple class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm">
                            <option value="">-- Chọn phòng chiếu --</option>
                            <!-- Danh sách phòng sẽ được tải bằng JavaScript -->
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Giữ Ctrl (Windows) hoặc Cmd (Mac) để chọn nhiều phòng</p>
                    </div>

                    <!-- Khung giờ -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian chiếu</label>
                        <div class="flex space-x-2" id="single-time-row">
                            <div class="flex-1">
                                <label for="start-time" class="block text-xs text-gray-500 mb-1">Giờ bắt đầu</label>
                                <input type="text" id="start-time" name="start_time" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm" placeholder="Chọn giờ bắt đầu">
                            </div>
                            <div class="flex-1">
                                <label for="end-time" class="block text-xs text-gray-500 mb-1">Giờ kết thúc</label>
                                <input type="text" id="end-time" name="end_time" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm" disabled>
                            </div>
                        </div>
                        <div id="per-room-times" class="space-y-4 hidden"></div>
                    </div>

                    <!-- Khung giờ gợi ý -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Khung giờ gợi ý</label>
                        <div id="suggested-times" class="space-y-4">
                            <!-- Các khung giờ sẽ được tạo bằng JavaScript -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="flex justify-end space-x-3 mt-6 px-6 py-3 border-t">
                <button type="button" id="btn-cancel" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md">Hủy</button>
                <button type="submit" form="showtime-form" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">Lưu</button>
            </div>
        </div>
    </div>

    <!-- Modal xác nhận -->
    <div id="confirm-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Xác nhận xóa</h2>
                <p class="text-gray-700 mb-6">Bạn có chắc chắn muốn xóa suất chiếu này? Hành động này không thể hoàn tác.</p>
                <div class="flex justify-end space-x-3">
                    <button id="btn-cancel-delete" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md">Hủy</button>
                    <button id="btn-confirm-delete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">Xóa</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast notification -->
    <div id="toast" class="fixed bottom-4 right-4 px-4 py-2 bg-green-500 text-white rounded-md shadow-lg transform transition-transform duration-300 translate-y-20 opacity-0">
        <span id="toast-message">Thao tác thành công</span>
    </div>
        </div>
    </div>

    <!-- Tab: Kế hoạch -->
    <div id="tab-kehoach" class="tab-content">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded">
                <div class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div class="text-sm text-green-700">
                        <p class="font-semibold mb-1">Kế hoạch suất chiếu tuần</p>
                        <p class="mb-2">Tab này dùng để lập kế hoạch chiếu phim cho các tuần tới. Workflow: <strong>Tạo kế hoạch → Gửi duyệt → Sau khi duyệt → Áp dụng vào suất chiếu thực tế</strong>.</p>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li>Có thể sao chép kế hoạch từ tuần trước để tiết kiệm thời gian</li>
                            <li>Sau khi kế hoạch được duyệt, sử dụng nút "Áp dụng kế hoạch" để chuyển sang suất chiếu thực tế</li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Kế hoạch suất chiếu tuần</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Tạo kế hoạch chiếu phim cho tuần tới. Sau khi duyệt, kế hoạch sẽ được áp dụng vào suất chiếu thực tế.
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <button id="btn-copy-from-last-week" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium flex items-center transition" title="Sao chép kế hoạch từ tuần trước">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                            <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
                        </svg>
                        Sao chép từ tuần trước
                    </button>
                    <button id="btn-apply-plan" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium flex items-center transition hidden" title="Áp dụng kế hoạch đã duyệt vào suất chiếu thực tế">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Áp dụng kế hoạch
                    </button>
                </div>
            </div>

            <!-- Thông tin tuần kế hoạch hiện tại với navigation -->
            <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="bg-blue-600 p-3 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600">Tuần kế hoạch</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <!-- Navigation buttons -->
                                <button id="btn-prev-week" class="p-1.5 rounded-md hover:bg-blue-100 text-blue-700 transition" title="Tuần trước">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <p id="plan-week-range" class="text-xl font-bold text-blue-900 min-w-[200px] text-center">Đang tải...</p>
                                <button id="btn-next-week" class="p-1.5 rounded-md hover:bg-blue-100 text-blue-700 transition" title="Tuần sau">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                            <!-- Label hiển thị offset tuần -->
                            <p id="week-offset-label" class="text-xs text-gray-600 mt-1 text-center"></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div id="total-showtimes-badge" class="text-sm text-gray-600 mb-2">0 suất chiếu</div>
                        <div id="plan-status-info" class="text-xs text-gray-500"></div>
                    </div>
                </div>
            </div>

            <!-- Danh sách kế hoạch tuần -->
            <div id="plan-listing" class="space-y-6" data-url="{{$_ENV['URL_WEB_BASE']}}" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}">
                <!-- State: Chưa có kế hoạch -->
                <div id="empty-state" class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Chưa có kế hoạch cho tuần này</h3>
                    <p class="text-gray-500 mb-6">Tạo kế hoạch mới để bắt đầu lên lịch chiếu phim</p>
                    <div class="flex items-center justify-center space-x-3">
                        <button id="btn-create-new-plan" class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Tạo kế hoạch mới
                        </button>
                        <button id="btn-copy-template-empty" class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                                <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
                            </svg>
                            Sao chép từ tuần trước
                        </button>
                    </div>
                </div>

                <!-- State: Có kế hoạch - Sẽ được render bởi JavaScript -->
                <div id="plan-content" class="hidden">
                    <!-- Header kế hoạch -->
                    <div class="flex items-center justify-between mb-6 pb-4 border-b">
                        <div class="flex items-center space-x-3">
                            <h2 class="text-lg font-bold text-gray-900">Kế hoạch tuần</h2>
                            <span id="plan-week-label" class="text-sm text-gray-500"></span>
                            <span id="plan-status-badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"></span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button id="btn-copy-template-existing" class="px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-md text-sm font-medium flex items-center transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                                    <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
                                </svg>
                                Sao chép từ tuần trước
                            </button>
                        </div>
                    </div>

                    <!-- Danh sách suất chiếu theo ngày -->
                    <div id="showtimes-by-day" class="space-y-4">
                        <!-- Nội dung sẽ được render bởi JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal thêm suất chiếu vào kế hoạch -->
        <div id="plan-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] flex flex-col">
                <div class="p-6 border-b flex justify-between items-center bg-gradient-to-r from-blue-600 to-indigo-600">
                    <div>
                        <h2 class="text-xl font-bold text-white">Thêm suất chiếu vào kế hoạch</h2>
                        <p class="text-blue-100 text-sm mt-1">Có thể thêm nhiều suất chiếu cho cùng một ngày</p>
                    </div>
                    <button id="btn-close-plan-modal" class="text-white hover:text-gray-200 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="overflow-y-auto px-6 py-4 flex-1">


                    <!-- Danh sách suất chiếu cho ngày đang chọn (single container) -->
                    <div id="showtimes-list" class="space-y-4 mb-4">
                        <!-- JS sẽ render form suất chiếu cho ngày được chọn -->
                    </div>

                    <!-- Nút thêm suất chiếu mới -->
                    <button 
                        type="button" 
                        id="btn-add-another-showtime"
                        class="w-full py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-500 hover:text-blue-600 hover:bg-blue-50 transition flex items-center justify-center font-medium"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Thêm suất chiếu khác cho cùng ngày
                    </button>
                </div>
                <div class="flex justify-between items-center px-6 py-4 border-t bg-gray-50">
                    <p class="text-sm text-gray-500">
                        <span class="text-red-500">*</span> Thông tin bắt buộc · Tổng: <span id="total-showtimes-count" class="font-bold text-blue-600">1</span> suất chiếu
                    </p>
                    <div class="flex space-x-3">
                        <button 
                            type="button" 
                            id="btn-cancel-plan" 
                            class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md font-medium transition"
                        >
                            Hủy
                        </button>
                        <button 
                            type="button" 
                            id="btn-save-all-showtimes"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium shadow-sm transition flex items-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Lưu
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal xác nhận xóa -->
        <div id="plan-confirm-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Xác nhận xóa</h2>
                    <p class="text-gray-700 mb-6">Bạn có chắc chắn muốn xóa kế hoạch này?</p>
                    <div class="flex justify-end space-x-3">
                        <button id="btn-cancel-plan-delete" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md">Hủy</button>
                        <button id="btn-confirm-plan-delete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">Xóa</button>
                    </div>
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
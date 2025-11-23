<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đặt lịch gọi video tư vấn - EPIC CINEMAS</title>
<link rel="stylesheet" href="{{ $_ENV['URL_WEB_BASE'] }}/css/tailwind.css">
<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

@include('customer.layout.header')

<main>
    <!-- Header Section -->
    <section class="container mx-auto max-w-screen-xl px-4 mt-6">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Đặt lịch gọi video tư vấn</h1>
            <p class="text-lg text-gray-600">Chọn ngày và thời gian phù hợp để được tư vấn trực tiếp</p>
        </div>
    </section>

    <!-- Lịch làm việc theo tháng -->
    <section class="w-full px-4 mt-8 mb-8">
        <div class="w-full max-w-screen-xl mx-auto bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center mb-4">
                <div class="w-1 h-6 bg-red-600 mr-2"></div>
                <h3 class="text-xl font-bold">Lịch Đặt Tư Vấn</h3>
            </div>

            <!-- Điều hướng tháng -->
            <div class="flex items-center justify-between mb-6">
                <button id="prev-month" class="text-gray-400 hover:text-red-500 transition-colors p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <span id="current-month" class="text-lg font-medium text-gray-900" data-url="{{$_ENV['URL_WEB_BASE']}}"></span>

                <button id="next-month" class="text-gray-400 hover:text-red-500 transition-colors p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <hr class="border-t-2 border-red-500 w-full mx-auto mb-6">

            <!-- Calendar Grid -->
            <div class="overflow-x-auto">
                <div class="min-w-full">
                    <!-- Header ngày trong tuần -->
                    <div class="grid grid-cols-7 gap-1 mb-2">
                        <div class="text-center py-2 font-medium text-gray-800">T2</div>
                        <div class="text-center py-2 font-medium text-gray-800">T3</div>
                        <div class="text-center py-2 font-medium text-gray-800">T4</div>
                        <div class="text-center py-2 font-medium text-gray-800">T5</div>
                        <div class="text-center py-2 font-medium text-gray-800">T6</div>
                        <div class="text-center py-2 font-medium text-gray-800">T7</div>
                        <div class="text-center py-2 font-medium text-red-600">CN</div>
                    </div>
                    
                    <!-- Calendar days -->
                    <div id="calendar-grid" class="grid grid-cols-7 gap-1">
                        <!-- Calendar content sẽ được load động -->
                    </div>
                </div>
            </div>

            <!-- Chú thích -->
            <div class="mt-6 flex flex-wrap items-center justify-center gap-6 text-sm">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-white border border-gray-300 rounded mr-2"></div>
                    <span class="text-gray-600">Có thể đặt</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-gray-100 border border-gray-300 rounded mr-2"></div>
                    <span class="text-gray-600">Đã qua</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-red-100 border border-red-300 rounded mr-2"></div>
                    <span class="text-gray-600">Ngày hiện tại</span>
                </div>
            </div>
            
            <!-- Danh sách các cuộc gọi video đã đặt -->
            <div class="mt-8 border-t pt-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-1 h-6 bg-blue-600 mr-2"></div>
                        <h3 class="text-xl font-bold">Lịch tư vấn đã đặt</h3>
                    </div>
                    <div class="flex items-center">
                        <span class="mr-3 text-sm font-medium">Ngày: <span id="selected-date-display">Chưa chọn</span></span>
                        <button id="book-appointment-btn" class="hidden px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                            Đặt lịch
                        </button>
                    </div>
                </div>
                <div id="scheduled-calls" class="mt-4">
                    <p class="text-gray-500 text-center py-4">Chọn một ngày để xem lịch tư vấn đã đặt</p>
                </div>
            </div>
        </div>
    </section>
</main>

@include('customer.layout.footer')

<!-- Modal đặt lịch -->
<div id="booking-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Đặt lịch tư vấn</h3>
            <button id="close-modal" class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="booking-form" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày đã chọn</label>
                <input type="text" id="selected-date" readonly class="w-full p-3 border border-gray-300 rounded-lg bg-gray-50">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rạp chiếu phim</label>
                <select id="cinema-select" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none">
                    <option value="">Chọn rạp chiếu phim</option>
                    <!-- Danh sách rạp sẽ được load động -->
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian bắt đầu</label>
                <select id="time-select" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none">
                    <option value="">Chọn thời gian</option>
                    <option value="09:00">09:00</option>
                    <option value="10:00">10:00</option>
                    <option value="11:00">11:00</option>
                    <option value="14:00">14:00</option>
                    <option value="15:00">15:00</option>
                    <option value="16:00">16:00</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nội dung tư vấn</label>
                <textarea id="consultation-content" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none" 
                    placeholder="Mô tả nội dung bạn muốn được tư vấn..."></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại liên hệ</label>
                <input type="tel" id="phone-number" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none" 
                    placeholder="Nhập số điện thoại của bạn">
            </div>
            
            <div class="pt-4">
                <button type="submit" class="w-full px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors">
                    Xác nhận đặt lịch
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal xác nhận thành công -->
<div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 max-w-md w-full mx-4">
        <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Đặt lịch thành công!</h3>
            <p class="text-gray-600 mb-4">Chúng tôi sẽ liên hệ với bạn để xác nhận cuộc hẹn trong thời gian sớm nhất.</p>
            <button id="close-confirmation" class="w-full px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors">
                Đóng
            </button>
        </div>
    </div>
</div>

<script src="{{ $_ENV['URL_WEB_BASE'] }}/js/dat-lich-goi-video.js"></script>

</body>
</html>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sơ đồ ghế</title>
  <link rel="icon" type="image/png" href="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756391269/logo_cinema_z2pcda.jpg">
  <link rel="stylesheet" href="{{$_ENV['URL_WEB_BASE']}}/css/tailwind.css">
</head>
<body class="min-h-screen flex flex-col bg-gray-50 text-gray-800 font-sans" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}">
@include('customer.layout.header')
<main class="flex-1">
    
<div id="thongTinPhim" class="flex flex-col md:flex-row max-w-6xl mx-auto px-4 mt-10">
    
</div>
<div id="seatCountdownWrapper" class="mb-4">
            <div id="seatCountdownTimer" class="text-center text-lg font-semibold text-red-600">Thời gian giữ ghế: 10:00</div>
        </div>
<div class="flex flex-col md:flex-row max-w-6xl mx-auto px-4 mt-10 mb-10">
    
    <!-- Bên trái: Sơ đồ ghế -->
    <div id="leftContainer" class="flex-1 transition-opacity duration-500">
        <!-- Countdown hiển thị khi khách bắt đầu giữ chỗ (10 phút) -->
        <div class="w-full text-white text-center py-3 rounded-lg mb-6 
                    shadow-2xl tracking-wider font-bold text-lg
                    bg-gray-900 border border-gray-800">
        MÀN HÌNH
        </div>

        <div id="seatMap" class="grid gap-4 mb-6"
            style="grid-template-columns: repeat(10, minmax(0, 1fr));">
        <!-- Ghế sẽ được JS render -->
        </div>

        <!-- Chú thích -->
        <div class="mt-6 space-y-3">
        <h2 class="text-lg font-semibold text-gray-700">Chú thích</h2>
        <div id="chuthich" class="flex flex-wrap gap-6">
            <div class="flex items-center gap-2">
            <div class="w-12 h-12 rounded-xl shadow-md flex items-center justify-center text-white font-bold bg-gray-400"></div> 
            <span>Đang chọn</span>
            </div>
            <div class="flex items-center gap-2">
            <div class="w-12 h-12 rounded-xl bg-white-400 flex items-center justify-center shadow-md">
                🎟️
            </div>
            <span>Đã đặt</span>
            </div>
        </div>
        </div>
    </div>

    <!-- Chọn đồ ăn -->
    <div id="foodContainer" class="flex-1 transition-opacity duration-500 bg-white rounded-lg shadow-lg p-6 hidden">
        <h2 class="text-lg font-bold mb-4">Chọn bắp & nước</h2>
    </div>

    <!-- QR thanh toán -->
    <div id="qrContainer" class="flex-1 transition-opacity duration-500 bg-white rounded-lg shadow-lg p-6 hidden">
        <h2 class="text-lg  text-center font-bold mb-4">Quét mã QR để thanh toán</h2>
        <img id="qrImage" src="" alt="QR Thanh toán" class="mx-auto">
        <p class="mt-4 text-center text-gray-600">Vui lòng quét QR để hoàn tất thanh toán</p>
        <p id="countdownTimer" class="mt-4 text-center text-red-600 font-bold text-lg"></p>
    </div>

    <div id="success_pay_box" class="flex-1 transition-opacity duration-500 bg-white rounded-lg shadow-lg p-6 hidden">
        <h2 class="text-success flex justify-center items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-check-circle text-success" viewBox="0 0 16 16">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
            <path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05"/>
            </svg>
            Thanh toán thành công
        </h2>
        <p class="text-center text-success">Chúng mừng bạn đã đặt vé thành công!</p>

        <div id="ticket_detail_box" class="mt-4"></div>
    </div>

    <!-- Bên phải: Thông tin phim + ghế đã chọn + tổng cộng -->
    <div id="movieInfo" class="w-full md:w-96 bg-white rounded-lg shadow-lg p-6 flex flex-col gap-4">
        <!-- Nội dung sẽ render bằng JS -->
    </div>
</div>
</main>
@include('customer.layout.footer')
<script type="module" src="{{$_ENV['URL_WEB_BASE']}}/js/so-do-ghe.js"></script>
</body>
</html>

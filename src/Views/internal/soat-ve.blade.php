@extends('internal.layout')

@section('head')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<style>
    #reader {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    #reader video {
        width: 100% !important;
        height: auto !important;
        border-radius: 12px;
    }
    
    .scan-overlay {
        position: relative;
    }
    
    .scan-overlay::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 250px;
        height: 250px;
        border: 2px solid #ef4444;
        border-radius: 12px;
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.3);
        pointer-events: none;
    }
    
    .ticket-card {
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .success-animation {
        animation: scaleIn 0.3s ease-out;
    }
    
    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Mobile optimization */
    @media (max-width: 640px) {
        .scan-overlay::after {
            width: 200px;
            height: 200px;
        }
    }

    /* Alert Toast Styles */
    .toast-alert {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        min-width: 300px;
        max-width: 90%;
        animation: slideDown 0.3s ease-out;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }

    .toast-alert.fade-out {
        animation: fadeOut 0.3s ease-out forwards;
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        to {
            opacity: 0;
            transform: translateX(-50%) translateY(-20px);
        }
    }

    .step-item {
        transition: all 0.3s ease;
    }

    .step-item.active {
        border-color: #f97316;
        background-color: #fff7ed;
    }

    .step-item.completed {
        border-color: #22c55e;
        background-color: #f0fdf4;
    }

    .step-number {
        transition: all 0.3s ease;
    }

    .step-item.active .step-number {
        background-color: #f97316;
        color: white;
    }

    .step-item.completed .step-number {
        background-color: #22c55e;
        color: white;
    }
</style>
@endsection

@section('title', 'Soát vé')

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-4 text-sm font-medium text-gray-500">Soát vé</span>
    </div>
</li>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Soát vé khách hàng</h1>
                <p class="mt-1 text-sm text-gray-600">Chọn phòng chiếu và suất chiếu để bắt đầu</p>
            </div>
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Steps Progress -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4">
        <div class="flex items-center justify-between">
            <div class="step-item flex items-center space-x-3 flex-1 border-2 rounded-lg p-3 active" id="step-1">
                <div class="step-number w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">1</div>
                <span class="text-sm font-medium text-gray-700">Chọn phòng</span>
            </div>
            <svg class="w-5 h-5 text-gray-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <div class="step-item flex items-center space-x-3 flex-1 border-2 rounded-lg p-3" id="step-2">
                <div class="step-number w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">2</div>
                <span class="text-sm font-medium text-gray-700">Chọn suất</span>
            </div>
            <svg class="w-5 h-5 text-gray-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <div class="step-item flex items-center space-x-3 flex-1 border-2 rounded-lg p-3" id="step-3">
                <div class="step-number w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">3</div>
                <span class="text-sm font-medium text-gray-700">Quét vé</span>
            </div>
        </div>
    </div>

    <!-- Step 1: Chọn Phòng Chiếu -->
    <div id="select-room-section" class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4" data-url="{{$_ENV['URL_WEB_BASE']}}">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Bước 1: Chọn phòng chiếu</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($phongChieu as $phong)
            <button 
                class="room-btn p-4 border-2 border-gray-300 rounded-lg hover:border-orange-500 hover:bg-orange-50 transition-all text-left"
                data-room-id="{{ $phong['id'] }}"
                data-room-name="{{ $phong['ten'] }}">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $phong['ten'] }}</p>
                        <p class="text-sm text-gray-500">{{ $phong['so_luong_ghe'] }} ghế</p>
                    </div>
                </div>
            </button>
            @endforeach
        </div>
    </div>

    <!-- Step 2: Chọn Suất Chiếu -->
    <div id="select-showtime-section" class="hidden bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Bước 2: Chọn suất chiếu</h2>
                <p class="text-sm text-gray-600 mt-1">Phòng: <span id="selected-room-name" class="font-medium text-orange-600"></span></p>
            </div>
            <button id="back-to-room-btn" class="text-sm text-gray-600 hover:text-gray-900 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Đổi phòng
            </button>
        </div>
        <div id="showtime-list" class="space-y-3">
            <!-- Danh sách suất chiếu sẽ được load bằng JavaScript -->
        </div>
    </div>

    <!-- Step 3: Scanner Section -->
    <div id="scanner-section" class="hidden bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Bước 3: Quét vé</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        <span id="scanning-room-name" class="font-medium text-orange-600"></span> - 
                        <span id="scanning-showtime-info" class="font-medium text-orange-600"></span>
                    </p>
                </div>
                <button id="back-to-showtime-btn" class="text-sm text-gray-600 hover:text-gray-900 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Đổi suất chiếu
                </button>
            </div>

            <!-- Camera Scanner -->
            <div id="scanner-container" class="hidden">
                <div class="bg-gradient-to-r from-gray-50 to-white border border-gray-200 rounded-xl p-4 mb-3 space-y-3 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M4 18V6a2 2 0 012-2h5.586a1 1 0 01.707.293l2.414 2.414A1 1 0 0115 7.414V18a2 2 0 01-2 2H6a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Chọn camera</p>
                                <p class="text-xs text-gray-500">Ưu tiên camera sau để quét vé giấy rõ nét.</p>
                            </div>
                        </div>
                        <span class="hidden sm:inline-flex px-2.5 py-1 text-xs font-medium bg-orange-100 text-orange-700 rounded-full border border-orange-200">Trạng thái thiết bị</span>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-end sm:space-x-3 space-y-2 sm:space-y-0">
                        <div class="flex-1">
                            <label for="camera-select" class="block text-sm font-medium text-gray-700 mb-1">Danh sách camera</label>
                            <div class="relative">
                                <select id="camera-select" class="appearance-none w-full border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 text-sm pr-10 bg-white">
                                    <option value="">Đang tải danh sách camera...</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <button id="refresh-camera-btn" class="px-3 py-2 text-sm bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-orange-200 transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19a9 9 0 0111.66-8.49m2.34 5.49A9 9 0 016.34 8.51" />
                            </svg>
                            Tải lại danh sách
                        </button>
                    </div>
                </div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-gray-900">Camera</h3>
                    <button id="stop-scan-btn" class="px-3 py-1.5 text-sm bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Dừng
                    </button>
                </div>
                <div id="reader" class="scan-overlay"></div>
                <p class="text-xs text-gray-500 text-center mt-3">
                    Đưa mã QR/barcode vào khung để quét
                </p>
            </div>

            <!-- Control Buttons -->
            <div id="control-buttons">
                <button id="start-scan-btn" class="w-full py-4 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all flex items-center justify-center space-x-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Bắt đầu quét vé</span>
                </button>

                <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-blue-800">
                                <strong>Hướng dẫn:</strong> Bật camera và đưa mã QR trên vé vào khung để quét tự động.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 text-center">
            <svg class="animate-spin h-12 w-12 text-orange-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-gray-700 font-medium">Đang xử lý...</p>
        </div>
    </div>
</div>

<script src="{{ $_ENV['URL_INTERNAL_BASE'] }}/js/soat-ve.js"></script>
@endsection
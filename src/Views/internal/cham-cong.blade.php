@extends('internal.layout')

@section('title', 'Chấm công')

@section('head')
<style>
    /* Optimize video rendering */
    #video, #canvas {
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }
    
    /* Smooth transitions */
    * {
        -webkit-tap-highlight-color: transparent;
    }
    
    /* Custom scrollbar for desktop */
    @media (min-width: 1024px) {
        .overflow-x-auto::-webkit-scrollbar {
            height: 8px;
        }
        .overflow-x-auto::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    }
</style>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-4 w-4 sm:h-5 sm:w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-2 sm:ml-4 text-xs sm:text-sm font-medium text-gray-500">Chấm công</span>
    </div>
</li>
@endsection

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 pb-8 sm:pb-12">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8">
        <!-- Header Section -->
        <div class="pt-4 sm:pt-6 pb-4 sm:pb-6">
            <div class="text-center lg:text-left">
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 tracking-tight">
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                        Chấm công bằng khuôn mặt
                    </span>
                </h1>
                <p class="mt-2 text-sm sm:text-base text-gray-600 max-w-2xl mx-auto lg:mx-0">
                    Sử dụng camera để nhận diện khuôn mặt và chấm công tự động
                </p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="space-y-4 sm:space-y-6">
            <!-- Registration Status Card -->
            <div id="registrationStatus" 
                 class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden hover:shadow-xl transition-shadow duration-300" 
                 data-url="{{$_ENV['URL_WEB_BASE']}}">
                <div class="p-4 sm:p-6 lg:p-8">
                    <div class="flex items-center gap-3 mb-4 sm:mb-6">
                        <div class="flex-shrink-0 p-2.5 sm:p-3 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl shadow-lg">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900">Trạng thái đăng ký</h2>
                    </div>
                    <div id="statusContent" class="flex items-center justify-center py-8 sm:py-10">
                        <svg class="animate-spin h-8 w-8 sm:h-10 sm:w-10 text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="ml-3 text-sm sm:text-base text-gray-700 font-medium">Đang kiểm tra...</span>
                    </div>
                </div>
            </div>

            <!-- Camera Section -->
            <div id="cameraSection" 
                 class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden hover:shadow-xl transition-shadow duration-300" 
                 data-ip="{{ $dinhVi->wifi_ip ?? '' }}" 
                 data-ten="{{ $dinhVi->wifi_ten ?? '' }}">
                <div class="p-4 sm:p-6 lg:p-8">
                    <!-- Camera Header -->
                    <div class="flex items-center justify-between mb-4 sm:mb-6">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 p-2.5 sm:p-3 bg-gradient-to-br from-green-600 to-green-700 rounded-xl shadow-lg">
                                <svg class="h-5 w-5 sm:h-6 sm:w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-bold text-gray-900">Camera nhận diện</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            <span class="text-xs sm:text-sm text-green-600 font-medium hidden sm:inline">Đang hoạt động</span>
                        </div>
                    </div>
                    
                    <!-- Video Container -->
                    <div class="relative mx-auto rounded-xl sm:rounded-2xl overflow-hidden bg-gradient-to-br from-gray-900 to-gray-800 shadow-2xl mb-4 sm:mb-6" 
                         style="width:100%; max-width:960px; aspect-ratio:16/9;">
                        <video id="video" autoplay muted playsinline class="object-contain w-full h-full"></video>
                        <canvas id="canvas" width="1280" height="720" class="absolute inset-0 w-full h-full pointer-events-none"></canvas>
                        
                        <!-- Camera Overlay Frame -->
                        <div class="absolute inset-0 pointer-events-none">
                            <div class="absolute inset-3 sm:inset-4 border-2 border-white/40 rounded-lg"></div>
                            <!-- Corner accents -->
                            <div class="absolute top-2 left-2 sm:top-3 sm:left-3 w-6 h-6 sm:w-8 sm:h-8 border-t-4 border-l-4 border-green-400 rounded-tl-lg"></div>
                            <div class="absolute top-2 right-2 sm:top-3 sm:right-3 w-6 h-6 sm:w-8 sm:h-8 border-t-4 border-r-4 border-green-400 rounded-tr-lg"></div>
                            <div class="absolute bottom-2 left-2 sm:bottom-3 sm:left-3 w-6 h-6 sm:w-8 sm:h-8 border-b-4 border-l-4 border-green-400 rounded-bl-lg"></div>
                            <div class="absolute bottom-2 right-2 sm:bottom-3 sm:right-3 w-6 h-6 sm:w-8 sm:h-8 border-b-4 border-r-4 border-green-400 rounded-br-lg"></div>
                        </div>
                        
                        <!-- Status Indicator -->
                        <div class="absolute top-3 left-1/2 transform -translate-x-1/2 bg-black/60 backdrop-blur-sm px-3 py-1.5 sm:px-4 sm:py-2 rounded-full">
                            <p class="text-xs sm:text-sm text-white font-medium flex items-center gap-2">
                                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                <span>Sẵn sàng nhận diện</span>
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <button id="btnCheckin" 
                                class="group relative bg-gradient-to-r from-green-600 to-green-700 text-white px-6 py-4 sm:py-5 rounded-xl hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-bold transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1 active:scale-95">
                            <span class="flex items-center justify-center gap-2 sm:gap-3">
                                <svg class="h-5 w-5 sm:h-6 sm:w-6 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-base sm:text-lg">Check In</span>
                            </span>
                            <div class="absolute inset-0 rounded-xl bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </button>
                        <button id="btnCheckout" 
                                class="group relative bg-gradient-to-r from-orange-600 to-orange-700 text-white px-6 py-4 sm:py-5 rounded-xl hover:from-orange-700 hover:to-orange-800 focus:outline-none focus:ring-4 focus:ring-orange-300 font-bold transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1 active:scale-95">
                            <span class="flex items-center justify-center gap-2 sm:gap-3">
                                <svg class="h-5 w-5 sm:h-6 sm:w-6 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                                <span class="text-base sm:text-lg">Check Out</span>
                            </span>
                            <div class="absolute inset-0 rounded-xl bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- History Table Section -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden hover:shadow-xl transition-shadow duration-300">
                <!-- Table Header -->
                <div class="p-4 sm:p-6 lg:p-8 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-blue-50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 p-2.5 sm:p-3 bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-xl shadow-lg">
                                <svg class="h-5 w-5 sm:h-6 sm:w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-bold text-gray-900">Lịch sử chấm công</h2>
                        </div>
                        <button id="btnRefresh" 
                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 sm:px-5 sm:py-3 bg-white text-indigo-700 rounded-xl hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-200 font-semibold transition-all duration-300 shadow-md hover:shadow-lg border border-indigo-200 active:scale-95">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span class="text-sm sm:text-base">Làm mới</span>
                        </button>
                    </div>
                </div>
                
                <!-- Table Container -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Ngày
                                </th>
                                <th scope="col" class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Ca
                                </th>
                                <th scope="col" class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Giờ vào
                                </th>
                                <th scope="col" class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Giờ ra
                                </th>
                                <th scope="col" class="hidden sm:table-cell px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Số giờ
                                </th>
                                <th scope="col" class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Trạng thái
                                </th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody" class="bg-white divide-y divide-gray-100">
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <svg class="animate-spin h-10 w-10 sm:h-12 sm:w-12 text-indigo-600 mx-auto" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="mt-3 text-sm sm:text-base text-gray-500 font-medium">Đang tải dữ liệu...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-md overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
    <div class="relative mx-auto max-w-sm w-full bg-white shadow-2xl rounded-3xl transform transition-all animate-fadeIn">
        <div class="p-8 sm:p-10 text-center">
            <div class="mx-auto flex items-center justify-center h-20 w-20 sm:h-24 sm:w-24 rounded-full bg-gradient-to-br from-blue-500 via-indigo-600 to-purple-600 shadow-xl">
                <svg class="animate-spin h-10 w-10 sm:h-12 sm:w-12 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mt-6 sm:mt-8">Đang nhận diện khuôn mặt</h3>
            <p class="text-sm sm:text-base text-gray-600 mt-2">Vui lòng đợi trong giây lát...</p>
            <div class="mt-6 sm:mt-8 flex justify-center gap-1.5">
                <div class="h-2.5 w-2.5 bg-blue-600 rounded-full animate-bounce"></div>
                <div class="h-2.5 w-2.5 bg-indigo-600 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                <div class="h-2.5 w-2.5 bg-purple-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            </div>
        </div>
    </div>
</div>

<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/cham-cong.js"></script>
@endsection
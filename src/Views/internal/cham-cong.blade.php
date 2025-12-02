@extends('internal.layout')

@section('title', 'Chấm công')

@section('head')
<style>
    @media screen and (min-width: 769px) {
        .mobile-only {
            display: none;
        }
    }
    @media screen and (max-width: 768px) {
        .desktop-only {
            display: none;
        }
    }
</style>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-4 text-sm font-medium text-gray-500">Chấm công</span>
    </div>
</li>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-6 sm:mb-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Chấm công bằng khuôn mặt</h2>
        <p class="mt-2 text-sm text-gray-600">Sử dụng camera để nhận diện khuôn mặt và chấm công</p>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
        <!-- Left Column: Camera & Registration Status -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Registration Status Card -->
            <div id="registrationStatus" class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl shadow-md border border-blue-100 p-6" data-url="{{$_ENV['URL_WEB_BASE']}}">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-blue-600 rounded-lg">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Trạng thái đăng ký</h3>
                </div>
                <div id="statusContent" class="flex items-center justify-center py-6">
                    <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-3 text-gray-700 font-medium">Đang kiểm tra...</span>
                </div>
            </div>

            <!-- Camera Section -->
            <div id="cameraSection" class="bg-white rounded-xl shadow-lg overflow-hidden" data-ip="{{ $dinhVi->wifi_ip ?? '' }}" data-ten="{{ $dinhVi->wifi_ten ?? '' }}">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-green-600 rounded-lg">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Camera nhận diện</h3>
                    </div>
                    
                    <!-- Video Container -->
                    <div class="relative mx-auto rounded-lg overflow-hidden bg-gray-900 shadow-inner" style="width:100%; max-width:960px; aspect-ratio:16/9;">
                        <video id="video" autoplay muted playsinline class="object-contain w-full h-full"></video>
                        <canvas id="canvas" width="1280" height="720" class="absolute inset-0 w-full h-full pointer-events-none"></canvas>
                        
                        <!-- Camera Overlay -->
                        <div class="absolute inset-0 pointer-events-none">
                            <div class="absolute inset-4 border-2 border-white/30 rounded-lg"></div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <button id="btnCheckin" class="group relative bg-gradient-to-r from-green-600 to-green-700 text-white px-6 py-4 rounded-xl hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-semibold transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Check In</span>
                            </span>
                        </button>
                        <button id="btnCheckout" class="group relative bg-gradient-to-r from-orange-600 to-orange-700 text-white px-6 py-4 rounded-xl hover:from-orange-700 hover:to-orange-800 focus:outline-none focus:ring-4 focus:ring-orange-300 font-semibold transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                                <span>Check Out</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Today's Attendance -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg p-6 sticky top-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-purple-600 rounded-lg">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Chấm công hôm nay</h3>
                </div>
                <div id="todayAttendance" class="space-y-4">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-4 text-gray-500 font-medium">Chưa có dữ liệu</p>
                        <p class="mt-1 text-sm text-gray-400">Thực hiện check in để bắt đầu</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="mt-8 bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-600 rounded-lg">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Lịch sử chấm công</h3>
                </div>
                <button id="btnRefresh" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors duration-200 font-medium">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span class="hidden sm:inline">Làm mới</span>
                </button>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ngày</th>
                        <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ca</th>
                        <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Giờ vào</th>
                        <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Giờ ra</th>
                        <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider hidden sm:table-cell">Số giờ</th>
                        <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Trạng thái</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody" class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center">
                            <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="mt-2 text-gray-500">Đang tải...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
    <div class="relative mx-auto max-w-sm w-full bg-white shadow-2xl rounded-2xl transform transition-all">
        <div class="p-8 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600">
                <svg class="animate-spin h-8 w-8 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mt-6">Đang nhận diện...</h3>
            <div class="mt-6 flex justify-center gap-1">
                <div class="h-2 w-2 bg-blue-600 rounded-full animate-bounce"></div>
                <div class="h-2 w-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                <div class="h-2 w-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            </div>
        </div>
    </div>
</div>

<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/cham-cong.js"></script>
@endsection
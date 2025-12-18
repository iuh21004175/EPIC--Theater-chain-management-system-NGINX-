@extends('internal.layout')

@section('title', 'Quản lý thông tin server chấm công')

@section('head')
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/server-cham-cong.js"></script>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-4 text-gray-500 font-medium">Server chấm công</span>
    </div>
</li>
@endsection

@section('content')
    <!-- Page header -->
    <div class="pb-5 border-b border-gray-200 sm:flex sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">Quản lý thông tin server chấm công</h3>
            <p class="mt-1 text-sm text-gray-500">Cấu hình thông tin WiFi và cổng server để xác định vị trí chấm công cho nhân viên</p>
        </div>
    </div>

    <!-- Form -->
    <div class="mt-8">
        <div class="max-w-2xl">
            <form id="dinh-vi-form" class="space-y-6" data-url="{{$_ENV['URL_WEB_BASE']}}">
                <!-- WiFi IP -->
                <div>
                    <label for="wifiIp" class="block text-sm font-medium text-gray-700 mb-2">
                        Địa chỉ IP WiFi <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="wifiIp" 
                            id="wifiIp" 
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm" 
                            placeholder="Ví dụ: 192.168.1.1"
                            value="{{ $serverChamCong  ? $serverChamCong->wifi_ip ?? '' : '' }}"
                            required
                        >
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Nhập địa chỉ IP của mạng WiFi tại rạp phim</p>
                    <p class="text-red-500 text-xs italic hidden" id="wifiIp-error">Vui lòng nhập địa chỉ IP WiFi.</p>
                </div>

                <!-- WiFi Name -->
                <div>
                    <label for="wifiTen" class="block text-sm font-medium text-gray-700 mb-2">
                        Tên mạng WiFi <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="wifiTen" 
                            id="wifiTen" 
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm" 
                            placeholder="Ví dụ: EPIC_Cinema_WiFi"
                            value="{{ $serverChamCong ? $serverChamCong->wifi_ten ?? '' : '' }}"
                            required
                        >
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Nhập tên mạng WiFi (SSID) để nhân viên có thể kết nối và chấm công</p>
                    <p class="text-red-500 text-xs italic hidden" id="wifiTen-error">Vui lòng nhập tên mạng WiFi.</p>
                </div>

                <!-- Server Port -->
                <div>
                    <label for="serverPort" class="block text-sm font-medium text-gray-700 mb-2">
                        Cổng Server <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                            </svg>
                        </div>
                        <input 
                            type="number" 
                            name="serverPort" 
                            id="serverPort" 
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm" 
                            placeholder="Ví dụ: 8080"
                            value="{{ $serverChamCong ? $serverChamCong->server_port ?? '' : '' }}"
                            min="1"
                            max="65535"
                            required
                        >
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Nhập cổng của server chấm công (1-65535)</p>
                    <p class="text-red-500 text-xs italic hidden" id="serverPort-error">Vui lòng nhập cổng server hợp lệ.</p>
                </div>

                <!-- Info Box -->
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                           <p class="text-sm text-blue-700">
                                <strong>Lưu ý:</strong> Thông tin server chấm công này sẽ được client sử dụng để kết nối đến hệ thống khi nhân viên thực hiện chấm công. 
                                Vui lòng đảm bảo địa chỉ IP, tên WiFi và cổng server chính xác để quá trình chấm công diễn ra ổn định.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button 
                        type="button" 
                        id="btn-reset"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        Đặt lại
                    </button>
                    <button 
                        type="submit" 
                        id="btn-submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        <svg class="hidden animate-spin -ml-1 mr-2 h-4 w-4 text-white" id="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="btn-text">Cập nhật thông tin</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
@extends('internal.layout')

@section('title', 'Quản lý phân công nhân viên')

@section('head')
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/plugin/isoWeek.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/plugin/isSameOrBefore.js"></script>
    <script>
        dayjs.extend(window.dayjs_plugin_isoWeek);
        dayjs.extend(window.dayjs_plugin_isSameOrBefore);
    </script>
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/phan-cong.js"></script>
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/vi-tri-lam-viec.js"></script>
    <style>
    .phancong-tooltip {
        min-width: 220px;
        max-width: 350px;
        pointer-events: none;
        transition: all 0.2s ease;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        z-index: 9999;
    }
    
    .phancong-nv {
        transition: all 0.2s ease;
    }
    
    .phancong-nv:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .phancong-dropzone {
        transition: all 0.2s ease;
    }
    
    .phancong-cell:hover .phancong-dropzone {
        background-color: rgba(59, 130, 246, 0.05);
    }
    
    /* Animation cho drag over */
    @keyframes pulse-ring {
        0% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
        }
    }
    
    .phancong-dropzone.ring {
        animation: pulse-ring 1.5s infinite;
    }
    
    /* Scrollbar styling */
    #nv-list::-webkit-scrollbar {
        width: 8px;
    }
    
    #nv-list::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    
    #nv-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    
    #nv-list::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    </style>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-4 text-gray-500 font-medium">Quản lý phân công nhân viên</span>
    </div>
</li>
<li>
    <div class="flex items-center ml-4 space-x-2">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <div class="flex rounded-md shadow-sm">
            <button id="tab-btn-phancong" class="tab-btn px-4 py-2 text-sm font-medium rounded-l-md bg-red-600 text-white" aria-current="page">
                Phân công
            </button>
            <button id="tab-btn-vitri" class="tab-btn px-4 py-2 text-sm font-medium rounded-r-md border border-gray-200 text-gray-700">
                Vị trí công việc
            </button>
        </div>
    </div>
</li>
@endsection

@section('content')
    <div id="tab-phancong" class="tab-content">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Cột 1: Nhân viên -->
            <div class="lg:w-1/4 w-full bg-white rounded-xl shadow-lg p-5 flex flex-col min-h-[500px] border border-gray-200">
                <h3 class="text-xl font-bold mb-3 text-gray-800 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Danh sách nhân viên
                    </span>
                    <span id="nv-count" class="text-sm font-medium bg-blue-100 text-blue-700 px-3 py-1 rounded-full">0</span>
                </h3>
                <!-- Filter nhân viên -->
                <div class="mb-3">
                    <div class="relative">
                        <input type="text" id="nv-filter" placeholder="Tìm theo tên..." 
                               class="w-full pl-10 pr-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
                <div id="nv-list" class="flex-1 overflow-y-auto space-y-3 pr-2 min-h-[350px]" data-url="{{$_ENV['URL_WEB_BASE']}}">
                    <!-- JS render thẻ nhân viên -->
                </div>
                <!-- Thanh phân trang nhân viên -->
                <div id="nv-pagination-bar" class="flex justify-center mt-4 gap-2"></div>
            </div>
            <!-- Cột 2: Lịch phân công -->
            <div class="lg:w-3/4 w-full bg-white rounded-xl shadow-lg p-6 flex flex-col border border-gray-200">
                <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <button id="btn-prev-week" class="px-4 py-2 rounded-lg bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Tuần trước
                        </button>
                        <h3 id="week-title" class="text-xl font-bold text-gray-800 px-4 py-2 bg-blue-50 rounded-lg border-2 border-blue-200"></h3>
                        <button id="btn-next-week" class="px-4 py-2 rounded-lg bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            Tuần sau
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex gap-3">
                        <button id="btn-copy-week" 
                                class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-5 py-2.5 rounded-lg font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:from-gray-400 disabled:to-gray-500"
                                title="Sao chép phân công từ tuần trước sang tuần này">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span>Sao chép tuần trước</span>
                        </button>
                        <button id="btn-clear-week" 
                                class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-5 py-2.5 rounded-lg font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2"
                                title="Xóa toàn bộ phân công trong tuần này">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Xóa tuần</span>
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto rounded-lg border-2 border-gray-300 shadow-md">
                    <table id="phancong-main-table" class="min-w-full text-center bg-white">
                        <thead>
                            <tr id="phancong-header-row">
                                <!-- JS sẽ render các ngày thứ 2 -> CN -->
                            </tr>
                        </thead>
                        <tbody id="phancong-main-tbody" data-url="{{$_ENV['URL_WEB_BASE']}}">
                            <!-- JS sẽ render các hàng ca sáng, chiều, tối -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div id="tab-vitri" class="tab-content hidden">
        <div class="bg-white shadow-lg rounded-xl p-8 border border-gray-200">
            <h3 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Quản lý vị trí công việc
            </h3>
            <form id="vitri-form" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-700">Tên vị trí công việc</label>
                    <input type="text" id="input-vitri" class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all" placeholder="Nhập tên vị trí công việc">
                </div>
                <div>
                    <button type="submit" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Thêm vị trí
                    </button>
                </div>
            </form>
            <div class="mt-10">
                <h4 class="font-bold text-lg mb-4 text-gray-700">Danh sách vị trí công việc</h4>
                <div id="vitri-list" class="overflow-x-auto" data-url="{{$_ENV['URL_WEB_BASE']}}">
                    <!-- JS sẽ render bảng vị trí công việc tại đây -->
                </div>
            </div>
        </div>
    </div>    
        
@endsection

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Đổi màu tab
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('bg-red-600', 'text-white'));
            this.classList.add('bg-red-600', 'text-white');
            // Ẩn tất cả tab-content
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
            // Hiện tab được chọn
            const tabId = this.id.replace('tab-btn-', 'tab-');
            document.getElementById(tabId).classList.remove('hidden');
        });
    });
});
</script>
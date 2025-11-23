@extends('internal.layout')

@section('title', 'Quản lý quy tắc giá vé')

@section('head')
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/gia-ve.js"></script>
    <style>
        .modal {
            transition: opacity 0.25s ease;
        }
        .modal-active {
            overflow-x: hidden;
            overflow-y: visible !important;
        }
        .modal-container {
            max-height: 80vh !important;
        }
        .modal-body {
            overflow-y: scroll;
            max-height: 60vh;
            padding-right: 0.5rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
        }
        .modal-body::-webkit-scrollbar {
            width: 8px;
            display: block;
        }
        .modal-body::-webkit-scrollbar-track {
            background: transparent;
        }
        .modal-body::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.5);
            border-radius: 20px;
            border: transparent;
        }
        .modal-body::-webkit-scrollbar-thumb:hover {
            background-color: rgba(156, 163, 175, 0.8);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .invalid-feedback {
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        /* Cải tiến style cho input */
        .price-input-container {
            position: relative;
            width: 100%;
            max-width: 400px;
        }
        
        .price-input {
            width: 100%;
            padding: 0.75rem 4rem 0.75rem 3rem;
            font-size: 1.125rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            background-color: white;
        }
        
        .price-input:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
            outline: none;
        }
        
        .price-input-icon-left {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.125rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .price-input-icon-right {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .input-group-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .input-group {
            margin-bottom: 1.5rem;
        }
        
        .input-group:hover .price-input {
            border-color: #d1d5db;
        }
    </style>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-4 text-gray-500 font-medium">Quản lý quy tắc giá vé</span>
    </div>
</li>
@endsection

@section('content')
<div class="bg-white shadow rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Danh sách quy tắc giá vé</h2>
        <button id="add-rule-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Thêm quy tắc mới
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên quy tắc</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giá trị</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Độ ưu tiên</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điều kiện áp dụng</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody id="rules-list" class="bg-white divide-y divide-gray-200" data-url="{{$_ENV['URL_WEB_BASE']}}">
                <!-- Quy tắc sẽ được render ở đây bằng JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal thêm/sửa quy tắc -->
<div id="rule-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6 relative">
        <button id="close-modal-btn" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <h3 class="text-lg font-semibold mb-4" id="modal-title">Thêm mới quy tắc giá vé</h3>
        <form id="rule-form" class="space-y-6">
            <div>
                <label for="rule-name" class="block text-sm font-medium text-gray-700">Tên quy tắc</label>
                <input type="text" id="rule-name" name="rule_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                <div class="invalid-feedback hidden" id="rule_name_error">Vui lòng nhập tên quy tắc</div>
            </div>
            <div>
                <label for="rule-action" class="block text-sm font-medium text-gray-700">Hành động</label>
                <select id="rule-action" name="rule_action" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">-- Chọn hành động --</option>
                    <option value="Thiết lập giá">Thiết lập giá</option>
                    <option value="Cộng thêm tiền">Cộng thêm tiền</option>
                </select>
                <div class="invalid-feedback hidden" id="rule_action_error">Vui lòng chọn hành động</div>
            </div>
            <div>
                <label for="rule-value" class="block text-sm font-medium text-gray-700">Giá trị</label>
                <input type="number" id="rule-value" name="rule_value" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                <div class="invalid-feedback hidden" id="rule_value_error">Vui lòng nhập giá trị hợp lệ</div>
            </div>
            <div>
                <label for="rule-priority" class="block text-sm font-medium text-gray-700">Độ ưu tiên</label>
                <input type="number" id="rule-priority" name="rule_priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required min="1" value="1">
                <div class="invalid-feedback hidden" id="rule_priority_error">Vui lòng nhập độ ưu tiên hợp lệ</div>
            </div>
            <div>
                <label for="rule-status" class="block text-sm font-medium text-gray-700">Trạng thái</label>
                <select id="rule-status" name="rule_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="1">Kích hoạt</option>
                    <option value="0">Vô hiệu hóa</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Điều kiện áp dụng</label>
                <div id="conditions-list" class="space-y-4">
                    <!-- Điều kiện sẽ được render ở đây bằng JS -->
                </div>
                <button type="button" id="add-condition-btn" class="mt-2 inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Thêm điều kiện
                </button>
            </div>
            <div class="flex justify-end pt-4">
                <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    Lưu quy tắc
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast notification for success -->
<div id="success-toast" class="fixed bottom-0 right-0 mb-4 mr-4 bg-green-50 border-l-4 border-green-400 p-4 opacity-0 transition-opacity duration-500 ease-in-out transform translate-y-full" style="max-width: 24rem; z-index: 50;">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </div>
        <div class="ml-3">
            <p id="toast-message" class="text-sm text-green-700">Thành công!</p>
        </div>
        <div class="ml-auto pl-3">
            <div class="-mx-1.5 -my-1.5">
                <button id="close-toast" class="inline-flex bg-green-50 rounded-md p-1.5 text-green-500 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <span class="sr-only">Đóng</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
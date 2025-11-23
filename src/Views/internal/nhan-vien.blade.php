@extends('internal.layout')

@section('title', 'Quản lý nhân viên')

@section('head')
<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/nhan-vien.js" defer></script>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-4 text-sm font-medium text-gray-500">Quản lý nhân viên</span>
    </div>
</li>
@endsection

@section('content')
<div class="bg-white shadow rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Quản lý nhân viên</h1>
        <button id="add-employee-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Thêm nhân viên mới
        </button>
    </div>

    <!-- Filters -->
    <div class="mb-6">
        <div class="flex flex-wrap gap-4">
            <div>
                <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select id="status-filter" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active">Đang làm việc</option>
                    <option value="inactive">Đã nghỉ việc</option>
                </select>
            </div>
            <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                <input id="search" type="text" placeholder="Tên, email, SĐT..." class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
            </div>
        </div>
    </div>

    <!-- Employee List -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nhân viên
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Liên hệ
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Tên đăng nhập
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Trạng thái
                    </th>
                </tr>
            </thead>
            <tbody id="employee-list" class="bg-white divide-y divide-gray-200" data-url="{{$_ENV['URL_WEB_BASE']}}">
                <!-- Employee list will be populated here -->
                <tr id="no-employees" class="text-center">
                    <td colspan="6" class="px-6 py-8 text-gray-500">
                        Chưa có nhân viên nào
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-5 flex items-center justify-between">
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700" id="pagination-info">
                    Hiển thị <span class="font-medium">1</span> đến <span class="font-medium">10</span> trong số <span class="font-medium">20</span> nhân viên
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <button class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Previous</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Employee -->
<div id="employee-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modal-title" class="text-xl font-semibold text-gray-900">Thêm nhân viên mới</h3>
            <button id="close-modal" class="text-gray-400 hover:text-gray-500">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <form id="employee-form">
            <input type="hidden" id="employee-id" name="employee-id">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="employee-name">
                    Họ tên <span class="text-red-500">*</span>
                </label>
                <input type="text" id="employee-name" name="employee-name" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                       placeholder="Nguyễn Văn A">
                <p id="name-error" class="text-red-500 text-xs italic mt-1 hidden">Vui lòng nhập họ tên nhân viên</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="employee-phone">
                    Số điện thoại <span class="text-red-500">*</span>
                </label>
                <input type="text" id="employee-phone" name="employee-phone" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                       placeholder="0123456789">
                <p id="phone-error" class="text-red-500 text-xs italic mt-1 hidden">Vui lòng nhập số điện thoại hợp lệ</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="employee-email">
                    Email <span class="text-red-500">*</span>
                </label>
                <input type="email" id="employee-email" name="employee-email" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                       placeholder="example@email.com">
                <p id="email-error" class="text-red-500 text-xs italic mt-1 hidden">Vui lòng nhập email hợp lệ</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="employee-username">
                    Tên đăng nhập <span class="text-red-500">*</span>
                </label>
                <input type="text" id="employee-username" name="employee-username" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                       placeholder="username">
                <p id="username-error" class="text-red-500 text-xs italic mt-1 hidden">Vui lòng nhập tên đăng nhập</p>
            </div>
            
            <div id="password-container" class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="employee-password">
                    Mật khẩu <span class="text-red-500">*</span>
                </label>
                <input type="password" id="employee-password" name="employee-password" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                       placeholder="********">
                <p id="password-error" class="text-red-500 text-xs italic mt-1 hidden">Mật khẩu phải có ít nhất 8 ký tự</p>
            </div>
            
            <div class="flex justify-between mt-6">
                <div>
                    <button type="button" id="status-toggle" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline hidden">
                        Nghỉ việc
                    </button>
                </div>
                <div class="flex space-x-4">
                    <button type="button" id="cancel-employee" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Hủy
                    </button>
                    <button type="button" id="save-employee" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Thêm
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Status Change Confirmation Modal -->
<div id="status-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="mb-4">
            <h3 id="status-modal-title" class="text-xl font-semibold text-gray-900">Xác nhận thay đổi trạng thái</h3>
        </div>
        
        <div class="mb-6">
            <p id="status-message" class="text-gray-700">Bạn có chắc chắn muốn thay đổi trạng thái của nhân viên này?</p>
        </div>
        
        <div class="flex justify-end space-x-4">
            <button type="button" id="cancel-status-change" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Hủy
            </button>
            <button type="button" id="confirm-status-change" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Xác nhận
            </button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast-notification" class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg transform transition-transform duration-300 translate-y-20 opacity-0">
    Thao tác thành công
</div>
@endsection
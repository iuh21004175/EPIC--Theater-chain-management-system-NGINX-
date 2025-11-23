@extends('internal.layout')

@section('title', 'Quản lý tài khoản')

@section('head')
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/tai-khoan.js"></script>
    <style>
        .status-badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }
        .status-badge.active {
            @apply bg-green-100 text-green-800;
        }
        .status-badge.inactive {
            @apply bg-red-100 text-red-800;
        }
        .status-badge.unassigned {
            @apply bg-gray-100 text-gray-800;
        }
        .status-badge.assigned {
            @apply bg-blue-100 text-blue-800;
        }
    </style>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-1 text-gray-500 hover:text-gray-700 text-sm font-medium">Quản lý tài khoản</span>
    </div>
</li>
@endsection

@section('content')
    <!-- Page header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Quản lý tài khoản</h1>
        <button type="button" id="btn-add-account" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Tạo tài khoản mới
        </button>
    </div>

    <!-- Danh sách tài khoản -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md" style="min-height: 300px;">
        <ul class="divide-y divide-gray-200" id="accounts-list" data-url="{{$_ENV['URL_WEB_BASE']}}">
            <!-- JS sẽ render danh sách tài khoản tại đây -->
        </ul>
    </div>

    <!-- Modal tạo tài khoản mới -->
    <div id="modal-add-account" class="fixed inset-0 overflow-y-auto hidden z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full relative z-10 max-h-[90vh] flex flex-col">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 overflow-y-auto flex-1">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Tạo tài khoản quản lý rạp mới
                            </h3>
                            <div class="mt-4">
                                <form action="{{$_ENV['URL_WEB_BASE']}}/api/tai-khoan" id="form-add-account">
                                    <div class="mb-4">
                                        <label for="account-fullname" class="block text-sm font-medium text-gray-700">Họ và tên</label>
                                        <input type="text" name="fullname" id="account-fullname" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                        <p class="mt-1 text-sm text-red-600 min-h-[1.25rem] invisible" id="fullname-error"></p>
                                    </div>
                                    <div class="mb-4">
                                        <label for="account-username" class="block text-sm font-medium text-gray-700">Tên đăng nhập</label>
                                        <input type="text" name="username" id="account-username" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                        <p class="mt-1 text-sm text-gray-500">Tên đăng nhập không được chứa dấu cách và ký tự đặc biệt</p>
                                        <p class="mt-1 text-sm text-red-600 min-h-[1.25rem] invisible" id="username-error"></p>
                                    </div>
                                    <div class="mb-4">
                                        <label for="account-email" class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" name="email" id="account-email" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                        <p class="mt-1 text-sm text-red-600 min-h-[1.25rem] invisible" id="email-error"></p>
                                    </div>
                                    <div class="mb-4">
                                        <label for="account-password" class="block text-sm font-medium text-gray-700">Mật khẩu</label>
                                        <input type="password" name="password" id="account-password" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                        <p class="mt-1 text-sm text-gray-500">Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, và số</p>
                                        <p class="mt-1 text-sm text-red-600 min-h-[1.25rem] invisible" id="password-error"></p>
                                    </div>
                                    <div class="mb-4">
                                        <label for="account-password-confirm" class="block text-sm font-medium text-gray-700">Xác nhận mật khẩu</label>
                                        <input type="password" name="password_confirm" id="account-password-confirm" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                        <p class="mt-1 text-sm text-red-600 min-h-[1.25rem] invisible" id="password-confirm-error"></p>
                                    </div>
                                    <div class="mb-4">
                                        <label for="account-phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                                        <input type="tel" name="phone" id="account-phone" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        <p class="mt-1 text-sm text-red-600 min-h-[1.25rem] invisible" id="phone-error"></p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 flex-shrink-0">
                    <button type="button" id="btn-submit-add" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Tạo tài khoản
                    </button>
                    <button type="button" class="btn-cancel mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Hủy
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal chỉnh sửa tài khoản -->
    <div id="modal-edit-account" class="fixed inset-0 overflow-y-auto hidden z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full relative z-10 max-h-[90vh] flex flex-col">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 overflow-y-auto flex-1">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Chỉnh sửa thông tin tài khoản
                            </h3>
                            <div class="mt-4">
                                <form id="form-edit-account">
                                    <input type="hidden" id="edit-account-id">
                                    <div class="mb-4">
                                        <label for="edit-account-fullname" class="block text-sm font-medium text-gray-700">Họ và tên</label>
                                        <input type="text" name="fullname" id="edit-account-fullname" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                        <p class="mt-1 text-sm text-red-600 min-h-[1.25rem] invisible" id="edit-fullname-error"></p>
                                    </div>
                                    <div class="mb-4">
                                        <label for="edit-account-username" class="block text-sm font-medium text-gray-700">Tên đăng nhập</label>
                                        <input type="text" name="username" id="edit-account-username" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        <p class="mt-1 text-sm text-gray-500">Tên đăng nhập không được chứa dấu cách và ký tự đặc biệt</p>
                                        <p class="mt-1 text-sm text-red-600 min-h-[1.25rem] invisible" id="edit-username-error"></p>
                                    </div>
                                    <div class="mb-4">
                                        <label for="edit-account-email" class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" name="email" id="edit-account-email" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-100" readonly>
                                    </div>
                                    <div class="mb-4">
                                        <label for="edit-account-phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                                        <input type="tel" name="phone" id="edit-account-phone" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        <p class="mt-1 text-sm text-red-600 min-h-[1.25rem] invisible" id="edit-phone-error"></p>
                                    </div>
                                    <div class="mb-4">
                                        <div class="flex items-center">
                                            <input id="edit-account-reset-password" name="reset_password" type="checkbox" class="h-4 w-4 focus:ring-red-500 border-gray-300 rounded">
                                            <label for="edit-account-reset-password" class="ml-2 block text-sm text-gray-900">
                                                Đặt lại mật khẩu
                                            </label>
                                        </div>
                                    </div>
                                    <div id="reset-password-fields" class="hidden">
                                        <div class="mb-4">
                                            <label for="edit-account-password" class="block text-sm font-medium text-gray-700">Mật khẩu mới</label>
                                            <input type="password" name="password" id="edit-account-password" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                            <p class="mt-1 text-sm text-gray-500">Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, và số</p>
                                            <p class="mt-1 text-sm text-red-600 min-h-[1.25rem] invisible" id="edit-password-error"></p>
                                        </div>
                                        <div class="mb-4">
                                            <label for="edit-account-password-confirm" class="block text-sm font-medium text-gray-700">Xác nhận mật khẩu mới</label>
                                            <input type="password" name="password_confirm" id="edit-account-password-confirm" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                            <p class="mt-1 text-sm text-red-600 min-h-[1.25rem] invisible" id="edit-password-confirm-error"></p>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <div class="flex items-center">
                                            <input id="edit-account-active" name="active" type="checkbox" class="h-4 w-4 focus:ring-red-500 border-gray-300 rounded">
                                            <label for="edit-account-active" class="ml-2 block text-sm text-gray-900">
                                                Tài khoản đang hoạt động
                                            </label>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500">Bỏ chọn để khóa tài khoản này</p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 flex-shrink-0">
                    <button type="button" id="btn-submit-edit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Lưu thay đổi
                    </button>
                    <button type="button" class="btn-cancel mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Hủy
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal gán rạp phim -->
    <div id="modal-assign-cinema" class="fixed inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Gán quản lý cho rạp phim
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500" id="assign-account-name"></p>
                            </div>
                            <div class="mt-4">
                                <form id="form-assign-cinema">
                                    <input type="hidden" id="assign-account-id">
                                    <div class="mb-4">
                                        <label for="assign-cinema-id" class="block text-sm font-medium text-gray-700">Chọn rạp phim</label>
                                        <select id="assign-cinema-id" name="cinema_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm rounded-md">
                                            <option value="">-- Chọn rạp phim --</option>
                                            <!-- Các tùy chọn sẽ được thêm bằng JavaScript -->
                                        </select>
                                        <p class="mt-1 text-sm text-red-600 hidden" id="assign-cinema-error"></p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="btn-submit-assign" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Xác nhận
                    </button>
                    <button type="button" id="btn-unassign" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm hidden">
                        Hủy phân công
                    </button>
                    <button type="button" class="btn-cancel mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast thông báo -->
    <div id="toast-notification" class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg transform transition-all duration-300 translate-y-20 opacity-0">
        <!-- Nội dung thông báo sẽ được thêm bằng JavaScript -->
    </div>
@endsection
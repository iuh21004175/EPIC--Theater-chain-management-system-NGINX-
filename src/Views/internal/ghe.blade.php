@extends('internal.layout')

@section('title', 'Quản lý loại ghế')

@section('head')
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/ghe.js"></script>
    <style>
        .seat-type-icon {
            display: inline-block;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            margin-right: 8px;
            vertical-align: middle;
        }
        .seat-price {
            font-weight: 600;
            color: #EF4444;
        }
    </style>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-1 text-gray-500 hover:text-gray-700 text-sm font-medium">Quản lý loại ghế</span>
    </div>
</li>
@endsection

@section('content')
    <!-- Page header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Quản lý loại ghế</h1>
        <button type="button" id="btn-add-seat-type" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Thêm loại ghế mới
        </button>
    </div>

    <!-- Danh sách loại ghế -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200" id="seat-types-list" data-url="{{$_ENV['URL_WEB_BASE']}}">
            <!-- Dữ liệu sẽ được load bằng JavaScript -->
            <li class="px-6 py-4 flex items-center">
                <div class="w-full text-center text-gray-500">Đang tải dữ liệu...</div>
            </li>
        </ul>
    </div>

    <!-- Modal thêm loại ghế mới -->
    <div id="modal-add-seat-type" class="fixed inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Thêm loại ghế mới
                            </h3>
                            <div class="mt-4">
                                <form id="form-add-seat-type">
                                    <div class="mb-4">
                                        <label for="seat-type-name" class="block text-sm font-medium text-gray-700">Tên loại ghế</label>
                                        <input type="text" name="name" id="seat-type-name" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                        <p class="mt-1 text-sm text-red-600 hidden" id="name-error"></p>
                                    </div>
                                    <div class="mb-4">
                                        <label for="seat-type-description" class="block text-sm font-medium text-gray-700">Mô tả</label>
                                        <textarea name="description" id="seat-type-description" rows="3" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                                        <p class="mt-1 text-sm text-red-600 hidden" id="description-error"></p>
                                    </div>
                                    <div class="mb-4">
                                        <label for="seat-type-color" class="block text-sm font-medium text-gray-700">Màu hiển thị</label>
                                        <input type="color" name="color" id="seat-type-color" value="#EF4444" class="mt-1 block shadow-sm sm:text-sm border-gray-300 rounded-md h-10">
                                        <p class="mt-1 text-sm text-gray-500">Màu sắc hiển thị cho loại ghế này trên sơ đồ</p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="btn-submit-add" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Thêm
                    </button>
                    <button type="button" class="btn-cancel mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Hủy
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal cập nhật loại ghế -->
    <div id="modal-edit-seat-type" class="fixed inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Cập nhật thông tin loại ghế
                            </h3>
                            <div class="mt-4">
                                <form id="form-edit-seat-type">
                                    <input type="hidden" id="edit-seat-type-id">
                                    <div class="mb-4">
                                        <label for="edit-seat-type-name" class="block text-sm font-medium text-gray-700">Tên loại ghế</label>
                                        <input type="text" name="name" id="edit-seat-type-name" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                        <p class="mt-1 text-sm text-red-600 hidden" id="edit-name-error"></p>
                                    </div>
                                    <div class="mb-4">
                                        <label for="edit-seat-type-description" class="block text-sm font-medium text-gray-700">Mô tả</label>
                                        <textarea name="description" id="edit-seat-type-description" rows="3" class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                                        <p class="mt-1 text-sm text-red-600 hidden" id="edit-description-error"></p>
                                    </div>
                                    <div class="mb-4">
                                        <label for="edit-seat-type-color" class="block text-sm font-medium text-gray-700">Màu hiển thị</label>
                                        <input type="color" name="color" id="edit-seat-type-color" class="mt-1 block shadow-sm sm:text-sm border-gray-300 rounded-md h-10">
                                        <p class="mt-1 text-sm text-gray-500">Màu sắc hiển thị cho loại ghế này trên sơ đồ</p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="btn-submit-edit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Lưu
                    </button>
                    <button type="button" class="btn-cancel mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Hủy
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
@extends('internal.layout')

@section('title', 'Quản lý Banner')

@section('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{$_ENV['URL_INTERNAL_BASE']}}/css/sortable.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/banner.js" defer></script>
<style>
    /* Styles for sortable items */
    .sortable-chosen {
        background-color: #f7fafc;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        z-index: 10;
    }

    .sortable-ghost {
        opacity: 0.5;
        background-color: #ebf4ff;
    }

    .sortable-drag {
        opacity: 0.8;
        transform: rotate(3deg);
    }

    .handle {
        cursor: grab;
    }

    .handle:active {
        cursor: grabbing;
    }
</style>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-4 text-sm font-medium text-gray-500">Quản lý Banner</span>
    </div>
</li>
@endsection

@section('content')
<div class="bg-white shadow rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Quản lý Banner</h1>
        <button id="add-banner-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Thêm banner mới
        </button>
    </div>

    <!-- Banner Preview Section (Slideshow) -->
    <div class="mb-8">
        <h2 class="text-lg font-medium text-gray-800 mb-4">Xem trước Slideshow</h2>
        <div class="border border-gray-300 rounded-lg p-4 bg-gray-100">
            <div class="relative bg-gray-200 rounded overflow-hidden mx-auto" style="width:1200px; height:600px; max-width:100%;">
                <!-- Preview slideshow will be shown here -->
                <div id="slideshow-preview" class="w-full h-full flex items-center justify-center text-gray-500">
                    <span id="no-banners-message" class="text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p>Chưa có banner nào được thêm vào slideshow</p>
                    </span>
                    <div id="slideshow-images" class="hidden w-full h-full">
                        <!-- Slideshow images will be added here dynamically -->
                    </div>
                </div>
                
                <!-- Navigation arrows -->
                <button id="prev-slide" class="absolute top-1/2 left-2 -translate-y-1/2 bg-black bg-opacity-50 text-white rounded-full p-2 hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
                <button id="next-slide" class="absolute top-1/2 right-2 -translate-y-1/2 bg-black bg-opacity-50 text-white rounded-full p-2 hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Banner Order Management -->
    <div class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-medium text-gray-800">Sắp xếp thứ tự hiển thị</h2>
            
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-300">
            <p class="text-gray-600 mb-4">Kéo và thả các banner để thay đổi thứ tự hiển thị trong slideshow.</p>
            
            <div id="sortable-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Will be populated with banners -->
                <div id="no-banners-sortable" class="col-span-full text-center py-6 text-gray-500">
                    <p>Chưa có banner nào được thêm vào hệ thống</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Banner List -->
    <div>
        <h2 class="text-lg font-medium text-gray-800 mb-4">Danh sách Banner</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Hình ảnh
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Trạng thái
                        </th>
                        <!-- XÓA cột thao tác -->
                    </tr>
                </thead>
                <tbody id="banner-list" class="bg-white divide-y divide-gray-200" data-url="{{$_ENV['URL_WEB_BASE']}}" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}">
                    <!-- Banner items will be added here dynamically -->
                    <tr id="no-banners-row" class="text-center">
                        <td colspan="6" class="px-6 py-8 text-gray-500">
                            Chưa có banner nào được thêm vào hệ thống
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Banner -->
<div id="banner-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modal-title" class="text-xl font-semibold text-gray-900">Thêm banner mới</h3>
            <button id="close-modal" class="text-gray-400 hover:text-gray-500">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <form id="banner-form">
            <input type="hidden" id="banner-id" name="banner-id">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Hình ảnh banner
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center" id="upload-area">
                    <div id="upload-placeholder" class="py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="mt-1 text-sm text-gray-600">
                            Kéo thả file hoặc <span class="text-blue-600 hover:underline cursor-pointer">chọn từ máy tính</span>
                        </p>
                        <p class="mt-1 text-xs text-gray-500">PNG, JPG, GIF lên đến 2MB</p>
                    </div>
                    <div id="preview-container" class="hidden">
                        <img id="image-preview" class="max-h-48 max-w-full mx-auto" src="" alt="Banner preview">
                        <button type="button" id="change-image" class="mt-2 text-sm text-blue-600 hover:underline">
                            Thay đổi hình ảnh
                        </button>
                    </div>
                    <input type="file" id="banner-image" name="AnhUrl" accept="image/jpeg,image/jpg,image/png,image/gif" class="hidden">
                </div>
                <p id="image-error" class="text-red-500 text-xs italic mt-1 hidden">Vui lòng tải lên hình ảnh banner</p>
            </div>

            <div class="mb-4 hidden" id="modal-status-row">
                <label class="block text-gray-700 text-sm font-bold mb-2">Trạng thái hiện tại</label>
                <span id="modal-banner-status" class="inline-block px-3 py-1 rounded-full text-xs font-semibold"></span>
            </div>
            
            <div class="flex justify-between mt-6">
                <div class="flex gap-2">
                    <button type="button" id="change-status-banner" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Thay đổi trạng thái
                    </button>
                    <button type="button" id="delete-banner" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline hidden">
                        Xóa
                    </button>
                </div>
                <div class="flex space-x-4">
                    <button type="button" id="cancel-banner" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Hủy
                    </button>
                    <button type="button" id="save-banner" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Lưu
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="mb-4">
            <h3 class="text-xl font-semibold text-gray-900">Xác nhận xóa banner</h3>
        </div>
        
        <div class="mb-6">
            <p class="text-gray-700">Bạn có chắc chắn muốn xóa banner này? Hành động này không thể hoàn tác.</p>

            <div class="mt-4 flex justify-center bg-gray-100 p-3 rounded-lg">
                <img id="delete-preview" class="w-56 max-h-48 object-contain rounded mr-4 bg-white border" src="" alt="Banner thumbnail">
            </div>
        </div>
        
        <div class="flex justify-end space-x-4">
            <button type="button" id="cancel-delete" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Hủy
            </button>
            <button type="button" id="confirm-delete" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Xác nhận
            </button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast-notification" class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg transform transition-transform duration-300 translate-y-20 opacity-0 z-50">
    Thao tác thành công
</div>
@endsection
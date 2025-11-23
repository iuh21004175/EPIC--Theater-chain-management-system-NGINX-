@extends('internal.layout')

@section('title', 'Quản lý rạp phim')

@section('head')
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/rap-phim.js"></script>
    <style>
        .status-active {
            background-color: #DEF7EC;
            color: #03543E;
        }
        .status-inactive {
            background-color: #FDE8E8;
            color: #9B1C1C;
        }
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
        .modal-header, .modal-footer {
            position: sticky;
            background-color: white;
            z-index: 10;
        }
        .modal-header {
            top: 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-footer {
            bottom: 0;
            border-top: 1px solid #e5e7eb;
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
    </style>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-4 text-gray-500 font-medium">Quản lý rạp phim</span>
    </div>
</li>
@endsection

@section('content')
    <!-- Page header -->
    <div class="pb-5 border-b border-gray-200 sm:flex sm:items-center sm:justify-between">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Danh sách rạp phim</h3>
        <div class="mt-3 sm:mt-0 sm:ml-4">
            <button id="btn-add-cinema" type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Thêm rạp mới
            </button>
        </div>
    </div>

    <!-- Cinema list -->
    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg" style="min-height: 300px;">
                    <table class="min-w-full divide-y divide-gray-200" >
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tên rạp
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Địa chỉ
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Trạng thái
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" data-url="{{$_ENV['URL_WEB_BASE']}}">
                            <tr class="cinema-item cursor-pointer hover:bg-gray-50" data-id="1" data-status="active">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">EPIC Cinema - Quận 1</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">123 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">Nguyễn Văn A</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-active">
                                        Đang hoạt động
                                    </span>
                                </td>
                            </tr>
                            <tr class="cinema-item cursor-pointer hover:bg-gray-50" data-id="2" data-status="inactive">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">EPIC Cinema - Quận 7</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">456 Nguyễn Thị Thập, Quận 7, TP. Hồ Chí Minh</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">Trần Văn B</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-inactive">
                                        Ngừng hoạt động
                                    </span>
                                </td>
                            </tr>
                            <tr class="cinema-item cursor-pointer hover:bg-gray-50" data-id="3" data-status="active">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">EPIC Cinema - Hà Nội</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">789 Nguyễn Trãi, Thanh Xuân, Hà Nội</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">Lê Thị C</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-active">
                                        Đang hoạt động
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- No Results Message -->
    <div id="no-results-message" class="hidden flex-col items-center justify-center py-8">
        <svg class="h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <p class="mt-2 text-gray-500 text-lg">Không tìm thấy rạp phim phù hợp.</p>
    </div>

    <!-- Add Cinema Modal -->
    <div id="add-cinema-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-2xl mx-auto rounded shadow-lg z-50">
            <!-- Modal Header -->
            <div class="modal-header px-6 py-4">
                <div class="flex justify-between items-center">
                    <p class="text-xl font-bold">Thêm rạp phim mới</p>
                    <div class="modal-close cursor-pointer z-50">
                        <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                            <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <form action="{{$_ENV['URL_WEB_BASE']}}/api/rap-phim" id="add-cinema-form" class="space-y-4">
                <!-- Modal Body -->
                <div class="modal-body px-6 py-2">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="cinema-name">
                                Tên rạp <span class="text-red-500">*</span>
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-red-500" id="cinema-name" type="text" placeholder="Nhập tên rạp phim">
                            <p class="text-red-500 text-xs italic hidden" id="cinema-name-error">Vui lòng nhập tên rạp phim.</p>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="cinema-address">
                                Địa chỉ <span class="text-red-500">*</span>
                            </label>
                            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-red-500" id="cinema-address" rows="2" placeholder="Nhập địa chỉ rạp phim"></textarea>
                            <p class="text-red-500 text-xs italic hidden" id="cinema-address-error">Vui lòng nhập địa chỉ rạp phim.</p>
                        </div>

                        <!-- NEW: hotline + mô tả -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="cinema-hotline">
                                Hotline
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline" id="cinema-hotline" type="text" placeholder="Số hotline liên hệ (ví dụ: 0909123456)">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="cinema-description">
                                Mô tả
                            </label>
                            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline" id="cinema-description" rows="3" placeholder="Mô tả ngắn về rạp (tùy chọn)"></textarea>
                        </div>
                        <!-- END NEW -->
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="cinema-map">
                                URL Google Maps Embed
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-red-500" id="cinema-map" type="text" placeholder="Dán URL hoặc iframe Google Maps...">
                            <p class="text-xs text-gray-500 mt-1">
                                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Lấy từ Google Maps → Share → Embed a map → Copy HTML → Dán toàn bộ iframe hoặc chỉ URL trong src="..." (hệ thống sẽ tự động trích xuất URL)
                            </p>
                            
                            <!-- Preview Map -->
                            <div id="cinema-map-preview" class="mt-3 hidden">
                                <p class="text-sm font-semibold text-gray-700 mb-2">Xem trước bản đồ:</p>
                                <div class="border-2 border-gray-300 rounded-lg overflow-hidden">
                                    <iframe id="cinema-map-iframe" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Kinh độ và Vĩ độ -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Tọa độ địa lý
                            </label>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="cinema-longitude">
                                        Kinh độ (Longitude)
                                    </label>
                                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline" id="cinema-longitude" type="number" step="any" placeholder="Ví dụ: 106.7776">
                                    <p class="text-xs text-gray-500 mt-1">Tọa độ kinh độ (từ -180 đến 180)</p>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="cinema-latitude">
                                        Vĩ độ (Latitude)
                                    </label>
                                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline" id="cinema-latitude" type="number" step="any" placeholder="Ví dụ: 10.8466">
                                    <p class="text-xs text-gray-500 mt-1">Tọa độ vĩ độ (từ -90 đến 90)</p>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="modal-footer px-6 py-4">
                    <div class="flex items-center justify-end">
                        <button type="button" class="modal-close-btn px-4 bg-gray-200 p-3 rounded-lg text-black hover:bg-gray-300 mr-2">Hủy</button>
                        <button type="submit" class="px-4 bg-red-600 p-3 rounded-lg text-white hover:bg-red-700">Thêm</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Cinema Modal -->
    <div id="edit-cinema-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-2xl mx-auto rounded shadow-lg z-50">
            <!-- Modal Header -->
            <div class="modal-header px-6 py-4">
                <div class="flex justify-between items-center">
                    <p class="text-xl font-bold">Cập nhật thông tin rạp phim</p>
                    <div class="modal-close cursor-pointer z-50">
                        <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                            <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <form id="edit-cinema-form" class="space-y-4">
                <input type="hidden" id="edit-cinema-id">
                <input type="hidden" id="edit-cinema-status">
                
                <!-- Modal Body -->
                <div class="modal-body px-6 py-2">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-cinema-name">
                                Tên rạp <span class="text-red-500">*</span>
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-red-500" id="edit-cinema-name" type="text" placeholder="Nhập tên rạp phim">
                            <p class="text-red-500 text-xs italic hidden" id="edit-cinema-name-error">Vui lòng nhập tên rạp phim.</p>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-cinema-address">
                                Địa chỉ <span class="text-red-500">*</span>
                            </label>
                            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-red-500" id="edit-cinema-address" rows="2" placeholder="Nhập địa chỉ rạp phim"></textarea>
                            <p class="text-red-500 text-xs italic hidden" id="edit-cinema-address-error">Vui lòng nhập địa chỉ rạp phim.</p>
                        </div>

                        <!-- NEW: hotline + mô tả (edit) -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-cinema-hotline">
                                Hotline
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline" id="edit-cinema-hotline" type="text" placeholder="Số hotline liên hệ (ví dụ: 0909123456)">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-cinema-description">
                                Mô tả
                            </label>
                            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline" id="edit-cinema-description" rows="3" placeholder="Mô tả ngắn về rạp (tùy chọn)"></textarea>
                        </div>
                        <!-- END NEW -->
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-cinema-map">
                                URL Google Maps Embed
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-red-500" id="edit-cinema-map" type="text" placeholder="Dán URL hoặc iframe Google Maps...">
                            <p class="text-xs text-gray-500 mt-1">
                                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Lấy từ Google Maps → Share → Embed a map → Copy HTML → Dán toàn bộ iframe hoặc chỉ URL trong src="..." (hệ thống sẽ tự động trích xuất URL)
                            </p>
                            
                            <!-- Preview Map -->
                            <div id="edit-cinema-map-preview" class="mt-3 hidden">
                                <p class="text-sm font-semibold text-gray-700 mb-2">Xem trước bản đồ:</p>
                                <div class="border-2 border-gray-300 rounded-lg overflow-hidden">
                                    <iframe id="edit-cinema-map-iframe" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Kinh độ và Vĩ độ -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Tọa độ địa lý
                            </label>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-cinema-longitude">
                                        Kinh độ (Longitude)
                                    </label>
                                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline" id="edit-cinema-longitude" type="number" step="any" placeholder="Ví dụ: 106.7776">
                                    <p class="text-xs text-gray-500 mt-1">Tọa độ kinh độ (từ -180 đến 180)</p>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-cinema-latitude">
                                        Vĩ độ (Latitude)
                                    </label>
                                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline" id="edit-cinema-latitude" type="number" step="any" placeholder="Ví dụ: 10.8466">
                                    <p class="text-xs text-gray-500 mt-1">Tọa độ vĩ độ (từ -90 đến 90)</p>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Trạng thái hiện tại</label>
                            <div class="flex items-center">
                                <span id="status-indicator" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                                    Đang hoạt động
                                </span>
                                <button type="button" id="toggle-status-btn" class="ml-3 inline-flex items-center px-3 py-1 border border-gray-300 text-sm leading-5 font-medium rounded-md text-gray-700 bg-white hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:text-gray-800 active:bg-gray-50 transition ease-in-out duration-150">
                                    Thay đổi trạng thái
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="modal-footer px-6 py-4">
                    <div class="flex items-center justify-end">
                        <button type="button" class="modal-close-btn px-4 bg-gray-200 p-3 rounded-lg text-black hover:bg-gray-300 mr-2">Hủy</button>
                        <button type="submit" class="px-4 bg-red-600 p-3 rounded-lg text-white hover:bg-red-700">Lưu</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal for Status Change -->
    <div id="confirm-status-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-50">
            <div class="modal-header px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <p class="text-xl font-bold">Xác nhận thay đổi trạng thái</p>
                    <div class="modal-close cursor-pointer z-50">
                        <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                            <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="modal-body px-6 py-4">
                <p id="confirm-status-message" class="text-gray-700">Bạn có chắc chắn muốn thay đổi trạng thái rạp phim này không?</p>
            </div>
            
            <div class="modal-footer px-6 py-4 border-t border-gray-200">
                <div class="flex justify-end">
                    <button id="confirm-status-cancel" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg mr-2 hover:bg-gray-300">Hủy</button>
                    <button id="confirm-status-ok" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Xác nhận</button>
                </div>
            </div>
        </div>
    </div>
@endsection
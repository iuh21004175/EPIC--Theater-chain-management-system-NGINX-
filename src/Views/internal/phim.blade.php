@extends('internal.layout')

@section('title', 'Quản lý Phim')

@section('head')
<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/phim.js"></script>
<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/the-loai-phim.js"></script>
<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/phan-phoi-phim.js"></script>
<style>
    /* Giữ nguyên CSS cũ */
    .movie-poster {
        width: 100px;
        height: 150px;
        object-fit: cover;
    }
    .modal {
        transition: opacity 0.25s ease;
    }
    .modal-active {
        overflow-x: hidden;
        overflow-y: visible !important;
    }
    .status-coming {
        background-color: #EBF5FF;
        color: #1E40AF;
    }
    .status-now {
        background-color: #DEF7EC;
        color: #03543E;
    }
    .status-stopped {
        background-color: #FDE8E8;
        color: #9B1C1C;
    }
    
    /* Tab styling */
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
    
    /* Modal styling - Cập nhật */
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
    
    /* Hiển thị thanh cuộn trong modal */
    .modal-body {
        overflow-y: scroll; /* Thay auto bằng scroll để luôn hiển thị thanh cuộn */
        max-height: 60vh;
        padding-right: 0.5rem;
        scrollbar-width: thin; /* Cho Firefox */
        scrollbar-color: rgba(156, 163, 175, 0.5) transparent; /* Cho Firefox */
    }
    
    /* Tùy chỉnh thanh cuộn cho Chrome, Safari và Edge */
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

    /* Thêm CSS cho phần phân phối phim */
    .movie-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f9fafb;
        border-radius: 8px;
        padding: 8px 12px;
        cursor: grab;
        border: 1px solid #e5e7eb;
        transition: box-shadow 0.2s, opacity 0.3s, transform 0.3s;
    }
    .movie-card:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }
    .movie-card.dragging {
        opacity: 0.5;
        box-shadow: 0 0 0 2px #3b82f6;
    }
    .movie-card .poster {
        width: 48px;
        height: 72px;
        object-fit: cover;
        border-radius: 4px;
        transition: transform 0.2s;
    }
    .movie-card:hover .poster {
        transform: scale(1.05);
    }
    .rap-item {
        transition: background-color 0.2s, transform 0.2s, opacity 0.2s;
    }
    .rap-item.selected {
        background: #e0f2fe;
        font-weight: bold;
        transform: translateX(4px);
    }
    .rap-item:hover:not(.selected) {
        background: #f3f4f6;
    }
    .rap-item .count {
        background: #f87171;
        color: #fff;
        border-radius: 8px;
        padding: 0 8px;
        font-size: 12px;
        margin-left: 6px;
    }
    /* CSS cho phim đã phân phối */
    .phim-phanphoi-item {
        transition: opacity 0.3s, transform 0.3s, box-shadow 0.2s;
    }
    .phim-phanphoi-item:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transform: translateX(4px);
    }
    .btn-remove-phanphoi {
        transition: color 0.2s, transform 0.2s;
    }
    .btn-remove-phanphoi:hover {
        transform: scale(1.1);
    }
</style>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-4 text-gray-500 font-medium">Quản lý Phim</span>
    </div>
</li>
<li>
    <div class="flex items-center ml-4 space-x-2">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <div class="flex rounded-md shadow-sm">
            <button id="tab-btn-phim" class="tab-btn px-4 py-2 text-sm font-medium rounded-l-md bg-red-600 text-white" aria-current="page">
                Phim
            </button>
            <button id="tab-btn-theloai" class="tab-btn px-4 py-2 text-sm font-medium rounded-r-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
                Thể loại
            </button>
            <button id="tab-btn-phanphoi" class="tab-btn px-4 py-2 text-sm font-medium rounded-r-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
                Phân phối phim
            </button>
        </div>
    </div>
</li>
@endsection

@section('content')
<!-- Tab Content Container -->
<div class="tab-container">
    <!-- Tab: Phim -->
    <div id="tab-phim" class="tab-content active">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Danh sách Phim</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Quản lý các bộ phim trong hệ thống</p>
                </div>
                <button id="btn-add-movie" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Thêm phim mới
                </button>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <!-- Filter and search section -->
                <div class="flex flex-col md:flex-row gap-4 mb-5">
                    <div class="relative flex-1 group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400 group-hover:text-red-500 transition-colors duration-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" id="search" class="focus:ring-red-500 focus:border-red-500 block w-full pl-10 py-2.5 sm:text-sm border-gray-300 rounded-lg shadow-sm transition-all duration-200 hover:border-red-300" placeholder="Tìm kiếm phim theo tên, đạo diễn hoặc diễn viên...">
                        <div class="absolute inset-y-0 right-0 flex py-1.5 pr-1.5">
                            <kbd class="inline-flex items-center rounded border border-gray-200 px-2 font-sans text-sm font-medium text-gray-400 group-hover:border-red-300 group-hover:text-red-500 transition-colors duration-200">⌘K</kbd>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <select id="filter-status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm rounded-lg shadow-sm">
                            <option value="">Tất cả trạng thái</option>
                            <option value="0">Sắp chiếu</option>
                            <option value="1">Đang chiếu</option>
                            <option value="-1">Ngừng chiếu</option>
                        </select>
                    </div>
                    <div class="flex-shrink-0">
                        <select id="filter-genre" name="genre" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm rounded-lg shadow-sm">
                            <option value="">Tất cả thể loại</option>
                            <option value="1">Hành động</option>
                            <option value="2">Kinh dị</option>
                            <option value="3">Hài</option>
                            <option value="4">Tình cảm</option>
                        </select>
                    </div>
                    <div class="flex-shrink-0">
                        <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:border-red-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200">
                            <svg class="h-5 w-5 mr-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Lọc
                        </button>
                    </div>
                </div>

                <!-- Movies Table -->
                <div class="flex flex-col">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg" style="min-height: 300px;">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phim</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thể loại</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời lượng</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="movie-list" data-url="{{$_ENV['URL_WEB_BASE']}}" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}">
                                        <!-- JS sẽ render danh sách phim tại đây -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <!-- Xóa toàn bộ phần phân trang này -->
                <!-- 
                <div class="mt-4 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                    <div class="flex flex-1 justify-between sm:hidden">
                        <a href="#" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</a>
                        <a href="#" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">1</span>
                                to
                                <span class="font-medium">2</span>
                                of
                                <span class="font-medium">2</span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                <a href="#" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                </a>
                                <a href="#" aria-current="page" class="relative z-10 inline-flex items-center bg-red-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">1</a>
                                <a href="#" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
                -->
                <!-- Pagination cho tab Phim - cần khôi phục lại -->
                <div class="mt-4 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                    <div class="flex flex-1 justify-between sm:hidden">
                        <a href="#" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</a>
                        <a href="#" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">1</span>
                                to
                                <span class="font-medium">2</span>
                                of
                                <span class="font-medium">2</span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                <a href="#" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                                <a href="#" aria-current="page" class="relative z-10 inline-flex items-center bg-red-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">1</a>
                                <a href="#" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Thể loại -->
    <div id="tab-theloai" class="tab-content">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Danh sách Thể loại</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Quản lý các thể loại phim trong hệ thống</p>
                </div>
                <button id="btn-add-genre" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Thêm thể loại mới
                </button>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">

                <!-- Genres Table -->
                <div class="flex flex-col">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên thể loại</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số phim</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" data-url="{{$_ENV['URL_WEB_BASE']}}">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">Hành động</div>
                                            </td>
                                            <!-- Xóa phần mô tả -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">12</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">Kinh dị</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">8</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">Hài</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">15</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <!-- Xóa hoàn toàn phần phân trang trong tab Thể loại này -->
                <!-- 
                <div class="mt-4 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                    <div class="flex flex-1 justify-between sm:hidden">
                        <a href="#" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</a>
                        <a href="#" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">1</span>
                                to
                                <span class="font-medium">3</span>
                                of
                                <span class="font-medium">10</span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                <a href="#" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                </a>
                                <a href="#" aria-current="page" class="relative z-10 inline-flex items-center bg-red-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">1</a>
                                <a href="#" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
                -->
            </div>
        </div>
    </div>

    <!-- Tab: Phân phối phim -->
    <div id="tab-phanphoi" class="tab-content">
        <div class="flex gap-6">
        <!-- Cột 1: Kho phim -->
        <div class="w-1/3 bg-white rounded-lg shadow p-4 flex flex-col">
            <h2 class="text-lg font-semibold mb-4 text-gray-800" id="movie-stock-title"></h2>
            <div class="mb-4">
                <input type="text" id="search-movie" class="w-full border rounded px-3 py-2" placeholder="Tìm kiếm phim...">
                <div class="flex gap-2 mt-2">
                    <select id="filter-movie-status" class="border rounded px-2 py-1">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1">Đang chiếu</option>
                        <option value="0">Sắp chiếu</option>
                    </select>
                    <select id="filter-movie-genre" class="border rounded px-2 py-1">
                        <option value="">Tất cả thể loại</option>
                        <!-- JS render -->
                    </select>
                    <select id="filter-movie-rating" class="border rounded px-2 py-1">
                        <option value="">Tất cả nhãn</option>
                        <option value="P">P</option>
                        <option value="C13">C13</option>
                        <option value="C16">C16</option>
                        <option value="C18">C18</option>
                    </select>
                </div>
            </div>
            <div id="movie-stock-list" class="flex-1 overflow-y-auto space-y-3 pr-2" data-url="{{$_ENV['URL_WEB_BASE']}}" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}">
                <!-- JS render card phim -->
            </div>
            <div id="movie-stock-pagination" class="flex items-center justify-center gap-1 mt-4"></div>
        </div>

        <!-- Cột 2: Danh sách rạp -->
        <div class="w-1/4 bg-white rounded-lg shadow p-4 flex flex-col">
            <div class="mb-4">
                <input type="text" id="search-rap" class="w-full border rounded px-3 py-2" placeholder="Tìm kiếm rạp...">
            </div>
            <div id="rap-list" class="flex-1 overflow-y-auto space-y-2 pr-2" data-url="{{$_ENV['URL_WEB_BASE']}}">
                <!-- JS render danh sách rạp, mỗi rạp có checkbox và số lượng phim -->
            </div>
            <button id="btn-bulk-phanphoi" class="mt-4 w-full bg-blue-600 text-white py-2 rounded hidden">
                Phân phối cho <span id="bulk-rap-count">0</span> rạp đã chọn
            </button>
        </div>

        <!-- Cột 3: Chi tiết phân phối của rạp -->
        <div class="w-2/5 bg-white rounded-lg shadow p-4 flex flex-col">
            <div class="flex items-center mb-4">
                <span class="font-semibold text-lg" id="selected-rap-title">Chọn rạp để xem chi tiết phân phối</span>
            </div>
            <div id="rap-phim-list" class="flex-1 overflow-y-auto space-y-3 pr-2 min-h-[120px]" data-url="{{$_ENV['URL_WEB_BASE']}}" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}">
                <!-- JS render danh sách phim đã phân phối cho rạp -->
            </div>
        </div>
    </div>
    
</div>

<!-- Add Movie Modal -->
<div id="add-movie-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
    <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
    
    <div class="modal-container bg-white w-11/12 md:max-w-3xl mx-auto rounded shadow-lg z-50">
        <!-- Modal Header -->
        <div class="modal-header px-6 py-4">
            <div class="flex justify-between items-center">
                <p class="text-xl font-bold">Thêm phim mới</p>
                <div class="modal-close cursor-pointer z-50">
                    <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                        <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <form id="add-movie-form" class="space-y-4">
            <!-- Modal Body -->
            <div class="modal-body px-6 py-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="movie-title">
                            Tên phim <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="movie-title" type="text" placeholder="Nhập tên phim">
                        <p class="text-red-500 text-xs italic hidden" id="movie-title-error">Vui lòng nhập tên phim.</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="movie-director">
                            Đạo diễn <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="movie-director" type="text" placeholder="Nhập tên đạo diễn">
                        <p class="text-red-500 text-xs italic hidden" id="movie-director-error">Vui lòng nhập tên đạo diễn.</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="movie-actors">
                            Diễn viên <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="movie-actors" type="text" placeholder="Nhập tên diễn viên (cách nhau bởi dấu phẩy)">
                        <p class="text-red-500 text-xs italic hidden" id="movie-actors-error">Vui lòng nhập tên diễn viên.</p>
                    </div>
                    
                    <!-- Thay thế phần thể loại trong add-movie-modal -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Thể loại <span class="text-red-500">*</span>
                        </label>
                        <div class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white max-h-40 overflow-y-auto">
                            <div class="grid grid-cols-2 gap-2" id="movie-genres-container">
                                <!-- JS sẽ render checkbox thể loại ở đây -->
                            </div>
                        </div>
                        <p class="text-red-500 text-xs italic hidden" id="movie-genres-error">Vui lòng chọn ít nhất một thể loại.</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="movie-duration">
                            Thời lượng (phút) <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="movie-duration" type="number" min="1" placeholder="Nhập thời lượng phim (phút)">
                        <p class="text-red-500 text-xs italic hidden" id="movie-duration-error">Vui lòng nhập thời lượng phim.</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="movie-rating">
                            Tiêu chuẩn phân loại <span class="text-red-500">*</span>
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="movie-rating">
                            <option value="">Chọn tiêu chuẩn phân loại</option>
                            <option value="P">P - Phim dành cho mọi đối tượng</option>
                            <option value="C13">C13 - Phim cấm khán giả dưới 13 tuổi</option>
                            <option value="C16">C16 - Phim cấm khán giả dưới 16 tuổi</option>
                            <option value="C18">C18 - Phim cấm khán giả dưới 18 tuổi</option>
                        </select>
                        <p class="text-red-500 text-xs italic hidden" id="movie-rating-error">Vui lòng chọn tiêu chuẩn phân loại.</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="movie-poster">
                            Poster phim <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="movie-poster" type="file" accept="image/*">
                        <p class="text-red-500 text-xs italic hidden" id="movie-poster-error">Vui lòng chọn poster phim.</p>
                    </div>
                    
                    <!-- Thay thế phần trailer trong add-movie-modal -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="movie-trailer">
                            Trailer (URL YouTube)
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                               id="movie-trailer" 
                               type="url" 
                               placeholder="Dán URL YouTube vào đây (https://www.youtube.com/watch?v=...)"
                               pattern="https?:\/\/(www\.)?youtube\.com\/watch\?v=.+">
                        <p class="text-xs text-gray-500 mt-1">Ví dụ: https://www.youtube.com/watch?v=dQw4w9WgXcQ</p>
                    </div>

                    <!-- Thêm đoạn này ngay sau input trailer trong cả hai modal -->
                    <div class="mt-3 hidden" id="movie-trailer-preview">
                        <div class="border rounded-md overflow-hidden">
                            <div class="relative bg-gray-100 cursor-pointer" id="movie-trailer-thumbnail-container">
                                <img id="movie-trailer-thumbnail" src="" alt="Thumbnail" class="w-full object-cover">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="bg-red-600 bg-opacity-80 rounded-full w-12 h-12 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3">
                                <h3 id="movie-trailer-title" class="font-medium text-sm line-clamp-2"></h3>
                                <p id="movie-trailer-channel" class="text-xs text-gray-500 mt-1"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Thêm vào trong <form id="add-movie-form" ...> trước phần mô tả -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="movie-release-date">
                            Ngày công chiếu <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                               id="movie-release-date"
                               type="date"
                               required>
                        <p class="text-red-500 text-xs italic hidden" id="movie-release-date-error">Vui lòng chọn ngày công chiếu.</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-movie-video">
                            Tải video lên
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="movie-video"
                            type="file"
                            accept="video/mp4,video/webm,video/ogg,video/x-matroska,video/avi,video/mov,video/mpeg,video/3gpp,video/quicktime">
                        <p class="text-xs text-gray-500 mt-1">Chấp nhận các định dạng: mp4, webm, ogg, mkv, avi, mov, mpeg, 3gp, quicktime</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="movie-country">
                            Quốc gia <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                               id="movie-country"
                               type="text"
                               placeholder="Nhập quốc gia sản xuất"
                               required>
                        <p class="text-red-500 text-xs italic hidden" id="movie-country-error">Vui lòng nhập quốc gia.</p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="movie-description">
                        Mô tả <span class="text-red-500">*</span>
                    </label>
                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="movie-description" rows="4" placeholder="Nhập mô tả phim"></textarea>
                    <p class="text-red-500 text-xs italic hidden" id="movie-description-error">Vui lòng nhập mô tả phim.</p>
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

<!-- Edit Movie Modal -->
<div id="edit-movie-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
    <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
    <div class="modal-container bg-white w-11/12 md:max-w-3xl mx-auto rounded shadow-lg z-50">
        <!-- Modal Header -->
        <div class="modal-header px-6 py-4">
            <div class="flex justify-between items-center">
                <p class="text-xl font-bold">Cập nhật thông tin phim</p>
                <div class="modal-close cursor-pointer z-50">
                    <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                        <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <form id="edit-movie-form" class="space-y-4">
            <!-- Modal Body -->
            <div class="modal-body px-6 py-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Các trường giống như modal thêm phim, chỉ đổi id thành edit-movie-* -->
                    <input type="hidden" id="edit-movie-id">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-movie-title">
                            Tên phim <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="edit-movie-title" type="text" placeholder="Nhập tên phim">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-movie-director">
                            Đạo diễn <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="edit-movie-director" type="text" placeholder="Nhập tên đạo diễn">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-movie-actors">
                            Diễn viên <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="edit-movie-actors" type="text" placeholder="Nhập tên diễn viên (cách nhau bởi dấu phẩy)">
                    </div>
                    <!-- Thể loại -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Thể loại <span class="text-red-500">*</span>
                        </label>
                        <div class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white max-h-40 overflow-y-auto">
                            <div class="grid grid-cols-2 gap-2" id="edit-movie-genres-container">
                                <!-- JS sẽ render checkbox thể loại ở đây -->
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-movie-duration">
                            Thời lượng (phút) <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="edit-movie-duration" type="number" min="1" placeholder="Nhập thời lượng phim (phút)">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-movie-rating">
                            Tiêu chuẩn phân loại <span class="text-red-500">*</span>
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="edit-movie-rating">
                            <option value="">Chọn tiêu chuẩn phân loại</option>
                            <option value="P">P - Phim dành cho mọi đối tượng</option>
                            <option value="C13">C13 - Phim cấm khán giả dưới 13 tuổi</option>
                            <option value="C16">C16 - Phim cấm khán giả dưới 16 tuổi</option>
                            <option value="C18">C18 - Phim cấm khán giả dưới 18 tuổi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-movie-status">
                            Trạng thái <span class="text-red-500">*</span>
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="edit-movie-status">
                            <option value="">Chọn trạng thái</option>
                            <option value="1">Đang chiếu</option>
                            <option value="0">Ngừng chiếu</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-movie-poster">
                            Poster phim <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="edit-movie-poster" type="file" accept="image/*">
                        <img id="current-poster-img" src="" alt="Poster" class="h-20 w-14 rounded-sm object-cover mt-2">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-movie-trailer">
                            Trailer (URL YouTube)
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                               id="edit-movie-trailer" 
                               type="url" 
                               placeholder="Dán URL YouTube vào đây (https://www.youtube.com/watch?v=...)"
                               pattern="https?:\/\/(www\.)?youtube\.com\/watch\?v=.+">
                        <p class="text-xs text-gray-500 mt-1">Ví dụ: https://www.youtube.com/watch?v=dQw4w9WgXcQ</p>
                    </div>
                    <div class="mt-3 hidden" id="edit-movie-trailer-preview">
                        <div class="border rounded-md overflow-hidden">
                            <div class="relative bg-gray-100 cursor-pointer" id="edit-movie-trailer-thumbnail-container">
                                <img id="edit-movie-trailer-thumbnail" src="" alt="Thumbnail" class="w-full object-cover">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="bg-red-600 bg-opacity-80 rounded-full w-12 h-12 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3">
                                <h3 id="edit-movie-trailer-title" class="font-medium text-sm line-clamp-2"></h3>
                                <p id="edit-movie-trailer-channel" class="text-xs text-gray-500 mt-1"></p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-movie-release-date">
                            Ngày công chiếu <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                               id="edit-movie-release-date"
                               type="date"
                               required>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-movie-video">
                            Tải video lên
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="edit-movie-video"
                            type="file"
                            accept="video/mp4,video/webm,video/ogg,video/x-matroska,video/avi,video/mov,video/mpeg,video/3gpp,video/quicktime">
                        <p class="text-xs text-gray-500 mt-1">Chấp nhận các định dạng: mp4, webm, ogg, mkv, avi, mov, mpeg, 3gp, quicktime</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-movie-country">
                            Quốc gia <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                               id="edit-movie-country"
                               type="text"
                               placeholder="Nhập quốc gia sản xuất"
                               required>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-movie-description">
                        Mô tả <span class="text-red-500">*</span>
                    </label>
                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="edit-movie-description" rows="4" placeholder="Nhập mô tả phim"></textarea>
                </div>
            </div>
            <!-- Modal Footer -->
            <div class="modal-footer px-6 py-4">
                <div class="flex items-center justify-end">
                    <button type="button" class="modal-close-btn px-4 bg-gray-200 p-3 rounded-lg text-black hover:bg-gray-300 mr-2">Hủy</button>
                    <button type="submit" class="px-4 bg-red-600 p-3 rounded-lg text-white hover:bg-red-700">Cập nhật</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Genre Modal -->
<div id="add-genre-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
    <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
    
    <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-50">
        <div class="modal-content py-4 text-left px-6">
            <div class="flex justify-between items-center pb-3">
                <p class="text-xl font-bold">Thêm thể loại mới</p>
                <div class="modal-close cursor-pointer z-50">
                    <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                        <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                    </svg>
                </div>
            </div>

            <form id="add-genre-form">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="genre-name">
                        Tên thể loại <span class="text-red-500">*</span>
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="genre-name" type="text" placeholder="Nhập tên thể loại">
                    <p class="text-red-500 text-xs italic hidden" id="genre-name-error">Vui lòng nhập tên thể loại.</p>
                </div>
                <div class="flex items-center justify-end pt-2">
                    <button type="button" class="modal-close-btn px-4 bg-gray-200 p-3 rounded-lg text-black hover:bg-gray-300 mr-2">Hủy</button>
                    <button type="submit" class="px-4 bg-red-600 p-3 rounded-lg text-white hover:bg-red-700">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Genre Modal -->
<div id="edit-genre-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
    <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
    
    <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-50">
        <div class="modal-content py-4 text-left px-6">
            <div class="flex justify-between items-center pb-3">
                <p class="text-xl font-bold">Chỉnh sửa thông tin thể loại</p>
                <div class="modal-close cursor-pointer z-50">
                    <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                        <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                    </svg>
                </div>
            </div>

            <form id="edit-genre-form">
                <input type="hidden" id="edit-genre-id">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-genre-name">
                        Tên thể loại <span class="text-red-500">*</span>
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="edit-genre-name" type="text" placeholder="Nhập tên thể loại">
                    <p class="text-red-500 text-xs italic hidden" id="edit-genre-name-error">Vui lòng nhập tên thể loại.</p>
                </div>
                <div class="flex items-center justify-end pt-2">
                    <button type="button" class="modal-close-btn px-4 bg-gray-200 p-3 rounded-lg text-black hover:bg-gray-300 mr-2">Hủy</button>
                    <button type="submit" class="px-4 bg-red-600 p-3 rounded-lg text-white hover:bg-red-700">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- No Results Message -->
<div id="no-results-message" class="hidden flex-col items-center justify-center py-8">
    <svg class="h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
    </svg>
    <p class="mt-2 text-gray-500 text-lg">Không tìm thấy kết quả phù hợp.</p>
</div>

<!-- Thêm modal xem video -->
<div id="video-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
    <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-90"></div>
    
    <div class="modal-container bg-black w-11/12 md:max-w-4xl mx-auto rounded shadow-lg z-50">
        <div class="modal-content py-3 text-left px-3">
            <div class="flex justify-between items-center pb-2">
                <p class="text-lg font-bold text-white" id="video-modal-title"></p>
                <div class="modal-close cursor-pointer z-50">
                    <svg class="fill-current text-white" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 18 18">
                        <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                    </svg>
                </div>
            </div>

            <div class="aspect-w-16">
                <iframe id="youtube-iframe" class="w-full" src="" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
    </div>
</div>


<!-- Modal phân phối phim (dùng cho kéo-thả hoặc nút bulk) -->
<div id="phanphoi-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">Phân phối phim</h3>
        <div class="mb-3">
            <span id="phanphoi-modal-movie-title" class="font-semibold"></span>
        </div>
        <div class="mb-3">
            <label class="block mb-1">Ngày bắt đầu</label>
            <input type="date" id="phanphoi-start-date" class="border rounded px-2 py-1 w-full">
        </div>
        <div class="mb-3">
            <label class="block mb-1">Ngày kết thúc</label>
            <input type="date" id="phanphoi-end-date" class="border rounded px-2 py-1 w-full">
        </div>
        <div class="flex justify-end gap-2">
            <button id="btn-cancel-phanphoi" class="bg-gray-300 px-4 py-2 rounded">Hủy</button>
            <button id="btn-save-phanphoi" class="bg-blue-600 text-white px-4 py-2 rounded">Lưu</button>
        </div>
    </div>
</div>
@endsection
@extends('internal.layout')

@section('title', 'Quản lý sản phẩm ăn uống')

@section('head')
    <style>
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/san-pham-an-uong.js"></script>
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/danh-muc-san-pham.js"></script>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-4 text-gray-500 font-medium">Quản lý sản phẩm ăn uống</span>
    </div>
</li>
<li>
    <div class="flex items-center ml-4 space-x-2">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <div class="flex rounded-md shadow-sm">
            <button id="tab-btn-products" class="tab-btn px-4 py-2 text-sm font-medium rounded-l-md bg-red-600 text-white">
                Sản phẩm
            </button>
            <button id="tab-btn-categories" class="tab-btn px-4 py-2 text-sm font-medium rounded-r-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
                Danh mục
            </button>
        </div>
    </div>
</li>
@endsection

@section('content')
    <!-- Tab Content Container -->
    <div class="tab-container">
        <!-- Tab: Products -->
        <div id="tab-products" class="tab-content active">
            <!-- Page header -->
            <div class="pb-5 border-b border-gray-200 sm:flex sm:items-center sm:justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Danh sách sản phẩm ăn uống</h3>
                <div class="mt-3 sm:mt-0 sm:ml-4">
                    <button id="btn-add-product" type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Thêm sản phẩm mới
                    </button>
                </div>
            </div>

            <!-- Search and filter bar -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6 mb-6">
                <div class="flex flex-col lg:flex-row gap-4 items-end">
                    <!-- Search input -->
                    <div class="flex-1 w-full">
                        <label for="search-product" class="block text-sm font-medium text-gray-700 mb-2">
                            Tìm kiếm
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400 group-focus-within:text-red-500 transition-colors duration-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input 
                                type="text" 
                                id="search-product" 
                                class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200 hover:border-gray-400" 
                                placeholder="Nhập tên sản phẩm hoặc mô tả..."
                            >
                        </div>
                    </div>
                    
                    <!-- Category filter -->
                    <div class="w-full lg:w-64">
                        <label for="filter-category" class="block text-sm font-medium text-gray-700 mb-2">
                            Lọc theo danh mục
                        </label>
                        <div class="relative">
                            <select 
                                id="filter-category" 
                                class="block w-full pl-4 pr-10 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-700 bg-white appearance-none focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200 hover:border-gray-400 cursor-pointer"
                            >
                                <option value="">Tất cả danh mục</option>
                                @foreach($danhMucs as $danhMuc)
                                    <option value="{{$danhMuc['id']}}">{{$danhMuc['ten']}}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search button -->
                    <div class="w-full lg:w-auto">
                        <button 
                            id="btn-search-product" 
                            type="button" 
                            class="w-full lg:w-auto inline-flex items-center justify-center px-6 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200"
                        >
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Tìm kiếm
                        </button>
                    </div>
                </div>
            </div>

            <!-- Products list -->
            <div class="flex flex-col">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Sản phẩm
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Danh mục
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Giá bán
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Mô tả
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" data-url="{{$_ENV['URL_WEB_BASE']}}" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}">
                                    <!-- Product rows will be dynamically inserted here -->
                                   
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Results Message -->
            <div id="no-results-products" class="hidden flex-col items-center justify-center py-8">
                <svg class="h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <p class="mt-2 text-gray-500 text-lg">Không tìm thấy sản phẩm phù hợp.</p>
            </div>
        </div>

        <!-- Tab: Categories -->
        <div id="tab-categories" class="tab-content">
            <!-- Page header -->
            <div class="pb-5 border-b border-gray-200 sm:flex sm:items-center sm:justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Danh mục sản phẩm</h3>
                <div class="mt-3 sm:mt-0 sm:ml-4">
                    <button id="btn-add-category" type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Thêm danh mục mới
                    </button>
                </div>
            </div>

            <!-- Categories list -->
            <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200" id="category-list" data-url="{{$_ENV['URL_WEB_BASE']}}">
            
                </ul>
            </div>
        </div>

    </div>

    <!-- Add Product Modal -->
    <div id="add-product-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-2xl mx-auto rounded shadow-lg z-50">
            <!-- Modal Header -->
            <div class="modal-header px-6 py-4">
                <div class="flex justify-between items-center">
                    <p class="text-xl font-bold">Thêm sản phẩm mới</p>
                    <div class="modal-close cursor-pointer z-50">
                        <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                            <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <form id="add-product-form" class="space-y-4">
                <!-- Modal Body -->
                <div class="modal-body px-6 py-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="product-name">
                                Tên sản phẩm <span class="text-red-500">*</span>
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="product-name" type="text" placeholder="Nhập tên sản phẩm">
                            <p class="text-red-500 text-xs italic hidden" id="product-name-error">Vui lòng nhập tên sản phẩm.</p>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="product-category">
                                Danh mục <span class="text-red-500">*</span>
                            </label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="product-category">
                                <option value="">Chọn danh mục</option>
                            </select>
                            <p class="text-red-500 text-xs italic hidden" id="product-category-error">Vui lòng chọn danh mục.</p>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="product-price">
                                Giá bán <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="product-price" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="0" min="0">
                            <p class="text-red-500 text-xs italic hidden" id="product-price-error">Vui lòng nhập giá bán hợp lệ.</p>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="product-image">
                                Hình ảnh <span class="text-red-500">*</span>
                            </label>
                            <input type="file" id="product-image" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" accept="image/*">
                            <p class="text-red-500 text-xs italic hidden" id="product-image-error">Vui lòng chọn hình ảnh cho sản phẩm.</p>
                            <div class="mt-2">
                                <img id="preview-image" class="hidden max-w-full h-32 object-contain rounded">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="product-description">
                            Mô tả
                        </label>
                        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="product-description" rows="3" placeholder="Nhập mô tả sản phẩm"></textarea>
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

    <!-- Edit Product Modal -->
    <div id="edit-product-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-2xl mx-auto rounded shadow-lg z-50">
            <!-- Modal Header -->
            <div class="modal-header px-6 py-4">
                <div class="flex justify-between items-center">
                    <p class="text-xl font-bold">Cập nhật sản phẩm</p>
                    <div class="modal-close cursor-pointer z-50">
                        <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                            <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <form id="edit-product-form" class="space-y-4">
                <input type="hidden" id="edit-product-id">
                
                <!-- Modal Body -->
                <div class="modal-body px-6 py-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-product-name">
                                Tên sản phẩm <span class="text-red-500">*</span>
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="edit-product-name" type="text" placeholder="Nhập tên sản phẩm">
                            <p class="text-red-500 text-xs italic hidden" id="edit-product-name-error">Vui lòng nhập tên sản phẩm.</p>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-product-category">
                                Danh mục <span class="text-red-500">*</span>
                            </label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="edit-product-category">
                                <option value="">Chọn danh mục</option>
                            </select>
                            <p class="text-red-500 text-xs italic hidden" id="edit-product-category-error">Vui lòng chọn danh mục.</p>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-product-price">
                                Giá bán <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="edit-product-price" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="0" min="0">
                            <p class="text-red-500 text-xs italic hidden" id="edit-product-price-error">Vui lòng nhập giá bán hợp lệ.</p>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-product-image">
                                Hình ảnh
                            </label>
                            <div class="mb-2">
                                <img id="edit-preview-image" class="hidden max-w-full h-32 object-contain rounded">
                            </div>
                            <input type="file" id="edit-product-image" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" accept="image/*">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-product-description">
                            Mô tả
                        </label>
                        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="edit-product-description" rows="3" placeholder="Nhập mô tả sản phẩm"></textarea>
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

    <!-- Add Category Modal -->
    <div id="add-category-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-50">
            <!-- Modal Header -->
            <div class="modal-header px-6 py-4">
                <div class="flex justify-between items-center">
                    <p class="text-xl font-bold">Thêm danh mục mới</p>
                    <div class="modal-close cursor-pointer z-50">
                        <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                            <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <form id="add-category-form">
                <!-- Modal Body -->
                <div class="modal-body px-6 py-2">
                    <div class="input-group">
                        <label class="input-group-label" for="category-name">
                            Tên danh mục <span class="text-red-500">*</span>
                        </label>
                        <input class="form-input" id="category-name" type="text" placeholder="Nhập tên danh mục">
                        <div class="invalid-feedback hidden" id="category-name-error">Vui lòng nhập tên danh mục.</div>
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

    <!-- Edit Category Modal -->
    <div id="edit-category-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-50">
            <!-- Modal Header -->
            <div class="modal-header px-6 py-4">
                <div class="flex justify-between items-center">
                    <p class="text-xl font-bold">Cập nhật danh mục</p>
                    <div class="modal-close cursor-pointer z-50">
                        <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                            <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <form id="edit-category-form">
                <input type="hidden" id="edit-category-id">
                
                <!-- Modal Body -->
                <div class="modal-body px-6 py-2">
                    <div class="input-group">
                        <label class="input-group-label" for="edit-category-name">
                            Tên danh mục <span class="text-red-500">*</span>
                        </label>
                        <input class="form-input" id="edit-category-name" type="text" placeholder="Nhập tên danh mục">
                        <div class="invalid-feedback hidden" id="edit-category-name-error">Vui lòng nhập tên danh mục.</div>
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

    <!-- Toast notification for success -->
    <div id="success-toast" class="fixed bottom-0 right-0 mb-4 mr-4 bg-green-50 border-l-4 border-green-400 p-4 opacity-0 transition-opacity duration-500 ease-in-out transform translate-y-full" style="max-width: 24rem; z-index: 50;">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
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

    <!-- Edit Combo Modal -->
    <div id="edit-combo-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-3xl mx-auto rounded shadow-lg z-50">
            <!-- Modal Header -->
            <div class="modal-header px-6 py-4">
                <div class="flex justify-between items-center">
                    <p class="text-xl font-bold">Cập nhật combo</p>
                    <div class="modal-close cursor-pointer z-50">
                        <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                            <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <form id="edit-combo-form" class="space-y-4">
                <input type="hidden" id="edit-combo-id">
                
                <!-- Modal Body -->
                <div class="modal-body px-6 py-2">
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Thông tin chung combo -->
                        <div class=" p-4 rounded-lg">
                            <h3 class="text-base font-medium text-gray-900 mb-4">Thông tin combo</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="input-group">
                                    <label class="input-group-label" for="edit-combo-name">
                                        Tên combo <span class="text-red-500">*</span>
                                    </label>
                                    <input class="form-input" id="edit-combo-name" type="text" placeholder="Nhập tên combo">
                                    <div class="invalid-feedback hidden" id="edit-combo-name-error">Vui lòng nhập tên combo.</div>
                                </div>
                                
                                <div class="input-group">
                                    <label class="input-group-label" for="edit-combo-price">
                                        Giá bán <span class="text-red-500">*</span>
                                    </label>
                                    <div class="price-input-container">
                                        <span class="price-input-icon-left">₫</span>
                                        <input type="number" id="edit-combo-price" class="price-input" placeholder="0">
                                        <span class="price-input-icon-right">VND</span>
                                    </div>
                                    <div class="invalid-feedback hidden" id="edit-combo-price-error">Vui lòng nhập giá bán hợp lệ.</div>
                                </div>
                            </div>
                            
                            <div class="input-group mt-4">
                                <label class="input-group-label" for="edit-combo-image">
                                    Hình ảnh
                                </label>
                                <div class="image-preview mb-2">
                                    <img id="edit-combo-preview-image" class="w-full h-full object-contain">
                                </div>
                                <input type="file" id="edit-combo-image" class="hidden" accept="image/*">
                                <button type="button" id="edit-combo-select-image-btn" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    Thay đổi ảnh
                                </button>
                            </div>
                            
                            <div class="input-group mt-4">
                                <label class="input-group-label" for="edit-combo-description">
                                    Mô tả
                                </label>
                                <textarea class="form-input" id="edit-combo-description" rows="3" placeholder="Nhập mô tả combo"></textarea>
                            </div>
                        </div>
                        
                        <!-- Thêm sản phẩm vào combo -->
                        <div class=" p-4 rounded-lg">
                            <h3 class="text-base font-medium text-gray-900 mb-4">Thêm sản phẩm vào combo</h3>
                            
                            <div class="flex flex-col md:flex-row gap-4 mb-4">
                                <div class="flex-1">
                                    <label class="input-group-label" for="edit-combo-product-search">
                                        Tìm sản phẩm
                                    </label>
                                    <input type="text" id="edit-combo-product-search" class="form-input" placeholder="Nhập tên sản phẩm...">
                                </div>
                                <div class="w-full md:w-1/4">
                                    <label class="input-group-label" for="edit-combo-product-quantity">
                                        Số lượng
                                    </label>
                                    <input type="number" id="edit-combo-product-quantity" class="form-input" min="1" value="1">
                                </div>
                                <div class="md:self-end">
                                    <button type="button" id="btn-add-product-to-edit-combo" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 h-[42px]">
                                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Thêm sản phẩm
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Danh sách sản phẩm đã chọn -->
                            <div class="mt-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Sản phẩm đã chọn:</h4>
                                <div id="edit-combo-products-container" class="border border-gray-200 rounded-md overflow-hidden">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Sản phẩm
                                                </th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Số lượng
                                                </th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Thành tiền
                                                </th>
                                                <th scope="col" class="relative px-4 py-3 w-10">
                                                    <span class="sr-only">Xóa</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="edit-combo-products-list" class="bg-white divide-y divide-gray-200">
                                            <!-- Products will be added here dynamically -->
                                        </tbody>
                                        <tfoot class="bg-gray-50">
                                            <tr>
                                                <td colspan="2" class="px-4 py-2 text-right text-sm font-medium text-gray-900">
                                                    Tổng giá trị sản phẩm:
                                                </td>
                                                <td class="px-4 py-2 text-left text-sm text-gray-900">
                                                    <span id="edit-combo-total-value">0₫</span>
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <p id="edit-combo-empty-message" class="text-gray-500 text-sm mt-2 hidden">Chưa có sản phẩm nào được thêm vào combo.</p>
                                <div class="invalid-feedback hidden" id="edit-combo-products-error">Vui lòng thêm ít nhất một sản phẩm vào combo.</div>
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

    <!-- Add Combo Modal -->
    <div id="add-combo-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-3xl mx-auto rounded shadow-lg z-50">
            <!-- Modal Header -->
            <div class="modal-header px-6 py-4">
                <div class="flex justify-between items-center">
                    <p class="text-xl font-bold">Thêm combo mới</p>
                    <div class="modal-close cursor-pointer z-50">
                        <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                            <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <form id="add-combo-form" class="space-y-4">
                <!-- Modal Body -->
                <div class="modal-body px-6 py-2">
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Thông tin chung combo -->
                        <div class=" p-4 rounded-lg">
                            <h3 class="text-base font-medium text-gray-900 mb-4">Thông tin combo</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="input-group">
                                    <label class="input-group-label" for="combo-name">
                                        Tên combo <span class="text-red-500">*</span>
                                    </label>
                                    <input class="form-input" id="combo-name" type="text" placeholder="Nhập tên combo">
                                    <div class="invalid-feedback hidden" id="combo-name-error">Vui lòng nhập tên combo.</div>
                                </div>
                                
                                <div class="input-group">
                                    <label class="input-group-label" for="combo-price">
                                        Giá bán <span class="text-red-500">*</span>
                                    </label>
                                    <div class="price-input-container">
                                        <span class="price-input-icon-left">₫</span>
                                        <input type="number" id="combo-price" class="price-input" placeholder="0">
                                        <span class="price-input-icon-right">VND</span>
                                    </div>
                                    <div class="invalid-feedback hidden" id="combo-price-error">Vui lòng nhập giá bán hợp lệ.</div>
                                </div>
                            </div>
                            
                            <div class="input-group mt-4">
                                <label class="input-group-label" for="combo-image">
                                    Hình ảnh <span class="text-red-500">*</span>
                                </label>
                                <div class="image-preview mb-2">
                                    <div class="image-preview-placeholder">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>Chọn hoặc kéo thả hình ảnh vào đây</span>
                                    </div>
                                    <img id="combo-preview-image" class="hidden">
                                </div>
                                <input type="file" id="combo-image" class="hidden" accept="image/*">
                                <button type="button" id="combo-select-image-btn" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    Chọn ảnh
                                </button>
                                <div class="invalid-feedback hidden" id="combo-image-error">Vui lòng chọn hình ảnh cho combo.</div>
                            </div>
                            
                            <div class="input-group mt-4">
                                <label class="input-group-label" for="combo-description">
                                    Mô tả
                                </label>
                                <textarea class="form-input" id="combo-description" rows="3" placeholder="Nhập mô tả combo"></textarea>
                            </div>
                        </div>
                        
                        <!-- Thêm sản phẩm vào combo -->
                        <div class=" p-4 rounded-lg">
                            <h3 class="text-base font-medium text-gray-900 mb-4">Thêm sản phẩm vào combo</h3>
                            
                            <div class="flex flex-col md:flex-row gap-4 mb-4">
                                <div class="flex-1">
                                    <label class="input-group-label" for="combo-product-search">
                                        Tìm sản phẩm
                                    </label>
                                    <input type="text" id="combo-product-search" class="form-input" placeholder="Nhập tên sản phẩm...">
                                </div>
                                <div class="w-full md:w-1/4">
                                    <label class="input-group-label" for="combo-product-quantity">
                                        Số lượng
                                    </label>
                                    <input type="number" id="combo-product-quantity" class="form-input" min="1" value="1">
                                </div>
                                <div class="md:self-end">
                                    <button type="button" id="btn-add-product-to-combo" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 h-[42px]">
                                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Thêm sản phẩm
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Danh sách sản phẩm đã chọn -->
                            <div class="mt-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Sản phẩm đã chọn:</h4>
                                <div id="combo-products-container" class="border border-gray-200 rounded-md overflow-hidden">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Sản phẩm
                                                </th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Số lượng
                                                </th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Thành tiền
                                                </th>
                                                <th scope="col" class="relative px-4 py-3 w-10">
                                                    <span class="sr-only">Xóa</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="combo-products-list" class="bg-white divide-y divide-gray-200">
                                            <!-- Products will be added here dynamically -->
                                        </tbody>
                                        <tfoot class="bg-gray-50">
                                            <tr>
                                                <td colspan="2" class="px-4 py-2 text-right text-sm font-medium text-gray-900">
                                                    Tổng giá trị sản phẩm:
                                                </td>
                                                <td class="px-4 py-2 text-left text-sm text-gray-900">
                                                    <span id="combo-total-value">0₫</span>
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <p id="combo-empty-message" class="text-gray-500 text-sm mt-2">Chưa có sản phẩm nào được thêm vào combo.</p>
                                <div class="invalid-feedback hidden" id="combo-products-error">Vui lòng thêm ít nhất một sản phẩm vào combo.</div>
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
@endsection
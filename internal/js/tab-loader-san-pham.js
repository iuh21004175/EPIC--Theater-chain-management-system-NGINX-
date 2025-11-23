import Spinner from "./util/spinner.js";

document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for other scripts to load and export their functions
    setTimeout(() => {
        initializeTabLoader();
    }, 100);
});

function initializeTabLoader() {
    // Tab switching functionality
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all tabs
            tabBtns.forEach(b => {
                b.classList.remove('bg-red-600', 'text-white');
                b.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-200');
            });
            
            // Add active class to clicked tab
            this.classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-200');
            this.classList.add('bg-red-600', 'text-white');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Show corresponding tab content
            const tabId = this.id.replace('tab-btn-', 'tab-');
            document.getElementById(tabId).classList.add('active');
            
            // Load data for the active tab
            loadTabData(tabId);
        });
    });

    // Load data for each tab
    function loadTabData(tabId) {
        switch(tabId) {
            case 'tab-products':
                loadProducts();
                break;
            case 'tab-categories':
                loadCategories();
                break;
            case 'tab-combos':
                loadCombos();
                break;
        }
    }

    // Load products data
    function loadProducts() {
        const spinner = Spinner.show({text: 'Đang tải danh sách sản phẩm...'});
        const url = document.querySelector('#tab-products tbody').dataset.url + '/api/san-pham';
        
        fetch(url)
            .then(res => res.json())
            .then(data => {
                Spinner.hide(spinner);
                const tbody = document.querySelector('#tab-products tbody');
                tbody.innerHTML = '';
                if (Array.isArray(data.data) && data.data.length > 0) {
                    data.data.forEach(product => {
                        const tr = document.createElement('tr');
                        tr.className = 'product-item cursor-pointer hover:bg-gray-50';
                        tr.setAttribute('data-id', product.id);
                        const urlMinio = document.querySelector('#tab-products tbody').dataset.urlminio;
                        tr.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <img class="h-10 w-10 rounded-full object-cover" src="${urlMinio}/${product.hinh_anh}" alt="${product.ten}">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">${product.ten}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">${product.danh_muc ? product.danh_muc.ten : ''}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">${product.gia.toLocaleString('vi-VN')}₫</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500 max-w-xs truncate">${product.mo_ta || ''}</div>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-6 text-gray-400">Không có sản phẩm nào</td></tr>`;
                }
                // Re-attach click handlers
                attachProductClickHandlers();
            })
            .catch((e) => {
                Spinner.hide(spinner); 
                alert('Lỗi khi tải danh sách sản phẩm!');
            });
    }

    // Load categories data
    function loadCategories() {
        if (window.loadCategories) {
            window.loadCategories();
        } else {
            const spinner = Spinner.show({text: 'Đang tải danh sách danh mục...'});
            const categoryList = document.getElementById('category-list');
            
            if (categoryList && categoryList.dataset.url) {
                fetch(`${categoryList.dataset.url}/api/danh-muc`)
                    .then(res => res.json())
                    .then(data => {
                        Spinner.hide(spinner);
                        renderCategories(data.data || []);
                    })
                    .catch(error => {
                        Spinner.hide(spinner);
                        console.error('Error loading categories:', error);
                        alert('Có lỗi khi tải danh sách danh mục!');
                    });
            } else {
                Spinner.hide(spinner);
            }
        }
    }

    // Load combos data
    function loadCombos() {
        if (window.loadCombos) {
            window.loadCombos();
        } else {
            const spinner = Spinner.show({text: 'Đang tải danh sách combo...'});
            
            // In a real application, you would fetch from API
            // For demo purposes, we'll simulate loading
            setTimeout(() => {
                Spinner.hide(spinner);
                console.log('Combos loaded');
                // Here you would update the combos grid with fresh data
            }, 1000);
        }
    }

    // Render categories
    function renderCategories(categories = []) {
        if (window.renderCategories) {
            window.renderCategories(categories);
        } else {
            const categoryList = document.getElementById('category-list');
            if (!categoryList) return;
            
            categoryList.innerHTML = '';
            if (!categories || categories.length === 0) {
                categoryList.innerHTML = '<li class="px-4 py-4 text-gray-400">Chưa có danh mục nào</li>';
                return;
            }
            
            categories.forEach(cat => {
                const li = document.createElement('li');
                li.className = 'category-item cursor-pointer hover:bg-gray-50';
                li.setAttribute('data-id', cat.id);
                li.innerHTML = `
                    <a href="#" class="block">
                        <div class="px-4 py-4 sm:px-6 flex items-center">
                            <div class="w-full">
                                <p class="text-sm font-medium text-indigo-600">${cat.ten}</p>
                                <p class="mt-2 text-sm text-gray-500">Số sản phẩm: ${cat.so_sanpham ?? 0}</p>
                            </div>
                        </div>
                    </a>
                `;
                categoryList.appendChild(li);
            });
            
            // Re-attach click handlers for category items
            attachCategoryClickHandlers();
        }
    }

    // Attach click handlers for product items
    function attachProductClickHandlers() {
        const productItems = document.querySelectorAll('.product-item');
        productItems.forEach(item => {
            // Remove existing event listeners to avoid duplicates
            item.removeEventListener('click', handleProductClick);
            item.addEventListener('click', handleProductClick);
        });
    }

    // Separate function for product click handler
    function handleProductClick(event) {
        event.preventDefault(); // Ngăn reload trang khi click vào <a>
        event.stopPropagation(); // Ngăn event bubbling
        
        const productId = this.getAttribute('data-id');
        const productItems = document.querySelectorAll('.product-item');
        
        // Highlight selected row
        productItems.forEach(row => row.classList.remove('bg-gray-100'));
        this.classList.add('bg-gray-100');
        
        // Debug: Check if functions and modals exist
        console.log('window.loadProductData:', window.loadProductData);
        console.log('window.openModal:', window.openModal);
        console.log('window.modals:', window.modals);
        console.log('window.modals.editProduct:', window.modals ? window.modals.editProduct : 'modals is undefined');
        
        // Load product data and show edit modal
        if (window.loadProductData) {
            window.loadProductData(productId);
        } else {
            console.error('window.loadProductData is not available');
        }
        
        if (window.openModal && window.modals && window.modals.editProduct) {
            window.openModal(window.modals.editProduct);
        } else {
            console.warn('Cannot open modal - missing dependencies');
            // Fallback: try to get modal directly
            const editModal = document.getElementById('edit-product-modal');
            if (editModal && window.openModal) {
                console.log('Using fallback modal approach');
                window.openModal(editModal);
            } else {
                console.error('Modal not found or openModal not available');
            }
        }
    }

    // Attach click handlers for category items
    function attachCategoryClickHandlers() {
        if (window.attachCategoryClickHandlers) {
            window.attachCategoryClickHandlers();
        } else {
            const categoryItems = document.querySelectorAll('.category-item');
            categoryItems.forEach(item => {
                // Remove existing event listeners to avoid duplicates
                item.removeEventListener('click', handleCategoryClick);
                item.addEventListener('click', handleCategoryClick);
            });
        }
    }

    // Separate function for category click handler
    function handleCategoryClick(event) {
        event.preventDefault(); // Ngăn reload trang khi click vào <a>
        event.stopPropagation(); // Ngăn event bubbling
        
        const categoryId = this.getAttribute('data-id');
        const categoryItems = document.querySelectorAll('.category-item');
        
        // Highlight selected row
        categoryItems.forEach(row => row.classList.remove('bg-gray-100'));
        this.classList.add('bg-gray-100');
        
        // Load category data and show edit modal
        if (window.loadCategoryData) {
            window.loadCategoryData(categoryId);
        }
        if (window.openModal && window.modals && window.modals.editCategory) {
            window.openModal(window.modals.editCategory);
        }
    }

    // Load initial data when page loads
    loadTabData('tab-products');
}
import Spinner from "./util/spinner.js";

document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const addProductBtn = document.getElementById('btn-add-product');
    const addProductModal = document.getElementById('add-product-modal');
    const addProductForm = document.getElementById('add-product-form');
    const editProductModal = document.getElementById('edit-product-modal');
    const editProductForm = document.getElementById('edit-product-form');
    const productList = document.querySelector('#tab-products tbody');
    
    // Cache
    let productsCache = [];
    
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
            if (tabId === 'tab-products') {
                loadProducts();
            } else if (tabId === 'tab-categories') {
                if (window.loadCategories) {
                    window.loadCategories();
                }
            }
        });
    });
    
    // Load products data
    function loadProducts() {
        if (!productList) return;
        
        const spinner = Spinner.show({text: 'Đang tải danh sách sản phẩm...'});
        const url = productList.dataset.url + '/api/san-pham';
        
        fetch(url)
            .then(res => res.json())
            .then(data => {
                Spinner.hide(spinner);
                productList.innerHTML = '';
                
                if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                    const urlMinio = productList.dataset.urlminio;
                    productsCache = data.data;
                    
                    data.data.forEach(product => {
                        const tr = document.createElement('tr');
                        tr.className = 'product-item cursor-pointer hover:bg-gray-50';
                        tr.setAttribute('data-id', product.id);
                        tr.setAttribute('data-category-id', product.danh_muc_id || '');
                        
                        tr.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <img class="h-10 w-10 rounded-full object-cover" src="${urlMinio}/${product.hinh_anh}" alt="${product.ten}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'40\'%3E%3Crect width=\'40\' height=\'40\' fill=\'%23e5e7eb\'/%3E%3C/svg%3E'">
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
                                <div class="text-sm text-gray-500">${product.gia ? product.gia.toLocaleString('vi-VN') : '0'}₫</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500 max-w-xs truncate">${product.mo_ta || ''}</div>
                            </td>
                        `;
                        productList.appendChild(tr);
                    });
                    
                    // Attach click handlers
                    attachProductClickHandlers();
                } else {
                    productList.innerHTML = `<tr><td colspan="4" class="text-center py-6 text-gray-400">Không có sản phẩm nào</td></tr>`;
                }
            })
            .catch((e) => {
                Spinner.hide(spinner);
                console.error('Error loading products:', e);
                alert('Lỗi khi tải danh sách sản phẩm!');
            });
    }
    
    // Modal functions
    function openModal(modalElement) {
        document.body.classList.add('modal-active');
        modalElement.classList.remove('opacity-0', 'pointer-events-none');
    }
    
    function closeModal() {
        document.body.classList.remove('modal-active');
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.add('opacity-0', 'pointer-events-none');
        });
    }
    
    // Add product button click event
    if (addProductBtn) {
        addProductBtn.addEventListener('click', function() {
            loadCategoriesForSelect('product-category');
            if (addProductForm) addProductForm.reset();
            document.querySelectorAll('.invalid-feedback').forEach(errorMsg => errorMsg.classList.add('hidden'));
            const previewImage = document.getElementById('preview-image');
            if (previewImage) previewImage.classList.add('hidden');
            openModal(addProductModal);
        });
    }
    
    // Close buttons click events
    document.querySelectorAll('.modal-close-btn, .modal-close, .modal-overlay').forEach(closeBtn => {
        closeBtn.addEventListener('click', closeModal);
    });
    
    // Close modal when clicking outside the modal content
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    });
    
    // Prevent closing when clicking inside the modal content
    document.querySelectorAll('.modal-container').forEach(container => {
        container.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Load categories for select
    function loadCategoriesForSelect(selectId, selectedValue = null) {
        const categorySelect = document.getElementById(selectId);
        const categoryList = document.getElementById('category-list');
        if (!categorySelect || !categoryList) return Promise.resolve();
        
        const url = categoryList.dataset.url + '/api/danh-muc';
        return fetch(url)
            .then(res => res.json())
            .then(data => {
                categorySelect.innerHTML = '<option value="">Chọn danh mục</option>';
                if (data.success && Array.isArray(data.data)) {
                    data.data.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.ten;
                        if (selectedValue && String(cat.id) === String(selectedValue)) {
                            option.selected = true;
                        }
                        categorySelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading categories:', error);
            });
    }
    
    // Image preview handling
    const productImageInput = document.getElementById('product-image');
    const previewImage = document.getElementById('preview-image');
    
    if (productImageInput && previewImage) {
        productImageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.classList.remove('hidden');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Edit image preview handling
    const editProductImageInput = document.getElementById('edit-product-image');
    const editPreviewImage = document.getElementById('edit-preview-image');
    
    if (editProductImageInput && editPreviewImage) {
        editProductImageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    editPreviewImage.src = e.target.result;
                    editPreviewImage.classList.remove('hidden');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Add Product Form Submit
    if (addProductForm) {
        addProductForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('product-name').value.trim();
            const category = document.getElementById('product-category').value;
            const price = document.getElementById('product-price').value;
            const imageInput = document.getElementById('product-image');
            const description = document.getElementById('product-description').value.trim();
            
            // Validation
            let isValid = true;
            document.querySelectorAll('.invalid-feedback').forEach(errorMsg => errorMsg.classList.add('hidden'));
            
            if (!name) {
                document.getElementById('product-name-error').classList.remove('hidden');
                isValid = false;
            }
            
            if (!category) {
                document.getElementById('product-category-error').classList.remove('hidden');
                isValid = false;
            }
            
            if (!price || price <= 0) {
                document.getElementById('product-price-error').classList.remove('hidden');
                isValid = false;
            }
            
            if (!imageInput.files || imageInput.files.length === 0) {
                document.getElementById('product-image-error').classList.remove('hidden');
                isValid = false;
            }
            
            if (!isValid) return;
            
            const spinner = Spinner.show({text: 'Đang thêm sản phẩm...'});
            const formData = new FormData();
            formData.append('ten', name);
            formData.append('danh_muc_id', category);
            formData.append('gia', price);
            formData.append('mo_ta', description);
            formData.append('hinh_anh', imageInput.files[0]);
            
            fetch(productList.dataset.url + '/api/san-pham', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Spinner.hide(spinner);
                if (data.success) {
                    showToast('Thêm sản phẩm thành công!', false);
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Thêm sản phẩm thất bại!', true);
                }
            })
            .catch(() => {
                Spinner.hide(spinner);
                showToast('Có lỗi khi gửi dữ liệu!', true);
            });
        });
    }
    
    // Edit Product Form Submit
    if (editProductForm) {
        editProductForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const productId = document.getElementById('edit-product-id').value;
            const name = document.getElementById('edit-product-name').value.trim();
            const category = document.getElementById('edit-product-category').value;
            const price = document.getElementById('edit-product-price').value;
            const description = document.getElementById('edit-product-description').value.trim();
            const imageInput = document.getElementById('edit-product-image');
            
            // Validation
            let isValid = true;
            document.querySelectorAll('.invalid-feedback').forEach(errorMsg => errorMsg.classList.add('hidden'));
            
            if (!name) {
                document.getElementById('edit-product-name-error').classList.remove('hidden');
                isValid = false;
            }
            
            if (!category) {
                document.getElementById('edit-product-category-error').classList.remove('hidden');
                isValid = false;
            }
            
            if (!price || price <= 0) {
                document.getElementById('edit-product-price-error').classList.remove('hidden');
                isValid = false;
            }
            
            if (!isValid) return;
            
            const spinner = Spinner.show({text: 'Đang cập nhật sản phẩm...'});
            const formData = new FormData();
            formData.append('ten', name);
            formData.append('danh_muc_id', category);
            formData.append('gia', price);
            formData.append('mo_ta', description);
            
            if (imageInput.files && imageInput.files.length > 0) {
                formData.append('hinh_anh', imageInput.files[0]);
            }
            
            fetch(productList.dataset.url + `/api/san-pham/${productId}`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Spinner.hide(spinner);
                if (data.success) {
                    showToast('Cập nhật sản phẩm thành công!', false);
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Cập nhật sản phẩm thất bại!', true);
                }
            })
            .catch(() => {
                Spinner.hide(spinner);
                showToast('Có lỗi khi gửi dữ liệu!', true);
            });
        });
    }
    
    // Open edit modal when clicking product row
    function attachProductClickHandlers() {
        document.querySelectorAll('.product-item').forEach(item => {
            item.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                openEditProductModal(productId);
            });
        });
    }
    
    // Open edit product modal
    function openEditProductModal(productId) {
        const product = productsCache.find(p => p.id == productId);
        if (!product) {
            // Load from API if not in cache
            const spinner = Spinner.show({text: 'Đang tải thông tin sản phẩm...'});
            fetch(productList.dataset.url + '/api/san-pham/' + productId)
                .then(res => res.json())
                .then(data => {
                    Spinner.hide(spinner);
                    if (data.success && data.data) {
                        const product = Array.isArray(data.data) ? data.data[0] : data.data;
                        if (product) {
                            fillEditForm(product);
                            openModal(editProductModal);
                        } else {
                            showToast('Không tìm thấy thông tin sản phẩm!', true);
                        }
                    }
                })
                .catch(error => {
                    Spinner.hide(spinner);
                    showToast('Lỗi khi tải thông tin sản phẩm!', true);
                });
        } else {
            fillEditForm(product);
            openModal(editProductModal);
        }
    }
    
    // Fill edit form with product data
    function fillEditForm(product) {
        document.getElementById('edit-product-id').value = product.id || '';
        document.getElementById('edit-product-name').value = product.ten || '';
        document.getElementById('edit-product-price').value = product.gia ? String(product.gia) : '';
        document.getElementById('edit-product-description').value = product.mo_ta || '';
        
        // Load categories and set selected value
        const selectedCategoryId = product.danh_muc_id ? String(product.danh_muc_id) : null;
        loadCategoriesForSelect('edit-product-category', selectedCategoryId);
        
        // Load image
        const urlMinio = productList.dataset.urlminio;
        const editPreviewImage = document.getElementById('edit-preview-image');
        if (editPreviewImage) {
            if (product.hinh_anh) {
                editPreviewImage.src = `${urlMinio}/${product.hinh_anh}`;
                editPreviewImage.classList.remove('hidden');
            } else {
                editPreviewImage.classList.add('hidden');
            }
        }
        
        // Reset error messages
        document.querySelectorAll('.invalid-feedback').forEach(errorMsg => errorMsg.classList.add('hidden'));
    }
    
    // Search functionality
    const searchBtn = document.getElementById('btn-search-product');
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            const searchTerm = document.getElementById('search-product').value.toLowerCase();
            const categoryFilter = document.getElementById('filter-category').value;
            
            const productRows = document.querySelectorAll('.product-item');
            let hasVisibleRows = false;
            
            productRows.forEach(row => {
                const name = row.querySelector('td:first-child')?.textContent.toLowerCase() || '';
                const description = row.querySelector('td:last-child')?.textContent.toLowerCase() || '';
                const rowCategoryId = row.getAttribute('data-category-id') || '';
                
                const matchesSearch = searchTerm === '' || name.includes(searchTerm) || description.includes(searchTerm);
                const matchesCategory = categoryFilter === '' || rowCategoryId === categoryFilter;
                
                if (matchesSearch && matchesCategory) {
                    row.classList.remove('hidden');
                    hasVisibleRows = true;
                } else {
                    row.classList.add('hidden');
                }
            });
            
            const noResultsMessage = document.getElementById('no-results-products');
            if (noResultsMessage) {
                if (hasVisibleRows) {
                    noResultsMessage.classList.add('hidden');
                } else {
                    noResultsMessage.classList.remove('hidden');
                }
            }
        });
    }
    
    // Toast notification
    function showToast(message, isError = false) {
        const toast = document.getElementById('success-toast');
        const toastMessage = document.getElementById('toast-message');
        
        if (!toast || !toastMessage) return;
        
        toastMessage.textContent = message;
        if (isError) {
            toast.classList.remove('bg-green-50', 'border-green-400');
            toast.classList.add('bg-red-50', 'border-red-400');
            toastMessage.classList.remove('text-green-700');
            toastMessage.classList.add('text-red-700');
        } else {
            toast.classList.remove('bg-red-50', 'border-red-400');
            toast.classList.add('bg-green-50', 'border-green-400');
            toastMessage.classList.remove('text-red-700');
            toastMessage.classList.add('text-green-700');
        }
        
        toast.classList.remove('opacity-0', 'translate-y-full');
        toast.classList.add('opacity-100', 'translate-y-0');
        
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-full');
            toast.classList.remove('opacity-100', 'translate-y-0');
        }, 3000);
    }
    
    // Load initial products when page loads
    loadProducts();
});

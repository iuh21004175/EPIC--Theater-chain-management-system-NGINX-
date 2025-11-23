import Spinner from "./util/spinner.js";
document.addEventListener('DOMContentLoaded', function() {
    let productsList = [];
    // Modals
    const modals = {
        addProduct: document.getElementById('add-product-modal'),
        editProduct: document.getElementById('edit-product-modal')
    };
    
    // Open Add Product Modal
    document.getElementById('btn-add-product').addEventListener('click', function() {
        // Load categories for combobox
        const categorySelect = document.getElementById('product-category');
        const url = document.getElementById('category-list').dataset.url + '/api/danh-muc';
        fetch(url)
            .then(res => res.json())
            .then(data => {
                // Xóa các option cũ
                categorySelect.innerHTML = '<option value="">Chọn danh mục</option>';
                if (data.success && Array.isArray(data.data)) {
                    data.data.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.ten;
                        categorySelect.appendChild(option);
                    });
                }
            });
        openModal(modals.addProduct);
    });
    
    
    // Product rows click handler
    const productItems = document.querySelectorAll('.product-item');
    productItems.forEach(item => {
        item.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            // Highlight selected row
            productItems.forEach(row => row.classList.remove('bg-gray-100'));
            this.classList.add('bg-gray-100');
            
            // Load product data and show edit modal
            loadProductData(productId);
            openModal(modals.editProduct);
        });
    });
    
    
    // Add Product Form Submit
    document.getElementById('add-product-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const name = document.getElementById('product-name').value.trim();
        const category = document.getElementById('product-category').value;
        const price = document.getElementById('product-price').value;
        const imageInput = document.getElementById('product-image');
        const description = document.getElementById('product-description').value.trim();
        const hasImage = imageInput.files && imageInput.files.length > 0;
        let isValid = validateProductForm(name, category, price, hasImage);
        if (isValid) {
            const spinner = Spinner.show({text: 'Đang thêm sản phẩm...'});
            const formData = new FormData();
            formData.append('ten', name);
            formData.append('danh_muc_id', category);
            formData.append('gia', price);
            formData.append('mo_ta', description);
            if (hasImage) {
                formData.append('hinh_anh', imageInput.files[0]);
            }
            fetch(document.querySelector('#tab-products tbody').dataset.url + '/api/san-pham', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Spinner.hide(spinner);
                if (data.success) {
                    showSuccessToast('Thêm sản phẩm thành công!');
                    closeModal(modals.addProduct);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showSuccessToast(data.message || 'Thêm sản phẩm thất bại!');
                }
            })
            .catch(() => {
                Spinner.hide(spinner);
                showSuccessToast('Có lỗi khi gửi dữ liệu!');
            });
        }
    });
    
    // Edit Product Form Submit
    document.getElementById('edit-product-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const productId = document.getElementById('edit-product-id').value;
        const name = document.getElementById('edit-product-name').value.trim();
        const category = document.getElementById('edit-product-category').value;
        const price = document.getElementById('edit-product-price').value;
        const description = document.getElementById('edit-product-description').value.trim();

        let isValid = validateProductForm(name, category, price, true, 'edit-');
        if (!isValid) return;

        const spinner = Spinner.show({text: 'Đang cập nhật sản phẩm...'});
        fetch(document.querySelector('#tab-products tbody').dataset.url + `/api/san-pham/${productId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                ten: name,
                danh_muc_id: category,
                gia: price,
                mo_ta: description
            })
        })
        .then(res => res.json())
        .then(data => {
            Spinner.hide(spinner);
            if (data.success) {
                showSuccessToast('Cập nhật thông tin sản phẩm thành công!');
                closeModal(modals.editProduct);
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showSuccessToast(data.message || 'Cập nhật sản phẩm thất bại!');
            }
        })
        .catch(() => {
            Spinner.hide(spinner);
            showSuccessToast('Có lỗi khi gửi dữ liệu!');
        });
    });
    
    
    // Close modals with close buttons
    const closeButtons = document.querySelectorAll('.modal-close, .modal-close-btn');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });
    
    // Close modals when clicking on overlay
    const overlays = document.querySelectorAll('.modal-overlay');
    overlays.forEach(overlay => {
        overlay.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });
    
    // Image handling for add product form
    const selectImageBtn = document.getElementById('select-image-btn');
    const productImageInput = document.getElementById('product-image');
    const previewImage = document.getElementById('preview-image');
    const imagePreviewPlaceholder = document.querySelector('.image-preview-placeholder');
    
    selectImageBtn.addEventListener('click', function() {
        productImageInput.click();
    });
    
    productImageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewImage.classList.remove('hidden');
                imagePreviewPlaceholder.classList.add('hidden');
            };
            
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // Image handling for edit product form
    const editSelectImageBtn = document.getElementById('edit-select-image-btn');
    const editProductImageInput = document.getElementById('edit-product-image');
    const editPreviewImage = document.getElementById('edit-preview-image');
    
    editSelectImageBtn.addEventListener('click', function() {
        editProductImageInput.click();
    });
    
    editProductImageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                editPreviewImage.src = e.target.result;
            };
            
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    
    // Search product functionality
    document.getElementById('btn-search-product').addEventListener('click', function() {
        const searchTerm = document.getElementById('search-product').value.toLowerCase();
        const categoryFilter = document.getElementById('filter-category').value;
        
        const productRows = document.querySelectorAll('.product-item');
        let hasVisibleRows = false;
        
        productRows.forEach(row => {
            const name = row.querySelector('td:first-child').textContent.toLowerCase();
            const description = row.querySelector('td:last-child').textContent.toLowerCase();
            const rowCategoryId = getCategoryIdFromRow(row);
            
            const matchesSearch = searchTerm === '' || name.includes(searchTerm) || description.includes(searchTerm);
            const matchesCategory = categoryFilter === '' || rowCategoryId === categoryFilter;
            
            if (matchesSearch && matchesCategory) {
                row.classList.remove('hidden');
                hasVisibleRows = true;
            } else {
                row.classList.add('hidden');
            }
        });
        
        // Show/hide "No results" message
        const noResultsMessage = document.getElementById('no-results-products');
        if (hasVisibleRows) {
            noResultsMessage.classList.add('hidden');
            noResultsMessage.classList.remove('flex');
        } else {
            noResultsMessage.classList.remove('hidden');
            noResultsMessage.classList.add('flex');
        }
    });
    
    // Helper Functions
    function openModal(modal) {
        if (!modal) {
            console.error('Modal is undefined');
            return;
        }
        document.body.classList.add('modal-active');
        modal.classList.add('opacity-100');
        modal.classList.remove('opacity-0', 'pointer-events-none');
    }
    
    function closeModal(modal) {
        if (!modal) {
            console.error('Modal is undefined');
            return;
        }
        document.body.classList.remove('modal-active');
        modal.classList.add('opacity-0', 'pointer-events-none');
        modal.classList.remove('opacity-100');
    }
    
    function validateProductForm(name, category, price, hasImage, prefix = '') {
        let isValid = true;
        prefix = prefix || '';
        // Reset error messages
        document.querySelectorAll('.invalid-feedback').forEach(el => el.classList.add('hidden'));
        // Validate name (required)
        if (!name) {
            document.getElementById(`${prefix}product-name-error`).classList.remove('hidden');
            isValid = false;
        }
        // Validate category (required)
        if (!category) {
            document.getElementById(`${prefix}product-category-error`).classList.remove('hidden');
            isValid = false;
        }
        // Validate price (required and must be positive)
        if (!price || price <= 0) {
            document.getElementById(`${prefix}product-price-error`).classList.remove('hidden');
            isValid = false;
        }
        // Validate image for new products
        if (prefix === '') {
            const imageInput = document.getElementById('product-image');
            if (!imageInput.files || imageInput.files.length === 0) {
                document.getElementById('product-image-error').classList.remove('hidden');
                isValid = false;
            }
        }
        return isValid;
    }
    
    
    function loadProductData(productId) {
        console.log('Loading product data for ID:', productId);
        // Load product data from API
        const spinner = Spinner.show({text: 'Đang tải thông tin sản phẩm...'});
        const url = document.querySelector('#tab-products tbody').dataset.url + '/api/san-pham/' + productId;
        console.log('API URL:', url);
        
        fetch(url)
            .then(res => res.json())
            .then(data => {
                Spinner.hide(spinner);
                if (data.success && data.data) {
                    const product = data.data;
                    document.getElementById('edit-product-id').value = product.id;
                    document.getElementById('edit-product-name').value = product.ten;
                    document.getElementById('edit-product-price').value = product.gia;
                    document.getElementById('edit-product-description').value = product.mo_ta || '';
                    // Load current image
                    const urlMinio = document.querySelector('#tab-products tbody').dataset.urlminio;
                    if (product.hinh_anh) {
                        document.getElementById('edit-preview-image').src = `${urlMinio}/${product.hinh_anh}`;
                    }
                    // --- Bổ sung load danh mục ---
                    const categorySelect = document.getElementById('edit-product-category');
                    const categoryUrl = document.getElementById('category-list').dataset.url + '/api/danh-muc';
                    fetch(categoryUrl)
                        .then(res => res.json())
                        .then(catData => {
                            categorySelect.innerHTML = '<option value="">Chọn danh mục</option>';
                            if (catData.success && Array.isArray(catData.data)) {
                                catData.data.forEach(cat => {
                                    const option = document.createElement('option');
                                    option.value = cat.id;
                                    option.textContent = cat.ten;
                                    categorySelect.appendChild(option);
                                });
                            }
                            // Set đúng danh mục cho sản phẩm
                            categorySelect.value = product.danh_muc_id;
                        });
                    // --- Kết thúc bổ sung ---
                }
            })
            .catch((error) => {
                console.error('Error loading product data:', error);
                Spinner.hide(spinner);
                alert('Lỗi khi tải thông tin sản phẩm!');
            });
    }


    function showSuccessToast(message) {
        const toast = document.getElementById('success-toast');
        const toastMessage = document.getElementById('toast-message');
        
        toastMessage.textContent = message;
        toast.classList.remove('opacity-0', 'translate-y-full');
        toast.classList.add('opacity-100', 'translate-y-0');
        
        setTimeout(() => {
            hideToast();
        }, 3000);
    }

    function hideToast() {
        const toast = document.getElementById('success-toast');
        toast.classList.add('opacity-0', 'translate-y-full');
        toast.classList.remove('opacity-100', 'translate-y-0');
    }


    // Export functions for use in tab-loader.js
    window.loadProductData = loadProductData;
    window.openModal = openModal;
    window.modals = {
        addProduct: document.getElementById('add-product-modal'),
        editProduct: document.getElementById('edit-product-modal')
    };

});
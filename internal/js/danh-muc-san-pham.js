import Spinner from "./util/spinner.js";
const categoryList = document.getElementById('category-list');

document.addEventListener('DOMContentLoaded', function() {
    const modals = {
        addCategory: document.getElementById('add-category-modal'),
        editCategory: document.getElementById('edit-category-modal'),
    };
    function closeModal(modal) {
        if (!modal) {
            console.error('Modal is undefined');
            return;
        }
        document.body.classList.remove('modal-active');
        modal.classList.add('opacity-0', 'pointer-events-none');
        modal.classList.remove('opacity-100');
    }
    function openModal(modal) {
        if (!modal) {
            console.error('Modal is undefined');
            return;
        }
        document.body.classList.add('modal-active');
        modal.classList.add('opacity-100');
        modal.classList.remove('opacity-0', 'pointer-events-none');
    }
    // Load danh mục khi vào trang
    function fetchCategories() {
        const spinner = Spinner.show({text: 'Đang tải danh mục...'});
        fetch(`${categoryList.dataset.url}/api/danh-muc`)
            .then(res => res.json())
            .then(data => {
                Spinner.hide(spinner);
                renderCategories(data.data);
            })
            .catch(() => {
                Spinner.hide(spinner);
                showToast('Có lỗi khi tải danh sách danh mục!', 'error');
            });
    }

    function renderCategories(categories=[]) {
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
        
        // Attach click handlers for category items
        attachCategoryClickHandlers();
    }

    // Attach click handlers for category items
    function attachCategoryClickHandlers() {
        const categoryItems = document.querySelectorAll('.category-item');
        categoryItems.forEach(item => {
            item.addEventListener('click', function(event) {
                event.preventDefault(); // Ngăn reload trang khi click vào <a>
                const categoryId = this.getAttribute('data-id');
                // Highlight selected row
                categoryItems.forEach(row => row.classList.remove('bg-gray-100'));
                this.classList.add('bg-gray-100');
                
                // Load category data and show edit modal
                loadCategoryData(categoryId);
                openModal(modals.editCategory);
            });
        });
    }

    // Load category data for editing
    function loadCategoryData(categoryId) {
        document.getElementById('edit-category-id').value = categoryId;
        // Lấy tên danh mục từ phần tử vừa click
        const item = document.querySelector(`.category-item[data-id='${categoryId}']`);
        if (item) {
            const ten = item.querySelector('.text-indigo-600').textContent;
            document.getElementById('edit-category-name').value = ten;
        } else {
            document.getElementById('edit-category-name').value = '';
        }
    }

    function showToast(message, type = 'success') {
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'fixed bottom-4 right-4 z-50';
            document.body.appendChild(toastContainer);
        }
        const bgColor = type === 'success' ? 'bg-green-500' : (type === 'error' ? 'bg-red-500' : 'bg-yellow-500');
        const toast = document.createElement('div');
        toast.className = `p-4 mb-3 rounded-md shadow-md text-white ${bgColor}`;
        toast.innerHTML = `<span>${message}</span>`;
        toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    // Thêm danh mục mới
    const addCategoryForm = document.getElementById('add-category-form');
    if (addCategoryForm) {
        addCategoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const nameInput = document.getElementById('category-name');
            const nameError = document.getElementById('category-name-error');
            const ten = nameInput.value.trim();
            if (!ten) {
                nameError.classList.remove('hidden');
                showToast('Vui lòng nhập tên danh mục.', 'error');
                return;
            } else {
                nameError.classList.add('hidden');
            }
            const spinner = Spinner.show({text: 'Đang thêm danh mục...'});
            const formData = new FormData();
            formData.append('ten', ten);
            fetch(`${categoryList.dataset.url}/api/danh-muc`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Spinner.hide(spinner);
                if (data.success) {
                    fetchCategories();
                    nameInput.value = '';
                    showToast('Thêm danh mục thành công!', 'success');
                    // Đóng modal nếu cần
                    closeModal(modals.addCategory);
                } else {
                    nameError.textContent = data.message || 'Thêm danh mục thất bại';
                    nameError.classList.remove('hidden');
                    showToast(data.message || 'Thêm danh mục thất bại', 'error');
                }
            })
            .catch(() => {
                Spinner.hide(spinner);
                nameError.textContent = 'Có lỗi khi gửi dữ liệu';
                nameError.classList.remove('hidden');
                showToast('Có lỗi khi gửi dữ liệu', 'error');
            });
        });
    }

    // Sửa danh mục
    const editCategoryForm = document.getElementById('edit-category-form');
    if (editCategoryForm) {
        editCategoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('edit-category-id').value;
            const nameInput = document.getElementById('edit-category-name');
            const nameError = document.getElementById('edit-category-name-error');
            const ten = nameInput.value.trim();
            if (!ten) {
                nameError.classList.remove('hidden');
                showToast('Vui lòng nhập tên danh mục.', 'error');
                return;
            } else {
                nameError.classList.add('hidden');
            }
            const spinner = Spinner.show({text: 'Đang cập nhật danh mục...'});
            const formData = new FormData();
            const payload = { ten };
            fetch(`${categoryList.dataset.url}/api/danh-muc/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                Spinner.hide(spinner);
                if (data.success) {
                    fetchCategories();
                    showToast('Cập nhật danh mục thành công!', 'success');
                    closeModal(modals.editCategory);
                } else {
                    nameError.textContent = data.message || 'Cập nhật danh mục thất bại';
                    nameError.classList.remove('hidden');
                    showToast(data.message || 'Cập nhật danh mục thất bại', 'error');
                }
            })
            .catch(() => {
                Spinner.hide(spinner);
                nameError.textContent = 'Có lỗi khi gửi dữ liệu';
                nameError.classList.remove('hidden');
                showToast('Có lỗi khi gửi dữ liệu', 'error');
            });
        });
    }

    // Thêm event listener cho nút "Thêm danh mục mới"
    const btnAddCategory = document.getElementById('btn-add-category');
    if (btnAddCategory) {
        btnAddCategory.addEventListener('click', function() {
            // Reset form
            const addCategoryForm = document.getElementById('add-category-form');
            if (addCategoryForm) {
                addCategoryForm.reset();
            }
            // Ẩn các thông báo lỗi
            const nameError = document.getElementById('category-name-error');
            if (nameError) {
                nameError.classList.add('hidden');
            }
            // Mở modal
            openModal(modals.addCategory);
        });
    }

    // Close modal buttons
    document.querySelectorAll('.modal-close-btn, .modal-close, .modal-overlay').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            closeModal(modals.addCategory);
            closeModal(modals.editCategory);
        });
    });

    // fetchCategories(); // Removed - now handled by tab switching
    
    // Export functions for use in tab-loader.js
    window.loadCategories = fetchCategories;
    window.renderCategories = renderCategories;
    window.attachCategoryClickHandlers = attachCategoryClickHandlers;
    window.loadCategoryData = loadCategoryData;
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.modals = modals;
});

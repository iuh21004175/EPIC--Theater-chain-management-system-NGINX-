import Spinner from "./util/spinner.js";

document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const seatTypesList = document.getElementById('seat-types-list');
    const btnAddSeatType = document.getElementById('btn-add-seat-type');
    const modalAddSeatType = document.getElementById('modal-add-seat-type');
    const modalEditSeatType = document.getElementById('modal-edit-seat-type');
    // Thêm 2 dòng này để định nghĩa các form trước khi gọi openAddModal / openEditModal
    const formAddSeatType = document.getElementById('form-add-seat-type');
    const formEditSeatType = document.getElementById('form-edit-seat-type');
    const btnSubmitAdd = document.getElementById('btn-submit-add');
    const btnSubmitEdit = document.getElementById('btn-submit-edit');
    const cancelButtons = document.querySelectorAll('.btn-cancel');
    const toast = document.getElementById('toast-notification');

    // Store seat types data
    let seatTypesData = [];

    // Load seat types list
    function loadSeatTypes() {
        // Show loading message
        seatTypesList.innerHTML = `
            <li class="px-6 py-4 flex items-center">
                <div class="w-full text-center text-gray-500">Đang tải dữ liệu...</div>
            </li>
        `;
        
        // Call API to get seat types
        fetch(`${seatTypesList.dataset.url}/api/ghe`, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Map API data to UI format
                seatTypesData = data.data.map(item => ({
                    id: item.id,
                    name: item.ten,
                    description: item.mo_ta,
                    color: item.ma_mau,
                    // phu_thu removed
                }));
                renderSeatTypes(seatTypesData);
            } else {
                // Show error message
                seatTypesList.innerHTML = `
                    <li class="px-6 py-4 flex items-center">
                        <div class="w-full text-center text-red-500">Lỗi khi tải dữ liệu: ${data.message || 'Không xác định'}</div>
                    </li>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            seatTypesList.innerHTML = `
                <li class="px-6 py-4 flex items-center">
                    <div class="w-full text-center text-red-500">Đã xảy ra lỗi khi tải dữ liệu</div>
                </li>
            `;
        });
    }

    // Render seat types list
    function renderSeatTypes(seatTypes) {
        if (!seatTypes || seatTypes.length === 0) {
            seatTypesList.innerHTML = `
                <li class="px-6 py-4 flex items-center">
                    <div class="w-full text-center text-gray-500">Chưa có loại ghế nào</div>
                </li>
            `;
            return;
        }

        seatTypesList.innerHTML = '';
        seatTypes.forEach(seatType => {
            const listItem = document.createElement('li');
            listItem.className = 'px-6 py-4 flex items-center justify-between hover:bg-gray-50';
            listItem.innerHTML = `
                <div class="flex items-center">
                    <span class="seat-type-icon" style="background-color: ${seatType.color}"></span>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">${seatType.name}</h3>
                        <p class="text-sm text-gray-500">${seatType.description || 'Không có mô tả'}</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button type="button" class="btn-edit inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" data-id="${seatType.id}">
                        <svg class="-ml-1 mr-1 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                        Sửa
                    </button>
                </div>
            `;
            seatTypesList.appendChild(listItem);
        });

        // Add event listeners to edit buttons
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', function() {
                const seatTypeId = parseInt(this.getAttribute('data-id'));
                openEditModal(seatTypeId);
            });
        });
    }

    // Show toast notification
    function showToast(message, isError = false) {
        toast.textContent = message;
        toast.classList.remove('translate-y-20', 'opacity-0', 'bg-green-500', 'bg-red-500');
        toast.classList.add(isError ? 'bg-red-500' : 'bg-green-500');
        
        // Show the toast
        setTimeout(() => {
            toast.classList.remove('translate-y-20', 'opacity-0');
        }, 10);
        
        // Hide the toast after 3 seconds
        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0');
        }, 3000);
    }

    // Validate form
    function validateForm(formData, isEdit = false) {
        let isValid = true;
        const errors = {};
        
        // Validate name
        if (!formData.name || formData.name.trim() === '') {
            errors.name = 'Tên loại ghế không được để trống';
            isValid = false;
        }
        
        // No price validation (field removed)
        
        // Show errors if any
        const prefix = isEdit ? 'edit-' : '';
        Object.keys(errors).forEach(field => {
            const errorElement = document.getElementById(`${prefix}${field}-error`);
            if (errorElement) {
                errorElement.textContent = errors[field];
                errorElement.classList.remove('hidden');
            }
        });
        
        // Clear previous error messages for valid fields
        ['name', 'description'].forEach(field => {
            if (!errors[field]) {
                const errorElement = document.getElementById(`${prefix}${field}-error`);
                if (errorElement) {
                    errorElement.textContent = '';
                    errorElement.classList.add('hidden');
                }
            }
        });
        
        return isValid;
    }

    // Open Add Modal
    function openAddModal() {
        // Reset form
        formAddSeatType.reset();
        document.getElementById('seat-type-color').value = '#EF4444';
        
        // Clear error messages
        document.querySelectorAll('#form-add-seat-type .text-red-600').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });
        
        // Show modal
        modalAddSeatType.classList.remove('hidden');
    }

    // Open Edit Modal
    function openEditModal(seatTypeId) {
        // Get seat type data from our stored array
        const seatType = seatTypesData.find(st => st.id === seatTypeId);
        if (!seatType) {
            showToast('Không tìm thấy thông tin loại ghế', true);
            return;
        }
        
        // Populate form
        document.getElementById('edit-seat-type-id').value = seatType.id;
        document.getElementById('edit-seat-type-name').value = seatType.name;
        document.getElementById('edit-seat-type-description').value = seatType.description || '';
        document.getElementById('edit-seat-type-color').value = seatType.color;
        
        // Clear error messages
        document.querySelectorAll('#form-edit-seat-type .text-red-600').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });
        
        // Show modal
        modalEditSeatType.classList.remove('hidden');
    }

    // Close modals
    function closeModals() {
        modalAddSeatType.classList.add('hidden');
        modalEditSeatType.classList.add('hidden');
    }

    // Add new seat type
    function addSeatType() {
        const formData = new FormData();
        formData.append('ten', document.getElementById('seat-type-name').value.trim());
        formData.append('ma_mau', document.getElementById('seat-type-color').value);
        
        // Also add the description if it exists
        const description = document.getElementById('seat-type-description').value.trim();
        if (description) {
            formData.append('mo_ta', description);
        }
        
        // Validate form
        if (!validateForm({
            name: document.getElementById('seat-type-name').value.trim(),
            // no price
        })) {
            return;
        }
        
        // Show loading spinner
        const spinner = Spinner.show({
            text: 'Đang thêm loại ghế...',
            color: '#E11D48',
            overlay: true
        });
        
        // Call API to add new seat type
        fetch(`${seatTypesList.dataset.url}/api/ghe`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Hide spinner
            Spinner.hide(spinner);
            
            if (data.success) {
                // Close modal
                closeModals();
                
                // Show success message
                showToast('Thêm loại ghế mới thành công');
                
                // Refresh the list
                loadSeatTypes();
            } else {
                // Show error message
                showToast(data.message || 'Thêm loại ghế thất bại', true);
            }
        })
        .catch(error => {
            // Hide spinner
            Spinner.hide(spinner);
            
            // Show error message
            console.error('Error:', error);
            showToast('Đã xảy ra lỗi khi thêm loại ghế', true);
        });
    }

    // Update seat type
    function updateSeatType() {
        const seatTypeId = parseInt(document.getElementById('edit-seat-type-id').value);
        
        // Tạo JSON data thay vì FormData vì API đang đọc từ php://input
        const data = {
            ten: document.getElementById('edit-seat-type-name').value.trim(),
            ma_mau: document.getElementById('edit-seat-type-color').value,
            mo_ta: document.getElementById('edit-seat-type-description').value.trim() || null
        };
        
        // Validate form
        if (!validateForm({
            name: document.getElementById('edit-seat-type-name').value.trim(),
            // no price
        }, true)) {
            return;
        }
        
        // Show loading spinner
        const spinner = Spinner.show({
            text: 'Đang cập nhật loại ghế...',
            color: '#E11D48',
            overlay: true
        });
        
        // Call API to update seat type - Sửa URL từ /api/ghe sang /api/v1/ghe
        fetch(`${seatTypesList.dataset.url}/api/ghe/${seatTypeId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            // Hide spinner
            Spinner.hide(spinner);
            
            if (data.success) {
                // Close modal
                closeModals();
                
                // Show success message
                showToast('Cập nhật loại ghế thành công');
                
                // Refresh the list
                loadSeatTypes();
            } else {
                // Show error message
                showToast(data.message || 'Cập nhật loại ghế thất bại', true);
            }
        })
        .catch(error => {
            // Hide spinner
            Spinner.hide(spinner);
            
            // Show error message
            console.error('Error:', error);
            showToast('Đã xảy ra lỗi khi cập nhật loại ghế', true);
        });
    }

    // Event Listeners
    btnAddSeatType.addEventListener('click', openAddModal);
    
    cancelButtons.forEach(button => {
        button.addEventListener('click', closeModals);
    });
    
    btnSubmitAdd.addEventListener('click', addSeatType);
    
    btnSubmitEdit.addEventListener('click', updateSeatType);
    
    // When clicking outside the modal content, close the modal
    window.addEventListener('click', function(event) {
        if (event.target === modalAddSeatType || event.target === modalEditSeatType) {
            closeModals();
        }
    });

    // Initialize
    loadSeatTypes();
});
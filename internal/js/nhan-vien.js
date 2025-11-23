import Spinner from './util/spinner.js';

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const addEmployeeBtn = document.getElementById('add-employee-btn');
    const employeeModal = document.getElementById('employee-modal');
    const closeModalBtn = document.getElementById('close-modal');
    const employeeForm = document.getElementById('employee-form');
    const employeeIdInput = document.getElementById('employee-id');
    const modalTitle = document.getElementById('modal-title');
    const employeeNameInput = document.getElementById('employee-name');
    const employeePhoneInput = document.getElementById('employee-phone');
    const employeeEmailInput = document.getElementById('employee-email');
    const employeeUsernameInput = document.getElementById('employee-username');
    const employeePasswordInput = document.getElementById('employee-password');
    const passwordContainer = document.getElementById('password-container');
    const saveEmployeeBtn = document.getElementById('save-employee');
    const cancelEmployeeBtn = document.getElementById('cancel-employee');
    const statusToggleBtn = document.getElementById('status-toggle');
    const employeeList = document.getElementById('employee-list');
    const noEmployees = document.getElementById('no-employees');
    const statusModal = document.getElementById('status-modal');
    const statusModalTitle = document.getElementById('status-modal-title');
    const statusMessage = document.getElementById('status-message');
    const cancelStatusChangeBtn = document.getElementById('cancel-status-change');
    const confirmStatusChangeBtn = document.getElementById('confirm-status-change');
    const toastNotification = document.getElementById('toast-notification');
    const statusFilter = document.getElementById('status-filter');
    const searchInput = document.getElementById('search');
    
    // Error message elements
    const nameError = document.getElementById('name-error');
    const phoneError = document.getElementById('phone-error');
    const emailError = document.getElementById('email-error');
    const usernameError = document.getElementById('username-error');
    const passwordError = document.getElementById('password-error');
    
    // Sample data for testing (would be fetched from server in production)
    let employees = [];
    
    // Variables for status change
    let changingEmployeeId = null;
    let newStatus = null;
    
    // Add these variables at the top of your DOMContentLoaded function
    let currentPage = 1;
    let totalPages = 1;
    let perPage = 10;
    
    // Get pagination elements
    const prevPageBtn = document.querySelector('[aria-label="Pagination"] button:first-child');
    const nextPageBtn = document.querySelector('[aria-label="Pagination"] button:last-child');
    const paginationInfo = document.querySelector('.text-sm.text-gray-700');
    const paginationButtons = document.querySelector('nav[aria-label="Pagination"]');
    
    // Load initial data
    loadEmployees();
    
    // Event listeners
    addEmployeeBtn.addEventListener('click', openAddModal);
    closeModalBtn.addEventListener('click', closeModal);
    cancelEmployeeBtn.addEventListener('click', closeModal);
    saveEmployeeBtn.addEventListener('click', handleSaveEmployee);
    statusToggleBtn.addEventListener('click', openStatusModal);
    cancelStatusChangeBtn.addEventListener('click', closeStatusModal);
    confirmStatusChangeBtn.addEventListener('click', changeEmployeeStatus);
    
    // Filters
    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }
    
    // Phone number validation
    employeePhoneInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    // Functions
    
    function loadEmployees() {
        // Show spinner while loading
        const container = employeeList.closest('.overflow-x-auto');
        const spinner = Spinner.show({
            target: container,
            text: 'Đang tải danh sách nhân viên...'
        });
        
        // Get filter values
        const status = statusFilter?.value || 'all';
        const search = searchInput?.value || '';
        
        // Fetch from API with pagination parameters
        fetch(`${employeeList.dataset.url}/api/nhan-vien?page=${currentPage}&per_page=${perPage}&status=${status}&search=${encodeURIComponent(search)}`)
            .then(response => response.json())
            .then(data => {
                // Hide spinner
                Spinner.hide(spinner);
                
                // Debug information
                // console.log("API Response:", data);
                
                if (data.success && data.data) {
                    // Map API data to our employee format
                    employees = data.data.map(item => ({
                        id: item.id,
                        name: item.ten,
                        phone: item.dien_thoai,
                        email: item.email,
                        username: item.tai_khoan?.tendangnhap || 'N/A',
                        status: item.trang_thai === 1 ? 'active' : 'inactive'
                    }));
                    
                    // console.log("Employee count:", employees.length);
                    // console.log("Pagination data:", data.pagination);
                    
                    // Update pagination information
                    if (data.pagination) {
                        currentPage = data.pagination.current_page;
                        totalPages = data.pagination.total_pages;
                        perPage = data.pagination.per_page;
                        updatePaginationUI(data.pagination);
                    }
                    
                    // Render employees
                    renderEmployees();
                } else {
                    showToast('Không thể tải danh sách nhân viên', true);
                    noEmployees.classList.remove('hidden');
                }
            })
            .catch(error => {
                // Hide spinner
                Spinner.hide(spinner);
                
                // Show error message
                showToast('Lỗi kết nối: ' + error.message, true);
                console.error('Error:', error);
                noEmployees.classList.remove('hidden');
            });
    }

    // Separate render function for cleaner code
    function renderEmployees() {
        if (employees.length === 0) {
            noEmployees.classList.remove('hidden');
            return;
        }
        
        noEmployees.classList.add('hidden');
        
        // Clear existing content
        employeeList.innerHTML = '';
        
        // Populate employee list
        employees.forEach(employee => {
            const row = createEmployeeTableRow(employee);
            employeeList.appendChild(row);
        });
    }
    
    function filterEmployees() {
        let filtered = [...employees];
        
        // Apply status filter
        if (statusFilter && statusFilter.value !== 'all') {
            filtered = filtered.filter(employee => employee.status === statusFilter.value);
        }
        
        // Apply search filter
        if (searchInput) {
            const searchTerm = searchInput.value.toLowerCase().trim();
            if (searchTerm) {
                filtered = filtered.filter(employee => 
                    employee.name.toLowerCase().includes(searchTerm) ||
                    employee.email.toLowerCase().includes(searchTerm) ||
                    employee.phone.includes(searchTerm) ||
                    employee.username.toLowerCase().includes(searchTerm)
                );
            }
        }
        
        return filtered;
    }
    
    function applyFilters() {
        // Reset to page 1 when filters change
        currentPage = 1;
        loadEmployees();
    }
    
    function createEmployeeTableRow(employee) {
    const row = document.createElement('tr');
    
    // Add cursor-pointer and hover effect to indicate clickable row
    row.classList.add('cursor-pointer', 'hover:bg-gray-50', 'transition', 'duration-150');
    
    // Create status badge
    const statusBadge = document.createElement('span');
    statusBadge.classList.add('px-2', 'inline-flex', 'text-xs', 'leading-5', 'font-semibold', 'rounded-full');
    
    if (employee.status === 'active') {
        statusBadge.classList.add('bg-green-100', 'text-green-800');
        statusBadge.textContent = 'Đang làm việc';
    } else {
        statusBadge.classList.add('bg-red-100', 'text-red-800');
        statusBadge.textContent = 'Đã nghỉ việc';
    }
    
    row.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                    <span class="text-lg font-medium text-gray-600">${employee.name.charAt(0)}</span>
                </div>
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">${employee.name}</div>
                </div>
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900">${employee.phone}</div>
            <div class="text-sm text-gray-500">${employee.email}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900">${employee.username}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            ${statusBadge.outerHTML}
        </td>
        <!-- Removed "Thao tác" cell with edit button -->
    `;
    
    // Add event listener to the entire row
    row.addEventListener('click', () => openEditModal(employee.id));
    
    return row;
}
    
    function openAddModal() {
        // Reset form
        employeeForm.reset();
        employeeIdInput.value = '';
        
        // Update modal title and button text
        modalTitle.textContent = 'Thêm nhân viên mới';
        saveEmployeeBtn.textContent = 'Thêm';
        
        // Show password field
        passwordContainer.classList.remove('hidden');
        
        // Hide status toggle button
        statusToggleBtn.classList.add('hidden');
        
        // Clear validation errors
        clearValidationErrors();
        
        // Show modal
        employeeModal.classList.remove('hidden');
    }
    
    function openEditModal(employeeId) {
        // Find employee by ID
        const employee = employees.find(e => e.id === employeeId);
        if (!employee) return;
        
        // Reset form and fill with employee data
        employeeForm.reset();
        employeeIdInput.value = employee.id;
        employeeNameInput.value = employee.name;
        employeePhoneInput.value = employee.phone;
        employeeEmailInput.value = employee.email;
        employeeUsernameInput.value = employee.username;
        
        // Hide password field for editing
        passwordContainer.classList.add('hidden');
        
        // Update modal title and button text
        modalTitle.textContent = 'Cập nhật thông tin nhân viên';
        saveEmployeeBtn.textContent = 'Lưu';
        
        // Update status toggle button - phần này được sửa
        statusToggleBtn.classList.remove('hidden');
        
        // -1 là nghỉ việc, 1 là đang hoạt động
        if (employee.status === 'active') {
            statusToggleBtn.textContent = 'Nghỉ việc';
            statusToggleBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
            statusToggleBtn.classList.add('bg-red-600', 'hover:bg-red-700');
        } else {
            statusToggleBtn.textContent = 'Đang hoạt động';
            statusToggleBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
            statusToggleBtn.classList.add('bg-green-600', 'hover:bg-green-700');
        }
        
        // Clear validation errors
        clearValidationErrors();
        
        // Show modal
        employeeModal.classList.remove('hidden');
    }
    
    function closeModal() {
        employeeModal.classList.add('hidden');
    }
    
    function openStatusModal() {
        const employeeId = parseInt(employeeIdInput.value);
        const employee = employees.find(e => e.id === employeeId);
        if (!employee) return;
        
        // Set status change info
        changingEmployeeId = employeeId;
        
        if (employee.status === 'active') {
            // Đang hoạt động -> chuyển sang nghỉ việc
            statusModalTitle.textContent = 'Xác nhận nghỉ việc';
            statusMessage.textContent = `Bạn có chắc chắn muốn chuyển nhân viên "${employee.name}" sang trạng thái nghỉ việc không?`;
            confirmStatusChangeBtn.textContent = 'Nghỉ việc';
            confirmStatusChangeBtn.classList.add('bg-red-600', 'hover:bg-red-700');
            confirmStatusChangeBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
            newStatus = 'inactive';
        } else {
            // Nghỉ việc -> chuyển sang đang hoạt động
            statusModalTitle.textContent = 'Xác nhận kích hoạt';
            statusMessage.textContent = `Bạn có chắc chắn muốn chuyển nhân viên "${employee.name}" sang trạng thái đang hoạt động không?`;
            confirmStatusChangeBtn.textContent = 'Kích hoạt';
            confirmStatusChangeBtn.classList.add('bg-green-600', 'hover:bg-green-700');
            confirmStatusChangeBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
            newStatus = 'active';
        }
        
        // Hide employee modal and show status modal
        employeeModal.classList.add('hidden');
        statusModal.classList.remove('hidden');
    }
    
    function closeStatusModal() {
        statusModal.classList.add('hidden');
        // Show employee modal again
        employeeModal.classList.remove('hidden');
        changingEmployeeId = null;
        newStatus = null;
    }
    
    function changeEmployeeStatus() {
        if (changingEmployeeId === null || newStatus === null) return;
        
        // Hiển thị spinner
        const spinner = Spinner.show({
            target: statusModal,
            text: newStatus === 'active' ? 'Đang kích hoạt nhân viên...' : 'Đang chuyển trạng thái nghỉ việc...'
        });
        
        // Xác định giá trị trạng thái dựa trên newStatus
        const statusValue = newStatus === 'active' ? 1 : -1; // 1: Đang hoạt động, -1: Nghỉ việc
        
        // Gọi API thay đổi trạng thái
        fetch(`${employeeList.dataset.url}/api/nhan-vien/${changingEmployeeId}/trang-thai`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ trang_thai: statusValue })
        })
        .then(response => response.json())
        .then(data => {
            // Ẩn spinner
            Spinner.hide(spinner);
            
            if (data.success) {
                // Đóng modal
                statusModal.classList.add('hidden');
                employeeModal.classList.add('hidden');
                
                // Hiển thị thông báo thành công
                const message = newStatus === 'active' 
                    ? 'Đã kích hoạt nhân viên thành công' 
                    : 'Đã chuyển trạng thái nhân viên thành nghỉ việc';
                showToast(data.message || message);
                
                // Tải lại danh sách nhân viên
                loadEmployees();
                
                // Reset biến
                changingEmployeeId = null;
                newStatus = null;
            } else {
                // Hiển thị thông báo lỗi
                showToast(data.message || 'Không thể thay đổi trạng thái nhân viên', true);
            }
        })
        .catch(error => {
            // Ẩn spinner
            Spinner.hide(spinner);
            
            // Hiển thị thông báo lỗi
            showToast('Lỗi kết nối: ' + error.message, true);
            console.error('Error:', error);
        });
    }
    
    function handleSaveEmployee() {
        // Validate form
        if (!validateForm()) return;
        
        // Get form data
        const employeeId = employeeIdInput.value ? parseInt(employeeIdInput.value) : null;
        const name = employeeNameInput.value.trim();
        const phone = employeePhoneInput.value.trim();
        const email = employeeEmailInput.value.trim();
        const username = employeeUsernameInput.value.trim();
        const password = employeePasswordInput.value;
        
        // Show spinner
        const spinner = Spinner.show({
            target: employeeModal,
            text: employeeId ? 'Đang cập nhật nhân viên...' : 'Đang thêm nhân viên mới...'
        });
        
        if (employeeId) {
            // UPDATE: Sử dụng API để cập nhật nhân viên
            // Tạo dữ liệu gửi lên API
            const data = {
                ten: name,
                dien_thoai: phone,
                email: email,
                ten_dang_nhap: username
            };
            
            // Gọi API cập nhật
            fetch(`${employeeList.dataset.url}/api/nhan-vien/${employeeId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                // Ẩn spinner
                Spinner.hide(spinner);
                
                if (data.success) {
                    // Đóng modal
                    closeModal();
                    
                    // Hiển thị thông báo thành công
                    showToast(data.message || 'Cập nhật thông tin thành công');
                    
                    // Tải lại danh sách nhân viên
                    loadEmployees();
                } else {
                    // Hiển thị thông báo lỗi
                    showToast(data.message || 'Cập nhật thông tin thất bại', true);
                }
            })
            .catch(error => {
                // Ẩn spinner
                Spinner.hide(spinner);
                
                // Hiển thị thông báo lỗi
                showToast('Lỗi kết nối: ' + error.message, true);
                console.error('Error:', error);
            });
        } else {
            // Thêm mới nhân viên - Giữ nguyên code hiện có
            const formData = new FormData();
            formData.append('ten', name);
            formData.append('dien_thoai', phone);
            formData.append('email', email);
            formData.append('ten_dang_nhap', username);
            formData.append('mat_khau', password);
            
            // Make API call with FormData
            fetch(`${employeeList.dataset.url}/api/nhan-vien`, {
                method: 'POST',
                // No Content-Type header needed - browser sets it automatically
                body: formData
            })
            // .then(response => response.text())
            // .then(text => {
            //     // Hide spinner
            //     Spinner.hide(spinner);
            //     console.log("API Response Text:", text);
            // })
            .then(response => response.json())
            .then(data => {
                // Hide spinner
                Spinner.hide(spinner);
                
                if (data.success) {
                    // Close modal
                    closeModal();
                    
                    // Show success message
                    showToast(data.message || 'Thêm nhân viên mới thành công');
                    
                    // Add the new employee to our local array for UI update
                    const newEmployee = {
                        id: data.data?.id || Date.now(), // Use returned ID if available
                        name,
                        phone,
                        email,
                        username,
                        status: 'active'
                    };
                    
                    employees.push(newEmployee);
                    loadEmployees();
                } else {
                    // Show error message
                    showToast(data.message || 'Thêm nhân viên thất bại', true);
                }
            })
            .catch(error => {
                // Hide spinner
                Spinner.hide(spinner);
                
                // Show error message
                showToast('Lỗi kết nối: ' + error.message, true);
                console.error('Error:', error);
            });
        }
    }
    
    function validateForm() {
        let isValid = true;
        clearValidationErrors();
        
        // Validate name
        if (!employeeNameInput.value.trim()) {
            nameError.classList.remove('hidden');
            isValid = false;
        }
        
        // Validate phone
        const phoneRegex = /^[0-9]{10}$/;
        if (!employeePhoneInput.value.trim() || !phoneRegex.test(employeePhoneInput.value.trim())) {
            phoneError.classList.remove('hidden');
            isValid = false;
        }
        
        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!employeeEmailInput.value.trim() || !emailRegex.test(employeeEmailInput.value.trim())) {
            emailError.classList.remove('hidden');
            isValid = false;
        }
        
        // Validate username
        if (!employeeUsernameInput.value.trim()) {
            usernameError.classList.remove('hidden');
            isValid = false;
        }
        
        // Validate password (only for new employees)
        if (!employeeIdInput.value && (!employeePasswordInput.value || employeePasswordInput.value.length < 8)) {
            passwordError.classList.remove('hidden');
            isValid = false;
        }
        
        return isValid;
    }
    
    function clearValidationErrors() {
        nameError.classList.add('hidden');
        phoneError.classList.add('hidden');
        emailError.classList.add('hidden');
        usernameError.classList.add('hidden');
        passwordError.classList.add('hidden');
    }
    
    function showToast(message, isError = false) {
        toastNotification.textContent = message;
        
        // Set color based on message type
        if (isError) {
            toastNotification.classList.remove('bg-green-500');
            toastNotification.classList.add('bg-red-500');
        } else {
            toastNotification.classList.remove('bg-red-500');
            toastNotification.classList.add('bg-green-500');
        }
        
        toastNotification.classList.remove('translate-y-20', 'opacity-0');
        
        setTimeout(() => {
            toastNotification.classList.add('translate-y-20', 'opacity-0');
        }, 3000);
    }
    
    function updatePagination(totalEmployees) {
        // Calculate total pages
        totalPages = Math.ceil(totalEmployees / perPage);
        
        // Update pagination info text
        if (paginationInfo) {
            paginationInfo.textContent = `Trang ${currentPage} của ${totalPages}`;
        }
        
        // Hide pagination if there's only one page
        if (totalPages <= 1) {
            paginationButtons.classList.add('hidden');
        } else {
            paginationButtons.classList.remove('hidden');
        }
        
        // Disable/enable prev/next buttons
        if (prevPageBtn && nextPageBtn) {
            prevPageBtn.disabled = currentPage === 1;
            nextPageBtn.disabled = currentPage === totalPages;
        }
    }
    
    function updatePaginationUI(pagination) {
        // Update text showing pagination info
        const paginationInfo = document.getElementById('pagination-info');
        if (paginationInfo) {
            const start = pagination.total > 0 ? ((pagination.current_page - 1) * pagination.per_page) + 1 : 0;
            const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);
            paginationInfo.innerHTML = `
                Hiển thị <span class="font-medium">${start}</span> đến 
                <span class="font-medium">${end}</span> trong số 
                <span class="font-medium">${pagination.total}</span> nhân viên
            `;
        }
        
        // Generate page buttons
        const pageButtons = document.querySelector('nav[aria-label="Pagination"] .inline-flex');
        if (pageButtons) {
            // Clear existing buttons
            pageButtons.innerHTML = '';
            
            // Don't show pagination if there's only one page
            if (pagination.total_pages <= 1) {
                document.querySelector('nav[aria-label="Pagination"]').closest('.sm\\:flex').classList.add('hidden');
                return;
            } else {
                document.querySelector('nav[aria-label="Pagination"]').closest('.sm\\:flex').classList.remove('hidden');
            }
            
            // Previous button
            const prevButton = document.createElement('button');
            prevButton.classList.add('px-4', 'py-2', 'mr-2', 'text-sm', 'font-medium', 'text-gray-700', 'bg-white', 'border', 'border-gray-300', 'rounded-md', 'shadow-sm', 'hover:bg-gray-50', 'focus:outline-none', 'focus:ring-2', 'focus:ring-offset-2', 'focus:ring-indigo-500');
            prevButton.setAttribute('aria-label', 'Trang trước');
            prevButton.disabled = currentPage === 1;
            prevButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width={2} d="M15 19l-7-7 7-7" />
                </svg>
            `;
            prevButton.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    loadEmployees();
                }
            });
            pageButtons.appendChild(prevButton);
            
            // Numbered buttons
            for (let i = 1; i <= totalPages; i++) {
                const pageButton = document.createElement('button');
                pageButton.classList.add(
                    'px-4', 'py-2', 'mr-2', 'text-sm', 'font-medium', 'rounded-md', 
                    'focus:outline-none', 'focus:ring-2', 'focus:ring-offset-2', 'focus:ring-indigo-500'
                );
                pageButton.textContent = i;
                if (i === currentPage) {
                    // Trang hiện tại: active
                    pageButton.classList.add('bg-blue-600', 'text-white', 'border', 'border-blue-600');
                    pageButton.disabled = true;
                } else {
                    pageButton.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-300', 'hover:bg-gray-50');
                    pageButton.disabled = false;
                }
                pageButton.addEventListener('click', () => {
                    currentPage = i;
                    loadEmployees();
                });
                pageButtons.appendChild(pageButton);
            }
            
            // Next button
            const nextButton = document.createElement('button');
            nextButton.classList.add('px-4', 'py-2', 'text-sm', 'font-medium', 'text-gray-700', 'bg-white', 'border', 'border-gray-300', 'rounded-md', 'shadow-sm', 'hover:bg-gray-50', 'focus:outline-none', 'focus:ring-2', 'focus:ring-offset-2', 'focus:ring-indigo-500');
            nextButton.setAttribute('aria-label', 'Trang tiếp theo');
            nextButton.disabled = currentPage === totalPages;
            nextButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width={2} d="M9 5l7 7-7 7" />
                </svg>
            `;
            nextButton.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    loadEmployees();
                }
            });
            pageButtons.appendChild(nextButton);
        }
    }
});
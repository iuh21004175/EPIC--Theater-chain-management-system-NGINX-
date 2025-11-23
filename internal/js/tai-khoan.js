import Spinner from './util/spinner.js';

document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const accountsList = document.getElementById('accounts-list');
    const btnAddAccount = document.getElementById('btn-add-account');
    const modalAddAccount = document.getElementById('modal-add-account');
    const modalEditAccount = document.getElementById('modal-edit-account');
    const modalAssignCinema = document.getElementById('modal-assign-cinema');
    const btnSubmitAdd = document.getElementById('btn-submit-add');
    const btnSubmitEdit = document.getElementById('btn-submit-edit');
    const btnSubmitAssign = document.getElementById('btn-submit-assign');
    const btnUnassign = document.getElementById('btn-unassign');
    const btnApplyFilters = document.getElementById('btn-apply-filters');
    const cancelButtons = document.querySelectorAll('.btn-cancel');
    const toast = document.getElementById('toast-notification');

    // Filters
    const filterStatus = document.getElementById('filter-status');
    const filterAssignment = document.getElementById('filter-assignment');
    const filterSearch = document.getElementById('filter-search');

    // Form elements
    const formAddAccount = document.getElementById('form-add-account');
    const formEditAccount = document.getElementById('form-edit-account');
    const formAssignCinema = document.getElementById('form-assign-cinema');
    const resetPasswordCheckbox = document.getElementById('edit-account-reset-password');
    const resetPasswordFields = document.getElementById('reset-password-fields');

    // Load accounts list
    function loadAccounts() {
        // Hiển thị spinner inline giống như trong phim.js
        Spinner.hide();
        accountsList.innerHTML = `
            <li class="px-6 py-8 flex items-center justify-center">
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div class="epic-spinner" style="
                        width: 32px;
                        height: 32px;
                        border: 4px solid rgba(0,0,0,0.1);
                        border-radius: 50%;
                        border-top: 4px solid #E11D48;
                        animation: epic-spin 1s linear infinite;
                        margin-bottom: 12px;
                    "></div>
                    <span style="color: #374151; font-size: 15px;">Đang tải tài khoản...</span>
                </div>
            </li>
        `;
        
        // Đảm bảo CSS animation được thêm vào
        if (!document.getElementById('epic-spinner-style')) {
            const styleElement = document.createElement('style');
            styleElement.id = 'epic-spinner-style';
            styleElement.textContent = `
                @keyframes epic-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(styleElement);
        }
        
        // Fetch accounts from API
        fetch(`${accountsList.dataset.url}/api/tai-khoan`)
            .then(response => response.json())
            .then(data => {
                Spinner.hide();
                
                if (data.success && data.data) {
                    // Render accounts
                    renderAccounts(data.data);
                } else {
                    // Show error message
                    accountsList.innerHTML = `
                        <li class="px-6 py-4 flex items-center">
                            <div class="w-full text-center text-red-500">Không thể tải dữ liệu tài khoản</div>
                        </li>
                    `;
                    console.error('API Error:', data.message);
                }
            })
            .catch(error => {
                Spinner.hide();
                
                // Show error message
                accountsList.innerHTML = `
                    <li class="px-6 py-4 flex items-center">
                        <div class="w-full text-center text-red-500">Lỗi kết nối: ${error.message}</div>
                    </li>
                `;
                console.error('Fetch Error:', error);
            });
    }

    // Render accounts list
    function renderAccounts(accounts) {
        if (!accounts || accounts.length === 0) {
            accountsList.innerHTML = `
                <li class="px-6 py-4 flex items-center">
                    <div class="w-full text-center text-gray-500">Không tìm thấy tài khoản nào</div>
                </li>
            `;
            return;
        }

        accountsList.innerHTML = '';
        accounts.forEach(account => {
            const listItem = document.createElement('li');
            listItem.className = 'px-6 py-4 flex items-center justify-between hover:bg-gray-50';
            
            // Get user info from nested object
            const userInfo = account.nguoi_dung_internals || {};
            const isActive = userInfo.trang_thai !== 0; // Assuming 0 means inactive
            
            // Cinema assignment info
            const cinemaInfo = userInfo.rap_phim || {};
            const isAssigned = userInfo.id_rapphim != null;
            
            const statusBadge = isActive 
                ? '<span class="status-badge active">Đang hoạt động</span>' 
                : '<span class="status-badge inactive">Đã khóa</span>';
            
            const assignmentBadge = isAssigned 
                ? `<span class="status-badge assigned">Quản lý: ${cinemaInfo.ten || 'N/A'}</span>` 
                : '<span class="status-badge unassigned">Chưa phân công</span>';
            
            listItem.innerHTML = `
                <div>
                    <h3 class="text-lg font-medium text-gray-900">${userInfo.ten || 'Chưa cập nhật'}</h3>
                    <p class="text-sm text-gray-500">${userInfo.email || 'Không có email'}</p>
                    <p class="text-sm text-gray-500">SĐT: ${userInfo.dien_thoai || 'Chưa cập nhật'}</p>
                    <div class="mt-2 flex items-center space-x-2">
                        ${statusBadge}
                        ${assignmentBadge}
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button type="button" class="btn-assign inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" data-id="${account.id}">
                        <svg class="-ml-1 mr-1 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7 2a1 1 0 00-.707 1.707L7 4.414v3.758a1 1 0 01-.293.707l-4 4C.817 14.769 2.156 18 4.828 18h10.343c2.673 0 4.012-3.231 2.122-5.121l-4-4A1 1 0 0113 8.172V4.414l.707-.707A1 1 0 0013 2H7zm2 6.172V4h2v4.172a3 3 0 00.879 2.12l1.168 1.168a4 4 0 00-2.929.986L9 13.92l-1.046-1.046a4 4 0 00-2.929-.986l1.168-1.168A3 3 0 007 8.172z" clip-rule="evenodd" />
                        </svg>
                        Phân công
                    </button>
                    <button type="button" class="btn-edit inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" data-id="${account.id}">
                        <svg class="-ml-1 mr-1 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                        Sửa
                    </button>
                </div>
            `;
            accountsList.appendChild(listItem);
        });

        // Add event listeners to buttons
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', function() {
                const accountId = this.getAttribute('data-id');
                openEditModal(accountId);
            });
        });

        document.querySelectorAll('.btn-assign').forEach(button => {
            button.addEventListener('click', function() {
                const accountId = this.getAttribute('data-id');
                console.log('Assign cinema to account ID:', accountId);
                openAssignModal(accountId);
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
    function validateForm(formData, isAdd = true) {
        let isValid = true;
        const errors = {};
        
        // Validate fullname
        if (!formData.fullname || formData.fullname.trim() === '') {
            errors.fullname = 'Họ và tên không được để trống';
            isValid = false;
        }
        
        // Validate username for new accounts
        if (isAdd) {
            if (!formData.username || formData.username.trim() === '') {
                errors.username = 'Tên đăng nhập không được để trống';
                isValid = false;
            } else if (/\s/.test(formData.username) || !/^[a-zA-Z0-9_]+$/.test(formData.username)) {
                errors.username = 'Tên đăng nhập không được chứa dấu cách và ký tự đặc biệt';
                isValid = false;
            }
        } else {
            // Allow updating username: validate when editing as well
            if (!formData.username || formData.username.trim() === '') {
                errors.username = 'Tên đăng nhập không được để trống';
                isValid = false;
            } else if (/\s/.test(formData.username) || !/^[a-zA-Z0-9_]+$/.test(formData.username)) {
                errors.username = 'Tên đăng nhập không được chứa dấu cách và ký tự đặc biệt';
                isValid = false;
            }
        }
        
        // Validate email for new accounts
        if (isAdd) {
            if (!formData.email || formData.email.trim() === '') {
                errors.email = 'Email không được để trống';
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
                errors.email = 'Email không hợp lệ';
                isValid = false;
            }
        }
        
        // Validate password for new accounts or when resetting password
        if (isAdd || (formData.reset_password && formData.reset_password === 'on')) {
            if (!formData.password || formData.password.length < 8) {
                errors.password = 'Mật khẩu phải có ít nhất 8 ký tự';
                isValid = false;
            } else if (!/[A-Z]/.test(formData.password) || !/[a-z]/.test(formData.password) || !/[0-9]/.test(formData.password)) {
                errors.password = 'Mật khẩu phải có ít nhất một chữ hoa, một chữ thường và một số';
                isValid = false;
            }
            
            if (formData.password !== formData.password_confirm) {
                errors.password_confirm = 'Xác nhận mật khẩu không khớp';
                isValid = false;
            }
        }
        
        // Validate phone
        if (formData.phone && !/^[0-9]{10,11}$/.test(formData.phone)) {
            errors.phone = 'Số điện thoại không hợp lệ';
            isValid = false;
        }
        
        // Clear all error messages first
        const prefix = isAdd ? '' : 'edit-';
        ['fullname', 'username', 'email', 'password', 'password_confirm', 'phone'].forEach(field => {
            const errorElement = document.getElementById(`${field}-error`);
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.classList.add('invisible');
                errorElement.classList.remove('visible');
            }
            
            // Also clear edit form errors
            if (isAdd) {
                const editErrorElement = document.getElementById(`edit-${field}-error`);
                if (editErrorElement) {
                    editErrorElement.textContent = '';
                    editErrorElement.classList.add('invisible');
                    editErrorElement.classList.remove('visible');
                }
            }
        });
        
        // Display new errors
        Object.keys(errors).forEach(field => {
            const errorElement = document.getElementById(`${prefix}${field}-error`);
            if (errorElement) {
                // Set error message
                errorElement.textContent = errors[field];
                
                // Make it visible
                errorElement.classList.remove('invisible');
                errorElement.classList.add('visible');
                
                // Highlight the input field with error
                const inputField = document.getElementById(`${prefix}account-${field}`);
                if (inputField) {
                    inputField.classList.add('border-red-500');
                    inputField.classList.add('focus:ring-red-500');
                    inputField.classList.add('focus:border-red-500');
                }
                
                // Log the error for debugging
                console.log(`Validation error for ${field}: ${errors[field]}`);
            } else {
                console.warn(`Error element for ${prefix}${field}-error not found`);
            }
        });
        
        return isValid;
    }

    // Open Add Modal
    function openAddModal() {
        // Reset form
        formAddAccount.reset();
        
        // Clear error messages
        document.querySelectorAll('#form-add-account .text-red-600').forEach(el => {
            el.textContent = '';
            el.classList.add('invisible');
            el.classList.remove('visible');
        });
        
        // Show modal
        modalAddAccount.classList.remove('hidden');
    }

    // Open Edit Modal
    function openEditModal(accountId) {
        // Fetch account data from API
        fetch(`${accountsList.dataset.url}/api/tai-khoan/${accountId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    // Fix: Check if data.data is an array and access the first element if it is
                    const account = Array.isArray(data.data) ? data.data[0] : data.data;
                    const userInfo = account.nguoi_dung_internals || {};
                    
                    // Populate form
                    document.getElementById('edit-account-id').value = account.id;
                    document.getElementById('edit-account-fullname').value = userInfo.ten || '';
                    document.getElementById('edit-account-username').value = account.tendangnhap || ''; // Add this line
                    document.getElementById('edit-account-email').value = userInfo.email || '';
                    document.getElementById('edit-account-phone').value = userInfo.dien_thoai || '';
                    document.getElementById('edit-account-active').checked = userInfo.trang_thai !== 0;
                    
                    // Reset password fields
                    document.getElementById('edit-account-reset-password').checked = false;
                    resetPasswordFields.classList.add('hidden');
                    document.getElementById('edit-account-password').value = '';
                    document.getElementById('edit-account-password-confirm').value = '';
                    
                    // Clear error messages
                    document.querySelectorAll('#form-edit-account .text-red-600').forEach(el => {
                        el.textContent = '';
                        el.classList.add('invisible');
                        el.classList.remove('visible');
                    });
                    
                    // Show modal
                    modalEditAccount.classList.remove('hidden');
                } else {
                    showToast('Không thể tải thông tin tài khoản', true);
                }
            })
            .catch(error => {
                showToast('Lỗi kết nối: ' + error.message, true);
            });
    }

    // Open Assign Modal
    function openAssignModal(accountId) {
        // Fetch both account and cinema data
        Promise.all([
            fetch(`${accountsList.dataset.url}/api/tai-khoan/${accountId}`).then(res => res.json()),
            fetch(`${accountsList.dataset.url}/api/rap-phim`).then(res => res.json())
        ])
        .then(([accountData, cinemasData]) => {
            if (accountData.success && accountData.data && cinemasData.success && cinemasData.data) {
                // The data structure has the account as the first element in an array
                const account = Array.isArray(accountData.data) ? accountData.data[0] : accountData.data;
                const userInfo = account.nguoi_dung_internals || {};
                const cinemas = cinemasData.data;
                
                // Populate form
                document.getElementById('assign-account-id').value = account.id;
                
                // Fix email display by using the correct property access
                document.getElementById('assign-account-name').textContent = 
                    `Tài khoản: ${userInfo.ten || account.tendangnhap} (${userInfo.email || 'Không có email'})`;
                
                // Get cinema dropdown
                const cinemaDropdown = document.getElementById('assign-cinema-id');
                cinemaDropdown.innerHTML = '<option value="">-- Chọn rạp phim --</option>';
                
                // Add cinema options
                cinemas.forEach(cinema => {
                    // Skip cinemas that are already assigned to other accounts
                    if (!cinema.nguoi_dung_id || cinema.nguoi_dung_id == userInfo.id) {
                        const option = document.createElement('option');
                        option.value = cinema.id;
                        option.textContent = cinema.ten;
                        
                        // Select current cinema if assigned
                        if (userInfo.id_rapphim && cinema.id == userInfo.id_rapphim) {
                            option.selected = true;
                        }
                        
                        cinemaDropdown.appendChild(option);
                    }
                });
                
                // Show/hide unassign button
                if (userInfo.id_rapphim) {
                    btnUnassign.classList.remove('hidden');
                } else {
                    btnUnassign.classList.add('hidden');
                }
                
                // Clear error messages
                document.getElementById('assign-cinema-error').textContent = '';
                document.getElementById('assign-cinema-error').classList.add('hidden');
                
                // Show modal
                modalAssignCinema.classList.remove('hidden');
            } else {
                showToast('Không thể tải thông tin', true);
            }
        })
        .catch(error => {
            showToast('Lỗi kết nối: ' + error.message, true);
        });
    }

    // Close modals
    function closeModals() {
        modalAddAccount.classList.add('hidden');
        modalEditAccount.classList.add('hidden');
        modalAssignCinema.classList.add('hidden');
        // Reset assign modal fields
        document.getElementById('assign-account-id').value = '';
        document.getElementById('assign-cinema-id').selectedIndex = 0;
        document.getElementById('assign-account-name').textContent = '';
        document.getElementById('assign-cinema-error').textContent = '';
        document.getElementById('assign-cinema-error').classList.add('hidden');
        btnUnassign.classList.add('hidden');
    }

    // Add new account
    function addAccount() {
        // Hiển thị spinner giống như trong phim.js
        const spinner = Spinner.show({
            target: modalAddAccount,
            text: 'Đang thêm tài khoản...'
        });
        const formData = {
            fullname: document.getElementById('account-fullname').value.trim(),
            username: document.getElementById('account-username').value.trim(),
            email: document.getElementById('account-email').value.trim(),
            password: document.getElementById('account-password').value,
            password_confirm: document.getElementById('account-password-confirm').value,
            phone: document.getElementById('account-phone').value.trim()
        };
        
        // Validate form
        if (!validateForm(formData, true)) {
            Spinner.hide(spinner);
            return;
        }

        // Create payload for API
        const payload = {
            tendangnhap: formData.username,
            matkhau: formData.password,
            ten: formData.fullname,
            email: formData.email,
            dien_thoai: formData.phone || ''
        };

        // Disable submit button
        btnSubmitAdd.disabled = true;
        
        

        // Make API call
        // Get the form action URL
        const formAction = formAddAccount.getAttribute('action');
        
        fetch(formAction, {
            method: 'POST',
            headers: {
            'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        })
        // .then(response => response.text())
        // .then(text => console.log('Response Text:', text)) Log raw response text for debugging
        .then(response => response.json()) // Changed from text() to json()
        .then(data => {
            // Hide spinner
            Spinner.hide(spinner);
            
            // Re-enable button
            btnSubmitAdd.disabled = false;

            if (data.success) {
                // Close modal
                closeModals();
                
                // Show success message
                showToast(data.message || 'Tạo tài khoản mới thành công');
                
                // Refresh the list
                loadAccounts();
            } else {
            // Show error message
                showToast(data.message || 'Tạo tài khoản thất bại', true);
            }
            
        })
        .catch(error => {
            // Hide spinner
            Spinner.hide(spinner);
            
            // Re-enable button
            btnSubmitAdd.disabled = false;
            
            // Show error message
            showToast('Lỗi kết nối: ' + error.message, true);
            console.error('Error:', error);
        });
    }

    // Update account
    function updateAccount() {
            const accountId = document.getElementById('edit-account-id').value;
            const resetPassword = document.getElementById('edit-account-reset-password').checked;
            
            const formData = {
                fullname: document.getElementById('edit-account-fullname').value.trim(),
                username: document.getElementById('edit-account-username').value.trim(),
                phone: document.getElementById('edit-account-phone').value.trim(),
                active: document.getElementById('edit-account-active').checked,
                reset_password: resetPassword ? 'on' : 'off'
            };
            
            if (resetPassword) {
                formData.password = document.getElementById('edit-account-password').value;
                formData.password_confirm = document.getElementById('edit-account-password-confirm').value;
            }
            
            // Validate form
            if (!validateForm(formData, false)) {
                return;
            }
            
            // Hiển thị spinner giống như trong phim.js
            const spinner = Spinner.show({
                target: modalEditAccount,
                text: 'Đang cập nhật tài khoản...'
            });
            
            // Create API payload with correct field names matching the backend
            const payload = {
                ten: formData.fullname,
                tendangnhap: formData.username, // <-- include username for update
                email: document.getElementById('edit-account-email').value.trim(), // Include email even though it's readonly
                dien_thoai: formData.phone,
                khoa_tai_khoan: !formData.active, // Note: Backend expects khoa_tai_khoan (true = locked)
                dat_lai_mat_khau: resetPassword,
            };
            
            // Add password if resetting
            if (resetPassword) {
                payload.mat_khau_moi = formData.password;
            }
            
            // Make API call
            fetch(`${accountsList.dataset.url}/api/tai-khoan/${accountId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                // Hide spinner
                Spinner.hide(spinner);
                
                if (data.success) {
                    // Close modal
                    closeModals();
                    
                    // Show success message
                    showToast(data.message || 'Cập nhật thông tin tài khoản thành công');
                    
                    // Refresh the list
                    loadAccounts();
                } else {
                    showToast(data.message || 'Cập nhật thông tin thất bại', true);
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

    // Assign cinema to account
    function assignCinema(e) {
        const accountId = document.getElementById('assign-account-id').value;
        const cinemaId = document.getElementById('assign-cinema-id').value;
        
        if (!cinemaId) {
            document.getElementById('assign-cinema-error').textContent = 'Vui lòng chọn rạp phim';
            document.getElementById('assign-cinema-error').classList.remove('hidden');
            return;
        }
        
        // Hiển thị spinner giống như trong phim.js
        const spinner = Spinner.show({
            target: modalAssignCinema,
            text: 'Đang phân công rạp phim...'
        });
        
        // Create payload for API
        const payload = {
            id_rapphim: parseInt(cinemaId)
        };
        
        // Make API call
        fetch(`${accountsList.dataset.url}/api/tai-khoan/${accountId}/phan-cong`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then (data => {
            // Hide spinner
            Spinner.hide(spinner);
            
            if (data.success) {
                // Close modal
                closeModals();
                
                // Show success message
                showToast(data.message || 'Phân công rạp phim thành công');
                
                // Refresh the list
                loadAccounts();
            } else {
                // Show error message
                showToast(data.message || 'Phân công rạp phim thất bại', true);
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

    // Unassign cinema from account
    function unassignCinema() {
        const accountId = parseInt(document.getElementById('assign-account-id').value);
        
        // Get account
        const account = sampleAccounts.find(acc => acc.id === accountId);
        if (!account || !account.cinema_id) return;
        
        // Get cinema
        const cinema = sampleCinemas.find(cin => cin.id === account.cinema_id);
        const cinemaName = account.cinema_name;
        
        // Unassign cinema from account
        if (cinema) {
            cinema.assigned = false;
        }
        
        account.cinema_id = null;
        account.cinema_name = null;
        
        // Close modal
        closeModals();
        
        // Show success message
        showToast(`Đã hủy phân công ${account.fullname} quản lý rạp ${cinemaName}`);
        
        // Refresh the list
        loadAccounts();
    }

    // Event Listeners
    btnAddAccount.addEventListener('click', openAddModal);
    
    cancelButtons.forEach(button => {
        button.addEventListener('click', closeModals);
    });
    
    btnSubmitAdd.addEventListener('click', addAccount);
    
    btnSubmitEdit.addEventListener('click', updateAccount);
    
    btnSubmitAssign.addEventListener('click', assignCinema);
    
    btnUnassign.addEventListener('click', unassignCinema);
    
    // Toggle password fields when reset password checkbox is clicked
    if (resetPasswordCheckbox && resetPasswordFields) {
        resetPasswordCheckbox.addEventListener('change', function() {
            if (this.checked) {
                resetPasswordFields.classList.remove('hidden');
            } else {
                resetPasswordFields.classList.add('hidden');
                // Clear password fields when hiding
                const passwordInput = document.getElementById('edit-account-password');
                const passwordConfirmInput = document.getElementById('edit-account-password-confirm');
                if (passwordInput) passwordInput.value = '';
                if (passwordConfirmInput) passwordConfirmInput.value = '';
                
                // Clear error messages
                const passwordError = document.getElementById('edit-password-error');
                const passwordConfirmError = document.getElementById('edit-password-confirm-error');
                if (passwordError) {
                    passwordError.textContent = '';
                    passwordError.classList.add('invisible');
                }
                if (passwordConfirmError) {
                    passwordConfirmError.textContent = '';
                    passwordConfirmError.classList.add('invisible');
                }
            }
        });
    }
    
    // When clicking outside the modal content, close the modal
    window.addEventListener('click', function(event) {
        if (event.target === modalAddAccount || event.target === modalEditAccount || event.target === modalAssignCinema) {
            closeModals();
        }
    });

    // Initialize
    loadAccounts();
});

// Add this to your JavaScript to ensure error messages stand out
document.addEventListener('DOMContentLoaded', function() {
    // Enhance error message styling
    const errorMessages = document.querySelectorAll('.text-red-600');
    errorMessages.forEach(error => {
        error.classList.add('font-medium');
        error.style.display = 'block';
        error.style.marginTop = '0.25rem';
    });
    
    // Make form fields show red border immediately when they get validation errors
    const formInputs = document.querySelectorAll('input, select, textarea');
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Remove error styling when user starts typing
            this.classList.remove('border-red-500');
            
            // Hide corresponding error message
            const fieldName = this.name;
            const errorElement = document.getElementById(`${fieldName}-error`);
            if (errorElement) {
                errorElement.classList.add('invisible');
                errorElement.classList.remove('visible');
                errorElement.textContent = '';
            }
            // Also check for edit form
            const editErrorElement = document.getElementById(`edit-${fieldName}-error`);
            if (editErrorElement) {
                editErrorElement.classList.add('invisible');
                editErrorElement.classList.remove('visible');
                editErrorElement.textContent = '';
            }
        });
    });
});
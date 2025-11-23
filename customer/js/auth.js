document.addEventListener('DOMContentLoaded', function() {
        const modalLogin = document.getElementById('modalLogin');
        const modalRegister = document.getElementById('modalRegister');
        const modalForgotPassword = document.getElementById('modalForgotPassword');
        const body = document.body;
        const btnSave = document.getElementById('btnSave');
        const termsCheckbox = document.getElementById('termsCheckbox');

        function openModal(modal) {
            modal.classList.add('is-open');
            body.classList.add('modal-open');
        }

        function closeModal(modal) {
            modal.classList.remove('is-open');
            body.classList.remove('modal-open');
        }

        function switchModal(fromModal, toModal) {
            closeModal(fromModal);
            openModal(toModal);
        }
        
        // Cập nhật trạng thái nút Đăng Ký dựa trên checkbox
        function toggleSubmitButton() {
            if (termsCheckbox.checked) {
                btnSave.disabled = false;
                btnSave.classList.remove('bg-blue-400', 'cursor-not-allowed');
                btnSave.classList.add('bg-blue-600', 'hover:bg-blue-700');
            } else {
                btnSave.disabled = true;
                btnSave.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                btnSave.classList.add('bg-blue-400', 'cursor-not-allowed');
            }
        }

        // Event listeners for opening and closing modals
        const btnLogin = document.getElementById('btn-login');
        if (btnLogin) {
            btnLogin.addEventListener('click', () => openModal(modalLogin));
        }

        document.querySelectorAll('.modal .close').forEach(button => {
            button.addEventListener('click', (event) => {
                const modal = event.target.closest('.modal');
                if (modal) closeModal(modal);
            });
        });

        modalLogin.addEventListener('click', (event) => {
            if (event.target === modalLogin) {
                closeModal(modalLogin);
            }
        });
        modalRegister.addEventListener('click', (event) => {
            if (event.target === modalRegister) {
                closeModal(modalRegister);
            }
        });
        modalForgotPassword.addEventListener('click', (event) => {
            if (event.target === modalForgotPassword) {
                closeModal(modalForgotPassword);
            }
        });

        // Event listeners for switching modals
        document.getElementById('btnRegister').addEventListener('click', (e) => {
            e.preventDefault();
            switchModal(modalLogin, modalRegister);
        });

        document.getElementById('btnBackToLogin').addEventListener('click', (e) => {
            e.preventDefault();
            switchModal(modalRegister, modalLogin);
        });

        document.getElementById('btnForgotPassword').addEventListener('click', (e) => {
            e.preventDefault();
            switchModal(modalLogin, modalForgotPassword);
        });

        // Event listener cho checkbox
        termsCheckbox.addEventListener('change', toggleSubmitButton);
        
        // Kiểm tra trạng thái ban đầu của nút khi trang tải
        toggleSubmitButton();

        // --- Form Validation Functions ---
        function checkEmail(inputElement, errorElement) {
            const kt = /^[a-zA-Z0-9](?:[a-zA-Z0-9._%+-]{0,62}[a-zA-Z0-9])?@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z]{2,})+$/;
            const value = inputElement.value.trim();
            if (value === "") {
                errorElement.textContent = "Email không được để trống!";
                return false;
            }
            if (!kt.test(value)) {
                errorElement.textContent = "Email không hợp lệ!";
                return false;
            }
            errorElement.textContent = "";
            return true;
        }

        function checkPassword(inputElement, errorElement) {
            const kt = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+[\]{}|;:',.<>?/]).{8,}$/;
            const value = inputElement.value;
            if (value === "") {
                errorElement.textContent = "Mật khẩu không được để trống!";
                return false;
            }
            if (value.length < 8) {
                errorElement.textContent = "Mật khẩu phải có ít nhất 8 ký tự!";
                return false;
            }
            if (!kt.test(value)) {
                errorElement.textContent = "Mật khẩu phải có chữ hoa, chữ thường, số, ký tự đặc biệt";
                return false;
            }
            errorElement.textContent = "";
            return true;
        }

        function checkPasswordConfirm() {
            const pw = document.getElementById('registerPassword').value;
            const value = document.getElementById('registerPasswordConfirm').value;
            const errorElement = document.getElementById('tbRegisterPasswordConfirm');
            if (value === "") {
                errorElement.textContent = "Nhập lại mật khẩu không được để trống!";
                return false;
            }
            if (pw === "") {
                errorElement.textContent = "Bạn chưa nhập mật khẩu chính!";
                return false;
            }
            if (value !== pw) {
                errorElement.textContent = "Mật khẩu nhập lại không khớp";
                return false;
            }
            errorElement.textContent = "";
            return true;
        }

        function checkName() {
            const inputElement = document.getElementById('registerName');
            const errorElement = document.getElementById('tbRegisterName');
            const kt = /^(([A-Z]{1})([a-z]+))(\s([A-Z]{1})([a-z]+)){1,}$/;
            if (inputElement.value.trim() === "") {
                errorElement.textContent = "Họ tên không được để trống!";
                return false;
            }
            if (!kt.test(inputElement.value)) {
                errorElement.textContent = "Ký tự đầu viết hoa, ít nhất có 2 từ!";
                return false;
            }
            errorElement.textContent = "";
            return true;
        }

        function checkNgaySinh() {
            const inputElement = document.getElementById('txtNgaySinh');
            const errorElement = document.getElementById('tbNgaySinh');
            const value = inputElement.value;
            if (!value) {
                errorElement.textContent = "Ngày sinh không được để trống!";
                return false;
            }
            const today = new Date();
            const todayStr = today.toISOString().split("T")[0];
            if (value > todayStr) {
                errorElement.textContent = "Ngày sinh không được sau hiện tại!";
                return false;
            }
            const ngaySinh = new Date(value);
            let tuoi = today.getFullYear() - ngaySinh.getFullYear();
            const m = today.getMonth() - ngaySinh.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < ngaySinh.getDate())) {
                tuoi--;
            }
            if (tuoi < 13) {
                errorElement.textContent = "Bạn phải từ 13 tuổi trở lên!";
                return false;
            }
            errorElement.textContent = "";
            return true;
        }

        function checkGender() {
            const selectElement = document.getElementById('sexSelect');
            const errorElement = document.getElementById('tbSex');
            if (selectElement.value === "") {
                errorElement.textContent = "Giới tính không được để trống!";
                return false;
            }
            errorElement.textContent = "";
            return true;
        }
        
        function checkPhone() {
            const inputElement = document.getElementById('registerPhone');
            const errorElement = document.getElementById('tbRegisterPhone');
            const regex = /^(03|05|07|08|09)[0-9]{8}$/;

            if (inputElement.value.trim() === "") {
                errorElement.textContent = "Số điện thoại không được để trống!";
                return false;
            }
            if (!regex.test(inputElement.value)) {
                errorElement.textContent = "Số điện thoại phải gồm 10 số và bắt đầu bằng 03, 05, 07, 08 hoặc 09!";
                return false;
            }

            errorElement.textContent = "";
            return true;
        }

        // --- Event Listeners for validation on blur/change ---
        document.getElementById('loginEmail').addEventListener('blur', (e) => checkEmail(e.target, document.getElementById('tbLoginEmail')));
        document.getElementById('registerEmail').addEventListener('blur', (e) => checkEmail(e.target, document.getElementById('tbRegisterEmail')));
        document.getElementById('forgotEmail').addEventListener('blur', (e) => checkEmail(e.target, document.getElementById('tbForgotEmail')));
        document.getElementById('loginPassword').addEventListener('blur', (e) => checkPassword(e.target, document.getElementById('tbLoginPassword')));
        document.getElementById('registerPassword').addEventListener('blur', (e) => {
            checkPassword(e.target, document.getElementById('tbRegisterPassword'));
            checkPasswordConfirm();
        });
        document.getElementById('registerPasswordConfirm').addEventListener('blur', checkPasswordConfirm);
        document.getElementById('registerName').addEventListener('blur', checkName);
        document.getElementById('registerPhone').addEventListener('blur', checkPhone);
        document.getElementById('txtNgaySinh').addEventListener('blur', checkNgaySinh);
        document.getElementById('sexSelect').addEventListener('change', checkGender);

        // --- Form Submission Validation ---
        document.getElementById('btnLogin').addEventListener('click', function(e) {
            e.preventDefault(); // ngăn form submit mặc định

            let isEmailValid = checkEmail(document.getElementById('loginEmail'), document.getElementById('tbLoginEmail'));
            let isPasswordValid = checkPassword(document.getElementById('loginPassword'), document.getElementById('tbLoginPassword'));

            if (isEmailValid && isPasswordValid) {
                const form = document.getElementById('loginForm');
                const formData = new FormData(form);

                fetch(baseUrl + "/api/dang-nhap-khach-hang", {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message || 'Đăng nhập thành công!');
                        window.location.reload(); 
                    } else {
                        alert(data.message || 'Đăng nhập thất bại. Vui lòng thử lại.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Đã xảy ra lỗi khi kết nối với máy chủ. Vui lòng thử lại sau!');
                });
            } else {
                alert("Vui lòng kiểm tra lại thông tin đăng nhập!");
            }   
        });


        document.getElementById('btnSave').addEventListener('click', function(e) {
            e.preventDefault();

            let isNameValid = checkName();
            let isPhoneValid = checkPhone();
            let isEmailValid = checkEmail(document.getElementById('registerEmail'), document.getElementById('tbRegisterEmail'));
            let isGenderValid = checkGender();
            let isDateValid = checkNgaySinh();
            let isPasswordValid = checkPassword(document.getElementById('registerPassword'), document.getElementById('tbRegisterPassword'));
            let isPasswordConfirmValid = checkPasswordConfirm();
            let isTermsChecked = document.getElementById('termsCheckbox').checked;

            if (isNameValid && isPhoneValid && isEmailValid && isGenderValid && isDateValid && isPasswordValid && isPasswordConfirmValid && isTermsChecked) {
                const form = document.getElementById('registerForm');
                const formData = new FormData(form);
                const btnSave = document.getElementById('btnSave');

                btnSave.textContent = 'Đang xử lý...';
                btnSave.disabled = true;

                fetch(baseUrl + "/api/dang-ky", {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    btnSave.textContent = 'Đăng ký';
                    btnSave.disabled = false;

                    if (data.status === 'success') { 
                        closeModal(modalRegister); 
                        alert(data.message || 'Đăng ký thành công!');
                    } else {
                        alert(data.message || 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Đã xảy ra lỗi khi kết nối với máy chủ. Vui lòng thử lại sau!');
                    btnSave.textContent = 'Đăng ký';
                    btnSave.disabled = false;
                });
            } else {
                alert("Vui lòng kiểm tra lại thông tin đăng ký!");
            }
        });

        document.getElementById('btnSendReset').addEventListener('click', function(e) {
            let isEmailValid = checkEmail(document.getElementById('forgotEmail'), document.getElementById('tbForgotEmail'));
            if (!isEmailValid) {
                e.preventDefault();
            }
        });

        const modalTerms = document.getElementById('modalTerms');
        const btnTerms = document.getElementById('btnTerms');
        const btnCloseTerms = document.getElementById('btnCloseTerms');

        // Mở modal điều khoản
        btnTerms.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(modalTerms);
        });

        // Đóng modal khi bấm nút Đã hiểu
        btnCloseTerms.addEventListener('click', () => closeModal(modalTerms));

        // Đóng modal khi click ra ngoài
        modalTerms.addEventListener('click', (event) => {
            if (event.target === modalTerms) {
                closeModal(modalTerms);
            }
        });
    });

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu</title>
    <link rel="stylesheet" href="{{$_ENV['URL_WEB_BASE']}}/css/tailwind.css">
</head>

<body class="bg-gradient-to-r from-gray-200 via-gray-100 to-gray-200 font-sans antialiased"
    data-url="{{$_ENV['URL_WEB_BASE']}}">
    @include('customer.layout.header')
    <div class="container mx-auto px-4 py-16">
        <div class="bg-white shadow-xl rounded-2xl p-8 md:p-12 max-w-3xl mx-auto">
            <div class="flex flex-col items-center text-center mb-8">
                <div class="bg-blue-500 rounded-full p-4 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-800">Đổi mật khẩu</h2>
                <p class="text-gray-500 mt-2">Thay đổi mật khẩu tài khoản của bạn</p>
            </div>

            <form id="changePasswordForm" class="space-y-6">
                <!-- Mật khẩu hiện tại -->
                <div class="relative">
                    <label for="current_password" class="block text-gray-700 font-medium mb-2">Mật khẩu hiện tại</label>
                    <input type="password" id="current_password" placeholder="Nhập mật khẩu hiện tại"
                        class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300">
                    <span id="tb_current_password" class="text-red-500 text-sm"></span>
                    <button type="button" class="absolute right-3 top-1/2 mt-4 transform -translate-y-1/2 text-gray-500"
                        onclick="togglePassword('current_password', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>

                <!-- Mật khẩu mới -->
                <div class="relative">
                    <label for="new_password" class="block text-gray-700 font-medium mb-2">Mật khẩu mới</label>
                    <input type="password" id="new_password" placeholder="Nhập mật khẩu mới"
                        class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300">
                    <span id="tb_new_password" class="text-red-500 text-sm"></span>
                    <button type="button" class="absolute right-3 top-1/2 mt-4 transform -translate-y-1/2 text-gray-500"
                        onclick="togglePassword('new_password', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>

                <!-- Xác nhận mật khẩu mới -->
                <div class="relative">
                    <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Xác nhận mật khẩu mới</label>
                    <input type="password" id="confirm_password" placeholder="Xác nhận mật khẩu mới"
                        class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300">
                    <span id="tb_confirm_password" class="text-red-500 text-sm"></span>
                    <button type="button" class="absolute right-3 top-1/2 mt-4 transform -translate-y-1/2 text-gray-500"
                        onclick="togglePassword('confirm_password', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>

                <div class="mt-8 flex justify-center">
                    <button type="submit" id="btnSave"
                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Đổi mật khẩu
                    </button>
                </div>
            </form>
        </div>
    </div>
    @include('customer.layout.footer')

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const svgPaths = btn.querySelectorAll('path');
    if (input.type === "password") {
        input.type = "text";
        svgPaths[0].setAttribute('d', 'M3 3l18 18'); // icon tắt
    } else {
        input.type = "password";
        svgPaths[0].setAttribute('d', 'M15 12a3 3 0 11-6 0 3 3 0 016 0z'); // icon mở
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const baseUrl = document.body.dataset.url;
    const form = document.getElementById('changePasswordForm');
    const current = document.getElementById('current_password');
    const newP = document.getElementById('new_password');
    const confirm = document.getElementById('confirm_password');
    const tbCurrent = document.getElementById('tb_current_password');
    const tbNew = document.getElementById('tb_new_password');
    const tbConfirm = document.getElementById('tb_confirm_password');

    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\[\]{}|;:',.<>?\/]).{8,}$/;

    function check(input, tb) {
        if (!input.value) {
            tb.textContent = 'Mật khẩu không được để trống!';
            return false;
        }
        if (input.value.length < 8) {
            tb.textContent = 'Mật khẩu phải có ít nhất 8 ký tự!';
            return false;
        }
        if (!regex.test(input.value)) {
            tb.textContent = 'Mật khẩu phải có chữ hoa, chữ thường, số, ký tự đặc biệt';
            return false;
        }
        tb.textContent = '';
        return true;
    }

    [current, newP, confirm].forEach((input, i) => {
        const tb = [tbCurrent, tbNew, tbConfirm][i];
        input.addEventListener('input', () => {
            tb.textContent = '';
            if (input === confirm && newP.value && confirm.value && newP.value !== confirm.value) {
                tbConfirm.textContent = 'Mật khẩu xác nhận không khớp!';
            }
        });
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        let valid = true;

        if (!check(current, tbCurrent)) valid = false;
        if (!check(newP, tbNew)) valid = false;
        if (!check(confirm, tbConfirm)) valid = false;
        if (newP.value !== confirm.value) {
            tbConfirm.textContent = 'Mật khẩu xác nhận không khớp!';
            valid = false;
        }
        if (!valid) return;

        try {
            const response = await fetch(baseUrl + "/api/doi-mat-khau", {
                method: 'PUT',
                headers: { "Content-Type": "application/json" },
                credentials: 'include',
                body: JSON.stringify({
                    currentPassword: current.value,
                    newPassword: newP.value
                })
            });

            const text = await response.text(); // đọc raw text
            let result = {};
            try {
                result = JSON.parse(text); // parse JSON
            } catch (err) {
                console.error("Server trả về dữ liệu không hợp lệ:", text);
                alert("Server trả về dữ liệu không hợp lệ. Vui lòng thử lại.");
                return;
            }

            if (response.ok && result.success) {
                alert(result.message || "Đổi mật khẩu thành công!");
                form.reset();
                tbCurrent.textContent = '';
                tbNew.textContent = '';
                tbConfirm.textContent = '';
            } else if (!result.success && result.message) {
                if (result.message.toLowerCase().includes('hiện tại')) {
                    tbCurrent.textContent = result.message;
                } else {
                    alert("Lỗi: " + result.message);
                }
            } else {
                alert("Đã xảy ra lỗi, vui lòng thử lại.");
            }

        } catch (err) {
            console.error(err);
            alert('Không thể kết nối server. Vui lòng thử lại.');
        }
    });
});
</script>

</body>

</html>

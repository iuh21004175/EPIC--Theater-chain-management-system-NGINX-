<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đặt lại mật khẩu</title>
  <link rel="icon" type="image/png" href="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756391269/logo_cinema_z2pcda.jpg">
  <link rel="stylesheet" href="{{$_ENV['URL_WEB_BASE']}}/css/tailwind.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

  <div class="bg-white rounded-lg shadow-md p-6 w-full max-w-md">
    <h2 class="text-2xl font-bold text-center text-red-600 mb-6">Đặt lại mật khẩu</h2>
    
    <form id="resetForm" class="space-y-4">
      <input type="hidden" id="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">

      <!-- Mật khẩu -->
      <div class="relative">
        <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu mới</label>
        <div class="relative mt-1">
          <input type="password" id="password"
            class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 pr-10">
          <!-- Icon con mắt -->
          <button type="button"
            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500"
            onmousedown="togglePasswordPress('password', this)"
            onmouseup="togglePasswordRelease('password', this)"
            onmouseleave="togglePasswordRelease('password', this)">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 eye-icon" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <!-- Mắt mở -->
              <path class="eye-open hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path class="eye-open hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 
                   8.268 2.943 9.542 7-1.274 4.057-5.065 
                   7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              <!-- Mắt đóng -->
              <path class="eye-closed" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 3l18 18M2.458 12C3.732 7.943 7.523 5 
                   12 5c1.772 0 3.432.52 4.858 1.416M21.542 
                   12A9.956 9.956 0 0112 19c-4.477 0-8.268-2.943-9.542-7" />
            </svg>
          </button>
        </div>
        <p id="passwordError" class="text-red-500 text-xs mt-1"></p>
      </div>

      <!-- Xác nhận mật khẩu -->
      <div class="relative">
        <label for="confirmPassword" class="block text-sm font-medium text-gray-700">Xác nhận mật khẩu</label>
        <div class="relative mt-1">
          <input type="password" id="confirmPassword"
            class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 pr-10">
          <!-- Icon con mắt -->
          <button type="button"
            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500"
            onmousedown="togglePasswordPress('confirmPassword', this)"
            onmouseup="togglePasswordRelease('confirmPassword', this)"
            onmouseleave="togglePasswordRelease('confirmPassword', this)">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 eye-icon" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <!-- Mắt mở -->
              <path class="eye-open hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path class="eye-open hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 
                   8.268 2.943 9.542 7-1.274 4.057-5.065 
                   7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              <!-- Mắt đóng -->
              <path class="eye-closed" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 3l18 18M2.458 12C3.732 7.943 7.523 5 
                   12 5c1.772 0 3.432.52 4.858 1.416M21.542 
                   12A9.956 9.956 0 0112 19c-4.477 0-8.268-2.943-9.542-7" />
            </svg>
          </button>
        </div>
        <p id="confirmPasswordError" class="text-red-500 text-xs mt-1"></p>
      </div>

      <button type="submit" 
        class="w-full bg-red-600 text-white py-2 rounded-lg font-semibold hover:bg-red-700 transition">
        Cập nhật mật khẩu
      </button>
    </form>

    <p id="message" class="mt-4 text-center text-sm"></p>
  </div>

  <script>
    // Show khi giữ chuột, ẩn khi nhả
    function togglePasswordPress(inputId, btn) {
      const input = document.getElementById(inputId);
      input.type = "text";
      const svg = btn.querySelector('svg');
      svg.querySelectorAll('.eye-open').forEach(el => el.classList.remove('hidden'));
      svg.querySelectorAll('.eye-closed').forEach(el => el.classList.add('hidden'));
    }

    function togglePasswordRelease(inputId, btn) {
      const input = document.getElementById(inputId);
      input.type = "password";
      const svg = btn.querySelector('svg');
      svg.querySelectorAll('.eye-open').forEach(el => el.classList.add('hidden'));
      svg.querySelectorAll('.eye-closed').forEach(el => el.classList.remove('hidden'));
    }

    const form = document.getElementById('resetForm');
    const message = document.getElementById('message');

    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    const kt = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\[\]{}|;:',.<>?\/]).{8,}$/;

    function checkPassword() {
      const value = passwordInput.value.trim();
      if (value === "") {
        passwordError.textContent = "Mật khẩu không được để trống!";
        return false;
      }
      if (value.length < 8) {
        passwordError.textContent = "Mật khẩu phải có ít nhất 8 ký tự!";
        return false;
      }
      if (!kt.test(value)) {
        passwordError.textContent = "Mật khẩu phải có chữ hoa, chữ thường, số, ký tự đặc biệt!";
        return false;
      }
      passwordError.textContent = "";
      return true;
    }

    function checkConfirmPassword() {
      const value = confirmPasswordInput.value.trim();
      if (value === "") {
        confirmPasswordError.textContent = "Vui lòng nhập lại mật khẩu!";
        return false;
      }
      if (value !== passwordInput.value.trim()) {
        confirmPasswordError.textContent = "Mật khẩu xác nhận không khớp!";
        return false;
      }
      confirmPasswordError.textContent = "";
      return true;
    }

    passwordInput.addEventListener("input", checkPassword);
    confirmPasswordInput.addEventListener("input", checkConfirmPassword);

    form.addEventListener('submit', async function(e) {
      e.preventDefault();

      const token = document.getElementById('token').value.trim();
      const password = passwordInput.value.trim();

      if (!checkPassword() || !checkConfirmPassword()) return;

      message.style.color = "black";
      message.textContent = 'Đang cập nhật mật khẩu...';

      try {
        const res = await fetch("{{$_ENV['URL_WEB_BASE']}}/api/reset-pass", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ token, password })
        });

        const text = await res.text();
        console.log("Server trả về:", text);

        let data;
        try {
          data = JSON.parse(text);
        } catch (err) {
          message.style.color = "red";
          message.textContent = "Server không trả về JSON:\n" + text;
          return;
        }

        if (data.success) {
          message.style.color = "green";
          message.innerHTML = `
            ${data.message}<br>
            <a href="{{$_ENV['URL_WEB_BASE']}}" 
               class="inline-block mt-3 px-4 py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition">
               Quay về trang chủ
            </a>
          `;
        } else {
          message.style.color = "red";
          message.textContent = data.message;
        }
      } catch (err) {
        console.error("Fetch error:", err);
        message.style.color = "red";
        message.textContent = "Lỗi kết nối server, vui lòng thử lại!";
      }
    });
  </script>
</body>
</html>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân</title>
    <link rel="icon" type="image/png" href="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756391269/logo_cinema_z2pcda.jpg">
    <link rel="stylesheet" href="{{$_ENV['URL_WEB_BASE']}}/css/tailwind.css">
</head>
<body class="bg-gradient-to-r from-gray-200 via-gray-100 to-gray-200 font-sans antialiased"
      data-url="{{$_ENV['URL_WEB_BASE']}}">
    @include('customer.layout.header')
    <div class="container mx-auto px-4 py-16">
        <div class="bg-white shadow-xl rounded-2xl p-8 md:p-12 max-w-3xl mx-auto">
            <div class="flex flex-col items-center text-center mb-8">
                <div class="bg-blue-500 rounded-full p-4 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-800">Thông tin cá nhân</h2>
                <p class="text-gray-500 mt-2">Chi tiết hồ sơ của bạn</p>
            </div>

            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-y-4 md:gap-x-6 items-center">
                    <label for="ho_ten" class="md:col-span-1 text-gray-700 px-4 font-medium text-right md:text-left">Họ và tên:</label>
                    <div class="md:col-span-2">
                        <input type="text" id="ho_ten" name="ho_ten" class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300" value="">
                        <span id="tb_ho_ten" class="text-red-500 text-sm mt-1 block"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-y-4 md:gap-x-6 items-center">
                    <label for="email" class="md:col-span-1 text-gray-700 px-4 font-medium text-right md:text-left">Email:</label>
                    <div class="md:col-span-2">
                        <input type="email" id="email" class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300" value="">
                        <span id="tb_email" class="text-red-500 text-sm"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-y-4 md:gap-x-6 items-center">
                    <label for="ngay_sinh" class="md:col-span-1 text-gray-700 px-4 font-medium text-right md:text-left">Ngày sinh:</label>
                    <div class="md:col-span-2">
                        <input type="date" id="ngay_sinh" class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300" value="">
                        <span id="tb_ngay_sinh" class="text-red-500 text-sm"></span>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-y-4 md:gap-x-6 items-center">
                    <label for="gioi_tinh" class="md:col-span-1 text-gray-700 px-4 font-medium text-right md:text-left">Giới tính:</label>
                    <div class="md:col-span-2">
                        <select id="gioi_tinh" class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300">
                            <option value="1">Nam</option>
                            <option value="0">Nữ</option>
                        </select>
                        <span id="tb_gioi_tinh" class="text-red-500 text-sm"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-y-4 md:gap-x-6 items-center">
                    <label for="phone" class="md:col-span-1 text-gray-700 px-4 font-medium text-right md:text-left">Số điện thoại:</label>
                    <div class="md:col-span-2">
                        <input type="tel" id="phone" class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300" value="">
                        <span id="tb_phone" class="text-red-500 text-sm"></span>
                    </div>
                </div>
            </div>
            <div class="mt-8 flex justify-center space-x-4">
                 <button id="btnUpdate" class="bg-red-600 hover:bg-red-700 mb-2 text-white font-bold py-3 px-6 rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Cập nhật thông tin
                </button>
            </div>
        </div>
    </div>
    @include('customer.layout.footer')
</body>
<script>
document.addEventListener("DOMContentLoaded", async () => {
    const baseUrl = document.body.dataset.url;

    // Fetch dữ liệu ban đầu
    try {
        const res = await fetch(baseUrl + "/api/thong-tin-ca-nhan");
        const result = await res.json();
        if (result.success && result.data) {
            const data = result.data;
            document.getElementById("ho_ten").value = data.ho_ten ?? "";
            document.getElementById("email").value = data.email ?? "";
            document.getElementById("ngay_sinh").value = data.ngay_sinh ?? "";
            document.getElementById("gioi_tinh").value = data.gioi_tinh ?? "";
            document.getElementById("phone").value = data.so_dien_thoai ?? "";
        }
    } catch (err) {
        console.error("Lỗi fetch:", err);
    }

    // Validation
    const checkName = () => {
        const val = document.getElementById("ho_ten").value.trim();
        const errEl = document.getElementById("tb_ho_ten");
        const kt = /^(([A-Z]{1})([a-z]+))(\s([A-Z]{1})([a-z]+)){1,}$/;
        if (!val) { errEl.textContent = "Họ tên không được để trống!"; return false; }
        if (!kt.test(val)) { errEl.textContent = "Họ tên phải có ít nhất 2 từ và mỗi từ viết hoa chữ cái đầu!"; return false; }
        errEl.textContent = ""; return true;
    };

    const checkEmail = () => {
        const val = document.getElementById("email").value.trim();
        const errEl = document.getElementById("tb_email");
        const kt = /^[a-zA-Z0-9](?:[a-zA-Z0-9._%+-]{0,62}[a-zA-Z0-9])?@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z]{2,})+$/;
        if (!val) { errEl.textContent = "Email không được để trống!"; return false; }
        if (!kt.test(val)) { errEl.textContent = "Email không hợp lệ!"; return false; }
        errEl.textContent = ""; return true;
    };

    const checkNgaySinh = () => {
        const val = document.getElementById("ngay_sinh").value;
        const errEl = document.getElementById("tb_ngay_sinh");
        if (!val) { errEl.textContent = "Ngày sinh không được để trống!"; return false; }
        const today = new Date();
        if (val > today.toISOString().split("T")[0]) { errEl.textContent = "Ngày sinh không được sau ngày hiện tại!"; return false; }
        const ns = new Date(val);
        let age = today.getFullYear() - ns.getFullYear();
        const m = today.getMonth() - ns.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < ns.getDate())) age--;
        if (age < 13) { errEl.textContent = "Bạn phải từ 13 tuổi trở lên!"; return false; }
        errEl.textContent = ""; return true;
    };

    const checkGender = () => {
        const val = document.getElementById("gioi_tinh").value;
        const errEl = document.getElementById("tb_gioi_tinh");
        if (val === "") { errEl.textContent = "Giới tính không được để trống!"; return false; }
        errEl.textContent = ""; return true;
    };

    const checkPhone = () => {
        const val = document.getElementById("phone").value.trim();
        const errEl = document.getElementById("tb_phone");
        const regex = /^(03|05|07|08|09)[0-9]{8}$/;

        if (!val) {
            errEl.textContent = "Số điện thoại không được để trống!";
            return false;
        }
        if (!regex.test(val)) {
            errEl.textContent = "Số điện thoại phải gồm 10 số và bắt đầu bằng 03, 05, 07, 08 hoặc 09!";
            return false;
        }

        errEl.textContent = "";
        return true;
    };

    // Blur events
    document.getElementById('ho_ten').addEventListener('blur', checkName);
    document.getElementById('email').addEventListener('blur', checkEmail);
    document.getElementById('ngay_sinh').addEventListener('blur', checkNgaySinh);
    document.getElementById('gioi_tinh').addEventListener('blur', checkGender);
    document.getElementById('phone').addEventListener('blur', checkPhone);

    // Click update
    document.getElementById("btnUpdate").addEventListener('click', async (e) => {
        e.preventDefault();
        if (!checkName() || !checkEmail() || !checkNgaySinh() || !checkGender()) return;

        const payload = {
            ho_ten: document.getElementById("ho_ten").value.trim(),
            email: document.getElementById("email").value.trim(),
            ngay_sinh: document.getElementById("ngay_sinh").value,
            gioi_tinh: document.getElementById("gioi_tinh").value,
            so_dien_thoai: document.getElementById("phone").value.trim()
        };

        try {
            const res = await fetch(baseUrl + "/api/thong-tin-ca-nhan", {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });

            const text = await res.text();

            const result = JSON.parse(text); 
            if (result.success) {
                alert("Cập nhật thông tin thành công!");
            } else {
                alert("Cập nhật thất bại: " + (result.message || "Lỗi server"));
            }
        } catch (err) {
            console.error("Lỗi fetch/JSON:", err);
            alert("Đã xảy ra lỗi khi cập nhật thông tin!");
        }
    });
});
</script>
</html>

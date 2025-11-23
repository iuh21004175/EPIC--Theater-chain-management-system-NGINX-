<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đồ ăn & Thức uống - EPIC CINEMAS</title>
    <link rel="stylesheet" href="{{$_ENV['URL_WEB_BASE']}}/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans min-h-screen flex flex-col">

    <!-- Header -->
    @include('customer.layout.header')

    <!-- Main -->
    <main class="container mx-auto max-w-screen-xl px-4 py-10 flex-1">
        <h2 class="text-2xl font-bold mb-6 text-gray-900">🍿 Đồ ăn & Thức uống</h2>
        <section id="product-list" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6"></section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-200">
        @include('customer.layout.footer')
    </footer>


    <!-- Modal chọn rạp -->
    <div id="cinemaModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-[90%] max-w-md p-6 relative">
            <h3 class="text-xl font-semibold mb-4 text-gray-800">🎬 Chọn rạp phim</h3>
            <select id="cinema-select" class="w-full border border-gray-300 rounded-lg px-4 py-2 mb-4 focus:border-red-600 outline-none">
                <option value="">-- Chọn rạp --</option>
            </select>
            <button id="choose-cinema" class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-500 transition">
                Xác nhận
            </button>
        </div>
    </div>

    <!-- Giỏ hàng -->
    <div class="fixed bottom-4 right-4 w-80 bg-white border border-gray-200 rounded-xl shadow-lg p-4">
        <h4 class="font-bold text-gray-900 mb-2">🛒 Giỏ hàng</h4>
        <ul id="cart-items" class="divide-y divide-gray-200 max-h-60 overflow-y-auto text-sm"></ul>
        <div class="flex items-center justify-between mt-3">
            <span class="font-semibold">Tổng:</span>
            <span id="cart-total" class="font-bold text-red-600">0 đ</span>
        </div>
        <button id="checkout-btn" class="mt-3 w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-500 transition">
            Thanh toán
        </button>
    </div>

    <!-- Modal Thanh toán -->
    <div id="checkoutModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg w-[90%] max-w-lg p-6">
            
            <!-- Nội dung checkout -->
            <div id="checkoutContent">
                <h3 class="text-xl font-bold mb-4">Xác nhận đơn hàng</h3>
                <!-- Rạp -->
                <div class="mb-4 p-3 bg-gray-100 rounded-lg">
                    <p class="font-semibold text-gray-800">🎬 Rạp: <span id="checkout-cinema" class="text-red-600"></span></p>
                </div>

                <!-- Danh sách sản phẩm -->
                <ul id="checkout-items" class="divide-y divide-gray-200 mb-4 text-sm"></ul>

                <!-- Tổng cộng -->
                <div class="flex justify-between font-semibold mb-4">
                    <span>Tổng cộng:</span>
                    <span id="checkout-total" class="text-red-600"></span>
                </div>

                <!-- QR Code -->
                <div class="flex flex-col items-center mb-4">
                    <p class="text-sm text-gray-600 mb-2">Quét mã QR để thanh toán</p>
                    <img id="checkout-qr" src="" alt="QR Code" class="w-40 h-40 border rounded-lg shadow-md">
                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-2">
                    <button onclick="closeCheckout()" class="px-4 py-2 bg-gray-200 rounded-lg">Hủy</button>
                    <button onclick="submitCheckout()" class="px-4 py-2 bg-red-600 text-white rounded-lg">Xác nhận</button>
                </div>
            </div>

            <!-- Box thành công (ẩn mặc định) -->
            <div id="successPayBox" class="hidden text-center">
                <h2 class="text-success flex justify-center items-center gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-check-circle text-success" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                    <path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05"/>
                    </svg>
                    Thanh toán thành công
                </h2>
                <p class="text-success">Chúc mừng bạn đã đặt món thành công!</p>
                <div class="mt-6 flex justify-center">
                    <a href="{{$_ENV['URL_WEB_BASE']}}" class="px-6 py-2 bg-red-600 text-white rounded-lg shadow hover:bg-red-500 transition">
                        Về trang chủ
                    </a>
                </div>
            </div>

        </div>
        </div>

    <script>
document.addEventListener("DOMContentLoaded", () => {
    document.getElementById('btn-open-chat').style.display = 'none';
    const urlMinio = "{{ $_ENV['MINIO_SERVER_URL'] }}";
    const baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}";
    const cinemaModal = document.getElementById("cinemaModal");
    const chooseBtn = document.getElementById("choose-cinema");
    const cinemaSelect = document.getElementById("cinema-select");
    const productList = document.getElementById("product-list");
    const cartItems = document.getElementById("cart-items");
    const cartTotal = document.getElementById("cart-total");
    const checkoutBtn = document.getElementById("checkout-btn");

    const checkoutModal = document.getElementById("checkoutModal");
    const checkoutItems = document.getElementById("checkout-items");
    const checkoutTotal = document.getElementById("checkout-total");
    const checkoutCinema = document.getElementById("checkout-cinema");
    const checkoutQR = document.getElementById("checkout-qr");
    const checkoutContent = document.getElementById("checkoutContent");
    const successPayBox = document.getElementById("successPayBox");
    const modalLogin = document.getElementById('modalLogin');
    const body = document.body;

    function openModal(modal) { // Hiển thị modal đăng nhập
        modal.classList.add('is-open');
        body.classList.add('modal-open');
    }

    let cart = {};

    // Render rạp vào select
    fetch(baseUrl + '/api/rap-phim-khach')
    .then(res => res.json())
    .then(result => {
        const cinemas = result.data || [];
        cinemaSelect.innerHTML = '<option value="">-- Chọn rạp --</option>';
        cinemas.forEach(c => {
            const opt = document.createElement("option");
            opt.value = c.id;
            opt.textContent = c.ten;
            cinemaSelect.appendChild(opt);
        });
    })
    .catch(err => console.error("Lỗi tải rạp:", err));

    // Khi chọn rạp → load sản phẩm
    chooseBtn.addEventListener("click", () => {
        let cinemaId = cinemaSelect.value;
        if (!cinemaId) return alert("Vui lòng chọn rạp!");
        cinemaModal.classList.add("hidden");

        fetch(`${baseUrl}/api/lay-san-pham-khach/${cinemaId}`)
        .then(res => res.json())
        .then(result => {
            const products = result.data || []; 
            productList.innerHTML = "";
            if (products.length === 0) {
                productList.innerHTML = `<p class="col-span-full text-center text-gray-500">Chưa có sản phẩm cho rạp này.</p>`;
            } else {
                products.forEach(p => {
                    productList.innerHTML += `
                    <div class="bg-white rounded-xl shadow hover:shadow-md transition overflow-hidden">
                       <div class="w-32 h-32">
                            <img src="${urlMinio}/${p.hinh_anh}" alt="${p.ten}" 
                                class="w-full h-full object-cover rounded-md">
                            </div>
                        <div class="p-3">
                            <h5 class="font-semibold text-gray-900">${p.ten}</h5>
                            <p class="text-red-600 font-bold">${parseInt(p.gia).toLocaleString()} đ</p>
                            <div class="flex items-center gap-3 mt-2">
                                <button class="minus bg-gray-200 w-10 h-10 text-lg font-bold flex items-center justify-center rounded-md hover:bg-red-500 hover:text-white transition"
                                    data-id="${p.id}" data-price="${p.gia}" data-name="${p.ten}">
                                    -
                                </button>
                                <span id="qty-${p.id}" class="w-8 text-center font-semibold text-lg">0</span>
                                <button class="plus bg-gray-200 w-10 h-10 text-lg font-bold flex items-center justify-center rounded-md hover:bg-red-500 hover:text-white transition"
                                    data-id="${p.id}" data-price="${p.gia}" data-name="${p.ten}">
                                    +
                                </button>
                            </div>
                        </div>
                    </div>`;
                });
            }
        })
        .catch(err => {
            console.error("Lỗi tải sản phẩm:", err);
            productList.innerHTML = `<p class="col-span-full text-center text-red-500">Không thể tải sản phẩm!</p>`;
        });
    });

    // Event tăng/giảm số lượng
    document.addEventListener("click", e => {
        if (e.target.classList.contains("plus")) {
            let id = e.target.dataset.id;
            let price = parseInt(e.target.dataset.price);
            let name = e.target.dataset.name;
            if (!cart[id]) cart[id] = { qty: 0, price: price, name: name };
            cart[id].qty++;
            updateCart();
        }
        if (e.target.classList.contains("minus")) {
            let id = e.target.dataset.id;
            if (cart[id]) {
                cart[id].qty--;
                if (cart[id].qty <= 0) delete cart[id];
                updateCart();
            }
        }
    });

    // Event trong giỏ hàng
    document.addEventListener("click", e => {
        if (e.target.classList.contains("cart-plus")) {
            let id = e.target.dataset.id;
            cart[id].qty++;
            updateCart();
        }
        if (e.target.classList.contains("cart-minus")) {
            let id = e.target.dataset.id;
            cart[id].qty--;
            if (cart[id].qty <= 0) delete cart[id];
            updateCart();
        }
        if (e.target.closest(".cart-remove")) {
            let id = e.target.closest(".cart-remove").dataset.id;
            delete cart[id];
            updateCart();
        }
    });

    // Nút thanh toán
    checkoutBtn.addEventListener("click", async () => {
        if (Object.keys(cart).length === 0) {
            alert("Giỏ hàng đang trống, vui lòng chọn sản phẩm!");
            return;
        }

        try {
            // --- Check login trước ---
            const resLogin = await fetch(`${baseUrl}/api/check-login`);
            const dataLogin = await resLogin.json();

            if (dataLogin.status !== "success") {
                openModal(modalLogin); 
                alert("Vui lòng đăng nhập!");
                return;
            }

            // Nếu đã login thì mới chạy tiếp luồng tạo đơn hàng
            checkoutItems.innerHTML = "";
            let total = 0;
            const selectedFood = [];

            for (let id in cart) {
                let item = cart[id];
                checkoutItems.innerHTML += `
                    <li class="flex justify-between py-2">
                        <span>${item.name} x${item.qty}</span>
                        <span>${(item.qty * item.price).toLocaleString()} đ</span>
                    </li>`;
                total += item.qty * item.price;
                selectedFood.push({id, quantity: item.qty, gia: item.price, ten: item.name});
            }

            checkoutTotal.textContent = total.toLocaleString() + " đ";
            const cinemaName = cinemaSelect.options[cinemaSelect.selectedIndex].text;
            checkoutCinema.textContent = cinemaName;

            // --- Tạo đơn hàng ---
            let cinemaId = cinemaSelect.value;
            const maVe = Math.floor(100000000 + Math.random() * 900000000); // random 9 số
            const resDH = await fetch(`${baseUrl}/api/tao-don-hang`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    suat_chieu_id: null, // thay id suất chiếu nếu có
                    tong_tien: total,
                    ma_ve: maVe,
                    phuong_thuc_mua: 3,
                    trang_thai: 1,
                    rap_id: cinemaId 
                })
            });
            const jDH = await resDH.json();
            if (!jDH.success) throw new Error(jDH.message || "Lỗi tạo đơn hàng");
            const donhangId = jDH.data.id;

            // --- Tạo chi tiết đơn hàng ---
            for (const f of selectedFood) {
                const resSP = await fetch(`${baseUrl}/api/tao-chi-tiet-don-hang`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        donhang_id: donhangId,
                        sanpham_id: f.id,
                        so_luong: f.quantity,
                        don_gia: f.gia,
                        thanh_tien: f.gia * f.quantity
                    })
                });
                const jSP = await resSP.json();
                if (!jSP.success) throw new Error(jSP.message || "Lỗi lưu chi tiết đơn hàng");
            }

            // --- Generate QR Sepay ---
            checkoutQR.src = `https://qr.sepay.vn/img?bank=TPBank&acc=10001198354&template=compact&amount=${total}&des=DH${donhangId}`;

            checkoutModal.classList.remove("hidden");
            const interval = setInterval(async () => {
                try {
                    const res = await fetch(`${baseUrl}/api/lay-trang-thai`, {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ donhang_id: donhangId })
                    });
                    const status = await res.json();
                    if (status.payment_status === "Paid") {
                        checkoutContent.classList.add("hidden");   // ẩn nội dung cũ
                        successPayBox.classList.remove("hidden"); // hiện box thành công
                        clearInterval(interval);
                    }
                } catch (err) {
                    console.error("Lỗi check thanh toán:", err);
                }
            }, 3000);

        } catch (err) {
            console.error(err);
            alert("Có lỗi khi thanh toán: " + err.message);
        }
    });

    // Cập nhật giỏ hàng
    function updateCart() {
        cartItems.innerHTML = "";
        let total = 0;

        for (let id in cart) {
            let item = cart[id];
            if (item.qty <= 0) continue;

            const qtySpan = document.getElementById("qty-" + id);
            if (qtySpan) qtySpan.textContent = item.qty;

            cartItems.innerHTML += `
            <li class="flex justify-between items-center py-2">
                <div class="flex-1">
                    <span class="font-medium">${item.name}</span>
                    <p class="text-xs text-gray-500">${item.price.toLocaleString()} đ</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="cart-minus bg-gray-200 w-10 h-10 text-lg font-bold flex items-center justify-center rounded-md hover:bg-red-500 hover:text-white transition" data-id="${id}">-</button>
                    <span class="w-6 text-center">${item.qty}</span>
                    <button class="cart-plus bg-gray-200 w-10 h-10 text-lg font-bold flex items-center justify-center rounded-md hover:bg-red-500 hover:text-white transition" data-id="${id}">+</button>
                    <button class="cart-remove text-red-500 ml-2" data-id="${id}"><i class="fa fa-trash"></i></button>
                </div>
            </li>`;
            total += item.qty * item.price;
        }

        cartTotal.textContent = total.toLocaleString() + " đ";
    }

    window.closeCheckout = function() {
        checkoutModal.classList.add("hidden");
    }

    window.submitCheckout = function() {
        alert("Thanh toán thành công!");
        cart = {};
        updateCart();
        localStorage.removeItem("epic_cart");
        checkoutModal.classList.add("hidden");
    }
});
</script>

</body>
</html>

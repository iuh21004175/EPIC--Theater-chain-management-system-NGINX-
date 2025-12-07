<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đặt vé - EPIC CINEMAS</title>
<link rel="stylesheet" href="{{ $_ENV['URL_WEB_BASE'] }}/css/tailwind.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

@include('customer.layout.header')

<main>
    <!-- Thông tin phim -->
    <section id="thongTinPhim" class="container mx-auto max-w-screen-xl px-4 mt-6"></section>

    <!-- Nội dung phim -->
    <section id="noiDungPhim" class="w-full px-4 mt-8"></section>

    <!-- Lịch chiếu -->
    <section class="w-full px-4 mt-8">
        <div class="w-full max-w-screen-xl mx-auto bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center mb-4">
                <div class="w-1 h-6 bg-red-600 mr-2"></div>
                <h3 class="text-xl font-bold">Lịch Chiếu</h3>
            </div>

            <!-- Tabs chọn ngày -->
            <div class="flex items-center space-x-2 mb-6">
                <button id="prevDay" class="text-gray-400 hover:text-red-500 transition-colors p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <div id="dayTabs" class="flex space-x-3 overflow-x-hidden flex-1"></div>

                <button id="nextDay" class="text-gray-400 hover:text-red-500 transition-colors p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- <div class="w-48">
                    <label for="citySelect" class="block text-gray-700 font-semibold mb-1 text-sm">Chọn Thành Phố</label>
                    <select id="citySelect" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 text-sm">
                        <option value="tq">Toàn quốc</option>
                        <option value="sg">TP. Hồ Chí Minh</option>
                        <option value="hn">Hà Nội</option>
                        <option value="dn">Đà Nẵng</option>
                    </select>
                </div> -->

                <div class="w-64">
                    <label for="rapSelect" class="block text-gray-700 font-semibold mb-1 text-sm">Chọn Rạp</label>
                    <select id="rapSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 text-sm">
                        <option value="">Tất cả rạp</option>
                    </select>
                </div>
            </div>

            <hr class="border-t-2 border-red-500 w-full mx-auto mb-10">
            <div class="w-full mb-4">
                <label class="block text-gray-700 font-semibold mb-1 text-sm">Chọn khung giờ</label>
                <div id="timeFilterButtons" class="flex gap-2">
                    <button data-time="" class="time-btn px-3 py-2 bg-gray-100 rounded-lg text-sm text-gray-700 hover:bg-red-500 hover:text-white transition-colors">Tất cả</button>
                    <button data-time="8-12" class="time-btn px-3 py-2 bg-gray-100 rounded-lg text-sm text-gray-700 hover:bg-red-500 hover:text-white transition-colors">08:00 - 12:00</button>
                    <button data-time="12-16" class="time-btn px-3 py-2 bg-gray-100 rounded-lg text-sm text-gray-700 hover:bg-red-500 hover:text-white transition-colors">12:00 - 16:00</button>
                    <button data-time="16-20" class="time-btn px-3 py-2 bg-gray-100 rounded-lg text-sm text-gray-700 hover:bg-red-500 hover:text-white transition-colors">16:00 - 20:00</button>
                    <button data-time="20-24" class="time-btn px-3 py-2 bg-gray-100 rounded-lg text-sm text-gray-700 hover:bg-red-500 hover:text-white transition-colors">20:00 - 24:00</button>
                </div>
            </div>

            <div id="suatChieu" class="space-y-6"></div>
        </div>
    </section>

    <!-- Bình luận & đánh giá -->
    <section class="w-full px-4 mt-8 mb-8">
      <div class="w-full max-w-screen-xl mx-auto bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold mb-4">Bình luận & Đánh giá</h3>

        <form class="mb-6 space-y-4 p-4 border rounded-lg shadow-sm bg-white" id="commentForm">
          <div class="flex items-center gap-4">
                <?php if (isset($_SESSION['user'])): 
                    $user = $_SESSION['user']; 
                    $hoten = $user['ho_ten'];
                ?>
                <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 font-bold"><?php echo strtoupper($hoten[0]); ?></div>
                <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($hoten); ?></span>
                <?php endif; ?>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-sm font-medium">Đánh giá:</span>
            <div class="flex gap-1" id="starRating">
              <button type="button" data-value="1" class="text-2xl text-gray-300 hover:text-yellow-400">★</button>
              <button type="button" data-value="2" class="text-2xl text-gray-300 hover:text-yellow-400">★</button>
              <button type="button" data-value="3" class="text-2xl text-gray-300 hover:text-yellow-400">★</button>
              <button type="button" data-value="4" class="text-2xl text-gray-300 hover:text-yellow-400">★</button>
              <button type="button" data-value="5" class="text-2xl text-gray-300 hover:text-yellow-400">★</button>
            </div>
            <span id="ratingValue" class="ml-2 font-semibold text-gray-700">5</span>
          </div>

          <textarea placeholder="Viết bình luận của bạn..." name="comment" rows="3"
            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400"></textarea>

          <div class="mt-4 flex justify-end">
            <button type="submit"
              class="btn-gui px-6 py-2 bg-red-500 text-white font-semibold rounded-lg shadow hover:bg-red-600">Gửi bình luận</button>
          </div>
        </form>

        <div id="commentList" class="space-y-4">
            
        </div>
      </div>
    </section>

</main>

@include('customer.layout.footer')

<!-- Modal Trailer -->
<div id="trailerModal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 hidden">
  <div class="bg-black rounded-xl shadow-lg w-[90%] max-w-3xl relative">
    <!-- Nút đóng -->
    <button id="closeModal" 
      class="absolute top-2 right-2 text-white text-2xl font-bold hover:text-red-500">&times;</button>

    <!-- Video -->
    <div class="aspect-video">
      <iframe id="trailerIframe" class="w-full h-full rounded-xl"
        src="" title="Trailer" frameborder="0"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
        allowfullscreen>
      </iframe>
    </div>
  </div>
</div>
<?php
 $user = $_SESSION['user'] ?? null;
?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const urlMinio = "{{ $_ENV['MINIO_SERVER_URL'] }}";
    const baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}";
    const salt = "{{ $_ENV['URL_SALT'] }}";

    // DOM elements
    const trailerModal = document.getElementById("trailerModal");
    const closeModal = document.getElementById("closeModal");
    const trailerIframe = document.getElementById("trailerIframe");
    const rapSelect = document.getElementById('rapSelect');
    const dayTabs = document.getElementById('dayTabs');
    const nextBtn = document.getElementById('nextDay');
    const prevBtn = document.getElementById('prevDay');
    const suatChieuDiv = document.getElementById('suatChieu');
    const stars = document.querySelectorAll('#starRating button');
    const ratingValue = document.getElementById('ratingValue');
    const commentForm = document.getElementById('commentForm');
    const commentList = document.getElementById('commentList');

    const modalLogin = document.getElementById('modalLogin');
    const body = document.body;
    const timeFilterButtons = document.querySelectorAll('.time-btn');

    timeFilterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Bỏ active cũ
            timeFilterButtons.forEach(b => {
                b.classList.remove('bg-red-600', 'text-white');
                b.classList.add('bg-gray-100', 'text-gray-700');
            });

            // Set active mới
            btn.classList.remove('bg-gray-100', 'text-gray-700');
            btn.classList.add('bg-red-600', 'text-white');

            // Lọc lại suất chiếu
            const timeValue = btn.dataset.time; // "" hoặc "8-12"
            renderSuatChieu(timeValue);
        });
    });

    function openModal(modal) { // Hiển thị modal đăng nhập
        modal.classList.add('is-open');
        body.classList.add('modal-open');
    }

    const currentUserId = <?php echo $user ? (int)$user['id'] : 'null'; ?>;

    let currentRating = 5;

    function updateStars(rating) {
        stars.forEach(star => {
            if (star.dataset.value <= rating) {
                star.classList.add('text-yellow-400');
                star.classList.remove('text-gray-300');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            }
        });
        ratingValue.textContent = rating;
    }

    stars.forEach(star => {
        star.addEventListener('click', () => {
            currentRating = star.dataset.value;
            updateStars(currentRating);
        });
    });

    updateStars(currentRating);

    // Trailer modal
    closeModal.addEventListener("click", () => {
        trailerModal.classList.add("hidden");
        trailerIframe.src = "";
    });

    trailerModal.addEventListener("click", (e) => {
        if (e.target === trailerModal) {
            trailerModal.classList.add("hidden");
            trailerIframe.src = "";
        }
    });

    function getYouTubeEmbedUrl(url) {
        if (!url) return "";
        const regex = /(?:youtube\.com\/(?:.*v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/;
        const match = url.match(regex);
        if (match && match[1]) return "https://www.youtube.com/embed/" + match[1];
        return url;
    }

    function getYouTubeThumbnail(url) {
        if (!url) return "";
        const regex = /(?:youtube\.com\/(?:.*v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/;
        const match = url.match(regex);
        if (match && match[1]) {
            // Trả về thumbnail chất lượng cao của YouTube
            return `https://img.youtube.com/vi/${match[1]}/maxresdefault.jpg`;
        }
        return "";
    }
    let idRapPhim = "";

    // Load rạp
    function loadRap() {
        if (!rapSelect) return;
        fetch(baseUrl + "/api/rap-phim-khach")
            .then(res => res.json())
            .then(data => {
                rapSelect.innerHTML = '<option value="">Tất cả rạp</option>';
                if (data.success && data.data.length) {
                    data.data.forEach(rap => {
                        const option = document.createElement("option");
                        option.value = rap.id;
                        option.textContent = rap.ten;
                        rapSelect.appendChild(option);
                    });
                } else {
                    rapSelect.innerHTML = '<option value="">Không có rạp</option>';
                }
            })
            .catch(err => {
                console.error("Lỗi load rạp:", err);
                rapSelect.innerHTML = '<option value="">Lỗi tải rạp</option>';
            });
    }
    loadRap();

    rapSelect.addEventListener("change", function () {
        idRapPhim = this.value; // gán id rạp được chọn
        console.log("ID rạp đã chọn:", idRapPhim);

        // mỗi khi chọn rạp thì load lại suất chiếu
        loadSuatChieu();
    });

    function base64Decode(str) { return decodeURIComponent(escape(atob(str))); }
    function base64Encode(str) { return btoa(unescape(encodeURIComponent(str))); }

    const pathParts = window.location.pathname.split("/");
    const slugWithId = pathParts[pathParts.length - 1];  
    const encodedId = slugWithId.split("-").pop();
    const decoded = base64Decode(encodedId); 
    const idPhim = decoded.replace(salt, "");   

    let allSuatChieu = [];
    let lastComments = [];

    function renderSuatChieu(timeValue = "") {
        const selectedDate = getSelectedDate();
        console.log("Ngày đã chọn:", selectedDate);

        // Lọc theo ngày
        let filtered = allSuatChieu.filter(suat => suat.batdau.split(" ")[0] === selectedDate);

        // Lọc theo khung giờ nếu có
        if (timeValue) {
            const [startHour, endHour] = timeValue.split("-").map(Number);
            filtered = filtered.filter(suat => {
                const hour = new Date(suat.batdau).getHours();
                return hour >= startHour && hour < endHour;
            });
        }

        if (!filtered.length) {
            suatChieuDiv.innerHTML = '<p class="text-gray-500">Chưa có suất chiếu cho ngày này.</p>';
            return;
        }

        // Nhóm theo rạp
        const groupedByRap = {};
        filtered.forEach(suat => {
            const rapName = suat.phong_chieu.rap_chieu_phim.ten || "Không xác định";
            if (!groupedByRap[rapName]) groupedByRap[rapName] = [];
            groupedByRap[rapName].push(suat);
        });

        // Render ra HTML
        suatChieuDiv.innerHTML = Object.entries(groupedByRap).map(([rapName, suats]) => {
            const groupedByLoai = {};
            suats.forEach(suat => {
                const loaiChieu = (suat.phong_chieu.loai_phongchieu || "Không xác định").toUpperCase();
                if (!groupedByLoai[loaiChieu]) groupedByLoai[loaiChieu] = [];
                groupedByLoai[loaiChieu].push(suat);
            });

            const loaiHtml = Object.entries(groupedByLoai).map(([loaiChieu, suatsLoai]) => {
                const suatHtml = suatsLoai.map(suat => {
                    const batDau = new Date(suat.batdau).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                    return `<button type="button" class="suat-btn px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-red-500 hover:text-white transition-colors"
                        data-suat-id="${suat.id}" data-phong-id="${suat.phong_chieu.id}" data-rap-id="${suat.phong_chieu.rap_chieu_phim.id}">${batDau}</button>`;
                }).join(' ');
                return `<div class="flex items-center mb-2"><span class="font-medium mr-4 min-w-[80px]">${loaiChieu}</span><div class="flex flex-wrap gap-2">${suatHtml}</div></div>`;
            }).join('');

            return `<div class="bg-gray-50 p-4 rounded-xl shadow-sm mb-6"><h4 class="text-lg font-semibold mb-4" data-phong-id="${suats[0].phong_chieu.id}">${rapName}</h4>${loaiHtml}</div><hr class="border-t-2 border-grey-500 w-full mx-auto mb-10">`;
        }).join('');

        // Gán sự kiện click
        document.querySelectorAll('.suat-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.suat-btn').forEach(b => {
                    b.classList.remove('bg-red-600', 'text-white');
                    b.classList.add('bg-white', 'text-gray-700');
                });
                btn.classList.remove('bg-white', 'text-gray-700');
                btn.classList.add('bg-red-600', 'text-white');

                const suatId = btn.dataset.suatId;
                const encoded = base64Encode(suatId + salt);

                fetch(`${baseUrl}/api/check-login`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === "success") {
                            window.location.href = `${baseUrl}/so-do-ghe/${encoded}`;
                        } else {
                            openModal(modalLogin);
                            alert("Vui lòng đăng nhập!");
                        }
                    }).catch(err => { console.error(err); alert("Không thể xác thực đăng nhập"); });
            });
        });
    }

    function loadSuatChieu() {
        const selectedDate = getSelectedDate();
        fetch(`${baseUrl}/api/suat-chieu-khach?ngay=${selectedDate}&id_phim=${idPhim}&id_rapphim=${idRapPhim}`)
            .then(res => res.json())
            .then(data => {
                allSuatChieu = Array.isArray(data.data) ? data.data : [];
                renderSuatChieu();
            }).catch(err => console.error("Lỗi load suất chiếu:", err));
    }

    function loadThongTinPhim(phim) {
        const thumbnailUrl = getYouTubeThumbnail(phim.trailer_url) || `${urlMinio}/${phim.poster_url}`;
        const html = `
            <div class="relative w-full h-72 md:h-80 lg:h-96 bg-black">
                <img src="${thumbnailUrl}" alt="${phim.ten_phim}" class="w-full h-full object-cover opacity-70">
                <div class="absolute inset-0 flex items-center justify-center">
                    <button type="button" data-url="${getYouTubeEmbedUrl(phim.trailer_url)}" class="trailer-btn flex items-center justify-center w-[320px] h-[100px] rounded-lg text-white font-semibold px-4 py-2 text-sm transition-all duration-300"> <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle-play" class="w-12 h-12 mr-3" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"> <path fill="currentColor" d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zM188.3 147.1c-7.6 4.2-12.3 12.3-12.3 20.9V344c0 8.7 4.7 16.7 12.3 20.9s16.8 4.1 24.3-.5l144-88c7.1-4.4 11.5-12.1 11.5-20.5s-4.4-16.1-11.5-20.5l-144-88c-7.4-4.5 -16.7-4.7-24.3-.5z"></path> </svg> </button>
                </div>
            </div>
            <div class="container mx-auto max-w-4xl px-4 mt-6 relative">
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="w-full md:w-1/3 flex-shrink-0 -mt-16 md:-mt-24">
                        <img src="${urlMinio}/${phim.poster_url}" alt="${phim.ten_phim}" class="w-full rounded-xl shadow-lg">
                    </div>
                    <div class="w-full md:w-2/3 bg-white rounded-xl shadow-lg p-6">
                        <h1 class="text-3xl md:text-4xl font-bold">${phim.ten_phim} <span class="text-sm px-2 py-1 bg-red-600 text-white font-bold rounded">${phim.do_tuoi}</span></h1>
                        <p><strong>Thời lượng:</strong> ${phim.thoi_luong} phút | <strong>Khởi chiếu:</strong> ${new Date(phim.ngay_cong_chieu).toLocaleDateString("vi-VN")}</p>
                        <div class="flex items-center mt-2">
                            <svg class="w-5 h-5 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 576 512"> <path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/> </svg>
                            <span id="averageRating" class="text-gray-800 font-semibold text-sm md:text-base">0.0 (0 votes)</span>
                        </div>
                        <p><strong>Quốc gia:</strong> ${phim.quoc_gia}</p>
                        <p><strong>Thể loại:</strong> ${phim.the_loai.map(t=>t.the_loai.ten).join(", ")}</p>
                        <p><strong>Đạo diễn:</strong> ${phim.dao_dien}</p>
                        <p><strong>Diễn viên:</strong> ${phim.dien_vien}</p>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('thongTinPhim').innerHTML = html;

        document.querySelectorAll(".trailer-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const url = btn.getAttribute("data-url");
                if (url) {
                    trailerIframe.src = url + (url.includes("?") ? "&" : "?") + "autoplay=1";
                    trailerModal.classList.remove("hidden");
                }
            });
        });
    }

    function loadNoiDungPhim(phim) {
        const html = `<div class="w-full max-w-screen-xl mx-auto bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold mb-2">Nội dung phim</h3>
            <p class="text-gray-700">${phim.mo_ta}</p>
        </div>`;
        document.getElementById('noiDungPhim').innerHTML = html;
    }

    function updateStars(rating, container = stars) {
        container.forEach(star => {
            if (parseInt(star.dataset.value) <= rating) {
                star.classList.add('text-yellow-400');
                star.classList.remove('text-gray-300');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            }
        });
        ratingValue.textContent = rating;
    }
    stars.forEach(star => star.addEventListener('click', () => {
        currentRating = parseInt(star.dataset.value);
        updateStars(currentRating);
    }));
    updateStars(currentRating);

    // Escape HTML to avoid XSS
    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Load danh sách bình luận
    function loadDanhSachCmt(danhGia, currentUserId) {
        if (!Array.isArray(danhGia)) danhGia = []; // đảm bảo luôn là mảng
        lastComments = danhGia;

        const averageRatingSpan = document.getElementById('averageRating');
        if (danhGia.length === 0) {
            // commentList.innerHTML = '<p class="text-gray-500">Chưa có bình luận nào.</p>';
            if (averageRatingSpan) averageRatingSpan.textContent = '0.0 (0 votes)';
            return;
        }

        const totalStars = danhGia.reduce((sum, cmt) => sum + (cmt.so_sao || 0), 0);
        const avgStars = (totalStars / danhGia.length).toFixed(1);
        if (averageRatingSpan) averageRatingSpan.textContent = `${avgStars} (${danhGia.length} votes)`;

        const html = danhGia.map(cmt => {
            const starsStr = '★'.repeat(cmt.so_sao) + '☆'.repeat(5 - cmt.so_sao);
            const ngayGui = new Date(cmt.created_at || cmt.ngay_tao).toLocaleString('vi-VN', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });

            let actionButtons = '';
            if (currentUserId !== null && cmt.khachhang_id === currentUserId) {
                actionButtons = `
                    <div class="mt-2 flex gap-2 text-sm">
                        <button class="text-blue-500 hover:underline" onclick="editComment(${cmt.id})">Sửa</button>
                        <button class="text-red-500 hover:underline" onclick="deleteComment(${cmt.id})">Xóa</button>
                    </div>
                `;
            }

            return `<div class="p-4 bg-gray-50 rounded-lg shadow-sm" id="cmt-${cmt.id}">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 font-bold">
                        ${cmt.khach_hang?.ho_ten?.charAt(0).toUpperCase() || '?'}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">${cmt.khach_hang?.ho_ten || 'Khách'}</p>
                        <div class="flex text-sm text-yellow-400">${starsStr}</div>
                    </div>
                </div>
                <div class="comment-body text-gray-700" id="cmt-body-${cmt.id}">${escapeHtml(cmt.cmt)}</div>
                <p class="text-gray-400 text-xs mt-1">${ngayGui}</p>
                ${actionButtons}
            </div>`;
        }).join('');
        commentList.innerHTML = html;
    }
    function fetchComments() {
        fetch(baseUrl + "/api/doc-danh-gia/" + idPhim)
            .then(res => res.json())
            .then(data => { 
                if (data.success) {
                    loadDanhSachCmt(data.data, currentUserId);
                } else {
                    commentList.innerHTML = '<p class="text-gray-500">Không tải được bình luận.</p>';
                }
            })
            .catch(err => console.error("Lỗi load bình luận:", err));
    }

    // Thêm bình luận
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const commentTextarea = commentForm.querySelector('textarea[name="comment"]');
            const comment = commentTextarea.value.trim();
            if (!comment) return alert('Nội dung bình luận không được rỗng.');

            fetch(`${baseUrl}/api/check-login`)
            .then(res => res.json())
            .then(loginData => {
                if (loginData.status !== "success") throw "not logged in";

                const userName = loginData.user?.ho_ten || 'Khách';
                const userInitial = userName.charAt(0).toUpperCase();

                return fetch(`${baseUrl}/api/them-danh-gia`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        phim_id: idPhim, 
                        so_sao: parseInt(ratingValue.textContent), 
                        cmt: comment 
                    })
                })
                .then(res => res.json())
                .then(data => {
            if (!data.success) return alert('Gửi thất bại: ' + (data.message || 'Server trả về lỗi'));

            const newComment = data.data;
            lastComments.push(newComment);

            const commentList = document.getElementById('commentList');
            if (commentList) {
                const starsStr = Array.from({length: 5}, (_, i) => 
                    `<span class="${i < (newComment.so_sao || 0) ? 'text-yellow-400' : 'text-gray-300'}">★</span>`
                ).join('');

                const now = new Date();
                    const ngayGui = `${now.getHours().toString().padStart(2,'0')}:${now.getMinutes().toString().padStart(2,'0')} ${now.getDate().toString().padStart(2,'0')}/${(now.getMonth()+1).toString().padStart(2,'0')}/${now.getFullYear()}`;

                const actionButtons = `
                    <div class="flex gap-2 mt-1">
                        <div class="mt-2 flex gap-2 text-sm">
                            <button onclick="editComment(${newComment.id})" class="text-blue-500 hover:underline">Sửa</button>
                            <button onclick="deleteComment(${newComment.id})" class="text-red-500 hover:underline">Xóa</button>
                        </div>
                    </div>
                `;

                const div = document.createElement('div');
                div.id = `cmt-${newComment.id}`;
                div.innerHTML = `
                    <div class="p-4 bg-gray-50 rounded-lg shadow-sm">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 font-bold">
                                ${userInitial}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">${userName}</p>
                                <div class="flex text-sm text-yellow-400">${starsStr}</div>
                            </div>
                        </div>
                        <div class="comment-body text-gray-700" id="cmt-body-${newComment.id}">
                            ${escapeHtml(newComment.cmt)}
                        </div>
                        <p class="text-gray-400 text-xs mt-1">${ngayGui}</p>
                        ${actionButtons}
                    </div>
                `;
                commentList.prepend(div);
            }

            // Reset form
            commentTextarea.value = '';
            currentRating = 5;
            updateStars(currentRating);
            fetchComments();
            // Cập nhật rating tổng thể
            const totalStars = lastComments.reduce((sum, cmt) => sum + (cmt.so_sao || 0), 0);
            const avgStars = (lastComments.length ? (totalStars / lastComments.length).toFixed(1) : 0);
            const averageRatingSpan = document.getElementById('averageRating');
            if (averageRatingSpan) averageRatingSpan.textContent = `${avgStars} (${lastComments.length} votes)`;
            
            alert('Gửi bình luận thành công!');
        });
    })
        .catch(err => {
            if (err !== "not logged in") {
                console.error('Lỗi server khi gửi bình luận:', err);
                alert('Lỗi server khi gửi bình luận.');
            } else {
                openModal(modalLogin);
                alert("Vui lòng đăng nhập để gửi bình luận!");
            }
        });
    });

    // Sửa bình luận
    window.editComment = function(id) {
        const commentObj = lastComments.find(c => c.id === id);
        if (!commentObj) return alert("Không tìm thấy bình luận.");

        const bodyDiv = document.getElementById(`cmt-body-${id}`);
        if (!bodyDiv) return;
        if (bodyDiv.querySelector('textarea')) return;

        let originalText = commentObj.cmt || '';
        let originalSao = commentObj.so_sao || 5;

        bodyDiv.innerHTML = `
            <textarea id="edit-area-${id}" rows="3" class="w-full p-2 border rounded">${escapeHtml(originalText)}</textarea>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-sm font-medium">Đánh giá:</span>
                <div class="flex gap-1" id="edit-star-${id}">
                    ${[1,2,3,4,5].map(i => `<button type="button" data-value="${i}" class="text-2xl ${i <= originalSao ? 'text-yellow-400':'text-gray-300'}">★</button>`).join('')}
                </div>
            </div>
            <div class="mt-2 flex gap-2">
                <button class="px-3 py-1 bg-gray-300 rounded" id="save-edit-${id}">Lưu</button>
                <button class="px-3 py-1 bg-gray-300 rounded" id="cancel-edit-${id}">Hủy</button>
            </div>
        `;

        const editStars = bodyDiv.querySelectorAll(`#edit-star-${id} button`);
        editStars.forEach(star => {
            star.addEventListener('click', () => {
                originalSao = parseInt(star.dataset.value);
                editStars.forEach(s => {
                    s.classList.toggle('text-yellow-400', parseInt(s.dataset.value) <= originalSao);
                    s.classList.toggle('text-gray-300', parseInt(s.dataset.value) > originalSao);
                });
            });
        });

        document.getElementById(`cancel-edit-${id}`).addEventListener('click', () => {
            bodyDiv.innerHTML = escapeHtml(originalText);
        });

        document.getElementById(`save-edit-${id}`).addEventListener('click', () => {
        const newText = document.getElementById(`edit-area-${id}`).value.trim();
        if (!newText) return alert('Nội dung bình luận không được rỗng.');

        fetch(`${baseUrl}/api/sua-danh-gia/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cmt: newText, so_sao: originalSao })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Cập nhật trực tiếp DOM
                const bodyDiv = document.getElementById(`cmt-body-${id}`);
                if(bodyDiv){
                    bodyDiv.innerHTML = escapeHtml(newText);
                }
                const commentObj = lastComments.find(c => c.id === id);
                if(commentObj){
                    commentObj.cmt = newText;
                    commentObj.so_sao = originalSao;
                }
                fetchComments();
                // Cập nhật rating tổng thể
                const totalStars = lastComments.reduce((sum, cmt) => sum + (cmt.so_sao || 0), 0);
                const avgStars = (totalStars / lastComments.length).toFixed(1);
                const averageRatingSpan = document.getElementById('averageRating');
                if (averageRatingSpan) averageRatingSpan.textContent = `${avgStars} (${lastComments.length} votes)`;
            } else {
                alert('Lỗi sửa bình luận: ' + (data.message || 'Server trả về lỗi'));
            }
        })
        .catch(err => {
            console.error('Lỗi sửa bình luận:', err);
            alert('Lỗi khi gọi server để sửa bình luận.');
        });
    });

    };

    // Xóa bình luận
    window.deleteComment = function(id) {
    if (!confirm('Bạn có chắc muốn xóa bình luận này?')) return;

    fetch(`${baseUrl}/api/xoa-danh-gia/${id}`, { method: 'DELETE' })
        .then(res => res.text()) // Lấy raw text để kiểm tra JSON
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Server trả về không phải JSON:', text);
                alert('Lỗi server: dữ liệu trả về không hợp lệ.');
                return;
            }

            if (data.success) {
                // Xóa DOM của bình luận
                const commentDiv = document.getElementById(`cmt-${id}`);
                if (commentDiv) commentDiv.remove();

                // Xóa khỏi mảng local
                lastComments = lastComments.filter(c => c.id !== id);
                fetchComments();
                // Cập nhật rating tổng thể
                const totalStars = lastComments.reduce((sum, c) => sum + (c.so_sao || 0), 0);
                const avgStars = lastComments.length ? (totalStars / lastComments.length).toFixed(1) : 0;
                const averageRatingSpan = document.getElementById('averageRating');
                if (averageRatingSpan) averageRatingSpan.textContent = `${avgStars} (${lastComments.length} votes)`;

            } else {
                alert('Xóa thất bại: ' + (data.message || 'Server trả về lỗi'));
            }
        })
        .catch(err => {
            console.error('Lỗi xóa bình luận:', err);
            alert('Lỗi khi gọi server để xóa bình luận.');
        });
};

    // Load thông tin phim + suất chiếu + bình luận
    fetch(`${baseUrl}/api/dat-ve/${idPhim}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data) {
                loadThongTinPhim(data.data);
                loadNoiDungPhim(data.data);
                loadSuatChieu();

                // Load danh sách đánh giá
                fetch(baseUrl + "/api/doc-danh-gia/" + idPhim)
                    .then(res => res.json())
                    .then(data => { if (data.success) loadDanhSachCmt(data.data,  currentUserId); });
            }
        }).catch(err => console.error(err));
    // --- Day Tabs ---
    const visibleDays = 10;
    let currentStartIndex = 0;
    let activeIndex = -1;
    const allDays = [];
    const today = new Date();
    for (let i=0;i<30;i++){ const d=new Date(today); d.setDate(today.getDate()+i); allDays.push(d); }

    function formatDate(d){ return ("0"+d.getDate()).slice(-2)+"/"+("0"+(d.getMonth()+1)).slice(-2);}
    function formatWeekday(d){ return ["CN","T2","T3","T4","T5","T6","T7"][d.getDay()]; }

    function renderDayTabs(){
        dayTabs.innerHTML='';
        for(let i=currentStartIndex;i<currentStartIndex+visibleDays;i++){
            if(!allDays[i]) continue;
            const btn=document.createElement('button');
            btn.className='flex-shrink-0 text-center px-4 py-2 rounded-lg border border-gray-300 font-semibold text-gray-700 hover:bg-red-500 hover:text-white transition-colors';
            btn.innerHTML=`${formatWeekday(allDays[i])}<br>${formatDate(allDays[i])}`;
            btn.dataset.index=i;
            if(activeIndex===-1 && i===0){ btn.classList.add('bg-red-600','text-white'); activeIndex=0; }
            else if(i===activeIndex){ btn.classList.add('bg-red-600','text-white'); }
            dayTabs.appendChild(btn);
        }
    }

    function getSelectedDate(){ return activeIndex>=0 ? allDays[activeIndex].toISOString().split('T')[0] : today.toISOString().split('T')[0]; }

    dayTabs.addEventListener('click', e=>{
        const btn = e.target.closest('button'); if(!btn) return;
        dayTabs.querySelectorAll('button').forEach(b=>{ b.classList.remove('bg-red-600','text-white'); b.classList.add('text-gray-700','border-gray-300'); });
        btn.classList.add('bg-red-600','text-white'); activeIndex=parseInt(btn.dataset.index); loadSuatChieu();
    });

    nextBtn.addEventListener('click',()=>{ if(currentStartIndex+visibleDays<allDays.length){ currentStartIndex++; renderDayTabs(); } });
    prevBtn.addEventListener('click',()=>{ if(currentStartIndex>0){ currentStartIndex--; renderDayTabs(); } });
    renderDayTabs();

});
</script>
</body>
</html>

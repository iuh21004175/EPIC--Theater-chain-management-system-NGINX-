<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xem Phim - EPIC CINEMAS</title>
    <link rel="stylesheet" href="{{$_ENV['URL_WEB_BASE']}}/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans">
    
    <!-- Header -->
    @include('customer.layout.header')

    <main class="container mx-auto max-w-screen-xl px-4 py-10">
        <!-- Search & Filter -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-10">
            <!-- Search box -->
            <div class="flex w-full md:w-auto">
                <input 
                    id="search-input"
                    type="text" 
                    placeholder="Tìm kiếm theo tên phim, đạo diễn, diễn viên..."
                    class="w-full md:w-96 pl-4 pr-2 py-2 border border-gray-300 rounded-l-full bg-white 
                        focus:border-red-600 focus:outline-none transition"
                />
                <button id="search-btn" 
                    class="bg-red-600 text-white px-4 rounded-r-full hover:bg-red-700 transition">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>

            <!-- Filters: gộp 2 select vào 1 flex để sát nhau -->
            <div class="flex w-full md:w-auto gap-2"> <!-- giảm gap -->
                <div class="relative w-full md:w-56">
                    <select id="doTuoi"
                        class="appearance-none w-full pl-4 pr-8 py-2 border border-gray-300 rounded-l-full bg-white 
                            focus:border-red-600 focus:ring-0 outline-none transition">
                        <option value="">Tất cả độ tuổi</option>
                        <option value="p">P (Phù hợp mọi lứa tuổi)</option>
                        <option value="c13">C13 (Trên 13 tuổi)</option>
                        <option value="c16">C16 (Trên 16 tuổi)</option>
                        <option value="c18">C18 (Trên 18 tuổi)</option>
                    </select>
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">▾</span>
                </div>

                <div class="relative w-full md:w-56">
                    <select id="the-loai"
                        class="appearance-none w-full pl-4 pr-8 py-2 border border-gray-300 rounded-r-full bg-white 
                            focus:border-red-600 focus:ring-0 outline-none transition">
                        <option value="">Tất cả thể loại</option>
                    </select>
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">▾</span>
                </div>
            </div>
        </div>


        <!-- Tabs -->
        <div class="flex justify-center mb-8">
            <div class="inline-flex bg-gray-100 rounded-full shadow-inner p-1">
                <button class="tab-btn px-6 py-2 rounded-full bg-red-600 text-white font-semibold transition" data-tab="now-showing">
                    Đang chiếu
                </button>
                <button class="tab-btn px-6 py-2 rounded-full text-gray-700 font-semibold transition hover:bg-gray-200" data-tab="coming-soon">
                    Sắp chiếu
                </button>
            </div>
        </div>

        <!-- Now Showing -->
        <section id="now-showing" class="tab-content grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6"></section>

        <!-- Coming Soon -->
        <section id="coming-soon" class="tab-content hidden grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6"></section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-200 mt-12">
        <div class="container mx-auto max-w-screen-xl px-4 py-6">
            @include('customer.layout.footer')
        </div>
    </footer>

    <!-- Modal Trailer -->
    <div id="trailerModal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 hidden">
        <div class="bg-black rounded-xl shadow-lg w-[90%] max-w-3xl relative">
            <!-- Nút đóng -->
            <button id="closeModal" class="absolute top-2 right-2 text-white text-2xl font-bold hover:text-red-500">&times;</button>
            <!-- Video -->
            <div class="aspect-video">
                <iframe id="trailerIframe" class="w-full h-full rounded-xl" src="" title="Trailer" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
    </div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}"; 
    const urlMinio = "{{ $_ENV['MINIO_SERVER_URL'] }}";
    const salt = "{{ $_ENV['URL_SALT'] }}"; 

    const trailerModal = document.getElementById("trailerModal");
    const closeModal = document.getElementById("closeModal");
    const trailerIframe = document.getElementById("trailerIframe");

    const nowShowing = document.getElementById("now-showing");
    const comingSoon = document.getElementById("coming-soon");

    const searchInput = document.getElementById("search-input");
    const searchBtn = document.getElementById("search-btn");
    const theLoaiMenu = document.getElementById("the-loai");
    const doTuoi = document.getElementById("doTuoi");

    // === Hàm chuyển link youtube sang embed ===
    function youtubeEmbed(url) {
        if (!url) return "";
        const regex = /(?:youtube\.com\/(?:.*v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/;
        const match = url.match(regex);
        if (match && match[1]) {
            return "https://www.youtube.com/embed/" + match[1];
        }
        return url;
    }

    function slugify(str) {
        return str.toLowerCase()
                  .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                  .replace(/[^a-z0-9]+/g, "-")
                  .replace(/^-+|-+$/g, "");
    }

    function base64Encode(str) {
        return btoa(unescape(encodeURIComponent(str)));
    }

    function renderCard(phim) {
        const encoded = base64Encode(phim.id + salt);
        return `
        <div class="relative rounded-xl overflow-hidden shadow-lg group bg-gray-50">
            <img src="${urlMinio}/${phim.poster_url}" alt="${phim.ten_phim}"
                 class="w-full h-[400px] object-cover transition-transform duration-300 group-hover:scale-105">
            <div class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center gap-3 
                        opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-20">
                <a href="${baseUrl}/dat-ve/${slugify(phim.ten_phim)}-${encoded}"
                   class="flex items-center justify-center w-[140px] h-[40px] rounded-lg text-white font-semibold 
                          bg-red-600 hover:bg-red-500 transition-all duration-300">
                    🎟 Mua vé
                </a>
                <button type="button" data-url="${youtubeEmbed(phim.trailer_url)}"
                        class="trailer-btn flex items-center justify-center w-[140px] h-[40px] border border-white rounded-lg text-white font-semibold px-4 py-2 text-sm hover:bg-red-500 hover:border-transparent transition-all duration-300">
                    <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle-play" 
                         class="w-4 h-4 mr-2" role="img" xmlns="http://www.w3.org/2000/svg" 
                         viewBox="0 0 512 512">
                        <path fill="currentColor" 
                              d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zM188.3 
                              147.1c-7.6 4.2-12.3 12.3-12.3 
                              20.9V344c0 8.7 4.7 16.7 12.3 
                              20.9s16.8 4.1 24.3-.5l144-88c7.1-4.4 
                              11.5-12.1 11.5-20.5s-4.4-16.1-11.5-20.5l-144-88c-7.4-4.5-16.7-4.7-24.3-.5z">
                        </path>
                    </svg>
                    Trailer
                </button>
            </div>
            <span class="absolute top-2 right-2 inline-flex items-center justify-center 
                         px-2 py-1 bg-red-500 text-white text-sm font-bold rounded z-10">
                ${phim.do_tuoi}
            </span>
            <div class="text-left bg-gray-50">
                <h3 class="font-bold text-lg text-gray-900 py-2 ml-2">${phim.ten_phim}</h3>
            </div>
        </div>
        `;
    }

    async function loadPhim(tuKhoa = "", theLoaiId = "", doTuoiVal = "") {
        try {
            const url = new URL(baseUrl + "/api/phim");
            if (tuKhoa) url.searchParams.append("tuKhoaTimKiem", tuKhoa);
            if (theLoaiId) url.searchParams.append("theLoaiId", theLoaiId);
            if (doTuoiVal) url.searchParams.append("doTuoi", doTuoiVal);

            const res = await fetch(url);
            const result = await res.json();

            nowShowing.innerHTML = "";
            comingSoon.innerHTML = "";

            if (!result.success || result.data.length === 0) {
                nowShowing.innerHTML = `<p class="text-gray-700 font-semibold text-lg">Không có phim!</p>`;
                comingSoon.innerHTML = `<p class="text-gray-700 font-semibold text-lg">Không có phim!</p>`;
                return;
            }

            const today = new Date();
            today.setHours(0,0,0,0);

            let coNowShowing = false;
            let coComingSoon = false;

            result.data.forEach(phim => {
                const releaseDate = new Date(phim.ngay_cong_chieu);
                releaseDate.setHours(0,0,0,0);
                const card = renderCard(phim);
                if (releaseDate <= today) {
                    nowShowing.innerHTML += card;
                    coNowShowing = true;
                } else {
                    comingSoon.innerHTML += card;
                    coComingSoon = true;
                }
            });

            if (!coNowShowing) nowShowing.innerHTML = `<p class="text-gray-700 font-semibold text-lg">Không có phim!</p>`;
            if (!coComingSoon) comingSoon.innerHTML = `<p class="text-gray-700 font-semibold text-lg">Không có phim!</p>`;

            // Gắn sự kiện trailer
            document.querySelectorAll(".trailer-btn").forEach(btn => {
                btn.addEventListener("click", () => {
                    const url = btn.getAttribute("data-url");
                    if (url) {
                        trailerIframe.src = url + (url.includes("?") ? "&" : "?") + "autoplay=1";
                        trailerModal.classList.remove("hidden");
                    }
                });
            });

        } catch (err) {
            console.error("Lỗi load phim:", err);
        }
    }

    // đóng modal trailer
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

    // Tabs
    const tabs = document.querySelectorAll(".tab-btn");
    const contents = document.querySelectorAll(".tab-content");
    tabs.forEach(tab => {
        tab.addEventListener("click", () => {
            tabs.forEach(t => {
                t.classList.remove("bg-red-600", "text-white");
                t.classList.add("text-gray-700");
            });
            tab.classList.add("bg-red-600", "text-white");
            contents.forEach(c => c.classList.add("hidden"));
            document.getElementById(tab.dataset.tab).classList.remove("hidden");
        });
    });

    // Load thể loại
    fetch(baseUrl + "/api/loai-phim")
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                data.data.forEach(loai => {
                    const option = document.createElement("option");
                    option.value = loai.id;
                    option.textContent = loai.ten;
                    theLoaiMenu.appendChild(option);
                });

                // Nếu URL có query param theLoai → set lại select
                const params = new URLSearchParams(window.location.search);
                if (params.get("theLoai")) {
                    theLoaiMenu.value = params.get("theLoai");
                }
            }
        });

    // === Hàm build URL redirect khi search/filter ===
    function redirectWithFilters() {
        const params = new URLSearchParams();
        if (searchInput.value) params.set("tuKhoa", searchInput.value);
        if (theLoaiMenu.value) params.set("theLoai", theLoaiMenu.value);
        if (doTuoi.value) params.set("doTuoi", doTuoi.value);

        window.location.href = baseUrl + "/phim" + (params.toString() ? "?" + params.toString() : "");
    }

    // Event filter → redirect
    searchBtn.addEventListener("click", redirectWithFilters);
    theLoaiMenu.addEventListener("change", redirectWithFilters);
    doTuoi.addEventListener("change", redirectWithFilters);

    // Khi load trang: đọc query param & load phim
    const params = new URLSearchParams(window.location.search);
    const tuKhoa = params.get("tuKhoa") || "";
    const theLoaiId = params.get("theLoai") || "";
    const doTuoiVal = params.get("doTuoi") || "";

    if (tuKhoa) searchInput.value = tuKhoa;
    if (theLoaiId) theLoaiMenu.value = theLoaiId;
    if (doTuoiVal) doTuoi.value = doTuoiVal;

    loadPhim(tuKhoa, theLoaiId, doTuoiVal);
});

</script>

</body>
</html>

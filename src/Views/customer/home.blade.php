<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Trang chủ - EPIC CINEMAS</title>
  <link rel="stylesheet" href="{{$_ENV['URL_WEB_BASE']}}/css/tailwind.css">
  <link rel="icon" type="image/png" href="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756391269/logo_cinema_z2pcda.jpg">
  <script src="{{$_ENV['URL_WEB_BASE']}}/js/banner.js"></script>
  <style>
    @keyframes slide {
      0%   { transform: translateX(0); }
      33%  { transform: translateX(-100%); }
      66%  { transform: translateX(-200%); }
      100% { transform: translateX(0); }
    }
    .animate-slide {
      width: 300%;
      animation: slide 15s infinite;
    }
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .fade-in-up {
      animation: fadeInUp 0.6s ease-out;
    }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 text-gray-800 font-sans">
@include('customer.layout.header')

<main>
  <!-- Banner -->
  <section class="relative w-full max-w-screen-2xl mx-auto mt-4 px-4">
    <div class="relative w-full overflow-hidden rounded-2xl shadow-2xl bg-black">
      <!-- Khung giữ tỉ lệ ~3:1 của ảnh (2048x682) -->
      <div class="relative w-full aspect-[1024/341] max-h-[520px] mx-auto">
        <!-- Track slide (JS sẽ translateX) -->
        <div
          id="bannerContainer"
          class="absolute inset-0 flex transition-transform duration-700 ease-out"
          data-url="{{$_ENV['URL_WEB_BASE']}}"
          data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}">
          <!-- JS render: mỗi banner là 1 div chứa <img class="w-full h-full object-cover"> -->
        </div>

        <!-- Gradient overlay êm, không gây loạn mắt -->
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-black/40"></div>

        <!-- Nút điều hướng -->
        <button
          id="bannerPrev"
          type="button"
          class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-9 h-9 sm:w-10 sm:h-10 rounded-full
                 bg-black/40 hover:bg-black/70 border border-white/20 flex items-center justify-center
                 text-white text-lg sm:text-xl shadow-lg backdrop-blur-sm transition"
          aria-label="Banner trước">
          ‹
        </button>
        <button
          id="bannerNext"
          type="button"
          class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-9 h-9 sm:w-10 sm:h-10 rounded-full
                 bg-black/40 hover:bg-black/70 border border-white/20 flex items-center justify-center
                 text-white text-lg sm:text-xl shadow-lg backdrop-blur-sm transition"
          aria-label="Banner tiếp theo">
          ›
        </button>

        <!-- Dots chỉ số slide -->
        <div
          id="bannerDots"
          class="absolute bottom-4 inset-x-0 flex items-center justify-center gap-2 z-20"
        >
          <!-- JS sẽ render các dot: span.rounded-full.w-2.h-2.bg-white/40 & dot active bg-white -->
        </div>
      </div>
    </div>
  </section>

  <!-- Phim mới -->
  <section id="thongTinPhimMoi" class="container mx-auto max-w-screen-xl px-4 py-20">
    <div class="text-center mb-12 fade-in-up">
      <div class="inline-flex items-center justify-center mb-4">
        <div class="h-1 w-12 bg-gradient-to-r from-blue-400 to-purple-400 rounded-full"></div>
        <h2 class="text-4xl md:text-5xl font-extrabold mx-4 bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
          Phim Mới
        </h2>
        <div class="h-1 w-12 bg-gradient-to-r from-purple-400 to-blue-400 rounded-full"></div>
      </div>
      <p class="text-gray-600 text-lg">Khám phá những bộ phim mới nhất đang được chiếu</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 md:gap-8">
      <!-- Nội dung render bằng JS -->
    </div>
  </section>

  <!-- Phim được xem nhiều nhất -->
  <section id="thongTinPhimBanChay" class="container mx-auto max-w-screen-xl px-4 py-20">
    <div class="text-center mb-12 fade-in-up">
      <div class="inline-flex items-center justify-center mb-4">
        <div class="h-1 w-12 bg-gradient-to-r from-amber-400 to-rose-400 rounded-full"></div>
        <h2 class="text-4xl md:text-5xl font-extrabold mx-4 bg-gradient-to-r from-amber-600 to-rose-600 bg-clip-text text-transparent">
          Phim Hot Nhất
        </h2>
        <div class="h-1 w-12 bg-gradient-to-r from-rose-400 to-amber-400 rounded-full"></div>
      </div>
      <p class="text-gray-600 text-lg">Top phim được yêu thích và xem nhiều nhất</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 md:gap-8">
      <!-- Nội dung render bằng JS -->
    </div>
  </section>
</main>

@include('customer.layout.footer')

<!-- Modal Trailer -->
<div id="trailerModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 hidden p-4">
  <div class="bg-gradient-to-br from-gray-900 to-black rounded-2xl shadow-2xl w-full max-w-4xl relative 
              border border-gray-700 transform transition-all duration-300">
    <!-- Close button -->
    <button id="closeModal" 
            class="absolute -top-12 right-0 sm:-right-12 sm:top-0 w-10 h-10 rounded-full 
                   bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 
                   text-white text-xl font-bold hover:text-rose-400 hover:rotate-90 
                   transition-all duration-300 flex items-center justify-center shadow-lg z-10">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
    
    <!-- Video container -->
    <div class="aspect-video rounded-2xl overflow-hidden">
      <iframe id="trailerIframe" class="w-full h-full"
        src="" title="Trailer" frameborder="0"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
        allowfullscreen></iframe>
    </div>
  </div>
</div>

<script>
    const trailerModal = document.getElementById("trailerModal");
    const closeModal = document.getElementById("closeModal");
    const trailerIframe = document.getElementById("trailerIframe");
    const urlMinio = "{{ $_ENV['MINIO_SERVER_URL'] }}"; 

    function youtubeEmbed(url) {
      if (!url) return "";
      // match dạng full youtube hoặc rút gọn
      const regex = /(?:youtube\.com\/(?:.*v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/;
      const match = url.match(regex);
      if (match && match[1]) {
        return "https://www.youtube.com/embed/" + match[1];
      }
        return url; // fallback nếu không khớp
    }

    function slugify(str) {
      return str
        .toLowerCase()
        .normalize("NFD").replace(/[\u0300-\u036f]/g, "") // bỏ dấu tiếng Việt
        .replace(/[^a-z0-9]+/g, "-") // thay ký tự đặc biệt thành "-"
        .replace(/^-+|-+$/g, ""); // bỏ dấu - thừa
    }

    function base64Encode(str) {
        return btoa(unescape(encodeURIComponent(str)));
    }

    function renderCard(phim) {
      const encoded = base64Encode(phim.id + salt);
      return `
        <div class="relative rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl group bg-gradient-to-br from-white to-gray-50 
                    transform hover:-translate-y-2 transition-all duration-500 ease-out border border-gray-100">
          <!-- Ảnh poster -->
          <div class="relative overflow-hidden aspect-[2/3]">
            <img src="${urlMinio}/${phim.poster_url}" alt="${phim.ten_phim}"
                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
            
            <!-- Gradient overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent 
                        opacity-60 group-hover:opacity-90 transition-opacity duration-300"></div>
            
            <!-- Độ tuổi tag -->
            <span class="absolute top-3 right-3 inline-flex items-center justify-center 
                        px-3 py-1.5 bg-gradient-to-r from-rose-500 to-pink-500 text-white text-sm font-bold 
                        rounded-full shadow-lg z-10 border-2 border-white/30">
              ${phim.do_tuoi}
            </span>
            
            <!-- Hover overlay với buttons -->
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 
                        opacity-0 group-hover:opacity-100 transition-all duration-300 z-20 p-4">
              <a href="${baseUrl}/dat-ve/${slugify(phim.ten_phim)}-${encoded}"
                class="flex items-center justify-center w-full max-w-[160px] px-6 py-3 rounded-xl 
                       text-white font-bold text-sm bg-gradient-to-r from-blue-500 to-purple-500 
                       hover:from-blue-600 hover:to-purple-600 shadow-lg hover:shadow-xl 
                       transform hover:scale-105 transition-all duration-300">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14 8a1 1 0 11-2 0 1 1 0 012 0zM7 8a1 1 0 11-2 0 1 1 0 012 0zM2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/>
                </svg>
                Mua vé
              </a>
              <button type="button" data-url="${youtubeEmbed(phim.trailer_url)}"
                class="trailer-btn flex items-center justify-center w-full max-w-[160px] px-6 py-3 
                       rounded-xl text-white font-bold text-sm bg-white/20 backdrop-blur-sm 
                       border-2 border-white/50 hover:bg-white/30 shadow-lg hover:shadow-xl 
                       transform hover:scale-105 transition-all duration-300">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                </svg>
                Trailer
              </button>
            </div>
          </div>
          
          <!-- Thông tin phim -->
          <div class="p-4 bg-gradient-to-br from-white to-gray-50">
            <h3 class="font-bold text-lg text-gray-900 line-clamp-2 mb-8 group-hover:text-blue-600 
                       transition-colors duration-300">
              ${phim.ten_phim}
            </h3>
            <div class="flex items-center gap-2 text-sm text-gray-600" style="position: absolute; bottom: 16px;">
              <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
              <span class="font-medium">Đang chiếu</span>
            </div>
          </div>
        </div>
      `;
    }

    function renderCardBanChay(phim) {
      const encoded = base64Encode(phim.id + salt);
      return `
        <div class="relative rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl group bg-gradient-to-br from-white to-amber-50
                    transform hover:-translate-y-2 transition-all duration-500 ease-out border border-amber-100">
          <!-- Ảnh poster -->
          <div class="relative overflow-hidden aspect-[2/3]">
            <img src="${urlMinio}/${phim.poster_url}" alt="${phim.ten_phim}"
                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
            
            <!-- Gradient overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-amber-900/80 via-orange-900/20 to-transparent 
                        opacity-60 group-hover:opacity-90 transition-opacity duration-300"></div>
            
            <!-- Hot badge -->
            <div class="absolute top-3 left-3 z-10">
              <div class="flex items-center gap-1 px-3 py-1.5 bg-gradient-to-r from-amber-500 to-orange-500 
                          text-white text-xs font-bold rounded-full shadow-lg border-2 border-white/30">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                </svg>
                HOT
              </div>
            </div>
            
            <!-- Độ tuổi tag -->
            <span class="absolute top-3 right-3 inline-flex items-center justify-center 
                        px-3 py-1.5 bg-gradient-to-r from-rose-500 to-pink-500 text-white text-sm font-bold 
                        rounded-full shadow-lg z-10 border-2 border-white/30">
              ${phim.do_tuoi}
            </span>
            
            <!-- Hover overlay với buttons -->
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 
                        opacity-0 group-hover:opacity-100 transition-all duration-300 z-20 p-4">
              <a href="${baseUrl}/dat-ve/${slugify(phim.ten_phim)}-${encoded}"
                class="flex items-center justify-center w-full max-w-[160px] px-6 py-3 rounded-xl 
                       text-white font-bold text-sm bg-gradient-to-r from-amber-500 to-orange-500 
                       hover:from-amber-600 hover:to-orange-600 shadow-lg hover:shadow-xl 
                       transform hover:scale-105 transition-all duration-300">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14 8a1 1 0 11-2 0 1 1 0 012 0zM7 8a1 1 0 11-2 0 1 1 0 012 0zM2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/>
                </svg>
                Mua vé
              </a>
              <button type="button" data-url="${youtubeEmbed(phim.trailer_url)}"
                class="trailer-btn flex items-center justify-center w-full max-w-[160px] px-6 py-3 
                       rounded-xl text-white font-bold text-sm bg-white/20 backdrop-blur-sm 
                       border-2 border-white/50 hover:bg-white/30 shadow-lg hover:shadow-xl 
                       transform hover:scale-105 transition-all duration-300">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                </svg>
                Trailer
              </button>
            </div>
          </div>
          
          <!-- Thông tin phim -->
          <div class="p-4 bg-gradient-to-br from-white to-amber-50">
            <h3 class="font-bold text-lg text-gray-900 line-clamp-2 mb-8 group-hover:text-amber-600 
                       transition-colors duration-300">
              ${phim.ten_phim}
            </h3>
            <div class="flex items-center gap-2 text-sm text-gray-600" style="position: absolute; bottom: 16px;">
              <svg class="w-4 h-4 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
              </svg>
              <span class="font-medium">Được yêu thích</span>
            </div>
          </div>
        </div>
      `;
    }

    // Fetch phim mới
    fetch("{{$_ENV['URL_WEB_BASE']}}/api/phim-moi")
      .then(res => res.json())
      .then(data => {
        const container = document.querySelector("#thongTinPhimMoi .grid");
        // sắp xếp theo id tăng dần
        container.innerHTML = data.data.map(phim => renderCard(phim)).join("");

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
        
      })
      .catch(err => console.error("Lỗi khi lấy dữ liệu phim mới:", err));

    // Fetch phim bán chạy
    fetch("{{$_ENV['URL_WEB_BASE']}}/api/doc-phim-ban-chay")
      .then(res => res.json())
      .then(data => {
        const container = document.querySelector("#thongTinPhimBanChay .grid");
        // sắp xếp theo id tăng dần
        container.innerHTML = data.data.map(phim => renderCardBanChay(phim)).join("");

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
        
      })
      .catch(err => console.error("Lỗi khi lấy dữ liệu phim mới:", err));

      // đóng modal
      closeModal.addEventListener("click", () => {
        trailerModal.classList.add("hidden");
        trailerIframe.src = "";
      });
      // đóng khi click ngoài iframe
      trailerModal.addEventListener("click", (e) => {
        if (e.target === trailerModal) {
          trailerModal.classList.add("hidden");
          trailerIframe.src = "";
        }
      });
</script>

</body>
</html>

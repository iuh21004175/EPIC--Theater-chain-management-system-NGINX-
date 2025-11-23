<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Trang chủ - EPIC CINEMAS</title>
  <link rel="stylesheet" href="{{$_ENV['URL_WEB_BASE']}}/css/tailwind.css">
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
  </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">
@include('customer.layout.header')

<main>
  <!-- Banner -->
  <section class="relative w-full h-[680px] overflow-hidden rounded-xl shadow-lg">
    <div class="w-full h-full flex animate-slide" id="bannerContainer" data-url="{{$_ENV['URL_WEB_BASE']}}" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}">
    
    </div>
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-black/80"></div>
  </section>

  <!-- Phim mới -->
  <section id="thongTinPhimMoi" class="container mx-auto max-w-screen-xl px-4 py-16">
    <h2 class="text-3xl font-bold text-center mb-10">Phim Mới</h2>
    <hr class="border-t-2 border-red-500 w-48 mx-auto mb-10">
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
      <!-- Nội dung render bằng JS -->
    </div>
  </section>

  <!-- Phim được xem nhiều nhất -->
  <section id="thongTinPhimBanChay" class="container mx-auto max-w-screen-xl px-4 py-16">
    <h2 class="text-3xl font-bold text-center mb-10">Phim Được Xem Nhiều Nhất</h2>
    <hr class="border-t-2 border-red-500 w-48 mx-auto mb-10">
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
    
    </div>
  </section>
</main>

@include('customer.layout.footer')

<!-- Modal Trailer -->
<div id="trailerModal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 hidden">
  <div class="bg-black rounded-xl shadow-lg w-[90%] max-w-3xl relative">
    <button id="closeModal" class="absolute top-2 right-2 text-white text-2xl font-bold hover:text-red-500">&times;</button>
    <div class="aspect-video">
      <iframe id="trailerIframe" class="w-full h-full rounded-xl"
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
        <div class="relative rounded-xl overflow-hidden shadow-lg group bg-white">
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
                <path fill="currentColor" d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zM188.3 
                147.1c-7.6 4.2-12.3 12.3-12.3 
                20.9V344c0 8.7 4.7 16.7 12.3 
                20.9s16.8 4.1 24.3-.5l144-88c7.1-4.4 
                11.5-12.1 11.5-20.5s-4.4-16.1-11.5-20.5l-144-88c-7.4-4.5-16.7-4.7-24.3-.5z"></path>
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

    function renderCardBanChay(phim) {
      const encoded = base64Encode(phim.id + salt);
      return `
        <div class="relative rounded-xl overflow-hidden shadow-lg group bg-white">
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
                <path fill="currentColor" d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zM188.3 
                147.1c-7.6 4.2-12.3 12.3-12.3 
                20.9V344c0 8.7 4.7 16.7 12.3 
                20.9s16.8 4.1 24.3-.5l144-88c7.1-4.4 
                11.5-12.1 11.5-20.5s-4.4-16.1-11.5-20.5l-144-88c-7.4-4.5-16.7-4.7-24.3-.5z"></path>
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

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Chi Tiết Phim</title>
  <link rel="stylesheet" href="{{ $_ENV['URL_WEB_BASE'] }}/css/tailwind.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<link rel="icon" type="image/png" href="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756391269/logo_cinema_z2pcda.jpg">
</head>
<body class="bg-gray-50 text-gray-800 font-sans flex flex-col min-h-screen">

@include('customer.layout.header')

<main class="flex-1">
  <div id="thongTinPhim" class="max-w-5xl mx-auto p-6 mb-10">
    <!-- Nội dung phim sẽ được render bằng JS -->
  </div>
</main>

@include('customer.layout.footer')

<!-- Modal Trailer -->
<div id="trailerModal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 hidden">
  <div class="bg-black rounded-xl shadow-lg w-[90%] max-w-3xl relative">
    <!-- Nút đóng -->
    <button id="closeModal" class="absolute top-2 right-2 text-white text-2xl font-bold hover:text-red-500">&times;</button>

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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const urlMinio = "{{ $_ENV['MINIO_SERVER_URL'] }}";
    const baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}";
    const salt = "{{ $_ENV['URL_SALT'] }}"; 

    const trailerModal = document.getElementById("trailerModal");
    const closeModal = document.getElementById("closeModal");
    const trailerIframe = document.getElementById("trailerIframe");

    function base64Decode(str) { return decodeURIComponent(escape(atob(str))); }
    function base64Encode(str) { return btoa(unescape(encodeURIComponent(str))); }

    const pathParts = window.location.pathname.split("/");
    const slugWithId = pathParts[pathParts.length - 1];  
    const encodedId = slugWithId.split("-").pop();
    const decoded = base64Decode(encodedId); 
    const idPhim = decoded.replace(salt, ""); 

    // Đóng modal trailer
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

    function renderPhim(phim){
        const encoded = base64Encode(phim.id + salt);
        const html = `
          <nav class="text-gray-600 text-sm mb-4" aria-label="Breadcrumb">
            <ol class="list-reset flex">
              <li><a href="${baseUrl}" class="text-blue-600 hover:underline">Trang chủ</a></li>
              <li><span class="mx-2">/</span></li>
              <li><a href="${baseUrl}/goc-dien-anh" class="text-blue-600 hover:underline">Góc điện ảnh</a></li>
              <li><span class="mx-2">/</span></li>
              <li class="text-gray-500">${phim.ten_phim}</li>
            </ol>
          </nav>

          <div class="flex flex-col md:flex-row gap-6">
            <!-- Poster phim -->
            <div class="relative w-full md:w-64 lg:w-72 flex-shrink-0">
              <img src="${urlMinio}/${phim.poster_url}" 
                   alt="${phim.ten_phim}" 
                   class="w-full h-auto rounded-xl shadow-lg object-cover">
              <!-- Nút trailer -->
              <button data-url="${getYouTubeEmbedUrl(phim.trailer_url)}"
                class="trailer-btn absolute inset-0 flex items-center justify-center 
                       text-white text-4xl opacity-90 hover:opacity-100 transition">
                <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle-play"
                    class="w-16 h-16 drop-shadow-lg" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 512 512">
                  <path fill="currentColor"
                    d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zM188.3  
                       147.1c-7.6 4.2-12.3 12.3-12.3  
                       20.9V344c0 8.7 4.7 16.7 12.3  
                       20.9s16.8 4.1 24.3-.5l144-88c7.1-4.4  
                       11.5-12.1 11.5-20.5s-4.4-16.1-11.5-20.5l-144-88
                       c-7.4-4.5-16.7-4.7-24.3-.5z">
                  </path>
                </svg>
              </button>
            </div>

            <!-- Thông tin phim -->
            <div class="flex-1">
              <h1 class="text-3xl font-bold mb-4">
                ${phim.ten_phim}
                <span class="inline-flex items-center justify-center w-9 h-7 bg-red-600 
                             text-white font-bold rounded text-sm ml-2">
                  ${phim.do_tuoi}
                </span>
              </h1>

              <div class="mb-4 text-gray-700 text-sm space-y-1">
                <div>Thời lượng: <strong>${phim.thoi_luong} phút</strong></div>
                <div>Ngày chiếu: <strong>${new Date(phim.ngay_cong_chieu).toLocaleDateString("vi-VN")}</strong></div>
                <p>Diễn viên: <strong>${phim.dien_vien}</strong></p>
                <p>Thể loại: <strong>${phim.the_loai.map(t => t.the_loai.ten).join(", ")}</strong></p>
                <p>Đạo diễn: <strong>${phim.dao_dien}</strong></p>
                <p>Quốc gia: <strong>${phim.quoc_gia}</strong></p>
              </div>

              <!-- Nút đặt vé (nếu có) -->
              ${phim.video_url ? 
                `<a href="${baseUrl}/dat-ve-online/${phim.ten_phim}-${encoded}" 
                    class="inline-block bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                   <i class="fas fa-ticket-alt"></i> Mua vé
                 </a>` : ''}
            </div>
          </div>

          <!-- Nội dung phim -->
          <div class="mt-6">
            <h2 class="text-xl font-semibold border-l-4 border-blue-600 pl-2 mb-2">NỘI DUNG PHIM</h2>
            <p class="text-gray-700 leading-relaxed">
              ${phim.mo_ta}
            </p>
          </div>
        `;
        document.getElementById('thongTinPhim').innerHTML = html;

        // Gắn sự kiện mở trailer
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

    // Fetch API
    fetch(`${baseUrl}/api/dat-ve/${idPhim}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data) {
                renderPhim(data.data);
            }
        }).catch(err => console.error(err));
});
</script>

</body>
</html>

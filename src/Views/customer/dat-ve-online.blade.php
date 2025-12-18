<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đặt vé xem online - EPIC CINEMAS</title>
<link rel="stylesheet" href="{{ $_ENV['URL_WEB_BASE'] }}/css/tailwind.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<link rel="icon" type="image/png" href="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756391269/logo_cinema_z2pcda.jpg">    
<link href="https://vjs.zencdn.net/7.20.3/video-js.css" rel="stylesheet">
    <script src="https://vjs.zencdn.net/7.20.3/video.min.js"></script>
    <!-- Quality Selector Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/videojs-contrib-quality-levels@2.1.0/dist/videojs-contrib-quality-levels.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/videojs-hls-quality-selector@1.1.1/dist/videojs-hls-quality-selector.min.js"></script>
    <!-- StreamSaver for streaming video download -->
    <script src="https://cdn.jsdelivr.net/npm/streamsaver@2.0.6/StreamSaver.min.js"></script>
    <script>
        // Kiểm tra StreamSaver đã load chưa
        // StreamSaver có thể export dưới nhiều tên khác nhau
        window.streamSaver = window.streamSaver || window.streamsaver || streamSaver;
        
        if (typeof window.streamSaver !== 'undefined') {
            console.log('✓ StreamSaver đã tải thành công');
            window.StreamSaverReady = true;
            window.dispatchEvent(new CustomEvent('streamsaver-ready'));
        } else {
            console.error('✗ StreamSaver không tải được');
        }
    </script>
    
    <style>
        .quality-selector {
            margin: 10px 0;
        }
        .quality-selector select {
            padding: 10px 20px;
            font-size: 14px;
            border: 2px solid #374151;
            border-radius: 10px;
            background: linear-gradient(135deg, #1f2937 0%, #111827 50%, #000000 100%);
            color: #ffffff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            cursor: pointer;
        }
        .quality-selector select:hover {
            border-color: #dc2626;
            background: linear-gradient(135deg, #1f2937 0%, #111827 50%, #1a1a1a 100%);
            box-shadow: 0 6px 12px rgba(220, 38, 38, 0.3);
            transform: translateY(-1px);
        }
        .quality-selector select:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2), 0 6px 12px rgba(220, 38, 38, 0.3);
        }
        .quality-selector select option {
            background: #111827;
            color: #ffffff;
            padding: 10px;
        }
        .video-container {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
        }
        .video-js .vjs-big-play-button {
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            font-size: 4em !important;
            border: none !important;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.9) 0%, rgba(185, 28, 28, 0.9) 100%) !important;
            color: white !important;
            border-radius: 50% !important;
            width: 80px !important;
            height: 80px !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3) !important;
        }
        .video-js .vjs-big-play-button:hover {
            transform: translate(-50%, -50%) scale(1.1) !important;
            box-shadow: 0 12px 24px rgba(220, 38, 38, 0.5) !important;
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
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        #downloadQualityMenu {
            animation: fadeInUp 0.3s ease-out;
        }
        .download-quality-option {
            border-bottom: 1px solid rgba(55, 65, 81, 0.5);
        }
        .download-quality-option:last-child {
            border-bottom: none;
        }
        #downloadQualityOptions::-webkit-scrollbar {
            width: 6px;
        }
        #downloadQualityOptions::-webkit-scrollbar-track {
            background: rgba(17, 24, 39, 0.5);
            border-radius: 3px;
        }
        #downloadQualityOptions::-webkit-scrollbar-thumb {
            background: rgba(220, 38, 38, 0.5);
            border-radius: 3px;
        }
        #downloadQualityOptions::-webkit-scrollbar-thumb:hover {
            background: rgba(220, 38, 38, 0.7);
        }
    </style>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-gray-100 font-sans">

@include('customer.layout.header')

<main>
    <!-- Thông tin phim -->
    <section id="thongTinPhim" class="container mx-auto max-w-screen-xl px-4 mt-6"></section>

    <!-- Nội dung phim -->
    <section id="noiDungPhim" class="w-full px-4 mt-8 hidden"></section>
    <section id="QR" class="w-full px-4 mt-8 hidden"></section>
    <!-- Video phim -->
    <section class="w-full px-4 mt-8">
        <div id="suatChieu" class="w-full max-w-screen-xl mx-auto bg-gradient-to-br from-gray-800 via-gray-900 to-black rounded-2xl shadow-2xl p-8 border border-gray-700">
            <div class="flex items-center justify-center min-h-[400px]">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-red-500 mb-4"></div>
                    <p class="text-gray-300 text-lg font-medium">Đang tải video phim...</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Bình luận & đánh giá -->
    <section class="w-full px-4 mt-8 mb-8">
      <div class="w-full max-w-screen-xl mx-auto bg-gradient-to-br from-gray-800 via-gray-900 to-black rounded-2xl shadow-2xl p-8 border border-gray-700 fade-in-up">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-1 h-10 bg-gradient-to-b from-red-500 to-red-600 rounded-full"></div>
          <h3 class="text-2xl font-bold bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">Bình luận & Đánh giá</h3>
        </div>

        <form class="mb-8 space-y-5 p-6 rounded-xl shadow-lg bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 card-hover" id="commentForm">
            <?php 
            $user = $_SESSION['user'] ?? null;
            $hoten = ($user && is_array($user) && isset($user['ho_ten'])) ? $user['ho_ten'] : "Người dùng";
            ?>

            <?php if ($user && is_array($user)): ?>
                <div class="flex items-center gap-4 pb-4 border-b border-gray-700">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center text-white font-bold text-xl shadow-lg">
                        <?= strtoupper($hoten[0]) ?>
                    </div>
                    <span class="font-semibold text-white text-lg"><?= htmlspecialchars($hoten) ?></span>
                </div>
            <?php endif; ?>

          <div class="flex items-center gap-3">
            <span class="text-sm font-medium text-gray-300">Đánh giá:</span>
            <div class="flex gap-1" id="starRating">
              <button type="button" data-value="1" class="text-3xl text-gray-600 hover:text-yellow-400 hover:scale-110 transition-all duration-200 transform">★</button>
              <button type="button" data-value="2" class="text-3xl text-gray-600 hover:text-yellow-400 hover:scale-110 transition-all duration-200 transform">★</button>
              <button type="button" data-value="3" class="text-3xl text-gray-600 hover:text-yellow-400 hover:scale-110 transition-all duration-200 transform">★</button>
              <button type="button" data-value="4" class="text-3xl text-gray-600 hover:text-yellow-400 hover:scale-110 transition-all duration-200 transform">★</button>
              <button type="button" data-value="5" class="text-3xl text-gray-600 hover:text-yellow-400 hover:scale-110 transition-all duration-200 transform">★</button>
            </div>
            <span id="ratingValue" class="ml-3 font-bold text-yellow-400 text-lg">5</span>
          </div>

          <textarea placeholder="Viết bình luận của bạn..." name="comment" rows="4"
            class="w-full px-5 py-3 bg-gray-800 border-2 border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-200 resize-none"></textarea>

          <div class="mt-5 flex justify-end">
            <button type="submit"
              class="px-8 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white font-semibold rounded-xl shadow-lg hover:from-red-600 hover:to-red-700 transform hover:scale-105 transition-all duration-200 flex items-center gap-2">
              <i class="fas fa-paper-plane"></i>
              <span>Gửi bình luận</span>
            </button>
          </div>
        </form>

        <div id="commentList" class="space-y-4">
            <div class="text-center py-8">
              <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-red-500 mb-3"></div>
              <p class="text-gray-400">Đang tải bình luận...</p>
            </div>
        </div>
      </div>
    </section>

</main>

@include('customer.layout.footer')

<!-- Modal Trailer -->
<div id="trailerModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm flex items-center justify-center z-50 hidden fade-in-up">
  <div class="bg-gradient-to-br from-gray-900 to-black rounded-2xl shadow-2xl w-[95%] max-w-4xl relative border border-gray-700 overflow-hidden">
    <!-- Nút đóng -->
    <button id="closeModal" 
      class="absolute top-4 right-4 z-10 w-10 h-10 bg-red-500/80 hover:bg-red-600 text-white text-xl font-bold rounded-full flex items-center justify-center transition-all duration-200 hover:scale-110 shadow-lg">
      <i class="fas fa-times"></i>
    </button>

    <!-- Video -->
    <div class="aspect-video bg-black">
      <iframe id="trailerIframe" class="w-full h-full rounded-2xl"
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
    const stars = document.querySelectorAll('#starRating button');
    const ratingValue = document.getElementById('ratingValue');
    const commentForm = document.getElementById('commentForm');
    const commentList = document.getElementById('commentList');
    const modalLogin = document.getElementById('modalLogin');
    const body = document.body;
    const noiDungPhim = document.getElementById('noiDungPhim');
    const QR = document.getElementById('QR');

    let allSuatChieu = [];
    let lastComments = [];
    const currentUserId = <?php echo $user ? (int)$user['id'] : 'null'; ?>;

    function openModal(modal) { // Hiển thị modal đăng nhập
        modal.classList.add('is-open');
        body.classList.add('modal-open');
    }

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
    stars.forEach(star => star.addEventListener('click', () => { currentRating = star.dataset.value; updateStars(currentRating); }));
    updateStars(currentRating);

    closeModal.addEventListener("click", () => { trailerModal.classList.add("hidden"); trailerIframe.src = ""; });
    trailerModal.addEventListener("click", (e) => { if(e.target===trailerModal){ trailerModal.classList.add("hidden"); trailerIframe.src=""; }});

    function getYouTubeVideoId(url) {
        if (!url) return null;
        const regex = /(?:youtube\.com\/(?:.*v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/;
        const match = url.match(regex);
        return match && match[1] ? match[1] : null;
    }

    function getYouTubeEmbedUrl(url) {
        const videoId = getYouTubeVideoId(url);
        if (videoId) return "https://www.youtube.com/embed/" + videoId;
        return url;
    }

    function getYouTubeThumbnail(url) {
        const videoId = getYouTubeVideoId(url);
        if (videoId) {
            // Thử maxresdefault trước (ảnh chất lượng cao nhất), nếu không có thì dùng hqdefault
            return `https://img.youtube.com/vi/${videoId}/maxresdefault.jpg`;
        }
        return null;
    }

    function base64Decode(str){ return decodeURIComponent(escape(atob(str))); }
    function base64Encode(str){ return btoa(unescape(encodeURIComponent(str))); }

    const pathParts = window.location.pathname.split("/");
    const slugWithId = pathParts[pathParts.length - 1];  
    const encodedId = slugWithId.split("-").pop();
    const decoded = base64Decode(encodedId); 
    const idPhim = decoded.replace(salt, "");   

    function loadThongTinPhim(phim) {
        // Lấy thumbnail từ YouTube, nếu không có thì fallback về poster
        const bannerImage = getYouTubeThumbnail(phim.trailer_url) || `${urlMinio}/${phim.poster_url}`;
        
        const html = `
            <div class="relative w-full min-h-[400px] md:min-h-[500px] lg:min-h-[600px] bg-black overflow-hidden rounded-2xl shadow-2xl flex items-center justify-center p-4 md:p-6 lg:p-8">
                <img src="${bannerImage}" alt="${phim.ten_phim}" 
                     class="w-full h-full object-contain max-h-[400px] md:max-h-[500px] lg:max-h-[600px] opacity-60"
                     onerror="this.onerror=null; this.src='${urlMinio}/${phim.poster_url}'">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <button type="button" data-url="${getYouTubeEmbedUrl(phim.trailer_url)}" class="trailer-btn group flex items-center justify-center w-20 h-20 md:w-24 md:h-24 rounded-full bg-gradient-to-r from-red-500 to-red-600 text-white font-bold transition-all duration-300 hover:from-red-600 hover:to-red-700 transform hover:scale-110 shadow-2xl hover:shadow-red-500/50"> 
                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle-play" class="w-10 h-10 md:w-12 md:h-12 group-hover:scale-110 transition-transform" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"> 
                            <path fill="currentColor" d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zM188.3 147.1c-7.6 4.2-12.3 12.3-12.3 20.9V344c0 8.7 4.7 16.7 12.3 20.9s16.8 4.1 24.3-.5l144-88c7.1-4.4 11.5-12.1 11.5-20.5s-4.4-16.1-11.5-20.5l-144-88c-7.4-4.5 -16.7-4.7-24.3-.5z"></path> 
                        </svg>
                    </button>
                </div>
            </div>
            <div class="container mx-auto max-w-6xl px-4 mt-8 relative fade-in-up">
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="w-full md:w-1/3 flex-shrink-0 -mt-24 md:-mt-32 relative z-10">
                        <div class="relative group">
                            <div class="absolute -inset-1 bg-gradient-to-r from-red-500 to-red-600 rounded-2xl blur opacity-75 group-hover:opacity-100 transition duration-300"></div>
                            <img src="${urlMinio}/${phim.poster_url}" alt="${phim.ten_phim}" class="relative w-full rounded-2xl shadow-2xl transform group-hover:scale-105 transition duration-300">
                        </div>
                    </div>
                    <div class="w-full md:w-2/3 bg-gradient-to-br from-gray-800 via-gray-900 to-black rounded-2xl shadow-2xl p-8 border border-gray-700 card-hover">
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <h1 class="text-3xl md:text-5xl font-bold bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">${phim.ten_phim}</h1>
                            <span class="px-3 py-1 bg-gradient-to-r from-red-500 to-red-600 text-white font-bold rounded-lg text-sm shadow-lg">${phim.do_tuoi}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 mb-4 text-gray-300">
                            <p class="flex items-center gap-2"><i class="fas fa-clock text-red-500"></i> <strong>Thời lượng:</strong> ${phim.thoi_luong} phút</p>
                            <p class="flex items-center gap-2"><i class="fas fa-calendar text-red-500"></i> <strong>Khởi chiếu:</strong> ${new Date(phim.ngay_cong_chieu).toLocaleDateString("vi-VN")}</p>
                        </div>
                        <div class="flex items-center gap-2 mb-6 p-3 bg-gray-800/50 rounded-lg border border-gray-700">
                            <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 576 512"> <path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/> </svg>
                            <span id="averageRating" class="text-white font-bold text-lg">0.0 (0 votes)</span>
                        </div>
                        <div class="space-y-3 text-gray-300">
                            <p class="flex items-start gap-3"><i class="fas fa-globe text-red-500 mt-1"></i> <strong class="text-white mr-2">Quốc gia:</strong> ${phim.quoc_gia}</p>
                            <p class="flex items-start gap-3"><i class="fas fa-tags text-red-500 mt-1"></i> <strong class="text-white mr-2">Thể loại:</strong> ${phim.the_loai.map(t=>t.the_loai.ten).join(", ")}</p>
                            <p class="flex items-start gap-3"><i class="fas fa-video text-red-500 mt-1"></i> <strong class="text-white mr-2">Đạo diễn:</strong> ${phim.dao_dien}</p>
                            <p class="flex items-start gap-3"><i class="fas fa-users text-red-500 mt-1"></i> <strong class="text-white mr-2">Diễn viên:</strong> ${phim.dien_vien}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('thongTinPhim').innerHTML = html;
        document.querySelectorAll(".trailer-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const url = btn.getAttribute("data-url");
                if (url) { trailerIframe.src = url + (url.includes("?") ? "&" : "?") + "autoplay=1"; trailerModal.classList.remove("hidden"); }
            });
        });
    }

    function loadNoiDungPhim(phim) {
        const html = `<div class="w-full max-w-screen-xl mx-auto bg-gradient-to-br from-gray-800 via-gray-900 to-black rounded-2xl shadow-2xl p-8 border border-gray-700 fade-in-up">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-1 h-10 bg-gradient-to-b from-red-500 to-red-600 rounded-full"></div>
                <h3 class="text-2xl font-bold bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">Nội dung phim</h3>
            </div>
            <p class="text-gray-300 leading-relaxed text-lg">${phim.mo_ta}</p>
        </div>`;
        document.getElementById('noiDungPhim').innerHTML = html;
        document.getElementById('noiDungPhim').classList.remove('hidden');
    }
    
    function renderVideoPhim(phim, goiFull = false) {
    const suatChieuDiv = document.getElementById('suatChieu');
    const videoUrl = `${urlMinio}/private/${phim.video_url}`;
    const filename = phim.video_url.split('/').pop() || "video.mp4";

    function startCountdown(duration, donhangId) {
        const countdownEl = document.getElementById("countdownTimer");
        const soDoGheUrl = window.location.href;
        let time = duration; // giây

        const interval = setInterval(() => {
            const minutes = Math.floor(time / 60);
            const seconds = time % 60;
            countdownEl.textContent = `Thời gian còn lại: ${minutes}:${seconds < 10 ? "0" : ""}${seconds}`;
            time--;

            if (time < 0) {
                clearInterval(interval);
                alert("Hết thời gian thanh toán. Vui lòng đặt lại!");
                window.location.href = soDoGheUrl;
            }
        }, 1000);
    }

    // Lấy trạng thái mua phim từ server
    fetch(`${baseUrl}/api/lay-trang-thai-mua-phim?khachHangId=${currentUserId}`)
        .then(res => res.json())
        .then(data => {
            let daMua = false;
            if (data.success && data.trang_thai === 2) {
                daMua = true; // Nếu trạng thái trả về là 2 => đã mua
            }
            const duocXem = daMua || goiFull;

            suatChieuDiv.innerHTML = `
                <div class="video-container w-full mx-auto fade-in-up">
                    <!-- Selector chất lượng đặt ngoài video -->
                    <div class="quality-selector mb-4 flex items-center gap-3">
                        <label for="quality-select" class="text-gray-300 font-medium flex items-center gap-2">
                            <i class="fas fa-hd-video text-red-500"></i>
                            <span>Chọn chất lượng:</span>
                        </label>
                        <select id="quality-select" class="text-white bg-gradient-to-br from-gray-800 via-gray-900 to-black border-2 border-gray-700 hover:border-red-500/50 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all duration-200">
                            <option value="auto" class="bg-gray-900">Tự động</option>
                        </select>
                    </div>

                    <div class="relative aspect-video rounded-2xl overflow-hidden shadow-2xl border-2 border-gray-700 bg-black">
                        <video 
                            id="my-video" 
                            class="video-js vjs-default-skin ${duocXem ? '' : 'filter blur-md'}" 
                            controls 
                            preload="auto" 
                            data-setup='{}'>
                            ${duocXem 
                                ? `<source src="${urlMinio}/private/${phim.video_url}" type="application/x-mpegURL">`
                            : ''}
                            <p class="vjs-no-js text-white">
                                To view this video please enable JavaScript, and consider upgrading to a web browser that
                                <a href="https://videojs.com/html5-video-support/" target="_blank" class="text-red-500">supports HTML5 video</a>.
                            </p>
                        </video>

                        ${!duocXem 
                            ? `<div class="absolute inset-0 bg-gradient-to-br from-black/90 via-black/80 to-black/90 flex flex-col items-center justify-center text-white gap-6 backdrop-blur-sm">
                                <div class="text-center">
                                    <i class="fas fa-lock text-6xl text-red-500 mb-4"></i>
                                    <p class="text-2xl font-bold mb-2">Bạn chưa mua gói để xem phim này!</p>
                                    <p class="text-gray-400 mb-6">Mua ngay để thưởng thức bộ phim tuyệt vời</p>
                                </div>
                                <button id="buyMovieBtn" class="px-8 py-4 bg-gradient-to-r from-red-500 to-red-600 rounded-xl hover:from-red-600 hover:to-red-700 font-bold text-lg transform hover:scale-105 transition-all duration-200 shadow-2xl hover:shadow-red-500/50 flex items-center gap-3">
                                    <i class="fas fa-ticket-alt"></i>
                                    <span>Mua gói ngay</span>
                                </button>
                                </div>` 
                            : ''
                        }
                    </div>

                    ${duocXem && daMua ? `
                    <div class="w-full flex justify-end mt-4 relative">
                        <div class="relative inline-block">
                            <button id="downloadVideoBtn" 
                                class="px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 font-semibold transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-red-500/50 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-download"></i>
                                <span>Tải xuống MP4</span>
                                <i class="fas fa-chevron-down ml-1 text-sm"></i>
                            </button>
                            <div id="downloadQualityMenu" class="hidden absolute right-0 mt-2 w-56 bg-gradient-to-br from-gray-800 via-gray-900 to-black rounded-xl shadow-2xl border border-gray-700 z-50 overflow-hidden">
                                <div class="p-2">
                                    <div class="px-4 py-2 text-gray-400 text-sm font-semibold border-b border-gray-700 mb-1">Chọn chất lượng:</div>
                                    <div id="downloadQualityOptions" class="max-h-64 overflow-y-auto">
                                        <div class="text-center py-4 text-gray-400">
                                            <i class="fas fa-spinner fa-spin mb-2"></i>
                                            <p class="text-sm">Đang tải danh sách chất lượng...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="downloadProgress" class="hidden mt-4 w-full bg-gray-700 rounded-full h-2.5">
                            <div id="downloadProgressBar" class="bg-red-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p id="downloadStatus" class="hidden text-gray-400 text-sm mt-2 text-right"></p>
                    </div>` : ''}
                </div>
                `;

            // Khởi tạo video player sau khi render DOM
            initVideoPlayer();

            // Nút mua phim
            if (!duocXem) {
                const random9Digits = () => Math.floor(100000000 + Math.random() * 900000000);
                const maVe = random9Digits();

                document.getElementById('buyMovieBtn').addEventListener('click', async () => {
                    try {
                        // Check login khi bấm
                        const resLogin = await fetch(`${baseUrl}/api/check-login`);
                        const loginData = await resLogin.json();

                        if (loginData.status !== "success") {
                            alert("Bạn chưa đăng nhập!");
                            openModal(modalLogin);
                            return;
                        }

                        const userName = loginData.user?.ho_ten || 'Khách';
                        const userInitial = userName.charAt(0).toUpperCase();

                        // Tạo đơn hàng
                        const resDH = await fetch(`${baseUrl}/api/tao-don-hang`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ 
                                // phim_id: idPhim,
                                tong_tien: 150000,
                                ma_ve: maVe,
                                phuong_thuc_mua: 1
                            })
                        });
                        const jDH = await resDH.json();
                        if (!jDH.success) throw new Error(jDH.message);
                        const donhangId = jDH.data.id;

                        // Tạo mua phim / cập nhật trạng thái
                        const resMua = await fetch(`${baseUrl}/api/them-mua-phim`, {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({
                                don_hang_id: donhangId,
                                phim_id: idPhim,
                                so_tien: 30000
                            })
                        });
                        const jMua = await resMua.json();
                        if (!jMua.success) throw new Error(jMua.message);

                        suatChieu.classList.add("hidden");
                        const soTien = 150000;
                        const qrUrl = `https://qr.sepay.vn/img?bank=TPBank&acc=10001198354&template=compact&amount=${soTien}&des=DH${donhangId}`;

                        QR.innerHTML = `
                            <div class="w-full max-w-screen-xl mx-auto bg-gradient-to-br from-gray-800 via-gray-900 to-black rounded-2xl shadow-2xl p-8 text-center border border-gray-700 fade-in-up">
                                <div class="flex items-center justify-center gap-3 mb-6">
                                    <div class="w-1 h-10 bg-gradient-to-b from-red-500 to-red-600 rounded-full"></div>
                                    <h3 class="text-2xl font-bold bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">Quét QR để thanh toán</h3>
                                </div>
                                <div class="bg-white p-6 rounded-2xl inline-block shadow-2xl mb-6">
                                    <img src="${qrUrl}" alt="QR Thanh toán" class="w-80 h-80" />
                                </div>
                                <div class="bg-gradient-to-r from-red-500/20 to-red-600/20 border border-red-500/30 rounded-xl p-4 mb-4">
                                    <p class="text-gray-300 mb-2">Số tiền cần thanh toán</p>
                                    <p class="text-3xl font-bold text-red-400">${soTien.toLocaleString()}đ</p>
                                </div>
                                <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4">
                                    <p id="countdownTimer" class="text-red-400 font-bold text-xl"></p>
                                </div>
                            </div>
                        `;
                        QR.classList.remove("hidden");

                        // Bắt đầu đếm ngược 5 phút
                        startCountdown(300, donhangId);

                        const interval = setInterval(async () => {
                            try {
                                const res = await fetch(`${baseUrl}/api/lay-trang-thai`, {
                                    method: "POST",
                                    headers: { "Content-Type": "application/json" },
                                    body: JSON.stringify({ donhang_id: donhangId })
                                });
                                const status = await res.json();
                                if (status.payment_status === "Paid") {
                                    QR.classList.add("hidden");
                                    suatChieu.classList.remove("hidden");
                                    renderVideoPhim(phim, goiFull);
                                    clearInterval(interval);
                                }
                            } catch (e) {
                                console.log("Lỗi check trạng thái:", e);
                            }
                        }, 1000);

                    } catch (err) {
                        alert("Mua phim thất bại: " + err.message);
                    }
                });
            }

            // Nút download - chỉ hiển thị khi đã mua phim (trạng thái = 2)
            if (duocXem && daMua) {
                const btn = document.getElementById('downloadVideoBtn');
                const qualityMenu = document.getElementById('downloadQualityMenu');
                const qualityOptions = document.getElementById('downloadQualityOptions');
                const progressDiv = document.getElementById('downloadProgress');
                const progressBar = document.getElementById('downloadProgressBar');
                const statusText = document.getElementById('downloadStatus');
                
                let qualityList = [];
                let masterM3u8Url = videoUrl;
                
                // Hàm parse m3u8 master playlist để lấy danh sách chất lượng
                async function loadQualityOptions() {
                    try {
                        let absoluteVideoUrl = masterM3u8Url;
                        if (!masterM3u8Url.startsWith('http')) {
                            const baseUrlObj = new URL(baseUrl);
                            absoluteVideoUrl = new URL(masterM3u8Url, baseUrlObj.origin).href;
                        }
                        
                        const response = await fetch(absoluteVideoUrl);
                        if (!response.ok) throw new Error('Không thể tải playlist');
                        
                        const m3u8Text = await response.text();
                        const lines = m3u8Text.split('\n');
                        
                        qualityList = [];
                        let currentBandwidth = null;
                        let currentResolution = null;
                        let currentUrl = null;
                        
                        for (let i = 0; i < lines.length; i++) {
                            const line = lines[i].trim();
                            
                            if (line.startsWith('#EXT-X-STREAM-INF:')) {
                                // Parse bandwidth và resolution
                                const bandwidthMatch = line.match(/BANDWIDTH=(\d+)/);
                                const resolutionMatch = line.match(/RESOLUTION=(\d+x\d+)/);
                                
                                currentBandwidth = bandwidthMatch ? parseInt(bandwidthMatch[1]) : null;
                                currentResolution = resolutionMatch ? resolutionMatch[1] : null;
                            } else if (line && !line.startsWith('#') && currentBandwidth !== null) {
                                // Đây là URL của playlist
                                if (line.startsWith('http')) {
                                    currentUrl = line;
                                } else if (line.startsWith('/')) {
                                    const urlObj = new URL(absoluteVideoUrl);
                                    currentUrl = urlObj.origin + line;
                                } else {
                                    const baseUrl = absoluteVideoUrl.substring(0, absoluteVideoUrl.lastIndexOf('/') + 1);
                                    currentUrl = baseUrl + line;
                                }
                                
                                const [width, height] = currentResolution ? currentResolution.split('x').map(Number) : [null, null];
                                
                                qualityList.push({
                                    url: currentUrl,
                                    bandwidth: currentBandwidth,
                                    resolution: currentResolution,
                                    height: height,
                                    width: width,
                                    label: height ? `${height}p` : `${Math.round(currentBandwidth / 1000)}kbps`
                                });
                                
                                currentBandwidth = null;
                                currentResolution = null;
                                currentUrl = null;
                            }
                        }
                        
                        // Nếu không tìm thấy quality levels trong master playlist, thử lấy từ player
                        if (qualityList.length === 0) {
                            const player = videojs.getPlayer('my-video');
                            if (player) {
                                const qualityLevels = player.qualityLevels();
                                if (qualityLevels && qualityLevels.length > 0) {
                                    for (let i = 0; i < qualityLevels.length; i++) {
                                        const ql = qualityLevels[i];
                                        qualityList.push({
                                            url: masterM3u8Url, // Dùng URL gốc, sẽ chọn quality trong FFmpeg
                                            bandwidth: ql.bitrate,
                                            resolution: ql.width && ql.height ? `${ql.width}x${ql.height}` : null,
                                            height: ql.height,
                                            width: ql.width,
                                            index: i,
                                            label: ql.height ? `${ql.height}p (${Math.round(ql.bitrate / 1000)}kbps)` : `${Math.round(ql.bitrate / 1000)}kbps`
                                        });
                                    }
                                }
                            }
                            
                            // Nếu vẫn không có, thêm option mặc định
                            if (qualityList.length === 0) {
                                qualityList.push({
                                    url: masterM3u8Url,
                                    bandwidth: null,
                                    resolution: null,
                                    height: null,
                                    width: null,
                                    label: 'Chất lượng mặc định'
                                });
                            }
                        }
                        
                        // Sắp xếp theo độ phân giải giảm dần
                        qualityList.sort((a, b) => (b.height || 0) - (a.height || 0));
                        
                        // Render options
                        if (qualityList.length === 0) {
                            qualityOptions.innerHTML = `
                                <div class="px-4 py-3 text-gray-400 text-sm text-center">
                                    Không tìm thấy chất lượng nào
                                </div>
                            `;
                        } else {
                            qualityOptions.innerHTML = qualityList.map((quality, idx) => `
                                <button 
                                    class="download-quality-option w-full text-left px-4 py-3 hover:bg-gray-700/50 transition-colors duration-200 flex items-center justify-between group"
                                    data-url="${quality.url}"
                                    data-index="${quality.index !== undefined ? quality.index : ''}"
                                    data-height="${quality.height || ''}">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-video text-red-500 group-hover:text-red-400"></i>
                                        <span class="text-white font-medium">${quality.label}</span>
                                    </div>
                                    <i class="fas fa-download text-gray-500 group-hover:text-red-400"></i>
                                </button>
                            `).join('');
                            
                            // Thêm event listener cho mỗi option
                            qualityOptions.querySelectorAll('.download-quality-option').forEach(option => {
                                option.addEventListener('click', () => {
                                    const selectedUrl = option.getAttribute('data-url');
                                    const selectedIndex = option.getAttribute('data-index');
                                    const selectedHeight = option.getAttribute('data-height');
                                    qualityMenu.classList.add('hidden');
                                    startDownload(selectedUrl, selectedIndex, selectedHeight);
                                });
                            });
                        }
                    } catch (error) {
                        console.error('Lỗi load chất lượng:', error);
                        qualityOptions.innerHTML = `
                            <div class="px-4 py-3 text-red-400 text-sm text-center">
                                Lỗi tải danh sách chất lượng
                            </div>
                        `;
                    }
                }
                
                // Hàm tải xuống với chất lượng đã chọn sử dụng StreamSaver
                async function startDownload(selectedUrl, qualityIndex, qualityHeight) {
                    try {
                        // Kiểm tra StreamSaver
                        const streamSaverLib = window.streamSaver || window.streamsaver || streamSaver;
                        if (typeof streamSaverLib === 'undefined' || !streamSaverLib.createWriteStream) {
                            throw new Error('StreamSaver chưa được tải. Vui lòng tải lại trang.');
                        }

                        // Disable button và hiển thị progress
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Đang xử lý...</span>';
                        progressDiv.classList.remove('hidden');
                        statusText.classList.remove('hidden');
                        statusText.textContent = 'Đang tải video từ server...';
                        progressBar.style.width = '10%';

                        // Sử dụng URL chất lượng đã chọn
                        const videoUrlToDownload = selectedUrl || videoUrl;
                        
                        // Chuyển đổi URL thành absolute nếu cần
                        let absoluteVideoUrl = videoUrlToDownload;
                        if (!videoUrlToDownload.startsWith('http')) {
                            const baseUrlObj = new URL(baseUrl);
                            absoluteVideoUrl = new URL(videoUrlToDownload, baseUrlObj.origin).href;
                        }

                        statusText.textContent = 'Đang tải playlist...';
                        progressBar.style.width = '20%';

                        // Tải file m3u8 để parse các segment
                        const m3u8Response = await fetch(absoluteVideoUrl);
                        if (!m3u8Response.ok) {
                            throw new Error('Không thể tải video playlist từ server.');
                        }
                        
                        const m3u8Text = await m3u8Response.text();
                        const m3u8Lines = m3u8Text.split('\n');
                        const baseM3u8Url = absoluteVideoUrl.substring(0, absoluteVideoUrl.lastIndexOf('/') + 1);
                        
                        // Parse danh sách các segment từ m3u8
                        const segments = [];
                        for (let i = 0; i < m3u8Lines.length; i++) {
                            const line = m3u8Lines[i].trim();
                            if (line && !line.startsWith('#') && !line.startsWith('http')) {
                                let segmentUrl;
                                if (line.startsWith('/')) {
                                    const urlObj = new URL(absoluteVideoUrl);
                                    segmentUrl = urlObj.origin + line;
                                } else {
                                    segmentUrl = baseM3u8Url + line;
                                }
                                segments.push(segmentUrl);
                            }
                        }

                        if (segments.length === 0) {
                            throw new Error('Không tìm thấy segment nào trong playlist.');
                        }

                        statusText.textContent = `Đang tải ${segments.length} segment...`;
                        progressBar.style.width = '30%';

                        // Tạo tên file từ tên phim - loại bỏ ký tự đặc biệt không hợp lệ
                        const tenPhim = phim.ten_phim || 'video';
                        let cleanTenPhim = tenPhim
                            .replace(/[<>:"/\\|?*]/g, '') // Loại bỏ ký tự đặc biệt
                            .replace(/\s+/g, ' ') // Chuẩn hóa khoảng trắng
                            .trim();
                        
                        // Giới hạn độ dài tên file (tránh quá dài)
                        if (cleanTenPhim.length > 200) {
                            cleanTenPhim = cleanTenPhim.substring(0, 200);
                        }
                        
                        // Đảm bảo tên file không rỗng
                        if (!cleanTenPhim) {
                            cleanTenPhim = 'video';
                        }
                        
                        const downloadFilename = `${cleanTenPhim}.ts`;
                        
                        // Tạo file stream với StreamSaver
                        const fileStream = streamSaverLib.createWriteStream(downloadFilename, {
                            size: null // Không biết trước kích thước
                        });
                        
                        const writer = fileStream.getWriter();

                        // Tải và ghi từng segment vào stream
                        let downloadedSegments = 0;
                        const totalSegments = segments.length;

                        for (let i = 0; i < segments.length; i++) {
                            try {
                                statusText.textContent = `Đang tải segment ${i + 1}/${totalSegments}...`;
                                const segmentProgress = 30 + ((i / totalSegments) * 60); // 30% -> 90%
                                progressBar.style.width = `${segmentProgress}%`;

                                const segmentResponse = await fetch(segments[i]);
                                if (!segmentResponse.ok) {
                                    console.warn(`Không thể tải segment ${i + 1}:`, segments[i]);
                                    continue;
                                }

                                const segmentData = await segmentResponse.arrayBuffer();
                                await writer.write(new Uint8Array(segmentData));
                                
                                downloadedSegments++;
                            } catch (segmentError) {
                                console.error(`Lỗi khi tải segment ${i + 1}:`, segmentError);
                                // Tiếp tục với segment tiếp theo
                            }
                        }

                        // Đóng writer
                        await writer.close();

                        progressBar.style.width = '100%';
                        statusText.textContent = `Tải xuống thành công! (${downloadedSegments}/${totalSegments} segments)`;

                        setTimeout(() => {
                            progressDiv.classList.add('hidden');
                            statusText.classList.add('hidden');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-download"></i> <span>Tải xuống Video</span><i class="fas fa-chevron-down ml-1 text-sm"></i>';
                            progressBar.style.width = '0%';
                        }, 2000);

                    } catch (error) {
                        console.error('Lỗi download video:', error);
                        
                        let errorMessage = 'Không thể tải video: ' + (error.message || 'Lỗi không xác định');
                        
                        // Kiểm tra các lỗi phổ biến
                        if (error.message && error.message.includes('CORS')) {
                            errorMessage += '\n\nLỗi CORS: Server không cho phép truy cập video từ trình duyệt.';
                        } else {
                            errorMessage += '\n\nLưu ý:\n';
                            errorMessage += '- Đảm bảo kết nối internet ổn định\n';
                            errorMessage += '- Một số segment có thể không tải được nhưng video vẫn có thể xem được';
                        }
                        
                        alert(errorMessage);
                        
                        // Reset UI
                        progressDiv.classList.add('hidden');
                        statusText.classList.add('hidden');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-download"></i> <span>Tải xuống MP4</span><i class="fas fa-chevron-down ml-1 text-sm"></i>';
                        progressBar.style.width = '0%';
                    }
                }
                
                // Xử lý click vào nút để mở/đóng menu
                if (btn && qualityMenu) {
                    let menuLoaded = false;
                    
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        
                        if (qualityMenu.classList.contains('hidden')) {
                            // Mở menu
                            qualityMenu.classList.remove('hidden');
                            
                            // Load danh sách chất lượng lần đầu
                            if (!menuLoaded) {
                                loadQualityOptions();
                                menuLoaded = true;
                            }
                        } else {
                            // Đóng menu
                            qualityMenu.classList.add('hidden');
                        }
                    });
                    
                    // Đóng menu khi click bên ngoài
                    document.addEventListener('click', (e) => {
                        if (!btn.contains(e.target) && !qualityMenu.contains(e.target)) {
                            qualityMenu.classList.add('hidden');
                        }
                    });
                }
            }
        })
        .catch(err => {
            console.error("Lỗi khi lấy trạng thái mua phim:", err);
        });
}

    function initVideoPlayer() {
        const videoEl = document.getElementById('my-video');
        if (!videoEl) return; // nếu chưa có video thì thoát

        // Nếu đã tồn tại player trước đó thì destroy
        if (videojs.getPlayer('my-video')) {
            videojs.getPlayer('my-video').dispose();
        }

        const player = videojs('my-video', {
            fluid: true,
            responsive: true,
            html5: {
                hls: {
                    overrideNative: !videojs.browser.IS_SAFARI
                }
            }
        });

        player.ready(function() {
            console.log('Video player is ready');

            // Khởi tạo quality levels và quality selector
            const qualityLevels = player.qualityLevels();
            const qualitySelector = document.getElementById('quality-select');

            // Xóa option cũ
            qualitySelector.innerHTML = '<option value="auto">Tự động</option>';

            qualityLevels.on('addqualitylevel', function(event) {
                const quality = event.qualityLevel;
                console.log('Quality level added:', quality);

                const option = document.createElement('option');
                option.value = quality.height + 'p';
                option.textContent = quality.height + 'p (' + Math.round(quality.bitrate / 1000) + ' kbps)';
                option.setAttribute('data-index', qualityLevels.length - 1);
                qualitySelector.appendChild(option);
            });

            qualitySelector.addEventListener('change', function() {
                const selectedValue = this.value;

                if (selectedValue === 'auto') {
                    for (let i = 0; i < qualityLevels.length; i++) {
                        qualityLevels[i].enabled = true;
                    }
                } else {
                    for (let i = 0; i < qualityLevels.length; i++) {
                        qualityLevels[i].enabled = false;
                    }
                    const selectedOption = this.options[this.selectedIndex];
                    const qualityIndex = selectedOption.getAttribute('data-index');
                    if (qualityIndex !== null) {
                        qualityLevels[qualityIndex].enabled = true;
                    }
                }
            });

            player.on('error', function() {
                const error = player.error();
                console.error('Video error:', error);
                alert('Lỗi phát video: ' + (error.message || 'Không thể phát video'));
            });

            player.on('loadstart', () => console.log('Video loading started'));
            player.on('loadedmetadata', () => console.log('Video metadata loaded', qualityLevels));
            player.on('canplay', () => console.log('Video can start playing'));

            
        });
    }

 
    // function renderVideoPhim(phim) {
    //     const suatChieuDiv = document.getElementById('suatChieu');

    //     const videoUrl = `${urlMinio}/${phim.video_url}`;
    //     // Lấy tên file từ đường dẫn (sau dấu / cuối cùng)
    //     const filename = phim.video_url.split('/').pop() || "video.mp4";

    //     suatChieuDiv.innerHTML = `
    //         <div class="flex flex-col items-center w-full max-w-5xl mx-auto">
    //             <div class="flex items-center mb-4 justify-start w-full">
    //                 <div class="w-1 h-6 bg-red-600 mr-2"></div>
    //                 <h3 class="text-xl font-bold">Phim: ${phim.ten_phim}</h3>
    //             </div>
    //             <div class="relative w-full aspect-video rounded-xl overflow-hidden shadow-lg mb-2">
    //                 <video controls class="w-full h-full rounded-xl">
    //                     <source src="${videoUrl}" type="video/mp4">
    //                     Trình duyệt của bạn không hỗ trợ video.
    //                 </video>
    //             </div>
    //             <div class="w-full flex justify-end">
    //                 <button id="downloadVideoBtn" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 font-semibold">
    //                     <i class="fas fa-download"></i> Tải xuống
    //                 </button>
    //             </div>
    //         </div>
    //     `;

    //     // Xử lý nút download
    //     const btn = document.getElementById('downloadVideoBtn');
    //     btn.addEventListener('click', () => {
    //         fetch(videoUrl)
    //             .then(res => res.blob())
    //             .then(blob => {
    //                 const link = document.createElement("a");
    //                 link.href = window.URL.createObjectURL(blob);
    //                 link.download = filename; 
    //                 document.body.appendChild(link);
    //                 link.click();
    //                 link.remove();
    //             })
    //             .catch(err => console.error("Lỗi tải video:", err));
    //     });
    // }
    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    function fetchComments() {
        fetch(baseUrl + "/api/doc-danh-gia/" + idPhim)
            .then(res => res.json())
            .then(data => { 
                if (data.success) {
                    loadDanhSachCmt(data.data, currentUserId);
                } else {
                    commentList.innerHTML = `
                        <div class="text-center py-12">
                            <i class="fas fa-exclamation-triangle text-5xl text-red-500 mb-4"></i>
                            <p class="text-gray-400 text-lg">Không tải được bình luận.</p>
                        </div>
                    `;
                }
            })
            .catch(err => console.error("Lỗi load bình luận:", err));
    }

    function loadDanhSachCmt(danhGia, currentUserId) {
        if (!Array.isArray(danhGia)) danhGia = []; // đảm bảo luôn là mảng
        lastComments = danhGia;

        const averageRatingSpan = document.getElementById('averageRating');
        if (danhGia.length === 0) {
            commentList.innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-comments text-6xl text-gray-600 mb-4"></i>
                    <p class="text-gray-400 text-lg">Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                </div>
            `;
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
                    <div class="mt-4 flex gap-3 ml-18">
                        <button class="px-4 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 rounded-lg transition-all duration-200 flex items-center gap-2" onclick="editComment(${cmt.id})">
                            <i class="fas fa-edit"></i>
                            <span>Sửa</span>
                        </button>
                        <button class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg transition-all duration-200 flex items-center gap-2" onclick="deleteComment(${cmt.id})">
                            <i class="fas fa-trash"></i>
                            <span>Xóa</span>
                        </button>
                    </div>
                `;
            }

            return `<div class="p-6 bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl shadow-lg border border-gray-700 card-hover" id="cmt-${cmt.id}">
                <div class="flex items-start gap-4 mb-3">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg flex-shrink-0">
                        ${cmt.khach_hang?.ho_ten?.charAt(0).toUpperCase() || '?'}
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-white text-lg mb-1">${cmt.khach_hang?.ho_ten || 'Khách'}</p>
                        <div class="flex text-lg text-yellow-400 mb-2">${starsStr}</div>
                        <p class="text-gray-400 text-xs">${ngayGui}</p>
                    </div>
                </div>
                <div class="comment-body text-gray-200 text-base leading-relaxed ml-18" id="cmt-body-${cmt.id}">${escapeHtml(cmt.cmt)}</div>
                ${actionButtons}
            </div>`;
        }).join('');
        commentList.innerHTML = html;
    }

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
                    <div class="mt-4 flex gap-3 ml-18">
                        <button onclick="editComment(${newComment.id})" class="px-4 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 rounded-lg transition-all duration-200 flex items-center gap-2">
                            <i class="fas fa-edit"></i>
                            <span>Sửa</span>
                        </button>
                        <button onclick="deleteComment(${newComment.id})" class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg transition-all duration-200 flex items-center gap-2">
                            <i class="fas fa-trash"></i>
                            <span>Xóa</span>
                        </button>
                    </div>
                `;

                const div = document.createElement('div');
                div.id = `cmt-${newComment.id}`;
                div.innerHTML = `
                    <div class="p-6 bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl shadow-lg border border-gray-700 card-hover">
                        <div class="flex items-start gap-4 mb-3">
                            <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg flex-shrink-0">
                                ${userInitial}
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-white text-lg mb-1">${userName}</p>
                                <div class="flex text-lg text-yellow-400 mb-2">${starsStr}</div>
                                <p class="text-gray-400 text-xs">${ngayGui}</p>
                            </div>
                        </div>
                        <div class="comment-body text-gray-200 text-base leading-relaxed ml-18" id="cmt-body-${newComment.id}">
                            ${escapeHtml(newComment.cmt)}
                        </div>
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
            <textarea id="edit-area-${id}" rows="4" class="w-full p-4 bg-gray-800 border-2 border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-200 resize-none mb-4">${escapeHtml(originalText)}</textarea>
            <div class="flex items-center gap-3 mb-4">
                <span class="text-sm font-medium text-gray-300">Đánh giá:</span>
                <div class="flex gap-1" id="edit-star-${id}">
                    ${[1,2,3,4,5].map(i => `<button type="button" data-value="${i}" class="text-3xl ${i <= originalSao ? 'text-yellow-400':'text-gray-600'} hover:scale-110 transition-transform">★</button>`).join('')}
                </div>
            </div>
            <div class="flex gap-3">
                <button class="px-6 py-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2 font-semibold" id="save-edit-${id}">
                    <i class="fas fa-check"></i>
                    <span>Lưu</span>
                </button>
                <button class="px-6 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 flex items-center gap-2 font-semibold" id="cancel-edit-${id}">
                    <i class="fas fa-times"></i>
                    <span>Hủy</span>
                </button>
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


    // Load thông tin phim + video + bình luận
    fetch(`${baseUrl}/api/dat-ve/${idPhim}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data) {
                loadThongTinPhim(data.data);
                loadNoiDungPhim(data.data);
                renderVideoPhim(data.data);
                // Load danh sách đánh giá
                fetch(baseUrl + "/api/doc-danh-gia/" + idPhim)
                    .then(res => res.json())
                    .then(data => { if (data.success) loadDanhSachCmt(data.data,  currentUserId); });
            }
        }).catch(err => console.error(err));
});
</script>
</body>
</html>

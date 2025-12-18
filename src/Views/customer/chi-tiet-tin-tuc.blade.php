<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chi tiết tin tức | EPIC CINEMAS</title>
  <link rel="icon" type="image/png" href="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756391269/logo_cinema_z2pcda.jpg">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f9fafb;
      color: #333;
      margin: 0;
    }

    .content-wrapper {
      max-width: 900px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    h1 {
      font-size: 2rem;
      color: #dc2626;
      margin-bottom: 2.5rem;
      text-transform: uppercase;
    }

    .tinTuc-img {
      width: 100%;
      border-radius: 10px;
      margin-bottom: 20px;
      object-fit: cover;
    }

    #articleContent {
      text-align: justify;
      line-height: 1.75;
      color: #444;
    }

    .meta {
      font-size: 0.95rem;
      color: #666;
      margin-bottom: 1.5rem;
    }

    .back-link {
      display: inline-block;
      padding: 12px 30px;
      border-radius: 30px;
      color: #fff;
      text-decoration: none;
      font-size: 1.05rem;
      transition: 0.3s;
      margin-right: 10px;
      cursor: pointer;
    }

    .back-link:hover {
      transform: scale(1.05);
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-800 font-sans min-h-screen flex flex-col">
  @include('customer.layout.header')

  <main class="flex-1">
    <div id="chiTietTinTuc" class="content-wrapper">
      <p>Đang tải nội dung...</p>
    </div>
  </main>

  @include('customer.layout.footer')

  <script>
    const chiTietTinTuc = document.getElementById('chiTietTinTuc');
    const urlMinio = "{{ $_ENV['MINIO_SERVER_URL'] }}";

    // Lấy ID tin từ URL
    const slugParts = window.location.pathname.split("/");
    const slugWithId = slugParts.pop() || slugParts.pop(); 
    const idTin = slugWithId.split("-").pop();

    // Dừng đọc khi rời trang
    window.addEventListener('beforeunload', () => {
      if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
      }
    });
    
    // Hàm đọc bài viết (speech synthesis)
    function handleSpeech(text) {
      if (!('speechSynthesis' in window)) {
        alert('Trình duyệt không hỗ trợ đọc văn bản.');
        return;
      }

      const synth = window.speechSynthesis;
      const readBtn = document.getElementById('readArticleBtn');

      if (synth.speaking) {
        synth.cancel();
        readBtn.textContent = "Đọc bài viết";
        return;
      }

      const speech = new SpeechSynthesisUtterance(text);
      speech.lang = 'vi-VN';
      speech.rate = 1;
      speech.pitch = 1;

      const voices = synth.getVoices();
      const viVoice = voices.find(v => v.lang === 'vi-VN');
      if (viVoice) speech.voice = viVoice;

      speech.onend = () => readBtn.textContent = "Đọc bài viết";

      synth.speak(speech);
      readBtn.textContent = "Dừng đọc";
    }

    // Hàm render tin tức
    function renderTinTuc(t) {
      const tieu_de = t.tieu_de || 'Không có tiêu đề';
      const anh = t.anh_tin_tuc ? `${urlMinio}/${t.anh_tin_tuc}` : 'https://via.placeholder.com/900x400';
      const tac_gia = t.tac_gia || 'Không rõ';
      const ngay = t.ngay_tao ? new Date(t.ngay_tao).toLocaleDateString('vi-VN') : 'Không xác định';
      const noi_dung = t.noi_dung || '<p>Không có nội dung</p>';

      chiTietTinTuc.innerHTML = `
        <h1><strong>${tieu_de}</strong></h1>

        <div class="meta">
          <p><strong>Người đăng:</strong> ${tac_gia}</p>
          <p><strong>Ngày đăng:</strong> ${ngay}</p>
        </div>

        <div id="articleContent">${noi_dung}</div>

        <div class="mt-10">
          <button id="readArticleBtn" class="back-link bg-red-600">Đọc bài viết</button>
        </div>
      `;

      // Gắn sự kiện đọc bài
      const contentText = document.getElementById('articleContent').innerText;
      document.getElementById('readArticleBtn').addEventListener('click', () => handleSpeech(contentText));
    }

    // Fetch tin tức
    fetch(`${baseUrl}/api/doc-chi-tiet-tin-tuc/${idTin}`)
      .then(res => res.json())
      .then(data => {
        if (data.success && data.data) renderTinTuc(data.data);
        else chiTietTinTuc.innerHTML = '<p>Không tìm thấy bài viết.</p>';
      })
      .catch(err => {
        console.error("Lỗi khi tải bài viết:", err);
        chiTietTinTuc.innerHTML = '<p>Đã xảy ra lỗi khi tải bài viết.</p>';
      });
  </script>
</body>
</html>

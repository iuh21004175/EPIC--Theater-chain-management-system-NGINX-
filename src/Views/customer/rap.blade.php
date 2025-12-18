<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title id="title"></title>
<link rel="icon" type="image/png" href="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756391269/logo_cinema_z2pcda.jpg">
<link rel="stylesheet" href="{{$_ENV['URL_WEB_BASE']}}/css/tailwind.css">
</head>
<body class="bg-gray-100 min-h-screen font-sans">

  <!-- Header -->
  @include('customer.layout.header')

  <main class="py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto bg-white shadow-xl rounded-lg overflow-hidden">
      <header class="p-6 sm:p-8 border-b border-gray-200">
        <h1 id="rapTen" class="text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight"></h1>
      </header>

      <section class="p-6 sm:p-8 space-y-10">
        <div>
          <h2 class="text-2xl font-bold text-gray-800 mb-4">Thông tin rạp</h2>
          <ul id="rapInfo" class="list-disc list-inside space-y-2 text-gray-700">
            <li><span class="font-semibold">Địa chỉ:</span></li>
            <li><span class="font-semibold">Hotline:</span> <a href="tel:19002224" class="text-blue-600 hover:underline"></a></li>
          </ul>
        </div>
      </section>

      <!-- Section hiển thị 2 cột -->
      <section class="p-6 sm:p-8 border-t border-gray-200">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Cột trái: Bản đồ -->
          <div id="rapMap" class="w-full h-[600px] rounded-lg overflow-hidden shadow-md">
           
          </div>

          <!-- Cột phải: Thông tin rạp -->
          <div id="rapMota" class="space-y-4 text-gray-700 text-gray-700 text-justify">
            
          </div>
        </div>
      </section>
    </div>

    <!-- PHIM -->
    <section class="max-w-6xl mx-auto px-4 py-10">
      <h2 class="text-2xl font-bold text-gray-900 border-l-4 border-blue-600 pl-3 mb-6">PHIM</h2>

      <!-- Tabs chọn ngày -->
      <div class="flex items-center gap-2 mb-8">
        <button id="prevDay" class="px-3 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">«</button>
        <div id="dayTabs" class="flex gap-2 overflow-x-auto"></div>
        <button id="nextDay" class="px-3 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">»</button>
      </div>

      <!-- Danh sách phim -->
      <div id="listPhim" class="grid grid-cols-2 md:grid-cols-4 gap-6"></div>

      <!-- Section suất chiếu -->
      <div id="loadSuatChieu" class="mt-10 hidden" data-movie=""></div>
    </section>

  </main>

  <!-- Footer -->
  @include('customer.layout.footer')

<script>
document.addEventListener("DOMContentLoaded", () => {
    const baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}";
    const urlMinio = "{{ $_ENV['MINIO_SERVER_URL'] }}";
    const modalLogin = document.getElementById('modalLogin');
    const body = document.body;


    function openModal(modal) { // Hiển thị modal đăng nhập
        modal.classList.add('is-open');
        body.classList.add('modal-open');
    }
  // Giải mã base64 + salt
  function base64Decode(str) {
      return decodeURIComponent(escape(atob(str)));
  }
  const pathParts = window.location.pathname.split("/");
  const slugWithId = pathParts[pathParts.length - 1];  
  const encodedId = slugWithId.split("-").pop();
  const salt = "{{ $_ENV['URL_SALT'] }}";
  const decoded = base64Decode(encodedId); 
  const idRap = decoded.replace(salt, ""); 

  // -------------------
  // Load thông tin rạp
  async function fetchRap(idRap) {
    try {
      const res = await fetch(`${baseUrl}/api/rap/${idRap}`);
      const data = await res.json();
      if (data.success && data.data && data.data.length > 0) {
        const rap = data.data[0];
        document.getElementById("rapTen").textContent = rap.ten;
        document.getElementById("title").textContent = rap.ten;
        document.getElementById("rapMap").textContent = rap.ban_do;
        document.getElementById("rapMota").textContent = rap.mo_ta;
        document.getElementById("rapInfo").innerHTML = `
          <li><span class="font-semibold">Địa chỉ:</span> ${rap.dia_chi}</li>
          <li><span class="font-semibold">Hotline:</span> <a href="${rap.hotline}" class="text-blue-600 hover:underline">${rap.hotline}</a></li>
        `;
        document.getElementById("rapMap").innerHTML = `
            <iframe 
              src="${rap.ban_do}" 
              width="100%" 
              height="100%" 
              style="border:0;" 
              allowfullscreen 
              loading="lazy" 
              referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        `;
        document.getElementById("rapMota").innerHTML = `
            ${rap.mo_ta}
        `;
      }
    } catch (err) {
      console.error("Lỗi load rạp:", err);
    }
  }

  // -------------------
  // Tab ngày
  const dayTabs = document.getElementById('dayTabs');
  const nextBtn = document.getElementById('nextDay');
  const prevBtn = document.getElementById('prevDay');
  const visibleDays = 7;
  let currentStartIndex = 0;
  let activeIndex = 0;

  const startDate = new Date();
  const allDays = [];
  for (let i=0; i<30; i++){
    const d = new Date(startDate);
    d.setDate(d.getDate() + i);
    allDays.push(d);
  }

  function formatDate(d) {
    return ("0" + d.getDate()).slice(-2) + "/" + ("0" + (d.getMonth()+1)).slice(-2);
  }
  function formatWeekday(d){
    const weekdays = ["Chủ Nhật","Thứ Hai","Thứ Ba","Thứ Tư","Thứ Năm","Thứ Sáu","Thứ Bảy"];
    return weekdays[d.getDay()];
  }
  function getSelectedDate() {
    const d = allDays[activeIndex] || new Date();
    return d.getFullYear() + "-" + ("0" + (d.getMonth()+1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2);
  }
  function renderDays() {
  dayTabs.innerHTML = '';
  for (let i=currentStartIndex; i<currentStartIndex+visibleDays; i++){
    if(!allDays[i]) continue;
    const btn = document.createElement('button');
    btn.className = 'flex-shrink-0 px-4 py-2 rounded-lg font-semibold border transition-colors text-sm';
    btn.innerHTML = `${formatWeekday(allDays[i])}<br>${formatDate(allDays[i])}`;
    if(i===activeIndex){
      btn.classList.add('bg-red-600','text-white','border-red-600');
    } else {
      btn.classList.add('bg-gray-100','hover:bg-gray-200','text-gray-800','border-gray-300');
    }

    btn.addEventListener('click', ()=> {
      activeIndex = i;
      renderDays();

      // Ẩn khung suất chiếu
      const loadSuatChieuEl = document.getElementById('loadSuatChieu');
      loadSuatChieuEl.classList.add('hidden');
      loadSuatChieuEl.dataset.movie = '';

      // Xóa nút "Đã chọn" trên các phim
      document.querySelectorAll('.mark-selected').forEach(btn => btn.remove());

      fetchPhimTheoRap(idRap, getSelectedDate());
    });
    dayTabs.appendChild(btn);
  }
}

  nextBtn.addEventListener('click', () => {
    if(currentStartIndex+visibleDays < allDays.length){
      currentStartIndex++;
      renderDays();
    }
  });
  prevBtn.addEventListener('click', () => {
    if(currentStartIndex>0){
      currentStartIndex--;
      renderDays();
    }
  });

  // Load phim theo rạp + ngày
  async function fetchPhimTheoRap(idRap, ngay) {
    try {
      const res = await fetch(`${baseUrl}/api/phim-theo-rap/${idRap}?ngay=${ngay}`);
      const data = await res.json();
      const listPhim = document.getElementById('listPhim');
      listPhim.innerHTML = '';

      if (data.success && data.data && data.data.length > 0) {
        data.data.forEach(phim => {
          const phimHTML = `
            <div class="group relative rounded-lg overflow-hidden shadow-md hover:shadow-xl transition cursor-pointer" 
                data-movie="${phim.id}" data-name="${phim.ten_phim}">
              <img src="${urlMinio}/${phim.poster_url}" 
                  alt="${phim.ten_phim}" 
                  class="w-full h-72 object-cover group-hover:scale-105 transition-transform duration-500">
              <p class="mb-5 mt-3 text-center font-bold text-gray-800">${phim.ten_phim}</p>
            </div>
          `;
          listPhim.insertAdjacentHTML('beforeend', phimHTML);
        });
        attachMovieClickEvents();
      } else {
        // Nếu không có phim thì hiện thông báo
        listPhim.innerHTML = `
          <div class="col-span-full py-10 text-gray-500 font-semibold">
            Hiện tại chưa có phim nào được chiếu trong ngày này.
          </div>
        `;
      }
    } catch (err) {
      console.error('Lỗi load phim theo rạp:', err);
      const listPhim = document.getElementById('listPhim');
      listPhim.innerHTML = `
        <div class="col-span-full text-center py-10 text-red-500 font-semibold">
          Lỗi khi tải danh sách phim. Vui lòng thử lại sau.
        </div>
      `;
    }
  }

  // Load suất chiếu theo phim
  function loadSuatChieu(idPhim, movieName) {
    const selectedDate = getSelectedDate();
    fetch(`${baseUrl}/api/suat-chieu-khach?ngay=${selectedDate}&id_phim=${idPhim}&id_rapphim=${idRap}`)
      .then(res => res.json())
      .then(data => {
        const suatChieu = Array.isArray(data.data) ? data.data : [];
        renderSuatChieu(suatChieu, movieName);
      })
      .catch(err => console.error("Lỗi load suất chiếu:", err));
  }

  function renderSuatChieu(suatChieu, movieName) {
      const loadSuatChieuEl = document.getElementById('loadSuatChieu');

      if (!suatChieu || suatChieu.length === 0) {
          loadSuatChieuEl.innerHTML = `<p class="text-gray-500 font-semibold">Chưa có suất chiếu trong ngày.</p>`;
          loadSuatChieuEl.classList.remove('hidden');
          return;
      }

      // Nhóm theo Rạp
      const groupedByRap = {};
      suatChieu.forEach(suat => {
          const rapName = suat.phong_chieu.rap_chieu_phim.ten || "Không xác định";
          if (!groupedByRap[rapName]) groupedByRap[rapName] = [];
          groupedByRap[rapName].push(suat);
      });

      // Render HTML
      let html = `<div class="showtimes mt-4 bg-gray-50 p-4 rounded-lg shadow-inner">
                      <h3 class="font-semibold mb-4">Suất chiếu: ${movieName}</h3>`;

      html += Object.entries(groupedByRap).map(([rapName, suats]) => {
          // Nhóm theo loại phòng chiếu
          const groupedByLoai = {};
          suats.forEach(suat => {
              const loaiChieu = (suat.phong_chieu.loai_phongchieu || "Không xác định").toUpperCase();
              if (!groupedByLoai[loaiChieu]) groupedByLoai[loaiChieu] = [];
              groupedByLoai[loaiChieu].push(suat);
          });

          // Render loại phòng
          const loaiHtml = Object.entries(groupedByLoai).map(([loaiChieu, suatsLoai]) => {
              // Sắp xếp theo giờ bắt đầu
              suatsLoai.sort((a, b) => new Date(a.batdau) - new Date(b.batdau));

              const suatHtml = suatsLoai.map(suat => {
                  const gioChieu = new Date(suat.batdau).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                  return `<button 
                              type="button"
                              class="suat-btn px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-red-500 hover:text-white transition-colors"
                              data-suat-id="${suat.id}"
                              data-phong-id="${suat.phong_chieu.id}"
                              data-rap-id="${suat.phong_chieu.rap_chieu_phim.id}">
                              ${gioChieu}
                          </button>`;
              }).join(' ');

              return `<div class="flex items-center mb-2">
                          <span class="font-medium mr-4 min-w-[80px]">${loaiChieu}</span>
                          <div class="flex flex-wrap gap-2">${suatHtml}</div>
                      </div>`;
          }).join('');

          return `<div class="bg-white p-4 rounded-xl shadow mb-6">
                      ${loaiHtml}
                  </div>`;
      }).join('');

      html += `</div>`;
      loadSuatChieuEl.innerHTML = html;
      loadSuatChieuEl.classList.remove('hidden');

      // Gắn sự kiện click cho từng suất
      const salt = "{{ $_ENV['URL_SALT'] }}";
      document.querySelectorAll('.suat-btn').forEach(btn => {
          btn.addEventListener('click', () => {
              const suatId = btn.dataset.suatId;
              const phongId = btn.dataset.phongId;
              const rapId = btn.dataset.rapId;

              function base64Encode(str) {
                  return btoa(unescape(encodeURIComponent(str)));
              }
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

  // Gắn sự kiện click phim
  function attachMovieClickEvents() {
    document.querySelectorAll('.group[data-movie]').forEach(card => {
      card.addEventListener('click', (e) => {
        e.stopPropagation();
        const movieKey = card.dataset.movie;
        const movieName = card.dataset.name;
        const loadSuatChieuEl = document.getElementById('loadSuatChieu');

        // Ẩn nếu click lại phim đã chọn
        if(loadSuatChieuEl.dataset.movie === movieKey && !loadSuatChieuEl.classList.contains('hidden')) {
          loadSuatChieuEl.classList.add('hidden');
          document.querySelectorAll('.mark-selected').forEach(btn => btn.remove());
          return;
        }

        document.querySelectorAll('.mark-selected').forEach(btn => btn.remove());

        loadSuatChieuEl.dataset.movie = movieKey;
        loadSuatChieu(movieKey, movieName);

        const markBtn = document.createElement('div');
        markBtn.textContent = "Đã chọn";
        markBtn.className = "mark-selected absolute top-2 right-2 bg-red-600 text-white text-xs px-2 py-1 rounded";
        card.appendChild(markBtn);
      });
    });
  }

  // -------------------
  // Init
  fetchRap(idRap);
  renderDays();
  fetchPhimTheoRap(idRap, getSelectedDate());
});
</script>


</body>
</html>

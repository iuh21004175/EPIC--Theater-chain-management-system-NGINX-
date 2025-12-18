<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Tin Tức Điện Ảnh Mới Nhất | EPIC CINEMAS</title>
  <link rel="icon" type="image/png" href="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756391269/logo_cinema_z2pcda.jpg">
  <link rel="stylesheet" href="{{ $_ENV['URL_WEB_BASE'] }}/css/tailwind.css">
  <style>
    /* Fallback nếu chưa cài plugin line-clamp */
    .line-clamp-3 {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-800 font-sans min-h-screen flex flex-col">
  @include('customer.layout.header')

  <main class="flex-1">
    <div class="max-w-5xl mx-auto p-6">
      <h1 class="text-2xl font-bold border-l-4 border-red-500 pl-2 mb-6 uppercase tracking-wide">
        Tin tức điện ảnh
      </h1>

      <!-- Banner hiển thị rạp đang lọc -->
      <div id="rap-filter-banner" class="hidden bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg shadow-md p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
          </svg>
          <div>
            <p class="text-sm opacity-90">Đang xem tin tức của:</p>
            <p class="text-lg font-semibold" id="rap-name-display">Rạp</p>
          </div>
        </div>
        <button id="remove-rap-filter" class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition-colors font-medium text-sm flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
          Xem tất cả
        </button>
      </div>

      <!-- Bộ lọc và tìm kiếm -->
      <div class="bg-white rounded-lg shadow-md p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <!-- Tìm kiếm -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
              Tìm kiếm
            </label>
            <input type="text" id="search-tin-tuc" placeholder="Tìm theo tiêu đề hoặc nội dung..." 
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent">
          </div>
          
          <!-- Sắp xếp -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path>
              </svg>
              Sắp xếp
            </label>
            <select id="sort-tin-tuc" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent">
              <option value="ngay_tao-desc">Mới nhất</option>
              <option value="ngay_tao-asc">Cũ nhất</option>
              <option value="tieu_de-asc">Tiêu đề A-Z</option>
              <option value="tieu_de-desc">Tiêu đề Z-A</option>
            </select>
          </div>
        </div>

        <!-- Hiển thị số dòng và reset -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
          <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700">Hiển thị:</label>
            <select id="rows-per-page-tin-tuc" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-red-500">
              <option value="5">5</option>
              <option value="10" selected>10</option>
              <option value="20">20</option>
              <option value="50">50</option>
            </select>
            <span class="text-sm text-gray-600">bài/trang</span>
          </div>
          <button id="reset-filters-tin-tuc" class="text-sm text-red-600 hover:text-red-800 font-medium flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Đặt lại bộ lọc
          </button>
        </div>
      </div>

      <!-- Danh sách tin tức -->
      <div id="tinTucContainer" class="space-y-6"></div>

      <!-- Phân trang -->
      <div class="bg-white rounded-lg shadow-md px-6 py-4 mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="text-sm text-gray-700">
          Hiển thị <span id="showing-from-tin-tuc" class="font-semibold">0</span> - <span id="showing-to-tin-tuc" class="font-semibold">0</span> 
          trong tổng số <span id="showing-total-tin-tuc" class="font-semibold">0</span> bài viết
        </div>
        <div id="pagination-tin-tuc" class="flex gap-2"></div>
      </div>
    </div>
  </main>

  @include('customer.layout.footer')

  <script>
    // Sử dụng biến từ header hoặc khai báo nếu chưa có (tránh lỗi khi đã khai báo trong header)
    if (typeof window.baseUrl === 'undefined') {
      window.baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}";
    }
    if (typeof window.salt === 'undefined') {
      window.salt = "{{ $_ENV['URL_SALT'] }}";
    }
    // Sử dụng biến global thay vì khai báo lại
    const urlMinio = "{{ $_ENV['MINIO_SERVER_URL'] }}";
    const tinTucContainer = document.getElementById('tinTucContainer');
    const searchInput = document.getElementById('search-tin-tuc');
    const sortSelect = document.getElementById('sort-tin-tuc');
    const rowsPerPageSelect = document.getElementById('rows-per-page-tin-tuc');
    const resetFiltersBtn = document.getElementById('reset-filters-tin-tuc');
    const rapFilterBanner = document.getElementById('rap-filter-banner');
    const rapNameDisplay = document.getElementById('rap-name-display');
    const removeRapFilterBtn = document.getElementById('remove-rap-filter');

    // Lấy rap_id từ URL nếu có
    const urlParams = new URLSearchParams(window.location.search);
    const rapEncoded = urlParams.get('rap');
    let currentRapId = null;
    let currentRapName = null;
    
    if (rapEncoded) {
      try {
        const decoded = atob(rapEncoded);
        const rapId = decoded.replace(window.salt, '');
        currentRapId = rapId;
        loadRapName(rapId);
      } catch (e) {
        console.error('Lỗi decode rap_id:', e);
      }
    } else {
      // Ẩn banner nếu không có rạp được chọn
      rapFilterBanner.classList.add('hidden');
    }

    // Hàm load tên rạp từ API
    function loadRapName(rapId) {
      fetch(`${window.baseUrl}/api/rap-phim-khach`)
        .then(res => res.json())
        .then(data => {
          if (data.success && Array.isArray(data.data)) {
            const rap = data.data.find(r => r.id == rapId);
            if (rap) {
              currentRapName = rap.ten;
              rapNameDisplay.textContent = rap.ten;
              rapFilterBanner.classList.remove('hidden');
            } else {
              rapFilterBanner.classList.add('hidden');
            }
          }
        })
        .catch(err => {
          console.error('Lỗi load tên rạp:', err);
          rapFilterBanner.classList.add('hidden');
        });
    }

    // Data
    let currentPage = 1;
    let rowsPerPage = 10;
    let paginationData = null;

    // Hàm tạo slug an toàn
    function slugify(str) {
      if (!str || typeof str !== 'string') return 'bai-viet';
      return str
        .toLowerCase()
        .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");
    }

    // Hàm decode base64
    function base64Decode(str) {
      try {
        return atob(str);
      } catch (e) {
        return '';
      }
    }

    // Hàm tải dữ liệu
    function loadTinTuc() {
      const search = searchInput.value.trim();
      const [sortBy, sortOrder] = sortSelect.value.split('-');
      const perPage = parseInt(rowsPerPageSelect.value);

      const params = new URLSearchParams({
        page: currentPage,
        per_page: perPage,
        sort_by: sortBy,
        sort_order: sortOrder
      });

      if (search) params.append('search', search);
      if (currentRapId) params.append('rap_id', currentRapId);

      fetch(`${window.baseUrl}/api/doc-tin-tuc?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
          if (data.success && Array.isArray(data.data)) {
            renderTinTucList(data.data);
            if (data.pagination) {
              paginationData = data.pagination;
              renderPagination();
              updatePaginationInfo();
            }
          } else {
            tinTucContainer.innerHTML = `<p class="text-center text-gray-500 py-8">Không có tin tức nào.</p>`;
            document.getElementById('pagination-tin-tuc').innerHTML = '';
            updatePaginationInfo(0, 0, 0);
          }
        })
        .catch(err => {
          console.error("Lỗi tải tin tức:", err);
          tinTucContainer.innerHTML = `<p class="text-center text-red-500 py-8">Đã xảy ra lỗi khi tải tin tức.</p>`;
        });
    }

    // Hàm render danh sách
    function renderTinTucList(list) {
      if (!list.length) {
        tinTucContainer.innerHTML = `<p class="text-center text-gray-500 py-8">Không tìm thấy tin tức nào.</p>`;
        return;
      }

      const html = list.map(tintuc => {
        const link = `${window.baseUrl}/chi-tiet-tin-tuc/${slugify(tintuc.tieu_de)}-${tintuc.id}`;
        const image = tintuc.anh_tin_tuc ? `${urlMinio}/${tintuc.anh_tin_tuc}` : 'https://via.placeholder.com/400x300?text=No+Image';
        const title = tintuc.tieu_de || 'Không có tiêu đề';
        const content = (tintuc.noi_dung || '').replace(/<[^>]*>/g, '').substring(0, 200);
        const date = new Date(tintuc.ngay_tao).toLocaleDateString('vi-VN');

        return `
          <a href="${link}" class="block bg-white shadow rounded overflow-hidden hover:shadow-lg transition flex flex-col md:flex-row">
            <img src="${image}" alt="${title}" class="w-full md:w-1/3 h-56 object-cover" onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
            <div class="p-4 flex-1 flex flex-col justify-between">
              <div>
                <h2 class="text-xl font-semibold mb-2 hover:text-red-500 line-clamp-2">${title}</h2>
                <p class="text-sm text-gray-600 mb-3 line-clamp-3">${content}...</p>
              </div>
              <div class="text-xs text-gray-500 flex justify-between items-center">
                <span>Ngày đăng: ${date}</span>
                ${tintuc.tac_gia ? `<span>Tác giả: ${tintuc.tac_gia.ten || tintuc.tac_gia || 'N/A'}</span>` : ''}
              </div>
            </div>
          </a>
        `;
      }).join("");

      tinTucContainer.innerHTML = html;
    }

    // Render phân trang
    function renderPagination() {
      if (!paginationData) return;
      const paginationEl = document.getElementById('pagination-tin-tuc');
      const totalPages = paginationData.total_pages;
      paginationEl.innerHTML = '';
      
      if (totalPages <= 1) return;
      
      // Nút Previous
      const prevBtn = document.createElement('button');
      prevBtn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>`;
      prevBtn.className = `px-3 py-2 border border-gray-300 rounded-l-lg ${currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}`;
      prevBtn.disabled = currentPage === 1;
      prevBtn.addEventListener('click', () => {
        if (currentPage > 1) {
          currentPage--;
          loadTinTuc();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
      paginationEl.appendChild(prevBtn);
      
      // Các nút số trang
      let startPage = Math.max(1, currentPage - 2);
      let endPage = Math.min(totalPages, currentPage + 2);
      
      if (startPage > 1) {
        const firstBtn = document.createElement('button');
        firstBtn.textContent = '1';
        firstBtn.className = `px-3 py-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50`;
        firstBtn.addEventListener('click', () => {
          currentPage = 1;
          loadTinTuc();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        paginationEl.appendChild(firstBtn);
        
        if (startPage > 2) {
          const dots = document.createElement('span');
          dots.textContent = '...';
          dots.className = 'px-2 py-2 text-gray-500';
          paginationEl.appendChild(dots);
        }
      }
      
      for (let i = startPage; i <= endPage; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = `px-3 py-2 border border-gray-300 ${i === currentPage ? 'bg-red-500 text-white border-red-500' : 'bg-white text-gray-700 hover:bg-gray-50'}`;
        btn.addEventListener('click', () => {
          currentPage = i;
          loadTinTuc();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        paginationEl.appendChild(btn);
      }
      
      if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
          const dots = document.createElement('span');
          dots.textContent = '...';
          dots.className = 'px-2 py-2 text-gray-500';
          paginationEl.appendChild(dots);
        }
        
        const lastBtn = document.createElement('button');
        lastBtn.textContent = totalPages;
        lastBtn.className = `px-3 py-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50`;
        lastBtn.addEventListener('click', () => {
          currentPage = totalPages;
          loadTinTuc();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        paginationEl.appendChild(lastBtn);
      }
      
      // Nút Next
      const nextBtn = document.createElement('button');
      nextBtn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>`;
      nextBtn.className = `px-3 py-2 border border-gray-300 rounded-r-lg ${currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}`;
      nextBtn.disabled = currentPage === totalPages;
      nextBtn.addEventListener('click', () => {
        if (currentPage < totalPages) {
          currentPage++;
          loadTinTuc();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
      paginationEl.appendChild(nextBtn);
    }

    // Cập nhật thông tin phân trang
    function updatePaginationInfo(from = null, to = null, total = null) {
      if (paginationData) {
        const start = (paginationData.page - 1) * paginationData.per_page + 1;
        const end = Math.min(paginationData.page * paginationData.per_page, paginationData.total);
        document.getElementById('showing-from-tin-tuc').textContent = from !== null ? from : start;
        document.getElementById('showing-to-tin-tuc').textContent = to !== null ? to : end;
        document.getElementById('showing-total-tin-tuc').textContent = total !== null ? total : paginationData.total;
      } else {
        document.getElementById('showing-from-tin-tuc').textContent = '0';
        document.getElementById('showing-to-tin-tuc').textContent = '0';
        document.getElementById('showing-total-tin-tuc').textContent = '0';
      }
    }

    // Event listeners
    searchInput.addEventListener('input', () => {
      currentPage = 1;
      loadTinTuc();
    });

    sortSelect.addEventListener('change', () => {
      currentPage = 1;
      loadTinTuc();
    });

    rowsPerPageSelect.addEventListener('change', () => {
      rowsPerPage = parseInt(rowsPerPageSelect.value);
      currentPage = 1;
      loadTinTuc();
    });

    resetFiltersBtn.addEventListener('click', () => {
      searchInput.value = '';
      sortSelect.value = 'ngay_tao-desc';
      rowsPerPageSelect.value = '10';
      rowsPerPage = 10;
      currentPage = 1;
      currentRapId = null;
      currentRapName = null;
      // Xóa rap từ URL
      const url = new URL(window.location);
      url.searchParams.delete('rap');
      window.history.pushState({}, '', url);
      rapFilterBanner.classList.add('hidden');
      loadTinTuc();
    });

    // Xử lý nút "Xem tất cả" trong banner
    removeRapFilterBtn.addEventListener('click', () => {
      currentRapId = null;
      currentRapName = null;
      // Xóa rap từ URL
      const url = new URL(window.location);
      url.searchParams.delete('rap');
      window.history.pushState({}, '', url);
      rapFilterBanner.classList.add('hidden');
      currentPage = 1;
      loadTinTuc();
    });

    // Load dữ liệu ban đầu
    loadTinTuc();
  </script>
</body>
</html>

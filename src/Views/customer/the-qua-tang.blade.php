<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="icon" type="image/png" href="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756391269/logo_cinema_z2pcda.jpg">
  <title>Thẻ quà tặng - Epic Cinema</title>
  <link rel="stylesheet" href="{{ $_ENV['URL_WEB_BASE'] }}/css/tailwind.css">
</head>

<body class="min-h-screen flex flex-col bg-gray-50 text-gray-800 font-sans">
  @include('customer.layout.header')

  <!-- Nội dung chính -->
  <main class="flex-1">
    <div class="max-w-6xl mx-auto px-4 pt-10 pb-0">
      <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 mt-10">
        <!-- Controls -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Lọc theo:</label>
            <select id="filterVoucher" class="border rounded-md p-2 text-sm">
              <option value="all">Tất cả</option>
              <option value="active">Còn hạn</option>
              <option value="expired">Hết hạn</option>
              <option value="used">Đã sử dụng</option>
            </select>
          </div>
          <div class="text-sm text-gray-500">Tổng: <span id="countVoucher">0</span> thẻ</div>
        </div>

        <!-- List -->
        <div id="list-voucher" class="grid gap-3 mt-4">
          <!-- Voucher cards sẽ render bằng JS -->
        </div>

        <!-- Empty state -->
        <div id="empty-voucher" class="hidden text-center py-8 text-gray-500">
          <p class="mb-2">Bạn chưa có thẻ quà tặng nào.</p>
        </div>
      </section>
    </div>
  </main>

  <!-- Footer -->
  @include('customer.layout.footer')

  <script>
  const listVoucher = document.getElementById('list-voucher');
  const emptyVoucher = document.getElementById('empty-voucher');
  const countVoucher = document.getElementById('countVoucher');
  const filterVoucher = document.getElementById('filterVoucher');

  let vouchers = [];

  // Fetch dữ liệu thẻ quà tặng
  fetch(baseUrl + "/api/doc-the-qua-tang")
    .then(res => res.json())
    .then(data => {
      if (data.success && Array.isArray(data.data)) {
        vouchers = data.data;
        renderVoucher(vouchers);
      } else {
        renderVoucher([]);
      }
    })
    .catch(err => {
      console.error("Lỗi load voucher:", err);
      renderVoucher([]);
    });

  // Render danh sách
  function renderVoucher(list) {
      listVoucher.innerHTML = '';
      if (!list || list.length === 0) {
          emptyVoucher.classList.remove('hidden');
          countVoucher.textContent = 0;
          return;
      }
      emptyVoucher.classList.add('hidden');

      const now = new Date();

      list.forEach(vc => {
          const expireDate = vc.ngay_het_han ? new Date(vc.ngay_het_han) : null;
          let remainingText = '';
          let watermark = '';

          if (vc.trang_thai == 0 || (expireDate && expireDate < now)) {
              // Hết hạn hoặc đã sử dụng
              watermark = `<div class="absolute inset-0 flex items-center justify-center bg-white/80 text-2xl font-bold text-gray-500 rotate-[-20deg]">ĐÃ SỬ DỤNG</div>`;
              remainingText = expireDate ? `Hết hạn` : 'Đã sử dụng';
          } else if (expireDate) {
              // Tính số ngày còn lại
              const diffTime = expireDate - now;
              const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
              remainingText = `Hạn sử dụng còn ${diffDays} ngày`;
          } else {
              remainingText = 'Không giới hạn';
          }

          const card = document.createElement('div');
          card.className = 'border rounded-lg p-4 bg-white shadow-sm relative overflow-hidden';

          card.innerHTML = `
              ${watermark}
              <div class="relative z-10">
                  <div class="font-semibold text-lg">${vc.ten || 'Không xác định'}</div>
                  <div class="text-sm text-gray-500">Mã: ${vc.ma_code || '-'}</div>
                  <div class="text-sm text-gray-500">Giá trị: ${Number(vc.gia_tri || 0).toLocaleString()} ₫</div>
                  <div class="text-sm text-gray-500">Phát hành: ${vc.ngay_phat_hanh ? new Date(vc.ngay_phat_hanh).toLocaleDateString('vi-VN') : '-'}</div>
                  <div class="text-sm text-gray-500">Hết hạn: ${expireDate ? expireDate.toLocaleDateString('vi-VN') : '-'}</div>
                  <div class="mt-1 text-sm font-semibold text-blue-600">${remainingText}</div>
                  ${vc.ghi_chu ? `<p class="mt-2 text-gray-600 text-sm">${vc.ghi_chu}</p>` : ''}
              </div>
          `;
          listVoucher.appendChild(card);
      });

      countVoucher.textContent = list.length;
  }

  // Lọc voucher
  filterVoucher.addEventListener('change', () => {
    const v = filterVoucher.value;
    const now = new Date();
    let filtered = vouchers;

    if (v === 'active') {
      filtered = vouchers.filter(vc => vc.trang_thai == 1 && (!vc.ngay_het_han || new Date(vc.ngay_het_han) >= now));
    } else if (v === 'expired') {
      filtered = vouchers.filter(vc => vc.ngay_het_han && new Date(vc.ngay_het_han) < now);
    } else if (v === 'used') {
      filtered = vouchers.filter(vc => vc.trang_thai == 0);
    }

    renderVoucher(filtered);
  });
  </script>
</body>
</html>

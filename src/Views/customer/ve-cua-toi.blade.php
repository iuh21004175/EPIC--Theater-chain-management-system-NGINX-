<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Vé của tôi - Epic Cinema</title>
  <link rel="stylesheet" href="{{$_ENV['URL_WEB_BASE']}}/css/tailwind.css">
  <style>
.modal-cancelled-overlay {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  display: flex;
  justify-content: center;
  align-items: center;
  pointer-events: none;
  z-index: 50;
}

.modal-cancelled-overlay span {
  background: rgba(255,0,0,0.8);
  color: white;
  font-weight: bold;
  font-size: 1.5rem;
  padding: 8px 16px;
  border-radius: 4px;
  transform: rotate(-15deg);
  opacity: 0.9;
}
</style>
</head>

<body class="bg-gray-50 text-gray-800 font-sans">
  @include('customer.layout.header')
  <div class="max-w-6xl mx-auto px-4 py-10">

    <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
      <!-- Tabs -->
      <nav class="flex gap-2 border-b pb-4 mb-4">
        <button id="tab-theater" class="tab-btn px-4 py-2 rounded-md font-medium text-sm bg-red-600 text-white">Vé xem tại rạp</button>
        <button id="tab-online" class="tab-btn px-4 py-2 rounded-md font-medium text-sm bg-transparent text-gray-600 hover:bg-gray-100">Vé xem online</button>
      </nav>

      <!-- Content: theater tickets -->
      <div id="content-theater" class="tab-content space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Tìm theo:</label>
            <select id="filterTheater" class="border rounded-md p-2 text-sm">
              <option value="all">Tất cả</option>
              <option value="upcoming">Sắp chiếu</option>
              <option value="used">Đã sử dụng</option>
            </select>
          </div>
          <div class="text-sm text-gray-500">Tổng: <span id="countTheater">0</span> vé</div>
        </div>

        <div id="list-theater" class="grid gap-3"></div>

        <div id="empty-theater" class="hidden text-center py-8 text-gray-500">
          <p class="mb-2">Bạn chưa có vé xem tại rạp.</p>
          <a href="{{ $_ENV['URL_WEB_BASE'] }}/phim" class="inline-block bg-red-600 text-white px-4 py-2 rounded-md">Mua vé ngay</a>
        </div>
      </div>

      <!-- Content: online tickets -->
      <div id="content-online" class="tab-content hidden space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Trạng thái:</label>
            <select id="filterOnline" class="border rounded-md p-2 text-sm">
              <option value="all">Tất cả</option>
              <option value="active">Đang hoạt động</option>
              <option value="expired">Hết hạn</option>
            </select>
          </div>
          <div class="text-sm text-gray-500">Tổng: <span id="countOnline">0</span> vé</div>
        </div>

        <div id="list-online" class="grid gap-3"></div>

        <div id="empty-online" class="hidden text-center py-8 text-gray-500">
          <p class="mb-2">Bạn chưa có vé xem online.</p>
          <a href="{{ $_ENV['URL_WEB_BASE'] }}/lich-chieu" class="inline-block bg-red-600 text-white px-4 py-2 rounded-md">Mua gói xem online</a>
        </div>
      </div>
    </section>
  </div>

  <!-- Modal chi tiết vé -->
  <div id="ticketModal" class="fixed inset-0 z-[99999] mt-10 flex items-center justify-center bg-black/40 p-4 overflow-y-auto hidden">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
      <div class="p-4 border-b flex justify-between items-center">
        <h3 class="font-semibold">Chi tiết vé</h3>
        <button id="closeModal" class="text-gray-600 hover:text-black">✕</button>
      </div>
      <div id="modalBody" class="p-4 space-y-4"></div>
    </div>
  </div>

<script>
const tabTheater = document.getElementById('tab-theater');
const tabOnline = document.getElementById('tab-online');
const contentTheater = document.getElementById('content-theater');
const contentOnline = document.getElementById('content-online');
const listTheater = document.getElementById('list-theater');
const listOnline = document.getElementById('list-online');
const emptyTheater = document.getElementById('empty-theater');
const emptyOnline = document.getElementById('empty-online');
const countTheater = document.getElementById('countTheater');
const countOnline = document.getElementById('countOnline');
const filterTheater = document.getElementById('filterTheater');
const filterOnline = document.getElementById('filterOnline');
const modal = document.getElementById('ticketModal');
const closeModalBtn = document.getElementById('closeModal');
const modalBody = document.getElementById('modalBody');

let theaterTickets = [];
let onlineTickets = [];

// Tab switching

function switchTab(tab) {
  if(tab==='theater'){
    tabTheater.classList.add('bg-red-600','text-white'); tabTheater.classList.remove('bg-transparent','text-gray-600');
    tabOnline.classList.remove('bg-red-600','text-white'); tabOnline.classList.add('bg-transparent','text-gray-600');
    contentTheater.classList.remove('hidden'); contentOnline.classList.add('hidden');
    renderTheater(theaterTickets);
  } else {
    tabOnline.classList.add('bg-red-600','text-white'); tabOnline.classList.remove('bg-transparent','text-gray-600');
    tabTheater.classList.remove('bg-red-600','text-white'); tabTheater.classList.add('bg-transparent','text-gray-600');
    contentOnline.classList.remove('hidden'); contentTheater.classList.add('hidden');
    renderOnline(onlineTickets);
  }
}

tabTheater.addEventListener('click',()=>switchTab('theater'));
tabOnline.addEventListener('click',()=>switchTab('online'));

// Fetch dữ liệu vé tại rạp

async function loadTheaterTickets() {
  try {
    const res = await fetch(`${baseUrl}/api/doc-don-hang`);
    if(!res.ok) throw new Error(`HTTP lỗi ${res.status}`);
    const data = await res.json();
    theaterTickets = Array.isArray(data.data) ? data.data : [];
    renderTheater(theaterTickets);
  } catch(err) {
    console.error(err);
    renderTheater([]);
  }
}

// Fetch dữ liệu vé online

async function loadOnlineTickets() {
  try {
    const res = await fetch(`${baseUrl}/api/doc-don-hang-online`);
    if(!res.ok) throw new Error(`HTTP lỗi ${res.status}`);
    const data = await res.json();
    onlineTickets = Array.isArray(data.data) ? data.data : [];
    renderOnline(onlineTickets);
  } catch(err) {
    console.error(err);
    renderOnline([]);
  }
}

// Render vé tại rạp

function renderTheater(list){
  listTheater.innerHTML='';
  if(!list || list.length===0){emptyTheater.classList.remove('hidden'); countTheater.textContent=0; return;}
  emptyTheater.classList.add('hidden');

  list.forEach(ticket=>{
    const totalPrice = Number(ticket.tong_tien||0);
    const batDau = ticket.suat_chieu?.batdau ? new Date(ticket.suat_chieu.batdau) : null;
    const suatChieuFormatted = batDau ? `${batDau.toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'})} ${batDau.toLocaleDateString('vi-VN',{weekday:'long',day:'2-digit',month:'2-digit',year:'numeric'})}` : 'Chưa có thông tin';
    const card = document.createElement('div');
    card.className='border rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white';
    card.innerHTML=`
      <div class="flex-1">
        <div class="text-lg font-semibold">${ticket.suat_chieu?.phim?.ten_phim || 'Chưa xác định phim'}</div>
        <div class="text-sm text-gray-500">${ticket.suat_chieu?.phong_chieu?.rap_chieu_phim?.ten || 'Chưa xác định rạp'} · ${ticket.suat_chieu?.phong_chieu?.ten || 'Chưa xác định phòng'}</div>
        <div class="text-sm text-gray-600 mt-2">${suatChieuFormatted}</div>
      </div>
      <div class="flex-shrink-0 flex items-center gap-3 mt-3 sm:mt-0">
        <div class="text-right">
          <div class="text-sm text-gray-500">Tổng</div>
          <div class="font-bold text-lg">${totalPrice.toLocaleString('vi-VN')} ₫</div>
        </div>
        <div class="flex flex-col gap-2">
          <button class="btn-detail bg-gray-100 text-gray-700 px-3 py-1 rounded" data-id="${ticket.id}">Chi tiết</button>
          <div class="text-sm font-medium ${ticket.trang_thai===0?'text-red-600':ticket.trang_thai===1?'text-green-600':ticket.trang_thai===2?'text-blue-600':'text-gray-500'}">
            ${ticket.trang_thai===0?'Đã hoàn vé':ticket.trang_thai===1?'Sắp chiếu':ticket.trang_thai===2?'Đã thanh toán':'Không xác định'}
          </div>
        </div>
      </div>
    `;
    listTheater.appendChild(card);
  });

  countTheater.textContent = list.length;

  document.querySelectorAll('.btn-detail').forEach(btn=>{
    btn.addEventListener('click', e => openTicketDetail(e.currentTarget.dataset.id));
  });
}

// Render vé online
    function slugify(str) {
        return str
            .toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]+/g, "-")
            .replace(/^-+|-+$/g, "");
    }

    function base64Encode(str) {
        return btoa(unescape(encodeURIComponent(str)));
    }

function renderOnline(list){
  listOnline.innerHTML='';
  if(!list || list.length===0){
    emptyOnline.classList.remove('hidden'); 
    countOnline.textContent=0; 
    return;
  }
  emptyOnline.classList.add('hidden');

  list.forEach(ticket=>{
    const totalPrice = Number(ticket.tong_tien||0);
    
    // link tới chi tiết phim online
    const encoded = base64Encode(ticket.phim?.id + salt);
    const url = `${baseUrl}/dat-ve-online/${slugify(ticket.phim?.ten_phim)}-${encoded}`;

    const card = document.createElement('a');
    card.href = url;
    card.className='border rounded-lg p-4 bg-white flex justify-between items-center hover:bg-gray-50 transition';
    card.innerHTML=`
      <div>
        <div class="text-lg font-semibold">${ticket.phim?.ten_phim || 'Chưa xác định phim'}</div>
        <div class="text-sm font-medium ${ticket.trang_thai===0?'text-red-600':ticket.trang_thai===1?'text-green-600':ticket.trang_thai===2?'text-blue-600':'text-gray-500'}">
            ${ticket.trang_thai===0?'Đã hoàn vé':ticket.trang_thai===1?'Sắp chiếu':ticket.trang_thai===2?'Đã thanh toán':'Không xác định'}
          </div>
      </div>
      <div class="text-sm font-semibold">${totalPrice.toLocaleString('vi-VN')} ₫</div>
    `;
    listOnline.appendChild(card);
  });

  countOnline.textContent = list.length;
}

// Mở modal chi tiết vé

async function openTicketDetail(id) {
  try {
    const res = await fetch(`${baseUrl}/api/doc-chi-tiet-don-hang/${id}`);
    if(!res.ok) throw new Error(`HTTP lỗi ${res.status}`);
    const data = await res.json();
    const ve = Array.isArray(data.data)?data.data[0]:data.data;
    openModalDetail(ve);
  } catch(err) {
    console.error(err);
    alert("Lỗi khi lấy chi tiết vé.");
  }
}

// Hiển thị modal chi tiết vé

function openModalDetail(ve){
  if(!ve){
    modalBody.innerHTML='<p class="text-center text-gray-500">Không có thông tin vé.</p>';
    modal.classList.remove('hidden');
    return;
  }

  const isCancelled = ve.trang_thai===0;
  const startTime = ve.ve?.[0]?.suat_chieu?.batdau ? new Date(ve.ve[0].suat_chieu.batdau) : null;
  const now = new Date();
  const canCancel = !isCancelled && startTime && (now < new Date(startTime.getTime() - 15 * 60 * 1000));


  let html = `
    <div class="relative ${isCancelled?'modal-cancelled':''} space-y-2 p-2 max-h-[80vh] overflow-y-auto">
      ${isCancelled?`<div class="modal-cancelled-overlay"><span>Đã hoàn vé</span></div>`:''}
      <div class="p-3 bg-white rounded shadow">
        <h5 class="font-bold text-lg flex items-center gap-2">
          ${ve.ve?.[0]?.suat_chieu?.phim?.ten_phim||'Không xác định'}
          <span class="inline-block px-2 py-0.5 text-xs font-semibold text-white bg-red-500 rounded">
            ${ve.ve?.[0]?.suat_chieu?.phim?.do_tuoi||'C'}
          </span>
        </h5>
      </div>
      <div class="p-3 bg-white rounded shadow text-sm text-gray-700 grid grid-cols-2 gap-4">
        <div class="space-y-1">
          <p><span class="font-semibold">Rạp:</span> ${ve.ve?.[0]?.suat_chieu?.phong_chieu?.rap_chieu_phim?.ten||'-'}</p>
          <p><span class="font-semibold">Phòng:</span> ${ve.ve?.[0]?.suat_chieu?.phong_chieu?.ten||'-'}</p>
          <p><span class="font-semibold">Loại phòng:</span> ${(ve.ve?.[0]?.suat_chieu?.phong_chieu?.loai_phongchieu||'-').toUpperCase()}</p>
        </div>
        <div class="space-y-1">
          <p><span class="font-semibold">Ngày chiếu:</span> ${startTime?startTime.toLocaleDateString('vi-VN',{ weekday:'long', day:'2-digit', month:'2-digit', year:'numeric' }):'-'}</p>
          <p><span class="font-semibold">Thời gian:</span> ${startTime?startTime.toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'}):'-'} - ${ve.ve?.[0]?.suat_chieu?.ketthuc?new Date(ve.ve[0].suat_chieu.ketthuc).toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'}):'-'}</p>
          <p><span class="font-semibold">Tổng tiền:</span> ${Number(ve.tong_tien||0).toLocaleString()} ₫</p>
        </div>
      </div>
      <div class="p-2 bg-white rounded shadow text-sm">
        <span class="font-semibold">Ghế:</span> <span>${ve.ve?.map(v=>v.ghe?.so_ghe).filter(Boolean).join(', ')||'-'}</span>
      </div>
      <div class="p-3 bg-white rounded shadow text-sm text-gray-700 grid grid-cols-2 gap-4">
        <div class="space-y-2">
          <div>
            <h4 class="font-semibold mb-1">Thức ăn kèm:</h4>
            ${ve.ve?.flatMap(v=>v.don_hang?.chi_tiet_don_hang||[]).map(item=>`
              <div class="flex justify-between border-b border-gray-100 py-1">
                <span>${item.san_pham?.ten||'-'} x ${item.so_luong||0}</span>
                <span class="font-semibold">${Number(item.thanh_tien||0).toLocaleString()} ₫</span>
              </div>`).join('')||'<p>Không có</p>'}
          </div>
          <div>
            <h4 class="font-semibold mb-1">Thẻ quà tặng:</h4>
            <div class="flex justify-between border-b border-gray-100 py-1">
              ${ve.the_qua_tang_su_dung>0?`<span>${Number(ve.the_qua_tang_su_dung||0).toLocaleString()} ₫</span>`:'<span>Không có</span>'}
            </div>
          </div>
        </div>
        <div class="flex flex-col items-center gap-1">
          <span class="font-semibold text-sm">Mã vé</span>
          <span class="text-blue-600 font-mono text-base">${ve.ma_ve||'-'}</span>
          <img src="${ve.qr_code||''}" alt="QR Code" class="w-24 h-24 ${ve.qr_code?'':'hidden'}">
        </div>
      </div>
      ${canCancel ? `
        <div class="p-2 bg-white rounded shadow text-sm">
          <button id="btnCancelTicket" class="w-full bg-red-600 text-white px-3 py-2 rounded">Hoàn vé</button>
        </div>` : ''
      }
    </div>
  `;

  modalBody.innerHTML=html;
  modal.classList.remove('hidden');

  // Gắn sự kiện hủy vé nếu có nút
  const btnCancelTicket = document.getElementById('btnCancelTicket');
  btnCancelTicket?.addEventListener('click', async () => {
    if(!confirm("LƯU Ý: Số tiền đã thanh toán sẽ được hoàn lại vào Thẻ quà tặng EPIC.\nBạn có chắc muốn hoàn vé này?")) return;

    try {
      let resGift = await fetch(`${baseUrl}/api/tao-the-qua-tang`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({id_donhang: ve.id, gia_tri: ve.tong_tien})
      });
      let dataGift = await resGift.json();
      if(!dataGift.success) throw new Error(dataGift.message||'Lỗi tạo thẻ quà tặng');

      await fetch(`${baseUrl}/api/cap-nhat-trang-thai-don-hang`, {
        method:'PUT',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({id: ve.id})
      });

      for(const v of ve.ve){
        const donHangId = v.don_hang?.id;
        if(donHangId){
          await fetch(`${baseUrl}/api/cap-nhat-trang-thai-ve`, {
            method:'PUT',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({donhang_id: donHangId})
          });
        }
      }

      alert(`Vé ${ve.ma_ve || ve.id} đã được hủy. Số tiền hoàn lại đã vào thẻ quà tặng.`);
      closeModal();
      renderTheater(theaterTickets.map(t => t.id === ve.id ? {...t, trang_thai:0} : t));

    } catch(err){
      console.error(err);
      alert("Lỗi khi hủy vé: "+err.message);
    }
  });
}


// Đóng modal
function closeModal(){ modal.classList.add('hidden'); }
closeModalBtn.addEventListener('click',closeModal);
modal.addEventListener('click', e => { if(e.target===modal) closeModal(); });

// Filter
filterTheater.addEventListener('change',()=>{
  const v = filterTheater.value; const now=new Date();
  let filtered = [];
  if(v==='all') filtered = theaterTickets;
  else if(v==='upcoming') filtered = theaterTickets.filter(t=>t.suat_chieu?.batdau && new Date(t.suat_chieu.batdau)>now);
  else if(v==='used') filtered = theaterTickets.filter(t=>t.suat_chieu?.batdau && new Date(t.suat_chieu.batdau)<=now);
  renderTheater(filtered);
});

filterOnline.addEventListener('change',()=>{
  const v = filterOnline.value;
  renderOnline(onlineTickets.filter(t=>v==='all'?true:t.status===v));
});

// Load dữ liệu lúc đầu
loadTheaterTickets();
loadOnlineTickets();
switchTab('theater');
</script>



@include('customer.layout.footer')
</body>
</html>

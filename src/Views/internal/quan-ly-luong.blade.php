@extends('internal.layout')

@section('title', 'Quản lý lương nhân viên')

@section('breadcrumbs')
<li>
  <div class="flex items-center">
    <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
    </svg>
    <span class="ml-1 text-gray-500 hover:text-gray-700 text-sm font-medium">Quản lý lương nhân viên</span>
  </div>
</li>
@endsection

@section('content')
<div class="px-6 py-6">
  <div class="flex justify-between items-center mb-6">
    <div class="flex items-center space-x-2">
      <button id="prev-month" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg shadow">&lt; Tháng trước</button>
      <h2 id="month-title" class="text-xl font-bold text-gray-800 min-w-[160px] text-center"></h2>
      <button id="next-month" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg shadow">Tháng sau &gt;</button>
    </div>
    <button id="btn-export" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow">
      <i class="fas fa-file-excel"></i> Xuất Excel
    </button>
  </div>

  <div class="overflow-x-auto bg-white shadow rounded-xl">
    <table class="w-full text-sm border-collapse">
      <thead>
        <tr class="bg-gray-100 text-gray-700 text-center font-semibold">
          <th class="p-2 border w-[50px]">#</th>
          <th class="p-2 border w-[160px]">Nhân viên</th>
          <th class="p-2 border w-[100px]">Tháng</th>
          <th class="p-2 border w-[120px]">Số ngày công</th>
          <th class="p-2 border w-[100px]">Số giờ công</th>
          <th class="p-2 border w-[120px]">Tổng lương (đ)</th>
          <th class="p-2 border w-[100px]">Thưởng (đ)</th>
          <th class="p-2 border w-[120px]">Tổng thu nhập</th>
          <th class="p-2 border w-[100px]">Trạng thái</th>
          <th class="p-2 border w-[220px]">Hành động</th>
        </tr>
      </thead>
      <tbody id="salary-table" class="text-center"></tbody>
    </table>
  </div>
</div>

<!-- Modal thưởng -->
<div id="bonus-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
  <div class="bg-white rounded-xl shadow-xl w-[400px] p-6">
    <h3 class="text-lg font-semibold mb-4 text-gray-700">Cập nhật thưởng nhân viên</h3>
    <form id="bonus-form">
      <input type="hidden" id="bonus-id">
      <div class="mb-4">
        <label for="bonus-amount" class="block text-sm font-medium text-gray-600 mb-1">Số tiền thưởng (đ):</label>
        <input id="bonus-amount" type="number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-100" placeholder="Nhập số tiền thưởng...">
      </div>
      <div class="flex justify-end space-x-3">
        <button type="button" id="cancel-bonus" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg">Hủy</button>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Lưu</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal chi tiết -->
<div id="detail-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
  <div class="bg-white rounded-xl shadow-2xl w-[90vw] max-w-[1200px] max-h-[90vh] overflow-y-auto p-6">
    <div class="flex justify-between items-center mb-4">
      <h3 id="detail-title" class="text-xl font-semibold text-gray-800"></h3>
      <button id="close-detail" class="text-gray-600 hover:text-gray-900 text-xl">&times;</button>
    </div>
    
    <table class="w-full border-collapse text-sm text-center">
      <thead class="bg-gray-100 text-gray-700 font-semibold">
        <tr>
          <th class="p-2 border w-[100px]">Ngày</th>
          <th class="p-2 border w-[180px]">Giờ vào</th>
          <th class="p-2 border w-[180px]">Giờ ra</th>
          <th class="p-2 border w-[100px]">Số giờ</th>
          <th class="p-2 border">Ghi chú</th>
        </tr>
      </thead>
      <tbody id="attendance-body"></tbody>
    </table>

    <div class="flex justify-end mt-4 space-x-3">
      <button id="save-attendance" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Lưu thay đổi</button>
      <button id="cancel-detail" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg">Đóng</button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}";
  const salaryTable = document.getElementById("salary-table");
  const monthTitle = document.getElementById("month-title");
  const prevBtn = document.getElementById("prev-month");
  const nextBtn = document.getElementById("next-month");
  const btnExport = document.getElementById("btn-export");

  const bonusModal = document.getElementById("bonus-modal");
  const bonusForm = document.getElementById("bonus-form");
  const bonusId = document.getElementById("bonus-id");
  const bonusAmount = document.getElementById("bonus-amount");
  const cancelBonus = document.getElementById("cancel-bonus");

  const detailModal = document.getElementById("detail-modal");
  const detailTitle = document.getElementById("detail-title");
  const attendanceBody = document.getElementById("attendance-body");
  const closeDetail = document.getElementById("close-detail");
  const cancelDetail = document.getElementById("cancel-detail");
  const saveAttendance = document.getElementById("save-attendance");

  let currentDate = new Date();
  let currentData = [];
  let currentAttendance = [];
  let detailLocked = false;

  function formatMonth(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
  }

  async function loadSalaryData() {
    const thang = formatMonth(currentDate);
    monthTitle.textContent = `${currentDate.toLocaleString('vi-VN', { month: 'long' })} ${currentDate.getFullYear()}`;

    try {
        // Lấy danh sách nhân viên
        const resNhanVien = await fetch(`${baseUrl}/api/nhan-vien`);
        const jsonNhanVien = await resNhanVien.json();
        const nhanVienList = Array.isArray(jsonNhanVien.data) ? jsonNhanVien.data : [];

        // Lấy dữ liệu chấm công toàn rạp
        const resChamCong = await fetch(`${baseUrl}/api/doc-cham-cong-toan-rap?thang=${thang}`);
        const jsonChamCong = await resChamCong.json();
        const chamCongList = Array.isArray(jsonChamCong.data) ? jsonChamCong.data : [];

        // Gom nhóm dữ liệu chấm công theo nhân viên
        const chamCongMap = {};

        chamCongList.forEach(item => {
        const idNV = item.nhan_vien?.id;
        if (!idNV) return;

        if (!chamCongMap[idNV]) {
            chamCongMap[idNV] = {
            so_cong: 0,
            gio_cong: 0,
            tong_luong: 0,
            chamsoc: []
            };
        }

        const gioVao = new Date(item.gio_vao);
        const gioRa = new Date(item.gio_ra);

        // Tính số giờ làm, làm tròn 2 chữ số
        let soGio = Math.max(0, (gioRa - gioVao) / (1000 * 60 * 60));
        soGio = parseFloat(soGio.toFixed(2)); // làm tròn 2 chữ số

        const heSo = Number(item.he_so ?? 1);
        const luongGio = 30000; // tiền mỗi giờ

        chamCongMap[idNV].so_cong += 1;
        chamCongMap[idNV].gio_cong += soGio;
        chamCongMap[idNV].tong_luong += soGio * luongGio * heSo;
        chamCongMap[idNV].chamsoc.push(item);
        });

        // Hợp nhất danh sách nhân viên + chấm công
        currentData = nhanVienList.map(nv => {
        const cham = chamCongMap[nv.id] || { so_cong: 0, gio_cong: 0, tong_luong: 0, chamsoc: [] };

        // Làm tròn 2 chữ số cho giờ và lương
        const gioCong = parseFloat(cham.gio_cong.toFixed(2));
        const tongLuong = parseFloat(cham.tong_luong.toFixed(2));

        return {
            id: nv.id,
            ten: nv.ten,
            thang,
            so_cong: cham.so_cong,
            gio_cong: gioCong,
            tong_luong: tongLuong,
            thuong: 0,
            trang_thai: 0,
            chamsoc: cham.chamsoc
        };
        });
        // Gọi API thưởng và gán vào currentData
        for (let nv of currentData) {
          try {
            const resThuong = await fetch(`${baseUrl}/api/lay-thuong/${nv.id}?thang=${thang}`);
            const jsonThuong = await resThuong.json();

            if (jsonThuong.success && jsonThuong.data.length > 0) {
              // Lọc thưởng theo tháng hiện tại
              const thuongThang = jsonThuong.data.find(t => t.thang === nv.thang);
              nv.thuong = thuongThang ? thuongThang.thuong : 0;
              nv.trang_thai = thuongThang ? thuongThang.trang_thai : 0;
            } else {
              nv.thuong = 0;
              nv.trang_thai = 0;
            }
          } catch (e) {
            nv.thuong = 0;
          }
        }
        //  Sắp xếp lương cao → thấp
        currentData.sort((a, b) => (b.tong_luong + b.thuong) - (a.tong_luong + a.thuong));

        renderTable();
    } catch (error) {
        console.error("Lỗi khi tải dữ liệu:", error);
        salaryTable.innerHTML = `<tr><td colspan="10" class="p-4 text-red-500">❌ Không thể kết nối API (${error.message})</td></tr>`;
    }
    }

  function renderTable() {
    salaryTable.innerHTML = '';

    currentData.forEach((nv, idx) => {
      const tongLuong = Number(nv.tong_luong ?? 0);
      const thuong = Number(nv.thuong ?? 0);
      const tongThuNhap = tongLuong + thuong;

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="p-2 border">${idx + 1}</td>
        <td class="p-2 border">${nv.ten}</td>
        <td class="p-2 border">${nv.thang}</td>
        <td class="p-2 border">${nv.so_cong}</td>
        <td class="p-2 border">${nv.gio_cong}</td>
        <td class="p-2 border text-green-700 font-semibold">${tongLuong.toLocaleString('vi-VN')} đ</td>
        <td class="p-2 border text-blue-600 font-semibold">${thuong.toLocaleString('vi-VN')} đ</td>
        <td class="p-2 border text-purple-700 font-bold">${tongThuNhap.toLocaleString('vi-VN')} đ</td>
        <td class="p-2 border">
          ${nv.trang_thai == 1 
            ? '<span class="text-green-600 font-semibold">Đã duyệt</span>'
            : '<span class="text-gray-500 italic">Chưa duyệt</span>'}
        </td>
        <td class="p-2 border space-x-2">
          ${nv.trang_thai == 1
            ? `<button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded view-detail"
                       data-id="${nv.id}" data-name="${nv.ten}" data-thang="${nv.thang}">Chi tiết</button>`
            : `
              <button class="bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1 rounded edit-bonus"
                      data-id="${nv.id}" data-thuong="${thuong}">Thưởng</button>
              <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded view-detail"
                      data-id="${nv.id}" data-name="${nv.ten}" data-thang="${nv.thang}">Chi tiết</button>
              <button class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded approve"
                      data-id="${nv.id}">Duyệt</button>
            `}
        </td>
      `;
      salaryTable.appendChild(tr);
    });

    // Sự kiện các nút
    document.querySelectorAll('.edit-bonus').forEach(btn => {
      btn.addEventListener('click', () => {
        bonusId.value = btn.dataset.id;
        bonusAmount.value = btn.dataset.thuong;
        bonusModal.classList.remove('hidden');
      });
    });

    document.querySelectorAll('.view-detail').forEach(btn => {
      btn.addEventListener('click', () => openDetail(btn.dataset.id, btn.dataset.name, btn.dataset.thang));
    });

    document.querySelectorAll('.approve').forEach(btn => {
      btn.addEventListener('click', async () => {
        const idNhanVien = parseInt(btn.dataset.id);
        const thang = formatMonth(currentDate);

        // Tìm nhân viên trong currentData để lấy dữ liệu
        const nv = currentData.find(n => n.id === idNhanVien);

        if (!confirm(`Bạn có chắc muốn duyệt lương cho ${nv.ten}?`)) return;

        try {
          const res = await fetch(`${baseUrl}/api/duyet-luong-thuong`, {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              id_nhanvien: idNhanVien,
              thang: thang,
              so_ngay_cong: nv.so_cong,
              so_gio_cong: nv.gio_cong,
              tong_luong: nv.tong_luong,
              tong_thu_nhap: nv.tong_luong + nv.thuong,
              trang_thai: 1
            })
          });

          const data = await res.json();
          if (data.success) {
            alert("Duyệt lương thành công!");
            loadSalaryData();
          } else {
            alert("Lỗi: " + data.message);
          }

        } catch (error) {
          alert("⚠️ Lỗi API duyệt!");
        }
      });
    });
  }

  bonusForm.addEventListener('submit', async e => {
    e.preventDefault();

    const idNhanVien = parseInt(bonusId.value);
    const thuong = parseInt(bonusAmount.value) || 0;
    const thang = formatMonth(currentDate);

    try {
      const res = await fetch(`${baseUrl}/api/tao-thuong`, {   
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          id_nhanvien: idNhanVien,
          thang: thang,
          thuong: thuong
        })
      });

      const data = await res.json();
      if (data.success) {
        alert("Lưu thưởng thành công!");
        bonusModal.classList.add('hidden');
        loadSalaryData();
      } else {
        alert("Lỗi: " + data.message);
      }
    } catch (error) {
      alert("Lỗi khi gọi API thưởng!");
    }
  });


  cancelBonus.addEventListener('click', () => bonusModal.classList.add('hidden'));

  async function openDetail(id, name, thang) {
    detailTitle.textContent = `Chấm công chi tiết - ${name} (${thang})`;

    try {
        const res = await fetch(`${baseUrl}/api/doc-cham-cong-toan-rap?thang=${thang}`);
        const json = await res.json();

        if (!json.success || !Array.isArray(json.data)) {
        attendanceBody.innerHTML = `<tr><td colspan="5" class="p-4 text-red-500">Không có dữ liệu chấm công.</td></tr>`;
        detailModal.classList.remove('hidden');
        return;
        }

        // Lọc ra những bản ghi thuộc nhân viên đang xem
        const records = json.data.filter(item => item.nhan_vien?.id == id);

        if (!records.length) {
        attendanceBody.innerHTML = `<tr><td colspan="5" class="p-4 text-gray-500 italic">Không có chấm công trong tháng này.</td></tr>`;
        detailModal.classList.remove('hidden');
        return;
        }

        // Lấy trạng thái duyệt từ bảng lương hiện tại (nếu có)
        const nv = currentData.find(x => x.id == id);
        detailLocked = nv?.trang_thai === 1;

        currentAttendance = records.map(item => {
        const gioVao = new Date(item.gio_vao);
        const gioRa = new Date(item.gio_ra);
        const soGio = calcHours(
            gioVao.toTimeString().slice(0, 5),
            gioRa.toTimeString().slice(0, 5)
        );
        return {
            id_ca: item.id,
            ngay: item.ngay,
            vao: gioVao.toTimeString().slice(0, 5),
            ra: gioRa.toTimeString().slice(0, 5),
            so_gio: soGio,
            he_so: item.he_so ?? 1,
            ghi_chu: item.cong_viec?.ten ?? ''
        };
        });

        renderAttendanceTable();
        detailModal.classList.remove('hidden');
    } catch (error) {
        console.error("Lỗi khi tải chi tiết chấm công:", error);
        attendanceBody.innerHTML = `<tr><td colspan="5" class="p-4 text-red-500">❌ Không thể tải dữ liệu (${error.message})</td></tr>`;
        detailModal.classList.remove('hidden');
    }
    }

    // Hiển thị bảng chi tiết chấm công trong modal
    function renderAttendanceTable() {
    attendanceBody.innerHTML = '';

    currentAttendance.forEach(d => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
        <td class="p-2 border">${d.ngay}</td>
        <td class="p-2 border">
            <input type="time" ${detailLocked ? 'disabled' : ''} 
            class="vao border rounded px-2 py-1 w-32 text-center" 
            value="${d.vao}">
        </td>
        <td class="p-2 border">
            <input type="time" ${detailLocked ? 'disabled' : ''} 
            class="ra border rounded px-2 py-1 w-32 text-center" 
            value="${d.ra}">
        </td>
        <td class="p-2 border gio-lam">${d.so_gio.toFixed(2)}</td>
        <td class="p-2 border text-sm text-gray-700">
            ${d.ghi_chu || '-'} 
            ${d.he_so > 1 ? `<span class="text-red-500 ml-2">(x${d.he_so})</span>` : ''}
        </td>
        `;
        attendanceBody.appendChild(tr);
    });

    // Cho phép cập nhật số giờ động (nếu chưa duyệt)
    if (!detailLocked) {
        attendanceBody.querySelectorAll('tr').forEach(tr => {
        const vaoInput = tr.querySelector('.vao');
        const raInput = tr.querySelector('.ra');
        const gioLamCell = tr.querySelector('.gio-lam');

        const updateHours = () => {
            const start = vaoInput.value;
            const end = raInput.value;

            // So sánh giờ: nếu giờ vào > giờ ra
            if (start >= end) {
                alert("Giờ vào không được sau giờ ra!");
                // Reset về giá trị cũ
                vaoInput.value = record.vao;
                raInput.value = record.ra;
                return;
            }

            const gio = calcHours(start, end);
            gioLamCell.textContent = gio.toFixed(2);

            const index = Array.from(attendanceBody.children).indexOf(tr);
            const record = currentAttendance[index];

            record.vao_full = `${record.ngay} ${start}:00`;
            record.ra_full  = `${record.ngay} ${end}:00`;
        };

        vaoInput.addEventListener('change', updateHours);
        raInput.addEventListener('change', updateHours);
        });
    }

    // Ẩn nút lưu nếu đã duyệt
    saveAttendance.style.display = detailLocked ? 'none' : 'inline-block';
    }

  function calcHours(start, end) {
    const [sh, sm] = start.split(':').map(Number);
    const [eh, em] = end.split(':').map(Number);
    const diff = (eh + em / 60) - (sh + sm / 60);
    return diff > 0 ? diff : 0;
  }

  saveAttendance.addEventListener('click', async () => {
    try {
      for (const record of currentAttendance) {
        const payload = {
          gio_vao: record.vao_full || `${record.ngay} ${record.vao}:00`,
          gio_ra:  record.ra_full  || `${record.ngay} ${record.ra}:00`,
          ghi_chu: record.ghi_chu || ""
        };

        await fetch(`${baseUrl}/api/gui-yeu-cau-nghi/${record.id_ca}`, {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });
      }

      alert("Cập nhật thành công!");
      detailModal.classList.add("hidden");
      loadSalaryData();
    } catch (error) {
      console.error(error);
      alert("Lỗi khi gửi yêu cầu nghỉ!");
    }
  });

  closeDetail.addEventListener('click', () => detailModal.classList.add('hidden'));
  cancelDetail.addEventListener('click', () => detailModal.classList.add('hidden'));

  prevBtn.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() - 1); loadSalaryData(); });
  nextBtn.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() + 1); loadSalaryData(); });

  btnExport.addEventListener('click', () => {
    if (!currentData.length) return alert("Không có dữ liệu để xuất!");
    const wsData = [["#", "Nhân viên", "Tháng", "Số ngày công", "Số giờ công", "Tổng lương", "Thưởng", "Tổng thu nhập", "Trạng thái"]];
    currentData.forEach((nv, i) => {
      wsData.push([
        i + 1,
        nv.ten_nhanvien,
        nv.thang,
        nv.so_cong,
        nv.gio_cong,
        nv.tong_luong,
        nv.thuong,
        nv.tong_luong + nv.thuong,
        nv.trang_thai ? "Đã duyệt" : "Chưa duyệt"
      ]);
    });
    const ws = XLSX.utils.aoa_to_sheet(wsData);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "BangLuong");
    XLSX.writeFile(wb, `BangLuong_${formatMonth(currentDate)}.xlsx`);
  });

  loadSalaryData();
});
</script>

@endsection

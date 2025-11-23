@extends('internal.layout')

@section('title', 'Xem lương theo tháng')

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-1 text-gray-500 hover:text-gray-700 text-sm font-medium">Bảng lương tháng</span>
    </div>
</li>
@endsection

@section('content')
<div class="px-4 py-6">
    <div class="flex justify-between items-center mb-4">
        <div>
            <button id="prev-month" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg shadow">&lt; Tháng trước</button>
            <button id="next-month" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg shadow">Tháng sau &gt;</button>
        </div>
        <h2 id="month-title" class="text-xl font-bold text-gray-800"></h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full table-fixed border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-700 text-sm font-semibold text-center">
                    <th class="p-2 border w-[40px]">Ngày</th>
                    <th class="p-2 border w-[80px]">Ca</th>
                    <th class="p-2 border w-[80px]">Giờ làm</th>
                    <th class="p-2 border w-[80px]">Hệ số</th>
                    <th class="p-2 border w-[120px]">Tiền lương (đ)</th>
                </tr>
            </thead>
            <tbody id="salary-body" class="text-center text-sm">
                <!-- Dữ liệu sẽ render ở đây -->
            </tbody>
            <tfoot>
                <tr class="bg-gray-200 font-semibold text-center">
                    <td colspan="4" class="p-2 border text-right">Thưởng:</td>
                    <td id="total-bonus" class="p-2 border text-green-700">0 đ</td>
                </tr>
                <tr class="bg-gray-200 font-semibold text-center">
                    <td colspan="4" class="p-2 border text-right">Tổng lương:</td>
                    <td id="total-salary" class="p-2 border text-green-700">0 đ</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}";
    const salaryBody = document.getElementById("salary-body");
    const totalSalaryEl = document.getElementById("total-salary");
    const totalBonusEl = document.getElementById("total-bonus");
    const monthTitle = document.getElementById("month-title");
    const prevBtn = document.getElementById("prev-month");
    const nextBtn = document.getElementById("next-month");

    let currentDate = new Date(); // mặc định tháng hiện tại
    const luongMotGio = 30000; // ví dụ: 30k/giờ
    const heSoMacDinh = 1.0;

    // Hàm định dạng YYYY-MM để gọi API
    function formatMonth(date) {
        const y = date.getFullYear();
        const m = (date.getMonth() + 1).toString().padStart(2, '0');
        return `${y}-${m}`;
    }

    // Gọi API lấy chấm công theo tháng
    async function loadSalaryData(date) {
        const thang = formatMonth(date);
        monthTitle.innerText = `${date.toLocaleString('vi-VN', { month: 'long' })} ${date.getFullYear()}`;
        salaryBody.innerHTML = `<tr><td colspan="5" class="p-4 text-gray-500 italic">Đang tải dữ liệu...</td></tr>`;

        let total = 0;
        let totalBonus = 0; 

        try {
            const res = await fetch(baseUrl + `/api/doc-cham-cong?thang=${thang}`);
            const json = await res.json();

            if (!json.success || !json.data || json.data.length === 0) {
                salaryBody.innerHTML = `<tr><td colspan="5" class="p-4 text-gray-500 italic">Không có dữ liệu trong tháng này</td></tr>`;
                totalSalaryEl.innerText = '0 đ';
                return;
            }

            try {
                const resThuong = await fetch(baseUrl + `/api/lay-thuong-nhan-vien?thang=${thang}`);
                const thuongJson = await resThuong.json();

                if (thuongJson.success && thuongJson.data) {
                    totalBonus = thuongJson.data.thuong || 0;
                }
            } catch (e) {
                console.warn("Không lấy được thưởng, để mặc định = 0");
            }

            salaryBody.innerHTML = '';

            // 3️⃣ XỬ LÝ CHẤM CÔNG + LƯƠNG
            json.data.forEach(item => {
                const ngay = new Date(item.ngay).getDate();
                const ca = item.ca || '-';
                const gioVao = item.gio_vao ? new Date(item.gio_vao) : null;
                const gioRa = item.gio_ra ? new Date(item.gio_ra) : null;

                let soGio = 0;
                if (gioVao && gioRa) {
                    soGio = Math.max(0, (gioRa - gioVao) / (1000 * 60 * 60));
                }

                const heSo = item.he_so ?? heSoMacDinh;
                const tienLuong = Math.round(soGio * luongMotGio * heSo);
                total += tienLuong;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="p-2 border">${ngay}</td>
                    <td class="p-2 border">${ca}</td>
                    <td class="p-2 border">
                        ${soGio.toFixed(2)} 
                        (${gioVao ? gioVao.toLocaleTimeString('vi-VN', { hour12: false, hour: '2-digit', minute: '2-digit' }) : '-'} 
                        - 
                        ${gioRa ? gioRa.toLocaleTimeString('vi-VN', { hour12: false, hour: '2-digit', minute: '2-digit' }) : '-'})
                    </td>
                    <td class="p-2 border">${heSo}</td>
                    <td class="p-2 border text-green-700 font-semibold">${tienLuong.toLocaleString('vi-VN')} đ</td>
                `;
                salaryBody.appendChild(tr);
            });

            // 4️⃣ HIỂN THỊ THƯỞNG & TỔNG
            totalBonusEl.innerText = totalBonus.toLocaleString('vi-VN') + ' đ';
            totalSalaryEl.innerText = (total + totalBonus).toLocaleString('vi-VN') + ' đ';

        } catch (err) {
            console.error(err);
            salaryBody.innerHTML = `<tr><td colspan="5" class="p-4 text-red-600 italic">Lỗi khi tải dữ liệu!</td></tr>`;
        }
    }

    // Điều hướng tháng
    prevBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        loadSalaryData(currentDate);
    });
    nextBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        loadSalaryData(currentDate);
    });

    // Gọi mặc định tháng hiện tại
    loadSalaryData(currentDate);
});
</script>

@endsection

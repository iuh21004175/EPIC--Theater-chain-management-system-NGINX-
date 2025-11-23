import Spinner from './util/spinner.js';

document.addEventListener('DOMContentLoaded', async function() {
    // Lấy danh sách vị trí công việc từ API
    let viTriCongViecs = [];
    let phanCongTuan = []; // Lưu dữ liệu phân công của tuần hiện tại
    // Fetch vị trí công việc từ API
    async function fetchViTriCongViecs() {
        try {
            const url = document.getElementById('nv-list')?.dataset.url || '';
            const res = await fetch(`${url}/api/vi-tri-cong-viec`);
            const data = await res.json();  
            if (data.success && Array.isArray(data.data)) {
                viTriCongViecs = data.data;
            } else {
                viTriCongViecs = [];
            }
        } catch (e) {
            viTriCongViecs = [];
        }
    }
    async function fetchPhanCongTuan(startDate, endDate) {
        const url = phancongMainTbody.dataset.url || '';
        const res = await fetch(`${url}/api/phan-cong?bat_dau=${startDate}&ket_thuc=${endDate}`);
        const data = await res.json();
        if (data.success && Array.isArray(data.data)) {
            phanCongTuan = data.data;
        } else {
            phanCongTuan = [];
        }
    }
    await fetchViTriCongViecs();

    // --- Nhân viên phân trang ---
    let nhanViens = [];
    let nhanViensFiltered = []; // Danh sách đã lọc
    let nvPagination = { current_page: 1, per_page: 10, total: 0, total_pages: 1 };
    const nvList = document.getElementById('nv-list');
    const nvPaginationBar = document.getElementById('nv-pagination-bar');

    async function fetchNhanViens(page = 1) {
        const spinner = Spinner.show({ target: '#nv-list', size: 'sm', text: 'Đang tải...' });
        try {
            const url = nvList.dataset.url || '';
            const res = await fetch(`${url}/api/nhan-vien?page=${page}&per_page=10`);
            const data = await res.json();
            if (data.success && Array.isArray(data.data)) {
                nhanViens = data.data;
                nhanViensFiltered = [...nhanViens]; // Reset filter
                nvPagination = data.pagination;
            } else {
                nhanViens = [];
                nhanViensFiltered = [];
                nvPagination = { current_page: 1, per_page: 10, total: 0, total_pages: 1 };
            }
        } catch (e) {
            nhanViens = [];
            nhanViensFiltered = [];
            nvPagination = { current_page: 1, per_page: 10, total: 0, total_pages: 1 };
        } finally {
            Spinner.hide(spinner);
        }
    }

    function renderNhanVienList() {
        const activeNVs = nhanViensFiltered.filter(nv => nv.trang_thai == 1);
        
        // Cập nhật số lượng
        const countBadge = document.getElementById('nv-count');
        if (countBadge) {
            countBadge.textContent = activeNVs.length;
        }
        
        nvList.innerHTML = activeNVs.map(nv => {
            const hasAvatar = nv.avatar_url && nv.avatar_url.trim() !== '';
            const firstChar = nv.ten ? nv.ten.trim().charAt(0).toUpperCase() : '?';
            return `
            <div class="nv-card flex items-center gap-3 p-3 border border-gray-200 rounded-lg bg-white hover:bg-blue-50 hover:border-blue-300 cursor-move transition-all shadow-sm hover:shadow-md"
             draggable="true" data-id="${nv.id}">
            ${
                hasAvatar
                ? `<img src="${nv.avatar_url}" class="w-12 h-12 rounded-full border-2 border-gray-200" alt="${firstChar}">`
                : `<div class="w-12 h-12 flex items-center justify-center rounded-full border-2 border-blue-300 bg-gradient-to-br from-blue-400 to-blue-600 text-white font-bold text-xl shadow-md">${firstChar}</div>`
            }
            <div class="flex-1">
                <div class="font-semibold text-gray-800">${nv.ten}</div>
                <div class="text-xs text-gray-500 mt-0.5">Kéo để phân công</div>
            </div>
            </div>
            `;
        }).join('');
        // Drag event
        nvList.querySelectorAll('.nv-card').forEach(card => {
            card.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('nvId', this.dataset.id);
            });
        });
        renderNvPagination();
    }

    function renderNvPagination() {
        if (nvPagination.total_pages <= 1) {
            nvPaginationBar.innerHTML = '';
            return;
        }
        let html = '';
        for (let i = 1; i <= nvPagination.total_pages; i++) {
            html += `<button class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors ${
                i === nvPagination.current_page 
                ? 'bg-blue-600 text-white shadow-md' 
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }" data-page="${i}">${i}</button>`;
        }
        nvPaginationBar.innerHTML = html;
        nvPaginationBar.querySelectorAll('button').forEach(btn => {
            btn.onclick = async function() {
                await fetchNhanViens(Number(this.dataset.page));
                renderNhanVienList();
            };
        });
    }

    // Filter nhân viên theo tên
    const nvFilterInput = document.getElementById('nv-filter');
    if (nvFilterInput) {
        nvFilterInput.addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase().trim();
            if (searchText === '') {
                nhanViensFiltered = [...nhanViens];
            } else {
                nhanViensFiltered = nhanViens.filter(nv => 
                    nv.ten.toLowerCase().includes(searchText)
                );
            }
            renderNhanVienList();
        });
    }

    let currentWeekStart = dayjs().startOf('week').add(1, 'day'); // Thứ 2 tuần hiện tại

    function updateWeekTitle() {
        const weekTitle = document.getElementById('week-title');
        const weekNumber = currentWeekStart.isoWeek();
        const start = currentWeekStart.format('DD/MM/YYYY');
        const end = currentWeekStart.add(6, 'day').format('DD/MM/YYYY');
        weekTitle.textContent = `Tuần ${weekNumber} (${start} - ${end})`;
        
        // Vô hiệu hóa nút sao chép nếu là tuần hiện tại hoặc quá khứ
        const btnCopy = document.getElementById('btn-copy-week');
        const today = dayjs();
        const currentWeekMonday = today.startOf('week').add(1, 'day');
        
        // So sánh bằng timestamp hoặc isBefore/isSame
        if (currentWeekStart.isBefore(currentWeekMonday, 'day') || currentWeekStart.isSame(currentWeekMonday, 'day')) {
            // Tuần hiện tại hoặc quá khứ
            btnCopy.disabled = true;
            btnCopy.title = 'Chỉ có thể sao chép cho tuần tương lai';
        } else {
            // Tuần tương lai
            btnCopy.disabled = false;
            btnCopy.title = 'Sao chép phân công từ tuần trước sang tuần này';
        }
    }

    // --- Phân công ---
    const phancongHeaderRow = document.getElementById('phancong-header-row');
    const phancongMainTbody = document.getElementById('phancong-main-tbody');
    const caLabels = [
        { key: 'morning', label: 'Ca sáng' },
        { key: 'afternoon', label: 'Ca chiều' },
        { key: 'evening', label: 'Ca tối' }
    ];
    const thuLabels = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'CN'];

    let ngayLoaiMap = {}; // { 'YYYY-MM-DD': 'le' | 'tet' | 'cuoituan' | 'thuong' }

    // Sao chép phân công từ tuần trước
    async function copyPreviousWeek() {
        const confirmCopy = confirm('Bạn có chắc muốn sao chép phân công từ tuần trước?\n\nLưu ý: Chỉ sao chép vào các ô trống trong tuần hiện tại.');
        if (!confirmCopy) return;

        const spinner = Spinner.show({ text: 'Đang sao chép phân công...', overlay: true });
        try {
            // Lấy phân công tuần trước
            const prevWeekStart = currentWeekStart.subtract(7, 'day');
            const prevStart = prevWeekStart.format('YYYY-MM-DD');
            const prevEnd = prevWeekStart.add(6, 'day').format('YYYY-MM-DD');
            
            const url = phancongMainTbody.dataset.url || '';
            const res = await fetch(`${url}/api/phan-cong?bat_dau=${prevStart}&ket_thuc=${prevEnd}`);
            const data = await res.json();
            
            if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
                alert('Tuần trước không có phân công nào để sao chép!');
                return;
            }
            
            const prevPhanCong = data.data;
            let copiedCount = 0;
            let skippedCount = 0;
            
            // Duyệt qua từng phân công tuần trước
            for (const pc of prevPhanCong) {
                // Tính ngày tương ứng tuần hiện tại (cộng 7 ngày)
                const newDate = dayjs(pc.ngay).add(7, 'day').format('YYYY-MM-DD');
                
                // Kiểm tra xem ngày mới có trong tuần hiện tại không
                const currentStart = currentWeekStart.format('YYYY-MM-DD');
                const currentEnd = currentWeekStart.add(6, 'day').format('YYYY-MM-DD');
                
                if (newDate < currentStart || newDate > currentEnd) {
                    continue; // Bỏ qua nếu không trong tuần hiện tại
                }
                
                // Xác định ca
                let caKey = '';
                if (pc.ca === 'Ca sáng') caKey = 'morning';
                else if (pc.ca === 'Ca chiều') caKey = 'afternoon';
                else if (pc.ca === 'Ca tối') caKey = 'evening';
                
                // Kiểm tra xem ô đích đã có nhân viên này chưa
                const existingPc = phanCongTuan.find(p => 
                    p.ngay === newDate && 
                    p.ca === pc.ca && 
                    p.id_nhanvien === pc.id_nhanvien &&
                    p.id_congviec === pc.id_congviec
                );
                
                if (existingPc) {
                    skippedCount++;
                    continue; // Bỏ qua nếu đã tồn tại
                }
                
                // Tạo phân công mới
                const formData = new FormData();
                formData.append('id_nhanvien', pc.id_nhanvien);
                formData.append('id_congviec', pc.id_congviec);
                formData.append('ngay', newDate);
                formData.append('ca', pc.ca);
                
                const createRes = await fetch(`${url}/api/phan-cong`, {
                    method: 'POST',
                    body: formData
                });
                
                const createData = await createRes.json();
                if (createData.success) {
                    copiedCount++;
                }
            }
            
            // Reload lại bảng phân công
            await reloadPhanCongTable();
            
            // Thông báo kết quả
            alert(`Sao chép thành công!\n\n✓ Đã sao chép: ${copiedCount} phân công\n${skippedCount > 0 ? `⊘ Đã bỏ qua: ${skippedCount} phân công (đã tồn tại)` : ''}`);
            
        } catch (error) {
            console.error('Lỗi khi sao chép tuần trước:', error);
            alert('Có lỗi xảy ra khi sao chép. Vui lòng thử lại!');
        } finally {
            Spinner.hide(spinner);
        }
    }

    async function fetchLoaiNgayOfWeek(startDay) {
        try {
            // Gọi API lấy danh sách ngày đặc biệt trong tháng
            const thang = startDay.month() + 1;
            const nam = startDay.year();
            const url = document.getElementById('nv-list')?.dataset.url || '';
            const res = await fetch(`${url}/api/gan-ngay/${thang}-${nam}`);
            const data = await res.json();
            ngayLoaiMap = {};
            if (data.success && Array.isArray(data.data)) {
                data.data.forEach(item => {
                    ngayLoaiMap[item.ngay] = item.loai_ngay === 'Ngày tết' ? 'tet'
                        : item.loai_ngay === 'Ngày lễ' ? 'le'
                        : 'dac_biet';
                });
            }
        } catch (error) {
            console.error('Lỗi khi lấy loại ngày:', error);
            ngayLoaiMap = {};
        }
    }

    function getLoaiNgay(date) {
        const iso = date.format('YYYY-MM-DD');
        if (ngayLoaiMap[iso] === 'le') return 'le';
        if (ngayLoaiMap[iso] === 'tet') return 'tet';
        if (date.day() === 0) return 'cuoituan'; // Chủ nhật
        if (date.day() === 6) return 'cuoituan'; // Thứ 7
        return 'thuong';
    }

    function getCellBgClass(loai) {
        switch (loai) {
            case 'le': return 'bg-yellow-100';
            case 'tet': return 'bg-red-100';
            case 'cuoituan': return 'bg-blue-50';
            default: return 'bg-white';
        }
    }

    function renderPhanCongTable() {
        // Render header
        let days = [];
        let today = dayjs();
        let d = currentWeekStart;
        phancongHeaderRow.innerHTML = `<th class="border-2 border-gray-300 px-4 py-3 bg-gradient-to-br from-gray-100 to-gray-200 font-bold text-gray-700">Ca / Ngày</th>` +
            Array.from({length: 7}).map((_, i) => {
                const day = d.add(i, 'day');
                days.push(day);
                const isToday = day.isSame(today, 'day');
                return `<th class="border-2 border-gray-300 px-4 py-3 ${
                    isToday 
                    ? 'bg-gradient-to-br from-green-400 to-green-500 text-white font-bold shadow-md' 
                    : 'bg-gradient-to-br from-gray-100 to-gray-200 text-gray-700 font-semibold'
                }">
                    <div class="text-sm">${thuLabels[i]}</div>
                    <div class="text-xs font-normal mt-1">${day.format('DD/MM')}</div>
                </th>`;
            }).join('');

        // Render body
        phancongMainTbody.innerHTML = caLabels.map(ca => {
            return `<tr class="hover:bg-gray-50 transition-colors">
                <td class="border-2 border-gray-300 px-4 py-3 bg-gradient-to-r from-gray-50 to-gray-100 font-semibold text-gray-700">${ca.label}</td>
                ${days.map(day => {
                    const loai = getLoaiNgay(day);
                    // Xác định giờ bắt đầu của ca
                    let caHour = 8;
                    if (ca.key === 'afternoon') caHour = 14;
                    if (ca.key === 'evening') caHour = 18;
                    const cellTime = dayjs(day.format('YYYY-MM-DD') + ` ${caHour}:00`, 'YYYY-MM-DD HH:mm');
                    const now = dayjs();
                    // Nếu thời gian ô < hiện tại thì thêm class đặc biệt
                    const isPast = cellTime.isBefore(now, 'minute');
                    let cellClass = '';
                    if (isPast) {
                        cellClass = 'bg-gray-200 text-gray-400 opacity-70 border-gray-300 cursor-not-allowed';
                    } else {
                        cellClass = getCellBgClass(loai) + ' hover:shadow-inner';
                    }
                    return `<td class="border-2 border-gray-300 px-2 py-2 phancong-cell ${cellClass} transition-all relative" 
                        data-ca="${ca.key}" data-date="${day.format('YYYY-MM-DD')}" 
                        data-loai="${loai}">
                        <div class="phancong-dropzone min-h-[60px] rounded-md relative">
                            <span class="phancong-count absolute top-1 right-1 bg-white text-xs font-bold text-gray-600 px-2 py-0.5 rounded-full shadow-sm hidden">0</span>
                        </div>
                    </td>`;
                }).join('')}
            </tr>`;
        }).join('');
        // Drag & drop logic có thể bổ sung ở đây

        // Kích hoạt drag & drop cho các ô phân công
        phancongMainTbody.querySelectorAll('.phancong-dropzone').forEach(dropzone => {
            dropzone.addEventListener('dragover', function(e) {
                const nvId = e.dataTransfer.getData('nvId');
                // Lấy thông tin ngày và ca của ô
                const cell = this.closest('.phancong-cell');
                const dateStr = cell.dataset.date;
                const ca = cell.dataset.ca;
                // Xác định giờ bắt đầu của ca
                let caHour = 8; // Ca sáng
                if (ca === 'afternoon') caHour = 14;
                if (ca === 'evening') caHour = 18;
                const cellTime = dayjs(dateStr + ` ${caHour}:00`, 'YYYY-MM-DD HH:mm');
                const now = dayjs();
                // Nếu thời gian ô < hiện tại thì không cho phép thả
                if (cellTime.isBefore(now, 'minute')) {
                    e.preventDefault();
                    this.classList.remove('ring', 'ring-blue-400');
                    this.style.cursor = 'not-allowed';
                    e.dataTransfer.dropEffect = 'none';
                    return;
                }
                // Kiểm tra trùng nhân viên
                if (nvId && this.querySelector(`.phancong-nv[data-id="${nvId}"]`)) {
                    e.preventDefault();
                    this.classList.remove('ring', 'ring-blue-400');
                    this.style.cursor = 'not-allowed';
                    e.dataTransfer.dropEffect = 'none';
                } else {
                    e.preventDefault();
                    this.classList.add('ring', 'ring-blue-400');
                    this.style.cursor = 'pointer';
                    e.dataTransfer.dropEffect = 'copy';
                }
            });
            dropzone.addEventListener('dragleave', function() {
                this.classList.remove('ring', 'ring-blue-400');
                this.style.cursor = '';
            });
            dropzone.addEventListener('drop', async function(e) {
                // Lấy thông tin ngày và ca của ô
                const cell = this.closest('.phancong-cell');
                const dateStr = cell.dataset.date;
                const ca = cell.dataset.ca;
                let caHour = 8;
                if (ca === 'afternoon') caHour = 14;
                if (ca === 'evening') caHour = 18;
                const cellTime = dayjs(dateStr + ` ${caHour}:00`, 'YYYY-MM-DD HH:mm');
                const now = dayjs();
                // Nếu thời gian ô < hiện tại thì không cho phép thả
                if (cellTime.isBefore(now, 'minute')) {
                    this.classList.remove('ring', 'ring-blue-400');
                    this.style.cursor = '';
                    return;
                }
                e.preventDefault();
                this.classList.remove('ring', 'ring-blue-400');
                this.style.cursor = '';
                const nvId = e.dataTransfer.getData('nvId');
                if (!nvId || this.querySelector(`.phancong-nv[data-id="${nvId}"]`)) return;
                const nv = nhanViens.find(nv => nv.id == nvId);
                if (!nv) return;

                // Hiện modal chọn vị trí công việc
                const modal = document.createElement('div');
                modal.className = "fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-[9999]";
                modal.innerHTML = `
                    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6">
                        <h3 class="text-lg font-bold mb-4">Chọn vị trí công việc</h3>
                        <div id="vitri-modal-list" class="mb-4 text-left"></div>
                        <div class="flex justify-end gap-2">
                            <button id="vitri-modal-cancel" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Hủy</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);

                // Load danh sách vị trí công việc
                const vitriListDiv = modal.querySelector('#vitri-modal-list');
                if (viTriCongViecs.length) {
                    vitriListDiv.innerHTML = viTriCongViecs.map(vt => `
                        <button class="vitri-modal-item w-full text-left px-3 py-2 rounded hover:bg-blue-100 border mb-2" data-id="${vt.id}" data-ten="${vt.ten}">
                            ${vt.ten}
                        </button>
                    `).join('');
                    // Chọn vị trí
                    vitriListDiv.querySelectorAll('.vitri-modal-item').forEach(btn => {
                        btn.onclick = async () => {
                            // Gọi API tạo phân công
                            const formData = new FormData();
                            formData.append('id_nhanvien', nv.id);
                            formData.append('id_congviec', btn.dataset.id);
                            formData.append('ngay', dateStr);
                            formData.append('ca', ca === 'morning' ? 'Ca sáng' : ca === 'afternoon' ? 'Ca chiều' : 'Ca tối');

                            const url = nvList.dataset.url || '';
                            const res = await fetch(`${url}/api/phan-cong`, {
                                method: 'POST',
                                body: formData
                            });
                            const data = await res.json();
                            if (data.success && data.data && data.data.id) {
                                // Thêm nhân viên vào ô với id phân công
                                this.insertAdjacentHTML('beforeend', `
                                    <div class="phancong-nv flex items-center gap-1 bg-blue-100 rounded px-2 py-1 text-xs font-medium mt-1 relative group"
                                        data-id="${nv.id}" data-vitri="${btn.dataset.id}" data-phancong-id="${data.data.id}">
                                        ${nv.avatar_url && nv.avatar_url.trim() !== ''
                                            ? `<img src="${nv.avatar_url}" class="w-6 h-6 rounded-full border">`
                                            : `<div class="w-6 h-6 flex items-center justify-center rounded-full border bg-gray-300 text-gray-700 font-bold text-xs">${nv.ten.trim().charAt(0).toUpperCase()}</div>`
                                        }
                                        <span>${nv.ten}</span>
                                        <button type="button" class="phancong-nv-remove ml-1 text-gray-500 hover:text-red-600 rounded-full w-4 h-4 flex items-center justify-center absolute -top-1 -right-1 bg-white border border-gray-300 group-hover:visible invisible" title="Xóa">
                                            &times;
                                        </button>
                                    </div>
                                `);
                                // Gắn sự kiện xóa cho nút vừa thêm
                                const lastNv = this.querySelector('.phancong-nv:last-child .phancong-nv-remove');
                                if (lastNv) {
                                    lastNv.onclick = async function(e) {
                                        e.stopPropagation();
                                        const nvDiv = this.closest('.phancong-nv');
                                        const cell = this.closest('.phancong-cell');
                                        const phanCongId = nvDiv.getAttribute('data-phancong-id');
                                        if (phanCongId) {
                                            await fetch(`${url}/api/phan-cong/${phanCongId}`, { method: 'DELETE' });
                                        }
                                        nvDiv.remove();
                                        updateCellCount(cell); // Cập nhật số lượng
                                        if (cell && cell.querySelectorAll('.phancong-nv').length === 0 && cell._phancongTooltip) {
                                            cell._phancongTooltip.remove();
                                            cell._phancongTooltip = null;
                                        }
                                    };
                                }
                                updateCellCount(cell); // Cập nhật số lượng
                                modal.remove();
                            } else {
                                alert('Không thể phân công. Vui lòng thử lại!');
                                modal.remove();
                            }
                        };
                    });
                } else {
                    vitriListDiv.innerHTML = '<div class="text-red-500">Không có vị trí công việc nào.</div>';
                }

                // Đóng modal
                modal.querySelector('#vitri-modal-cancel').onclick = () => modal.remove();
                modal.onclick = e => { if (e.target === modal) modal.remove(); };
            });
        });
        phancongMainTbody.querySelectorAll('.phancong-cell').forEach(cell => {
            cell.onmouseenter = function(e) {
                const nvs = this.querySelectorAll('.phancong-nv');
                if (!nvs.length) return;
                // Tính tổng số nhân viên theo vị trí (theo tên vị trí)
                const vitriMap = {};
                nvs.forEach(nvDiv => {
                    const vitriId = nvDiv.dataset.vitri || 'Chưa chọn';
                    // Lấy tên vị trí công việc từ biến toàn cục
                    const vitriObj = viTriCongViecs.find(vt => String(vt.id) === String(vitriId));
                    const vitriTen = vitriObj ? vitriObj.ten : vitriId;
                    if (!vitriMap[vitriTen]) vitriMap[vitriTen] = [];
                    vitriMap[vitriTen].push(nvDiv.querySelector('span').innerText);
                });
                // Tạo nội dung tooltip
                let html = `<div class="font-semibold mb-1">Danh sách nhân viên:</div>`;
                Object.entries(vitriMap).forEach(([vitri, ds]) => {
                    html += `<div class="mb-1"><span class="font-medium">${vitri}</span> <span class="text-xs text-gray-500">(${ds.length})</span><br>
                        <span class="ml-2 text-xs">${ds.join(', ')}</span>
                    </div>`;
                });
                // Tạo overlay
                let tooltip = document.createElement('div');
                tooltip.className = "phancong-tooltip absolute z-50 bg-white border rounded shadow-lg p-3 text-left text-sm";
                tooltip.style.top = (this.getBoundingClientRect().top + window.scrollY + this.offsetHeight + 4) + "px";
                tooltip.style.left = (this.getBoundingClientRect().left + window.scrollX) + "px";
                tooltip.innerHTML = html;
                document.body.appendChild(tooltip);
                this._phancongTooltip = tooltip;
            };
            cell.onmouseleave = function() {
                if (this._phancongTooltip) {
                    this._phancongTooltip.remove();
                    this._phancongTooltip = null;
                }
                // Xóa mọi tooltip còn sót lại trên body (phòng trường hợp lỗi)
                document.querySelectorAll('.phancong-tooltip').forEach(tip => tip.remove());
            };
        });
        // Sau khi render xong bảng...
        phanCongTuan.forEach(pc => {
            // Xác định ca key
            let caKey = '';
            if (pc.ca === 'Ca sáng') caKey = 'morning';
            else if (pc.ca === 'Ca chiều') caKey = 'afternoon';
            else if (pc.ca === 'Ca tối') caKey = 'evening';
            // Tìm đúng ô
            const cell = phancongMainTbody.querySelector(`.phancong-cell[data-date="${pc.ngay}"][data-ca="${caKey}"] .phancong-dropzone`);
            if (cell) {
                // Lấy thông tin nhân viên và vị trí công việc từ object trả về
                const nv = pc.nhan_vien;
                const vt = pc.cong_viec;
                cell.insertAdjacentHTML('beforeend', `
                    <div class="phancong-nv flex items-center gap-1 bg-blue-100 rounded px-2 py-1 text-xs font-medium mt-1 relative group"
                        data-id="${nv ? nv.id : pc.id_nhanvien}" data-vitri="${vt ? vt.id : pc.id_congviec}" data-phancong-id="${pc.id}">
                        ${nv && nv.avatar_url && nv.avatar_url.trim() !== ''
                            ? `<img src="${nv.avatar_url}" class="w-6 h-6 rounded-full border">`
                            : `<div class="w-6 h-6 flex items-center justify-center rounded-full border bg-gray-300 text-gray-700 font-bold text-xs">${nv && nv.ten ? nv.ten.trim().charAt(0).toUpperCase() : '?'}</div>`
                        }
                        <span>${nv ? nv.ten : 'NV'}</span>
                        <button type="button" class="phancong-nv-remove ml-1 text-gray-500 hover:text-red-600 rounded-full w-4 h-4 flex items-center justify-center absolute -top-1 -right-1 bg-white border border-gray-300 group-hover:visible invisible" title="Xóa">
                            &times;
                        </button>
                    </div>
                `);
            }
        });
        // Cập nhật số lượng cho tất cả các ô
        phancongMainTbody.querySelectorAll('.phancong-cell').forEach(cell => {
            updateCellCount(cell);
        });
        // Gắn lại sự kiện xóa cho tất cả nút xóa nhân viên trong ô
        phancongMainTbody.querySelectorAll('.phancong-nv-remove').forEach(btn => {
            btn.onclick = async function(e) {
                e.stopPropagation();
                const nvDiv = this.closest('.phancong-nv');
                const cell = this.closest('.phancong-cell');
                const phanCongId = nvDiv.getAttribute('data-phancong-id');
                const url = nvList.dataset.url || '';
                if (phanCongId) {
                    await fetch(`${url}/api/phan-cong/${phanCongId}`, { method: 'DELETE' });
                }
                nvDiv.remove();
                updateCellCount(cell); // Cập nhật số lượng
                if (cell._phancongTooltip) {
                    cell._phancongTooltip.remove();
                    cell._phancongTooltip = null;
                }
            };
        });
    }

    // Hàm cập nhật số lượng nhân viên trong ô
    function updateCellCount(cell) {
        const dropzone = cell.querySelector('.phancong-dropzone');
        const countBadge = dropzone.querySelector('.phancong-count');
        const nvCount = dropzone.querySelectorAll('.phancong-nv').length;
        
        if (countBadge) {
            countBadge.textContent = nvCount;
            if (nvCount > 0) {
                countBadge.classList.remove('hidden');
                // Màu sắc theo số lượng
                if (nvCount >= 3) {
                    countBadge.className = 'phancong-count absolute top-1 right-1 bg-green-500 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-sm';
                } else if (nvCount >= 2) {
                    countBadge.className = 'phancong-count absolute top-1 right-1 bg-blue-500 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-sm';
                } else {
                    countBadge.className = 'phancong-count absolute top-1 right-1 bg-yellow-500 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-sm';
                }
            } else {
                countBadge.classList.add('hidden');
            }
        }
    }

    // Khi chuyển tuần, fetch lại loại ngày và render bảng
    async function reloadPhanCongTable() {
        const spinner = Spinner.show({ target: '#phancong-main-table', text: 'Đang tải lịch phân công...', overlay: true });
        try {
            await fetchLoaiNgayOfWeek(currentWeekStart);
            // Lấy ngày bắt đầu và kết thúc tuần
            const start = currentWeekStart.format('YYYY-MM-DD');
            const end = currentWeekStart.add(6, 'day').format('YYYY-MM-DD');
            await fetchPhanCongTuan(start, end);
            renderPhanCongTable();
        } finally {
            Spinner.hide(spinner);
        }
    }

    // Sửa lại sự kiện chuyển tuần:
    document.getElementById('btn-prev-week').onclick = async function() {
        currentWeekStart = currentWeekStart.subtract(7, 'day');
        updateWeekTitle();
        await reloadPhanCongTable();
    };
    document.getElementById('btn-next-week').onclick = async function() {
        currentWeekStart = currentWeekStart.add(7, 'day');
        updateWeekTitle();
        await reloadPhanCongTable();
    };
    
    // Sao chép tuần trước
    document.getElementById('btn-copy-week').onclick = copyPreviousWeek;
    
    // Xóa toàn bộ phân công trong tuần
    document.getElementById('btn-clear-week').onclick = async function() {
        if (!confirm('Bạn có chắc muốn xóa TOÀN BỘ phân công trong tuần này?\n\nHành động này KHÔNG THỂ HOÀN TÁC!')) {
            return;
        }
        
        const spinner = Spinner.show({ text: 'Đang xóa phân công...', overlay: true });
        try {
            const url = phancongMainTbody.dataset.url || '';
            let deletedCount = 0;
            let errorCount = 0;
            
            // Xóa từng phân công
            for (const pc of phanCongTuan) {
                try {
                    const res = await fetch(`${url}/api/phan-cong/${pc.id}`, { method: 'DELETE' });
                    const data = await res.json();
                    if (data.success) {
                        deletedCount++;
                    } else {
                        errorCount++;
                    }
                } catch (err) {
                    errorCount++;
                }
            }
            
            // Reload lại bảng
            await reloadPhanCongTable();
            
            // Thông báo kết quả
            if (errorCount === 0) {
                alert(`Đã xóa thành công ${deletedCount} phân công!`);
            } else {
                alert(`Đã xóa ${deletedCount} phân công.\nLỗi: ${errorCount} phân công không thể xóa.`);
            }
        } catch (error) {
            console.error('Lỗi khi xóa tuần:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại!');
        } finally {
            Spinner.hide(spinner);
        }
    };

    // --- Khởi tạo ---
    await reloadPhanCongTable();
    await fetchNhanViens(1);
    nhanViensFiltered = [...nhanViens]; // Khởi tạo danh sách filtered
    renderNhanVienList();
    updateWeekTitle();

});
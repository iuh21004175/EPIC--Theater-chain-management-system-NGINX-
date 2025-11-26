import Spinner from './util/spinner.js';

let currentWeekStart;

// Khai báo các biến toàn cục để các hàm helper có thể truy cập
let moviesData = [];
let roomsData = [];
let nhatKyData = [];

document.addEventListener('DOMContentLoaded', function() {
    const showtimeListing = document.getElementById('showtime-listing');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    const cinemaNameEl = document.getElementById('cinema-name');

    const rejectModal = document.getElementById('reject-modal');
    const rejectReason = document.getElementById('reject-reason');
    const btnCancelReject = document.getElementById('btn-cancel-reject');
    const btnConfirmReject = document.getElementById('btn-confirm-reject');

    const rapId = showtimeListing.dataset.rap;
    let listSuatChieuChuaXem = [];
    let selectedDate = null;

    // Kiểm tra rạp có suất chiếu chưa xem không

    // Nếu có thì fetch dữ liệu tìm số suất chiếu chưa xem
    if(parseInt(cinemaNameEl.dataset.soSuatChuaXem) > 0){
        fetch(`${showtimeListing.dataset.url}/api/suat-chieu/chua-xem/${rapId}`)
        .then(res => res.json())
        .then(data => {
            if(data.success){
                listSuatChieuChuaXem = data.data;
                // Lây ngày chọn là ngày của suất chiếu chưa xem đầu tiên
                if(listSuatChieuChuaXem.length > 0){
                    selectedDate = listSuatChieuChuaXem[0].batdau;
                
                }
            }
        })
        .catch((e) => {
            console.error('Lỗi khi lấy suất chiếu chưa xem: ', e);
        });
    }
    else{
        // Nếu không có suất chiếu chưa xem thì lấy ngày hiện tại
        selectedDate = new Date().toISOString().split('T')[0];
    }

    // Khởi tạo ngày mặc định
    const datePicker = document.getElementById('date-picker');
    const today = new Date();
    datePicker.value = formatDateDisplay(today);
    const selectedDateAPI = formatDate(today);

    initWeekNavigation();
    loadShowtimes(selectedDateAPI);

    btnCancelReject.addEventListener('click', () => {
        rejectModal.classList.add('hidden');
        rejectReason.value = '';
        btnConfirmReject.onclick = null;
    });
 

    async function loadShowtimes(date) {
        Spinner.show({ text: 'Đang tải suất chiếu...' });
        try {
            const res = await fetch(`${showtimeListing.dataset.url}/api/suat-chieu?ngay=${date}&id_rap=${rapId}`);
            const data = await res.json();
            if (data.success && Array.isArray(data.data)) {
                const grouped = groupByMovie(data.data);
                displayShowtimes(grouped, date);
            } else {
                showtimeListing.innerHTML = '<div class="text-center py-8 text-gray-500">Không có dữ liệu suất chiếu</div>';
            }
        } catch (e) {
            console.error('Lỗi khi tải dữ liệu suất chiếu: ', e);
            showtimeListing.innerHTML = '<div class="text-center py-8 text-red-500">Lỗi tải dữ liệu suất chiếu</div>';
        } finally {
            Spinner.hide();
        }
    }

    function groupByMovie(showtimes) {
        const map = {};
        showtimes.forEach(s => {
            const movieId = s.phim.id;
            if (!map[movieId]) {
                map[movieId] = {
                    id: movieId,
                    title: s.phim.ten_phim,
                    duration: s.phim.thoi_luong,
                    poster: `${showtimeListing.dataset.urlminio}/${s.phim.poster_url}`,
                    showtimes: []
                };
            }
            map[movieId].showtimes.push({
                id: s.id,
                room_name: s.phong_chieu.ten,
                start_time: s.batdau.substr(11,5),
                end_time: s.ketthuc.substr(11,5),
                status: s.tinh_trang,
                so_ve_da_dat: s.so_ve_da_dat || 0
            });
        });
        return Object.values(map);
    }

    function displayShowtimes(movies, date) {
        showtimeListing.innerHTML = '';
        if (!movies.length) {
            showtimeListing.innerHTML = `<div class="text-center py-8 text-gray-500">Chưa có suất chiếu nào vào ngày ${displayDate(date)}</div>`;
            return;
        }
        let hasPending = false;
        movies.forEach(movie => {
            const card = document.createElement('div');
            card.className = 'bg-white border rounded-lg overflow-hidden shadow-sm mb-6';
            const showtimesHtml = movie.showtimes.map(s => {
                // Kiểm tra hết hạn
                const now = new Date();
                const endTime = new Date(`${date}T${s.end_time}:00`);
                const isExpired = endTime < now;
                
                // Kiểm tra giới hạn hoàn tác: chỉ cho phép hoàn tác nếu còn ít nhất 2 giờ trước khi suất chiếu bắt đầu
                const startTime = new Date(`${date}T${s.start_time}:00`);
                const timeUntilStart = startTime - now; // milliseconds
                const hoursUntilStart = timeUntilStart / (1000 * 60 * 60); // chuyển sang giờ
                const hasVe = (s.so_ve_da_dat || 0) > 0; // Có vé đã đặt hoặc đang giữ chỗ
                const canHoanTac = !isExpired && hoursUntilStart >= 2 && !hasVe; // Còn ít nhất 2 giờ và chưa có vé
                
                // console.log({now, endTime, isExpired, status: s.status});
                return `
                <div class="flex col border-t py-3 px-4 gap-1">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-1">
                        <div class="font-medium min-w-24">${s.start_time} - ${s.end_time}</div>
                        <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded ml-0 sm:ml-2">${s.room_name}</span>
                        ${hasVe ? `<span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded ml-0 sm:ml-2">${s.so_ve_da_dat} vé đã đặt</span>` : ''}
                    </div>
                    <div class="flex items-center ml-auto space-x-2">
                        ${
                            isExpired
                            ? `<span class="text-xs text-gray-400 italic">Đã hết hạn</span>`
                            : hasVe
                            ? `<span class="text-xs text-red-500 italic">Không thể hoàn tác vì đã có vé được đặt</span>`
                            : canHoanTac
                            ? `
                                <button class="btn-hoan-tac flex items-center gap-1 text-xs font-semibold px-3 py-1 rounded bg-orange-100 text-orange-700 hover:bg-orange-200 transition" data-id="${s.id}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                                    Hoàn tác suất chiếu
                                </button>
                            `
                            : `<span class="text-xs text-gray-400 italic">Chỉ có thể hoàn tác khi còn ít nhất 2 giờ trước khi suất chiếu bắt đầu</span>`
                        }
                    </div>
                </div>`;
            }).join('');
            card.innerHTML = `
                <div class="flex p-4">
                    <img src="${movie.poster}" alt="${movie.title}" class="w-16 h-24 object-cover rounded mr-4">
                    <div>
                        <h3 class="font-bold text-lg">${movie.title}</h3>
                        <p class="text-sm text-gray-600">${movie.duration} phút</p>
                    </div>
                </div>
                <div class="showtimes">${showtimesHtml}</div>
            `;
            showtimeListing.appendChild(card);
        });


        document.querySelectorAll('.btn-hoan-tac').forEach(btn => {
            btn.addEventListener('click', () => hoanTacSuatChieu(parseInt(btn.dataset.id)));
        });

    }

    async function hoanTacSuatChieu(id) {
        if (!confirm('Bạn có chắc muốn hoàn tác suất chiếu này? Suất chiếu sẽ bị xóa và trạng thái trong kế hoạch sẽ được cập nhật về chờ duyệt.\n\nLưu ý:\n- Chỉ có thể hoàn tác khi còn ít nhất 2 giờ trước khi suất chiếu bắt đầu\n- Không thể hoàn tác nếu đã có vé được đặt hoặc đang giữ chỗ')) {
            return;
        }
        Spinner.show({ text: 'Đang hoàn tác suất chiếu...' });
        try {
            const res = await fetch(`${showtimeListing.dataset.url}/api/suat-chieu/${id}/hoan-tac`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                showToast('Hoàn tác suất chiếu thành công');
                reloadByPicker();
            } else {
                showToast(data.message || 'Hoàn tác suất chiếu thất bại', 'error');
            }
        } catch (e) {
            console.error('Lỗi khi hoàn tác suất chiếu:', e);
            showToast('Hoàn tác suất chiếu thất bại', 'error');
        }
        Spinner.hide();
    }

    function reloadByPicker() {
        const value = document.getElementById('date-picker').value;
        const date = formatDate(parseDateFromDisplay(value));
        loadShowtimes(date);
    }

    function showToast(message, type = 'success') {
        toastMessage.textContent = message;
        if (type === 'error') { toast.classList.remove('bg-green-500'); toast.classList.add('bg-red-500'); }
        else { toast.classList.remove('bg-red-500'); toast.classList.add('bg-green-500'); }
        toast.classList.remove('translate-y-20', 'opacity-0');
        setTimeout(() => { toast.classList.add('translate-y-20', 'opacity-0'); }, 3000);
    }

    // Helpers: Ngày/tuần và status
    function formatDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }
    function formatDateDisplay(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${d}/${m}/${y}`;
    }
    function parseDateFromDisplay(str) {
        const [d, m, y] = str.split('/').map(Number);
        return new Date(y, m - 1, d);
    }
    function displayDate(date) {
        const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
        return new Date(date).toLocaleDateString('vi-VN', options);
    }
    function getWeekStartFromAnyDate(dateStr) {
        const d = new Date(dateStr);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        d.setDate(diff); d.setHours(0,0,0,0);
        return formatDate(d);
    }
    function getMonday(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        d.setDate(diff); d.setHours(0,0,0,0); return d;
    }
    function showtimeStatusLabel(status) {
        switch (status) {
            case 0: return { text: 'Chờ duyệt', color: 'bg-yellow-100 text-yellow-800' };
            case 1: return { text: 'Đã duyệt', color: 'bg-green-100 text-green-800' };
            case 2: return { text: 'Từ chối', color: 'bg-red-100 text-red-800' };
            case 3: return { text: 'Chờ duyệt lại', color: 'bg-blue-100 text-blue-800' };
            default: return { text: 'Không xác định', color: 'bg-gray-100 text-gray-800' };
        }
    }

    // Week navigation (tương tự trang quản lý suất)
    function updateWeekDisplay() {
        const weekRangeDisplay = document.getElementById('week-range');
        if (!weekRangeDisplay || !currentWeekStart) return;
        const weekEnd = new Date(currentWeekStart);
        weekEnd.setDate(currentWeekStart.getDate() + 6);
        const formatDay = date => {
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        };
        weekRangeDisplay.textContent = `${formatDay(currentWeekStart)} - ${formatDay(weekEnd)}`;
    }
    function renderWeekDays(currentStart, selectedDateStr) {
        const container = document.getElementById('date-nav-container');
        if (!container) return; container.innerHTML = '';
        const days = ['T2','T3','T4','T5','T6','T7','CN'];
        const monday = getMonday(currentStart);
        const today = new Date(); today.setHours(0,0,0,0);
        for (let i=0;i<7;i++) {
            const itemDate = new Date(monday); itemDate.setDate(monday.getDate()+i); itemDate.setHours(0,0,0,0);
            const day = itemDate.getDate(); const month = itemDate.getMonth()+1; const year = itemDate.getFullYear();
            const dayOfWeek = days[i];
            const formattedDate = `${year}-${month.toString().padStart(2,'0')}-${day.toString().padStart(2,'0')}`;
            const isSelected = formattedDate === selectedDateStr;
            const isToday = itemDate.getTime() === today.getTime();
            const itemDiv = document.createElement('div');
            itemDiv.className = 'date-nav-item';
            itemDiv.dataset.date = formattedDate;
            itemDiv.innerHTML = `<div class="text-center p-2 rounded-lg border cursor-pointer hover:bg-gray-50 transition-colors">
                <p class="text-xs font-medium ${isSelected ? 'text-blue-600' : isToday ? 'text-green-600' : 'text-gray-500'}">${dayOfWeek}</p>
                <p class="text-lg font-bold ${isSelected ? 'text-blue-600' : isToday ? 'text-green-600' : ''}">${day.toString().padStart(2,'0')}</p>
                <p class="text-xs ${isSelected ? 'text-blue-600' : isToday ? 'text-green-600' : 'text-gray-500'}">${month.toString().padStart(2,'0')}/${year.toString().slice(-2)}</p>
            </div>`;
            const div = itemDiv.querySelector('div');
            if (isSelected) { div.classList.add('border-blue-500','bg-blue-50'); }
            if (isToday) { div.classList.add('border-green-500','bg-green-50'); }
            itemDiv.addEventListener('click', function() {
                const dateDisplay = formatDateDisplay(new Date(formattedDate));
                document.getElementById('date-picker').value = dateDisplay;
                currentWeekStart = getMonday(new Date(formattedDate));
                updateWeekDisplay();
                renderWeekDays(currentWeekStart, formattedDate);
                loadShowtimes(formattedDate);
            });
            container.appendChild(itemDiv);
        }
    }
    function initWeekNavigation() {
        const prevWeekBtn = document.getElementById('prev-week');
        const nextWeekBtn = document.getElementById('next-week');
        let selectedDate = document.getElementById('date-picker').value;
        if (!selectedDate) {
            const today = new Date(); today.setHours(0,0,0,0);
            selectedDate = formatDateDisplay(today);
            document.getElementById('date-picker').value = selectedDate;
        }
        let baseDate;
        if (selectedDate.includes('/')) baseDate = parseDateFromDisplay(selectedDate); else baseDate = new Date(selectedDate);
        baseDate.setHours(0,0,0,0);
        currentWeekStart = getMonday(baseDate);
        updateWeekDisplay();
        const selectedDateAPI = formatDate(baseDate);
        renderWeekDays(currentWeekStart, selectedDateAPI);
        prevWeekBtn.addEventListener('click', () => {
            currentWeekStart.setDate(currentWeekStart.getDate() - 7);
            updateWeekDisplay();
            const newDateDisplay = formatDateDisplay(currentWeekStart);
            const newDateAPI = formatDate(currentWeekStart);
            document.getElementById('date-picker').value = newDateDisplay;
            renderWeekDays(currentWeekStart, newDateAPI);
            loadShowtimes(newDateAPI);
        });
        nextWeekBtn.addEventListener('click', () => {
            currentWeekStart.setDate(currentWeekStart.getDate() + 7);
            updateWeekDisplay();
            const newDateDisplay = formatDateDisplay(currentWeekStart);
            const newDateAPI = formatDate(currentWeekStart);
            document.getElementById('date-picker').value = newDateDisplay;
            renderWeekDays(currentWeekStart, newDateAPI);
            loadShowtimes(newDateAPI);
        });
    }

    function fetchNhatKy() {
        const logBadge = document.getElementById('log-badge');
        fetch(`${showtimeListing.dataset.url}/api/nhat-ky-suat-chieu?idRap=${rapId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                    nhatKyData = data.data;
                    // Đếm số nhật ký mới
                    const soMoi = nhatKyData.filter(item => item.da_xem == 0).length;
                    if (logBadge) {
                        if (soMoi > 0) {
                            logBadge.textContent = soMoi;
                            logBadge.classList.remove('hidden');
                        } else {
                            logBadge.classList.add('hidden');
                        }
                    }
                }
            });
    }
    fetchNhatKy();

    const btnLog = document.getElementById('btn-log');
    const logModal = document.getElementById('log-modal');
    const btnCloseLog = document.getElementById('btn-close-log');

    btnLog.addEventListener('click', () => {
        // Gọi API đánh dấu đã xem nhật ký
        fetch(`${showtimeListing.dataset.url}/api/nhat-ky-suat-chieu/chuoi-rap-da-xem?idRap=${rapId}`, {
            method: 'PUT'
        }).then(() => {
            const logBadge = document.getElementById('log-badge');
            if (logBadge) logBadge.classList.add('hidden');
        });

        logModal.classList.remove('hidden');
        const logContent = document.getElementById('log-content');
        if (!logContent) return;
        if (!nhatKyData.length) {
            logContent.innerHTML = '<div class="text-gray-500 text-center py-8">Chưa có nhật ký.</div>';
            return;
        }
        
        // Nhóm nhật ký theo phim (giữ nguyên thứ tự từ API - mới nhất trước)
        const groupedByMovie = {};
        nhatKyData.forEach(log => {
            const movieId = log.id_phim;
            if (!groupedByMovie[movieId]) {
                groupedByMovie[movieId] = {
                    movieInfo: log.phim || { ten_phim: log.ten_phim || 'Không rõ' },
                    logs: [],
                    latestTime: null // Lưu thời gian log mới nhất
                };
            }
            groupedByMovie[movieId].logs.push(log);
            // Cập nhật thời gian log mới nhất của nhóm phim này
            const logTime = new Date(log.created_at).getTime();
            if (!groupedByMovie[movieId].latestTime || logTime > groupedByMovie[movieId].latestTime) {
                groupedByMovie[movieId].latestTime = logTime;
            }
        });
        
        // Sắp xếp các nhóm phim theo log mới nhất (phim có log mới nhất sẽ hiển thị trước)
        const sortedMovieGroups = Object.values(groupedByMovie).sort((a, b) => {
            return (b.latestTime || 0) - (a.latestTime || 0);
        });
        
        // Render nhật ký theo format giống ảnh
        let html = '';
        sortedMovieGroups.forEach(movieGroup => {
            const movie = movieGroup.movieInfo;
            const posterUrl = getMoviePosterUrl({ phim: movie });
            const movieTitle = movie.ten_phim || 'Không rõ';
            const movieDuration = movie.thoi_luong || getMovieDuration({ id_phim: movie.id }) || 85;
            
            html += `
                <div class="mb-6 bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                    <div class="flex items-center p-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b">
                        <img src="${posterUrl}" alt="${movieTitle}" class="w-16 h-24 object-cover rounded-md shadow-sm mr-4">
                        <div>
                            <h3 class="font-bold text-lg text-gray-800">${movieTitle}</h3>
                            <p class="text-sm text-gray-600 mt-1">
                                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                ${movieDuration} phút
                            </p>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100">`;
            
            movieGroup.logs.forEach(log => {
                // Xác định nhãn hành động dựa trên hanh_dong và tinh_trang (nếu có)
                let hanhDongLabel = getHanhDongLabel(log.hanh_dong, log.tinh_trang);
                const hanhDongClass = getHanhDongClass(log.hanh_dong);
                const timeDisplay = formatLogTime(log.created_at);
                
                const batDauDate = log.batdau ? new Date(log.batdau) : null;
                const batDauTime = batDauDate ? `${batDauDate.getHours().toString().padStart(2, '0')}:${batDauDate.getMinutes().toString().padStart(2, '0')}` : 'N/A';
                const batDauDateStr = batDauDate ? `${batDauDate.getDate().toString().padStart(2, '0')}/${(batDauDate.getMonth()+1).toString().padStart(2, '0')}/${batDauDate.getFullYear()}` : 'N/A';
                
                const roomName = log.ten_phong || 'Không rõ';
                const rapName = log.ten_rap || 'Không rõ';
                
                html += `
                    <div class="p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="${hanhDongClass}">${hanhDongLabel}</span>
                                </div>
                                <div class="text-sm text-gray-700 space-y-1">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <span class="font-medium">Rạp:</span>
                                        <span class="ml-1">${rapName}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="font-medium">Giờ chiếu:</span>
                                        <span class="ml-1">${batDauTime} - ${batDauDateStr}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                        </svg>
                                        <span class="font-medium">Phòng:</span>
                                        <span class="ml-1">${roomName}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right ml-4">
                                <div class="text-xs text-gray-500">${timeDisplay}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
        });
        
        logContent.innerHTML = html;
    });
    btnCloseLog.addEventListener('click', () => {
        logModal.classList.add('hidden');
    });
    logModal.addEventListener('click', (e) => {
        if (e.target === logModal) logModal.classList.add('hidden');
    });

    // Fetch movies and rooms data for log display
    async function fetchMoviesAndRooms() {
        try {
            const [moviesRes, roomsRes] = await Promise.all([
                fetch(`${showtimeListing.dataset.url}/api/phim`),
                fetch(`${showtimeListing.dataset.url}/api/phong-chieu`)
            ]);
            const moviesData_result = await moviesRes.json();
            const roomsData_result = await roomsRes.json();
            
            if (moviesData_result.success) {
                moviesData = moviesData_result.data;
            }
            if (roomsData_result.success) {
                roomsData = roomsData_result.data;
            }
        } catch (e) {
            console.error('Error fetching movies and rooms:', e);
        }
    }
    fetchMoviesAndRooms();
});

// Các hàm helper cho modal nhật ký (giống suat-chieu.js)
function getMoviePosterUrl(log) {
    const urlMinio = document.getElementById('showtime-listing').dataset.urlminio;
    if (log && log.phim && log.phim.poster_url) {
        return `${urlMinio}/${log.phim.poster_url}`;
    }
    return 'https://via.placeholder.com/64x80?text=No+Image';
}

function getMovieDuration(log) {
    const phim = moviesData.find(m => m.ID === log.id_phim);
    return phim ? phim.ThoiLuong : 85;
}

function getTinhTrangFromLog(log) {
    const labels = ['Chờ duyệt', 'Đã duyệt', 'Từ chối', 'Chờ duyệt lại'];
    const classes = [
        'px-2 py-1 text-xs font-medium rounded bg-yellow-100 text-yellow-800',
        'px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800',
        'px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-800',
        'px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800'
    ];
    
    const tinhTrang = log.tinh_trang !== undefined ? log.tinh_trang : 0;
    
    return {
        label: labels[tinhTrang] || labels[0],
        class: classes[tinhTrang] || classes[0]
    };
}

function getHanhDongLabel(hanhDong, tinhTrang = null) {
    // Nếu là hành động Xóa (hanh_dong = 2) và có tinh_trang, xác định loại hoàn tác
    if (hanhDong === 2 && tinhTrang !== null && tinhTrang !== undefined) {
        if (tinhTrang === 1) {
            return 'Hoàn tác duyệt'; // Hoàn tác suất chiếu đã duyệt
        } else if (tinhTrang === 2) {
            return 'Hoàn tác từ chối'; // Hoàn tác suất chiếu bị từ chối
        }
        return 'Hoàn tác'; // Hoàn tác suất chiếu khác
    }
    
    // Nếu là hành động Cập nhật (hanh_dong = 1) và có tinh_trang = 0, có thể là sửa suất chiếu bị từ chối
    if (hanhDong === 1 && tinhTrang === 0) {
        // Có thể là sửa suất chiếu bị từ chối (đưa về chờ duyệt)
        // Nhưng không thể phân biệt chính xác nếu không có thông tin trạng thái trước đó
        // Vì vậy vẫn hiển thị "Cập nhật"
    }
    
    const labels = ['Tạo mới', 'Cập nhật', 'Xóa/Hoàn tác', 'Duyệt', 'Từ chối', 'Duyệt từ kế hoạch'];
    return labels[hanhDong] || 'Không rõ';
}

function getHanhDongClass(hanhDong) {
    const classes = [
        'px-2 py-1 text-xs font-medium rounded bg-blue-50 text-blue-700 border border-blue-200',      // 0 - Tạo mới
        'px-2 py-1 text-xs font-medium rounded bg-amber-50 text-amber-700 border border-amber-200',   // 1 - Cập nhật
        'px-2 py-1 text-xs font-medium rounded bg-red-50 text-red-700 border border-red-200',         // 2 - Xóa
        'px-2 py-1 text-xs font-medium rounded bg-green-50 text-green-700 border border-green-200',   // 3 - Duyệt
        'px-2 py-1 text-xs font-medium rounded bg-gray-50 text-gray-700 border border-gray-200',      // 4 - Từ chối
        'px-2 py-1 text-xs font-medium rounded bg-purple-50 text-purple-700 border border-purple-200' // 5 - Duyệt từ kế hoạch
    ];
    return classes[hanhDong] || classes[0];
}

function formatLogTime(timestamp) {
    if (!timestamp) return 'N/A';
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (minutes < 1) return 'Vừa xong';
    if (minutes < 60) return `${minutes} phút trước`;
    if (hours < 24) return `${hours} giờ trước`;
    if (days < 7) return `${days} ngày trước`;
    
    return date.toLocaleDateString('vi-VN');
}


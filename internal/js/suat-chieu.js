import Spinner from './util/spinner.js';

// Khai báo biến toàn cục cho navigation tuần
let currentWeekStart;

// Khai báo các biến toàn cục để các hàm helper có thể truy cập
let moviesData = [];
let roomsData = [];
let nhatKyData = []; // Lưu nhật ký toàn cục
let currentEditingShowtimeStatus = null; // Lưu trạng thái của suất chiếu đang sửa

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const datePicker = document.getElementById('date-picker');
    const showtimeModal = document.getElementById('showtime-modal');
    const confirmModal = document.getElementById('confirm-modal');
    const showtimeListing = document.getElementById('showtime-listing');
    const showtimeForm = document.getElementById('showtime-form');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    
    // Form fields
    const showtimeId = document.getElementById('showtime-id');
    const showtimeDate = document.getElementById('showtime-date');
    const modalTitle = document.getElementById('modal-title');
    const movieSearch = document.getElementById('movie-search');
    const selectedMovieId = document.getElementById('selected-movie-id');
    const movieSearchResults = document.getElementById('movie-search-results');
    const selectedMovieInfo = document.getElementById('selected-movie-info');
    const selectedMoviePoster = document.getElementById('selected-movie-poster');
    const selectedMovieTitle = document.getElementById('selected-movie-title');
    const selectedMovieDuration = document.getElementById('selected-movie-duration');
    const roomSelect = document.getElementById('room-select');
    const startTime = document.getElementById('start-time');
    const endTime = document.getElementById('end-time');
    const suggestedTimes = document.getElementById('suggested-times');
    const perRoomTimes = document.getElementById('per-room-times');
    const singleTimeRow = document.getElementById('single-time-row');
    
    // Buttons
    const btnAddShowtime = document.getElementById('btn-add-showtime');
    const btnCloseModal = document.getElementById('btn-close-modal');
    const btnSubmitWeek = document.getElementById('btn-submit-week');
    const weekStatus = document.getElementById('week-status');
    const btnCancel = document.getElementById('btn-cancel');
    const btnCancelDelete = document.getElementById('btn-cancel-delete');
    const btnConfirmDelete = document.getElementById('btn-confirm-delete');
    function fetchNhatKy() {
    const logBadge = document.getElementById('log-badge');
    fetch(`${showtimeListing.dataset.url}/api/nhat-ky-suat-chieu`)
        .then(res => res.json())
        .then(data => {
            if (data.success && Array.isArray(data.data)) {
                nhatKyData = data.data;
                // Đếm số nhật ký mới
                const soMoi = nhatKyData.filter(item => item.rap_da_xem == 0).length;
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
    fetchNhatKy(); // Gọi khi tải trang
    const btnLog = document.getElementById('btn-log');
        const logModal = document.getElementById('log-modal');
        const btnCloseLog = document.getElementById('btn-close-log');
        btnLog.addEventListener('click', () => {
            // Gọi API đánh dấu đã xem nhật ký
            fetch(`${showtimeListing.dataset.url}/api/nhat-ky-suat-chieu/rap-da-xem`, {
                method: 'PUT'
            }).then(() => {
                // Ẩn badge sau khi đã xem
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
                const idPhim = log.id_phim;
                if (!groupedByMovie[idPhim]) {
                    groupedByMovie[idPhim] = {
                        tenPhim: log.ten_phim || 'Không rõ',
                        logs: [],
                        latestTime: null // Lưu thời gian log mới nhất
                    };
                }
                groupedByMovie[idPhim].logs.push(log);
                // Cập nhật thời gian log mới nhất của nhóm phim này
                const logTime = new Date(log.created_at).getTime();
                if (!groupedByMovie[idPhim].latestTime || logTime > groupedByMovie[idPhim].latestTime) {
                    groupedByMovie[idPhim].latestTime = logTime;
                }
            });
            
            // Sắp xếp các nhóm phim theo log mới nhất (phim có log mới nhất sẽ hiển thị trước)
            const sortedMovieGroups = Object.values(groupedByMovie).sort((a, b) => {
                return (b.latestTime || 0) - (a.latestTime || 0);
            });
            
            // Render nhật ký theo format giống ảnh
            let html = '';
            sortedMovieGroups.forEach(movieGroup => {
                html += `
                    <div class="mb-6 bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                        <div class="flex p-4 border-b bg-gray-50">
                            <img src="${getMoviePosterUrl(movieGroup.logs[0])}" 
                                 alt="${movieGroup.tenPhim}" 
                                 class="w-16 h-20 object-cover rounded mr-4 shadow-md"
                                 onerror="this.src='https://via.placeholder.com/64x80?text=No+Image'">
                            <div class="flex-1">
                                <h3 class="font-bold text-base mb-1 text-gray-900">${movieGroup.tenPhim}</h3>
                                <p class="text-sm text-gray-600">${getMovieDuration(movieGroup.logs[0])} phút</p>
                            </div>
                        </div>
                        <div class="p-4 space-y-2">
                `;
                
                // Render từng log
                movieGroup.logs.forEach(log => {

                    const batDau = log.batdau ? new Date(log.batdau).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) : '??:??';
                    const ketThuc = log.batdau ? new Date(new Date(log.batdau).getTime() + getMovieDuration(log) * 60000).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) : '??:??';
                    const phongChieu = log.suat_chieu && log.suat_chieu.phong_chieu ? log.suat_chieu.phong_chieu.ten : 'Không rõ';
                    const hanhDong = getHanhDongLabel(log.hanh_dong, log.tinh_trang);
                    const hanhDongClass = getHanhDongClass(log.hanh_dong);
                    
                    html += `
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-center space-x-4 flex-1">
                                <div class="font-semibold text-gray-900 min-w-[110px]">${batDau} - ${ketThuc}</div>
                                <div class="text-sm text-gray-600 min-w-[100px]">${phongChieu}</div>
                                <div class="flex-1">
                                    <span class="${hanhDongClass}">${hanhDong}</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-gray-500 min-w-[100px] text-right">${formatLogTime(log.created_at)}</span>
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
    function fetchMovies() {
        return fetch(`${showtimeListing.dataset.url}/api/phim`)
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                    moviesData = data.data;
                } else {`${showtimeListing.dataset.url}/api/phim`
                    moviesData = [];
                }
            });
    }

    function fetchRooms() {
        return fetch(`${showtimeListing.dataset.url}/api/phong-chieu`)
            .then(res => res.json())
            .then( data => {
                if (data.success && Array.isArray(data.data)) {
                    roomsData = data.data;
                } else {
                    roomsData = [];
                }
            });
    }

    function fillRoomSelect() {
        roomSelect.innerHTML = '<option value="">-- Chọn phòng chiếu --</option>';
        roomsData.forEach(room => {
            const option = document.createElement('option');
            option.value = room.id;
            option.textContent = `${room.ten} - ${room.so_luong_ghe} ghế`;
            roomSelect.appendChild(option);
        });
    }

    // Initialize date picker
    const today = new Date();
    const flatpickrInstance = flatpickr(datePicker, {
        dateFormat: 'd/m/Y',
        minDate: today,
        defaultDate: today,
        locale: {
            firstDayOfWeek: 1
        },
        onChange: function(selectedDates) {
            loadShowtimes(formatDate(selectedDates[0]));
        }
    });
    
    // Initialize time pickers
    flatpickr(startTime, {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        minTime: "08:00",
        maxTime: "23:00",
        onChange: function(selectedDates, dateStr) {
            if (selectedMovieId.value) {
                updateEndTime();
            }
        }
    });
    
    // Đặt giá trị date-picker là ngày hiện tại nếu chưa có
    if (!datePicker.value) {
        const defaultDate = new Date();
        datePicker.value = formatDateDisplay(defaultDate);
    }
    // Chuyển sang format YYYY-MM-DD để load showtimes
    const selectedDateAPI = formatDate(parseDateFromDisplay(datePicker.value));
    loadShowtimes(selectedDateAPI);
    loadRooms();
    
    // Event listeners
    // Đã bỏ chức năng thêm suất chiếu mới - chỉ quản lý qua kế hoạch
    // btnAddShowtime.addEventListener('click', openAddModal);
    if (btnAddShowtime) {
        // Ẩn nút nếu còn tồn tại
        btnAddShowtime.style.display = 'none';
    }
    btnCloseModal.addEventListener('click', closeModal);
    if (btnSubmitWeek) {
        btnSubmitWeek.addEventListener('click', onSubmitWeekClick);
    }
    btnCancel.addEventListener('click', closeModal);
    btnCancelDelete.addEventListener('click', () => {
        confirmModal.classList.add('hidden');
    });
    
    showtimeForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        if (!validateForm()) return;
        Spinner.show({ target: showtimeModal, text: 'Đang xử lý...' });
        const batdau = `${showtimeDate.value} ${startTime.value}`;
        const ketthuc = `${showtimeDate.value} ${endTime.value}`;
        
        const selectedRooms = Array.from(roomSelect.selectedOptions)
            .map(opt => opt.value)
            .filter(val => val);
        const id = showtimeId.value;
        if (id && selectedRooms.length !== 1) {
                showToast('Vui lòng chọn đúng 1 phòng chiếu khi cập nhật', 'error');
                Spinner.hide();
                return;
            }
        if (!id && selectedRooms.length === 0) {
            showToast('Vui lòng chọn ít nhất một phòng chiếu', 'error');
            Spinner.hide();
            return;
        }
        try {
            // Kiểm tra trùng suất chiếu
            for (const roomId of selectedRooms) {
                const startValue = getStartTimeForRoom(roomId) || startTime.value;
                const batdauRoom = `${showtimeDate.value} ${startValue}`;
                const checkUrl = `${showtimeListing.dataset.url}/api/suat-chieu/kiem-tra-hop-le?batdau=${encodeURIComponent(batdauRoom)}&id_phong_chieu=${roomId}&thoi_luong_phim=${selectedMovieInfo.dataset.duration}`;
                const checkRes = await fetch(checkUrl, { method: 'GET' });
                const checkData = await checkRes.json();
                if (!checkData.success) {
                    showToast(checkData.message, 'error');
                    Spinner.hide();
                    return;
                }
            }
        } catch (e) {
            showToast('Lỗi kiểm tra suất chiếu', 'error');
            Spinner.hide();
            return;
        }
        const body = JSON.stringify({
            id_phim: selectedMovieId.value,
            id_phongChieu: parseInt(selectedRooms[0]),
            batdau,
            ketthuc
        });
        try {
            let res, data;
            const wasRejected = id && currentEditingShowtimeStatus == 2; // Kiểm tra nếu suất chiếu ban đầu bị từ chối
            if (id) {
                res = await fetch(`${showtimeListing.dataset.url}/api/suat-chieu/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body
                });
            } else {
                // Thêm mới
                if (selectedRooms.length > 1) {
                    for (const roomId of selectedRooms) {
                        const startVal = getStartTimeForRoom(roomId) || startTime.value;
                        if (!startVal) { data = { success: false, message: 'Vui lòng chọn giờ bắt đầu cho từng phòng' }; break; }
                        const batdauR = `${showtimeDate.value} ${startVal}`;
                        const endVal = calculateEndFromStart(startVal);
                        const ketthucR = `${showtimeDate.value} ${endVal}`;
                        const resp = await fetch(`${showtimeListing.dataset.url}/api/suat-chieu`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                id_phim: selectedMovieId.value,
                                list_phongChieu: [roomId],
                                batdau: batdauR,
                                ketthuc: ketthucR
                            })
                        });
                        const js = await resp.json();
                        if (!js.success) { data = js; break; }
                        data = js;
                    }
            } else {
                res = await fetch(`${showtimeListing.dataset.url}/api/suat-chieu`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        id_phim: selectedMovieId.value,
                        list_phongChieu: selectedRooms,
                        batdau,
                        ketthuc
                    })
                });
            data = await res.json();
                }
            }
            if (data.success) {
                closeModal();
                loadShowtimes(showtimeDate.value);
                // Thông báo đặc biệt nếu đang sửa suất chiếu bị từ chối
                const message = id && wasRejected 
                    ? 'Cập nhật suất chiếu thành công. Suất chiếu đã được đưa về trạng thái chờ duyệt.' 
                    : (id ? 'Cập nhật suất chiếu thành công' : 'Thêm suất chiếu thành công');
                showToast(message);
            } else {
                showToast(data.message, 'error');
            }
        } catch (e) {
            showToast(id ? 'Lỗi cập nhật suất chiếu' : 'Lỗi thêm suất chiếu', 'error');
        }
        Spinner.hide();
    });
    movieSearch.addEventListener('input', debounce(handleMovieSearch, 300));
    roomSelect.addEventListener('change', () => {
        renderPerRoomTimeInputs();
        generateSuggestedTimes();
    });
    
    // Functions
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    function formatDateDisplay(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${day}/${month}/${year}`;
    }
    
    function parseDateFromDisplay(dateStr) {
        // Chuyển từ DD/MM/YYYY sang Date object
        const parts = dateStr.split('/');
        const day = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1; // Tháng trong JavaScript là 0-11
        const year = parseInt(parts[2], 10);
        return new Date(year, month, day);
    }
    
    function parseDateFromAPI(dateStr) {
        // Chuyển từ YYYY-MM-DD sang Date object
        return new Date(dateStr);
    }
    
    function displayDate(date) {
        const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
        return new Date(date).toLocaleDateString('vi-VN', options);
    }
    
    function updateEndTime() {
        // Calculate end time based on movie duration and start time
        const movieDuration = parseInt(selectedMovieInfo.dataset.duration, 10) || 0;
        
        if (startTime.value && movieDuration > 0) {
            const [hours, minutes] = startTime.value.split(':').map(Number);
            let startMinutes = hours * 60 + minutes;
            let endMinutes = startMinutes + movieDuration + 30; // Add 30 minutes for ads/cleanup
            
            const endHours = Math.floor(endMinutes / 60);
            const endMins = endMinutes % 60;
            
            endTime.value = `${String(endHours).padStart(2, '0')}:${String(endMins).padStart(2, '0')}`;
        } else {
            endTime.value = '';
        }
    }
    
    // Đã bỏ chức năng thêm suất chiếu mới - chỉ quản lý qua kế hoạch
    async function openAddModal() {
        // Chuyển sang tab Kế hoạch thay vì mở modal
        const tabBtnKehoach = document.getElementById('tab-btn-kehoach');
        if (tabBtnKehoach) {
            tabBtnKehoach.click();
            showToast('Vui lòng sử dụng tab Kế hoạch để thêm suất chiếu mới', 'info');
        }
        return;
        
        /* Code cũ - đã vô hiệu hóa
        resetForm();
        modalTitle.textContent = 'Thêm suất chiếu mới';
        showtimeId.value = '';
        await Promise.all([fetchMovies(), fetchRooms()]);
        fillRoomSelect();

        // Thêm lại thuộc tính multiple khi thêm mới
        roomSelect.setAttribute('multiple', 'multiple');
        // Gán ngày chiếu theo date-picker hiện tại
        const dateDisplay = document.getElementById('date-picker').value;
        const dateAPI = dateDisplay && dateDisplay.includes('/')
            ? formatDate(parseDateFromDisplay(dateDisplay))
            : (dateDisplay || formatDate(new Date()));
        showtimeDate.value = dateAPI;
        // Khởi tạo khung giờ gợi ý + input theo phòng
        generateSuggestedTimes();
        renderPerRoomTimeInputs();
        showtimeModal.classList.remove('hidden');
        */
    }
    
    async function openEditModal(id) {
        Spinner.show({ target: showtimeModal, text: 'Đang tải...' });
        resetForm();
        modalTitle.textContent = 'Cập nhật suất chiếu';
        // Lưu ý: Chỉ dùng để sửa suất chiếu đã có, không thêm mới
        showtimeId.value = id;
        await Promise.all([fetchMovies(), fetchRooms()]);
        fillRoomSelect();

        // Bỏ thuộc tính multiple khi cập nhật
        roomSelect.removeAttribute('multiple');

        const dateDisplay = document.getElementById('date-picker').value;
        const date = formatDate(parseDateFromDisplay(dateDisplay)); // Chuyển sang YYYY-MM-DD
        let showtime = null;
        try {
            const res = await fetch(`${showtimeListing.dataset.url}/api/suat-chieu?ngay=${date}`);
            const data = await res.json();
            if (data.success && Array.isArray(data.data)) {
                showtime = data.data.find(s => s.id === id);
            }
        } catch (e) {}
        if (showtime) {
            showtimeDate.value = showtime.batdau.substr(0, 10);
            selectedMovieId.value = showtime.phim.id;
            startTime.value = showtime.batdau.substr(11,5);
            endTime.value = showtime.ketthuc.substr(11,5);
            roomSelect.value = showtime.phong_chieu.id;
            movieSearch.value = showtime.phim.ten_phim;
            const movie = moviesData.find(m => m.id === showtime.phim.id);
            if (movie) {
                selectedMovieInfo.classList.remove('hidden');
                selectedMoviePoster.src = `${showtimeListing.dataset.urlminio}/${movie.poster_url}`;
                selectedMovieTitle.textContent = movie.ten_phim;
                selectedMovieDuration.textContent = `${movie.thoi_luong} phút`;
                selectedMovieInfo.dataset.duration = movie.thoi_luong;
            }
            generateSuggestedTimes();
            
            // Lưu trạng thái ban đầu của suất chiếu
            currentEditingShowtimeStatus = showtime.tinh_trang;
            
            // Hiển thị thông báo nếu suất chiếu bị từ chối
            if (showtime.tinh_trang == 2) {
                modalTitle.textContent = 'Cập nhật suất chiếu (Từ chối → Chờ duyệt)';
                // Có thể thêm một thông báo trong modal nếu cần
            }
            
            showtimeModal.classList.remove('hidden');
        } else {
            showToast('Không tìm thấy thông tin suất chiếu', 'error');
        }
        Spinner.hide();
    }
    
    function closeModal() {
        showtimeModal.classList.add('hidden');
        resetForm();
        currentEditingShowtimeStatus = null; // Reset trạng thái khi đóng modal
    }
    
    function resetForm() {
        showtimeForm.reset();
        selectedMovieId.value = '';
        selectedMovieInfo.classList.add('hidden');
        movieSearchResults.innerHTML = '';
        movieSearchResults.classList.add('hidden');
        suggestedTimes.innerHTML = '';
    }
    
    function validateForm() {
        // Simple validation
        if (!selectedMovieId.value) {
            showToast('Vui lòng chọn phim', 'error');
            return false;
        }
        const selectedRooms = Array.from(roomSelect.selectedOptions)
            .map(opt => opt.value)
            .filter(Boolean);
        if (selectedRooms.length === 0) {
            showToast('Vui lòng chọn phòng chiếu', 'error');
            return false;
        }
        
        // Nếu nhiều phòng, yêu cầu từng phòng phải có giờ bắt đầu
        if (selectedRooms.length > 1) {
            for (const roomId of selectedRooms) {
                const startVal = getStartTimeForRoom(roomId);
                if (!startVal) {
                    const roomName = getRoomNameById(roomId) || `phòng ${roomId}`;
                    showToast(`Vui lòng nhập giờ bắt đầu cho ${roomName}`, 'error');
                    return false;
                }
            }
            return true;
        }
        
        // Một phòng: kiểm tra input chung
        if (!startTime.value) {
            showToast('Vui lòng chọn giờ bắt đầu', 'error');
            return false;
        }
        
        return true;
    }
    
    function handleMovieSearch() {
        const query = movieSearch.value.trim().toLowerCase();
        if (query.length < 2) {
            movieSearchResults.innerHTML = '';
            movieSearchResults.classList.add('hidden');
            return;
        }
        const filteredMovies = moviesData.filter(movie =>
            movie.ten_phim && movie.ten_phim.toLowerCase().includes(query)
        );
        displayMovieResults(filteredMovies);
    }
    
    function displayMovieResults(movies) {
        movieSearchResults.innerHTML = '';
        if (movies.length === 0) {
            movieSearchResults.innerHTML = '<div class="p-3 text-sm text-gray-500">Không tìm thấy phim</div>';
            movieSearchResults.classList.remove('hidden');
            return;
        }
        movies.forEach(movie => {
            const poster = `${showtimeListing.dataset.urlminio}/${movie.poster_url}`;
            const item = document.createElement('div');
            item.className = 'p-2 hover:bg-gray-100 cursor-pointer flex items-center';
            item.innerHTML = `
                <img src="${poster}" alt="${movie.ten_phim}" class="w-10 h-14 object-cover mr-2">
                <div>
                    <div class="font-medium">${movie.ten_phim}</div>
                    <div class="text-xs text-gray-600">${movie.thoi_luong || ''} phút</div>
                </div>
            `;
            item.addEventListener('click', () => {
                selectedMovieId.value = movie.id;
                movieSearch.value = movie.ten_phim;
                selectedMovieInfo.classList.remove('hidden');
                selectedMoviePoster.src = poster;
                selectedMovieTitle.textContent = movie.ten_phim;
                selectedMovieDuration.textContent = `${movie.thoi_luong || ''} phút`;
                selectedMovieInfo.dataset.duration = movie.thoi_luong || 0;
                movieSearchResults.classList.add('hidden');
                if (startTime.value) {
                    updateEndTime();
                }
                generateSuggestedTimes();
            });
            movieSearchResults.appendChild(item);
        });
        movieSearchResults.classList.remove('hidden');
    }
    
    async function loadShowtimes(date) {
        Spinner.show({ text: 'Đang tải suất chiếu...' });
        try {
            const res = await fetch(`${showtimeListing.dataset.url}/api/suat-chieu?ngay=${date}`);
            const data = await res.json();
            if (data.success && Array.isArray(data.data)) {
                const showtimes = data.data;
                const movieMap = {};
                showtimes.forEach(s => {
                    const movieId = s.phim.id;
                    if (!movieMap[movieId]) {
                        movieMap[movieId] = {
                            id: movieId,
                            title: s.phim.ten_phim,
                            duration: s.phim.thoi_luong,
                            poster: `${showtimeListing.dataset.urlminio}/${s.phim.poster_url}`,
                            showtimes: []
                        };
                    }
                    movieMap[movieId].showtimes.push({
                        id: s.id,
                        movie_id: movieId,
                        movie_title: s.phim.ten_phim,
                        movie_poster: `${showtimeListing.dataset.urlminio}/${s.phim.poster_url}`,
                        movie_duration: s.phim.thoi_luong,
                        room_id: s.phong_chieu.id,
                        room_name: s.phong_chieu.ten,
                        status: s.tinh_trang,
                        date: date,
                        start_time: s.batdau.substr(11,5),
                        end_time: s.ketthuc.substr(11,5)
                    });
                });
                const moviesWithShowtimes = Object.values(movieMap);
                displayShowtimes(moviesWithShowtimes, date);
                updateControlsLock();
            } else {
                showtimeListing.innerHTML = '<div class="text-center py-8 text-gray-500">Không có dữ liệu suất chiếu</div>';
            }
            // Không cập nhật date-picker.value ở đây để giữ format DD/MM/YYYY
        } catch (e) {
            showtimeListing.innerHTML = '<div class="text-center py-8 text-red-500">Lỗi tải dữ liệu suất chiếu</div>';
        } finally {
            Spinner.hide();
        }
    }

    function getWeekStartFromAnyDate(dateStr) {
        // dateStr: YYYY-MM-DD
        const d = new Date(dateStr);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        d.setDate(diff);
        d.setHours(0,0,0,0);
        return formatDate(d);
    }


    function updateControlsLock() {
        if (!weekStatus) return;
        const text = weekStatus.textContent || '';
        const locked = text.includes('Chờ duyệt') || text.includes('Đã duyệt');
        document.querySelectorAll('.btn-edit, .btn-delete').forEach(btn => {
            if (locked) {
                btn.setAttribute('disabled', 'true');
                btn.classList.add('opacity-50', 'pointer-events-none');
            } else {
                btn.removeAttribute('disabled');
                btn.classList.remove('opacity-50', 'pointer-events-none');
            }
        });
        if (btnAddShowtime) btnAddShowtime.style.display = locked ? 'none' : '';
    }

    async function onSubmitWeekClick() {
        const dateDisplay = document.getElementById('date-picker').value;
        const dateAPI = formatDate(parseDateFromDisplay(dateDisplay));
        const weekStart = getWeekStartFromAnyDate(dateAPI);
        if (!confirm('Bạn có chắc chắn muốn gửi kế hoạch chiếu phim cho tuần này để xét duyệt không? Sau khi gửi, bạn sẽ không thể chỉnh sửa.')) return;
        Spinner.show({ text: 'Đang gửi duyệt...' });
        try {
            const res = await fetch(`${showtimeListing.dataset.url}/api/suat-chieu/gui-duyet-tuan`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ week_start: weekStart })
            });
            const data = await res.json();
            if (data.success) {
                showToast('Gửi duyệt thành công');
                await fetchWeekStatus(dateAPI);
                updateControlsLock();
            } else {
                showToast(data.message || 'Gửi duyệt thất bại', 'error');
            }
        } catch (e) {
            showToast('Gửi duyệt thất bại', 'error');
        }
        Spinner.hide();
    }
    
    function displayShowtimes(movies, date) {
        if (movies.length === 0) {
            showtimeListing.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-gray-500 mb-4">Chưa có suất chiếu nào vào ngày ${displayDate(date)}</p>
                </div>
            `;
            return;
        }
        
        showtimeListing.innerHTML = '';
        
        movies.forEach(movie => {
            const movieCard = document.createElement('div');
            movieCard.className = 'bg-white border rounded-lg overflow-hidden shadow-sm mb-6';
            
            const showtimesHtml = movie.showtimes.map(showtime => {
                // Kiểm tra hết hạn
                const now = new Date();
                // Tạo đối tượng Date từ ngày và giờ kết thúc
                const endDateTime = new Date(`${showtime.date}T${showtime.end_time}:00`);
                const isExpired = endDateTime < now;
                // Cho phép sửa nếu: chưa duyệt (0), từ chối (2), chờ duyệt lại (3) và chưa hết hạn
                // Không cho sửa nếu đã duyệt (1) hoặc đã hết hạn
                const isEditable = showtime.status != 1 && !isExpired;

                return `
                    <div class="flex col border-t py-3 px-4 gap-1">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1">
                            <div class="font-medium min-w-24">${showtime.start_time} - ${showtime.end_time}</div>
                            <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded ml-0 sm:ml-2">${showtime.room_name}</span>
                        </div>
                        <div class="flex items-center ml-auto space-x-2">
                            
                        </div>
                    </div>
                `;
            }).join('');
            
            movieCard.innerHTML = `
                <div class="flex p-4">
                    <img src="${movie.poster}" alt="${movie.title}" class="w-16 h-24 object-cover rounded mr-4">
                    <div>
                        <h3 class="font-bold text-lg">${movie.title}</h3>
                        <p class="text-sm text-gray-600">${movie.duration} phút</p>
                    </div>
                </div>
                <div class="showtimes">
                    ${showtimesHtml}
                </div>
            `;
            
            showtimeListing.appendChild(movieCard);
        });
        
        // Add event listeners for edit and delete buttons
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', function() {
                const id = parseInt(this.dataset.id);
                openEditModal(id);
            });
        });
        
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function() {
                const id = parseInt(this.dataset.id);
                confirmModal.classList.remove('hidden');
                
                btnConfirmDelete.onclick = function() {
                    deleteShowtime(id);
                };
            });
        });
    }
    
    function deleteShowtime(id) {
        Spinner.show({ text: 'Đang xóa...' });
        fetch(`${showtimeListing.dataset.url}/api/suat-chieu/${id}`, {
            method: 'DELETE'
        })
        .then(res => res.json())
        .then(data => {
            confirmModal.classList.add('hidden');
            if (data.success) {
                loadShowtimes(formatDate(flatpickrInstance.selectedDates[0]));
                showToast('Xóa suất chiếu thành công');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(() => {
            showToast('Lỗi xóa suất chiếu', 'error');
        })
        .finally(() => {
            Spinner.hide();
        });
    }
    
    function loadRooms() {
        roomSelect.innerHTML = '<option value="">-- Chọn phòng chiếu --</option>';
    }
    
    function generateSuggestedTimes() {
        suggestedTimes.innerHTML = '';
        const movieId = selectedMovieId.value || '';
        const date = showtimeDate.value || '';
        let duration = selectedMovieInfo.dataset.duration;
        duration = /^\d+$/.test(duration) ? duration : '';

        const selectedRooms = Array.from(roomSelect.selectedOptions)
            .map(opt => opt.value)
            .filter(Boolean);

        if (!movieId || selectedRooms.length === 0 || !date || !duration) {
            suggestedTimes.innerHTML = '<div class="p-2 text-sm text-gray-500">Vui lòng chọn phim, phòng chiếu và ngày chiếu</div>';
            return;
        }

        const fetches = selectedRooms.map(roomId => {
        const url = `${showtimeListing.dataset.url}/api/suat-chieu/tao-khung-gio-goi-y?ngay=${date}&id_phong_chieu=${roomId}&thoi_luong_phim=${duration}`;
            return fetch(url)
            .then(res => res.json())
                .then(data => ({ roomId, times: (data.success && Array.isArray(data.data)) ? data.data : [] }))
                .catch(() => ({ roomId, times: null }));
        });

        Promise.all(fetches).then(results => {
        suggestedTimes.innerHTML = '';
            results.forEach(({ roomId, times }) => {
                const roomName = getRoomNameById(roomId) || `Phòng ${roomId}`;
                const group = document.createElement('div');
                group.className = 'mb-5 p-4 border rounded-lg bg-gray-50';

                const title = document.createElement('div');
                title.className = 'text-base font-bold text-blue-700 mb-3';
                title.textContent = `Khung giờ ${roomName}`;
                group.appendChild(title);

                const container = document.createElement('div');
                container.className = 'grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3';

                if (times === null) {
                    container.innerHTML = '<div class="p-2 text-sm text-red-500 col-span-full">Lỗi lấy khung giờ gợi ý</div>';
                } else if (times.length === 0) {
                    container.innerHTML = '<div class="p-2 text-sm text-gray-500 col-span-full">Không có khung giờ gợi ý phù hợp</div>';
                } else {
                    times.forEach(time => {
                        const label = extractTimeLabel(time);
                        const timeSlot = document.createElement('button');
                        timeSlot.type = 'button';
                        timeSlot.className = 'time-slot border rounded-full py-2 px-3 text-center text-sm font-medium bg-white shadow hover:bg-blue-50 whitespace-nowrap text-blue-700 border-blue-200';
                        timeSlot.textContent = label;
                        // Disable nếu là ngày hôm nay và giờ < hiện tại
                        if (shouldDisableTime(showtimeDate.value, label)) {
                            timeSlot.disabled = true;
                            timeSlot.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
                            timeSlot.title = 'Đã qua giờ cho phép';
                        }
            timeSlot.addEventListener('click', function() {
                            if (this.disabled) return;
                            const roomInput = document.getElementById(`start-time-room-${roomId}`);
                            if (roomInput) {
                                roomInput.value = label;
                                autoUpdateEndForRoom(roomId);
                            } else {
                                startTime.value = label;
                updateEndTime();
                            }
                            document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected', 'bg-blue-600', 'text-white', 'border-blue-600'));
                            this.classList.add('selected', 'bg-blue-600', 'text-white', 'border-blue-600');
                        });
                        container.appendChild(timeSlot);
                    });
                }

                group.appendChild(container);
                suggestedTimes.appendChild(group);
            });
        });
    }

    function getRoomNameById(id) {
        const room = roomsData.find(r => String(r.id) === String(id));
        return room ? room.ten : '';
    }
    function renderPerRoomTimeInputs() {
        if (!perRoomTimes) return;
        const selectedRooms = Array.from(roomSelect.selectedOptions).map(o => o.value).filter(Boolean);
        if (selectedRooms.length <= 1) {
            perRoomTimes.classList.add('hidden');
            if (singleTimeRow) singleTimeRow.classList.remove('hidden');
            return;
        }
        perRoomTimes.innerHTML = '';
        perRoomTimes.classList.remove('hidden');
        if (singleTimeRow) singleTimeRow.classList.add('hidden');
        selectedRooms.forEach(roomId => {
            const roomName = getRoomNameById(roomId) || `Phòng ${roomId}`;
            const row = document.createElement('div');
            row.className = 'p-3 border rounded-md bg-white';
            row.innerHTML = `
                <div class="text-sm font-medium text-gray-700 mb-2">${roomName}</div>
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Giờ bắt đầu</label>
                        <input type="text" id="start-time-room-${roomId}" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm" placeholder="Chọn giờ bắt đầu">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Giờ kết thúc</label>
                        <input type="text" id="end-time-room-${roomId}" class="border-gray-300 rounded-md shadow-sm block w-full sm:text-sm" disabled>
                    </div>
                </div>`;
            perRoomTimes.appendChild(row);
            const input = row.querySelector(`#start-time-room-${roomId}`);
            flatpickr(input, { enableTime: true, noCalendar: true, dateFormat: 'H:i', minTime: '08:00', maxTime: '23:00', onChange: () => autoUpdateEndForRoom(roomId) });
        });
    }
    function getStartTimeForRoom(roomId) {
        const el = document.getElementById(`start-time-room-${roomId}`);
        return el ? el.value : '';
    }
    function calculateEndFromStart(startVal) {
        const movieDuration = parseInt(selectedMovieInfo.dataset.duration, 10) || 0;
        if (!startVal || movieDuration <= 0) return '';
        const [h, m] = startVal.split(':').map(Number);
        let minutes = h * 60 + m + movieDuration + 30;
        const endHours = Math.floor(minutes / 60);
        const endMins = minutes % 60;
        return `${String(endHours).padStart(2,'0')}:${String(endMins).padStart(2,'0')}`;
    }
    function autoUpdateEndForRoom(roomId) {
        const endEl = document.getElementById(`end-time-room-${roomId}`);
        const startVal = getStartTimeForRoom(roomId);
        if (!endEl) return;
        endEl.value = calculateEndFromStart(startVal);
    }
    function extractTimeLabel(value) {
        // Lấy đúng định dạng HH:MM từ chuỗi bất kỳ
        const m = String(value).match(/\b(\d{1,2}:\d{2})\b/);
        return m ? m[1].padStart(5, '0') : String(value);
    }
    function shouldDisableTime(dateStr, timeStr) {
        // dateStr: YYYY-MM-DD, timeStr: HH:MM
        if (!dateStr || !timeStr) return false;
        const now = new Date();
        const [h, m] = timeStr.split(':').map(Number);
        const slot = new Date(dateStr + 'T' + String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':00');
        // Chỉ disable nếu là cùng ngày hôm nay và slot < hiện tại
        const isSameDay = now.toISOString().slice(0,10) === dateStr;
        return isSameDay && slot < now;
    }
    
    function showToast(message, type = 'success') {
        toastMessage.textContent = message;
        
        if (type === 'error') {
            toast.classList.remove('bg-green-500');
            toast.classList.add('bg-red-500');
        } else {
            toast.classList.remove('bg-red-500');
            toast.classList.add('bg-green-500');
        }
        
        // Show toast
        toast.classList.remove('translate-y-20', 'opacity-0');
        
        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0');
        }, 3000);
    }
    
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(context, args);
            }, wait);
        };
    }
    
    // Click outside to close dropdowns
    document.addEventListener('click', function(event) {
        if (!movieSearch.contains(event.target) && !movieSearchResults.contains(event.target)) {
            movieSearchResults.classList.add('hidden');
        }
    });
    
    // Các biến và hàm hiện có trong file đã được giữ nguyên

    // Chức năng điều hướng theo tuần (Phương án 1)
    function updateWeekDisplay() {
        const weekRangeDisplay = document.getElementById('week-range');
        if (!weekRangeDisplay || !currentWeekStart) return;
        
        const weekEnd = new Date(currentWeekStart);
        weekEnd.setDate(currentWeekStart.getDate() + 6);
        
        const formatDay = date => {
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`; // DD/MM/YYYY
        };
        weekRangeDisplay.textContent = `${formatDay(currentWeekStart)} - ${formatDay(weekEnd)}`;
    }

    function initWeekNavigation() {
        const prevWeekBtn = document.getElementById('prev-week');
        const nextWeekBtn = document.getElementById('next-week');
        let selectedDate = document.getElementById('date-picker').value;
        
        if (!selectedDate) {
            const today = new Date();
            today.setHours(0,0,0,0);
            selectedDate = formatDateDisplay(today);
            document.getElementById('date-picker').value = selectedDate;
        }
        
        // Parse date - date-picker hiển thị DD/MM/YYYY
        let baseDate;
        if (selectedDate.includes('/')) {
            // Format DD/MM/YYYY từ date-picker
            baseDate = parseDateFromDisplay(selectedDate);
        } else {
            // Format YYYY-MM-DD từ API
            baseDate = parseDateFromAPI(selectedDate);
        }
        
        baseDate.setHours(0,0,0,0);
        currentWeekStart = getMonday(baseDate); // Tuần bắt đầu từ thứ 2
        updateWeekDisplay();
        // Chuyển sang format YYYY-MM-DD cho renderWeekDays
        const selectedDateAPI = formatDate(baseDate);
        renderWeekDays(currentWeekStart, selectedDateAPI);
        
        prevWeekBtn.addEventListener('click', () => {
            currentWeekStart.setDate(currentWeekStart.getDate() - 7);
            updateWeekDisplay();
            // Cập nhật date-picker với ngày đầu tuần mới (format DD/MM/YYYY)
            const newDateDisplay = formatDateDisplay(currentWeekStart);
            const newDateAPI = formatDate(currentWeekStart);
            document.getElementById('date-picker').value = newDateDisplay;
            renderWeekDays(currentWeekStart, newDateAPI);
            loadShowtimes(newDateAPI);
        });
        
        nextWeekBtn.addEventListener('click', () => {
            currentWeekStart.setDate(currentWeekStart.getDate() + 7);
            updateWeekDisplay();
            // Cập nhật date-picker với ngày đầu tuần mới (format DD/MM/YYYY)
            const newDateDisplay = formatDateDisplay(currentWeekStart);
            const newDateAPI = formatDate(currentWeekStart);
            document.getElementById('date-picker').value = newDateDisplay;
            renderWeekDays(currentWeekStart, newDateAPI);
            loadShowtimes(newDateAPI);
        });
    }

    function getMonday(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Nếu Chủ nhật thì lùi về Thứ 2 tuần trước
        d.setDate(diff);
        d.setHours(0,0,0,0);
        return d;
    }

    function renderWeekDays(currentWeekStart, selectedDateStr) {
        const container = document.getElementById('date-nav-container');
        if (!container) return;
        container.innerHTML = '';
        const days = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        const monday = getMonday(currentWeekStart);
        const today = new Date();
        today.setHours(0,0,0,0);
        for (let i = 0; i < 7; i++) {
            const itemDate = new Date(monday);
            itemDate.setDate(monday.getDate() + i);
            itemDate.setHours(0,0,0,0);
            const day = itemDate.getDate();
            const month = itemDate.getMonth() + 1;
            const year = itemDate.getFullYear();
            const dayOfWeek = days[i];
            const formattedDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
            const isSelected = formattedDate === selectedDateStr;
            const isToday = itemDate.getTime() === today.getTime();
            const itemDiv = document.createElement('div');
            itemDiv.className = 'date-nav-item';
            itemDiv.dataset.date = formattedDate;
            itemDiv.innerHTML = `<div class="text-center p-2 rounded-lg border cursor-pointer hover:bg-gray-50 transition-colors">
                <p class="text-xs font-medium ${isSelected ? 'text-blue-600' : isToday ? 'text-green-600' : 'text-gray-500'}">${dayOfWeek}</p>
                <p class="text-lg font-bold ${isSelected ? 'text-blue-600' : isToday ? 'text-green-600' : ''}">${day.toString().padStart(2, '0')}</p>
                <p class="text-xs ${isSelected ? 'text-blue-600' : isToday ? 'text-green-600' : 'text-gray-500'}">${month.toString().padStart(2, '0')}/${year.toString().slice(-2)}</p>
            </div>`;
            const div = itemDiv.querySelector('div');
            if (isSelected) {
                div.classList.add('border-blue-500', 'bg-blue-50');
            }
            if (isToday) {
                div.classList.add('border-green-500', 'bg-green-50');
            }
            itemDiv.addEventListener('click', function() {
                // Cập nhật date-picker với format DD/MM/YYYY
                const dateDisplay = formatDateDisplay(new Date(formattedDate));
                document.getElementById('date-picker').value = dateDisplay;
                // Cập nhật currentWeekStart để tuần hiển thị đúng
                currentWeekStart = getMonday(new Date(formattedDate));
                updateWeekDisplay();
                renderWeekDays(currentWeekStart, formattedDate);
                loadShowtimes(formattedDate);
                updateAddShowtimeButtonVisibility();
            });
            container.appendChild(itemDiv);
        }
    }

    // Khởi tạo navigation tuần và load dữ liệu đúng thứ tự
    if (document.getElementById('prev-week')) {
        initWeekNavigation();
    }

    // Đã bỏ chức năng thêm suất chiếu mới - không cần hàm này nữa
    // function updateAddShowtimeButtonVisibility() { ... }
});

function showtimeStatusLabel(status) {
    console.log('Status:', status);
    switch (status) {
        case 0: return { text: 'Chờ duyệt', color: 'bg-yellow-100 text-yellow-800' };
        case 1: return { text: 'Đã duyệt', color: 'bg-green-100 text-green-800' };
        case 2: return { text: 'Từ chối', color: 'bg-red-100 text-red-800' };
        case 3: return { text: 'Chờ duyệt lại', color: 'bg-blue-100 text-blue-800' };
        default: return { text: 'Không xác định', color: 'bg-gray-100 text-gray-800' };
    }
}

// Các hàm helper cho modal nhật ký
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
    if (!timestamp) return '';
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
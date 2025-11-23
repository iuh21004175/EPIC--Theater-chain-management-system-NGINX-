// Các hàm tiện ích cho định dạng ngày tháng
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatDateDisplay(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

function parseDateFromAPI(dateString) {
    // Parse YYYY-MM-DD date format from API
    const [year, month, day] = dateString.split('-').map(Number);
    return new Date(year, month - 1, day);
}

function getDayName(date) {
    const days = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
    return days[date.getDay()];
}

// Thêm import Spinner từ file util/spinner.js
import Spinner from './util/spinner.js';

// Biến toàn cục
let keHoachData = [];
let moviesData = [];
let roomsData = [];
let currentPlanWeekStart = null;
let showtimeCounter = 1; // Đếm số suất chiếu trong modal
let currentSelectedDate = null; // Thêm biến toàn cục để lưu ngày đã chọn hiện tại
let weekOffset = 1; // 0 = tuần hiện tại, 1 = tuần kế tiếp, 2 = tuần kế tiếp + 1, -1 = tuần trước

document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo khi chuyển sang tab Kế hoạch
    const tabBtnKehoach = document.getElementById('tab-btn-kehoach');
    if (tabBtnKehoach) {
        tabBtnKehoach.addEventListener('click', function() {
            initializeKeHoachTab();
        });
    }
});

function initializeKeHoachTab() {
    // Khởi tạo event listeners và load dữ liệu
    setupKeHoachEventListeners();
    calculateNextWeek();
    loadMovies();
    loadRooms();
    loadKeHoach();
}

function setupKeHoachEventListeners() {
    const btnCreateNewPlan = document.getElementById('btn-create-new-plan');
    // const btnAddShowtimeToPlan = document.getElementById('btn-add-showtime-to-plan'); // removed - per-day buttons used
    const btnClosePlanModal = document.getElementById('btn-close-plan-modal');
    const btnCancelPlan = document.getElementById('btn-cancel-plan');
    const btnAddAnotherShowtime = document.getElementById('btn-add-another-showtime');
    const btnSaveAllShowtimes = document.getElementById('btn-save-all-showtimes');
    const btnCancelPlanDelete = document.getElementById('btn-cancel-plan-delete');
    const btnPrevWeek = document.getElementById('btn-prev-week');
    const btnNextWeek = document.getElementById('btn-next-week');

    if (btnCreateNewPlan) {
        // Render 7 empty day cards for the week instead of opening a 7-day modal
        btnCreateNewPlan.addEventListener('click', (e) => {
            e.preventDefault();
            createEmptyWeekView();
        });
    }

    // removed global "Chỉnh sửa kế hoạch" button listener - open modal is now per-day via .open-plan-for-day

    if (btnClosePlanModal) {
        btnClosePlanModal.addEventListener('click', closePlanModal);
    }
    if (btnCancelPlan) {
        btnCancelPlan.addEventListener('click', closePlanModal);
    }
    if (btnAddAnotherShowtime) {
        btnAddAnotherShowtime.addEventListener('click', addAnotherShowtime);
    }

    if (btnCancelPlanDelete) {
        btnCancelPlanDelete.addEventListener('click', () => {
            document.getElementById('plan-confirm-modal').classList.add('hidden');
        });
    }
    if (btnPrevWeek) {
        btnPrevWeek.addEventListener('click', prevWeek);
    }
    if (btnNextWeek) {
        btnNextWeek.addEventListener('click', nextWeek);
    }
    if (btnSaveAllShowtimes) {
        btnSaveAllShowtimes.addEventListener('click', async (e) => {
            e.preventDefault();
            await saveAllShowtimes();
        });
    }
    
    // Gắn event listeners cho các tính năng mới
    setupNewEventListeners();
}

function calculateNextWeek() {
    const today = new Date();
    const dayOfWeek = today.getDay(); // 0 = CN, 1 = T2, ..., 6 = T7
    
    // Tính thứ 2 của tuần HIỆN TẠI (không phải tuần sau)
    // Nếu hôm nay là CN (0) thì lùi 6 ngày, nếu T2 (1) thì lùi 0 ngày, T3 (2) lùi 1 ngày...
    const daysFromMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
    
    const currentWeekMonday = new Date(today);
    currentWeekMonday.setDate(today.getDate() - daysFromMonday);
    currentWeekMonday.setHours(0, 0, 0, 0);
    
    // Thêm weekOffset * 7 ngày để di chuyển giữa các tuần
    const targetMonday = new Date(currentWeekMonday);
    targetMonday.setDate(currentWeekMonday.getDate() + (weekOffset * 7));
    
    const targetSunday = new Date(targetMonday);
    targetSunday.setDate(targetMonday.getDate() + 6);
    
    currentPlanWeekStart = targetMonday;
    
    const weekRangeEl = document.getElementById('plan-week-range');
    if (weekRangeEl) {
        weekRangeEl.textContent = `${formatDateDisplay(targetMonday)} - ${formatDateDisplay(targetSunday)}`;
    }
    
    // Cập nhật label tuần nếu không phải tuần kế tiếp
    updateWeekOffsetLabel();
}

function prevWeek() {
    weekOffset--;
    calculateNextWeek();
    loadKeHoach();
}

function nextWeek() {
    weekOffset++;
    calculateNextWeek();
    loadKeHoach();
}

function updateWeekOffsetLabel() {
    const weekOffsetLabel = document.getElementById('week-offset-label');
    if (!weekOffsetLabel) return;
    
    if (weekOffset === 0) {
        weekOffsetLabel.textContent = "Tuần hiện tại";
        weekOffsetLabel.classList.remove('hidden');
    } else if (weekOffset === 1) {
        weekOffsetLabel.textContent = "Tuần kế tiếp";
        weekOffsetLabel.classList.remove('hidden');
    } else if (weekOffset > 1) {
        weekOffsetLabel.textContent = `Tuần kế tiếp + ${weekOffset - 1}`;
        weekOffsetLabel.classList.remove('hidden');
    } else if (weekOffset < 0) {
        weekOffsetLabel.textContent = `Tuần trước ${Math.abs(weekOffset)}`;
        weekOffsetLabel.classList.remove('hidden');
    }
}

function loadMovies() {
    const planListing = document.getElementById('plan-listing');
    const baseUrl = planListing.dataset.url || '';
    return fetch(`${baseUrl}/api/phim/`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                moviesData = data.data || [];
                console.log('Loaded movies:', moviesData.length);
            } else {
                console.error('Failed to load movies:', data.message);
                moviesData = [];
            }
            return moviesData;
        })
        .catch(error => {
            console.error('Error loading movies:', error);
            moviesData = [];
            return [];
        });
}

function loadRooms() {
    const planListing = document.getElementById('plan-listing');
    const baseUrl = planListing?.dataset?.url || '';
    
    fetch(`${baseUrl}/api/phong-chieu`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                roomsData = data.data || [];
                console.log('Loaded rooms:', roomsData.length);
            } else {
                console.error('Failed to load rooms:', data.message);
                roomsData = [];
            }
        })
        .catch(error => {
            console.error('Error loading rooms:', error);
            roomsData = [];
        });
}

function loadKeHoach() {
    const planListing = document.getElementById('plan-listing');
    if (!planListing || !currentPlanWeekStart) return;

    const baseUrl = planListing.dataset.url || '';
    const weekEnd = new Date(currentPlanWeekStart);
    weekEnd.setDate(currentPlanWeekStart.getDate() + 6);
    
    const batDau = formatDate(currentPlanWeekStart);
    const ketThuc = formatDate(weekEnd);

    // Hiển thị spinner
    try {
        Spinner.show({ target: planListing, text: 'Đang tải kế hoạch...' });
    } catch (e) {
        console.log('Spinner not available');
        planListing.innerHTML = '<div class="text-center py-8">Đang tải...</div>';
    }

    fetch(`${baseUrl}/api/ke-hoach-suat-chieu?batdau=${batDau}&ketthuc=${ketThuc}`)
        .then(res => res.json())
        .then(data => {
            try {
                Spinner.hide();
            } catch (e) {
                console.log('Spinner not available');
            }
            
            if (data.success) {
                // API có thể trả về array trực tiếp hoặc object với chi_tiet
                keHoachData = [];
                let chiTietList = [];
                
                if (Array.isArray(data.data)) {
                    // Trường hợp trả về array trực tiếp (backward compatible)
                    chiTietList = data.data;
                } else if (data.data && data.data.chi_tiet) {
                    // Trường hợp trả về object với chi_tiet (cấu trúc mới)
                    chiTietList = data.data.chi_tiet;
                }
                
                if (chiTietList && chiTietList.length > 0) {
                    chiTietList.forEach(chiTiet => {
                        keHoachData.push({
                            id: chiTiet.id,
                            id_kehoach: chiTiet.id_kehoach,
                            id_phim: chiTiet.id_phim,
                            ten_phim: chiTiet.phim?.ten_phim || chiTiet.phim?.ten || 'Không rõ',
                            id_phong_chieu: chiTiet.id_phongchieu,
                            ten_phong: chiTiet.phong_chieu?.ten || 'Không rõ',
                            gio_bat_dau: chiTiet.batdau.substring(11, 16),
                            gio_ket_thuc: chiTiet.ketthuc.substring(11, 16),
                            ngay_chieu: chiTiet.batdau.substring(0, 10),
                            ghi_chu: chiTiet.ghi_chu || '',
                            tinh_trang: chiTiet.tinh_trang || 0,
                            // Giữ lại reference đến phim để có thể lấy poster
                            phim: chiTiet.phim,
                            phong_chieu: chiTiet.phong_chieu
                        });
                    });
                }
                
                renderKeHoach();
                console.log('Loaded plans:', keHoachData.length);
            } else {
                console.error('Failed to load plans:', data.message);
                keHoachData = [];
                renderKeHoach();
            }
        })
        .catch(error => {
            try {
                Spinner.hide();
            } catch (e) {
                console.log('Spinner not available');
            }
            console.error('Error loading plans:', error);
            keHoachData = [];
            renderKeHoach();
        });
}
function renderKeHoach() {
    const emptyState = document.getElementById('empty-state');
    const planContent = document.getElementById('plan-content');
    const planStatusBadge = document.getElementById('plan-status-badge');
    const totalShowtimesBadge = document.getElementById('total-showtimes-badge');
    const showtimesByDay = document.getElementById('showtimes-by-day');
    
    if (!emptyState || !planContent) return;

    // Cập nhật status badge và thông tin trạng thái
    if (planStatusBadge) {
        if (keHoachData.length === 0) {
            planStatusBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-200 text-gray-700';
            planStatusBadge.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Chưa có kế hoạch
            `;
        } else {
            // Đếm số suất chiếu theo trạng thái
            const approvedCount = keHoachData.filter(p => p.tinh_trang == 1).length;
            const pendingCount = keHoachData.filter(p => p.tinh_trang == 0).length;
            const rejectedCount = keHoachData.filter(p => p.tinh_trang == 2).length;
            
            if (approvedCount > 0 && approvedCount === keHoachData.length) {
                // Tất cả đã duyệt
                planStatusBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800';
                planStatusBadge.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>Đã duyệt`;
                
                // Hiển thị nút áp dụng kế hoạch
                const btnApplyPlan = document.getElementById('btn-apply-plan');
                if (btnApplyPlan) {
                    btnApplyPlan.classList.remove('hidden');
                }
            } else if (pendingCount > 0) {
                // Có suất chiếu chờ duyệt
                planStatusBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800';
                planStatusBadge.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Chờ duyệt`;
                
                // Ẩn nút áp dụng nếu chưa duyệt hết
                const btnApplyPlan = document.getElementById('btn-apply-plan');
                if (btnApplyPlan) {
                    btnApplyPlan.classList.add('hidden');
                }
            } else {
                planStatusBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800';
                planStatusBadge.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Chưa hoàn thành`;
            }
            
            // Cập nhật thông tin trạng thái
            const planStatusInfo = document.getElementById('plan-status-info');
            if (planStatusInfo) {
                const statusParts = [];
                if (approvedCount > 0) statusParts.push(`${approvedCount} đã duyệt`);
                if (pendingCount > 0) statusParts.push(`${pendingCount} chờ duyệt`);
                if (rejectedCount > 0) statusParts.push(`${rejectedCount} từ chối`);
                planStatusInfo.textContent = statusParts.join(' · ') || '';
            }
        }
    }

    // Cập nhật badge tổng số suất chiếu NGAY khi render (cho cả empty state)
    if (totalShowtimesBadge) {
        totalShowtimesBadge.textContent = `${keHoachData.length} suất chiếu`;
    }

    if (keHoachData.length === 0) {
        emptyState.classList.remove('hidden');
        planContent.classList.add('hidden');
        
        // ⚠️ LOGIC ĐÚNG:
        // weekOffset > 0 → Các tuần tương lai → CHO PHÉP TẠO
        // weekOffset <= 0  → Tuần hiện tại/trước → CHỈ XEM
        if (weekOffset <= 0) {
            // Tuần hiện tại hoặc tuần trước: CHỈ XEM, KHÔNG TẠO
            emptyState.innerHTML = `
                <div class="text-center py-16">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Chưa có kế hoạch cho tuần này</h3>
                    <p class="text-gray-500 mb-4">Không thể tạo kế hoạch cho ${weekOffset === 0 ? 'tuần hiện tại' : 'tuần đã qua'}</p>
                    <button disabled class="px-6 py-3 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 002 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                        Chỉ xem (${weekOffset === 0 ? 'Tuần hiện tại' : 'Tuần trước'})
                    </button>
                </div>
            `;
        } else {
            // Các tuần tương lai: CHO PHÉP TẠO
            emptyState.innerHTML = `
                <div class="text-center py-16">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Chưa có kế hoạch cho tuần này</h3>
                    <p class="text-gray-500 mb-4">Tạo kế hoạch mới để bắt đầu lên lịch chiếu phim</p>
                    <button id="btn-create-new-plan" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Tạo kế hoạch tuần mới
                    </button>
                </div>
            `;
            
            // Gắn event listener cho nút vừa tạo
             const btnCreateNewPlan = document.getElementById('btn-create-new-plan');
            if (btnCreateNewPlan) {
                    btnCreateNewPlan.addEventListener('click', createEmptyWeekView);
            }
        }
        return;
    }
    
    // Hiển thị plan content khi có dữ liệu
    emptyState.classList.add('hidden');
    planContent.classList.remove('hidden');
    
    // Cập nhật badge tổng số suất chiếu (cả khi có data)
    if (totalShowtimesBadge) {
        totalShowtimesBadge.textContent = `${keHoachData.length} suất chiếu`;
    }

    // Nhóm theo ngày
    const groupedByDate = {};
    keHoachData.forEach(plan => {
        const dateKey = plan.ngay_chieu;
        if (!groupedByDate[dateKey]) {
            groupedByDate[dateKey] = [];
        }
        groupedByDate[dateKey].push(plan);
    });

    // Lấy urlMinio từ DOM để xây đường dẫn ảnh poster
    const planListing = document.getElementById('plan-listing');
    const urlMinio = planListing?.dataset?.urlminio || '';
    
    // Render 7 day cards from currentPlanWeekStart
    let html = '';
    for (let i = 0; i < 7; i++) {
        const date = new Date(currentPlanWeekStart);
        date.setDate(currentPlanWeekStart.getDate() + i);
        const dateStr = formatDate(date);
        const dayName = getDayName(date);
        const plans = groupedByDate[dateStr] || [];
        if (plans.length > 0) {
            html += `
                <div class="border rounded-lg p-4 bg-gradient-to-r from-gray-50 to-blue-50 mb-4">
                    <div class="flex space-x-2  justify-between align-items-center mb-3">
                    <h3 class="font-bold text-lg text-gray-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        ${dayName}, ${formatDateDisplay(date)}
                    </h3>
                                <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md open-plan-for-day" data-date="${dateStr}">
                                    Thêm suất cho ngày
                                </button>
                            </div>
                
                    <div class="space-y-2" id="plan-day-${dateStr}">
            `;
        }
        else {
            html += `
                <div class="border rounded-lg p-4 bg-gradient-to-r from-gray-50 to-blue-50">
                    
                    <div class="flex space-x-2  justify-between align-items-center mb-3">
                    <h3 class="font-bold text-lg mb-3 text-gray-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        ${dayName}, ${formatDateDisplay(date)}
                    </h3>
                                <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md open-plan-for-day" data-date="${dateStr}">
                                    Thêm suất cho ngày
                                </button>
                            </div>
                    <div class="space-y-2">
                        <div id="plan-day-${dateStr}" class="p-4 bg-white rounded-md border border-gray-200 min-h-[80px] flex flex-col justify-center items-center">
                            <div class="text-gray-500 mb-3">Chưa có suất chiếu</div>
                        </div>
                    </div>
                </div>
            `;
        }
         

        plans.forEach(plan => {
            console.log('Rendering plan:', plan);
            // Lấy poster từ cấu trúc dữ liệu API
            // ⚠️ LOGIC ĐÚNG: Chỉ cho phép xóa nếu weekOffset > 0 (các tuần tương lai) VÀ chưa duyệt (tinh_trang != 1)
            const canDelete = weekOffset > 0 && plan.tinh_trang != 1;
            
            // Xác định trạng thái
            let statusBadge = '';
            if (plan.tinh_trang == 0) {
                statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Chờ duyệt</span>';
            } else if (plan.tinh_trang == 1) {
                statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Đã duyệt</span>';
            } else if (plan.tinh_trang == 2) {
                statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Từ chối</span>';
            }
            
            html += `
                <div class="flex items-center p-3 bg-white rounded-md border border-gray-200 hover:shadow-md transition">
                    <img src="${urlMinio}/${plan.phim.poster_url}" alt="${plan.ten_phim}" class="w-12 h-16 object-cover rounded mr-3" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27100%27 height=%27100%27%3E%3Crect fill=%27%23e2e8f0%27 width=%27100%27 height=%27100%27/%3E%3C/text%3E%3C/svg%3E'">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h4 class="font-medium text-gray-900">${plan.ten_phim}</h4>
                            ${statusBadge}
                        </div>
                        <p class="text-sm text-gray-600">
                            <span class="font-medium text-blue-600">${plan.ten_phong}</span> · 
                            ${plan.gio_bat_dau}
                        </p>
                    </div>
                    ${canDelete ? `
                        <button class="btn-delete-plan text-red-600 hover:text-red-700 p-2" data-plan-id="${plan.id}" title="Xóa suất chiếu">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    ` : `
                        <div class="p-2 text-gray-400" title="${plan.tinh_trang == 1 ? 'Không thể xóa suất chiếu đã duyệt' : 'Không thể xóa suất chiếu của tuần hiện tại hoặc đã qua'}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    `}
                </div>
            `;
        });

        html += `</div></div>`;
    }

    if (showtimesByDay) {
        showtimesByDay.innerHTML = html;
    }
    
    // Attach per-day "Thêm suất cho ngày" handlers
    if (showtimesByDay) {
        showtimesByDay.querySelectorAll('.open-plan-for-day').forEach(btn => {
            // remove any previous listener to avoid duplicates
            btn.removeEventListener('click', openPlanModalForDate);
            btn.addEventListener('click', (e) => {
                const date = btn.getAttribute('data-date');
                if (!date) return;
                openPlanModalForDate(date);
            });
        });
    }

    // Re-attach event listeners - XÓA setTimeout để event gắn ngay lập tức
    const btnAddShowtimeToPlan = document.getElementById('btn-add-showtime-to-plan');
    if (btnAddShowtimeToPlan && !btnAddShowtimeToPlan.hasAttribute('data-listener')) {
        // ⚠️ LOGIC ĐÚNG:
        // weekOffset > 0 → Các tuần tương lai → CHO PHÉP THÊM
        // weekOffset <= 0  → Tuần hiện tại/trước → CHỈ XEM
        if (weekOffset <= 0) {
            // Tuần hiện tại hoặc tuần trước: CHỈ XEM, KHÔNG THÊM
            btnAddShowtimeToPlan.disabled = true;
            btnAddShowtimeToPlan.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            btnAddShowtimeToPlan.classList.add('bg-gray-400', 'cursor-not-allowed', 'opacity-60');
            btnAddShowtimeToPlan.title = 'Không thể thêm suất chiếu cho tuần hiện tại hoặc đã qua';
            
            // Thay đổi icon và text
            btnAddShowtimeToPlan.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 002 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                </svg>
                Chỉ xem
            `;
        } else {
            // Tuần kế tiếp trở đi: CHO PHÉP THÊM
            btnAddShowtimeToPlan.disabled = false;
            btnAddShowtimeToPlan.classList.remove('bg-gray-400', 'cursor-not-allowed', 'opacity-60');
            btnAddShowtimeToPlan.classList.add('bg-blue-600', 'hover:bg-blue-700');
            btnAddShowtimeToPlan.title = 'Thêm suất chiếu mới vào kế hoạch';
            
            btnAddShowtimeToPlan.innerHTML = `
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M15.232 5.232a2.5 2.5 0 00-3.535 0l-6.25 6.25a1 1 0 00-.263.465l-1 3.5a1 1 0 001.263 1.263l3.5-1a1 1 0 00.465-.263l6.25-6.25a2.5 2.5 0 000-3.535zm-2.121 1.414l2.121 2.121-6.25 6.25-2.121-2.121 6.25-6.25z" clip-rule="evenodd" />
              </svg>
              Chỉnh sửa kế hoạch
            `;
            
            btnAddShowtimeToPlan.addEventListener('click', openPlanModal);
        }
        btnAddShowtimeToPlan.setAttribute('data-listener', 'true');
    }
    
    // ✅ Event delegation HIỆU QUẢ: Gắn 1 listener duy nhất trên container cha
    if (showtimesByDay) {
        // Xóa listener cũ nếu có (tránh duplicate)
        const oldListener = showtimesByDay._deleteListener;
        if (oldListener) {
            showtimesByDay.removeEventListener('click', oldListener);
        }
        
        // Tạo listener mới
        const deleteListener = function(e) {
            const deleteBtn = e.target.closest('.btn-delete-plan');
            if (deleteBtn) {
                e.preventDefault();
                e.stopPropagation();
                const planId = deleteBtn.getAttribute('data-plan-id');
                console.log('🗑️ Delete button clicked, plan ID:', planId);
                deletePlan(planId);
            }
        };
        
        // Gắn listener mới
        showtimesByDay.addEventListener('click', deleteListener);
        // Lưu reference để xóa sau này
        showtimesByDay._deleteListener = deleteListener;
    }
}

function openPlanModal() {
    const planModal = document.getElementById('plan-modal');

    if (planModal) {
        planModal.classList.remove('hidden');
    }

    // Reset counter và danh sách suất
    showtimeCounter = 1;

    // Render day selector và chọn Thứ 2 mặc định
    renderDaySelector();
}

function closePlanModal() {
    const planModal = document.getElementById('plan-modal');
    if (planModal) {
        planModal.classList.add('hidden');
    }
}

function deletePlan(idKeHoachChiTiet) {
    console.log('🗑️ [deletePlan] Called with ID:', idKeHoachChiTiet);
    
    // Show confirmation modal
    const confirmModal = document.getElementById('plan-confirm-modal');
    if (!confirmModal) {
        console.error('❌ Modal #plan-confirm-modal not found!');
        return;
    }

    // Show modal
    confirmModal.classList.remove('hidden');
    console.log('✅ Modal shown');

    // Get buttons (they already exist in HTML)
    const btnCancel = document.getElementById('btn-cancel-plan-delete');
    const btnConfirm = document.getElementById('btn-confirm-plan-delete');
    
    if (!btnCancel || !btnConfirm) {
        console.error('❌ Buttons not found!', { btnCancel, btnConfirm });
        return;
    }

    // Remove old event listeners by cloning (prevents duplicate listeners)
    const newBtnCancel = btnCancel.cloneNode(true);
    const newBtnConfirm = btnConfirm.cloneNode(true);
    btnCancel.parentNode.replaceChild(newBtnCancel, btnCancel);
    btnConfirm.parentNode.replaceChild(newBtnConfirm, btnConfirm);

    // Handle cancel
    newBtnCancel.addEventListener('click', () => {
        console.log('🚫 Delete cancelled');
        confirmModal.classList.add('hidden');
    });

    // Handle confirm delete
    newBtnConfirm.addEventListener('click', async () => {
        console.log('✅ Delete confirmed, calling API...');
        confirmModal.classList.add('hidden');
        
        try {
            Spinner.show({ text: 'Đang xóa...' });
        } catch (e) {
            console.log('Spinner not available');
        }

        try {
            const planListing = document.getElementById('plan-listing');
            const baseUrl = planListing?.dataset?.url || '';
            const apiUrl = `${baseUrl}/api/ke-hoach-suat-chieu/${idKeHoachChiTiet}`;
            
            console.log('🌐 Calling DELETE API:', apiUrl);
            
            const response = await fetch(apiUrl, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            console.log('📡 API Response status:', response.status);
            const result = await response.json();
            console.log('📦 API Result:', result);

            if (result.success) {
                showSuccess('Đã xóa suất chiếu khỏi kế hoạch');
                // Reload the plan to reflect changes
                await loadKeHoach();
            } else {
                showError(result.message || 'Có lỗi xảy ra khi xóa suất chiếu');
            }
        } catch (error) {
            console.error('❌ Error deleting showtime from plan:', error);
            showError('Không thể kết nối đến máy chủ: ' + error.message);
        } finally {
            try {
                Spinner.hide();
            } catch (e) {
                console.log('Spinner not available');
            }
        }
    });
}

function createShowtimeItem(index) {
    return `
        <div class="showtime-item border rounded-lg p-4 bg-gray-50" data-index="${index}" data-id="" data-status="0">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-semibold text-gray-900 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z" />
                    </svg>
                    Suất chiếu #<span class="showtime-number">${index + 1}</span>
                    <span class="showtime-status-badge ml-2 text-xs px-2 py-0.5 rounded-full hidden" data-index="${index}"></span>
                </h3>
                <button type="button" class="btn-remove-showtime text-red-600 hover:text-red-700 ${index === 0 ? 'hidden' : ''}" data-index="${index}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Chọn phim -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Phim <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            class="plan-movie-search w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="Tìm phim..."
                            autocomplete="off"
                            data-index="${index}"
                        >
                        <input type="hidden" class="plan-selected-movie-id" data-index="${index}">
                        <div class="plan-movie-results absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto hidden" data-index="${index}"></div>
                    </div>
                    <div class="plan-selected-movie-info mt-2 hidden" data-index="${index}">
                        <div class="flex items-center p-2 bg-blue-50 rounded-md border border-blue-200">
                            <img class="plan-movie-poster w-8 h-10 object-cover rounded mr-2" src="" alt="">
                            <div>
                                <h4 class="plan-movie-title text-xs font-medium text-gray-900"></h4>
                                <p class="plan-movie-duration text-xs text-gray-600"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chọn phòng -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Phòng chiếu <span class="text-red-500">*</span>
                    </label>
                    <select class="plan-room-select w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" data-index="${index}">
                        <option value="">-- Chọn phòng --</option>
                        ${roomsData.map(room => `<option value="${room.id}">${room.ten}</option>`).join('')}
                    </select>
                </div>

                <!-- Chọn giờ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Giờ bắt đầu <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        class="plan-start-time w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                        placeholder="HH:mm"
                        data-index="${index}" 
                        readonly
                    >
                    <p class="text-xs text-gray-500 mt-1">
                        Kết thúc: <span class="plan-end-time text-blue-600 font-medium" data-index="${index}">--:--</span>
                    </p>
                </div>
            </div>
            
            <!-- Khung giờ gợi ý -->
            <div class="mt-4 plan-suggested-times-container hidden" data-index="${index}">
                <label class="block text-sm font-medium text-gray-700 mb-2">Khung giờ gợi ý</label>
                <div class="plan-suggested-times grid grid-cols-6 gap-2" data-index="${index}">
                    <!-- Thời gian gợi ý sẽ được render ở đây -->
                </div>
            </div>
        </div>
    `;
}

function initializeShowtimeItem(index) {
    // Khởi tạo movie search
    const movieSearch = document.querySelector(`.plan-movie-search[data-index="${index}"]`);
    if (movieSearch) {
        movieSearch.addEventListener('input', (e) => handleMovieSearch(e, index));
    }

    // Khởi tạo room select - XÓA TẤT CẢ CACHE khi thay đổi phòng
    const roomSelect = document.querySelector(`.plan-room-select[data-index="${index}"]`);
    if (roomSelect) {
        roomSelect.addEventListener('change', () => {
            // Xóa TOÀN BỘ cache để đảm bảo load lại với dữ liệu mới nhất
            console.log('✨ [Room Changed] Cleared ALL cache, will reload with latest modal data');
            
            // Load suggested times với dữ liệu mới
            loadSuggestedTimesForPlan(index);
        });
    }

    // Khởi tạo start time input -> cập nhật thời gian kết thúc khi thay đổi
    const startInput = document.querySelector(`.plan-start-time[data-index="${index}"]`);
    if (startInput) {
        // Nếu có time picker (flatpickr) sẽ trigger 'change' event; bắt cả 'input' và 'change'
        startInput.addEventListener('change', () => calculateEndTime(index));
        startInput.addEventListener('input', () => calculateEndTime(index));
    }

    // Khởi tạo nút xóa
    const removeBtn = document.querySelector(`.btn-remove-showtime[data-index="${index}"]`);
    if (removeBtn) {
        removeBtn.addEventListener('click', () => removeShowtime(index));
    }
}

function addAnotherShowtime() {
    const showtimesList = document.getElementById('showtimes-list');
    if (!showtimesList) return;

    // Kiểm tra tất cả suất hiện tại đã chọn giờ bắt đầu chưa
    const showtimeItems = document.querySelectorAll('.showtime-item');
    for (let item of showtimeItems) {
        const index = item.dataset.index;
        const startTimeInput = document.querySelector(`.plan-start-time[data-index="${index}"]`);
        
        if (!startTimeInput || !startTimeInput.value) {
            showError('Vui lòng chọn giờ bắt đầu cho tất cả suất chiếu trước khi thêm suất mới');
            return;
        }
    }

    const newIndex = showtimeCounter++;
    const newItem = createShowtimeItem(newIndex);
    showtimesList.insertAdjacentHTML('beforeend', newItem);
    
    initializeShowtimeItem(newIndex);
    updateTotalShowtimesCount();
    updateShowtimeNumbers();
}

function removeShowtime(index) {
    const showtimeItem = document.querySelector(`.showtime-item[data-index="${index}"]`);
    if (showtimeItem) {
        showtimeItem.remove();
        updateTotalShowtimesCount();
        updateShowtimeNumbers();
    }
}

function updateShowtimeNumbers() {
    const showtimeItems = document.querySelectorAll('.showtime-item');
    showtimeItems.forEach((item, idx) => {
        const numberSpan = item.querySelector('.showtime-number');
        if (numberSpan) {
            numberSpan.textContent = idx + 1;
        }
        
        // Hiện/ẩn nút xóa (không cho xóa nếu chỉ còn 1)
        const removeBtn = item.querySelector('.btn-remove-showtime');
        if (removeBtn) {
            if (showtimeItems.length === 1) {
                removeBtn.classList.add('hidden');
            } else {
                removeBtn.classList.remove('hidden');
            }
        }
    });
}

function updateTotalShowtimesCount() {
    const totalCount = document.getElementById('total-showtimes-count');
    const count = document.querySelectorAll('.showtime-item').length;
    if (totalCount) {
        totalCount.textContent = count;
    }
}

function renderDaySelector() {
    const daySelector = document.getElementById('plan-day-selector');
    if (!daySelector || !currentPlanWeekStart) return;

    daySelector.innerHTML = '';
    const days = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
    
    for (let i = 0; i < 7; i++) {
        const date = new Date(currentPlanWeekStart);
        date.setDate(currentPlanWeekStart.getDate() + i);
        const dateStr = formatDate(date);
        
        const dayBtn = document.createElement('button');
        dayBtn.type = 'button';
        dayBtn.className = `plan-day-btn px-3 py-2 border rounded-md text-sm hover:bg-gray-100 transition ${i === 0 ? 'bg-green-600 text-white border-green-600' : ''}`;
        dayBtn.dataset.date = dateStr;
        dayBtn.innerHTML = `
            <div class="font-medium">${days[i]}</div>
            <div class="text-xs ${i === 0 ? 'text-green-100' : 'text-gray-500'}">${date.getDate()}/${date.getMonth() + 1}</div>
        `;
        
        dayBtn.addEventListener('click', function() {
            document.querySelectorAll('.plan-day-btn').forEach(btn => {
                btn.classList.remove('bg-green-600', 'text-white', 'border-green-600');
                btn.querySelector('.text-xs').classList.remove('text-green-100');
                btn.querySelector('.text-xs').classList.add('text-gray-500');
            });
            
            this.classList.add('bg-green-600', 'text-white', 'border-green-600');
            this.querySelector('.text-xs').classList.remove('text-gray-500');
            this.querySelector('.text-xs').classList.add('text-green-100');
            
            // Lưu ngày đã chọn và tải danh sách suất chiếu
            currentSelectedDate = dateStr;
            loadShowtimesByDate(dateStr);
        });
        
        daySelector.appendChild(dayBtn);
    }
    
    // Đặt ngày mặc định là thứ 2
    if (currentPlanWeekStart) {
        currentSelectedDate = formatDate(currentPlanWeekStart);
        loadShowtimesByDate(currentSelectedDate);
    }
}

// Hàm tải danh sách suất chiếu theo ngày
function loadShowtimesByDate(dateStr) {
    console.log('loadShowtimesByDate', dateStr);
    // Lấy danh sách suất chiếu đã có cho ngày này từ kế hoạch
    const showtimesForDate = keHoachData.filter(plan => plan.ngay_chieu === dateStr);
    const showtimesList = document.getElementById('showtimes-list');
    
    // Reset danh sách
    showtimeCounter = 1;
    if (showtimesList) {
        if (showtimesForDate.length === 0) {
            // Nếu không có suất chiếu nào, hiện một form trống
            showtimesList.innerHTML = createShowtimeItem(0);
            initializeShowtimeItem(0);
        } else {
            // Nếu có suất chiếu, hiện tất cả cho ngày đó
            showtimesList.innerHTML = '';
            showtimesForDate.forEach((showtime, index) => {
                showtimesList.insertAdjacentHTML('beforeend', createShowtimeItem(index));
                initializeShowtimeItem(index);
                
                // Lưu ID và trạng thái của suất chiếu cũ
                const showtimeItem = document.querySelector(`.showtime-item[data-index="${index}"]`);
                if (showtimeItem && showtime.id) {
                    showtimeItem.setAttribute('data-id', showtime.id);
                    showtimeItem.setAttribute('data-status', showtime.tinh_trang || 0);
                }
                
                // Điền thông tin suất chiếu vào form
                setTimeout(() => {
                    // Đảm bảo moviesData đã được load trước khi selectMovie
                    if (moviesData && moviesData.length > 0) {
                        selectMovie(showtime.id_phim, index);
                    } else {
                        // Nếu chưa load, thử load lại và sau đó selectMovie
                        loadMovies().then(() => {
                            selectMovie(showtime.id_phim, index);
                        });
                    }
                    
                    const roomSelect = document.querySelector(`.plan-room-select[data-index="${index}"]`);
                    if (roomSelect) roomSelect.value = showtime.id_phong_chieu;
                    
                    const startTimeInput = document.querySelector(`.plan-start-time[data-index="${index}"]`);
                    if (startTimeInput) {
                        // showtime.gio_bat_dau đã là format "HH:MM" rồi, nhưng có thể có thêm giây
                        const timeValue = showtime.gio_bat_dau && showtime.gio_bat_dau.length >= 5 
                            ? showtime.gio_bat_dau.substring(0, 5) 
                            : showtime.gio_bat_dau;
                        startTimeInput.value = timeValue;
                        // Trigger change event để cập nhật end time
                        startTimeInput.dispatchEvent(new Event('change', { bubbles: true }));
                        calculateEndTime(index);
                    }
                    
                    const noteInput = document.querySelector(`.plan-note[data-index="${index}"]`);
                    if (noteInput && showtime.ghi_chu) noteInput.value = showtime.ghi_chu;
                    
                    // Hiển thị badge trạng thái và disable input nếu đã duyệt
                    const tinhTrang = showtime.tinh_trang || 0;
                    const statusBadge = document.querySelector(`.showtime-status-badge[data-index="${index}"]`);
                    const movieSearchInput = document.querySelector(`.plan-movie-search[data-index="${index}"]`);
                    const removeBtn = document.querySelector(`.btn-remove-showtime[data-index="${index}"]`);
                    
                    if (tinhTrang == 1) {
                        // Đã duyệt - disable tất cả input
                        if (statusBadge) {
                            statusBadge.textContent = 'Đã duyệt';
                            statusBadge.className = 'showtime-status-badge ml-2 text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800';
                            statusBadge.classList.remove('hidden');
                        }
                        if (movieSearchInput) movieSearchInput.disabled = true;
                        if (roomSelect) roomSelect.disabled = true;
                        if (startTimeInput) startTimeInput.disabled = true;
                        if (removeBtn) removeBtn.classList.add('hidden');
                        
                        // Thêm visual cue
                        if (showtimeItem) {
                            showtimeItem.classList.remove('bg-gray-50');
                            showtimeItem.classList.add('bg-green-50', 'border-green-200');
                        }
                    } else if (tinhTrang == 2) {
                        // Từ chối
                        if (statusBadge) {
                            statusBadge.textContent = 'Từ chối';
                            statusBadge.className = 'showtime-status-badge ml-2 text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-800';
                            statusBadge.classList.remove('hidden');
                        }
                        // Vẫn cho phép chỉnh sửa suất bị từ chối, hiển thị khung giờ gợi ý
                        loadSuggestedTimesForPlan(index);
                    } else {
                        // Chờ duyệt - cho phép chỉnh sửa, hiển thị khung giờ gợi ý
                        if (statusBadge) {
                            statusBadge.textContent = 'Chờ duyệt';
                            statusBadge.className = 'showtime-status-badge ml-2 text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800';
                            statusBadge.classList.remove('hidden');
                        }
                        loadSuggestedTimesForPlan(index);
                    }
                    
                    showtimeCounter++;
                }, 200);
            });
        }
        updateTotalShowtimesCount();
        updateShowtimeNumbers();
    }
    
    // Hiển thị ngày đã chọn trong header modal
    const selectedDate = new Date(dateStr);
    const dayNames = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
    const dayName = dayNames[selectedDate.getDay()];
    
    const modalHeader = document.querySelector('#plan-modal h2');
    if (modalHeader) {
        modalHeader.innerHTML = `Thêm suất chiếu cho <span class="text-blue-100">${dayName}, ${formatDateDisplay(selectedDate)}</span>`;
    }
}

/**
 * Render 7 empty day containers for the current plan week in the main plan view.
 * Each day has a button "Thêm suất cho ngày" that opens the modal for that single date.
 */
function createEmptyWeekView() {
    if (!currentPlanWeekStart) calculateNextWeek();
    const parent = document.getElementById('showtimes-by-day');
    if (!parent) return;

    // Build 7 days from currentPlanWeekStart
    let html = '';
    for (let i = 0; i < 7; i++) {
        const date = new Date(currentPlanWeekStart);
        date.setDate(currentPlanWeekStart.getDate() + i);
        const dateStr = formatDate(date);
        const dayName = getDayName(date);

        html += `
            <div class="border rounded-lg p-4 bg-gradient-to-r from-gray-50 to-blue-50">
                <h3 class="font-bold text-lg mb-3 text-gray-800 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                    </svg>
                    ${dayName}, ${formatDateDisplay(date)}
                </h3>
                <div class="space-y-2">
                    <div id="plan-day-${dateStr}" class="p-4 bg-white rounded-md border border-gray-200 min-h-[80px] flex flex-col justify-center items-center">
                        <div class="text-gray-500 mb-3">Chưa có suất chiếu</div>
                        <div class="flex space-x-2">
                            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md open-plan-for-day" data-date="${dateStr}">
                                Thêm suất cho ngày
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Show plan content area and inject html
    const emptyState = document.getElementById('empty-state');
    const planContent = document.getElementById('plan-content');
    if (emptyState) emptyState.classList.add('hidden');
    if (planContent) planContent.classList.remove('hidden');

    parent.innerHTML = html;

    // Attach click handlers for all "Thêm suất cho ngày" buttons
    parent.querySelectorAll('.open-plan-for-day').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const date = btn.getAttribute('data-date');
            if (!date) return;
            openPlanModalForDate(date);
        });
    });
}

/**
 * Open plan modal and preselect a specific date.
 */
function openPlanModalForDate(dateStr) {
    currentSelectedDate = dateStr;
    // Ensure modal opens and day selector is rendered so modal inputs are populated
    openPlanModal();
    // Đảm bảo moviesData đã được load trước khi load showtimes
    if (!moviesData || moviesData.length === 0) {
        loadMovies().then(() => {
            // Force loading showtimes for that date into the modal
            setTimeout(() => {
                loadShowtimesByDate(dateStr);
            }, 100);
        });
    } else {
        // Force loading showtimes for that date into the modal
        setTimeout(() => {
            loadShowtimesByDate(dateStr);
        }, 50);
    }
}

/**
 * Tìm và hiển thị kết quả tìm phim trong modal kế hoạch.
 * Gọi selectMovie khi người dùng chọn phim.
 */
function handleMovieSearch(event, index) {
    const q = (event.target.value || '').trim();
    const resultsEl = document.querySelector(`.plan-movie-results[data-index="${index}"]`);
    const selectedInfo = document.querySelector(`.plan-selected-movie-info[data-index="${index}"]`);
    const hiddenId = document.querySelector(`.plan-selected-movie-id[data-index="${index}"]`);
    if (!resultsEl) return;

    if (!q) {
        resultsEl.classList.add('hidden');
        resultsEl.innerHTML = '';
        if (hiddenId) hiddenId.value = '';
        if (selectedInfo) selectedInfo.classList.add('hidden');
        return;
    }

    const matches = (moviesData || []).filter(m => {
        const title = (m.ten_phim || m.ten || '').toString().toLowerCase();
        return title.includes(q.toLowerCase());
    }).slice(0, 10);

    if (matches.length === 0) {
        resultsEl.innerHTML = `<div class="p-2 text-sm text-gray-500">Không tìm thấy phim</div>`;
        resultsEl.classList.remove('hidden');
        return;
    }

    const planListing = document.getElementById('plan-listing');
    const urlMinio = planListing?.dataset?.urlminio || '';

    resultsEl.innerHTML = matches.map(m => {
        const title = m.ten_phim || m.ten || 'Không rõ';
        const dur = m.thoi_luong ? `${m.thoi_luong} phút` : '';
        const poster = m.poster_url ? `${urlMinio}/${m.poster_url}` : '';
        return `
            <div class="px-2 py-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2" data-id="${m.id}">
                <img src="${poster}" class="w-8 h-10 object-cover rounded" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2780%27 height=%27100%27%3E%3Crect fill=%27%23e2e8f0%27 width=%2780%27 height=%27100%27/%3E%3C/text%3E%3C/svg%3E'">
                <div class="text-sm">
                    <div class="font-medium text-gray-900">${title}</div>
                    <div class="text-xs text-gray-500">${dur}</div>
                </div>
            </div>
        `;
    }).join('');

    resultsEl.classList.remove('hidden');

    // Delegate click on results (works for dynamic content)
    resultsEl.querySelectorAll('[data-id]').forEach(node => {
        node.addEventListener('click', () => {
            const id = node.getAttribute('data-id');
            selectMovie(id, index);
        });
    });
}

/**
 * Chọn phim (điền vào input ẩn và hiển thị thông tin đã chọn).
 */
function selectMovie(movieId, index) {
    const movie = (moviesData || []).find(m => String(m.id) === String(movieId));
    const resultsEl = document.querySelector(`.plan-movie-results[data-index="${index}"]`);
    const searchInput = document.querySelector(`.plan-movie-search[data-index="${index}"]`);
    const hiddenId = document.querySelector(`.plan-selected-movie-id[data-index="${index}"]`);
    const selectedInfo = document.querySelector(`.plan-selected-movie-info[data-index="${index}"]`);
    
    if (!movie) {
        console.warn(`Movie not found with ID: ${movieId} in moviesData (length: ${moviesData?.length || 0})`);
        // Nếu không tìm thấy phim, vẫn cố gắng hiển thị ID phim trong input
        if (hiddenId) hiddenId.value = movieId;
        if (searchInput) searchInput.value = `Phim ID: ${movieId}`;
        return;
    }

    const planListing = document.getElementById('plan-listing');
    const urlMinio = planListing?.dataset?.urlminio || '';

    if (hiddenId) hiddenId.value = movie.id;
    if (searchInput) searchInput.value = movie.ten_phim || movie.ten || '';
    if (selectedInfo) {
        selectedInfo.classList.remove('hidden');
        const posterEl = selectedInfo.querySelector('.plan-movie-poster');
        const titleEl = selectedInfo.querySelector('.plan-movie-title');
        const durEl = selectedInfo.querySelector('.plan-movie-duration');
        if (posterEl) posterEl.src = movie.poster_url ? `${urlMinio}/${movie.poster_url}` : '';
        if (titleEl) titleEl.textContent = movie.ten_phim || movie.ten || '';
        if (durEl) durEl.textContent = movie.thoi_luong ? `${movie.thoi_luong} phút` : '';
    }
    if (resultsEl) {
        resultsEl.classList.add('hidden');
        resultsEl.innerHTML = '';
    }

    // Trigger suggested time load if function exists
    if (typeof loadSuggestedTimesForPlan === 'function') {
        try { loadSuggestedTimesForPlan(index); } catch (e) { console.warn(e); }
    }
}

/**
 * Tải và hiển thị khung giờ gợi ý cho một suất chiếu (index).
 * - Dựa trên phim đã chọn, thời lượng phim và phòng đã chọn.
 * - Loại bỏ các khung giờ xung đột với kế hoạch hiện có (keHoachData) cho cùng phòng/ngày
 * - Loại bỏ xung đột với các suất tạm trong modal (các .showtime-item hiện tại)
 */
function loadSuggestedTimesForPlan(index) {
    try {
        const selectedMovieIdEl = document.querySelector(`.plan-selected-movie-id[data-index="${index}"]`);
        const roomSelect = document.querySelector(`.plan-room-select[data-index="${index}"]`);
        const resultsContainer = document.querySelector(`.plan-suggested-times[data-index="${index}"]`);
        const containerWrap = document.querySelector(`.plan-suggested-times-container[data-index="${index}"]`);
        const startInput = document.querySelector(`.plan-start-time[data-index="${index}"]`);
        if (!selectedMovieIdEl || !roomSelect || !resultsContainer || !containerWrap) return;

        const movieId = selectedMovieIdEl.value;
        const roomId = roomSelect.value;
        if (!movieId || !roomId || !currentSelectedDate) {
            containerWrap.classList.add('hidden');
            resultsContainer.innerHTML = '';
            return;
        }

        const movie = (moviesData || []).find(m => String(m.id) === String(movieId));
        if (!movie || !movie.thoi_luong) {
            containerWrap.classList.add('hidden');
            resultsContainer.innerHTML = '';
            return;
        }
        const durationMin = parseInt(movie.thoi_luong, 10) || 120;

        // build modal temporary showtimes payload (same shape server expects)
        const modalItems = Array.from(document.querySelectorAll('.showtime-item'));
        const suatChieuModal = modalItems.map(item => {
            const idx = item.dataset.index;
            const mid = (document.querySelector(`.plan-selected-movie-id[data-index="${idx}"]`) || {}).value;
            const st = (document.querySelector(`.plan-start-time[data-index="${idx}"]`) || {}).value;
            const endEl = document.querySelector(`.plan-end-time[data-index="${idx}"]`);
            const et = endEl ? endEl.textContent : null;
            const room = (document.querySelector(`.plan-room-select[data-index="${idx}"]`) || {}).value;
            if (!mid || !st || !room) return null;
            const batdau = `${currentSelectedDate} ${st}:00`;
            const ketthuc = (et && et !== '--:--') ? `${currentSelectedDate} ${et}:00` : null;
            return {
                id_phim: parseInt(mid, 10),
                id_phongchieu: parseInt(room, 10),
                batdau: batdau,
                ketthuc: ketthuc
            };
        }).filter(Boolean);

        // local fallback generator (existing logic)
        const timeToMinutes = (t) => {
            if (!t) return null;
            const [hh, mm] = t.split(':').map(Number);
            return hh * 60 + mm;
        };
        const minutesToTime = (m) => {
            const dayMinutes = 24 * 60;
            const mm = ((m % dayMinutes) + dayMinutes) % dayMinutes;
            const hh = Math.floor(mm / 60).toString().padStart(2, '0');
            const mins = (mm % 60).toString().padStart(2, '0');
            return `${hh}:${mins}`;
        };
        const overlaps = (s1, e1, s2, e2) => !(e1 <= s2 || s1 >= e2);

        const booked = (keHoachData || [])
            .filter(p => String(p.id_phong_chieu) === String(roomId) && String(p.ngay_chieu) === String(currentSelectedDate))
            .map(p => ({ start: timeToMinutes(p.gio_bat_dau), end: timeToMinutes(p.gio_ket_thuc) }));

        const modalBooked = suatChieuModal
            .filter(s => String(s.id_phongchieu) === String(roomId) && s.batdau)
            .map(s => {
                const smin = timeToMinutes(s.batdau.substring(11, 16));
                const emin = s.ketthuc ? timeToMinutes(s.ketthuc.substring(11, 16)) : (smin + durationMin);
                return { start: smin, end: emin };
            });

        const allBooked = booked.concat(modalBooked);

        const dayStart = 8 * 60;
        const dayEnd = 23 * 60;
        const step = 30;
        const localCandidates = [];
        for (let t = dayStart; t + durationMin <= dayEnd; t += step) {
            const s = t, e = t + durationMin;
            const conflict = allBooked.some(b => overlaps(s, e, b.start, b.end));
            if (!conflict) localCandidates.push(minutesToTime(s));
        }

        // Try server-side suggestions first (controller accepts GET and reads body)
        (async () => {
            try {
                const planListing = document.getElementById('plan-listing');
                const baseUrl = planListing?.dataset?.url || '';
                const apiUrl = `${baseUrl}/api/ke-hoach-suat-chieu/tao-khung-gio-goi-y?ngay=${encodeURIComponent(currentSelectedDate)}&id_phong_chieu=${encodeURIComponent(roomId)}&thoi_luong_phim=${encodeURIComponent(durationMin)}`;

                const resp = await fetch(apiUrl, {
                    method: 'GET',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ suat_chieu_hien_tai: suatChieuModal })
                });

                if (!resp.ok) throw new Error('Network response not ok');
                const json = await resp.json();
                const suggestions = (json && json.success && Array.isArray(json.data)) ? json.data : localCandidates;

                if (!suggestions || suggestions.length === 0) {
                    resultsContainer.innerHTML = `<div class="col-span-6 p-2 text-sm text-gray-500">Không có khung giờ gợi ý phù hợp</div>`;
                    containerWrap.classList.remove('hidden');
                    return;
                }

                resultsContainer.innerHTML = suggestions.map(t => `<button type="button" class="suggested-time-btn px-3 py-1 bg-white border rounded text-sm hover:bg-blue-50" data-time="${t}">${t}</button>`).join('');

                resultsContainer.querySelectorAll('.suggested-time-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const t = btn.getAttribute('data-time');
                        if (startInput) {
                            startInput.value = t;
                            if (typeof calculateEndTime === 'function') calculateEndTime(index);
                        }
                    });
                });

                containerWrap.classList.remove('hidden');
            } catch (err) {
                // fallback to local candidates on any error
                console.warn('Server suggestions failed, using local suggestions:', err);
                if (localCandidates.length === 0) {
                    resultsContainer.innerHTML = `<div class="col-span-6 p-2 text-sm text-gray-500">Không có khung giờ gợi ý phù hợp</div>`;
                } else {
                    resultsContainer.innerHTML = localCandidates.map(t => `<button type="button" class="suggested-time-btn px-3 py-1 bg-white border rounded text-sm hover:bg-blue-50" data-time="${t}">${t}</button>`).join('');
                    resultsContainer.querySelectorAll('.suggested-time-btn').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const tt = btn.getAttribute('data-time');
                            if (startInput) {
                                startInput.value = tt;
                                if (typeof calculateEndTime === 'function') calculateEndTime(index);
                            }
                        });
                    });
                }
                containerWrap.classList.remove('hidden');
            }
        })();
    } catch (err) {
        console.error('loadSuggestedTimesForPlan error:', err);
    }
}

/**
 * Tính và hiển thị thời gian kết thúc cho suất chiếu tại index dựa trên
 * thời lượng phim đã chọn và giờ bắt đầu hiện tại.
 * Nếu không đủ thông tin thì hiển thị '--:--'
 */
function calculateEndTime(index) {
    try {
        const startInput = document.querySelector(`.plan-start-time[data-index="${index}"]`);
        const hiddenMovieIdEl = document.querySelector(`.plan-selected-movie-id[data-index="${index}"]`);
        const endEl = document.querySelector(`.plan-end-time[data-index="${index}"]`);
        if (!endEl) return;

        // Default
        endEl.textContent = '--:--';

        if (!startInput || !startInput.value) return;
        if (!hiddenMovieIdEl || !hiddenMovieIdEl.value) return;

        const movieId = hiddenMovieIdEl.value;
        const movie = (moviesData || []).find(m => String(m.id) === String(movieId));
        if (!movie) return;

        const durationMin = parseInt(movie.thoi_luong || 0, 10);
        if (!durationMin || isNaN(durationMin) || durationMin <= 0) return;

        // helpers
        const timeToMinutes = (t) => {
            if (!t) return null;
            const parts = t.split(':').map(s => parseInt(s, 10));
            if (parts.length < 2 || parts.some(isNaN)) return null;
            return parts[0] * 60 + parts[1];
        };
        const minutesToTime = (m) => {
            const dayMinutes = 24 * 60;
            const mm = ((m % dayMinutes) + dayMinutes) % dayMinutes; // normalize
            const hh = Math.floor(mm / 60).toString().padStart(2, '0');
            const mins = (mm % 60).toString().padStart(2, '0');
            return `${hh}:${mins}`;
        };

        const startMin = timeToMinutes(startInput.value);
        if (startMin === null) return;

        const endMin = startMin + durationMin;
        endEl.textContent = minutesToTime(endMin);
    } catch (err) {
        console.error('calculateEndTime error:', err);
    }
}

// Lấy dữ liệu từ modal và gửi lên API để lưu vào ke-hoach
async function saveAllShowtimes() {
    try {
        const planListing = document.getElementById('plan-listing');
        const baseUrl = planListing?.dataset?.url || '';
        if (!baseUrl) {
            showError('Không xác định được URL API');
            return;
        }
        if (!currentSelectedDate) {
            showError('Chưa chọn ngày để lưu kế hoạch');
            return;
        }

        // Thu thập tất cả showtime-item trong modal
        const items = Array.from(document.querySelectorAll('.showtime-item'));
        if (items.length === 0) {
            showError('Chưa có suất chiếu để lưu');
            return;
        }

        const payloadSuat = [];
        for (const item of items) {
            const idx = item.dataset.index;
            const movieId = (document.querySelector(` .plan-selected-movie-id[data-index="${idx}"]`) || {}).value;
            const roomId = (document.querySelector(`.plan-room-select[data-index="${idx}"]`) || {}).value;
            const startTime = (document.querySelector(`.plan-start-time[data-index="${idx}"]`) || {}).value;
            const endTimeEl = document.querySelector(`.plan-end-time[data-index="${idx}"]`);
            const endTime = endTimeEl ? endTimeEl.textContent : null;
            const ghiChuEl = document.querySelector(`.plan-note[data-index="${idx}"]`);
            const note = ghiChuEl ? ghiChuEl.value.trim() : '';

            // Validate cơ bản
            if (!movieId) {
                showError('Vui lòng chọn phim cho tất cả suất chiếu');
                return;
            }
            if (!roomId) {
                showError('Vui lòng chọn phòng cho tất cả suất chiếu');
                return;
            }
            if (!startTime) {
                showError('Vui lòng chọn giờ bắt đầu cho tất cả suất chiếu');
                return;
            }

            // Tạo datetime string: yyyy-mm-dd HH:MM:SS (không timezone)
            const batdau = `${currentSelectedDate} ${startTime}:00`;
            // Nếu endTime không hợp lệ giữ null
            const ketthuc = (endTime && endTime !== '--:--') ? `${currentSelectedDate} ${endTime}:00` : null;

            payloadSuat.push({
                id_phim: parseInt(movieId, 10),
                id_phongchieu: parseInt(roomId, 10),
                batdau: batdau,
                ketthuc: ketthuc,
                ghi_chu: note || null,
                // nếu edit suất đã tồn tại, giữ id để backend cập nhật
                id_kehoach_chitiet: item.getAttribute('data-id') || null
            });
        }

        const payload = {
            ngay_chieu: currentSelectedDate,
            suat_chieu: payloadSuat
        };

        try { Spinner.show({ target: document.getElementById('plan-modal'), text: 'Đang lưu...' }); } catch(e){}

        const res = await fetch(`${baseUrl}/api/ke-hoach-suat-chieu`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await res.json();
        if (result && result.success) {
            showSuccess(result.message || 'Lưu kế hoạch thành công');
            // Đóng modal và reload kế hoạch tuần
            closePlanModal();
            await loadKeHoach();
        } else {
            showError(result?.message || 'Lưu kế hoạch thất bại');
            console.error('Save plan error:', result);
        }
        
    } catch (err) {
        try { Spinner.hide(); } catch(e){
             console.error('saveAllShowtimes error:', err);
            showError('Có lỗi khi lưu kế hoạch: ' + (err.message || err));
        }
       
    }
}
function showToast(message = '', type = 'success', duration = 4000) {
    let container = document.getElementById('kehoach-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'kehoach-toast-container';
        container.className = 'fixed right-4 top-4 z-50 flex flex-col items-end space-y-2';
        document.body.appendChild(container);
    }

    const typeClasses = {
        success: 'bg-green-600 text-white',
        error: 'bg-red-600 text-white',
        info: 'bg-blue-600 text-white'
    };
    const toast = document.createElement('div');
    toast.className = `px-4 py-2 rounded shadow ${typeClasses[type] || typeClasses.info} text-sm`;
    toast.style.maxWidth = '320px';
    toast.style.wordBreak = 'break-word';
    toast.textContent = message || (type === 'success' ? 'Thành công' : 'Có lỗi');

    container.appendChild(toast);

    // Fade out + remove
    setTimeout(() => {
        toast.style.transition = 'opacity 300ms, transform 300ms';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-6px)';
        setTimeout(() => {
            toast.remove();
            // remove container if empty
            if (container && container.children.length === 0) container.remove();
        }, 350);
    }, duration);

    return toast;
}

function showSuccess(message = 'Thao tác thành công', duration = 4000) {
    return showToast(message, 'success', duration);
}

function showError(message = 'Có lỗi xảy ra', duration = 6000) {
    return showToast(message, 'error', duration);
}

// Hàm sao chép kế hoạch từ tuần trước
async function copyPlanFromLastWeek() {
    if (!currentPlanWeekStart) {
        showError('Không xác định được tuần hiện tại');
        return;
    }
    
    // Tính tuần trước (lùi 7 ngày)
    const lastWeekStart = new Date(currentPlanWeekStart);
    lastWeekStart.setDate(lastWeekStart.getDate() - 7);
    const lastWeekEnd = new Date(lastWeekStart);
    lastWeekEnd.setDate(lastWeekStart.getDate() + 6);
    
    const planListing = document.getElementById('plan-listing');
    const baseUrl = planListing?.dataset?.url || '';
    
    try {
        Spinner.show({ text: 'Đang tải kế hoạch tuần trước...' });
        
        const batDau = formatDate(lastWeekStart);
        const ketThuc = formatDate(lastWeekEnd);
        
        // Lấy kế hoạch tuần trước
        const res = await fetch(`${baseUrl}/api/ke-hoach-suat-chieu?batdau=${batDau}&ketthuc=${ketThuc}`);
        const data = await res.json();
        
        // Xử lý cả 2 cấu trúc dữ liệu (array hoặc object với chi_tiet)
        let chiTietList = [];
        if (Array.isArray(data.data)) {
            chiTietList = data.data;
        } else if (data.data && data.data.chi_tiet) {
            chiTietList = data.data.chi_tiet;
        }
        
        if (!data.success || !chiTietList || chiTietList.length === 0) {
            showError('Không tìm thấy kế hoạch tuần trước để sao chép');
            Spinner.hide();
            return;
        }
        
        // Xác nhận với người dùng
        const confirmMsg = `Bạn có muốn sao chép ${chiTietList.length} suất chiếu từ tuần trước (${formatDateDisplay(lastWeekStart)} - ${formatDateDisplay(lastWeekEnd)}) vào tuần này không?`;
        if (!confirm(confirmMsg)) {
            Spinner.hide();
            return;
        }
        
        // Gọi API sao chép từ backend
        const currentWeekEnd = new Date(currentPlanWeekStart);
        currentWeekEnd.setDate(currentPlanWeekStart.getDate() + 6);
        
        const copyRes = await fetch(`${baseUrl}/api/ke-hoach-suat-chieu/sao-chep`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                batdau: formatDate(currentPlanWeekStart),
                ketthuc: formatDate(currentWeekEnd)
            })
        });
        
        const copyData = await copyRes.json();
        
        if (copyData.success) {
            showSuccess(copyData.message || `Đã sao chép ${copyData.count || chiTietList.length} suất chiếu từ tuần trước`);
            await loadKeHoach();
        } else {
            showError(copyData.message || 'Có lỗi xảy ra khi sao chép kế hoạch');
        }
        
    } catch (error) {
        console.error('Lỗi khi sao chép kế hoạch:', error);
        showError('Có lỗi xảy ra khi sao chép kế hoạch: ' + error.message);
    } finally {
        Spinner.hide();
    }
}

// Hàm áp dụng kế hoạch đã duyệt vào suất chiếu thực tế
async function applyPlanToShowtimes() {
    if (!currentPlanWeekStart) {
        showError('Không xác định được tuần hiện tại');
        return;
    }
    
    const weekEnd = new Date(currentPlanWeekStart);
    weekEnd.setDate(currentPlanWeekStart.getDate() + 6);
    
    const planListing = document.getElementById('plan-listing');
    const baseUrl = planListing?.dataset?.url || '';
    
    // Kiểm tra xem có kế hoạch đã duyệt không
    const approvedPlans = keHoachData.filter(p => p.tinh_trang == 1);
    if (approvedPlans.length === 0) {
        showError('Không có kế hoạch nào đã được duyệt để áp dụng');
        return;
    }
    
    const confirmMsg = `Bạn có chắc chắn muốn áp dụng ${approvedPlans.length} suất chiếu đã duyệt vào lịch chiếu thực tế không?`;
    if (!confirm(confirmMsg)) {
        return;
    }
    
    try {
        Spinner.show({ text: 'Đang áp dụng kế hoạch...' });
        
        const batDau = formatDate(currentPlanWeekStart);
        const ketThuc = formatDate(weekEnd);
        
        const res = await fetch(`${baseUrl}/api/ke-hoach-suat-chieu/ap-dung`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                batdau: batDau,
                ketthuc: ketThuc
            })
        });
        
        const data = await res.json();
        
        if (data.success) {
            showSuccess(`Đã áp dụng ${approvedPlans.length} suất chiếu vào lịch chiếu thực tế`);
            // Có thể reload lại dữ liệu nếu cần
        } else {
            showError(data.message || 'Có lỗi xảy ra khi áp dụng kế hoạch');
        }
    } catch (error) {
        console.error('Lỗi khi áp dụng kế hoạch:', error);
        showError('Có lỗi xảy ra khi áp dụng kế hoạch: ' + error.message);
    } finally {
        Spinner.hide();
    }
}

// Gắn event listeners cho các nút mới
function setupNewEventListeners() {
    // Nút copy từ tuần trước (trong header)
    const btnCopyFromLastWeek = document.getElementById('btn-copy-from-last-week');
    if (btnCopyFromLastWeek) {
        btnCopyFromLastWeek.addEventListener('click', copyPlanFromLastWeek);
    }
    
    // Nút copy template khi empty state
    const btnCopyTemplateEmpty = document.getElementById('btn-copy-template-empty');
    if (btnCopyTemplateEmpty) {
        btnCopyTemplateEmpty.addEventListener('click', copyPlanFromLastWeek);
    }
    
    // Nút copy template khi đã có kế hoạch
    const btnCopyTemplateExisting = document.getElementById('btn-copy-template-existing');
    if (btnCopyTemplateExisting) {
        btnCopyTemplateExisting.addEventListener('click', copyPlanFromLastWeek);
    }
    
    // Nút áp dụng kế hoạch
    const btnApplyPlan = document.getElementById('btn-apply-plan');
    if (btnApplyPlan) {
        btnApplyPlan.addEventListener('click', applyPlanToShowtimes);
    }
}

// Đảm bảo setupNewEventListeners được gọi khi tab được mở
document.addEventListener('DOMContentLoaded', function() {
    // Gắn listener cho tab button để setup khi chuyển tab
    const tabBtnKehoach = document.getElementById('tab-btn-kehoach');
    if (tabBtnKehoach) {
        tabBtnKehoach.addEventListener('click', function() {
            // Delay một chút để đảm bảo DOM đã render
            setTimeout(() => {
                setupNewEventListeners();
            }, 100);
        });
    }
});
// File: duyet-ke-hoach.js - Duyệt kế hoạch suất chiếu tuần cho quản lý chuỗi rạp

// Import Spinner
import Spinner from './util/spinner.js';

// Biến toàn cục
let keHoachData = [];
let moviesData = [];
let roomsData = [];
let currentPlanWeekStart = null;
let weekOffset = 1; // 0 = tuần hiện tại, 1 = tuần kế tiếp (mặc định)
let currentSelectedShowtime = null; // Lưu suất chiếu đang xem chi tiết
let currentRapId = null; // ID rạp đang duyệt
let thongKeData = null; // Thông tin thống kê từ API

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

function getDayName(date) {
    const days = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
    return days[date.getDay()];
}

// Khởi tạo khi chuyển sang tab Kế hoạch
document.addEventListener('DOMContentLoaded', function() {
    const tabBtnKehoach = document.getElementById('tab-btn-kehoach');
    if (tabBtnKehoach) {
        tabBtnKehoach.addEventListener('click', function() {
            setTimeout(() => initializePlanApprovalTab(), 100);
        });
    }
});

function initializePlanApprovalTab() {
    // Lấy ID rạp từ data attribute
    const planListing = document.getElementById('plan-approval-listing');
    if (planListing) {
        currentRapId = planListing.dataset.rap;
    }
    
    setupPlanApprovalEventListeners();
    calculateNextWeek();
    loadKeHoach();
}

function setupPlanApprovalEventListeners() {
    const btnPrevWeek = document.getElementById('plan-btn-prev-week');
    const btnNextWeek = document.getElementById('plan-btn-next-week');
    const btnApproveAll = document.getElementById('btn-approve-all-plan');
    const btnClosePlanDetail = document.getElementById('btn-close-plan-detail');
    const btnApprovePlanShowtime = document.getElementById('btn-approve-plan-showtime');
    const btnRejectPlanShowtime = document.getElementById('btn-reject-plan-showtime');

    if (btnPrevWeek) btnPrevWeek.addEventListener('click', prevWeek);
    if (btnNextWeek) btnNextWeek.addEventListener('click', nextWeek);
    if (btnApproveAll) btnApproveAll.addEventListener('click', approveAllWeek);
    if (btnClosePlanDetail) btnClosePlanDetail.addEventListener('click', closePlanDetailModal);
    if (btnApprovePlanShowtime) btnApprovePlanShowtime.addEventListener('click', approveCurrentShowtime);
    if (btnRejectPlanShowtime) btnRejectPlanShowtime.addEventListener('click', () => {
        if (!currentSelectedShowtime) return;
        if (!confirm('Bạn có chắc muốn từ chối suất chiếu này?')) return;
        rejectShowtime(currentSelectedShowtime.id);
    });
}

function calculateNextWeek() {
    const today = new Date();
    const dayOfWeek = today.getDay();
    const daysFromMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
    
    const currentWeekMonday = new Date(today);
    currentWeekMonday.setDate(today.getDate() - daysFromMonday);
    currentWeekMonday.setHours(0, 0, 0, 0);
    
    const targetMonday = new Date(currentWeekMonday);
    targetMonday.setDate(currentWeekMonday.getDate() + (weekOffset * 7));
    
    const targetSunday = new Date(targetMonday);
    targetSunday.setDate(targetMonday.getDate() + 6);
    
    currentPlanWeekStart = targetMonday;
    
    const weekRangeEl = document.getElementById('plan-week-range');
    if (weekRangeEl) {
        weekRangeEl.textContent = `${formatDateDisplay(targetMonday)} - ${formatDateDisplay(targetSunday)}`;
    }
    
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
    const weekOffsetLabel = document.getElementById('plan-week-offset-label');
    if (!weekOffsetLabel) return;
    
    if (weekOffset === 0) {
        weekOffsetLabel.textContent = 'Tuần hiện tại';
    } else if (weekOffset === 1) {
        weekOffsetLabel.textContent = 'Tuần kế tiếp';
    } else if (weekOffset > 1) {
        weekOffsetLabel.textContent = `Tuần kế tiếp + ${weekOffset - 1}`;
    } else {
        weekOffsetLabel.textContent = `Tuần trước ${Math.abs(weekOffset)}`;
    }
}

function loadKeHoach() {
    const planListing = document.getElementById('plan-approval-listing');
    if (!planListing || !currentPlanWeekStart || !currentRapId) return;

    const baseUrl = planListing.dataset.url || '';
    const weekEnd = new Date(currentPlanWeekStart);
    weekEnd.setDate(currentPlanWeekStart.getDate() + 6);
    
    const batDau = formatDate(currentPlanWeekStart);
    const ketThuc = formatDate(weekEnd);

    try {
        Spinner.show({ target: planListing, text: 'Đang tải kế hoạch...' });
    } catch (e) {
        console.log('Spinner not available');
    }

    // Gọi API với tham số id_rap
    fetch(`${baseUrl}/api/ke-hoach-suat-chieu?batdau=${batDau}&ketthuc=${ketThuc}&id_rap=${currentRapId}`)
        .then(res => res.json())
        .then(data => {
            try {
                Spinner.hide();
            } catch (e) {
                console.log('Spinner not available');
            }

            // Xử lý cả 2 cấu trúc dữ liệu (array hoặc object với chi_tiet)
            keHoachData = [];
            let chiTietList = [];
            thongKeData = null;
            
            if (data.success && data.data) {
                if (Array.isArray(data.data)) {
                    // Trường hợp trả về array trực tiếp (backward compatible)
                    chiTietList = data.data;
                } else if (data.data && typeof data.data === 'object') {
                    // Trường hợp trả về object với chi_tiet (cấu trúc mới)
                    if (data.data.chi_tiet) {
                        chiTietList = data.data.chi_tiet;
                    }
                    // Lưu thông tin thống kê nếu có
                    if (data.data.thong_ke) {
                        thongKeData = data.data.thong_ke;
                    }
                }
            }
            
            if (chiTietList && chiTietList.length > 0) {
                keHoachData = chiTietList.map(item => ({
                    id: item.id,
                    id_kehoach: item.id_kehoach,
                    id_phim: item.id_phim,
                    ten_phim: item.phim?.ten_phim || 'N/A',
                    poster_url: item.phim?.poster_url || '',
                    thoi_luong: item.phim?.thoi_luong || 0,
                    id_phong_chieu: item.id_phongchieu,
                    ten_phong: item.phong_chieu?.ten || 'N/A',
                    ngay_chieu: formatDate(new Date(item.batdau)),
                    gio_bat_dau: new Date(item.batdau).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
                    gio_ket_thuc: new Date(item.ketthuc).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
                    tinh_trang: item.tinh_trang || 0,
                    batdau: item.batdau,
                    ketthuc: item.ketthuc
                }));
            } else {
                keHoachData = [];
            }

            renderKeHoach();
        })
        .catch(error => {
            try {
                Spinner.hide();
            } catch (e) {
                console.log('Spinner not available');
            }
            console.error('Error loading plan:', error);
            showToast('Lỗi khi tải kế hoạch', 'error');
        });
}

function renderKeHoach() {
    const emptyState = document.getElementById('plan-empty-state');
    const planContent = document.getElementById('plan-content');
    const totalShowtimesBadge = document.getElementById('plan-total-showtimes-badge');
    const showtimesByDay = document.getElementById('plan-showtimes-by-day');
    
    if (!emptyState || !planContent) return;

    // Cập nhật badge tổng số suất chiếu với thông tin thống kê
    if (totalShowtimesBadge) {
        if (thongKeData) {
            // Hiển thị thông tin chi tiết từ thống kê
            const { tong_so = 0, da_duyet = 0, cho_duyet = 0, tu_choi = 0 } = thongKeData;
            totalShowtimesBadge.innerHTML = `
                <span class="font-semibold">${tong_so} suất</span>
                <span class="text-xs ml-2 opacity-90">• ${da_duyet} đã duyệt • ${cho_duyet} chờ duyệt • ${tu_choi} từ chối</span>
            `;
        } else {
            // Fallback nếu không có thống kê
            totalShowtimesBadge.textContent = `${keHoachData.length} suất chiếu`;
        }
    }

    if (keHoachData.length === 0) {
        emptyState.classList.remove('hidden');
        planContent.classList.add('hidden');
        return;
    }
    
    emptyState.classList.add('hidden');
    planContent.classList.remove('hidden');

    // Nhóm theo ngày
    const groupedByDate = {};
    keHoachData.forEach(plan => {
        if (!groupedByDate[plan.ngay_chieu]) {
            groupedByDate[plan.ngay_chieu] = [];
        }
        groupedByDate[plan.ngay_chieu].push(plan);
    });

    let html = '';
    const planListing = document.getElementById('plan-approval-listing');
    const urlMinio = planListing?.dataset?.urlminio || '';

    Object.keys(groupedByDate).sort().forEach(dateKey => {
        const plans = groupedByDate[dateKey];
        const date = new Date(dateKey);
        const dayName = getDayName(date);
        
        // Đếm số suất theo trạng thái
        const pendingCount = plans.filter(p => p.tinh_trang == 0).length;
        const approvedCount = plans.filter(p => p.tinh_trang == 1).length;
        const rejectedCount = plans.filter(p => p.tinh_trang == 2).length;

        html += `
            <div class="border rounded-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">${dayName}, ${formatDateDisplay(date)}</h3>
                        <p class="text-sm text-gray-600 mt-1">${plans.length} suất chiếu</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">Chờ: ${pendingCount}</span>
                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">Duyệt: ${approvedCount}</span>
                        <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-800">Từ chối: ${rejectedCount}</span>
                    </div>
                </div>
                <div class="p-6 space-y-4">`;

        plans.forEach(plan => {
            const statusBadge = getStatusBadge(plan.tinh_trang);
            const canApprove = plan.tinh_trang != 1; // Chỉ duyệt nếu chưa duyệt
            const isRejected = plan.tinh_trang == 2; // Kiểm tra nếu bị từ chối

            html += `
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition cursor-pointer" data-showtime-id="${plan.id}">
                    <div class="flex items-center space-x-4 flex-1" onclick="viewPlanDetail(${plan.id})">
                        <img src="${urlMinio}/${plan.poster_url}" alt="${plan.ten_phim}" class="w-12 h-16 object-cover rounded shadow">
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900">${plan.ten_phim}</h4>
                            <p class="text-sm text-gray-600 mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                ${plan.ten_phong}
                                <span class="mx-2">•</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                ${plan.gio_bat_dau} - ${plan.gio_ket_thuc}
                            </p>
                        </div>
                        ${statusBadge}
                    </div>
                    <div class="flex items-center space-x-2 ml-4">
                        ${isRejected ? `
                            <button onclick="event.stopPropagation(); quickHoanTacKeHoach(${plan.id})" class="px-3 py-1 bg-orange-600 hover:bg-orange-700 text-white text-sm rounded-md transition flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                Hoàn tác
                            </button>
                        ` : canApprove ? `
                            <button onclick="event.stopPropagation(); quickApproveShowtime(${plan.id})" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded-md transition">
                                Duyệt
                            </button>
                            <button onclick="event.stopPropagation(); quickRejectShowtime(${plan.id})" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded-md transition">
                                Từ chối
                            </button>
                        ` : `
                            <span class="text-sm text-gray-500 italic">Đã xử lý</span>
                        `}
                    </div>
                </div>`;
        });

        html += `
                </div>
            </div>`;
    });

    if (showtimesByDay) {
        showtimesByDay.innerHTML = html;
    }
}

function getStatusBadge(tinhTrang) {
    switch(parseInt(tinhTrang)) {
        case 0:
            return '<span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800 font-medium">Chờ duyệt</span>';
        case 1:
            return '<span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800 font-medium">Đã duyệt</span>';
        case 2:
            return '<span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-800 font-medium">Từ chối</span>';
        default:
            return '<span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-800 font-medium">Không xác định</span>';
    }
}

// View chi tiết suất chiếu
window.viewPlanDetail = function(showtimeId) {
    const showtime = keHoachData.find(s => s.id === showtimeId);
    if (!showtime) return;

    currentSelectedShowtime = showtime;

    const modal = document.getElementById('plan-detail-modal');
    const content = document.getElementById('plan-detail-content');
    const planListing = document.getElementById('plan-approval-listing');
    const urlMinio = planListing?.dataset?.urlminio || '';

    if (!modal || !content) return;

    const statusBadge = getStatusBadge(showtime.tinh_trang);
    const canModify = showtime.tinh_trang != 1;

    content.innerHTML = `
        <div class="space-y-4">
            <div class="flex items-start space-x-4">
                <img src="${urlMinio}/${showtime.poster_url}" alt="${showtime.ten_phim}" class="w-24 h-32 object-cover rounded shadow">
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">${showtime.ten_phim}</h3>
                    ${statusBadge}
                    <div class="mt-4 space-y-2 text-sm text-gray-700">
                        <p><strong>Phòng chiếu:</strong> ${showtime.ten_phong}</p>
                        <p><strong>Ngày chiếu:</strong> ${getDayName(new Date(showtime.ngay_chieu))}, ${formatDateDisplay(new Date(showtime.ngay_chieu))}</p>
                        <p><strong>Giờ chiếu:</strong> ${showtime.gio_bat_dau} - ${showtime.gio_ket_thuc}</p>
                        <p><strong>Thời lượng:</strong> ${showtime.thoi_luong} phút</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Hiển thị/ẩn nút duyệt/từ chối/hoàn tác
    const btnApprove = document.getElementById('btn-approve-plan-showtime');
    const btnReject = document.getElementById('btn-reject-plan-showtime');
    const isRejected = showtime.tinh_trang == 2;
    
    if (btnApprove && btnReject) {
        if (canModify && !isRejected) {
            btnApprove.classList.remove('hidden');
            btnReject.classList.remove('hidden');
        } else {
            btnApprove.classList.add('hidden');
            btnReject.classList.add('hidden');
        }
    }
    
    // Thêm nút hoàn tác nếu bị từ chối
    if (isRejected) {
        const btnContainer = document.querySelector('#plan-detail-modal .modal-footer');
        if (btnContainer) {
            // Kiểm tra xem đã có nút hoàn tác chưa
            let btnHoanTac = btnContainer.querySelector('.btn-hoan-tac-ke-hoach');
            if (!btnHoanTac) {
                btnHoanTac = document.createElement('button');
                btnHoanTac.className = 'btn-hoan-tac-ke-hoach px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-md transition flex items-center gap-2';
                btnHoanTac.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                    Hoàn tác suất chiếu
                `;
                btnHoanTac.addEventListener('click', () => {
                    if (!confirm('Bạn có chắc muốn hoàn tác suất chiếu này? Suất chiếu sẽ được đưa về trạng thái chờ duyệt.')) {
                        return;
                    }
                    hoanTacKeHoach(showtime.id);
                });
                btnContainer.appendChild(btnHoanTac);
            }
            btnHoanTac.classList.remove('hidden');
        }
    } else {
        // Ẩn nút hoàn tác nếu không bị từ chối
        const btnHoanTac = document.querySelector('.btn-hoan-tac-ke-hoach');
        if (btnHoanTac) {
            btnHoanTac.classList.add('hidden');
        }
    }

    modal.classList.remove('hidden');
};

function closePlanDetailModal() {
    const modal = document.getElementById('plan-detail-modal');
    if (modal) modal.classList.add('hidden');
    currentSelectedShowtime = null;
}

// Quick approve từ danh sách
window.quickApproveShowtime = function(showtimeId) {
    approveShowtime(showtimeId);
};

// Quick reject từ danh sách
window.quickRejectShowtime = function(showtimeId) {
    if (!confirm('Bạn có chắc muốn từ chối suất chiếu này?')) return;
    rejectShowtime(showtimeId);
};

// Quick hoàn tác từ danh sách
window.quickHoanTacKeHoach = function(showtimeId) {
    if (!confirm('Bạn có chắc muốn hoàn tác suất chiếu này? Suất chiếu sẽ được đưa về trạng thái chờ duyệt.')) return;
    hoanTacKeHoach(showtimeId);
};

// Hoàn tác suất chiếu bị từ chối trong kế hoạch
function hoanTacKeHoach(showtimeId) {
    const planListing = document.getElementById('plan-approval-listing');
    const baseUrl = planListing?.dataset?.url || '';

    fetch(`${baseUrl}/api/ke-hoach-suat-chieu/${showtimeId}/hoan-tac`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Hoàn tác suất chiếu thành công', 'success');
            loadKeHoach();
            closePlanDetailModal();
        } else {
            showToast(data.message || 'Lỗi khi hoàn tác suất chiếu', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Lỗi khi hoàn tác suất chiếu', 'error');
    });
}

// Duyệt suất chiếu hiện tại (từ modal chi tiết)
function approveCurrentShowtime() {
    if (!currentSelectedShowtime) return;
    approveShowtime(currentSelectedShowtime.id);
    closePlanDetailModal();
}

// Duyệt suất chiếu
function approveShowtime(showtimeId) {
    const planListing = document.getElementById('plan-approval-listing');
    const baseUrl = planListing?.dataset?.url || '';

    fetch(`${baseUrl}/api/ke-hoach-suat-chieu/${showtimeId}/duyet`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Đã duyệt suất chiếu', 'success');
            loadKeHoach(); // Reload
        } else {
            showToast(data.message || 'Lỗi khi duyệt suất chiếu', 'error');
        }
    })
    .catch(error => {
        console.error('Error approving showtime:', error);
        showToast('Lỗi khi duyệt suất chiếu', 'error');
    });
}

// Từ chối suất chiếu
function rejectShowtime(showtimeId) {
    const planListing = document.getElementById('plan-approval-listing');
    const baseUrl = planListing?.dataset?.url || '';

    fetch(`${baseUrl}/api/ke-hoach-suat-chieu/${showtimeId}/tu-choi`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Đã từ chối suất chiếu', 'success');
            loadKeHoach();
            closePlanDetailModal();
        } else {
            showToast(data.message || 'Lỗi khi từ chối suất chiếu', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Lỗi khi từ chối suất chiếu', 'error');
    });
}

// Duyệt toàn bộ tuần
function approveAllWeek() {
    if (keHoachData.length === 0) {
        showToast('Không có suất chiếu nào để duyệt', 'error');
        return;
    }

    const pendingShowtimes = keHoachData.filter(s => s.tinh_trang == 0);
    if (pendingShowtimes.length === 0) {
        showToast('Không có suất chiếu chờ duyệt', 'error');
        return;
    }

    if (!confirm(`Bạn có chắc muốn duyệt ${pendingShowtimes.length} suất chiếu chờ duyệt?`)) {
        return;
    }

    const planListing = document.getElementById('plan-approval-listing');
    const baseUrl = planListing?.dataset?.url || '';

    // Duyệt tuần (API sẽ duyệt tất cả suất chờ duyệt)
    const weekEnd = new Date(currentPlanWeekStart);
    weekEnd.setDate(currentPlanWeekStart.getDate() + 6);
    
    const batDau = formatDate(currentPlanWeekStart);
    const ketThuc = formatDate(weekEnd);

    fetch(`${baseUrl}/api/ke-hoach-suat-chieu/duyet-tuan`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ batdau: batDau, ketthuc: ketThuc, id_rap: currentRapId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(`Đã duyệt ${data.count || pendingShowtimes.length} suất chiếu`, 'success');
            loadKeHoach(); // Reload
        } else {
            showToast(data.message || 'Lỗi khi duyệt tuần', 'error');
        }
    })
    .catch(error => {
        console.error('Error approving week:', error);
        showToast('Lỗi khi duyệt tuần', 'error');
    });
}

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('plan-toast');
    const toastMessage = document.getElementById('plan-toast-message');
    
    if (!toast || !toastMessage) return;
    
    toastMessage.textContent = message;
    
    // Đổi màu theo type
    toast.classList.remove('bg-green-500', 'bg-red-500', 'bg-yellow-500');
    if (type === 'error') {
        toast.classList.add('bg-red-500');
    } else if (type === 'warning') {
        toast.classList.add('bg-yellow-500');
    } else {
        toast.classList.add('bg-green-500');
    }
    
    toast.classList.remove('translate-y-20', 'opacity-0');
    toast.classList.add('translate-y-0', 'opacity-100');
    
    setTimeout(() => {
        toast.classList.remove('translate-y-0', 'opacity-100');
        toast.classList.add('translate-y-20', 'opacity-0');
    }, 3000);
}

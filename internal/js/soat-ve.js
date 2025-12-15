let html5QrCode = null;
let isScanning = false;
let isProcessing = false;
let availableCameras = [];
let selectedCameraId = null;
const CAMERA_STORAGE_KEY = 'soat-ve-selected-camera';

// State management
let selectedRoom = null;
let selectedShowtime = null;

// DOM Elements
const selectRoomSection = document.getElementById('select-room-section');
const selectShowtimeSection = document.getElementById('select-showtime-section');
const scannerSection = document.getElementById('scanner-section');
const scannerContainer = document.getElementById('scanner-container');
const controlButtons = document.getElementById('control-buttons');
const loadingOverlay = document.getElementById('loading-overlay');
const cameraSelect = document.getElementById('camera-select');
const refreshCameraBtn = document.getElementById('refresh-camera-btn');

const startScanBtn = document.getElementById('start-scan-btn');
const stopScanBtn = document.getElementById('stop-scan-btn');
const backToRoomBtn = document.getElementById('back-to-room-btn');
const backToShowtimeBtn = document.getElementById('back-to-showtime-btn');

const step1 = document.getElementById('step-1');
const step2 = document.getElementById('step-2');
const step3 = document.getElementById('step-3');

const BASE_URL = selectRoomSection.dataset.url;

// Toast notification function
function showToast(message, type = 'success', details = null) {
    const existingToasts = document.querySelectorAll('.toast-alert');
    existingToasts.forEach(toast => toast.remove());

    const toast = document.createElement('div');
    toast.className = `toast-alert rounded-lg shadow-lg p-4 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    
    let detailsHtml = '';
    if (details && type === 'success') {
        detailsHtml = `
            <div class="mt-3 pt-3 border-t border-white/20 text-sm space-y-1.5">
                ${details.ten_phim ? `<div class="flex items-center"><span class="mr-2">🎬</span><span class="font-medium">${details.ten_phim}</span></div>` : ''}
                ${details.phong_chieu ? `<div class="flex items-center"><span class="mr-2">🚪</span><span>${details.phong_chieu}</span></div>` : ''}
                ${details.ghe ? `<div class="flex items-center"><span class="mr-2">💺</span><span>Ghế: ${details.ghe}</span></div>` : ''}
                ${details.gio_chieu ? `<div class="flex items-center"><span class="mr-2">🕐</span><span>${details.gio_chieu}</span></div>` : ''}
                ${details.khach_hang ? `<div class="flex items-center"><span class="mr-2">👤</span><span>${details.khach_hang}</span></div>` : ''}
                ${details.so_luong_ve ? `<div class="flex items-center"><span class="mr-2">🎫</span><span>Số lượng: ${details.so_luong_ve} vé</span></div>` : ''}
                ${details.thoi_gian_soat ? `<div class="flex items-center text-white/70"><span class="mr-2">⏰</span><span>Soát lúc: ${details.thoi_gian_soat}</span></div>` : ''}
            </div>
        `;
    }
    
    toast.innerHTML = `
        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                ${type === 'success' 
                    ? '<svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                    : '<svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                }
            </div>
            <div class="flex-1">
                <p class="font-bold text-white text-lg">${type === 'success' ? '✓ Vé hợp lệ!' : '✗ Vé không hợp lệ!'}</p>
                <p class="text-white/90 mt-1">${message}</p>
                ${detailsHtml}
            </div>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Update step progress
function updateStepProgress(currentStep) {
    [step1, step2, step3].forEach((step, index) => {
        step.classList.remove('active', 'completed');
        if (index + 1 < currentStep) {
            step.classList.add('completed');
        } else if (index + 1 === currentStep) {
            step.classList.add('active');
        }
    });
}

// Step 1: Room Selection
document.querySelectorAll('.room-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        selectedRoom = {
            id: this.dataset.roomId,
            name: this.dataset.roomName
        };
        
        document.getElementById('selected-room-name').textContent = selectedRoom.name;
        
        // Load showtimes
        await loadShowtimes(selectedRoom.id);
        
        // Show step 2
        selectRoomSection.classList.add('hidden');
        selectShowtimeSection.classList.remove('hidden');
        updateStepProgress(2);
    });
});
// Helper function to get showtime status
function getShowtimeStatus(batdau, ketthuc) {
    if (!batdau || !ketthuc) return { isExpired: false, label: 'Chưa chiếu', color: 'gray' };
    
    const startTime = new Date(batdau);
    const endTime = new Date(ketthuc);
    const now = new Date();
    
    if (now > endTime) {
        return { isExpired: true, label: 'Đã kết thúc', color: 'red' };
    } else if (now >= startTime && now <= endTime) {
        return { isExpired: false, label: 'Đang chiếu', color: 'green' };
    } else {
        return { isExpired: false, label: 'Sắp chiếu', color: 'blue' };
    }
}

// Helper function to format time
function formatTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

// Helper function to format date
function formatDate(datetime) {
    const date = new Date(datetime);
    return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

async function loadCameras() {
    if (!cameraSelect) return;

    cameraSelect.innerHTML = '<option value="">Đang tải danh sách camera...</option>';
    cameraSelect.disabled = true;
    startScanBtn.disabled = true;
    if (refreshCameraBtn) refreshCameraBtn.disabled = true;

    try {
        const devices = await Html5Qrcode.getCameras();
        availableCameras = devices || [];

        if (!availableCameras.length) {
            cameraSelect.innerHTML = '<option value="">Không tìm thấy camera</option>';
            return;
        }

        const savedCameraId = localStorage.getItem(CAMERA_STORAGE_KEY);
        const defaultId = savedCameraId && availableCameras.some(cam => cam.id === savedCameraId)
            ? savedCameraId
            : availableCameras[0].id;
        selectedCameraId = defaultId;

        cameraSelect.innerHTML = availableCameras
            .map(cam => `<option value="${cam.id}" ${cam.id === selectedCameraId ? 'selected' : ''}>${cam.label || 'Camera'}</option>`) 
            .join('');

        startScanBtn.disabled = false;
    } catch (error) {
        console.error('Error loading cameras:', error);
        showToast('Không thể lấy danh sách camera', 'error');
    } finally {
        cameraSelect.disabled = false;
        if (refreshCameraBtn) refreshCameraBtn.disabled = false;
    }
}

// Step 1: Room Selection
document.querySelectorAll('.room-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        selectedRoom = {
            id: this.dataset.roomId,
            name: this.dataset.roomName
        };
        
        document.getElementById('selected-room-name').textContent = selectedRoom.name;
        
        // Load showtimes
        await loadShowtimes(selectedRoom.id);
        
        // Show step 2
        selectRoomSection.classList.add('hidden');
        selectShowtimeSection.classList.remove('hidden');
        updateStepProgress(2);
    });
});

// Load showtimes for selected room
async function loadShowtimes(roomId) {
    loadingOverlay.classList.remove('hidden');
    
    try {
        const response = await fetch(`${BASE_URL}/api/soat-ve/lay-suat-chieu/${roomId}`);
        const data = await response.json();
        
        const showtimeList = document.getElementById('showtime-list');
        showtimeList.innerHTML = '';
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(showtime => {
                const status = getShowtimeStatus(showtime.batdau, showtime.ketthuc);
                
                const showtimeBtn = document.createElement('button');
                showtimeBtn.className = `showtime-btn w-full p-4 border-2 rounded-lg transition-all text-left ${
                    status.isExpired 
                        ? 'border-gray-200 bg-gray-50 cursor-not-allowed opacity-60' 
                        : 'border-gray-300 hover:border-orange-500 hover:bg-orange-50'
                }`;
                showtimeBtn.dataset.showtimeId = showtime.id;
                showtimeBtn.dataset.showtimeInfo = JSON.stringify(showtime);
                
                if (status.isExpired) {
                    showtimeBtn.disabled = true;
                }
                
                // Xác định màu badge dựa trên status
                let badgeClass = '';
                if (status.color === 'red') {
                    badgeClass = 'bg-red-100 text-red-800';
                } else if (status.color === 'green') {
                    badgeClass = 'bg-green-100 text-green-800';
                } else if (status.color === 'blue') {
                    badgeClass = 'bg-blue-100 text-blue-800';
                } else {
                    badgeClass = 'bg-gray-100 text-gray-800';
                }
                
                showtimeBtn.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 ${status.isExpired ? 'bg-gray-200' : 'bg-orange-100'} rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 ${status.isExpired ? 'text-gray-400' : 'text-orange-600'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold ${status.isExpired ? 'text-gray-500' : 'text-gray-900'}">${showtime.phim.ten_phim}</p>
                                <p class="text-sm ${status.isExpired ? 'text-gray-400' : 'text-gray-600'} mt-1">
                                    <span class="inline-flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        ${formatTime(showtime.batdau)}
                                    </span>
                                    <span class="mx-2">•</span>
                                    <span class="inline-flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        ${formatDate(showtime.batdau)}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Trạng thái</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeClass}">
                                ${status.label}
                            </span>
                        </div>
                    </div>
                `;
                
                showtimeList.appendChild(showtimeBtn);
            });
            
            // Add event listeners to showtime buttons (only for non-expired)
            document.querySelectorAll('.showtime-btn:not([disabled])').forEach(btn => {
                btn.addEventListener('click', function() {
                    selectedShowtime = JSON.parse(this.dataset.showtimeInfo);
                    goToScannerStep();
                });
            });
        } else {
            showtimeList.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="font-medium">Không có suất chiếu nào</p>
                    <p class="text-sm mt-1">Phòng này chưa có suất chiếu nào được lên lịch</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading showtimes:', error);
        showToast('Có lỗi khi tải danh sách suất chiếu', 'error');
    } finally {
        loadingOverlay.classList.add('hidden');
    }
}


// Step 3: Go to scanner
function goToScannerStep() {
    selectShowtimeSection.classList.add('hidden');
    scannerSection.classList.remove('hidden');
    
    document.getElementById('scanning-room-name').textContent = selectedRoom.name;
    document.getElementById('scanning-showtime-info').textContent = 
        `${selectedShowtime.phim.ten_phim} - ${formatTime(selectedShowtime.batdau)}, ${formatDate(selectedShowtime.batdau)}`;
    
    updateStepProgress(3);
    loadCameras();
}

// Back buttons
backToRoomBtn.addEventListener('click', () => {
    selectShowtimeSection.classList.add('hidden');
    selectRoomSection.classList.remove('hidden');
    selectedShowtime = null;
    updateStepProgress(1);
});

backToShowtimeBtn.addEventListener('click', async () => {
    await stopScanning();
    scannerSection.classList.add('hidden');
    selectShowtimeSection.classList.remove('hidden');
    selectedShowtime = null;
    updateStepProgress(2);
});

if (refreshCameraBtn) {
    refreshCameraBtn.addEventListener('click', async () => {
        await stopScanning();
        await loadCameras();
    });
}

if (cameraSelect) {
    cameraSelect.addEventListener('change', async (event) => {
        selectedCameraId = event.target.value || null;
        localStorage.setItem(CAMERA_STORAGE_KEY, selectedCameraId || '');
        if (isScanning) {
            await stopScanning();
            showToast('Đã đổi camera. Nhấn "Bắt đầu quét vé" để tiếp tục.', 'success');
        }
    });
}

// Start camera scan
startScanBtn.addEventListener('click', async () => {
    try {
        scannerContainer.classList.remove('hidden');
        controlButtons.classList.add('hidden');
        
        html5QrCode = new Html5Qrcode("reader");
        
        const config = { 
            fps: 10, 
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };
        const cameraConfig = selectedCameraId
            ? { deviceId: { exact: selectedCameraId } }
            : { facingMode: "environment" };
        
        await html5QrCode.start(
            cameraConfig,
            config,
            onScanSuccess,
            onScanError
        );
        
        isScanning = true;
    } catch (err) {
        console.error('Error starting scanner:', err);
        showToast('Không thể khởi động camera. Vui lòng kiểm tra quyền truy cập.', 'error');
        scannerContainer.classList.add('hidden');
        controlButtons.classList.remove('hidden');
    }
});

// Stop scan
stopScanBtn.addEventListener('click', async () => {
    await stopScanning();
});

async function stopScanning() {
    if (html5QrCode && isScanning) {
        try {
            await html5QrCode.stop();
            html5QrCode.clear();
        } catch (err) {
            console.error('Error stopping scanner:', err);
        }
    }
    scannerContainer.classList.add('hidden');
    controlButtons.classList.remove('hidden');
    isScanning = false;
    html5QrCode = null;
}

async function onScanSuccess(decodedText) {
    if (isProcessing) return;
    isProcessing = true;

    await stopScanning();
    loadingOverlay.classList.remove('hidden');
    
    try {
        const response = await fetch(`${BASE_URL}/api/soat-ve/kiem-tra`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                ma_ve: decodedText,
                id_suat_chieu: selectedShowtime.id,
            })
        });
        
        const data = await response.json();
        loadingOverlay.classList.add('hidden');
        
        if (data.success) {
            showToast(`Vé ${data.data.ma_ve} đã được xác nhận`, 'success', data.data);
            setTimeout(() => {
                isProcessing = false;
                startScanBtn.click();
            }, 3200);
        } else {
            showToast(data.message || 'Vé không hợp lệ', 'error');
            setTimeout(() => {
                isProcessing = false;
                startScanBtn.click();
            }, 3200);
        }
    } catch (error) {
        console.error('Error verifying ticket:', error);
        loadingOverlay.classList.add('hidden');
        showToast('Có lỗi khi kiểm tra vé. Vui lòng thử lại.', 'error');
        setTimeout(() => {
            isProcessing = false;
            startScanBtn.click();
        }, 3200);
    }
}

function onScanError(error) {
    // Ignore scan errors
}
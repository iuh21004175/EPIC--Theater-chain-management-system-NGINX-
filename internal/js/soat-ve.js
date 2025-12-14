    let html5QrCode = null;
let isScanning = false;
let isProcessing = false;

const startScanBtn = document.getElementById('start-scan-btn');
const stopScanBtn = document.getElementById('stop-scan-btn');
const scannerContainer = document.getElementById('scanner-container');
const controlButtons = document.getElementById('control-buttons');
const loadingOverlay = document.getElementById('loading-overlay');
const fileUpload = document.getElementById('file-upload');

// Toast notification function
function showToast(message, type = 'success', details = null) {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.toast-alert');
    existingToasts.forEach(toast => toast.remove());

    const toast = document.createElement('div');
    toast.className = `toast-alert rounded-lg shadow-lg p-4 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    
    let detailsHtml = '';
    if (details && type === 'success') {
        detailsHtml = `
            <div class="mt-2 pt-2 border-t border-white/20 text-sm space-y-1">
                ${details.ten_phim ? `<div>🎬 ${details.ten_phim}</div>` : ''}
                ${details.ghe ? `<div>💺 Ghế: ${details.ghe}</div>` : ''}
                ${details.phong_chieu ? `<div>🚪 ${details.phong_chieu}</div>` : ''}
                ${details.khach_hang ? `<div>👤 ${details.khach_hang}</div>` : ''}
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
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
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
        
        await html5QrCode.start(
            { facingMode: "environment" },
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
}

// File upload scan
fileUpload.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (isProcessing) return;

    try {
        const reader = new FileReader();
        reader.onload = async (event) => {
            const html5QrCodeScanner = new Html5Qrcode("reader");
            try {
                const decodedText = await html5QrCodeScanner.scanFile(file, true);
                onScanSuccess(decodedText);
            } catch (err) {
                showToast('Không tìm thấy mã QR trong ảnh. Vui lòng thử lại.', 'error');
            }
        };
        reader.readAsDataURL(file);
    } catch (err) {
        console.error('Error scanning file:', err);
        showToast('Có lỗi khi đọc file. Vui lòng thử lại.', 'error');
    }
    
    // Reset file input
    fileUpload.value = '';
});

async function onScanSuccess(decodedText) {
    // Chặn callback lặp lại khi quét cùng một khung hình
    if (isProcessing) return;
    isProcessing = true;

    // Dừng quét ngay để không phát sinh thêm callback
    await stopScanning();

    // Show loading
    loadingOverlay.classList.remove('hidden');
    
    // Call API to verify ticket
    try {
        const response = await fetch(`${scannerContainer.dataset.url}/api/soat-ve/kiem-tra`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ma_ve: decodedText })
        });
        
        const data = await response.json();
        loadingOverlay.classList.add('hidden');
        
        if (data.success) {
            showToast(`Vé ${data.data.ma_ve} đã được xác nhận`, 'success', data.data);
            // Auto restart scanning after 3 seconds
            setTimeout(() => {
                isProcessing = false;
                startScanBtn.click();
            }, 3200);
        } else {
            showToast(data.message || 'Vé không hợp lệ', 'error');
            // Auto restart scanning after 3 seconds
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
    // Ignore scan errors (they happen frequently during scanning)
}
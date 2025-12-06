// Ensure import matches registration page
import { FaceDetector, FilesetResolver } from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.0";
import Spinner from './util/spinner.js';
document.addEventListener("DOMContentLoaded", async function () {
    const API_URL = document.getElementById('registrationStatus').dataset.url + '/api';
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const loadingModal = document.getElementById('loadingModal');
    const cameraSection = document.getElementById('cameraSection');
    const statusContent = document.getElementById('statusContent');

    // UI controls (already in HTML)
    const cameraSelect = document.getElementById('cameraSelect');
    const btnSelectCamera = document.getElementById('btnSelectCamera');
    const btnCheckin = document.getElementById('btnCheckin');
    const btnCheckout = document.getElementById('btnCheckout');
    
    // Helper: toggle small inline spinner inside a button
    function setBtnLoading(btn, loading, label) {
        if (!btn) return;
        if (loading) {
            btn.disabled = true;
            if (!btn.dataset.origHtml) btn.dataset.origHtml = btn.innerHTML;
            const text = label || btn.dataset.origHtml?.replace(/<[^>]+>/g,'') || '';
            btn.innerHTML = `<span class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg><span>${text}</span></span>`;
        } else {
            btn.disabled = false;
            if (btn.dataset.origHtml) {
                btn.innerHTML = btn.dataset.origHtml;
                delete btn.dataset.origHtml;
            }
        }
    }

    let stream = null;
    let detector = null;
    let isCapturing = false;
    let facePresent = false;
    let faceStreakStart = 0;
    const RECORD_DURATION_MS = 3000; // 3 seconds required
    const TARGET_WIDTH = 1280;
    const TARGET_HEIGHT = 720;

    // New: only start recording when user explicitly requests checkin/checkout
    let armedAction = null; // 'checkin' | 'checkout' | null
    let armedTimeoutId = null;
    const ARM_TIMEOUT_MS = 15000; // cancel arm after 15s if no action

    // Global variable to store shift history (lịch sử chấm công)
    let shiftHistory = [];
    
    // Pagination variables
    let currentPage = 1;
    const recordsPerPage = 10;
    let totalPages = 1;

    // Standard shift definitions (giờ chuẩn cho từng ca)
    const CA_CONFIG = {
        'Ca sáng': { 
            gioVaoChuan: '08:00', 
            gioRaChuan: '12:00',
            gioMoCua: 8,
            gioDongCua: 22
        },
        'Ca chiều': { 
            gioVaoChuan: '12:00', 
            gioRaChuan: '17:00',
            gioMoCua: 8,
            gioDongCua: 22
        },
        'Ca tối': { 
            gioVaoChuan: '17:00', 
            gioRaChuan: '22:00',
            gioMoCua: 8,
            gioDongCua: 22
        }
    };

    // init MediaPipe face detector
    async function initDetector() {
        try {
            const vision = await FilesetResolver.forVisionTasks("https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision/wasm");
            detector = await FaceDetector.createFromOptions(vision, {
                baseOptions: {
                    modelAssetPath: "https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/1/blaze_face_short_range.tflite"
                },
                runningMode: "VIDEO",
                minDetectionConfidence: 0.5
            });
            console.log('FaceDetector ready');
        } catch (err) {
            console.error('Failed to init FaceDetector:', err);
            detector = null;
        }
    }

    // populate camera list
    async function populateCameraList() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const cams = devices.filter(d => d.kind === 'videoinput');
            cameraSelect.innerHTML = '<option value="">Tự động</option>';
            cams.forEach((c, i) => {
                const label = c.label || `Camera ${i + 1}`;
                const opt = document.createElement('option');
                opt.value = c.deviceId;
                opt.textContent = label;
                cameraSelect.appendChild(opt);
            });
        } catch (err) {
            console.warn('Cannot list cameras:', err);
        }
    }

    function stopStream() {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        video.srcObject = null;
        isCapturing = false;
    }

    async function startCameraWithDevice(deviceId = '') {
        stopStream();
        const constraints = {
            video: {
                width: { ideal: TARGET_WIDTH },
                height: { ideal: TARGET_HEIGHT },
                facingMode: 'user'
            },
            audio: false
        };
        if (deviceId) constraints.video.deviceId = { exact: deviceId };
        try {
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = stream;
            await video.play();
            // wait until metadata/size available
            if (!video.videoWidth || !video.videoHeight) {
                await new Promise(resolve => {
                    const onMeta = () => { video.removeEventListener('loadedmetadata', onMeta); resolve(); };
                    video.addEventListener('loadedmetadata', onMeta);
                    // fallback timeout
                    setTimeout(resolve, 500);
                });
            }
            adjustCanvas();
            // mark that we have an active video source for detection loop
            isCapturing = true;
        } catch (err) {
            alert('Không thể truy cập camera: ' + err.message);
            console.error(err);
        }
    }

    function adjustCanvas() {
        // use displayed 1280x720 area; canvas for processing set to that resolution
        // keep aspect ratio consistent; set canvas to target size
        canvas.width = TARGET_WIDTH;
        canvas.height = TARGET_HEIGHT;
        canvas.style.width = '100%';
        canvas.style.height = 'auto';
    }

    // capture snapshot at specified resolution from video (center-crop/pad if needed)
    // captureFrameToCanvas: return false when can't draw
    function captureFrameToCanvas() {
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        const vw = video.videoWidth || video.clientWidth || TARGET_WIDTH;
        const vh = video.videoHeight || video.clientHeight || TARGET_HEIGHT;
        if (vw === 0 || vh === 0) return false;
        // draw video scaled to canvas size, center-crop preserve aspect
        const canvasRatio = TARGET_WIDTH / TARGET_HEIGHT;
        const videoRatio = vw / vh;
        let sx = 0, sy = 0, sw = vw, sh = vh;
        if (videoRatio > canvasRatio) {
            // video wider -> crop sides
            const desiredW = vh * canvasRatio;
            sx = Math.floor((vw - desiredW) / 2);
            sw = Math.floor(desiredW);
        } else if (videoRatio < canvasRatio) {
            // video taller -> crop top/bottom
            const desiredH = vw / canvasRatio;
            sy = Math.floor((vh - desiredH) / 2);
            sh = Math.floor(desiredH);
        }
        ctx.drawImage(video, sx, sy, sw, sh, 0, 0, TARGET_WIDTH, TARGET_HEIGHT);
        return true;
    }

    // when face detected continuously for RECORD_DURATION_MS, record 3s video (MediaRecorder)
    let mediaRecorder = null;
    let recordedChunks = [];
    let recording = false;
    let recordTimeoutId = null;

    async function startRecording() {
        if (!stream || recording) return;
        recordedChunks = [];
        const options = { mimeType: 'video/webm;codecs=vp9' };
        if (!MediaRecorder.isTypeSupported(options.mimeType)) options.mimeType = 'video/webm';
        try {
            // Record from the canvas to guarantee resolution = TARGET_WIDTH x TARGET_HEIGHT
            // ensure canvas.width/height already set to TARGET_WIDTH/TARGET_HEIGHT in adjustCanvas()
            let recordingStream = null;
            try {
                // capture at 30 fps (adjust if needed)
                recordingStream = canvas.captureStream(30);
                mediaRecorder = new MediaRecorder(recordingStream, options);
            } catch (err) {
                console.warn('Canvas captureStream failed, fallback to camera stream', err);
                try {
                    mediaRecorder = new MediaRecorder(stream, options);
                } catch (err2) {
                    console.warn('MediaRecorder init error, trying without options', err2);
                    mediaRecorder = new MediaRecorder(stream);
                }
            }
        } catch (err) {
            console.warn('MediaRecorder init error, trying without options', err);
            mediaRecorder = new MediaRecorder(stream);
        }
        mediaRecorder.ondataavailable = (e) => { if (e.data && e.data.size) recordedChunks.push(e.data); };
        mediaRecorder.onstop = async () => {
            recording = false;
            loadingModal.classList.remove('hidden');
            const blob = new Blob(recordedChunks, { type: 'video/webm' });
            // Offer download to user
            // Upload result according to armedAction (fallback to 'checkin')
            const action = armedAction || 'checkin';
            if (armedTimeoutId) { clearTimeout(armedTimeoutId); armedTimeoutId = null; }
            armedAction = null;
            try {
                // Find current shift (ca hiện tại) based on current time
                const currentShift = getCurrentShift();
                
                // Check if shift is approved for leave (trang_thai == 2)
                if (currentShift && currentShift.trang_thai == 2) {
                    loadingModal.classList.add('hidden');
                    alert('Ca làm việc này đã được duyệt nghỉ. Không thể chấm công.');
                    setBtnLoading(btnCheckin, false);
                    setBtnLoading(btnCheckout, false);
                    return;
                }
                
                const formData = new FormData();
                formData.append('video', blob, `face_record_${Date.now()}.webm`);
                formData.append('loai', action);
                formData.append('wifiTen', cameraSection.dataset.ten);
                formData.append('token', await getTokenApi());
                
                // Add id_phancong if current shift is found
                if (currentShift && currentShift.id) {
                    formData.append('id_phancong', currentShift.id);
                }
                const res = await fetch(`${API_URL}/cham-cong/cham-cong`, {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                loadingModal.classList.add('hidden');
                if (json.success) {
                    alert(`✓ ${json.message}\n${json.data?.ten || ''}`);
                    loadHistory();
                } else {
                    alert('✗ ' + (json.message || 'Lỗi server'));
                }
                // hide button spinners after upload completes
                setBtnLoading(btnCheckin, false);
                setBtnLoading(btnCheckout, false);
            } catch (err) {
                loadingModal.classList.add('hidden');
                console.error('Upload failed', err);
                alert((err.message || err));
                setBtnLoading(btnCheckin, false);
                setBtnLoading(btnCheckout, false);
            }
        };
        mediaRecorder.start();
        recording = true;
        // stop after RECORD_DURATION_MS if not interrupted (but we'll handle stop earlier if face lost)
        recordTimeoutId = setTimeout(() => {
            if (mediaRecorder && mediaRecorder.state === 'recording') mediaRecorder.stop();
        }, RECORD_DURATION_MS);
    }
    async function getTokenApi(){
        const wifiIp = cameraSection.dataset.ip;
        const wifiTen = cameraSection.dataset.ten;
        
        if (!wifiIp) {
            throw new Error('Không tìm thấy thông tin IP wifi. Vui lòng liên hệ quản lý rạp.');
        }
        
        try {
            // Thêm timeout cho fetch request (10 giây)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);
            
            const response = await fetch(`http://${wifiIp}:2552`, {
                signal: controller.signal,
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`Hãy đảm bảo bạn đã kết nối tới wifi: ${wifiTen}\nNếu đã kết nối đúng tới wifi, hãy liên hệ quản lý rạp để xử lý.`);
            }
            
            const data = await response.json();
            if(data.status != 'success'){
                throw new Error(`Hệ thống đang cập nhật wifi vui lòng thử lại sau.`)
            }
            else {
                return data.token;
            }
        }
        catch (error) {
            if (error.name === 'AbortError') {
                throw new Error(`Vui lòng kết nối tới wifi ${wifiTen}. Kiểm tra kết nối mạng và thử lại.`);
            } else if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                throw new Error(`Không thể kết nối tới Server Local.\nVui lòng đảm bảo:\n- Bạn đã kết nối đúng wifi\n- Router đang hoạt động bình thường\n- Liên hệ quản lý rạp nếu vấn đề vẫn tiếp tục.`);
            } else {
                throw new Error(`Lỗi hệ thống: ${error.message}`);
            }
        }

    }
    function stopRecordingAndReset() {
        if (recordTimeoutId) {
            clearTimeout(recordTimeoutId);
            recordTimeoutId = null;
        }
        if (mediaRecorder && recording) {
            try { mediaRecorder.stop(); } catch (e) { /* ignore */ }
        }
        recording = false;
        recordedChunks = [];
        // hide any button spinner if recording cancelled
        setBtnLoading(btnCheckin, false);
        setBtnLoading(btnCheckout, false);
    }

    // main detection loop
    // detection loop: guard before calling detector
    async function detectLoop() {
        if (!detector || !stream || !isCapturing) { requestAnimationFrame(detectLoop); return; }

        // ensure video size valid
        if (!video.videoWidth || !video.videoHeight) {
            // wait a bit and retry
            await new Promise(r => setTimeout(r, 50));
            requestAnimationFrame(detectLoop);
            return;
        }

        // draw & ensure capture succeeded
        const ok = captureFrameToCanvas();
        if (!ok) { requestAnimationFrame(detectLoop); return; }

        const ctx = canvas.getContext('2d');
        
        try {
            // pass video element and timestamp (as in registration file)
            const results = await detector.detectForVideo(video, performance.now());
            const detections = results?.detections || [];
            
            // Draw bounding boxes for all detected faces
            if (detections.length > 0) {
                detections.forEach((detection, index) => {
                    const bbox = detection.boundingBox;
                    if (bbox) {
                        // Calculate bounding box coordinates scaled to canvas size
                        const x = bbox.originX;
                        const y = bbox.originY;
                        const w = bbox.width;
                        const h = bbox.height;
                        
                        // Draw rectangle around face
                        ctx.strokeStyle = detections.length === 1 ? '#00ff00' : '#ff0000'; // Green for 1 face, red for multiple
                        ctx.lineWidth = 3;
                        ctx.strokeRect(x, y, w, h);
                        
                        // Draw label
                        const label = detections.length === 1 ? 'Khuôn mặt' : `Khuôn mặt ${index + 1}`;
                        ctx.fillStyle = detections.length === 1 ? '#00ff00' : '#ff0000';
                        ctx.font = '16px Arial';
                        ctx.fillText(label, x, y - 5);
                        
                        // Draw confidence score
                        const confidence = (detection.categories[0]?.score * 100).toFixed(1);
                        ctx.fillText(`${confidence}%`, x, y + h + 20);
                    }
                });
            }
            
            if (detections.length === 1) {
                // face present
                const d = detections[0];
                // optional: you can inspect boundingBox or landmarks
                if (!facePresent) {
                    facePresent = true;
                    faceStreakStart = Date.now();
                } else {
                    // still present, check duration
                    const elapsed = Date.now() - faceStreakStart;
                    // Only start recording if user armed (clicked Check In / Out)
                    if (armedAction && !recording) {
                        // start recording immediately; if face disappears during RECORD_DURATION_MS it will be cancelled
                        await startRecording();
                        // if (statusContent) statusContent.innerHTML = `<p class="text-green-700">Đang ghi ${armedAction}... Vui lòng giữ mặt trong khung ${RECORD_DURATION_MS/1000}s</p>`;
                    }
                }
            } else if (detections.length > 1) {
                // multiple faces - treat as not ok
                facePresent = false;
                faceStreakStart = 0;
                // if recording, stop and reset (will retry)
                if (recording) stopRecordingAndReset();
                // show warning
                // if (statusContent) statusContent.innerHTML = '<p class="text-yellow-700">Nhiều hơn một khuôn mặt — chỉ chấm công khi chỉ còn 1 khuôn mặt</p>';
            } else {
                // no face
                if (facePresent) {
                    // face was present but now lost — reset
                    facePresent = false;
                    faceStreakStart = 0;
                    if (recording) {
                        // if face disappears during the required record window, cancel recording and restart
                        stopRecordingAndReset();
                        if (armedAction) {
                            // keep armedAction so user can try again without re-clicking within arm timeout
                            // if (statusContent) statusContent.innerHTML = '<p class="text-gray-600">Khuôn mặt biến mất — thử lại</p>';
                        }
                    }
                }
                // update UI
                // if (statusContent) statusContent.innerHTML = '<p class="text-gray-600">Không thấy khuôn mặt — vui lòng quay lại khung hình</p>';
            }
        } catch (err) {
            console.warn('detect error', err);
        }
        requestAnimationFrame(detectLoop);
    }

    // Helper: Find current shift assignment based on current time and today's date
    function getCurrentShift() {
        const now = new Date();
        const today = now.toISOString().slice(0, 10); // YYYY-MM-DD
        const currentHour = now.getHours();
        const currentMinute = now.getMinutes();
        
        // Find today's shifts from shiftHistory
        const todayShifts = shiftHistory.filter(record => {
            const recordDate = (record.ngay || record.ngay_cham);
            return recordDate === today;
        });
        
        // Match current time against shift definitions
        for (const shift of todayShifts) {
            const ca = shift.ca;
            const config = CA_CONFIG[ca];
            if (!config) continue;
            
            // Parse shift start/end times
            const [startHour, startMin] = config.gioVaoChuan.split(':').map(Number);
            const [endHour, endMin] = config.gioRaChuan.split(':').map(Number);
            
            // Check if current time falls within this shift (with tolerance)
            const currentTimeMinutes = currentHour * 60 + currentMinute;
            const shiftStartMinutes = startHour * 60 + startMin;
            const shiftEndMinutes = endHour * 60 + endMin;
            
            // Allow 30 minutes before shift start and after shift end
            if (currentTimeMinutes >= (shiftStartMinutes - 30) && currentTimeMinutes <= (shiftEndMinutes + 30)) {
                return shift; // Return the matching shift record (contains id, ca, etc.)
            }
        }
        
        // If no exact match, return the first shift of today (fallback)
        return todayShifts.length > 0 ? todayShifts[0] : null;
    }

    // original checkRegistrationStatus + loadHistory functions (reuse existing)
    async function checkRegistrationStatus() {
        try {
            const response = await fetch(`${API_URL}/cham-cong/kiem-tra-dang-ky`);
            const result = await response.json();
            if (result.success) {
                statusContent.innerHTML = `
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 mb-2">
                            <svg class="h-6 w-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p class="text-green-700 font-medium">Đã đăng ký khuôn mặt</p>
                        <p class="text-gray-600 text-sm mt-1">Ngày đăng ký: ${new Date(result.data.ngay_dang_ky).toLocaleDateString('vi-VN')}</p>
                    </div>
                `;
                cameraSection.classList.remove('hidden');
                // ensure detector ready and start camera preview
                await initDetector();
                await populateCameraList();
                await startCameraWithDevice(cameraSelect.value || '');
                detectLoop();
            } else {
                statusContent.innerHTML = `
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 mb-2">
                            <svg class="h-6 w-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p class="text-yellow-700 font-medium">Chưa đăng ký khuôn mặt</p>
                        <p class="text-gray-600 text-sm mt-1 mb-3">Bạn cần đăng ký khuôn mặt trước khi chấm công</p>
                        <a href="./dang-ky-khuon-mat" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            Đăng ký ngay
                        </a>
                    </div>
                `;
            }
        } catch (err) {
            console.error('Error checking registration:', err);
        }
    }

    async function loadHistory() {
        try {
            const response = await fetch(`${API_URL}/cham-cong/lich-su`);
            const result = await response.json();
            
            // Save history to global variable
            if (result.success && result.data) {
                shiftHistory = result.data;
            } else {
                shiftHistory = [];
            }
            
            // Render with pagination
            renderHistoryPage();
        } catch (err) {
            console.error('Error loading history:', err);
            document.getElementById('historyTableBody').innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Lỗi tải dữ liệu</td></tr>';
        }
    }
    
    function renderHistoryPage() {
        const tbody = document.getElementById('historyTableBody');
        const today = new Date().toISOString().slice(0, 10);

        // Parse a datetime value returned by API.
        // Accepts full ISO strings, timestamps, or time-only strings like "15:14:03".
        function parseDateTime(value) {
            if (!value && value !== 0) return null;
            if (typeof value === 'number') {
                const d = new Date(value);
                return isNaN(d) ? null : d;
            }
            // If it's an ISO-like string (contains '-' or 'T'), use built-in parser
            if (typeof value === 'string' && (value.indexOf('-') !== -1 || value.indexOf('T') !== -1)) {
                const d = new Date(value);
                return isNaN(d) ? null : d;
            }
            // Match HH:MM or HH:MM:SS
            if (typeof value === 'string') {
                const m = value.match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/);
                if (m) {
                    const now = new Date();
                    const hh = parseInt(m[1], 10);
                    const mm = parseInt(m[2], 10);
                    const ss = m[3] ? parseInt(m[3], 10) : 0;
                    const d = new Date(now.getFullYear(), now.getMonth(), now.getDate(), hh, mm, ss);
                    return d;
                }
                // Fallback to Date constructor
                const d2 = new Date(value);
                return isNaN(d2) ? null : d2;
            }
            return null;
        }

        if (shiftHistory.length > 0) {
            // Calculate pagination
            totalPages = Math.ceil(shiftHistory.length / recordsPerPage);
            const startIndex = (currentPage - 1) * recordsPerPage;
            const endIndex = Math.min(startIndex + recordsPerPage, shiftHistory.length);
            const currentRecords = shiftHistory.slice(startIndex, endIndex);
            
            tbody.innerHTML = currentRecords.map(record => {
                const ngayCham = record.ngay || record.ngay_cham;
                const gioVao = parseDateTime(record.gio_vao);
                const gioRa = parseDateTime(record.gio_ra);
                const ca = record.ca || '';
                const trangThaiDB = record.trang_thai;
                
                // Tính số giờ làm việc
                const soGio = gioVao && gioRa ? ((gioRa - gioVao) / 3600000).toFixed(2) : '-';
                
                // Lấy config của ca
                const configForCa = CA_CONFIG[ca];
                
                // Tính toán trạng thái sớm/muộn cho giờ vào
                let gioVaoText = gioVao ? gioVao.toLocaleTimeString('vi-VN') : '-';
                if (gioVao && configForCa) {
                    const [gioChuan, phutChuan] = configForCa.gioVaoChuan.split(':').map(Number);
                    const gioVaoChuan = new Date(gioVao);
                    gioVaoChuan.setHours(gioChuan, phutChuan, 0, 0);
                    
                    const chenhLechPhut = Math.round((gioVao - gioVaoChuan) / 60000);
                    if (chenhLechPhut < 0) {
                        gioVaoText += ` <span class="text-green-600">(sớm)</span>`;
                    } else if (chenhLechPhut > 0) {
                        gioVaoText += ` <span class="text-red-600">(muộn)</span>`;
                    }
                }
                
                // Tính toán trạng thái sớm/muộn cho giờ ra
                let gioRaText = gioRa ? gioRa.toLocaleTimeString('vi-VN') : '-';
                if (gioRa && configForCa) {
                    const [gioChuan, phutChuan] = configForCa.gioRaChuan.split(':').map(Number);
                    const gioRaChuan = new Date(gioRa);
                    gioRaChuan.setHours(gioChuan, phutChuan, 0, 0);
                    
                    const chenhLechPhut = Math.round((gioRa - gioRaChuan) / 60000);
                    if (chenhLechPhut < 0) {
                        gioRaText += ` <span class="text-red-600">(sớm)</span>`;
                    } else if (chenhLechPhut > 0) {
                        gioRaText += ` <span class="text-green-600">(muộn)</span>`;
                    }
                }
                
                // Tính toán đủ/thiếu giờ
                let soGioText = soGio;
                if (soGio !== '-' && configForCa) {
                    const [gioVaoH, gioVaoM] = configForCa.gioVaoChuan.split(':').map(Number);
                    const [gioRaH, gioRaM] = configForCa.gioRaChuan.split(':').map(Number);
                    const soGioChuan = (gioRaH * 60 + gioRaM - gioVaoH * 60 - gioVaoM) / 60;
                    const soGioThucTe = parseFloat(soGio);
                    
                    const chenhLech = (soGioThucTe - soGioChuan).toFixed(2);
                    if (chenhLech < 0) {
                        soGioText += ` <span class="text-red-600">(thiếu ${Math.abs(chenhLech)}h)</span>`;
                    } else if (chenhLech > 0) {
                        soGioText += ` <span class="text-green-600">(dư ${chenhLech}h)</span>`;
                    } else {
                        soGioText += ` <span class="text-gray-600">(đủ)</span>`;
                    }
                }
                
                // Logic xác định trạng thái
                let trangThai = '';
                let badgeColor = '';

                if (trangThaiDB != 2) {
                    if (!gioVao && !gioRa) {
                        const now = new Date();
                        const currentHour = now.getHours();
                        const currentMinute = now.getMinutes();
                        let withinShift = false;
                        if (configForCa) {
                            const [startHour, startMin] = configForCa.gioVaoChuan.split(':').map(Number);
                            const [endHour, endMin] = configForCa.gioRaChuan.split(':').map(Number);
                            const currentTimeMinutes = currentHour * 60 + currentMinute;
                            const shiftStartMinutes = startHour * 60 + startMin;
                            const shiftEndMinutes = endHour * 60 + endMin;
                            if (currentTimeMinutes >= (shiftStartMinutes - 30) && currentTimeMinutes <= (shiftEndMinutes + 30)) {
                                withinShift = true;
                            }
                        }
                        if (withinShift) {
                            trangThai = 'Chưa chấm';
                            badgeColor = 'bg-yellow-100 text-yellow-800';
                        } else {
                            trangThai = 'Chưa chấm (ngoài giờ)';
                            badgeColor = 'bg-gray-100 text-gray-800';
                        }
                    } else if (gioVao && !gioRa) {
                        trangThai = 'Đang làm';
                        badgeColor = 'bg-blue-100 text-blue-800';
                    } else if (gioVao && gioRa) {
                        trangThai = 'Đã chấm';
                        badgeColor = 'bg-green-100 text-green-800';
                    }
                }  else if (trangThaiDB == 2) {
                    trangThai = 'Đã duyệt nghỉ';
                    badgeColor = 'bg-gray-100 text-gray-800';
                }
                
                const standardTime = configForCa ? `${configForCa.gioVaoChuan} - ${configForCa.gioRaChuan}` : '';
                
                // Check if this record is from today
                const isToday = ngayCham && ngayCham.slice(0, 10) === today;
                const rowClass = isToday ? 'bg-blue-50 border-l-4 border-l-blue-500' : '';

                return `
                    <tr class="${rowClass}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="flex items-center gap-2">
                                ${isToday ? '<span class="flex h-2 w-2"><span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-blue-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span></span>' : ''}
                                <span class="${isToday ? 'font-bold text-blue-700' : ''}">
                                    ${ngayCham ? new Date(ngayCham).toLocaleDateString('vi-VN') : '-'}
                                    ${isToday ? '<span class="ml-1 text-xs text-blue-600">(Hôm nay)</span>' : ''}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="font-medium text-gray-900">${ca || '-'}</div>
                            ${standardTime ? `<div class="text-xs text-gray-500 mt-1">${standardTime}</div>` : ''}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${gioVaoText}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${gioRaText}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${soGioText}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${badgeColor}">
                                ${trangThai}
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');
            
            // Update pagination info
            updatePaginationInfo(startIndex, endIndex, shiftHistory.length);
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Chưa có lịch sử chấm công</td></tr>';
            document.getElementById('paginationContainer').classList.add('hidden');
        }
    }
    
    function updatePaginationInfo(start, end, total) {
        const container = document.getElementById('paginationContainer');
        
        if (total <= recordsPerPage) {
            container.classList.add('hidden');
            return;
        }
        
        container.classList.remove('hidden');
        
        document.getElementById('pageRangeStart').textContent = start + 1;
        document.getElementById('pageRangeEnd').textContent = end;
        document.getElementById('totalRecords').textContent = total;
        
        // Update button states
        const btnFirst = document.getElementById('btnFirstPage');
        const btnPrev = document.getElementById('btnPrevPage');
        const btnNext = document.getElementById('btnNextPage');
        const btnLast = document.getElementById('btnLastPage');
        
        btnFirst.disabled = currentPage === 1;
        btnPrev.disabled = currentPage === 1;
        btnNext.disabled = currentPage === totalPages;
        btnLast.disabled = currentPage === totalPages;
        
        // Render page numbers
        renderPageNumbers();
    }
    
    function renderPageNumbers() {
        const container = document.getElementById('pageNumbers');
        const maxVisible = 5;
        let pages = [];
        
        if (totalPages <= maxVisible) {
            // Show all pages
            for (let i = 1; i <= totalPages; i++) {
                pages.push(i);
            }
        } else {
            // Show subset with ellipsis
            if (currentPage <= 3) {
                pages = [1, 2, 3, 4, '...', totalPages];
            } else if (currentPage >= totalPages - 2) {
                pages = [1, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
            } else {
                pages = [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages];
            }
        }
        
        container.innerHTML = pages.map(page => {
            if (page === '...') {
                return '<span class="px-3 py-2 text-sm text-gray-500">...</span>';
            }
            
            const isActive = page === currentPage;
            const btnClass = isActive 
                ? 'px-3 py-2 text-sm font-bold text-white bg-indigo-600 border border-indigo-600 rounded-lg'
                : 'px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all';
            
            return `<button class="${btnClass}" onclick="goToPage(${page})">${page}</button>`;
        }).join('');
    }
    
    // Make goToPage globally accessible
    window.goToPage = function(page) {
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        renderHistoryPage();
    };

    // Event handlers
    // New: arm the system to start recording when face appears
    btnCheckin?.addEventListener('click', async (e) => {
        e.preventDefault();
        armedAction = 'checkin';
        // show spinner in button immediately
        setBtnLoading(btnCheckin, true, 'Đang thu dữ liệu...');
        if (armedTimeoutId) clearTimeout(armedTimeoutId);
        armedTimeoutId = setTimeout(() => {
            armedAction = null; armedTimeoutId = null;
            setBtnLoading(btnCheckin, false);
            // if (statusContent) statusContent.innerHTML = '<p class="text-gray-600">Hết thời gian chờ. Vui lòng nhấn lại Check In.</p>';
        }, ARM_TIMEOUT_MS);
        // if (statusContent) statusContent.innerHTML = '<p class="text-blue-600">Đang chờ khuôn mặt để thực hiện Check In — bạn có 15s</p>';
    });
    btnCheckout?.addEventListener('click', async (e) => {
        e.preventDefault();
        armedAction = 'checkout';
        setBtnLoading(btnCheckout, true, 'Đang thu dữ liệu...');
        if (armedTimeoutId) clearTimeout(armedTimeoutId);
        armedTimeoutId = setTimeout(() => {
            armedAction = null; armedTimeoutId = null;
            setBtnLoading(btnCheckout, false);
            // if (statusContent) statusContent.innerHTML = '<p class="text-gray-600">Hết thời gian chờ. Vui lòng nhấn lại Check Out.</p>';
        }, ARM_TIMEOUT_MS);
        // if (statusContent) statusContent.innerHTML = '<p class="text-blue-600">Đang chờ khuôn mặt để thực hiện Check Out — bạn có 15s</p>';
    });

    // camera select apply
    btnSelectCamera?.addEventListener('click', async (e) => {
        e.preventDefault();
        await startCameraWithDevice(cameraSelect.value);
    });
    
    // Event listeners for pagination buttons
    document.getElementById('btnFirstPage')?.addEventListener('click', () => goToPage(1));
    document.getElementById('btnPrevPage')?.addEventListener('click', () => goToPage(currentPage - 1));
    document.getElementById('btnNextPage')?.addEventListener('click', () => goToPage(currentPage + 1));
    document.getElementById('btnLastPage')?.addEventListener('click', () => goToPage(totalPages));
    
    // Refresh button should reset to page 1
    document.getElementById('btnRefresh')?.addEventListener('click', () => {
        currentPage = 1;
        loadHistory();
    });

    // init flow
    checkRegistrationStatus();
    loadHistory();
    // populate camera list as well (if registration ok init called populate too)
    populateCameraList();

    // cleanup
    window.addEventListener('beforeunload', () => {
        if (stream) stream.getTracks().forEach(t => t.stop());
    });
});
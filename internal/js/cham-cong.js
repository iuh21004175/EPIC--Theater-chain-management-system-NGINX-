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

    // new UI controls (insert if not present)
    let cameraSelect = document.getElementById('cameraSelect');
    let btnSelectCamera = document.getElementById('btnSelectCamera');
    const btnCheckin = document.getElementById('btnCheckin');
    const btnCheckout = document.getElementById('btnCheckout');
    if (!cameraSelect) {
        const wrap = document.createElement('div');
        wrap.className = 'p-3 flex items-end justify-between space-x-3 bg-gray-50';
        wrap.innerHTML = `
            <div class="flex-1">
                <label for="cameraSelect" class="block text-xs text-gray-600">Chọn camera</label>
                <select id="cameraSelect" class="mt-1 w-full border  rounded px-2 py-1 text-sm"><option value="">Tự động</option></select>
            </div>
            <div><button id="btnSelectCamera" class="px-3 py-2 bg-blue-600 text-white rounded text-sm">Sử dụng</button></div>`;
        cameraSection.insertBefore(wrap, cameraSection.firstChild);
        cameraSelect = document.getElementById('cameraSelect');
        btnSelectCamera = document.getElementById('btnSelectCamera');
    }
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
                const formData = new FormData();
                formData.append('video', blob, `face_record_${Date.now()}.webm`);
                formData.append('loai', action);
                formData.append('wifiTen', cameraSection.dataset.ten);
                formData.append('token', await getTokenApi());
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
                throw new Error(`Không thể kết nối tới Server GPS.\nVui lòng đảm bảo:\n- Bạn đã kết nối đúng wifi\n- Router đang hoạt động bình thường\n- Liên hệ quản lý rạp nếu vấn đề vẫn tiếp tục.`);
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

        try {
            // pass video element and timestamp (as in registration file)
            const results = await detector.detectForVideo(video, performance.now());
            const detections = results?.detections || [];
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
                if (statusContent) statusContent.innerHTML = '<p class="text-yellow-700">Nhiều hơn một khuôn mặt — chỉ chấm công khi chỉ còn 1 khuôn mặt</p>';
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
                            if (statusContent) statusContent.innerHTML = '<p class="text-gray-600">Khuôn mặt biến mất — thử lại</p>';
                        }
                    }
                }
                // update UI
                if (statusContent) statusContent.innerHTML = '<p class="text-gray-600">Không thấy khuôn mặt — vui lòng quay lại khung hình</p>';
            }
        } catch (err) {
            console.warn('detect error', err);
        }
        requestAnimationFrame(detectLoop);
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
            const tbody = document.getElementById('historyTableBody');
            if (result.success && result.data.length > 0) {
                tbody.innerHTML = result.data.map(record => {
                    const ngayCham = record.ngay || record.ngay_cham;
                    const gioVao = record.gio_vao ? new Date(record.gio_vao) : null;
                    const gioRa = record.gio_ra ? new Date(record.gio_ra) : null;
                    const soGio = gioVao && gioRa ? ((gioRa - gioVao) / 3600000).toFixed(2) : '-';
                    const trangThai = record.trang_thai || 'Đúng giờ';
                    const badgeColor = {
                        'Đúng giờ': 'bg-green-100 text-green-800',
                        'Muộn': 'bg-red-100 text-red-800',
                        'Sớm': 'bg-blue-100 text-blue-800',
                        'Nghỉ': 'bg-gray-100 text-gray-800'
                    }[trangThai] || 'bg-gray-100 text-gray-800';
                    return `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${ngayCham ? new Date(ngayCham).toLocaleDateString('vi-VN') : '-'}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${gioVao ? gioVao.toLocaleTimeString('vi-VN') : '-'}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${gioRa ? gioRa.toLocaleTimeString('vi-VN') : '-'}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${soGio}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${badgeColor}">
                                    ${trangThai}
                                </span>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                document.getElementById('historyTableBody').innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Chưa có lịch sử chấm công</td></tr>';
            }
        } catch (err) {
            console.error('Error loading history:', err);
        }
    }

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
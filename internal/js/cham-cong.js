// Ensure import matches registration page
import { FaceDetector, FilesetResolver } from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest";
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
    
    console.log('🔍 Button elements check:');
    console.log('  - btnCheckin:', btnCheckin ? '✓ Found' : '❌ NOT FOUND');
    console.log('  - btnCheckout:', btnCheckout ? '✓ Found' : '❌ NOT FOUND');
    console.log('  - btnCheckin disabled:', btnCheckin?.disabled);
    console.log('  - btnCheckout disabled:', btnCheckout?.disabled);
    
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
    const TARGET_WIDTH = 1280;
    const TARGET_HEIGHT = 720;

    // Track armed action for button state
    let armedAction = null; // 'checkin' | 'checkout' | null
    
    // Track processing state for display in bounding box
    let isProcessing = false;
    let processingAction = null; // 'checkin' | 'checkout' | null

    // Store current employee ID from registration status
    let currentNvId = null;

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
        if (detector) {
            console.log('FaceDetector already initialized');
            return true;
        }
        try {
            console.log('🔄 Initializing MediaPipe FaceDetector...');
            console.log('Loading FilesetResolver from CDN...');
            
            const vision = await FilesetResolver.forVisionTasks(
                "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest/wasm"
            );
            console.log('✓ FilesetResolver loaded successfully');
            
            console.log('Creating FaceDetector instance...');
            detector = await FaceDetector.createFromOptions(vision, {
                baseOptions: {
                    modelAssetPath: "https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/1/blaze_face_short_range.tflite",
                    delegate: "CPU"
                },
                runningMode: "VIDEO",
                minDetectionConfidence: 0.5
            });
            console.log('✓ FaceDetector ready - running on CPU (this is expected)');
            console.log('Detector object:', detector);
            return true;
        } catch (err) {
            console.error('❌ Failed to init FaceDetector:', err);
            console.error('Error details:', err.message, err.stack);
            alert('Không thể khởi tạo hệ thống nhận diện khuôn mặt.\n\nLỗi: ' + err.message + '\n\nVui lòng tải lại trang.');
            detector = null;
            return false;
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

    // ĐÃ BỎ HÀM CROP VÀ RESIZE - Gửi toàn bộ canvas gốc lên server

    // Capture frame and send to .NET server for face recognition
    async function captureAndProcess(action) {
        console.log('═══════════════════════════════════════════════');
        console.log('🚀 captureAndProcess STARTED');
        console.log('Action:', action);
        console.log('Stream:', !!stream, 'isCapturing:', isCapturing, 'detector:', !!detector);
        
        if (!detector) {
            console.error('❌ Detector is not initialized!');
            alert('Hệ thống nhận diện khuôn mặt chưa sẵn sàng.\n\nVui lòng tải lại trang và đợi thông báo "Hệ thống sẵn sàng".');
            // Try to reinitialize
            console.log('Attempting to reinitialize detector...');
            const success = await initDetector();
            if (!success || !detector) {
                console.error('❌ Reinitialization failed');
                return;
            }
            console.log('✓ Detector reinitialized successfully');
        }
        if (!stream || !isCapturing) {
            console.error('❌ Camera not ready - stream:', !!stream, 'isCapturing:', isCapturing);
            alert('Camera chưa được khởi động. Vui lòng thử lại.');
            return;
        }
        
        console.log('✓ Starting processing...');
        isProcessing = true;
        processingAction = action;
        
        // Find current shift
        const currentShift = getCurrentShift();
        
        // Check if shift is approved for leave
        if (currentShift && currentShift.trang_thai == 2) {
            isProcessing = false;
            processingAction = null;
            alert('Ca làm việc này đã được duyệt nghỉ. Không thể chấm công.');
            return;
        }
        
        const wifiIp = cameraSection.dataset.ip;
        const serverPort = cameraSection.dataset.port || '5000';
        currentNvId = cameraSection.dataset.idnhanvien || currentNvId;
        console.log('🔍 Server config check:');
        console.log('  - wifiIp:', wifiIp);
        console.log('  - serverPort:', serverPort);
        console.log('  - currentNvId:', currentNvId);
        console.log('  - cameraSection.dataset:', cameraSection.dataset);
        
        if (!wifiIp) {
            console.error('❌ No wifiIp found!');
            isProcessing = false;
            processingAction = null;
            alert('Không tìm thấy thông tin server chấm công. Vui lòng liên hệ quản lý.');
            return;
        }
        
        if (!currentNvId) {
            console.error('❌ No nv_id found!');
            isProcessing = false;
            processingAction = null;
            alert('Không tìm thấy thông tin nhân viên. Vui lòng tải lại trang.');
            return;
        }
        
        // Map action to loai: 'checkin' -> 'vao', 'checkout' -> 'ra'
        const loai = action === 'checkin' ? 'vao' : 'ra';
        const apiUrl = `http://${wifiIp}:${serverPort}/extract-face`;
        console.log('📍 API URL:', apiUrl);
        console.log('📍 Loai:', loai);
        
        try {
            const maxApiAttempts = 7; // Tối đa 7 lần gọi API
            let apiAttemptCount = 0;
            let successResult = null;
            
            console.log('🔁 Starting API retry loop (max 7 attempts)...');
            
            // Vòng lặp gọi API tối đa 7 lần
            while (apiAttemptCount < maxApiAttempts && !successResult) {
                apiAttemptCount++;
                console.log(`\n🔄 ═══ API ATTEMPT ${apiAttemptCount}/${maxApiAttempts} ═══`);
                
                try {
                    // Bước 1: Chờ phát hiện đúng 1 khuôn mặt với chất lượng tốt (MediaPipe)
                    let detection = null;
                    let faceDetectAttempts = 0;
                    const maxFaceDetectAttempts = 30; // Tối đa 3 giây cho mỗi lần detect (30 x 100ms)
                    console.log(`  🔍 Looking for face (max ${maxFaceDetectAttempts} detection attempts)...`);
                    
                    while (!detection && faceDetectAttempts < maxFaceDetectAttempts) {
                        faceDetectAttempts++;
                        
                        // Log every 10 attempts
                        if (faceDetectAttempts % 10 === 0) {
                            console.log(`    ⏱️ Face detection attempt ${faceDetectAttempts}/${maxFaceDetectAttempts}...`);
                        }
                        
                        // Capture current frame
                        const ok = captureFrameToCanvas();
                        if (!ok) {
                            await new Promise(resolve => setTimeout(resolve, 100));
                            continue;
                        }
                        
                        // Detect face with MediaPipe
                        const results = await detector.detectForVideo(video, performance.now());
                        const detections = results?.detections || [];
                        
                        // Check for exactly one face with good confidence (>= 0.7)
                        if (detections.length === 1) {
                            const confidence = detections[0].categories?.[0]?.score || 0;
                            if (confidence >= 0.7) {
                                detection = detections[0];
                                console.log(`✓ Face detected with confidence: ${(confidence * 100).toFixed(1)}%`);
                                break;
                            }
                        }
                        
                        // Chờ 100ms trước khi thử lại
                        await new Promise(resolve => setTimeout(resolve, 100));
                    }
                    
                    // Nếu không tìm thấy khuôn mặt trong lần này, thử lại API call tiếp theo
                    if (!detection) {
                        console.warn(`⚠️ Attempt ${apiAttemptCount}: No valid face detected after ${faceDetectAttempts} tries`);
                        console.warn(`  → Retrying API attempt...`);
                        continue;
                    }
                    
                    // Gửi TOÀN BỘ canvas gốc (KHÔNG CROP, KHÔNG RESIZE)
                    console.log('  📸 Capturing full canvas frame...');
                    console.log('  📏 Canvas size:', canvas.width, 'x', canvas.height, 'px');
                    
                    // Convert toàn bộ canvas to blob
                    console.log('  🖼️ Converting full canvas to JPEG blob...');
                    const blob = await new Promise((resolve, reject) => {
                        canvas.toBlob((b) => {
                            if (b) {
                                console.log('  ✓ Blob created, size:', (b.size / 1024).toFixed(2), 'KB');
                                resolve(b);
                            } else {
                                reject(new Error('Không thể chuyển đổi ảnh'));
                            }
                        }, 'image/jpeg', 0.95);
                    });
                    
                    // DEBUG: Tải ảnh xuống máy để kiểm tra trước khi gửi server
                    // const downloadUrl = URL.createObjectURL(blob);
                    // const downloadLink = document.createElement('a');
                    // downloadLink.href = downloadUrl;
                    // downloadLink.download = `chamcong_nv${currentNvId}_${loai}_${Date.now()}.jpg`;
                    // document.body.appendChild(downloadLink);
                    // downloadLink.click();
                    // document.body.removeChild(downloadLink);
                    // URL.revokeObjectURL(downloadUrl);
                    // console.log(`  📥 Downloaded face image for debugging`);
                    
                    // Bước 4: Gửi lên API
                    console.log('  📦 Preparing FormData...');
                    const formData = new FormData();
                    formData.append('image', blob, `face_${Date.now()}.jpg`);
                    formData.append('loai', loai);
                    formData.append('nv_id', currentNvId);
                    
                    console.log('  👤 FormData prepared:');
                    console.log('    - image:', blob.size, 'bytes');
                    console.log('    - loai:', loai);
                    console.log('    - nv_id:', currentNvId);
                    
                    // Add optional id_phancong if current shift is found
                    if (currentShift && currentShift.id) {
                        formData.append('id_phancong', currentShift.id);
                        console.log('    - id_phancong:', currentShift.id);
                    }
                    
                    console.log(`  📤 Sending POST request to:`, apiUrl);
                    console.log(`  📤 Parameters: loai=${loai}`);
                    
                    const fetchStartTime = Date.now();
                    const extractResponse = await fetch(apiUrl, {
                        method: 'POST',
                        body: formData
                    });
                    const fetchDuration = Date.now() - fetchStartTime;
                    console.log(`  ⏱️ API response received in ${fetchDuration}ms`);
                    console.log(`  📊 Status: ${extractResponse.status} ${extractResponse.statusText}`);
                    
                    if (!extractResponse.ok) {
                        console.warn(`  ⚠️ API returned error status ${extractResponse.status}`);
                        const errorText = await extractResponse.text();
                        console.warn(`  ⚠️ Error response:`, errorText);
                        console.warn(`  → Retrying...`);
                        continue;
                    }
                    
                    const result = await extractResponse.json();
                    console.log(`  📥 API JSON response:`, result);
                    
                    // Bước 5: Kiểm tra nếu success = true thì dừng vòng lặp
                    if (result.success === true) {
                        successResult = result;
                        console.log(`  ✅ ═══ RECOGNITION SUCCESSFUL on attempt ${apiAttemptCount} ═══`);
                        console.log(`  👤 Employee:`, result.employeeName || 'N/A');
                        console.log(`  💬 Message:`, result.message || 'N/A');
                        break;
                    } else {
                        console.warn(`  ⚠️ Recognition failed: ${result.message || 'Unknown error'}`);
                        console.warn(`  → Retrying...`);
                    }
                    
                } catch (attemptError) {
                    console.error(`  ❌ ERROR on attempt ${apiAttemptCount}:`);
                    console.error(`  ❌ Error type:`, attemptError.name);
                    console.error(`  ❌ Error message:`, attemptError.message);
                    console.error(`  ❌ Stack:`, attemptError.stack);
                    console.error(`  → Continuing to next attempt...`);
                    // Continue to next attempt
                }
                
                // Chờ một chút trước khi thử lại (tránh spam API)
                if (apiAttemptCount < maxApiAttempts && !successResult) {
                    await new Promise(resolve => setTimeout(resolve, 500));
                }
            }
            
            console.log('\n🏁 FINAL RESULT:');
            console.log('═══════════════════════════════════════════════');
            isProcessing = false;
            processingAction = null;
            
            // Hiển thị kết quả
            if (successResult) {
                console.log('✅ SUCCESS - Recognition completed');
                console.log('Employee:', successResult.employeeName);
                console.log('Message:', successResult.message);
                alert(`✓ ${successResult.message || (action === 'checkin' ? 'Check In thành công!' : 'Check Out thành công!')}
${successResult.employeeName || ''}`);
                await loadHistory();
            } else {
                console.error('❌ FAILED - No successful recognition after', maxApiAttempts, 'attempts');
                alert(`✗ Chấm công không thành công sau ${maxApiAttempts} lần thử.\n\nVui lòng đảm bảo:\n- Khuôn mặt rõ ràng, không bị che\n- Ánh sáng đầy đủ\n- Chỉ có 1 người trong khung hình\n\nHoặc liên hệ quản lý nếu vấn đề vẫn tiếp diễn.`);
            }
            console.log('═══════════════════════════════════════════════');
            
        } catch (err) {
            console.error('\n💥 FATAL ERROR in captureAndProcess:');
            console.error('Error name:', err.name);
            console.error('Error message:', err.message);
            console.error('Error stack:', err.stack);
            console.error('═══════════════════════════════════════════════');
            isProcessing = false;
            processingAction = null;
            alert(err.message || 'Lỗi khi xử lý chấm công');
        }
    }

    // main detection loop
    // detection loop: guard before calling detector
    let loopStarted = false;
    async function detectLoop() {
        if (!loopStarted) {
            loopStarted = true;
            console.log('🔁 Detection loop started');
        }
        
        if (!detector || !stream || !isCapturing) { 
            requestAnimationFrame(detectLoop); 
            return; 
        }

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
                // Log occasionally to confirm detection is working
                if (Math.random() < 0.01) { // Log ~1% of frames
                    console.log(`✓ Detected ${detections.length} face(s)`);
                }
                detections.forEach((detection, index) => {
                    const bbox = detection.boundingBox;
                    if (bbox) {
                        // Calculate bounding box coordinates scaled to canvas size
                        const x = bbox.originX;
                        const y = bbox.originY;
                        const w = bbox.width;
                        const h = bbox.height;
                        
                        // Determine color based on face count and confidence
                        const confidence = detection.categories[0]?.score || 0;
                        const isGoodQuality = confidence >= 0.7;
                        const isReady = detections.length === 1 && isGoodQuality;
                        
                        // Draw rectangle around face with thick border
                        ctx.strokeStyle = isReady ? '#00ff00' : '#ff0000'; // Green when ready, red otherwise
                        ctx.lineWidth = 4;
                        ctx.strokeRect(x, y, w, h);
                        
                        // Draw label text (no background)
                        ctx.fillStyle = '#ffffff';
                        ctx.font = 'bold 16px Arial';
                        let label;
                        if (isProcessing && detections.length === 1) {
                            // Show processing status when capturing face
                            label = processingAction === 'checkin' ? 'Đang Check In...' : 'Đang Check Out...';
                        } else {
                            label = detections.length == 1 ? 'Sẵn sàng' : `Khuôn mặt ${index + 1}`;
                        }
                        ctx.fillText(label, x + 10, y - 10);
                        
                        // Draw confidence score in green
                        const confidencePercent = (confidence * 100).toFixed(1);
                        ctx.fillStyle = '#00ff00'; // Green color for percentage
                        ctx.font = '14px Arial';
                        ctx.fillText(`${confidencePercent}%`, x + 10, y + h + 20);
                    }
                });
            } // end if detections.length > 0
            
            if (detections.length === 1) {
                // face present
                if (!facePresent) {
                    facePresent = true;
                    faceStreakStart = Date.now();
                }
                // Face detected - ready for capture when user clicks button
            } else if (detections.length > 1) {
                // multiple faces - not ready
                facePresent = false;
                faceStreakStart = 0;
            } else {
                // no face
                facePresent = false;
                faceStreakStart = 0;
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

    // Helper: Update status indicator UI
    function updateStatusIndicator(isReady) {
        const statusText = document.getElementById('statusText');
        const statusDot = document.getElementById('statusDot');
        const statusSpinner = document.getElementById('statusSpinner');
        
        if (!statusText || !statusDot || !statusSpinner) return;
        
        if (isReady) {
            // System ready
            statusSpinner.classList.add('hidden');
            statusDot.classList.remove('hidden');
            statusText.textContent = 'Sẵn sàng nhận diện';
        } else {
            // System loading
            statusSpinner.classList.remove('hidden');
            statusDot.classList.add('hidden');
            statusText.textContent = 'Đang tải hệ thống nhận diện';
        }
    }

    // Async initialization function to start camera and detector on page load
    async function initializeSystem() {
        console.log('🚀 Initializing face detection system...');
        
        // Show camera section
        cameraSection.classList.remove('hidden');
        
        // Show loading notification
        const initMessage = document.createElement('div');
        initMessage.className = 'fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
        initMessage.innerHTML = '<div class="flex items-center gap-2"><svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg><span>Đang khởi tạo...</span></div>';
        document.body.appendChild(initMessage);
        
        try {
            // Initialize detector
            console.log('Starting detector initialization...');
            const initSuccess = await initDetector();
            console.log('Detector initialization result:', initSuccess, 'detector:', !!detector);
            
            if (!initSuccess || !detector) {
                throw new Error('Không thể khởi tạo detector');
            }
            
            // Populate camera list
            await populateCameraList();
            
            // Start camera with default device
            await startCameraWithDevice(cameraSelect.value || '');
            
            // Start detection loop
            console.log('🎥 Starting detection loop...');
            detectLoop();
            
            // Update status indicator to ready
            updateStatusIndicator(true);
            
            // Success notification
            initMessage.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            initMessage.innerHTML = '<div class="flex items-center gap-2"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>✓ Hệ thống sẵn sàng!</span></div>';
            setTimeout(() => initMessage.remove(), 2000);
            
            console.log('✅ System initialized successfully');
        } catch (err) {
            console.error('❌ System initialization failed:', err);
            initMessage.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            initMessage.textContent = '❌ Lỗi khởi tạo! Vui lòng tải lại trang.';
            setTimeout(() => initMessage.remove(), 5000);
        }
    }

    // original checkRegistrationStatus + loadHistory functions (reuse existing)
    async function checkRegistrationStatus() {
        console.log('\n🔍 Checking registration status...');
        try {
            const response = await fetch(`${API_URL}/cham-cong/kiem-tra-dang-ky`);
            const result = await response.json();
            console.log('Registration check result:', result);
            
            // Store employee ID for later use
            if (result.data && result.data.nv_id) {
                currentNvId = result.data.nv_id;
                console.log('✓ Stored nv_id:', currentNvId);
            }
            
            if (result.success) {
                console.log('✅ User is registered - enabling buttons');
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
                // Enable check-in/out buttons
                if (btnCheckin) {
                    btnCheckin.disabled = false;
                    console.log('  ✓ btnCheckin enabled, disabled =', btnCheckin.disabled);
                } else {
                    console.warn('  ⚠️ btnCheckin not found!');
                }
                if (btnCheckout) {
                    btnCheckout.disabled = false;
                    console.log('  ✓ btnCheckout enabled, disabled =', btnCheckout.disabled);
                } else {
                    console.warn('  ⚠️ btnCheckout not found!');
                }
            } else {
                console.log('⚠️ User NOT registered - disabling buttons');
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
                // TEMPORARY: Allow testing without registration (comment out to enforce registration)
                console.log('  🔧 DEBUG MODE: Enabling buttons for testing despite no registration');
                if (btnCheckin) {
                    btnCheckin.disabled = false; // Changed from true to false
                    btnCheckin.title = 'TEST MODE - Registration check bypassed';
                    console.log('  ⚠️ btnCheckin enabled for testing, disabled =', btnCheckin.disabled);
                } else {
                    console.warn('  ⚠️ btnCheckin not found!');
                }
                if (btnCheckout) {
                    btnCheckout.disabled = false; // Changed from true to false
                    btnCheckout.title = 'TEST MODE - Registration check bypassed';
                    console.log('  ⚠️ btnCheckout enabled for testing, disabled =', btnCheckout.disabled);
                } else {
                    console.warn('  ⚠️ btnCheckout not found!');
                }
                
                /* ORIGINAL CODE - uncomment to enforce registration:
                if (btnCheckin) {
                    btnCheckin.disabled = true;
                    btnCheckin.title = 'Vui lòng đăng ký khuôn mặt trước';
                    console.log('  ❌ btnCheckin disabled, disabled =', btnCheckin.disabled);
                } else {
                    console.warn('  ⚠️ btnCheckin not found!');
                }
                if (btnCheckout) {
                    btnCheckout.disabled = true;
                    btnCheckout.title = 'Vui lòng đăng ký khuôn mặt trước';
                    console.log('  ❌ btnCheckout disabled, disabled =', btnCheckout.disabled);
                } else {
                    console.warn('  ⚠️ btnCheckout not found!');
                }
                */
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
    console.log('📝 Setting up event handlers...');
    
    if (btnCheckin) {
        console.log('✓ Adding click handler to btnCheckin');
        btnCheckin.addEventListener('click', async (e) => {
            console.log('\n🖱️ BUTTON CHECKIN CLICKED!');
            console.log('Button disabled state:', btnCheckin.disabled);
            e.preventDefault();
            setBtnLoading(btnCheckin, true, 'Đang chụp và xử lý...');
            armedAction = 'checkin';
        
            
            console.log('Calling captureAndProcess("checkin")...');
            await captureAndProcess('checkin');
            console.log('captureAndProcess("checkin") completed');
            
            armedAction = null;
            setBtnLoading(btnCheckin, false);
        });
    } else {
        console.error('❌ btnCheckin element not found!');
    }
    
    if (btnCheckout) {
        console.log('✓ Adding click handler to btnCheckout');
        btnCheckout.addEventListener('click', async (e) => {
            console.log('\n🖱️ BUTTON CHECKOUT CLICKED!');
            console.log('Button disabled state:', btnCheckout.disabled);
            e.preventDefault();
            
            setBtnLoading(btnCheckout, true, 'Đang chụp và xử lý...');
            armedAction = 'checkout';
        
            
            console.log('Calling captureAndProcess("checkout")...');
            await captureAndProcess('checkout');
            console.log('captureAndProcess("checkout") completed');
            
            armedAction = null;
            setBtnLoading(btnCheckout, false);
        });
    } else {
        console.error('❌ btnCheckout element not found!');
    }

    // camera select apply
    btnSelectCamera?.addEventListener('click', async (e) => {
        e.preventDefault();
        await startCameraWithDevice(cameraSelect.value);
        // Ensure detection loop is running after camera change
        if (detector && stream && isCapturing) {
            detectLoop();
        }
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

    // init flow - always start camera and detector, then check registration
    // Set initial loading state
    updateStatusIndicator(false);
    
    initializeSystem();
    checkRegistrationStatus();
    loadHistory();
    // populate camera list as well (done in initializeSystem)
    // populateCameraList(); // removed - already called in initializeSystem

    // cleanup
    window.addEventListener('beforeunload', () => {
        if (stream) stream.getTracks().forEach(t => t.stop());
    });
});
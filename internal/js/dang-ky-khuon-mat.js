import { FaceDetector, FilesetResolver } from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest";

document.addEventListener('DOMContentLoaded', async () => {

  // Khởi tạo MediaPipe
  const vision = await FilesetResolver.forVisionTasks(
    "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest/wasm"
  );

  // Create detector with baseOptions.modelAssetPath
  const faceDetector = await FaceDetector.createFromOptions(vision, {
    baseOptions: {
      modelAssetPath: "https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/1/blaze_face_short_range.tflite",
      delegate: "CPU"
    },
    runningMode: "VIDEO",
    minDetectionConfidence: 0.5
  });

  const video = document.getElementById('video');
  const overlay = document.getElementById('overlay');
  const faceNotify = document.getElementById('faceNotify');
  const btnStart = document.getElementById('btnStartCapture');
  const cameraSelect = document.getElementById('cameraSelect');
  const btnSelectCamera = document.getElementById('btnSelectCamera');
  const ctx = overlay.getContext('2d', { willReadFrequently: true });

  // target resolution (1280x720)
  const TARGET_WIDTH = 1280;
  const TARGET_HEIGHT = 720;

  let isCapturing = false;
  let captureStartTime = null;
  const CAPTURE_DURATION = 10000; // 10 giây
  const TARGET_IMAGES = 5; // ĐÃ GIẢM TỪ 30 XUỐNG 5
  let capturedImages = 0;
  let successfulUploads = 0;

  let stream = null;
  let detectionLoopStarted = false;
  
  // Get server config from HTML
  const serverIp = document.getElementById('cameraSection')?.dataset.ip || '';
  const serverPort = document.getElementById('cameraSection')?.dataset.port || '5000';
  const nvId = document.getElementById('cameraSection')?.dataset.idnhanvien || '';
  
  // Helper: Update status indicator UI
  function updateStatusIndicator(isReady) {
    const statusText = document.getElementById('statusText');
    const statusDot = document.getElementById('statusDot');
    const statusSpinner = document.getElementById('statusSpinner');
    
    if (!statusText || !statusDot || !statusSpinner) return;
    
    if (isReady) {
      statusSpinner.classList.add('hidden');
      statusDot.classList.remove('hidden');
      statusText.textContent = 'Sẵn sàng chụp';
    } else {
      statusSpinner.classList.remove('hidden');
      statusDot.classList.add('hidden');
      statusText.textContent = 'Đang tải hệ thống nhận diện';
    }
  }

  // Populate camera list
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
      console.warn('Không thể liệt kê camera:', err);
    }
  }

  // Stop existing stream
  function stopStream() {
    if (stream) {
      stream.getTracks().forEach(t => t.stop());
      stream = null;
    }
    video.srcObject = null;
  }

  // Start camera with optional deviceId
  async function startCameraWithDevice(deviceId = '') {
    stopStream();
    const videoConstraints = {
      width: { ideal: TARGET_WIDTH },
      height: { ideal: TARGET_HEIGHT },
      facingMode: 'user'
    };
    if (deviceId) videoConstraints.deviceId = { exact: deviceId };
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: videoConstraints, audio: false });
      video.srcObject = stream;
      await video.play();
      setupCanvasSize();
      
      // Start detection loop for real-time face detection
      if (!detectionLoopStarted) {
        detectionLoopStarted = true;
        realtimeDetectionLoop();
      }
      
      // show actual settings
      const track = stream.getVideoTracks()[0];
      const s = track.getSettings ? track.getSettings() : {};
      
      // ĐÃ XÓA: Không hiển thị thông báo "Nhấn 'Bắt đầu thu thập' để chụp..."
      faceNotify.innerText = ''; // Để trống
      
      // Enable capture button and update status
      btnStart.disabled = false;
      updateStatusIndicator(true);
    } catch (err) {
      console.error('Lỗi khi truy cập camera:', err);
      faceNotify.innerText = 'Không thể truy cập camera: ' + (err.message || err);
      updateStatusIndicator(false);
    }
  }

  function setupCanvasSize() {
    // Match cham-cong.js approach: no DPR scaling for consistent coordinate system
    overlay.width = TARGET_WIDTH;
    overlay.height = TARGET_HEIGHT;
    overlay.style.width = '100%';
    overlay.style.height = '100%';
  }

  // Real-time detection loop for visualization (before capture)
  async function realtimeDetectionLoop() {
    if (!stream) return;
    
    // Clear and redraw video frame
    ctx.clearRect(0, 0, overlay.width, overlay.height);
    ctx.drawImage(video, 0, 0, overlay.width, overlay.height);
    
    // Only detect if not capturing
    if (!isCapturing) {
      try {
        const detections = await faceDetector.detectForVideo(video, performance.now());
        
        if (detections && detections.detections.length > 0) {
          detections.detections.forEach((detection, index) => {
            const bbox = detection.boundingBox;
            if (bbox) {
              const x = bbox.originX;
              const y = bbox.originY;
              const w = bbox.width;
              const h = bbox.height;
              
              const confidence = detection.categories[0]?.score || 0;
              const isGoodQuality = confidence >= 0.7;
              const isReady = detections.detections.length === 1 && isGoodQuality;
              
              // Draw rectangle
              ctx.strokeStyle = isReady ? '#00ff00' : '#ff0000';
              ctx.lineWidth = 3;
              ctx.strokeRect(x, y, w, h);
              
              // ĐÃ THAY ĐỔI: Hiển thị "Đang tạo dữ liệu định danh"
              const label = ''
              
              ctx.fillStyle = isReady ? '#00ff00' : '#ff0000';
              ctx.font = 'bold 16px Arial';
              ctx.fillText(label, x, y - 5);
              
              // Draw confidence
              const confidencePercent = (confidence * 100).toFixed(1);
              ctx.fillStyle = '#00ff00';
              ctx.font = '14px Arial';
              ctx.fillText(`${confidencePercent}%`, x, y + h + 20);
            }
          });
        }
      } catch (err) {
        console.warn('Detection error:', err);
      }
    }
    
    requestAnimationFrame(realtimeDetectionLoop);
  }
  
  async function startCamera() {
    // Check server config
    if (!serverIp || !nvId) {
      faceNotify.innerText = '❌ Thiếu thông tin server hoặc nhân viên. Vui lòng tải lại trang.';
      return;
    }
    
    // Đếm ngược 3 giây trước khi bắt đầu
    try {
      for (let i = 3; i >= 1; i--) {
        faceNotify.innerText = `Chuẩn bị thu thập: ${i}`;
        await new Promise(resolve => setTimeout(resolve, 1000));
      }
      faceNotify.innerText = 'Bắt đầu!';
      startCapture();
    } catch (err) {
      console.error(err);
    }
  }

  function startCapture() {
    isCapturing = true;
    captureStartTime = Date.now();
    capturedImages = 0;
    successfulUploads = 0;
    faceNotify.innerText = `Đang tạo dữ liệu định danh: 0/${TARGET_IMAGES}`;
    btnStart.disabled = true;
    detectLoop();
  }

  async function finishCapture() {
    isCapturing = false;
    btnStart.disabled = false;
    
    if (successfulUploads >= TARGET_IMAGES) {
      faceNotify.innerText = `✅ Đã thu thập đủ ${successfulUploads}/${TARGET_IMAGES} ảnh. Đang xác nhận đăng ký...`;
      
      // Call API to confirm registration
      try {
        const apiUrl = document.body.dataset.url + '/api/dang-ky-khuon-mat';
        const response = await fetch(apiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ id_nhanvien: nvId })
        });
        
        const result = await response.json();
        
        if (result.success) {
          faceNotify.innerText = `✅ Hoàn tất! Đăng ký khuôn mặt thành công.`;
          console.log('✓ Face registration confirmed:', result);
        } else {
          faceNotify.innerText = `⚠️ Thu thập ảnh xong nhưng lỗi xác nhận: ${result.message}`;
          console.warn('⚠ Registration confirmation failed:', result.message);
        }
      } catch (error) {
        faceNotify.innerText = `⚠️ Thu thập ảnh xong nhưng lỗi kết nối server: ${error.message}`;
        console.error('❌ Registration confirmation error:', error);
      }
    } else {
      faceNotify.innerText = `⚠️ Chưa đủ ảnh! Chỉ thu thập được ${successfulUploads}/${TARGET_IMAGES} ảnh. Vui lòng thử lại.`;
    }
  }

  async function detectLoop() {
    if (!isCapturing) return;

    // Check if we already have enough successful uploads
    if (successfulUploads >= TARGET_IMAGES) {
      finishCapture();
      return;
    }

    // Clear and redraw video frame first
    ctx.clearRect(0, 0, overlay.width, overlay.height);
    ctx.drawImage(video, 0, 0, overlay.width, overlay.height);
    
    const detections = await faceDetector.detectForVideo(video, performance.now());

    // ĐÃ THAY ĐỔI: Vẽ label khi đang capture
    if (isCapturing && detections && detections.detections.length > 0) {
      detections.detections.forEach((detection, index) => {
        const bbox = detection.boundingBox;
        if (bbox) {
          const x = bbox.originX;
          const y = bbox.originY;
          const w = bbox.width;
          const h = bbox.height;
          
          const confidence = detection.categories[0]?.score || 0;
          const isGoodQuality = confidence >= 0.7;
          const isReady = detections.detections.length === 1 && isGoodQuality;
          
          // Vẽ hình chữ nhật bao quanh khuôn mặt
          ctx.strokeStyle = isReady ? '#00ff00' : '#ff0000'; 
          ctx.lineWidth = 3;
          ctx.strokeRect(x, y, w, h);
          
          // ĐÃ THAY ĐỔI: Hiển thị "Đang tạo dữ liệu định danh"
          const label = ''
          
          ctx.fillStyle = isReady ? '#00ff00' : '#ff0000';
          ctx.font = 'bold 16px Arial';
          ctx.fillText(label, x, y - 5);
          
          // Vẽ điểm tin cậy
          const confidencePercent = (confidence * 100).toFixed(1);
          ctx.fillStyle = '#00ff00';
          ctx.font = '14px Arial';
          ctx.fillText(`${confidencePercent}%`, x, y + h + 20);
        }
      });
    }

    // Check if we have exactly 1 face with good confidence
    if (detections && detections.detections.length === 1) {
      const detection = detections.detections[0];
      const confidence = detection.categories[0]?.score || 0;
      
      if (confidence >= 0.7 && successfulUploads < TARGET_IMAGES) {
        // Gửi toàn bộ overlay canvas (KHÔNG CROP, KHÔNG RESIZE)
        try {
          console.log(`📸 Capturing full overlay: ${overlay.width}x${overlay.height}px`);
          
          // Convert toàn bộ overlay canvas to blob
          const blob = await new Promise((resolve, reject) => {
            overlay.toBlob((b) => {
              if (b) resolve(b);
              else reject(new Error('Không thể tạo blob'));
            }, 'image/jpeg', 0.95);
          });
          
          capturedImages++;
          faceNotify.innerText = `Đang tạo dữ liệu định danh: ${successfulUploads}/${TARGET_IMAGES} (đang gửi...)`;
          
          // Wait for upload to complete before continuing
          const success = await uploadFaceImage(blob, capturedImages);
          if (success) {
            successfulUploads++;
            faceNotify.innerText = `Đang tạo dữ liệu định danh: ${successfulUploads}/${TARGET_IMAGES}`;
            console.log(`✓ Uploaded ${successfulUploads}/${TARGET_IMAGES}`);
          } else {
            faceNotify.innerText = `Đang tạo dữ liệu định danh: ${successfulUploads}/${TARGET_IMAGES} (lỗi upload, thử lại...)`;
            console.warn(`⚠ Upload failed, retrying... (${successfulUploads}/${TARGET_IMAGES})`);
          }
          
          // Wait 333ms before next capture (to get ~3 images per second)
          await new Promise(resolve => setTimeout(resolve, 333));
          
        } catch (err) {
          console.error('Capture error:', err);
        }
      } else if (confidence < 0.7) {
        faceNotify.innerText = `Độ tin cậy thấp (${(confidence * 100).toFixed(1)}%). Giữ khuôn mặt rõ ràng! [${successfulUploads}/${TARGET_IMAGES}]`;
      }
    } else if (detections && detections.detections.length > 1) {
      faceNotify.innerText = `❗Nhiều hơn một khuôn mặt — vui lòng chỉ để một khuôn mặt trong khung hình! [${successfulUploads}/${TARGET_IMAGES}]`;
    } else {
      faceNotify.innerText = `❗Không thấy khuôn mặt — vui lòng quay lại khung hình! [${successfulUploads}/${TARGET_IMAGES}]`;
    }

    const elapsed = Date.now() - captureStartTime;

    // Continue until we have enough successful uploads (no time limit)
    if (successfulUploads < TARGET_IMAGES) {
      requestAnimationFrame(detectLoop);
    } else {
      finishCapture();
    }
  }
  
  // Hàm tải ảnh xuống máy để kiểm tra
  function downloadFaceImage(blob, index) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `face_nv${nvId}_${index}_${Date.now()}.jpg`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    console.log(`📥 Downloaded face image #${index}`);
  }
  
  async function uploadFaceImage(blob, imageIndex) {
    const apiUrl = `http://${serverIp}:${serverPort}/register-face`;
    
    // Tải ảnh xuống máy để kiểm tra (DEBUG)
    // downloadFaceImage(blob, imageIndex);
    
    try {
      const formData = new FormData();
      formData.append('image', blob, `face_${Date.now()}.jpg`);
      formData.append('nv_id', nvId);
      
      const response = await fetch(apiUrl, {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        console.log('✓ Uploaded face image successfully');
        return true;
      } else {
        console.warn('⚠ Upload failed:', result.message);
        return false;
      }
    } catch (error) {
      console.error('❌ Upload error:', error);
      return false;
    }
  }

  // Hàm cập nhật tỉ lệ mẫu đạt chất lượng
  function updateQualityRatio(current, total) {
      document.getElementById('qualityRatio').textContent = `${current}/${total}`;
      const percent = (current / total) * 100;
      document.getElementById('qualityProgress').style.width = `${percent}%`;
  }

  // Hàm cập nhật tỉ lệ khuôn mặt
  function updateFaceRatio(current, total) {
      document.getElementById('faceRatio').textContent = `${current}/${total}`;
      const percent = (current / total) * 100;
      document.getElementById('faceProgress').style.width = `${percent}%`;
  }

  // Hàm cập nhật phát hiện giả mạo
  function updateSpoofDetection(current, total) {
      document.getElementById('faceDetected').textContent = `${current}/${total}`;
      const percent = (current / total) * 100;
      document.getElementById('spoofProgress').style.width = `${percent}%`;
  }

  // Hàm cập nhật chuyển đổi dữ liệu
  function updateDataTransfer(current, total) {
      document.getElementById('dataTransferred').textContent = `${current}/${total}`;
      const percent = (current / total) * 100;
      document.getElementById('dataProgress').style.width = `${percent}%`;
  }

  btnStart.addEventListener('click', startCamera);
  btnSelectCamera && btnSelectCamera.addEventListener('click', async (e) => {
    e.preventDefault();
    await startCameraWithDevice(cameraSelect.value);
  });

  // Initialize system: populate cameras and auto-start
  async function initializeSystem() {
    console.log('🚀 Initializing face registration system...');
    
    // Check server config
    if (!serverIp || !nvId) {
      console.error('❌ Missing server config or employee ID');
      faceNotify.innerText = '❌ Thiếu thông tin server hoặc nhân viên. Vui lòng tải lại trang.';
      updateStatusIndicator(false);
      return;
    }
    
    console.log('✓ Server config:', `${serverIp}:${serverPort}`);
    console.log('✓ Employee ID:', nvId);
    
    // Populate camera list
    await populateCameraList();
    
    // Auto-start camera with default device
    updateStatusIndicator(false);
    await startCameraWithDevice('');
    
    console.log('✓ Face registration system initialized');
  }
  
  // Start initialization
  initializeSystem();
});
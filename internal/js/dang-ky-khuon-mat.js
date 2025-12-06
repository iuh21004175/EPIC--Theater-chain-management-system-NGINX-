
import { FaceDetector, FilesetResolver } from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.0";

document.addEventListener('DOMContentLoaded', async () => {

  // Khởi tạo MediaPipe
  const vision = await FilesetResolver.forVisionTasks(
    "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision/wasm"
  );

  // Create detector with baseOptions.modelAssetPath
  const faceDetector = await FaceDetector.createFromOptions(vision, {
    baseOptions: {
      modelAssetPath: "https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/1/blaze_face_short_range.tflite"
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

  let mediaRecorder = null;
  let recordedChunks = [];
  let isCapturing = false;
  let captureStartTime = null;
  let pausedForNoFace = false;
  const CAPTURE_DURATION = 10000; // 10 giây

  let stream = null;

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
      // show actual settings
      const track = stream.getVideoTracks()[0];
      const s = track.getSettings ? track.getSettings() : {};
      faceNotify.innerText = `Kích thước camera: ${s.width || video.videoWidth}×${s.height || video.videoHeight} @ ${s.frameRate || '?'}fps`;
    } catch (err) {
      console.error('Lỗi khi truy cập camera:', err);
      faceNotify.innerText = 'Không thể truy cập camera: ' + (err.message || err);
    }
  }

  function setupCanvasSize() {
    // Match cham-cong.js approach: no DPR scaling for consistent coordinate system
    overlay.width = TARGET_WIDTH;
    overlay.height = TARGET_HEIGHT;
    overlay.style.width = '100%';
    overlay.style.height = '100%';
  }

  async function startCamera() {
    // compatibility wrapper: use selected camera
    const deviceId = cameraSelect ? cameraSelect.value : '';
    await startCameraWithDevice(deviceId);
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
    recordedChunks = [];
    faceNotify.innerText = 'Đang đếm ngược: 10s';
    btnStart.disabled = true;

    // Khởi tạo MediaRecorder
    const options = { mimeType: 'video/webm;codecs=vp9' };
    if (!MediaRecorder.isTypeSupported(options.mimeType)) {
      options.mimeType = 'video/webm';
    }

    mediaRecorder = new MediaRecorder(stream, options);

    mediaRecorder.ondataavailable = (event) => {
      if (event.data.size > 0) {
        recordedChunks.push(event.data);
      }
    };

    mediaRecorder.onstop = async () => {
      const blob = new Blob(recordedChunks, { type: 'video/webm' });
      const url = URL.createObjectURL(blob);
      
      // Tạo link download
      const a = document.createElement('a');
      a.href = url;
      a.download = `face_video_${Date.now()}.webm`;
      a.click();
      await fetchDangKyKhuonMat();
      fetch('h')
      console.log("Video blob:", blob);
      console.log("Video size:", (blob.size / 1024 / 1024).toFixed(2), "MB");
    };

    // Bắt đầu ghi
    mediaRecorder.start();
    detectLoop();
  }

  function finishCapture() {
    isCapturing = false;
    btnStart.disabled = false;
    
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      mediaRecorder.stop();
    }
    
    faceNotify.innerText = `Hoàn tất! Đang xử lý video...`;
  }

  async function detectLoop() {
    if (!isCapturing) return;

    // Clear and redraw video frame first
    ctx.clearRect(0, 0, overlay.width, overlay.height);
    ctx.drawImage(video, 0, 0, overlay.width, overlay.height);
    
    const detections = await faceDetector.detectForVideo(video, performance.now());

    if (detections && detections.detections.length > 0) {
      // Draw bounding boxes for all detected faces (similar to cham-cong.js)
      detections.detections.forEach((detection, index) => {
        const bbox = detection.boundingBox;
        if (bbox) {
          const x = bbox.originX;
          const y = bbox.originY;
          const w = bbox.width;
          const h = bbox.height;
          
          // Draw rectangle around face
          ctx.strokeStyle = detections.detections.length === 1 ? '#00ff00' : '#ff0000'; // Green for 1 face, red for multiple
          ctx.lineWidth = 3;
          ctx.strokeRect(x, y, w, h);
          
          // Draw label
          const label = detections.detections.length === 1 ? 'Khuôn mặt' : `Khuôn mặt ${index + 1}`;
          ctx.fillStyle = detections.detections.length === 1 ? '#00ff00' : '#ff0000';
          ctx.font = '16px Arial';
          ctx.fillText(label, x, y - 5);
          
          // Draw confidence score
          const confidence = (detection.categories[0]?.score * 100).toFixed(1);
          ctx.fillText(`${confidence}%`, x, y + h + 20);
        }
      });
    }

    if (detections && detections.detections.length == 1) {
      pausedForNoFace = false;

      // Kiểm tra chất lượng frame (giả sử bạn đã load WASM module)
      const imageData = ctx.getImageData(0, 0, overlay.width, overlay.height);
      
      // No quality checks: keep detecting and drawing only
    }
    else if(detections && detections.detections.length > 1){
      pausedForNoFace = true;
      faceNotify.innerText = "❗Nhiều hơn một khuôn mặt — vui lòng chỉ để một khuôn mặt trong khung hình!";
    }
    else {
      pausedForNoFace = true;
      faceNotify.innerText = "❗Không thấy khuôn mặt — vui lòng quay lại khung hình!";
      
      // Tạm dừng ghi video khi không thấy mặt
      if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.pause();
      }
    }

    if (!pausedForNoFace) {
      // Tiếp tục ghi nếu đang tạm dừng
      if (mediaRecorder && mediaRecorder.state === 'paused') {
        mediaRecorder.resume();
      }

      const elapsed = Date.now() - captureStartTime;
      const remaining = Math.ceil((CAPTURE_DURATION - elapsed) / 1000);

      if (remaining > 0) {
        faceNotify.innerText = `Giữ nguyên khuôn mặt trong: ${remaining}s`;
        requestAnimationFrame(detectLoop);
      } else {
        finishCapture();
      }
    } else {
      requestAnimationFrame(detectLoop);
    }
  }
  async function fetchDangKyKhuonMat(){
      // 1. Ghép chunks thành Blob
      const blob = new Blob(recordedChunks, { type: 'video/webm' });

      // 2. Gửi Blob lên server bằng FormData
      const formData = new FormData();
      formData.append('video', blob, 'video.webm');  // 'video.webm' là tên file
      try {
        const response = await fetch(`${document.body.dataset.url}/api/cham-cong/dang-ky-khuon-mat`, {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        if(result.success){
          faceNotify.innerText = 'Đăng ký khuôn mặt thành công!';
        } else {
          faceNotify.innerText = 'Đăng ký khuôn mặt thất bại: ' + (result.message || 'Lỗi không xác định');
        }
      } catch (error) {
        faceNotify.innerText = 'Lỗi khi gửi video đăng ký khuôn mặt: ' + error.message;
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

  // init camera list on load
  await populateCameraList();
  // optionally auto-start default camera preview
  // await startCameraWithDevice(); // uncomment to auto-start preview
});
// Dưới đây là ví dụ thô để đánh giá “OK / NOT_OK”:

// brightness (0–255): OK nếu trong [50, 200].

// Nếu <50 → quá tối; >230 → quá sáng / blown.

// dark_ratio < 0.2 và bright_ratio < 0.05 → OK.

// sharpness (variance of Laplacian): giá trị phụ thuộc scale, với ảnh downscale 320×240,

// sharpness > 1000 → rõ nét; 200–1000 trung bình; <200 → mờ. (thử điều chỉnh theo mô tản)

// noise (high-freq variance): nhỏ là tốt; giá trị lớn nghĩa nhiều nhiễu. So sánh noise vs sharpness: nếu noise quá lớn và sharpness nhỏ → ảnh nhiễu+mờ.

// rms_contrast: nếu < 10 → tương phản thấp.
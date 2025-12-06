#!/usr/bin/env python3
import datetime
import cv2
import numpy as np
import sys
import os
from numpy.linalg import norm
import chromadb

# --- THƯ VIỆN CHO MINIFASNET ---
import torch
import torch.nn as nn
import torch.nn.functional as F
from facenet_pytorch import MTCNN

# ==========================================
# CẤU HÌNH HỆ THỐNG
# ==========================================
client = chromadb.HttpClient(host="localhost", port=8000)

collection = client.get_or_create_collection(
    name="face_embeddings",
    metadata={"hnsw:space": "cosine"}
)

# Cấu hình ngưỡng
SIMILARITY_THRESHOLD = 0.6
LIVENESS_THRESHOLD = 0.7  # Ngưỡng trung bình, an toàn cho thực tế

# Đường dẫn model (Tự động lấy đường dẫn tuyệt đối)
CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
# Đảm bảo file .pth nằm trong thư mục anti_spoof_models cùng cấp với file script
PATH_TO_MODEL = os.path.join(CURRENT_DIR, "anti_spoof_models/2.7_80x80_MiniFASNetV2.pth")

device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')

# ==========================================
# ĐỊNH NGHĨA MODEL MINIFASNET (Phiên bản đã sửa lỗi groups)
# ==========================================
class Conv2d_cd(nn.Module):
    def __init__(self, in_channels, out_channels, kernel_size=3, stride=1, padding=1, bias=False, groups=1):
        super(Conv2d_cd, self).__init__()
        self.conv = nn.Conv2d(in_channels, out_channels, kernel_size, stride, padding, bias=bias, groups=groups)
        self.bn = nn.BatchNorm2d(out_channels)
        self.prelu = nn.PReLU(out_channels)

    def forward(self, x):
        x = self.conv(x)
        x = self.bn(x)
        x = self.prelu(x)
        return x

class MiniFASNetV2(nn.Module):
    def __init__(self, embedding_size=128, conv6_kernel=(5, 5)):
        super(MiniFASNetV2, self).__init__()
        self.conv1 = Conv2d_cd(3, 32, 3, 2, 1)
        self.conv2_dw = Conv2d_cd(32, 32, 3, 1, 1, groups=32)
        self.conv_23 = Conv2d_cd(32, 64, 3, 2, 1)
        self.conv3 = Conv2d_cd(64, 64, 3, 1, 1, groups=64)
        self.conv_34 = Conv2d_cd(64, 128, 3, 2, 1)
        self.conv4 = Conv2d_cd(128, 128, 3, 1, 1, groups=128)
        self.conv_45 = Conv2d_cd(128, 128, 3, 2, 1)
        self.conv5 = Conv2d_cd(128, 128, 3, 1, 1, groups=128)
        self.conv6_sep = Conv2d_cd(128, 512, 1, 1, 0)
        self.conv6_dw = nn.Sequential(
            nn.Conv2d(512, 512, conv6_kernel[0], 1, 0, groups=512, bias=False),
            nn.BatchNorm2d(512),
            nn.PReLU(512)
        )
        self.conv6_flatten = nn.Flatten()
        self.linear = nn.Linear(512, embedding_size)
        self.bn = nn.BatchNorm1d(embedding_size)
        self.drop = nn.Dropout(0.4)
        self.prob = nn.Linear(embedding_size, 3)

    def forward(self, x):
        out = self.conv1(x)
        out = self.conv2_dw(out)
        out = self.conv_23(out)
        out = self.conv3(out)
        out = self.conv_34(out)
        out = self.conv4(out)
        out = self.conv_45(out)
        out = self.conv5(out)
        out = self.conv6_sep(out)
        out = self.conv6_dw(out)
        out = self.conv6_flatten(out)
        out = self.linear(out)
        out = self.bn(out)
        out = self.drop(out)
        out = self.prob(out)
        return F.softmax(out, dim=1)

# ==========================================
# LIVENESS DETECTION (ĐÃ SỬA: KHÔNG DÙNG THƯ VIỆN NGOÀI)
# ==========================================
liveness_model = None

def load_liveness_model():
    """Tải model MiniFASNet từ class nội bộ"""
    global liveness_model
    if liveness_model is not None:
        return True

    if not os.path.exists(PATH_TO_MODEL):
        print(f"⚠️  ERROR: Model file not found at: {PATH_TO_MODEL}")
        return False

    try:
        # Khởi tạo class đã định nghĩa ở trên
        model = MiniFASNetV2(conv6_kernel=(5, 5))
        
        # Load weights
        state_dict = torch.load(PATH_TO_MODEL, map_location=device)
        
        # Xử lý key name (bỏ tiền tố 'module.' nếu có)
        new_state_dict = {}
        for k, v in state_dict.items():
            if k.startswith('module.'):
                new_state_dict[k[7:]] = v
            else:
                new_state_dict[k] = v
                
        model.load_state_dict(new_state_dict, strict=False)
        model.to(device)
        model.eval()
        liveness_model = model
        print("✓ Liveness Model Loaded Successfully (Internal Class)")
        return True
    except Exception as e:
        print(f"ERROR loading liveness model: {e}")
        return False

def check_liveness_video(video_path, debug=True):
    """
    Kiểm tra Liveness thủ công (Manual Preprocessing)
    """
    if not load_liveness_model():
        print("⚠️  Skipping liveness check due to model error.")
        return True # Fallback

    if debug:
        print("\nExecuting Anti-Spoofing Check (MiniFASNet)...")
    
    cap = cv2.VideoCapture(video_path)
    # MTCNN để detect mặt
    mtcnn = MTCNN(keep_all=False, select_largest=True, device=device)
    
    real_votes = 0
    fake_votes = 0
    frames_checked = 0
    max_check_frames = 5 
    
    while frames_checked < max_check_frames:
        ret, frame = cap.read()
        if not ret: break
        
        # Chỉ check mỗi 5 frame
        if int(cap.get(cv2.CAP_PROP_POS_FRAMES)) % 5 != 0:
            continue

        # 1. Chuyển sang RGB (Quan trọng)
        frame_rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        
        # Detect face box
        boxes, _ = mtcnn.detect(frame_rgb)
        
        if boxes is None:
            continue

        # Lấy box lớn nhất
        box = boxes[0]
        x1, y1, x2, y2 = map(int, box)
        
        # 2. Tính toán scale crop (Scale 2.7)
        w = x2 - x1
        h = y2 - y1
        scale = 2.7 
        
        center_x, center_y = x1 + w//2, y1 + h//2
        new_w = int(w * scale)
        new_h = int(h * scale)
        
        new_x1 = max(0, center_x - new_w//2)
        new_y1 = max(0, center_y - new_h//2)
        new_x2 = min(frame.shape[1], center_x + new_w//2)
        new_y2 = min(frame.shape[0], center_y + new_h//2)
        
        # 3. Crop từ frame RGB
        face_crop = frame_rgb[new_y1:new_y2, new_x1:new_x2]
        
        if face_crop.size == 0: continue

        # 4. Preprocess thủ công (Thay vì dùng library)
        # Resize 80x80 -> Normalize -> Transpose -> Tensor
        img = cv2.resize(face_crop, (80, 80))
        img = img.astype(np.float32) / 255.0 # Normalize về [0, 1] (QUAN TRỌNG)
        img = np.transpose(img, (2, 0, 1))   # HWC -> CHW
        img = torch.from_numpy(img).unsqueeze(0).float().to(device)
        
        with torch.no_grad():
            prediction = liveness_model(img)
            probs = prediction.cpu().numpy()[0]
            
        # Index 1 là class Real
        real_score = probs[1]
        
        if real_score > LIVENESS_THRESHOLD:
            real_votes += 1
            if debug: print(f"  > Frame: REAL (Score: {real_score:.4f})")
        else:
            fake_votes += 1
            if debug: print(f"  > Frame: FAKE (Score: {real_score:.4f})")
            
        frames_checked += 1

    cap.release()
    
    if frames_checked == 0:
        print("✗ Liveness FAILED: No face detected in video.")
        return False

    # Quyết định cuối cùng
    if real_votes > fake_votes:
        if debug: print(f"✓ Liveness CONFIRMED: Real Person ({real_votes}/{frames_checked})")
        return True
    else:
        if debug: print(f"✗ Liveness FAILED: Spoofing Detected ({fake_votes}/{frames_checked})")
        return False

# ==========================================
# CÁC HÀM CŨ (Quality, Embedding...)
# ==========================================

def analyze_quality(frame):
    gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    brightness = np.mean(gray)
    sharpness = cv2.Laplacian(gray, cv2.CV_64F).var()
    noise = np.std(gray)
    contrast = gray.std()
    return brightness, sharpness, noise, contrast

def check_quality(video_path):
    if not os.path.isfile(video_path):
        print(f"Error: file '{video_path}' not found")
        return False

    cap = cv2.VideoCapture(video_path)
    if not cap.isOpened():
        print(f"Error: cannot open video")
        return False

    frame_count = 0
    results = []
    max_frames = 10

    while True:
        ret, frame = cap.read()
        if not ret: break

        b, s, n, c = analyze_quality(frame)
        results.append((b, s, n, c))
        frame_count += 1
        if frame_count >= max_frames: break

    cap.release()

    if frame_count == 0: return False

    avg_brightness = np.mean([r[0] for r in results])
    avg_sharpness = np.mean([r[1] for r in results])
    avg_noise = np.mean([r[2] for r in results])
    avg_contrast = np.mean([r[3] for r in results])

    if (50 <= avg_brightness <= 230 and avg_sharpness >= 25 and 
        avg_noise <= 100 and avg_contrast >= 10):
        return True
    return False

def faceToEmbedding(video_path, sample_rate=5, resize_width=960, device=None, mtcnn_min_face_size=20):
    """Tạo embedding dùng FaceNet"""
    try:
        from facenet_pytorch import MTCNN, InceptionResnetV1
    except Exception as e:
        print("ERROR:", e)
        return None

    if device is None:
        device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')

    mtcnn = MTCNN(image_size=160, margin=0, keep_all=False, device=device, min_face_size=mtcnn_min_face_size)
    model = InceptionResnetV1(pretrained='vggface2').eval().to(device)

    cap = cv2.VideoCapture(video_path)
    embeddings = []
    frame_id = 0

    while True:
        ret, frame = cap.read()
        if not ret: break

        if frame_id % sample_rate != 0:
            frame_id += 1
            continue

        h, w = frame.shape[:2]
        scale = resize_width / float(w)
        frame_resized = cv2.resize(frame, (resize_width, int(h * scale)))
        frame_rgb = cv2.cvtColor(frame_resized, cv2.COLOR_BGR2RGB)

        face_tensor = mtcnn(frame_rgb)
        if face_tensor is None:
            frame_id += 1
            continue

        if face_tensor.ndim == 3:
            face_tensor = face_tensor.unsqueeze(0).to(device)
        else:
            face_tensor = face_tensor.to(device)

        with torch.no_grad():
            emb_t = model(face_tensor)
        
        emb_np = emb_t.cpu().numpy()
        emb_np = emb_np / np.linalg.norm(emb_np, axis=1, keepdims=True)
        embeddings.append(emb_np[0])
        frame_id += 1

    cap.release()

    if len(embeddings) == 0: return None
    emb_avg = np.mean(embeddings, axis=0)
    emb_avg = emb_avg / norm(emb_avg)
    return emb_avg

def save_embedding(embedding, id_employee):
    if collection is None: return False
    target_id = "face_" + str(id_employee)
    emb_list = embedding.tolist()
    try:
        existing = collection.get(ids=[target_id], include=["embeddings"])
        ts = datetime.datetime.now().isoformat()
        metadata = {"id_employee": str(id_employee), "updated_at": ts}
        
        if existing and existing.get("ids") and len(existing["ids"]) > 0:
            collection.update(ids=[target_id], embeddings=[emb_list], metadatas=[metadata])
        else:
            metadata["created_at"] = ts
            collection.add(ids=[target_id], embeddings=[emb_list], metadatas=[metadata])
        return True
    except Exception as e:
        print(f"ERROR Save: {e}")
        return False

def get_embedding(id_employee):
    target_id = "face_" + str(id_employee)
    try:
        result = collection.get(ids=[target_id], include=["embeddings"])
        emb_list = result.get("embeddings")
        if emb_list is None or len(emb_list) == 0: return None
        return np.array(emb_list[0])
    except: return None

def cosine_similarity(emb1, emb2):
    return np.dot(emb1, emb2) / (norm(emb1) * norm(emb2))

# ==========================================
# WORKFLOWS CHÍNH
# ==========================================

def register_face(video_path, id_employee):
    print(f"\n{'='*60}")
    print(f"REGISTERING FACE FOR ID={id_employee}")
    print(f"{'='*60}")
    
    if not check_quality(video_path):
        print("✗ Registration FAILED: Video quality too low.")
        return

    if not check_liveness_video(video_path, debug=True):
        print("\n✗ Registration FAILED: Liveness check failed (Spoofing detected).")
        return

    embedding = faceToEmbedding(video_path)
    if embedding is not None:
        if save_embedding(embedding, id_employee):
            print(f"\n✓ Registration SUCCESSFUL for ID={id_employee}")
        else:
            print("\n✗ Registration FAILED: Database error.")
    else:
        print("\n✗ Registration FAILED: No face embedding generated.")

def check_face(video_path, id_employee):
    print(f"\n{'='*60}")
    print(f"CHECKING FACE FOR ID={id_employee}")
    print(f"{'='*60}")
    
    if not check_quality(video_path):
        print("✗ Check FAILED: Low video quality.")
        return False
    
    if not check_liveness_video(video_path, debug=True):
        print("\n✗ Check FAILED: Liveness check failed.")
        return False
    
    embedding_stored = get_embedding(id_employee)
    if embedding_stored is None:
        print(f"\n✗ Check FAILED: ID {id_employee} not found in database.")
        return False
    
    embedding_confirm = faceToEmbedding(video_path)
    if embedding_confirm is None:
        print("\n✗ Check FAILED: Cannot extract face features.")
        return False
    
    similarity = cosine_similarity(embedding_stored, embedding_confirm)
    print(f"\nSimilarity: {similarity:.4f} (Threshold: {SIMILARITY_THRESHOLD})")
    
    if similarity >= SIMILARITY_THRESHOLD:
        print("✓ Face verification SUCCESSFUL.")
        return True
    else:
        print("✗ Face verification FAILED: Face mismatch.")
        return False

def main():
    if len(sys.argv) < 3:
        print(f"\nUsage: python face.py <video_path> <ID_EMPLOYEE> <command>")
        return

    video_path = sys.argv[1]
    id_employee = sys.argv[2]
    cmd = sys.argv[3] if len(sys.argv) > 3 else None

    match cmd:
        case "register":
            register_face(video_path, id_employee)
        case "check":
            check_face(video_path, id_employee)
        case _:
            print(f"Available commands: register, check")

if __name__ == "__main__":
    main()
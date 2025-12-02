#!/usr/bin/env python3
import datetime
import cv2
import numpy as np
import sys
import os
from numpy.linalg import norm
import chromadb

client = chromadb.HttpClient(host="localhost", port=8000)

collection = client.get_or_create_collection(
    name="face_embeddings",
    metadata={"hnsw:space": "cosine"}  # cosine similarity
)

# Ngưỡng độ tương đồng để xác nhận khuôn mặt (0.0-1.0, càng cao càng giống)
SIMILARITY_THRESHOLD = 0.6  # Điều chỉnh theo nhu cầu (0.5-0.7 thường phù hợp)


def analyze_quality(frame):
    """
    Tính các metric chất lượng ảnh cơ bản:
    - brightness: độ sáng trung bình
    - sharpness: variance of Laplacian
    - noise: độ lệch chuẩn
    - contrast: RMS contrast (std)
    """
    gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)

    brightness = np.mean(gray)
    sharpness = cv2.Laplacian(gray, cv2.CV_64F).var()
    noise = np.std(gray)
    contrast = gray.std()
    return brightness, sharpness, noise, contrast


def check_quality(video_path):
    """Kiểm tra chất lượng video"""
    if not os.path.isfile(video_path):
        print(f"Error: file '{video_path}' not found - {datetime.datetime.now()}")
        return False

    cap = cv2.VideoCapture(video_path)
    if not cap.isOpened():
        print(f"Error: cannot open video '{video_path}' - {datetime.datetime.now()}")
        return False

    frame_count = 0
    results = []
    max_frames = 10  # số frame muốn đọc

    # Tạo thư mục lưu frame đạt chuẩn
    base_dir = os.path.dirname(os.path.abspath(__file__))
    save_dir = os.path.join(base_dir, "frames_ok")
    os.makedirs(save_dir, exist_ok=True)

    while True:
        ret, frame = cap.read()
        if not ret:
            break

        brightness, sharpness, noise, contrast = analyze_quality(frame)
        results.append((frame_count, brightness, sharpness, noise, contrast))

        print(f"Frame {frame_count}: Brightness={brightness:.2f}, Sharpness={sharpness:.2f}, "
              f"Noise={noise:.2f}, Contrast={contrast:.2f} - {datetime.datetime.now()}")

        frame_count += 1
        if frame_count >= max_frames:
            break  # dừng sau 10 frame

    cap.release()
    print(f"Processed {frame_count} frames. - {datetime.datetime.now()}")

    if frame_count == 0:
        print("ERROR: No frames processed - {datetime.datetime.now()}")
        return False

    # Tính trung bình các metric
    avg_brightness = np.mean([r[1] for r in results])
    avg_sharpness = np.mean([r[2] for r in results])
    avg_noise = np.mean([r[3] for r in results])
    avg_contrast = np.mean([r[4] for r in results])

    print(f"\nAverage metrics over {frame_count} frames - {datetime.datetime.now()}:")
    print(f"Brightness: {avg_brightness:.2f}, Sharpness: {avg_sharpness:.2f}, "
          f"Noise: {avg_noise:.2f}, Contrast: {avg_contrast:.2f} - {datetime.datetime.now()}")

    # Ngưỡng đánh giá chất lượng
    BRIGHTNESS_MIN = 50
    BRIGHTNESS_MAX = 230
    SHARPNESS_MIN = 25
    NOISE_MAX = 100
    CONTRAST_MIN = 10

    # Kiểm tra đạt chuẩn
    if (BRIGHTNESS_MIN <= avg_brightness <= BRIGHTNESS_MAX and
            avg_sharpness >= SHARPNESS_MIN and
            avg_noise <= NOISE_MAX and
            avg_contrast >= CONTRAST_MIN):
        print(f"\nFACE QUALITY: OK")
        return True
    else:
        print(f"\nFACE QUALITY: NOT OK")
        return False


def faceToEmbedding(video_path, sample_rate=5, resize_width=960, device=None, mtcnn_min_face_size=20):
    """
    Tạo embedding trung bình từ video dùng FaceNet-PyTorch (MTCNN + InceptionResnetV1).
    """
    try:
        import torch
        from facenet_pytorch import MTCNN, InceptionResnetV1
    except Exception as e:
        print("ERROR: facenet-pytorch / torch chưa cài hoặc import lỗi:", e)
        return None

    if device is None:
        device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    else:
        device = torch.device(device)

    if not os.path.isfile(video_path):
        print(f"ERROR: File '{video_path}' not found - {datetime.datetime.now()}")
        return None

    # Detector + model
    mtcnn = MTCNN(image_size=160, margin=0, keep_all=False, device=device, min_face_size=mtcnn_min_face_size)
    model = InceptionResnetV1(pretrained='vggface2').eval().to(device)

    cap = cv2.VideoCapture(video_path)
    if not cap.isOpened():
        print(f"ERROR: Cannot open video '{video_path}' - {datetime.datetime.now()}")
        return None

    embeddings = []
    frame_id = 0

    while True:
        ret, frame = cap.read()
        if not ret:
            break

        if frame_id % sample_rate != 0:
            frame_id += 1
            continue

        # resize giữ tỉ lệ theo chiều ngang
        h, w = frame.shape[:2]
        scale = resize_width / float(w)
        frame_resized = cv2.resize(frame, (resize_width, int(h * scale)))
        frame_rgb = cv2.cvtColor(frame_resized, cv2.COLOR_BGR2RGB)

        # Detect & crop face trực tiếp
        face_tensor = mtcnn(frame_rgb)  # trả về Tensor (3,160,160) hoặc None
        if face_tensor is None:
            frame_id += 1
            continue

        # chuẩn hóa thành batch
        if face_tensor.ndim == 3:
            face_tensor = face_tensor.unsqueeze(0).to(device)
        else:
            face_tensor = face_tensor.to(device)

        # Tính embedding
        with torch.no_grad():
            emb_t = model(face_tensor)  # (1,512)
        emb_np = emb_t.cpu().numpy()
        emb_np = emb_np / np.linalg.norm(emb_np, axis=1, keepdims=True)

        embeddings.append(emb_np[0])
        frame_id += 1

    cap.release()

    if len(embeddings) == 0:
        print("ERROR: No face embeddings collected from video.")
        return None

    # Tính embedding trung bình và chuẩn hóa
    emb_avg = np.mean(embeddings, axis=0)
    emb_avg = emb_avg / norm(emb_avg)

    print(f"SUCCESS: Created embedding from video ({len(embeddings)} faces detected) - {datetime.datetime.now()}")
    print(f"Embedding shape: {emb_avg.shape}")

    return emb_avg


def save_embedding(embedding, id_employee):
    """Lưu embedding vào ChromaDB (HTTP API dùng vectors, không dùng embeddings)"""
    if collection is None:
        print("ERROR: Collection not initialized")
        return False

    target_id = "face_" + str(id_employee)
    emb_list = embedding.tolist()

    try:
        existing  = collection.get(ids=[target_id], include=["embeddings"])

        ts = datetime.datetime.now().isoformat()

        metadata = {
            "id_employee": str(id_employee),
            "updated_at" if existing and existing.get("ids") else "created_at": ts
        }
        if existing and existing.get("ids"): # HTTP API: dùng vectors 
            collection.upsert( ids=[target_id], embeddings=[emb_list], metadatas=[metadata] ) 
            print(f"SUCCESS: Updated embedding for ID={id_employee}") 
        else: 
            collection.add( ids=[target_id], vectors=[emb_list], metadatas=[metadata] ) 
            print(f"SUCCESS: Added new embedding for ID={id_employee}") 
            return True
       
            # HTTP API: dùng vectors
        collection.upsert(
            ids=[target_id],
            embeddings=[emb_list],
            metadatas=[metadata]
        )
        print(f"SUCCESS: Updated embedding for ID={id_employee}")
      

        return True

    except Exception as e:
        print(f"ERROR: Failed to save embedding: {e}")
        return False



def get_embedding(id_employee):
    """Lấy embedding từ ChromaDB"""
    target_id = "face_" + str(id_employee)
    try:
        result = collection.get(
            ids=[target_id],
            include=["embeddings", "metadatas", "documents"]
        )

        emb_list = result.get("embeddings")

        # Nếu không có embeddings
        if emb_list is None:
            print(f"ERROR: No embedding found for ID={id_employee} - {datetime.datetime.now()}")
            return None

        # emb_list là numpy array 2D => lấy phần tử đầu
        if isinstance(emb_list, np.ndarray):
            if emb_list.shape[0] == 0:
                print(f"ERROR: Empty embedding array for ID={id_employee}")
                return None
            emb = emb_list[0]
        else:
            # emb_list là list
            if len(emb_list) == 0:
                print(f"ERROR: Empty embedding list for ID={id_employee}")
                return None
            emb = emb_list[0]

        emb = np.array(emb)

        print(f"SUCCESS: Retrieved embedding for ID={id_employee} - {datetime.datetime.now()}")
        return emb

    except Exception as e:
        print(f"ERROR: Failed to retrieve embedding: {e} - {datetime.datetime.now()}")
        return None


def cosine_similarity(emb1, emb2):
    """Tính độ tương đồng cosine giữa 2 embedding"""
    return np.dot(emb1, emb2) / (norm(emb1) * norm(emb2))


def register_face(video_path, id_employee):
    """Đăng ký khuôn mặt mới"""
    print(f"\n{'='*60}")
    print(f"REGISTERING FACE FOR ID={id_employee}")
    print(f"{'='*60}")
    
    result_check_quality = check_quality(video_path)
    if result_check_quality:
        print(f"\n✓ Face quality check passed - {datetime.datetime.now()}")
        embedding = faceToEmbedding(video_path)
        
        if embedding is not None:
            success = save_embedding(embedding, id_employee)
            if success:
                print(f"\n✓ Face registration SUCCESSFUL for ID={id_employee} - {datetime.datetime.now()}")
                print("DEBUG RAW GET:", collection.get(ids=["face_3"], include=["embeddings"]))  # kiểm tra lại
            else:
                print(f"\n✗ Face registration FAILED (save error) - {datetime.datetime.now()}")
        else:
            print(f"\n✗ Face registration FAILED (no embedding) - {datetime.datetime.now()}")
    else:
        print(f"\n✗ Face registration FAILED (low quality) - {datetime.datetime.now()}")


def check_face(video_path, id_employee):
    """
    Xác thực khuôn mặt từ video với khuôn mặt đã đăng ký
    Returns: True nếu khớp, False nếu không khớp
    """
    print(f"\n{'='*60}")
    print(f"CHECKING FACE FOR ID={id_employee}")
    print(f"{'='*60}")
    
    # Bước 1: Kiểm tra chất lượng video
    result_check_quality = check_quality(video_path)
    if not result_check_quality:
        print(f"\n✗ Face verification FAILED (low quality) - {datetime.datetime.now()}")
        return False
    
    print(f"\n✓ Face quality check passed - {datetime.datetime.now()}")
    
    # Bước 2: Lấy embedding đã lưu từ database
    embedding_stored = get_embedding(id_employee)
    if embedding_stored is None:
        print(f"\n✗ Face verification FAILED (no stored embedding for ID={id_employee}) - {datetime.datetime.now()}")
        return False
    
    # Bước 3: Tạo embedding từ video xác nhận
    print(f"\nCreating embedding from verification video...")
    embedding_confirm = faceToEmbedding(video_path)
    if embedding_confirm is None:
        print(f"\n✗ Face verification FAILED (cannot create embedding from video) - {datetime.datetime.now()}")
        return False
    
    # Bước 4: So sánh 2 embedding
    similarity = cosine_similarity(embedding_stored, embedding_confirm)
    print(f"\n{'='*60}")
    print(f"SIMILARITY SCORE: {similarity:.4f}")
    print(f"THRESHOLD: {SIMILARITY_THRESHOLD}")
    print(f"{'='*60}")
    
    # Bước 5: Đánh giá kết quả
    if similarity >= SIMILARITY_THRESHOLD:
        print(f"\n✓ Face verification SUCCESSFUL - Match confirmed! - {datetime.datetime.now()}")
        print(f"  Similarity: {similarity:.4f} >= {SIMILARITY_THRESHOLD}")
        return True
    else:
        print(f"\n✗ Face verification FAILED - No match - {datetime.datetime.now()}")
        print(f"  Similarity: {similarity:.4f} < {SIMILARITY_THRESHOLD}")
        return False


def main():
    if len(sys.argv) < 3:
        print(f"\nUsage: python face.py <video_path> <ID_EMPLOYEE> <command>")
        print(f"Commands:")
        print(f"  register - Register a new face")
        print(f"  check    - Verify a face against stored embedding")
        print(f"\nExample:")
        print(f"  python face.py video.mp4 12345 register")
        print(f"  python face.py video.mp4 12345 check")
        return

    video_path = sys.argv[1]
    id_employee = sys.argv[2]
    cmd = sys.argv[3] if len(sys.argv) > 3 else None

    match cmd:
        case "register":
            register_face(video_path, id_employee)
        case "check":
            check_face(video_path, id_employee)
        case None:
            print(f"ERROR: No command given - {datetime.datetime.now()}")
            print(f"Use 'register' or 'check' command")
        case _:
            print(f"ERROR: Unknown command: {cmd} - {datetime.datetime.now()}")
            print(f"Available commands: register, check")


if __name__ == "__main__":
    main()
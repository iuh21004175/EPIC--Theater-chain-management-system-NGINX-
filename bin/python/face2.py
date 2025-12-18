#!/usr/bin/env python3
import datetime
import cv2
import numpy as np
import sys
import os
from numpy.linalg import norm
import chromadb
import json
import traceback

# Khởi tạo ChromaDB Client
client = chromadb.HttpClient(
    host="chroma.epiccinema.io.vn",
    port=443,
    ssl=True
)
collection = client.get_or_create_collection(
    name="face_embeddings",
    metadata={"hnsw:space": "cosine"}
)

SIMILARITY_THRESHOLD = 0.6

def check_image_quality(image):
    """Kiểm tra chất lượng một tấm ảnh duy nhất"""
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    
    brightness = np.mean(gray)
    sharpness = cv2.Laplacian(gray, cv2.CV_64F).var()
    contrast = gray.std()

    BRIGHTNESS_MIN, BRIGHTNESS_MAX = 50, 230
    SHARPNESS_MIN = 25
    CONTRAST_MIN = 10

    is_ok = (BRIGHTNESS_MIN <= brightness <= BRIGHTNESS_MAX and 
              sharpness >= SHARPNESS_MIN and 
              contrast >= CONTRAST_MIN)
    
    msg = f"Brightness: {brightness:.2f}, Sharpness: {sharpness:.2f}, Contrast: {contrast:.2f}"
    return is_ok, msg

def faceToEmbedding(image_path, device=None):
    """Tạo embedding từ một tấm ảnh duy nhất"""
    try:
        import torch
        from facenet_pytorch import MTCNN, InceptionResnetV1
    except Exception as e:
        print(f"DEBUG: facenet-pytorch/torch missing: {e}")
        return None

    device = device or torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    
    mtcnn = MTCNN(image_size=160, margin=0, keep_all=False, device=device)
    model = InceptionResnetV1(pretrained='vggface2').eval().to(device)

    img = cv2.imread(image_path)
    if img is None:
        return None

    img_rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
    face_tensor = mtcnn(img_rgb)
    
    if face_tensor is None:
        return None

    face_tensor = face_tensor.unsqueeze(0).to(device)
    with torch.no_grad():
        emb = model(face_tensor).cpu().numpy()
    
    emb = emb / np.linalg.norm(emb, axis=1, keepdims=True)
    return emb[0]

def register_face(image_path, id_employee):
    """Đăng ký khuôn mặt - Trả về (Success, Message)"""
    img = cv2.imread(image_path)
    if img is None:
        return False, "Không thể đọc tệp ảnh."

    is_ok, q_msg = check_image_quality(img)
    if not is_ok:
        return False, f"Chất lượng ảnh thấp: {q_msg}"

    embedding = faceToEmbedding(image_path)
    if embedding is None:
        return False, "Không tìm thấy khuôn mặt trong ảnh."

    try:
        target_id = f"face_{id_employee}"
        collection.upsert(
            ids=[target_id],
            embeddings=[embedding.tolist()],
            metadatas=[{"id_employee": str(id_employee), "timestamp": str(datetime.datetime.now())}]
        )
        return True, "Đăng ký thành công."
    except Exception as e:
        return False, f"Lỗi lưu trữ database: {str(e)}"

def check_face(image_path, id_employee):
    """Kiểm tra khuôn mặt - Trả về (Success, Similarity, Message)"""
    target_id = f"face_{id_employee}"
    
    try:
        result = collection.get(ids=[target_id], include=["embeddings"])
    except Exception as e:
        return False, 0.0, f"Lỗi truy vấn database: {str(e)}"

    if not result or not result['embeddings'] or len(result['embeddings']) == 0:
        return False, 0.0, f"Nhân viên ID {id_employee} chưa đăng ký khuôn mặt."

    stored_emb = np.array(result['embeddings'][0])
    current_emb = faceToEmbedding(image_path)
    
    if current_emb is None:
        return False, 0.0, "Không nhận diện được khuôn mặt trong ảnh mới."

    similarity = float(np.dot(stored_emb, current_emb) / (norm(stored_emb) * norm(current_emb)))
    
    if similarity >= SIMILARITY_THRESHOLD:
        return True, similarity, "Xác thực thành công."
    else:
        return False, similarity, "Khuôn mặt không khớp."

def main():
    if len(sys.argv) < 4:
        print(f"RESULT_JSON:{json.dumps({'success': False, 'message': 'Thiếu tham số truyền vào'})}")
        return

    img_path, emp_id, cmd = sys.argv[1], sys.argv[2], sys.argv[3]
    result_data = {"success": False, "similarity": 0.0, "message": ""}

    try:
        if cmd == "register":
            success, msg = register_face(img_path, emp_id)
            result_data.update({"success": success, "message": msg})
        
        elif cmd == "check":
            success, similarity, msg = check_face(img_path, emp_id)
            result_data.update({"success": success, "similarity": similarity, "message": msg})
        
        else:
            result_data["message"] = "Lệnh không hợp lệ (Dùng register/check)"

    except Exception as e:
        result_data.update({"success": False, "message": f"Lỗi hệ thống: {str(e)}"})
    
    # Đảm bảo in ra một dòng JSON duy nhất để .NET dễ dàng parse
    print(f"RESULT_JSON:{json.dumps(result_data)}")

if __name__ == "__main__":
    main()
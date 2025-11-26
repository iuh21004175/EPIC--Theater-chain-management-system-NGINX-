#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script sinh dữ liệu phòng chiếu và sơ đồ ghế cho hệ thống Epic Cinema

Yêu cầu:
    - Python 3.6+
    - mysql-connector-python: pip install mysql-connector-python (khuyến nghị, hỗ trợ UTF-8 tốt)
    - hoặc pymysql: pip install pymysql

Cách sử dụng:
    1. Cài đặt dependencies (khuyến nghị):
       pip install mysql-connector-python
    
    2. Chạy script:
       python face_phongchieu.py

Lưu ý:
    - Script sẽ tạo 2 phòng chiếu cho mỗi rạp phim
    - Mỗi phòng chiếu sẽ có sơ đồ ghế tự động
    - Sơ đồ ghế có format: A1, A2, B1, B2... (hàng A-Z, cột 1-N)
"""

try:
    import mysql.connector
    from mysql.connector import Error
    USE_MYSQL_CONNECTOR = True
except ImportError:
    try:
        import pymysql
        USE_MYSQL_CONNECTOR = False
        print("Cảnh báo: mysql-connector-python chưa được cài đặt.")
        print("Đang sử dụng pymysql (có thể gặp vấn đề với password có ký tự đặc biệt)")
        print("Khuyến nghị: pip install mysql-connector-python")
    except ImportError:
        print("Lỗi: Không tìm thấy mysql-connector-python hoặc pymysql!")
        print("Vui lòng cài đặt: pip install mysql-connector-python")
        sys.exit(1)

import random
import os
import sys

# Thêm đường dẫn để import các module khác nếu cần
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

# Tìm đường dẫn đến file .env (thư mục gốc của dự án)
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.abspath(os.path.join(SCRIPT_DIR, '..', '..', '..'))
ENV_FILE = os.path.join(PROJECT_ROOT, '.env')

# Load biến môi trường từ file .env
def load_env_file(env_path):
    """Đọc file .env và load vào os.environ"""
    if not os.path.exists(env_path):
        print(f"Cảnh báo: Không tìm thấy file .env tại {env_path}")
        print("Sử dụng giá trị mặc định hoặc biến môi trường hệ thống")
        return
    
    try:
        with open(env_path, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                # Bỏ qua comment và dòng trống
                if not line or line.startswith('#'):
                    continue
                # Parse key=value
                if '=' in line:
                    key, value = line.split('=', 1)
                    key = key.strip()
                    value = value.strip()
                    # Loại bỏ quotes nếu có
                    if value.startswith('"') and value.endswith('"'):
                        value = value[1:-1]
                    elif value.startswith("'") and value.endswith("'"):
                        value = value[1:-1]
                    # Chỉ set nếu chưa có trong os.environ (ưu tiên biến môi trường hệ thống)
                    if key not in os.environ:
                        os.environ[key] = value
        print(f"Đã load cấu hình từ file .env: {env_path}")
    except Exception as e:
        print(f"Cảnh báo: Không thể đọc file .env: {e}")
        print("Sử dụng giá trị mặc định hoặc biến môi trường hệ thống")

# Load file .env
load_env_file(ENV_FILE)

# Cấu hình kết nối database
def get_db_config():
    """Lấy cấu hình database với xử lý encoding đúng"""
    password = os.getenv('DB_PASSWORD', 'epic2025')
    
    if USE_MYSQL_CONNECTOR:
        config = {
            'host': os.getenv('DB_HOST', 'localhost'),
            'user': os.getenv('DB_USERNAME', 'admin_epic'),
            'password': password,
            'database': os.getenv('DB_DATABASE', 'epic'),
            'charset': 'utf8mb4',
            'collation': 'utf8mb4_unicode_ci',
            'use_unicode': True,
            'autocommit': False
        }
    else:
        config = {
            'host': os.getenv('DB_HOST', 'localhost'),
            'user': os.getenv('DB_USERNAME', 'admin_epic'),
            'password': password,
            'database': os.getenv('DB_DATABASE', 'epic'),
            'charset': 'utf8mb4',
            'use_unicode': True,
            'init_command': "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            'autocommit': False
        }
    return config

DB_CONFIG = get_db_config()

# Cấu hình phòng chiếu
LOAI_PHONG_CHIEU = ['2D', '3D', 'IMAX', '4DX']
TRANG_THAI_PHONG = [1, 1, 1, 0]  # 75% hoạt động, 25% bảo trì

# Cấu hình sơ đồ ghế (số hàng x số cột)
SO_DO_GHE_PRESETS = [
    {'rows': 8, 'cols': 12, 'name': 'Nhỏ'},      # 96 ghế
    {'rows': 10, 'cols': 14, 'name': 'Vừa'},    # 140 ghế
    {'rows': 12, 'cols': 16, 'name': 'Lớn'},    # 192 ghế
    {'rows': 15, 'cols': 20, 'name': 'Rất lớn'} # 300 ghế
]


def get_rapphim_ids(cursor):
    """Lấy danh sách ID rạp phim"""
    cursor.execute("SELECT id, ten FROM rapphim WHERE trang_thai = 1")
    results = cursor.fetchall()
    if USE_MYSQL_CONNECTOR:
        return [(row[0], row[1]) for row in results] if results else []
    else:
        return [(row[0], row[1]) for row in results] if results else []


def get_loaighe_ids(cursor):
    """Lấy danh sách ID loại ghế"""
    cursor.execute("SELECT id FROM loaighe")
    results = cursor.fetchall()
    if results:
        if USE_MYSQL_CONNECTOR:
            return [row[0] for row in results]
        else:
            return [row[0] for row in results]
    return []


def generate_seat_number(row_idx, col_idx):
    """Tạo số ghế từ chỉ số hàng và cột (A1, A2, B1, B2...)"""
    row_letter = chr(65 + row_idx)  # A, B, C...
    seat_number = f"{row_letter}{col_idx + 1}"
    return seat_number


def create_seat_layout(cursor, phongchieu_id, so_hang, so_cot, loaighe_ids):
    """Tạo sơ đồ ghế cho phòng chiếu"""
    seats_created = 0
    
    for row in range(so_hang):
        for col in range(so_cot):
            so_ghe = generate_seat_number(row, col)
            
            # Chọn loại ghế ngẫu nhiên (70% có loại ghế, 30% null)
            loaighe_id = None
            if loaighe_ids and random.random() < 0.7:
                loaighe_id = random.choice(loaighe_ids)
            
            try:
                cursor.execute("""
                    INSERT INTO sodo_ghe (so_ghe, loaighe_id, phongchieu_id, created_at, updated_at)
                    VALUES (%s, %s, %s, NOW(), NOW())
                """, (so_ghe, loaighe_id, phongchieu_id))
                seats_created += 1
            except Exception as e:
                print(f"    Cảnh báo: Không thể tạo ghế {so_ghe}: {e}")
                continue
    
    return seats_created


def create_phong_chieu(cursor, connection, id_rapphim, ten_rap, phong_so):
    """Tạo một phòng chiếu với sơ đồ ghế"""
    # Chọn cấu hình sơ đồ ghế ngẫu nhiên
    config = random.choice(SO_DO_GHE_PRESETS)
    so_hang = config['rows']
    so_cot = config['cols']
    
    # Tạo thông tin phòng chiếu
    ten_phong = f"Phòng {phong_so}"
    ma_phong = f"P{phong_so:02d}"
    loai_phong = random.choice(LOAI_PHONG_CHIEU)
    trang_thai = random.choice(TRANG_THAI_PHONG)
    mo_ta = f"Phòng chiếu {loai_phong} với {so_hang} hàng x {so_cot} cột"
    
    try:
        # Tạo phòng chiếu
        cursor.execute("""
            INSERT INTO phongchieu 
            (ten, ma_phong, mo_ta, loai_phongchieu, trang_thai, sohang_ghe, socot_ghe, so_luong_ghe, id_rapphim, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
        """, (ten_phong, ma_phong, mo_ta, loai_phong, trang_thai, so_hang, so_cot, 0, id_rapphim))
        
        phongchieu_id = cursor.lastrowid
        
        # Lấy danh sách loại ghế
        loaighe_ids = get_loaighe_ids(cursor)
        
        # Tạo sơ đồ ghế
        print(f"    Đang tạo sơ đồ ghế ({so_hang} hàng x {so_cot} cột)...")
        seats_created = create_seat_layout(cursor, phongchieu_id, so_hang, so_cot, loaighe_ids)
        
        # Cập nhật số lượng ghế
        cursor.execute("""
            UPDATE phongchieu 
            SET so_luong_ghe = %s 
            WHERE id = %s
        """, (seats_created, phongchieu_id))
        
        connection.commit()
        print(f"    ✓ Đã tạo phòng chiếu: {ten_phong} ({ma_phong}) - {seats_created} ghế")
        return True
        
    except Exception as e:
        connection.rollback()
        print(f"    ✗ Lỗi tạo phòng chiếu: {e}")
        return False


def create_phong_chieu_for_all_rap(cursor, connection):
    """Tạo phòng chiếu cho tất cả rạp phim"""
    rapphim_list = get_rapphim_ids(cursor)
    
    if not rapphim_list:
        print("Không tìm thấy rạp phim nào trong database!")
        return
    
    print(f"Tìm thấy {len(rapphim_list)} rạp phim")
    print(f"Bắt đầu tạo 2 phòng chiếu cho mỗi rạp...\n")
    
    total_success = 0
    total_error = 0
    
    for id_rap, ten_rap in rapphim_list:
        print(f"Rạp: {ten_rap} (ID: {id_rap})")
        
        # Tạo 2 phòng chiếu cho mỗi rạp
        for phong_so in range(1, 3):
            success = create_phong_chieu(cursor, connection, id_rap, ten_rap, phong_so)
            if success:
                total_success += 1
            else:
                total_error += 1
        
        print()  # Dòng trống giữa các rạp
    
    print(f"{'='*60}")
    print(f"Hoàn thành!")
    print(f"Thành công: {total_success} phòng chiếu")
    print(f"Lỗi: {total_error} phòng chiếu")
    print(f"{'='*60}")


def main():
    """Hàm chính"""
    # Kết nối database
    try:
        config = DB_CONFIG.copy()
        
        if USE_MYSQL_CONNECTOR:
            try:
                connection = mysql.connector.connect(**config)
                cursor = connection.cursor()
                print("Đã kết nối database thành công (sử dụng mysql-connector-python)!")
            except Error as e:
                print(f"Lỗi kết nối database: {e}")
                sys.exit(1)
        else:
            try:
                connection = pymysql.connect(**config)
                cursor = connection.cursor()
                # Thiết lập charset cho connection để xử lý tiếng Việt
                cursor.execute("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci")
                cursor.execute("SET CHARACTER SET utf8mb4")
                cursor.execute("SET character_set_connection=utf8mb4")
                cursor.execute("SET character_set_client=utf8mb4")
                cursor.execute("SET character_set_results=utf8mb4")
                print("Đã kết nối database thành công (sử dụng pymysql)!")
            except (pymysql.Error, UnicodeEncodeError) as e:
                print(f"Lỗi kết nối database: {e}")
                print("\nGợi ý: Cài đặt mysql-connector-python để xử lý password có ký tự đặc biệt:")
                print("  pip install mysql-connector-python")
                sys.exit(1)
        
        # Tạo phòng chiếu
        create_phong_chieu_for_all_rap(cursor, connection)
        
        cursor.close()
        connection.close()
        print("\nĐã đóng kết nối database.")
        
    except Exception as e:
        print(f"Lỗi: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == "__main__":
    main()


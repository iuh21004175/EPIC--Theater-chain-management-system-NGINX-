#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script kiểm tra và bổ sung suất chiếu từ kế hoạch vào bảng suất chiếu

Mục đích:
    - Kiểm tra các kế hoạch suất chiếu đã được chấp nhận (tinh_trang = 1) trong bảng kehoach_chitiet
    - Kiểm tra xem các suất chiếu này đã có trong bảng suatchieu chưa
    - Nếu chưa có thì thêm vào bảng suatchieu và ghi log vào log_suatchieu

Yêu cầu:
    - Python 3.6+
    - mysql-connector-python: pip install mysql-connector-python (khuyến nghị)
    - hoặc pymysql: pip install pymysql

Cách sử dụng:
    python fix_fake_kehoach.py

Cấu hình:
    Script tự động đọc file .env từ thư mục gốc của dự án.
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

import os
import sys
from datetime import datetime

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
                if not line or line.startswith('#'):
                    continue
                if '=' in line:
                    key, value = line.split('=', 1)
                    key = key.strip()
                    value = value.strip()
                    if value.startswith('"') and value.endswith('"'):
                        value = value[1:-1]
                    elif value.startswith("'") and value.endswith("'"):
                        value = value[1:-1]
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


def check_suatchieu_exists(cursor, id_phim, id_phongchieu, batdau, ketthuc):
    """Kiểm tra xem suất chiếu đã tồn tại trong bảng suatchieu chưa"""
    cursor.execute("""
        SELECT id FROM suatchieu 
        WHERE id_phim = %s 
        AND id_phongchieu = %s 
        AND batdau = %s 
        AND ketthuc = %s
    """, (id_phim, id_phongchieu, batdau, ketthuc))
    
    result = cursor.fetchone()
    return result is not None


def create_suatchieu_from_kehoach(cursor, connection, id_phim, id_phongchieu, batdau, ketthuc):
    """Tạo suất chiếu từ kế hoạch và ghi log
    
    Args:
        cursor: Database cursor
        connection: Database connection
        id_phim: ID phim
        id_phongchieu: ID phòng chiếu
        batdau: Thời gian bắt đầu
        ketthuc: Thời gian kết thúc
    
    Returns:
        int: ID suất chiếu mới tạo hoặc None nếu đã tồn tại
    """
    # Kiểm tra xem đã có suất chiếu chưa
    if check_suatchieu_exists(cursor, id_phim, id_phongchieu, batdau, ketthuc):
        return None
    
    # Lấy thông tin phim
    cursor.execute("SELECT ten_phim FROM phim WHERE id = %s", (id_phim,))
    phim_result = cursor.fetchone()
    ten_phim = phim_result[0] if phim_result else 'Không rõ'
    
    # Tạo mới suất chiếu
    cursor.execute("""
        INSERT INTO suatchieu 
        (id_phim, id_phongchieu, batdau, ketthuc, created_at, updated_at)
        VALUES (%s, %s, %s, %s, NOW(), NOW())
    """, (id_phim, id_phongchieu, batdau, ketthuc))
    
    id_suatchieu = cursor.lastrowid
    
    # Ghi log: hanh_dong = 5 (Duyệt từ kế hoạch)
    cursor.execute("""
        INSERT INTO log_suatchieu 
        (id_suatchieu, hanh_dong, batdau, id_phim, ten_phim, da_xem, rap_da_xem, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
    """, (
        id_suatchieu,
        5,  # hanh_dong: 5 - Duyệt từ kế hoạch
        batdau,
        id_phim,
        ten_phim,
        0,  # da_xem: 0 - Chưa xem (Quản lý chuỗi rạp)
        0   # rap_da_xem: 0 - Chưa xem (Quản lý rạp)
    ))
    
    connection.commit()
    return id_suatchieu


def fix_kehoach_suatchieu(cursor, connection):
    """Kiểm tra và bổ sung suất chiếu từ kế hoạch vào bảng suất chiếu"""
    print("Đang kiểm tra các kế hoạch suất chiếu đã được duyệt...")
    
    # Lấy tất cả các kế hoạch chi tiết đã được duyệt (tinh_trang = 1)
    cursor.execute("""
        SELECT 
            kc.id,
            kc.id_phim,
            kc.id_phongchieu,
            kc.batdau,
            kc.ketthuc,
            p.ten_phim,
            r.ten as ten_rap,
            pc.ten as ten_phong
        FROM kehoach_chitiet kc
        INNER JOIN phim p ON kc.id_phim = p.id
        INNER JOIN phongchieu pc ON kc.id_phongchieu = pc.id
        INNER JOIN rapphim r ON pc.id_rapphim = r.id
        WHERE kc.tinh_trang = 1
        ORDER BY kc.batdau ASC
    """)
    
    kehoach_list = cursor.fetchall()
    
    if not kehoach_list:
        print("Không tìm thấy kế hoạch suất chiếu nào đã được duyệt.")
        return
    
    print(f"Tìm thấy {len(kehoach_list)} kế hoạch suất chiếu đã được duyệt.")
    print("Đang kiểm tra và bổ sung vào bảng suất chiếu...\n")
    
    added_count = 0
    existed_count = 0
    error_count = 0
    
    for row in kehoach_list:
        try:
            if USE_MYSQL_CONNECTOR:
                id_kehoach_chitiet, id_phim, id_phongchieu, batdau, ketthuc, ten_phim, ten_rap, ten_phong = row
            else:
                id_kehoach_chitiet, id_phim, id_phongchieu, batdau, ketthuc, ten_phim, ten_rap, ten_phong = row
            
            # Kiểm tra xem đã có trong suatchieu chưa
            if check_suatchieu_exists(cursor, id_phim, id_phongchieu, batdau, ketthuc):
                existed_count += 1
                continue
            
            # Tạo suất chiếu mới
            id_suatchieu = create_suatchieu_from_kehoach(
                cursor, connection, id_phim, id_phongchieu, batdau, ketthuc
            )
            
            if id_suatchieu:
                added_count += 1
                batdau_str = batdau.strftime('%Y-%m-%d %H:%M:%S') if isinstance(batdau, datetime) else str(batdau)
                print(f"  ✓ Đã thêm suất chiếu ID {id_suatchieu}: {ten_phim} - {ten_rap} - {ten_phong} - {batdau_str}")
            else:
                existed_count += 1
                
        except Exception as e:
            connection.rollback()
            error_count += 1
            print(f"  ✗ Lỗi khi xử lý kế hoạch ID {id_kehoach_chitiet}: {e}")
            continue
    
    print(f"\n{'='*60}")
    print(f"Hoàn thành!")
    print(f"{'='*60}")
    print(f"Tổng số kế hoạch đã duyệt: {len(kehoach_list)}")
    print(f"Đã thêm mới: {added_count}")
    print(f"Đã tồn tại: {existed_count}")
    print(f"Lỗi: {error_count}")
    print(f"{'='*60}")


def main():
    """Hàm chính"""
    print("Bắt đầu kiểm tra và bổ sung suất chiếu từ kế hoạch...")
    print(f"Sử dụng {'mysql-connector-python' if USE_MYSQL_CONNECTOR else 'pymysql'}\n")
    
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
        
        # Kiểm tra và bổ sung suất chiếu
        fix_kehoach_suatchieu(cursor, connection)
        
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


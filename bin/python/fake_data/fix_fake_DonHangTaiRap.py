#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script sửa lại đơn hàng: gán id_nhanvien cho đơn hàng tại rạp

Yêu cầu:
    - Python 3.6+
    - mysql-connector-python hoặc pymysql

Cách sử dụng:
    python fix_fake_DonHangTaiRap.py

Chức năng:
    1. Tìm tất cả đơn hàng có id_nhanvien = null
    2. Chuyển một số đơn hàng có phuong_thuc_mua = 0 (đặt online) sang phuong_thuc_mua = 2 (tại rạp)
    3. Gán id_nhanvien cho các đơn hàng phuong_thuc_mua = 2 dựa trên rap_id
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
        print("Đang sử dụng pymysql")
    except ImportError:
        print("Lỗi: Không tìm thấy mysql-connector-python hoặc pymysql!")
        print("Vui lòng cài đặt: pip install mysql-connector-python")
        sys.exit(1)

import random
import os
import sys

# Thêm đường dẫn để import các module khác nếu cần
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

# Tìm đường dẫn đến file .env
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.abspath(os.path.join(SCRIPT_DIR, '..', '..', '..'))
ENV_FILE = os.path.join(PROJECT_ROOT, '.env')

# Load biến môi trường từ file .env
def load_env_file(env_path):
    """Đọc file .env và load vào os.environ"""
    if not os.path.exists(env_path):
        print(f"Cảnh báo: Không tìm thấy file .env tại {env_path}")
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

load_env_file(ENV_FILE)

# Cấu hình kết nối database
def get_db_config():
    """Lấy cấu hình database"""
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


def get_nhanvien_by_rap(cursor, id_rapphim):
    """Lấy danh sách nhân viên theo rạp"""
    cursor.execute("""
        SELECT tk.id 
        FROM taikhoan_noibo tk
        INNER JOIN nguoidung_noibo nd ON tk.id = nd.id_taikhoan
        WHERE nd.id_rapphim = %s AND nd.trang_thai = 1
    """, (id_rapphim,))
    results = cursor.fetchall()
    return [row[0] for row in results] if results else []


def fix_donhang_tai_rap(cursor, connection):
    """Sửa lại đơn hàng: gán id_nhanvien cho đơn hàng tại rạp"""
    print("Bắt đầu sửa lại đơn hàng...")
    
    # 1. Lấy tất cả đơn hàng có id_nhanvien = null
    cursor.execute("""
        SELECT id, rap_id, phuong_thuc_mua, user_id
        FROM donhang
        WHERE id_nhanvien IS NULL
    """)
    donhang_null_nv = cursor.fetchall()
    
    if not donhang_null_nv:
        print("Không có đơn hàng nào cần sửa (tất cả đã có id_nhanvien)")
        return
    
    print(f"Tìm thấy {len(donhang_null_nv)} đơn hàng không có id_nhanvien")
    
    # 2. Chuyển một số đơn hàng phuong_thuc_mua = 0 sang = 2 (30% số đơn hàng online)
    donhang_online = [dh for dh in donhang_null_nv if dh[2] == 0]  # phuong_thuc_mua = 0
    so_chuyen = int(len(donhang_online) * 0.3)  # 30% số đơn hàng online
    
    if so_chuyen > 0:
        donhang_chuyen = random.sample(donhang_online, min(so_chuyen, len(donhang_online)))
        print(f"\nChuyển {len(donhang_chuyen)} đơn hàng từ đặt online (0) sang tại rạp (2)...")
        
        for dh in donhang_chuyen:
            donhang_id = dh[0]
            try:
                cursor.execute("""
                    UPDATE donhang 
                    SET phuong_thuc_mua = 2
                    WHERE id = %s
                """, (donhang_id,))
                connection.commit()
            except Exception as e:
                connection.rollback()
                print(f"  ⚠ Lỗi khi chuyển đơn hàng {donhang_id}: {e}")
    
    # 3. Lấy lại danh sách đơn hàng cần gán id_nhanvien (bao gồm cả những đơn đã chuyển)
    cursor.execute("""
        SELECT id, rap_id, phuong_thuc_mua, user_id
        FROM donhang
        WHERE id_nhanvien IS NULL AND phuong_thuc_mua = 2
    """)
    donhang_tai_rap = cursor.fetchall()
    
    if not donhang_tai_rap:
        print("\nKhông có đơn hàng tại rạp nào cần gán id_nhanvien")
        return
    
    print(f"\nCần gán id_nhanvien cho {len(donhang_tai_rap)} đơn hàng tại rạp")
    
    # 4. Gán id_nhanvien cho từng đơn hàng dựa trên rap_id
    updated = 0
    failed = 0
    
    # Nhóm đơn hàng theo rap_id để tối ưu
    donhang_by_rap = {}
    for dh in donhang_tai_rap:
        rap_id = dh[1]
        if rap_id not in donhang_by_rap:
            donhang_by_rap[rap_id] = []
        donhang_by_rap[rap_id].append(dh)
    
    print(f"\nXử lý {len(donhang_by_rap)} rạp...")
    
    for rap_id, donhang_list in donhang_by_rap.items():
        if not rap_id:
            print(f"  ⚠ Bỏ qua {len(donhang_list)} đơn hàng không có rap_id")
            failed += len(donhang_list)
            continue
        
        # Lấy danh sách nhân viên của rạp
        nhanvien_list = get_nhanvien_by_rap(cursor, rap_id)
        
        if not nhanvien_list:
            print(f"  ⚠ Rạp {rap_id}: Không có nhân viên, bỏ qua {len(donhang_list)} đơn hàng")
            failed += len(donhang_list)
            continue
        
        print(f"  Rạp {rap_id}: {len(nhanvien_list)} nhân viên, {len(donhang_list)} đơn hàng")
        
        # Gán id_nhanvien ngẫu nhiên cho từng đơn hàng
        for dh in donhang_list:
            donhang_id = dh[0]
            id_nhanvien = random.choice(nhanvien_list)
            
            try:
                cursor.execute("""
                    UPDATE donhang 
                    SET id_nhanvien = %s
                    WHERE id = %s
                """, (id_nhanvien, donhang_id))
                connection.commit()
                updated += 1
            except Exception as e:
                connection.rollback()
                print(f"    ⚠ Lỗi khi cập nhật đơn hàng {donhang_id}: {e}")
                failed += 1
    
    print(f"\n{'='*60}")
    print(f"HOÀN THÀNH!")
    print(f"{'='*60}")
    print(f"Đã chuyển: {len(donhang_chuyen) if 'donhang_chuyen' in locals() else 0} đơn hàng từ online sang tại rạp")
    print(f"Đã cập nhật: {updated} đơn hàng")
    print(f"Thất bại: {failed} đơn hàng")
    print(f"{'='*60}")


def main():
    """Hàm chính"""
    print("Script sửa lại đơn hàng: Gán id_nhanvien cho đơn hàng tại rạp")
    print(f"Sử dụng {'mysql-connector-python' if USE_MYSQL_CONNECTOR else 'pymysql'}\n")
    
    try:
        config = DB_CONFIG.copy()
        
        if USE_MYSQL_CONNECTOR:
            try:
                connection = mysql.connector.connect(**config)
                cursor = connection.cursor()
                print("Đã kết nối database thành công!")
            except Error as e:
                print(f"Lỗi kết nối database: {e}")
                sys.exit(1)
        else:
            try:
                connection = pymysql.connect(**config)
                cursor = connection.cursor()
                cursor.execute("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci")
                print("Đã kết nối database thành công!")
            except (pymysql.Error, UnicodeEncodeError) as e:
                print(f"Lỗi kết nối database: {e}")
                sys.exit(1)
        
        fix_donhang_tai_rap(cursor, connection)
        
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


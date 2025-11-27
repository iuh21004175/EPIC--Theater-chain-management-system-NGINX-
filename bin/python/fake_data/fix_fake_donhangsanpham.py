#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script chuyển đổi đơn hàng: Từ chỉ mua sản phẩm sang vừa mua sản phẩm vừa mua vé

Yêu cầu:
    - Python 3.6+
    - mysql-connector-python hoặc pymysql

Cách sử dụng:
    python fix_fake_donhangsanpham.py

Chức năng:
    1. Tìm các đơn hàng có phuong_thuc_mua = 3 (chỉ mua sản phẩm)
    2. Chuyển 40% số đơn hàng sang vừa mua sản phẩm vừa mua vé
    3. Thêm vé xem phim cho các đơn hàng đó
    4. Cập nhật thông tin đơn hàng: suat_chieu_id, ma_ve, qr_code, tong_tien, phuong_thuc_mua
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
import string
import os
import sys
from datetime import datetime, timedelta

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


def generate_ma_ve():
    """Tạo mã vé ngẫu nhiên"""
    return ''.join(random.choices(string.ascii_uppercase + string.digits, k=10))


def generate_qr_code(ma_ve):
    """Tạo QR code URL từ mã vé"""
    import urllib.parse
    return f'https://quickchart.io/qr?text={urllib.parse.quote(ma_ve)}&size=300'


def get_suatchieu_by_rap_and_date(cursor, id_rapphim, ngay_dat):
    """Lấy suất chiếu phù hợp trong cùng rạp và khoảng thời gian"""
    # Tìm suất chiếu trong khoảng +/- 3 ngày so với ngày đặt
    ngay_start = ngay_dat - timedelta(days=3)
    ngay_end = ngay_dat + timedelta(days=3)
    
    cursor.execute("""
        SELECT sc.id, sc.id_phim, sc.id_phongchieu, sc.batdau, sc.ketthuc
        FROM suatchieu sc
        INNER JOIN phongchieu pc ON sc.id_phongchieu = pc.id
        WHERE pc.id_rapphim = %s
        AND sc.batdau >= %s
        AND sc.batdau <= %s
        ORDER BY sc.batdau
    """, (id_rapphim, ngay_start, ngay_end))
    results = cursor.fetchall()
    return results if results else []


def get_ghe_by_phong(cursor, id_phongchieu):
    """Lấy danh sách ghế theo phòng chiếu"""
    cursor.execute("""
        SELECT id FROM sodo_ghe 
        WHERE phongchieu_id = %s
    """, (id_phongchieu,))
    results = cursor.fetchall()
    return [row[0] for row in results] if results else []


def get_gia_ve_loai_ghe(cursor, id_loaighe, ngay_chieu=None):
    """Tính giá vé theo loại ghế và ngày chiếu"""
    # Giá cơ bản mặc định
    gia_co_ban = 50000
    
    # Lấy phụ thu từ loại ghế (nếu có)
    phu_thu = 0
    if id_loaighe:
        try:
            cursor.execute("SELECT phu_thu FROM loaighe WHERE id = %s", (id_loaighe,))
            result = cursor.fetchone()
            if result and result[0] is not None:
                phu_thu = result[0]
        except Exception:
            # Nếu không có cột phu_thu, bỏ qua
            pass
    
    # Tính giá dựa trên ngày (cuối tuần thường đắt hơn)
    if ngay_chieu:
        if isinstance(ngay_chieu, datetime):
            day_of_week = ngay_chieu.weekday()
        else:
            day_of_week = datetime.strptime(str(ngay_chieu), '%Y-%m-%d %H:%M:%S').weekday()
        
        # Thứ 6 (4), Thứ 7 (5), Chủ nhật (6) là cuối tuần
        if day_of_week >= 4:
            gia_co_ban = 60000  # Cuối tuần đắt hơn
        else:
            gia_co_ban = 50000  # Ngày thường
    
    # Tổng giá = giá cơ bản + phụ thu
    return gia_co_ban + phu_thu


def fix_donhang_sanpham_to_combo(cursor, connection):
    """Chuyển đổi đơn hàng từ chỉ mua sản phẩm sang vừa mua sản phẩm vừa mua vé"""
    print("\nBắt đầu chuyển đổi đơn hàng...")
    
    # 1. Lấy tất cả đơn hàng có phuong_thuc_mua = 3 (chỉ mua sản phẩm)
    cursor.execute("""
        SELECT dh.id, dh.rap_id, dh.user_id, dh.id_nhanvien, dh.ngay_dat, dh.tong_tien,
               dh.phuong_thuc_thanh_toan, dh.trang_thai
        FROM donhang dh
        WHERE dh.phuong_thuc_mua = 3
        AND dh.suat_chieu_id IS NULL
        AND dh.rap_id IS NOT NULL
        ORDER BY dh.ngay_dat DESC
    """)
    donhang_sanpham = cursor.fetchall()
    
    if not donhang_sanpham:
        print("Không tìm thấy đơn hàng chỉ mua sản phẩm nào!")
        return
    
    print(f"Tìm thấy {len(donhang_sanpham)} đơn hàng chỉ mua sản phẩm")
    
    # 2. Chọn 40% số đơn hàng để chuyển đổi
    ti_le_chuyen_doi = 0.4
    so_don_chuyen_doi = int(len(donhang_sanpham) * ti_le_chuyen_doi)
    
    if so_don_chuyen_doi == 0:
        print("Không có đơn hàng nào được chọn để chuyển đổi!")
        return
    
    donhang_chon = random.sample(donhang_sanpham, so_don_chuyen_doi)
    print(f"Sẽ chuyển đổi {len(donhang_chon)} đơn hàng ({ti_le_chuyen_doi*100}%)")
    
    # Nhóm theo rạp để xử lý
    donhang_by_rap = {}
    for dh in donhang_chon:
        rap_id = dh[1]
        if rap_id not in donhang_by_rap:
            donhang_by_rap[rap_id] = []
        donhang_by_rap[rap_id].append(dh)
    
    print(f"\nXử lý {len(donhang_by_rap)} rạp...")
    
    success = 0
    failed = 0
    
    for rap_id, donhang_list in donhang_by_rap.items():
        print(f"\n  Rạp {rap_id}: {len(donhang_list)} đơn hàng")
        
        for dh in donhang_list:
            donhang_id, rap_id, user_id, id_nhanvien, ngay_dat, tong_tien_cu, phuong_thuc_thanh_toan, trang_thai = dh
            
            try:
                # Chuyển string ngay_dat sang datetime nếu cần
                if isinstance(ngay_dat, str):
                    ngay_dat = datetime.strptime(ngay_dat, '%Y-%m-%d %H:%M:%S')
                
                # Tìm suất chiếu phù hợp
                suatchieu_list = get_suatchieu_by_rap_and_date(cursor, rap_id, ngay_dat)
                
                if not suatchieu_list:
                    print(f"    ⚠ Đơn hàng {donhang_id}: Không tìm thấy suất chiếu phù hợp")
                    failed += 1
                    continue
                
                # Chọn suất chiếu ngẫu nhiên
                suatchieu = random.choice(suatchieu_list)
                id_suatchieu, id_phim, id_phongchieu, batdau, ketthuc = suatchieu
                
                # Lấy danh sách ghế trong phòng
                ghe_list = get_ghe_by_phong(cursor, id_phongchieu)
                
                if not ghe_list:
                    print(f"    ⚠ Đơn hàng {donhang_id}: Phòng chiếu không có ghế")
                    failed += 1
                    continue
                
                # Chọn số lượng vé ngẫu nhiên (1-3 vé)
                so_ve = random.randint(1, 3)
                if len(ghe_list) < so_ve:
                    so_ve = len(ghe_list)
                
                # Chọn ghế ngẫu nhiên (không trùng)
                ghe_chon = random.sample(ghe_list, so_ve)
                
                # Tính tổng tiền vé
                tong_tien_ve = 0
                ve_data = []
                for ghe_id in ghe_chon:
                    # Lấy loại ghế
                    cursor.execute("SELECT loaighe_id FROM sodo_ghe WHERE id = %s", (ghe_id,))
                    ghe_result = cursor.fetchone()
                    id_loaighe = ghe_result[0] if ghe_result else None
                    
                    gia_ve = get_gia_ve_loai_ghe(cursor, id_loaighe, batdau)
                    tong_tien_ve += gia_ve
                    ve_data.append((ghe_id, gia_ve))
                
                # Tổng tiền mới = tổng tiền cũ (sản phẩm) + tiền vé
                tong_tien_moi = tong_tien_cu + tong_tien_ve
                
                # Tạo mã vé và QR code
                ma_ve = generate_ma_ve()
                qr_code = generate_qr_code(ma_ve)
                
                # Xác định phuong_thuc_mua mới
                # Nếu có user_id thì chuyển sang 0 (đặt online)
                # Nếu có id_nhanvien thì chuyển sang 2 (tại rạp)
                phuong_thuc_mua_moi = 0 if user_id else 2
                
                # Cập nhật đơn hàng
                cursor.execute("""
                    UPDATE donhang 
                    SET suat_chieu_id = %s,
                        ma_ve = %s,
                        qr_code = %s,
                        tong_tien = %s,
                        phuong_thuc_mua = %s
                    WHERE id = %s
                """, (id_suatchieu, ma_ve, qr_code, tong_tien_moi, phuong_thuc_mua_moi, donhang_id))
                
                # Tạo vé
                ngay_dat_str = ngay_dat.strftime('%Y-%m-%d %H:%M:%S')
                for ghe_id, gia_ve in ve_data:
                    cursor.execute("""
                        INSERT INTO ve 
                        (donhang_id, suat_chieu_id, ghe_id, gia_ve, khach_hang_id, trang_thai, ngay_tao)
                        VALUES (%s, %s, %s, %s, %s, %s, %s)
                    """, (donhang_id, id_suatchieu, ghe_id, gia_ve, user_id, 
                          2 if trang_thai == 2 else (1 if trang_thai == 1 else 0), ngay_dat_str))
                
                connection.commit()
                success += 1
                
                if success % 10 == 0:
                    print(f"    ✓ Đã xử lý {success} đơn hàng...")
                
            except Exception as e:
                connection.rollback()
                print(f"    ⚠ Lỗi khi xử lý đơn hàng {donhang_id}: {e}")
                failed += 1
                continue
    
    print(f"\n{'='*60}")
    print(f"HOÀN THÀNH!")
    print(f"{'='*60}")
    print(f"Tổng số đơn hàng chỉ mua sản phẩm: {len(donhang_sanpham)}")
    print(f"Đã chuyển đổi thành công: {success} đơn hàng")
    print(f"Thất bại: {failed} đơn hàng")
    print(f"Tỷ lệ thành công: {(success/len(donhang_chon)*100):.1f}%")
    print(f"{'='*60}")


def main():
    """Hàm chính"""
    print("="*60)
    print("Script chuyển đổi đơn hàng:")
    print("Từ chỉ mua sản phẩm → Vừa mua sản phẩm vừa mua vé")
    print("="*60)
    print(f"Sử dụng {'mysql-connector-python' if USE_MYSQL_CONNECTOR else 'pymysql'}\n")
    
    try:
        config = DB_CONFIG.copy()
        
        if USE_MYSQL_CONNECTOR:
            try:
                connection = mysql.connector.connect(**config)
                cursor = connection.cursor()
                print("✓ Đã kết nối database thành công!")
            except Error as e:
                print(f"✗ Lỗi kết nối database: {e}")
                sys.exit(1)
        else:
            try:
                connection = pymysql.connect(**config)
                cursor = connection.cursor()
                cursor.execute("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci")
                print("✓ Đã kết nối database thành công!")
            except (pymysql.Error, UnicodeEncodeError) as e:
                print(f"✗ Lỗi kết nối database: {e}")
                sys.exit(1)
        
        # Thực hiện chuyển đổi
        fix_donhang_sanpham_to_combo(cursor, connection)
        
        cursor.close()
        connection.close()
        print("\n✓ Đã đóng kết nối database.")
        
    except Exception as e:
        print(f"\n✗ Lỗi: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == "__main__":
    main()


#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script sinh dữ liệu đơn hàng giả cho hệ thống Epic Cinema

Yêu cầu:
    - Python 3.6+
    - mysql-connector-python: pip install mysql-connector-python (khuyến nghị)
    - hoặc pymysql: pip install pymysql

Cách sử dụng:
    python fake_donhang.py

Cấu hình:
    Script tự động đọc file .env từ thư mục gốc của dự án.

Lưu ý:
    - Mỗi rạp sẽ có khoảng 200-300 đơn hàng
    - Có 4 loại đơn hàng:
      * phuong_thuc_mua = 0: Khách hàng đặt vé online (có user_id, suat_chieu_id, ve)
      * phuong_thuc_mua = 1: Mua vé gói xem phim trực tuyến (có user_id, không có suat_chieu_id)
      * phuong_thuc_mua = 2: Nhân viên bán vé tại rạp (có id_nhanvien, suat_chieu_id, ve)
      * phuong_thuc_mua = 3: Chỉ mua sản phẩm (có user_id hoặc id_nhanvien, rap_id, chi_tiet_don_hang)
    - Trạng thái: 2 (Đã thanh toán) - 80%, 1 (Chờ thanh toán) - 15%, 0 (Đã hủy) - 5%
    - Phương thức thanh toán: 1 (Chuyển khoản) - 60%, 2 (Tiền mặt) - 40%
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
import string
from datetime import datetime, timedelta
import os
import sys
import uuid

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


def get_rapphim_list(cursor):
    """Lấy danh sách rạp phim"""
    cursor.execute("SELECT id, ten FROM rapphim WHERE trang_thai = 1")
    results = cursor.fetchall()
    return [(row[0], row[1]) for row in results] if results else []


def get_khachhang_list(cursor):
    """Lấy danh sách khách hàng"""
    cursor.execute("SELECT id FROM khach_hang")
    results = cursor.fetchall()
    return [row[0] for row in results] if results else []


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


def get_suatchieu_by_rap(cursor, id_rapphim):
    """Lấy danh sách suất chiếu theo rạp (lấy suất chiếu trong 30 ngày qua)"""
    today = datetime.now().date()
    past_date = today - timedelta(days=30)
    cursor.execute("""
        SELECT sc.id, sc.id_phim, sc.id_phongchieu, sc.batdau, sc.ketthuc
        FROM suatchieu sc
        INNER JOIN phongchieu pc ON sc.id_phongchieu = pc.id
        WHERE pc.id_rapphim = %s
        AND DATE(sc.batdau) >= %s
        AND DATE(sc.batdau) <= %s
        ORDER BY sc.batdau DESC
    """, (id_rapphim, past_date, today))
    results = cursor.fetchall()
    return results if results else []


def get_sanpham_by_rap(cursor, id_rapphim):
    """Lấy danh sách sản phẩm theo rạp"""
    cursor.execute("""
        SELECT id, gia FROM san_pham 
        WHERE id_rapphim = %s AND trang_thai = 1
    """, (id_rapphim,))
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


def create_donhang_ve_online(cursor, connection, id_rapphim, khachhang_list, suatchieu_list):
    """Tạo đơn hàng đặt vé online"""
    if not khachhang_list or not suatchieu_list:
        return 0
    
    so_luong = random.randint(200, 300)
    created = 0
    
    for i in range(so_luong):
        try:
            # Chọn ngẫu nhiên khách hàng và suất chiếu
            user_id = random.choice(khachhang_list)
            suatchieu = random.choice(suatchieu_list)
            id_suatchieu, id_phim, id_phongchieu, batdau, ketthuc = suatchieu
            
            # Ngày đặt phải trước hoặc bằng ngày chiếu
            if isinstance(batdau, datetime):
                ngay_chieu = batdau.date()
            else:
                ngay_chieu = datetime.strptime(str(batdau), '%Y-%m-%d %H:%M:%S').date()
            
            today = datetime.now().date()
            
            # Tính ngày đặt: từ 30 ngày trước đến ngày chiếu
            # Ngày đặt không được vượt quá ngày chiếu
            if ngay_chieu == today:
                # Suất chiếu hôm nay: ngày đặt từ 7 ngày trước đến hôm nay
                days_before = random.randint(0, 7)
                ngay_dat = today - timedelta(days=days_before)
            elif ngay_chieu < today:
                # Suất chiếu trong quá khứ: ngày đặt từ 30 ngày trước đến ngày chiếu
                days_diff = (today - ngay_chieu).days
                # Ngày đặt có thể từ 30 ngày trước đến ngày chiếu
                max_days_before = min(30, days_diff + 30)
                days_before = random.randint(0, max_days_before)
                ngay_dat = ngay_chieu - timedelta(days=days_before)
                # Đảm bảo không vượt quá 30 ngày trước
                past_limit = today - timedelta(days=30)
                if ngay_dat < past_limit:
                    ngay_dat = past_limit
                # Đảm bảo không vượt quá ngày chiếu
                if ngay_dat > ngay_chieu:
                    ngay_dat = ngay_chieu
            else:
                # Trường hợp này không nên xảy ra vì đã filter suất chiếu trong 30 ngày qua
                days_before = random.randint(0, 7)
                ngay_dat = today - timedelta(days=days_before)
            
            # Thêm giờ ngẫu nhiên trong ngày (8h-22h)
            gio_dat = random.randint(8, 22)
            phut_dat = random.randint(0, 59)
            ngay_dat_full = datetime.combine(ngay_dat, datetime.min.time()) + timedelta(hours=gio_dat, minutes=phut_dat)
            ngay_dat_str = ngay_dat_full.strftime('%Y-%m-%d %H:%M:%S')
            
            # Chọn số lượng vé (1-4 vé)
            so_ve = random.randint(1, 4)
            
            # Lấy danh sách ghế trong phòng
            ghe_list = get_ghe_by_phong(cursor, id_phongchieu)
            if not ghe_list:
                continue
            
            # Chọn ghế ngẫu nhiên (không trùng)
            if len(ghe_list) < so_ve:
                so_ve = len(ghe_list)
            
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
            
            # Có thể thêm sản phẩm (30% khả năng)
            tong_tien_sanpham = 0
            chi_tiet_sanpham = []
            if random.random() < 0.3:
                sanpham_list = get_sanpham_by_rap(cursor, id_rapphim)
                if sanpham_list:
                    so_sanpham = random.randint(1, 3)
                    sanpham_chon = random.sample(sanpham_list, min(so_sanpham, len(sanpham_list)))
                    for sanpham in sanpham_chon:
                        sanpham_id, gia_ban = sanpham
                        so_luong_sp = random.randint(1, 3)
                        thanh_tien = gia_ban * so_luong_sp
                        tong_tien_sanpham += thanh_tien
                        chi_tiet_sanpham.append((sanpham_id, so_luong_sp, gia_ban, thanh_tien))
            
            tong_tien = tong_tien_ve + tong_tien_sanpham
            
            # Trạng thái và phương thức thanh toán
            rand = random.random()
            if rand < 0.8:
                trang_thai = 2  # Đã thanh toán
            elif rand < 0.95:
                trang_thai = 1  # Chờ thanh toán
            else:
                trang_thai = 0  # Đã hủy
            
            phuong_thuc_thanh_toan = 1 if random.random() < 0.6 else 2  # 60% chuyển khoản, 40% tiền mặt
            
            # Tạo đơn hàng
            ma_ve = generate_ma_ve()
            qr_code = generate_qr_code(ma_ve)
            
            cursor.execute("""
                INSERT INTO donhang 
                (user_id, suat_chieu_id, rap_id, ma_ve, qr_code, tong_tien, 
                 phuong_thuc_thanh_toan, trang_thai, ngay_dat, phuong_thuc_mua)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """, (user_id, id_suatchieu, id_rapphim, ma_ve, qr_code, tong_tien,
                  phuong_thuc_thanh_toan, trang_thai, ngay_dat_str, 0))
            
            id_donhang = cursor.lastrowid
            
            # Tạo vé
            for ghe_id, gia_ve in ve_data:
                cursor.execute("""
                    INSERT INTO ve 
                    (donhang_id, suat_chieu_id, ghe_id, gia_ve, khach_hang_id, trang_thai, ngay_tao)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                """, (id_donhang, id_suatchieu, ghe_id, gia_ve, user_id, 
                      2 if trang_thai == 2 else (1 if trang_thai == 1 else 0), ngay_dat_str))
            
            # Tạo chi tiết đơn hàng (sản phẩm)
            for sanpham_id, so_luong_sp, don_gia, thanh_tien in chi_tiet_sanpham:
                cursor.execute("""
                    INSERT INTO chitiet_donhang 
                    (donhang_id, sanpham_id, so_luong, don_gia, thanh_tien, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                """, (id_donhang, sanpham_id, so_luong_sp, don_gia, thanh_tien, ngay_dat_str, ngay_dat_str))
            
            connection.commit()
            created += 1
            
        except Exception as e:
            connection.rollback()
            print(f"  ⚠ Lỗi khi tạo đơn hàng vé online: {e}")
            continue
    
    return created


def create_donhang_ve_tai_rap(cursor, connection, id_rapphim, nhanvien_list, suatchieu_list):
    """Tạo đơn hàng đặt vé tại rạp (nhân viên bán)"""
    if not nhanvien_list or not suatchieu_list:
        return 0
    
    so_luong = random.randint(200, 300)
    created = 0
    
    for i in range(so_luong):
        try:
            # Chọn ngẫu nhiên nhân viên và suất chiếu
            id_nhanvien = random.choice(nhanvien_list)
            suatchieu = random.choice(suatchieu_list)
            id_suatchieu, id_phim, id_phongchieu, batdau, ketthuc = suatchieu
            
            # Ngày đặt = ngày chiếu (bán tại rạp)
            # Nhưng giờ đặt có thể sớm hơn giờ chiếu (từ 1 giờ trước đến giờ chiếu)
            if isinstance(batdau, datetime):
                ngay_chieu = batdau
            else:
                ngay_chieu = datetime.strptime(str(batdau), '%Y-%m-%d %H:%M:%S')
            
            # Giờ đặt từ 1 giờ trước giờ chiếu đến giờ chiếu (hoặc sớm hơn nếu giờ chiếu sớm)
            gio_chieu = ngay_chieu.hour
            if gio_chieu >= 9:
                gio_dat = random.randint(gio_chieu - 1, gio_chieu)
            else:
                gio_dat = random.randint(8, gio_chieu)
            
            phut_dat = random.randint(0, 59)
            ngay_dat = ngay_chieu.replace(hour=gio_dat, minute=phut_dat, second=0)
            ngay_dat_str = ngay_dat.strftime('%Y-%m-%d %H:%M:%S')
            
            # Chọn số lượng vé (1-4 vé)
            so_ve = random.randint(1, 4)
            
            # Lấy danh sách ghế trong phòng
            ghe_list = get_ghe_by_phong(cursor, id_phongchieu)
            if not ghe_list:
                continue
            
            if len(ghe_list) < so_ve:
                so_ve = len(ghe_list)
            
            ghe_chon = random.sample(ghe_list, so_ve)
            
            # Tính tổng tiền vé
            tong_tien_ve = 0
            ve_data = []
            for ghe_id in ghe_chon:
                cursor.execute("SELECT loaighe_id FROM sodo_ghe WHERE id = %s", (ghe_id,))
                ghe_result = cursor.fetchone()
                id_loaighe = ghe_result[0] if ghe_result else None
                
                gia_ve = get_gia_ve_loai_ghe(cursor, id_loaighe, batdau)
                tong_tien_ve += gia_ve
                ve_data.append((ghe_id, gia_ve))
            
            # Có thể thêm sản phẩm (50% khả năng - tại rạp dễ mua hơn)
            tong_tien_sanpham = 0
            chi_tiet_sanpham = []
            if random.random() < 0.5:
                sanpham_list = get_sanpham_by_rap(cursor, id_rapphim)
                if sanpham_list:
                    so_sanpham = random.randint(1, 3)
                    sanpham_chon = random.sample(sanpham_list, min(so_sanpham, len(sanpham_list)))
                    for sanpham in sanpham_chon:
                        sanpham_id, gia_ban = sanpham
                        so_luong_sp = random.randint(1, 3)
                        thanh_tien = gia_ban * so_luong_sp
                        tong_tien_sanpham += thanh_tien
                        chi_tiet_sanpham.append((sanpham_id, so_luong_sp, gia_ban, thanh_tien))
            
            tong_tien = tong_tien_ve + tong_tien_sanpham
            
            # Trạng thái (tại rạp thường đã thanh toán)
            rand = random.random()
            if rand < 0.9:
                trang_thai = 2  # Đã thanh toán
            elif rand < 0.98:
                trang_thai = 1  # Chờ thanh toán
            else:
                trang_thai = 0  # Đã hủy
            
            phuong_thuc_thanh_toan = 2  # Tại rạp thường dùng tiền mặt
            
            # Tạo đơn hàng
            ma_ve = generate_ma_ve()
            qr_code = generate_qr_code(ma_ve)
            
            cursor.execute("""
                INSERT INTO donhang 
                (id_nhanvien, suat_chieu_id, rap_id, ma_ve, qr_code, tong_tien, 
                 phuong_thuc_thanh_toan, trang_thai, ngay_dat, phuong_thuc_mua)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """, (id_nhanvien, id_suatchieu, id_rapphim, ma_ve, qr_code, tong_tien,
                  phuong_thuc_thanh_toan, trang_thai, ngay_dat_str, 2))
            
            id_donhang = cursor.lastrowid
            
            # Tạo vé
            for ghe_id, gia_ve in ve_data:
                cursor.execute("""
                    INSERT INTO ve 
                    (donhang_id, suat_chieu_id, ghe_id, gia_ve, khach_hang_id, trang_thai, ngay_tao)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                """, (id_donhang, id_suatchieu, ghe_id, gia_ve, None, 
                      2 if trang_thai == 2 else (1 if trang_thai == 1 else 0), ngay_dat_str))
            
            # Tạo chi tiết đơn hàng (sản phẩm)
            for sanpham_id, so_luong_sp, don_gia, thanh_tien in chi_tiet_sanpham:
                cursor.execute("""
                    INSERT INTO chitiet_donhang 
                    (donhang_id, sanpham_id, so_luong, don_gia, thanh_tien, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                """, (id_donhang, sanpham_id, so_luong_sp, don_gia, thanh_tien, ngay_dat_str, ngay_dat_str))
            
            connection.commit()
            created += 1
            
        except Exception as e:
            connection.rollback()
            print(f"  ⚠ Lỗi khi tạo đơn hàng vé tại rạp: {e}")
            continue
    
    return created


def create_donhang_sanpham(cursor, connection, id_rapphim, khachhang_list, nhanvien_list):
    """Tạo đơn hàng chỉ mua sản phẩm"""
    sanpham_list = get_sanpham_by_rap(cursor, id_rapphim)
    if not sanpham_list:
        return 0
    
    so_luong = random.randint(200, 300)
    created = 0
    
    for i in range(so_luong):
        try:
            # 70% khách hàng mua, 30% nhân viên bán
            use_khachhang = random.random() < 0.7
            user_id = random.choice(khachhang_list) if use_khachhang and khachhang_list else None
            id_nhanvien = random.choice(nhanvien_list) if not use_khachhang and nhanvien_list else None
            
            # Ngày đặt trong 30 ngày qua (quá khứ)
            days_ago = random.randint(0, 30)
            ngay_dat = datetime.now() - timedelta(days=days_ago)
            
            # Thêm giờ ngẫu nhiên trong ngày (8h-22h)
            gio_dat = random.randint(8, 22)
            phut_dat = random.randint(0, 59)
            ngay_dat_full = ngay_dat.replace(hour=gio_dat, minute=phut_dat, second=0)
            ngay_dat_str = ngay_dat_full.strftime('%Y-%m-%d %H:%M:%S')
            
            # Chọn sản phẩm (1-5 sản phẩm)
            so_sanpham = random.randint(1, 5)
            sanpham_chon = random.sample(sanpham_list, min(so_sanpham, len(sanpham_list)))
            
            tong_tien = 0
            chi_tiet_sanpham = []
            for sanpham in sanpham_chon:
                sanpham_id, gia_ban = sanpham
                so_luong_sp = random.randint(1, 3)
                thanh_tien = gia_ban * so_luong_sp
                tong_tien += thanh_tien
                chi_tiet_sanpham.append((sanpham_id, so_luong_sp, gia_ban, thanh_tien))
            
            # Trạng thái
            rand = random.random()
            if rand < 0.85:
                trang_thai = 2  # Đã thanh toán
            elif rand < 0.95:
                trang_thai = 1  # Chờ thanh toán
            else:
                trang_thai = 0  # Đã hủy
            
            phuong_thuc_thanh_toan = 1 if random.random() < 0.5 else 2
            
            # Tạo đơn hàng
            cursor.execute("""
                INSERT INTO donhang 
                (user_id, id_nhanvien, rap_id, tong_tien, 
                 phuong_thuc_thanh_toan, trang_thai, ngay_dat, phuong_thuc_mua)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
            """, (user_id, id_nhanvien, id_rapphim, tong_tien,
                  phuong_thuc_thanh_toan, trang_thai, ngay_dat_str, 3))
            
            id_donhang = cursor.lastrowid
            
            # Tạo chi tiết đơn hàng
            for sanpham_id, so_luong_sp, don_gia, thanh_tien in chi_tiet_sanpham:
                cursor.execute("""
                    INSERT INTO chitiet_donhang 
                    (donhang_id, sanpham_id, so_luong, don_gia, thanh_tien, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                """, (id_donhang, sanpham_id, so_luong_sp, don_gia, thanh_tien, ngay_dat_str, ngay_dat_str))
            
            connection.commit()
            created += 1
            
        except Exception as e:
            connection.rollback()
            print(f"  ⚠ Lỗi khi tạo đơn hàng sản phẩm: {e}")
            continue
    
    return created


def create_donhang_cho_rap(cursor, connection, id_rapphim, ten_rap):
    """Tạo đơn hàng cho một rạp"""
    print(f"\n{'='*60}")
    print(f"Rạp: {ten_rap} (ID: {id_rapphim})")
    print(f"{'='*60}")
    
    # Lấy dữ liệu cần thiết
    khachhang_list = get_khachhang_list(cursor)
    nhanvien_list = get_nhanvien_by_rap(cursor, id_rapphim)
    suatchieu_list = get_suatchieu_by_rap(cursor, id_rapphim)
    
    print(f"  Khách hàng: {len(khachhang_list)}")
    print(f"  Nhân viên: {len(nhanvien_list)}")
    print(f"  Suất chiếu: {len(suatchieu_list)}")
    
    total_created = 0
    
    # Tạo đơn hàng đặt vé online
    print(f"\n  Đang tạo đơn hàng đặt vé online...")
    created_online = create_donhang_ve_online(cursor, connection, id_rapphim, khachhang_list, suatchieu_list)
    print(f"  ✓ Đã tạo {created_online} đơn hàng đặt vé online")
    total_created += created_online
    
    # Tạo đơn hàng đặt vé tại rạp
    print(f"\n  Đang tạo đơn hàng đặt vé tại rạp...")
    created_ta_rap = create_donhang_ve_tai_rap(cursor, connection, id_rapphim, nhanvien_list, suatchieu_list)
    print(f"  ✓ Đã tạo {created_ta_rap} đơn hàng đặt vé tại rạp")
    total_created += created_ta_rap
    
    # Tạo đơn hàng chỉ mua sản phẩm
    print(f"\n  Đang tạo đơn hàng chỉ mua sản phẩm...")
    created_sanpham = create_donhang_sanpham(cursor, connection, id_rapphim, khachhang_list, nhanvien_list)
    print(f"  ✓ Đã tạo {created_sanpham} đơn hàng chỉ mua sản phẩm")
    total_created += created_sanpham
    
    print(f"\n  Tổng cộng: {total_created} đơn hàng")
    
    return total_created


def main():
    """Hàm chính"""
    print("Bắt đầu tạo dữ liệu đơn hàng...")
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
        
        # Lấy danh sách rạp
        rapphim_list = get_rapphim_list(cursor)
        
        if not rapphim_list:
            print("Không tìm thấy rạp phim nào!")
            cursor.close()
            connection.close()
            sys.exit(1)
        
        print(f"Tìm thấy {len(rapphim_list)} rạp phim\n")
        
        total_all = 0
        for id_rap, ten_rap in rapphim_list:
            created = create_donhang_cho_rap(cursor, connection, id_rap, ten_rap)
            total_all += created
        
        print(f"\n{'='*60}")
        print(f"HOÀN THÀNH!")
        print(f"{'='*60}")
        print(f"Tổng số rạp: {len(rapphim_list)}")
        print(f"Tổng số đơn hàng: {total_all}")
        print(f"{'='*60}")
        
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


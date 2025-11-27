#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script sinh dữ liệu kế hoạch suất chiếu cho hệ thống Epic Cinema

Yêu cầu:
    - Python 3.6+
    - mysql-connector-python: pip install mysql-connector-python (khuyến nghị)
    - hoặc pymysql: pip install pymysql

Cách sử dụng:
    1. Cài đặt dependencies:
       pip install mysql-connector-python
    
    2. Chạy script:
       python fake_kehoachsuatchieu.py [id_rap]
    
    Ví dụ:
       python fake_kehoachsuatchieu.py 5    # Tạo kế hoạch cho rạp có ID = 5
       python fake_kehoachsuatchieu.py      # Tạo kế hoạch cho tất cả rạp

Lưu ý:
    - Script sẽ tạo kế hoạch suất chiếu cho tháng hiện tại (ngày 1-30) và tháng sau (ngày 1-30)
    - 3 trạng thái: 0 - Chờ duyệt, 1 - Đã duyệt, 2 - Từ chối
    - Nếu đã duyệt (tinh_trang = 1), sẽ tạo suất chiếu trong bảng suatchieu
    - Suất chiếu phải sau ngày công chiếu của phim
    - Kiểm tra xung đột với cả kế hoạch suất chiếu và suất chiếu hiện có
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
from datetime import datetime, timedelta

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

# Khung giờ chiếu phổ biến (giờ:phút)
# Giờ hoạt động: 08:00 - 22:00 (suất cuối cùng không được bắt đầu sau 22:00)
KHUNG_GIO_CHIEU = [
    '08:00', '09:30', '11:00', '12:30', '14:00', '15:30',
    '17:00', '18:30', '20:00', '21:30'
]

# Khoảng cách giữa các suất chiếu (phút) - buffer trước và sau mỗi suất
KHOANG_CACH_GIUA_SUAT = 30

# Giờ mở cửa và đóng cửa
GIO_MO_CUA = 8  # 08:00
GIO_DONG_CUA = 22  # 22:00 (suất cuối cùng không được bắt đầu sau giờ này)


def get_rapphim_list(cursor):
    """Lấy danh sách rạp phim"""
    cursor.execute("SELECT id, ten FROM rapphim WHERE trang_thai = 1")
    results = cursor.fetchall()
    if USE_MYSQL_CONNECTOR:
        return [(row[0], row[1]) for row in results] if results else []
    else:
        return [(row[0], row[1]) for row in results] if results else []


def get_phim_list(cursor):
    """Lấy danh sách phim với thông tin ngày công chiếu và thời lượng"""
    cursor.execute("""
        SELECT id, ten_phim, ngay_cong_chieu, thoi_luong 
        FROM phim 
        WHERE trang_thai IN (0, 1) AND ngay_cong_chieu IS NOT NULL
        ORDER BY ngay_cong_chieu DESC
        LIMIT 30
    """)
    results = cursor.fetchall()
    phim_list = []
    for row in results:
        phim_list.append({
            'id': row[0],
            'ten_phim': row[1] or '',
            'ngay_cong_chieu': row[2],
            'thoi_luong': row[3] or 120  # Mặc định 120 phút nếu không có
        })
    return phim_list


def get_phongchieu_by_rap(cursor, id_rapphim):
    """Lấy danh sách phòng chiếu theo rạp"""
    cursor.execute("""
        SELECT id, ten, sohang_ghe, socot_ghe 
        FROM phongchieu 
        WHERE id_rapphim = %s AND trang_thai = 1
    """, (id_rapphim,))
    results = cursor.fetchall()
    phong_list = []
    for row in results:
        phong_list.append({
            'id': row[0],
            'ten': row[1] or '',
            'sohang_ghe': row[2] or 0,
            'socot_ghe': row[3] or 0
        })
    return phong_list


def get_start_of_month(date):
    """Lấy ngày đầu tháng"""
    return date.replace(day=1)


def get_end_of_month(date, max_day=30):
    """Lấy ngày cuối tháng (giới hạn ở ngày 30)"""
    start = get_start_of_month(date)
    return start.replace(day=max_day)


def calculate_end_time(start_time_str, thoi_luong_phim):
    """Tính thời gian kết thúc suất chiếu
    
    Args:
        start_time_str: Thời gian bắt đầu (format: 'YYYY-MM-DD HH:MM:SS')
        thoi_luong_phim: Thời lượng phim (phút)
    
    Returns:
        str: Thời gian kết thúc (format: 'YYYY-MM-DD HH:MM:SS')
    """
    start_dt = datetime.strptime(start_time_str, '%Y-%m-%d %H:%M:%S')
    # Chỉ tính thời lượng phim, không cộng thêm thời gian chuẩn bị
    end_dt = start_dt + timedelta(minutes=thoi_luong_phim)
    return end_dt.strftime('%Y-%m-%d %H:%M:%S')


def check_time_conflict(batdau_str, ketthuc_str, existing_schedules, khoang_cach=30):
    """Kiểm tra xung đột thời gian với các suất chiếu đã có
    
    Args:
        batdau_str: Thời gian bắt đầu mới (format: 'YYYY-MM-DD HH:MM:SS' hoặc datetime object)
        ketthuc_str: Thời gian kết thúc mới (format: 'YYYY-MM-DD HH:MM:SS' hoặc datetime object)
        existing_schedules: Danh sách các suất chiếu đã có [(batdau, ketthuc), ...]
        khoang_cach: Khoảng cách tối thiểu giữa các suất (phút, mặc định 30)
    
    Returns:
        bool: True nếu có xung đột, False nếu không
    """
    # Chuyển đổi thời gian bắt đầu và kết thúc mới thành datetime object
    if isinstance(batdau_str, str):
        new_start = datetime.strptime(batdau_str, '%Y-%m-%d %H:%M:%S')
    else:
        new_start = batdau_str
    
    if isinstance(ketthuc_str, str):
        new_end = datetime.strptime(ketthuc_str, '%Y-%m-%d %H:%M:%S')
    else:
        new_end = ketthuc_str
    
    for existing_batdau, existing_ketthuc in existing_schedules:
        # Chuyển đổi thời gian đã có thành datetime object
        if isinstance(existing_batdau, str):
            existing_start = datetime.strptime(existing_batdau, '%Y-%m-%d %H:%M:%S')
        else:
            existing_start = existing_batdau
        
        if isinstance(existing_ketthuc, str):
            existing_end = datetime.strptime(existing_ketthuc, '%Y-%m-%d %H:%M:%S')
        else:
            existing_end = existing_ketthuc
        
        # Tạo vùng cấm: trừ khoảng cách buffer trước và sau
        vung_cam_start = existing_start - timedelta(minutes=khoang_cach)
        vung_cam_end = existing_end + timedelta(minutes=khoang_cach)
        
        # Kiểm tra xung đột: nếu suất mới chồng lấn với vùng cấm
        if new_start < vung_cam_end and new_end > vung_cam_start:
            return True  # Có xung đột
    
    return False  # Không có xung đột


def check_business_hours(batdau_str):
    """Kiểm tra xem suất chiếu có trong giờ hoạt động không
    
    Args:
        batdau_str: Thời gian bắt đầu (format: 'YYYY-MM-DD HH:MM:SS')
    
    Returns:
        bool: True nếu hợp lệ, False nếu không
    """
    start_dt = datetime.strptime(batdau_str, '%Y-%m-%d %H:%M:%S')
    hour = start_dt.hour
    
    # Suất chiếu phải bắt đầu trong khoảng 08:00 - 22:00
    if hour < GIO_MO_CUA or hour > GIO_DONG_CUA:
        return False
    
    return True


def get_existing_schedules(cursor, id_phongchieu, ngay_chieu):
    """Lấy danh sách suất chiếu đã có trong phòng vào ngày đó
    
    QUAN TRỌNG: Hàm này kiểm tra xung đột với CẢ 2 nguồn:
    1. Bảng kehoach_chitiet (kế hoạch suất chiếu - tất cả trạng thái)
    2. Bảng suatchieu (suất chiếu đã được duyệt)
    
    Args:
        cursor: Database cursor
        id_phongchieu: ID phòng chiếu
        ngay_chieu: Ngày chiếu (format: 'YYYY-MM-DD')
    
    Returns:
        list: Danh sách [(batdau, ketthuc), ...] từ cả kehoach_chitiet và suatchieu
    """
    schedules = []
    
    # Lấy từ kehoach_chitiet (tất cả các suất trong kế hoạch - bao gồm chờ duyệt, đã duyệt, từ chối)
    cursor.execute("""
        SELECT batdau, ketthuc 
        FROM kehoach_chitiet
        WHERE id_phongchieu = %s 
        AND DATE(batdau) = %s
    """, (id_phongchieu, ngay_chieu))
    
    results = cursor.fetchall()
    for row in results:
        schedules.append((row[0], row[1]))
    
    # Lấy từ suatchieu (tất cả các suất chiếu đã được tạo thực tế)
    cursor.execute("""
        SELECT batdau, ketthuc 
        FROM suatchieu
        WHERE id_phongchieu = %s 
        AND DATE(batdau) = %s
    """, (id_phongchieu, ngay_chieu))
    
    results = cursor.fetchall()
    for row in results:
        schedules.append((row[0], row[1]))
    
    return schedules


def create_kehoach_thang(cursor, connection, batdau_thang, ketthuc_thang):
    """Tạo hoặc lấy kế hoạch tháng"""
    # Kiểm tra xem đã có kế hoạch chưa
    cursor.execute("""
        SELECT id FROM kehoach_suatchieu 
        WHERE batdau = %s AND ketthuc = %s
    """, (batdau_thang, ketthuc_thang))
    
    result = cursor.fetchone()
    if result:
        return result[0] if USE_MYSQL_CONNECTOR else result[0]
    
    # Tạo mới kế hoạch
    cursor.execute("""
        INSERT INTO kehoach_suatchieu (batdau, ketthuc, created_at, updated_at)
        VALUES (%s, %s, NOW(), NOW())
    """, (batdau_thang, ketthuc_thang))
    
    connection.commit()
    return cursor.lastrowid


def create_kehoach_chitiet(
    cursor, connection, id_kehoach, id_phim, id_phongchieu, 
    batdau, ketthuc, tinh_trang
):
    """Tạo chi tiết kế hoạch suất chiếu"""
    cursor.execute("""
        INSERT INTO kehoach_chitiet 
        (id_kehoach, id_phim, id_phongchieu, batdau, ketthuc, tinh_trang, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW())
    """, (id_kehoach, id_phim, id_phongchieu, batdau, ketthuc, tinh_trang))
    
    connection.commit()
    return cursor.lastrowid


def create_suatchieu(cursor, connection, id_phim, id_phongchieu, batdau, ketthuc, ten_phim=None, ten_rap=None, ten_phong=None):
    """Tạo suất chiếu (chỉ khi kế hoạch đã được duyệt) và ghi log
    
    Args:
        cursor: Database cursor
        connection: Database connection
        id_phim: ID phim
        id_phongchieu: ID phòng chiếu
        batdau: Thời gian bắt đầu
        ketthuc: Thời gian kết thúc
        ten_phim: Tên phim (để ghi log, optional)
        ten_rap: Tên rạp (để ghi log, optional)
        ten_phong: Tên phòng (để ghi log, optional)
    
    Returns:
        int: ID suất chiếu mới tạo
    """
    # Kiểm tra xem đã có suất chiếu chưa
    cursor.execute("""
        SELECT id FROM suatchieu 
        WHERE id_phim = %s AND id_phongchieu = %s AND batdau = %s AND ketthuc = %s
    """, (id_phim, id_phongchieu, batdau, ketthuc))
    
    result = cursor.fetchone()
    if result:
        return result[0] if USE_MYSQL_CONNECTOR else result[0]
    
    # Tạo mới suất chiếu
    cursor.execute("""
        INSERT INTO suatchieu 
        (id_phim, id_phongchieu, batdau, ketthuc, created_at, updated_at)
        VALUES (%s, %s, %s, %s, NOW(), NOW())
    """, (id_phim, id_phongchieu, batdau, ketthuc))
    
    id_suatchieu = cursor.lastrowid
    
    # Lấy thông tin phim, rạp, phòng nếu chưa có
    if not ten_phim:
        cursor.execute("SELECT ten_phim FROM phim WHERE id = %s", (id_phim,))
        phim_result = cursor.fetchone()
        ten_phim = phim_result[0] if phim_result else 'Không rõ'
    
    if not ten_rap or not ten_phong:
        cursor.execute("""
            SELECT r.ten, p.ten 
            FROM phongchieu p
            INNER JOIN rapphim r ON p.id_rapphim = r.id
            WHERE p.id = %s
        """, (id_phongchieu,))
        rap_phong_result = cursor.fetchone()
        if rap_phong_result:
            if not ten_rap:
                ten_rap = rap_phong_result[0] if rap_phong_result[0] else 'Không rõ'
            if not ten_phong:
                ten_phong = rap_phong_result[1] if rap_phong_result[1] else 'Không rõ'
        else:
            if not ten_rap:
                ten_rap = 'Không rõ'
            if not ten_phong:
                ten_phong = 'Không rõ'
    
    # Ghi log: hanh_dong = 5 (Duyệt từ kế hoạch)
    # Lưu ý: Bảng log_suatchieu chỉ có các cột: id, id_suatchieu, hanh_dong, batdau, id_phim, ten_phim, da_xem, rap_da_xem, created_at, updated_at
    # ten_rap và ten_phong không phải là cột trong database, chúng được lấy từ relationship trong PHP
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


def generate_kehoach_for_rap(cursor, connection, id_rapphim, ten_rap):
    """Tạo kế hoạch suất chiếu cho một rạp (tháng hiện tại và tháng sau, ngày 1-30)"""
    print(f"\n{'='*60}")
    print(f"Rạp: {ten_rap} (ID: {id_rapphim})")
    print(f"{'='*60}")
    
    # Lấy danh sách phim và phòng chiếu
    phim_list = get_phim_list(cursor)
    phong_list = get_phongchieu_by_rap(cursor, id_rapphim)
    
    if not phim_list:
        print(f"  ⚠ Không có phim nào trong database!")
        return 0, 0, 0
    
    if not phong_list:
        print(f"  ⚠ Không có phòng chiếu nào cho rạp {ten_rap}!")
        return 0, 0, 0
    
    print(f"  Tìm thấy {len(phim_list)} phim")
    print(f"  Tìm thấy {len(phong_list)} phòng chiếu")
    
    # Tính toán tháng hiện tại và tháng sau
    today = datetime.now().date()
    
    total_cho_duyet = 0
    total_da_duyet = 0
    total_tu_choi = 0
    
    # Tạo kế hoạch cho 2 tháng: tháng hiện tại và tháng sau
    for thang_index in range(2):
        if thang_index == 0:
            # Tháng hiện tại
            batdau_thang = get_start_of_month(today)
        else:
            # Tháng sau
            # Tính tháng sau bằng cách cộng thêm 1 tháng
            if today.month == 12:
                batdau_thang = today.replace(year=today.year + 1, month=1, day=1)
            else:
                batdau_thang = today.replace(month=today.month + 1, day=1)
        
        ketthuc_thang = get_end_of_month(batdau_thang, max_day=30)
        
        batdau_thang_str = batdau_thang.strftime('%Y-%m-%d')
        ketthuc_thang_str = ketthuc_thang.strftime('%Y-%m-%d')
        
        print(f"\n  Tháng {thang_index + 1}: {batdau_thang_str} đến {ketthuc_thang_str}")
        
        # Tạo hoặc lấy kế hoạch tháng
        id_kehoach = create_kehoach_thang(cursor, connection, batdau_thang_str, ketthuc_thang_str)
        
        # Tạo suất chiếu cho mỗi ngày trong tháng (ngày 1-30)
        for day_offset in range(30):  # 30 ngày
            ngay_chieu = batdau_thang + timedelta(days=day_offset)
            ngay_chieu_str = ngay_chieu.strftime('%Y-%m-%d')
            
            # Chọn ngẫu nhiên 2-4 phim cho mỗi ngày
            so_phim_ngay = random.randint(2, min(4, len(phim_list)))
            phim_ngay = random.sample(phim_list, so_phim_ngay)
            
            # Chọn ngẫu nhiên 1-2 phòng cho mỗi phim
            for phim in phim_ngay:
                # Kiểm tra xem phim đã công chiếu chưa
                ngay_cong_chieu = phim['ngay_cong_chieu']
                if isinstance(ngay_cong_chieu, str):
                    ngay_cong_chieu = datetime.strptime(ngay_cong_chieu, '%Y-%m-%d').date()
                elif isinstance(ngay_cong_chieu, datetime):
                    ngay_cong_chieu = ngay_cong_chieu.date()
                
                if ngay_chieu < ngay_cong_chieu:
                    # Bỏ qua nếu ngày chiếu trước ngày công chiếu
                    continue
                
                so_phong = random.randint(1, min(2, len(phong_list)))
                phong_ngay = random.sample(phong_list, so_phong)
                
                for phong in phong_ngay:
                    # Lấy danh sách suất chiếu đã có trong phòng vào ngày này
                    existing_schedules = get_existing_schedules(
                        cursor, phong['id'], ngay_chieu_str
                    )
                    
                    # Chọn 2-4 khung giờ cho mỗi phim/phòng
                    so_suat = random.randint(2, 4)
                    khung_gio_chon = random.sample(KHUNG_GIO_CHIEU, min(so_suat, len(KHUNG_GIO_CHIEU)))
                    
                    suat_created = 0
                    max_attempts = len(KHUNG_GIO_CHIEU) * 2  # Giới hạn số lần thử
                    attempts = 0
                    
                    for gio in khung_gio_chon:
                        if attempts >= max_attempts:
                            break
                        
                        batdau_str = f"{ngay_chieu_str} {gio}:00"
                        
                        # Kiểm tra giờ hoạt động
                        if not check_business_hours(batdau_str):
                            attempts += 1
                            continue
                        
                        ketthuc_str = calculate_end_time(
                            batdau_str, 
                            phim['thoi_luong']
                        )
                        
                        # Kiểm tra xung đột với các suất đã có
                        if check_time_conflict(
                            batdau_str, ketthuc_str, 
                            existing_schedules, 
                            khoang_cach=KHOANG_CACH_GIUA_SUAT
                        ):
                            attempts += 1
                            continue  # Bỏ qua nếu xung đột
                        
                        # Kiểm tra xem suất chiếu có kết thúc sau 22:00 không
                        ketthuc_dt = datetime.strptime(ketthuc_str, '%Y-%m-%d %H:%M:%S')
                        if ketthuc_dt.hour > GIO_DONG_CUA or (ketthuc_dt.hour == GIO_DONG_CUA and ketthuc_dt.minute > 0):
                            attempts += 1
                            continue  # Bỏ qua nếu kết thúc sau 22:00
                        
                        # Chọn trạng thái ngẫu nhiên: 0 - Chờ duyệt (40%), 1 - Đã duyệt (40%), 2 - Từ chối (20%)
                        rand = random.random()
                        if rand < 0.4:
                            tinh_trang = 0  # Chờ duyệt
                        elif rand < 0.8:
                            tinh_trang = 1  # Đã duyệt
                        else:
                            tinh_trang = 2  # Từ chối
                        
                        try:
                            # Tạo chi tiết kế hoạch
                            id_kehoach_chitiet = create_kehoach_chitiet(
                                cursor, connection,
                                id_kehoach, phim['id'], phong['id'],
                                batdau_str, ketthuc_str, tinh_trang
                            )
                            
                            # Thêm vào danh sách suất đã có để tránh xung đột cho các suất tiếp theo
                            existing_schedules.append((batdau_str, ketthuc_str))
                            
                            if tinh_trang == 0:
                                total_cho_duyet += 1
                            elif tinh_trang == 1:
                                total_da_duyet += 1
                                # Nếu đã duyệt, tạo suất chiếu và ghi log
                                create_suatchieu(
                                    cursor, connection,
                                    phim['id'], phong['id'],
                                    batdau_str, ketthuc_str,
                                    ten_phim=phim['ten_phim'],
                                    ten_rap=ten_rap,
                                    ten_phong=phong['ten']
                                )
                            else:
                                total_tu_choi += 1
                            
                            suat_created += 1
                            attempts = 0  # Reset attempts khi tạo thành công
                        
                        except Exception as e:
                            connection.rollback()
                            print(f"    ⚠ Lỗi khi tạo suất chiếu {batdau_str}: {e}")
                            attempts += 1
                            continue
                    
                    # Nếu không tạo được suất nào, thử với các khung giờ khác
                    if suat_created == 0:
                        # Thử với tất cả các khung giờ còn lại
                        for gio in KHUNG_GIO_CHIEU:
                            if gio in khung_gio_chon:
                                continue  # Đã thử rồi
                            
                            if attempts >= max_attempts:
                                break
                            
                            batdau_str = f"{ngay_chieu_str} {gio}:00"
                            
                            if not check_business_hours(batdau_str):
                                attempts += 1
                                continue
                            
                            ketthuc_str = calculate_end_time(
                                batdau_str, 
                                phim['thoi_luong']
                            )
                            
                            if check_time_conflict(
                                batdau_str, ketthuc_str, 
                                existing_schedules, 
                                khoang_cach=KHOANG_CACH_GIUA_SUAT
                            ):
                                attempts += 1
                                continue
                            
                            ketthuc_dt = datetime.strptime(ketthuc_str, '%Y-%m-%d %H:%M:%S')
                            if ketthuc_dt.hour > GIO_DONG_CUA or (ketthuc_dt.hour == GIO_DONG_CUA and ketthuc_dt.minute > 0):
                                attempts += 1
                                continue
                            
                            rand = random.random()
                            if rand < 0.4:
                                tinh_trang = 0
                            elif rand < 0.8:
                                tinh_trang = 1
                            else:
                                tinh_trang = 2
                            
                            try:
                                id_kehoach_chitiet = create_kehoach_chitiet(
                                    cursor, connection,
                                    id_kehoach, phim['id'], phong['id'],
                                    batdau_str, ketthuc_str, tinh_trang
                                )
                                
                                existing_schedules.append((batdau_str, ketthuc_str))
                                
                                if tinh_trang == 0:
                                    total_cho_duyet += 1
                                elif tinh_trang == 1:
                                    total_da_duyet += 1
                                    # Nếu đã duyệt, tạo suất chiếu và ghi log
                                    create_suatchieu(
                                        cursor, connection,
                                        phim['id'], phong['id'],
                                        batdau_str, ketthuc_str,
                                        ten_phim=phim['ten_phim'],
                                        ten_rap=ten_rap,
                                        ten_phong=phong['ten']
                                    )
                                else:
                                    total_tu_choi += 1
                                
                                suat_created += 1
                                attempts = 0
                                break  # Đã tạo được, dừng lại
                            
                            except Exception as e:
                                connection.rollback()
                                attempts += 1
                                continue
        
        print(f"    ✓ Đã tạo kế hoạch cho tháng {thang_index + 1}")
    
    print(f"\n  Tổng kết:")
    print(f"    - Chờ duyệt: {total_cho_duyet}")
    print(f"    - Đã duyệt: {total_da_duyet}")
    print(f"    - Từ chối: {total_tu_choi}")
    
    return total_cho_duyet, total_da_duyet, total_tu_choi


def main():
    """Hàm chính"""
    # Lấy ID rạp từ command line (nếu có)
    id_rap_chon = None
    if len(sys.argv) > 1:
        try:
            id_rap_chon = int(sys.argv[1])
            print(f"Sẽ tạo kế hoạch cho rạp có ID: {id_rap_chon}")
        except ValueError:
            print("ID rạp không hợp lệ. Sẽ tạo cho tất cả rạp.")
            id_rap_chon = None
    
    print("="*60)
    print("FAKE KẾ HOẠCH SUẤT CHIẾU")
    print("="*60)
    print(f"Thời gian: Tháng hiện tại (ngày 1-30) và Tháng sau (ngày 1-30)")
    print(f"Sử dụng: {'mysql-connector-python' if USE_MYSQL_CONNECTOR else 'pymysql'}")
    print("="*60)
    
    # Kết nối database
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
                cursor.execute("SET CHARACTER SET utf8mb4")
                cursor.execute("SET character_set_connection=utf8mb4")
                cursor.execute("SET character_set_client=utf8mb4")
                cursor.execute("SET character_set_results=utf8mb4")
                print("✓ Đã kết nối database thành công!")
            except (pymysql.Error, UnicodeEncodeError) as e:
                print(f"✗ Lỗi kết nối database: {e}")
                print("\nGợi ý: Cài đặt mysql-connector-python để xử lý password có ký tự đặc biệt:")
                print("  pip install mysql-connector-python")
                sys.exit(1)
        
        # Lấy danh sách rạp
        if id_rap_chon:
            # Lấy thông tin rạp cụ thể
            cursor.execute("SELECT id, ten FROM rapphim WHERE id = %s AND trang_thai = 1", (id_rap_chon,))
            result = cursor.fetchone()
            if result:
                rapphim_list = [(result[0], result[1])]
            else:
                print(f"\n✗ Không tìm thấy rạp có ID {id_rap_chon} hoặc rạp không hoạt động!")
                cursor.close()
                connection.close()
                sys.exit(1)
        else:
            # Lấy tất cả rạp
            rapphim_list = get_rapphim_list(cursor)
        
        if not rapphim_list:
            print("\n✗ Không tìm thấy rạp phim nào trong database!")
            cursor.close()
            connection.close()
            sys.exit(1)
        
        print(f"\n✓ Tìm thấy {len(rapphim_list)} rạp phim")
        print(f"✓ Bắt đầu tạo kế hoạch suất chiếu...")
        
        total_cho_duyet = 0
        total_da_duyet = 0
        total_tu_choi = 0
        
        for id_rap, ten_rap in rapphim_list:
            cho_duyet, da_duyet, tu_choi = generate_kehoach_for_rap(
                cursor, connection, id_rap, ten_rap
            )
            total_cho_duyet += cho_duyet
            total_da_duyet += da_duyet
            total_tu_choi += tu_choi
        
        print(f"\n{'='*60}")
        print(f"HOÀN THÀNH!")
        print(f"{'='*60}")
        print(f"Số rạp đã xử lý: {len(rapphim_list)}")
        print(f"Thời gian: 2 tháng (tháng hiện tại + tháng sau, mỗi tháng 30 ngày)")
        print(f"\nTổng kết trạng thái:")
        print(f"  - Chờ duyệt: {total_cho_duyet}")
        print(f"  - Đã duyệt: {total_da_duyet} (đã tạo suất chiếu và ghi log)")
        print(f"  - Từ chối: {total_tu_choi}")
        print(f"  - TỔNG: {total_cho_duyet + total_da_duyet + total_tu_choi}")
        print(f"{'='*60}")
        
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


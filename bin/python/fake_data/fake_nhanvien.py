#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script sinh dữ liệu nhân viên giả cho hệ thống Epic Cinema

Yêu cầu:
    - Python 3.6+
    - mysql-connector-python: pip install mysql-connector-python (khuyến nghị, hỗ trợ UTF-8 tốt)
    - hoặc pymysql: pip install pymysql (có thể gặp vấn đề với password có ký tự đặc biệt)
    - argon2-cffi: pip install argon2-cffi (khuyến nghị để hash mật khẩu Argon2ID giống PHP)

Cách sử dụng:
    1. Cài đặt dependencies (khuyến nghị):
       pip install mysql-connector-python argon2-cffi
    
    2. Chạy script:
       python fake_nhanvien.py [số_lượng]
    
    3. Ví dụ:
       python fake_nhanvien.py 20    # Tạo 20 nhân viên
       python fake_nhanvien.py       # Tạo 10 nhân viên (mặc định)

Cấu hình:
    Script tự động đọc file .env từ thư mục gốc của dự án.
    Nếu không tìm thấy file .env, sẽ sử dụng giá trị mặc định:
    - DB_HOST (mặc định: localhost)
    - DB_USERNAME (mặc định: admin_epic)
    - DB_PASSWORD (mặc định: epic2025)
    - DB_DATABASE (mặc định: epic)

Lưu ý:
    - Mật khẩu mặc định cho tất cả nhân viên: 12345678
    - Mật khẩu được hash bằng Argon2ID (giống PHP PASSWORD_ARGON2ID)
    - Script sẽ tự động lấy danh sách rạp phim từ database
    - Mỗi nhân viên sẽ được gán ngẫu nhiên vào một rạp phim
    - Trạng thái nhân viên: 70% đang hoạt động, 15% đã khóa, 15% đã nghỉ việc
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
import hashlib
from datetime import datetime
import os
import sys

# Thêm đường dẫn để import các module khác nếu cần
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

# Tìm đường dẫn đến file .env (thư mục gốc của dự án)
# fake_nhanvien.py ở: bin/python/fake_data/fake_nhanvien.py
# .env ở: .env (thư mục gốc)
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
        # mysql-connector-python hỗ trợ UTF-8 tốt hơn
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
        # pymysql config
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

# Danh sách họ và tên tiếng Việt
HO_VIET_NAM = [
    'Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ',
    'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý', 'Đinh', 'Mai', 'Tô',
    'Trương', 'Đào', 'Cao', 'Lương', 'Thái', 'Vương', 'Hà', 'Lâm', 'Tạ',
    'Chu', 'Lưu', 'Phùng', 'Đoàn', 'Bạch', 'Hứa', 'Tôn', 'Tống', 'Quách',
    'Dư', 'Hồng', 'Tăng', 'Văn', 'Vĩnh', 'Thạch', 'Kim', 'La', 'Lạc'
]

TEN_VIET_NAM = [
    'An', 'Anh', 'Bảo', 'Bình', 'Cường', 'Dũng', 'Đức', 'Giang', 'Hải',
    'Hùng', 'Huy', 'Khang', 'Khoa', 'Long', 'Minh', 'Nam', 'Phong', 'Quang',
    'Sơn', 'Thành', 'Thắng', 'Thiện', 'Tiến', 'Trung', 'Tuấn', 'Tùng', 'Việt',
    'Vinh', 'Vũ', 'Xuân', 'Yên', 'Ánh', 'Bích', 'Chi', 'Diệu', 'Giang',
    'Hà', 'Hạnh', 'Hoa', 'Hồng', 'Hương', 'Lan', 'Linh', 'Loan', 'Mai',
    'My', 'Nga', 'Ngọc', 'Nhung', 'Phương', 'Quỳnh', 'Thảo', 'Thúy', 'Trang',
    'Trinh', 'Tuyết', 'Uyên', 'Vân', 'Vy', 'Yến', 'Anh', 'Dung', 'Hạnh'
]

# Tên đệm
TEN_DEM = [
    'Văn', 'Thị', 'Đức', 'Minh', 'Quang', 'Thành', 'Công', 'Hữu', 'Văn',
    'Thị', 'Ngọc', 'Thanh', 'Hoàng', 'Xuân', 'Hồng', 'Thúy', 'Kim', 'Bảo'
]


def hash_password(password):
    """Hash mật khẩu bằng Argon2ID (tương thích với PHP password_hash PASSWORD_ARGON2ID)"""
    try:
        from argon2 import PasswordHasher
        ph = PasswordHasher()
        # Hash với Argon2ID (giống PHP PASSWORD_ARGON2ID)
        hashed = ph.hash(password)
        return hashed
    except ImportError:
        try:
            # Thử với passlib
            from passlib.hash import argon2
            hashed = argon2.using(rounds=4, memory_cost=65536, parallelism=3, hash_len=32).hash(password)
            return hashed
        except ImportError:
            print("Cảnh báo: Không tìm thấy argon2-cffi hoặc passlib!")
            print("Đang sử dụng bcrypt (không tương thích với PHP PASSWORD_ARGON2ID)")
            print("Khuyến nghị: pip install argon2-cffi")
            try:
                import bcrypt
                salt = bcrypt.gensalt()
                hashed = bcrypt.hashpw(password.encode('utf-8'), salt)
                return hashed.decode('utf-8')
            except ImportError:
                # Fallback cuối cùng: SHA256 (không an toàn, chỉ để test)
                return hashlib.sha256(password.encode('utf-8')).hexdigest()


def generate_vietnamese_name():
    """Tạo tên tiếng Việt ngẫu nhiên"""
    ho = random.choice(HO_VIET_NAM)
    ten_dem = random.choice(TEN_DEM) if random.random() > 0.3 else ''
    ten = random.choice(TEN_VIET_NAM)
    
    if ten_dem:
        return f"{ho} {ten_dem} {ten}"
    return f"{ho} {ten}"


def generate_phone():
    """Tạo số điện thoại Việt Nam"""
    prefixes = ['090', '091', '092', '093', '094', '096', '097', '098', '032', '033', '034', '035', '036', '037', '038', '039']
    prefix = random.choice(prefixes)
    number = ''.join([str(random.randint(0, 9)) for _ in range(7)])
    return f"{prefix}{number}"


def generate_email(ten):
    """Tạo email từ tên"""
    # Loại bỏ dấu và chuyển thành chữ thường
    import unicodedata
    ten_khong_dau = unicodedata.normalize('NFKD', ten).encode('ascii', 'ignore').decode('ascii')
    ten_khong_dau = ten_khong_dau.lower().replace(' ', '')
    # Thêm số ngẫu nhiên để tránh trùng
    random_num = random.randint(100, 9999)
    domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'epiccinema.vn']
    domain = random.choice(domains)
    return f"{ten_khong_dau}{random_num}@{domain}"


def generate_username(ten):
    """Tạo tên đăng nhập từ tên"""
    import unicodedata
    ten_khong_dau = unicodedata.normalize('NFKD', ten).encode('ascii', 'ignore').decode('ascii')
    ten_khong_dau = ten_khong_dau.lower().replace(' ', '')
    random_num = random.randint(100, 9999)
    return f"{ten_khong_dau}{random_num}"


def get_rapphim_ids(cursor):
    """Lấy danh sách ID rạp phim"""
    cursor.execute("SELECT id FROM rapphim WHERE trang_thai = 1")
    results = cursor.fetchall()
    # Xử lý kết quả khác nhau giữa mysql-connector và pymysql
    if results:
        if USE_MYSQL_CONNECTOR:
            return [row[0] for row in results]
        else:
            return [row[0] for row in results]
    return [1]  # Mặc định là 1 nếu không có rạp nào


def create_nhan_vien(cursor, connection, so_luong=10):
    """Tạo nhân viên giả"""
    # Lấy danh sách rạp phim
    rapphim_ids = get_rapphim_ids(cursor)
    
    if not rapphim_ids:
        print("Không tìm thấy rạp phim nào trong database!")
        return
    
    print(f"Bắt đầu tạo {so_luong} nhân viên...")
    print(f"Danh sách rạp phim: {rapphim_ids}")
    
    success_count = 0
    error_count = 0
    
    for i in range(so_luong):
        try:
            # Tạo thông tin nhân viên
            ten = generate_vietnamese_name()
            email = generate_email(ten)
            dien_thoai = generate_phone()
            ten_dang_nhap = generate_username(ten)
            mat_khau = "12345678"  # Mật khẩu mặc định (giống PHP)
            id_rapphim = random.choice(rapphim_ids)
            trang_thai = random.choice([1, 1, 1, 0, -1])  # 70% đang hoạt động, 15% khóa, 15% nghỉ việc
            id_vaitro = 4  # Vai trò nhân viên
            
            # Hash mật khẩu
            matkhau_bam = hash_password(mat_khau)
            
            # Tạo tài khoản trước
            cursor.execute("""
                INSERT INTO taikhoan_noibo (tendangnhap, matkhau_bam, id_vaitro, created_at, updated_at)
                VALUES (%s, %s, %s, NOW(), NOW())
            """, (ten_dang_nhap, matkhau_bam, id_vaitro))
            
            id_taikhoan = cursor.lastrowid
            
            # Tạo nhân viên
            cursor.execute("""
                INSERT INTO nguoidung_noibo 
                (id_taikhoan, id_rapphim, ten, email, dien_thoai, trang_thai, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW())
            """, (id_taikhoan, id_rapphim, ten, email, dien_thoai, trang_thai))
            
            connection.commit()
            success_count += 1
            print(f"[{i+1}/{so_luong}] ✓ Đã tạo nhân viên: {ten} ({email}) - Rạp ID: {id_rapphim}")
            
        except Exception as e:
            connection.rollback()
            error_count += 1
            # Kiểm tra loại lỗi
            error_type = type(e).__name__
            error_msg = str(e)
            if 'IntegrityError' in error_type or 'Duplicate' in error_msg or '1062' in error_msg:
                print(f"[{i+1}/{so_luong}] ✗ Lỗi trùng dữ liệu: {e}")
            else:
                print(f"[{i+1}/{so_luong}] ✗ Lỗi: {e}")
            continue
    
    print(f"\n{'='*60}")
    print(f"Hoàn thành!")
    print(f"Thành công: {success_count}")
    print(f"Lỗi: {error_count}")
    print(f"{'='*60}")


def main():
    """Hàm chính
    
    Cách sử dụng:
        python fake_nhanvien.py [số_lượng]
    
    Ví dụ:
        python fake_nhanvien.py 20    # Tạo 20 nhân viên
        python fake_nhanvien.py       # Tạo 10 nhân viên (mặc định)
    """
    if len(sys.argv) > 1:
        try:
            so_luong = int(sys.argv[1])
            if so_luong <= 0:
                print("Số lượng phải lớn hơn 0. Sử dụng mặc định: 10")
                so_luong = 10
        except ValueError:
            print("Số lượng không hợp lệ. Sử dụng mặc định: 10")
            so_luong = 10
    else:
        so_luong = 10
    
    # Kết nối database
    try:
        config = DB_CONFIG.copy()
        
        if USE_MYSQL_CONNECTOR:
            # Sử dụng mysql-connector-python (hỗ trợ UTF-8 tốt hơn, giống PHP)
            try:
                connection = mysql.connector.connect(**config)
                cursor = connection.cursor()
                print("Đã kết nối database thành công (sử dụng mysql-connector-python)!")
            except Error as e:
                print(f"Lỗi kết nối database: {e}")
                sys.exit(1)
        else:
            # Sử dụng pymysql (có thể gặp vấn đề với password có ký tự đặc biệt)
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
        
        # Tạo nhân viên
        create_nhan_vien(cursor, connection, so_luong)
        
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


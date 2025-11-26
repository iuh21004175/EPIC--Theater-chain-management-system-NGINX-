#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script sinh dữ liệu khách hàng giả cho hệ thống Epic Cinema

Yêu cầu:
    - Python 3.6+
    - mysql-connector-python: pip install mysql-connector-python (khuyến nghị, hỗ trợ UTF-8 tốt)
    - hoặc pymysql: pip install pymysql (có thể gặp vấn đề với password có ký tự đặc biệt)
    - bcrypt: pip install bcrypt (để hash mật khẩu giống PHP PASSWORD_DEFAULT)

Cách sử dụng:
    1. Cài đặt dependencies (khuyến nghị):
       pip install mysql-connector-python bcrypt
    
    2. Chạy script:
       python fake_khachhang.py [số_lượng]
    
    3. Ví dụ:
       python fake_khachhang.py 50    # Tạo 50 khách hàng
       python fake_khachhang.py       # Tạo 20 khách hàng (mặc định)

Cấu hình:
    Script tự động đọc file .env từ thư mục gốc của dự án.
    Nếu không tìm thấy file .env, sẽ sử dụng giá trị mặc định:
    - DB_HOST (mặc định: localhost)
    - DB_USERNAME (mặc định: admin_epic)
    - DB_PASSWORD (mặc định: epic2025)
    - DB_DATABASE (mặc định: epic)

Lưu ý:
    - Mật khẩu mặc định cho tất cả khách hàng: 12345678
    - Mật khẩu được hash bằng bcrypt (giống PHP PASSWORD_DEFAULT)
    - Email sẽ được tạo tự động từ tên, tránh trùng lặp
    - Số điện thoại theo định dạng Việt Nam (10 số, bắt đầu bằng 0)
    - Ngày sinh ngẫu nhiên từ 18-70 tuổi
    - 70% khách hàng có giới tính Nam, 30% Nữ
    - 20% khách hàng có google_id (đăng nhập bằng Google)
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
from datetime import datetime, timedelta
import os
import sys
import unicodedata

# Thêm đường dẫn để import các module khác nếu cần
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

# Tìm đường dẫn đến file .env (thư mục gốc của dự án)
# fake_khachhang.py ở: bin/python/fake_data/fake_khachhang.py
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

TEN_NAM = [
    'An', 'Anh', 'Bảo', 'Bình', 'Cường', 'Dũng', 'Đức', 'Giang', 'Hải',
    'Hùng', 'Huy', 'Khang', 'Khoa', 'Long', 'Minh', 'Nam', 'Phong', 'Quang',
    'Sơn', 'Thành', 'Thắng', 'Thiện', 'Tiến', 'Trung', 'Tuấn', 'Tùng', 'Việt',
    'Vinh', 'Vũ', 'Xuân', 'Yên', 'Bảo', 'Đăng', 'Hào', 'Kiên', 'Lâm'
]

TEN_NU = [
    'Ánh', 'Bích', 'Chi', 'Diệu', 'Giang', 'Hà', 'Hạnh', 'Hoa', 'Hồng',
    'Hương', 'Lan', 'Linh', 'Loan', 'Mai', 'My', 'Nga', 'Ngọc', 'Nhung',
    'Phương', 'Quỳnh', 'Thảo', 'Thúy', 'Trang', 'Trinh', 'Tuyết', 'Uyên',
    'Vân', 'Vy', 'Yến', 'Anh', 'Dung', 'Hạnh', 'Huyền', 'Khánh', 'Ly'
]

# Tên đệm
TEN_DEM = [
    'Văn', 'Thị', 'Đức', 'Minh', 'Quang', 'Thành', 'Công', 'Hữu',
    'Ngọc', 'Thanh', 'Hoàng', 'Xuân', 'Hồng', 'Thúy', 'Kim', 'Bảo'
]


def hash_password(password):
    """Hash mật khẩu bằng bcrypt (tương thích với PHP password_hash PASSWORD_DEFAULT)"""
    try:
        import bcrypt
        salt = bcrypt.gensalt()
        hashed = bcrypt.hashpw(password.encode('utf-8'), salt)
        return hashed.decode('utf-8')
    except ImportError:
        print("Cảnh báo: Không tìm thấy bcrypt!")
        print("Đang sử dụng SHA256 (không an toàn, chỉ để test)")
        print("Khuyến nghị: pip install bcrypt")
        return hashlib.sha256(password.encode('utf-8')).hexdigest()


def generate_vietnamese_name(gioi_tinh=None):
    """Tạo tên tiếng Việt ngẫu nhiên
    
    Args:
        gioi_tinh: 0 (Nam) hoặc 1 (Nữ). Nếu None, sẽ chọn ngẫu nhiên.
    """
    ho = random.choice(HO_VIET_NAM)
    
    # Chọn giới tính nếu chưa có
    if gioi_tinh is None:
        gioi_tinh = 0 if random.random() < 0.7 else 1  # 70% Nam, 30% Nữ
    
    # Chọn tên đệm và tên dựa trên giới tính
    if gioi_tinh == 0:  # Nam
        ten_dem = random.choice(['Văn', 'Đức', 'Minh', 'Quang', 'Thành', 'Công', 'Hữu', '']) if random.random() > 0.3 else ''
        ten = random.choice(TEN_NAM)
    else:  # Nữ
        ten_dem = random.choice(['Thị', 'Ngọc', 'Thanh', 'Hồng', 'Thúy', 'Kim', '']) if random.random() > 0.3 else ''
        ten = random.choice(TEN_NU)
    
    if ten_dem:
        return f"{ho} {ten_dem} {ten}", gioi_tinh
    return f"{ho} {ten}", gioi_tinh


def generate_phone():
    """Tạo số điện thoại Việt Nam"""
    prefixes = ['090', '091', '092', '093', '094', '096', '097', '098', 
                '032', '033', '034', '035', '036', '037', '038', '039',
                '070', '076', '077', '078', '079', '081', '082', '083', '084', '085']
    prefix = random.choice(prefixes)
    number = ''.join([str(random.randint(0, 9)) for _ in range(7)])
    return f"{prefix}{number}"


def generate_email(ten):
    """Tạo email từ tên"""
    # Loại bỏ dấu và chuyển thành chữ thường
    ten_khong_dau = unicodedata.normalize('NFKD', ten).encode('ascii', 'ignore').decode('ascii')
    ten_khong_dau = ten_khong_dau.lower().replace(' ', '')
    # Thêm số ngẫu nhiên để tránh trùng
    random_num = random.randint(100, 9999)
    domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'epiccinema.vn']
    domain = random.choice(domains)
    return f"{ten_khong_dau}{random_num}@{domain}"


def generate_ngay_sinh():
    """Tạo ngày sinh ngẫu nhiên (18-70 tuổi)"""
    today = datetime.now().date()
    # Tuổi từ 18 đến 70
    tuoi = random.randint(18, 70)
    # Ngày sinh = hôm nay - tuổi năm
    ngay_sinh = today - timedelta(days=tuoi * 365 + random.randint(0, 364))
    return ngay_sinh.strftime('%Y-%m-%d')


def generate_google_id():
    """Tạo Google ID giả (20% khách hàng có Google ID)"""
    if random.random() < 0.2:  # 20% có Google ID
        # Google ID thường là số dài
        return str(random.randint(100000000000000000000, 999999999999999999999))
    return None


def check_email_exists(cursor, email):
    """Kiểm tra email đã tồn tại chưa"""
    cursor.execute("SELECT COUNT(*) FROM khach_hang WHERE email = %s", (email,))
    result = cursor.fetchone()
    count = result[0] if result else 0
    return count > 0


def create_khach_hang(cursor, connection, so_luong=20):
    """Tạo khách hàng giả"""
    print(f"Bắt đầu tạo {so_luong} khách hàng...")
    
    success_count = 0
    error_count = 0
    email_duplicates = 0
    
    for i in range(so_luong):
        try:
            # Tạo thông tin khách hàng
            ho_ten, gioi_tinh = generate_vietnamese_name()
            email = generate_email(ho_ten)
            
            # Kiểm tra email trùng (thử tối đa 5 lần)
            attempts = 0
            while check_email_exists(cursor, email) and attempts < 5:
                email = generate_email(ho_ten)
                attempts += 1
            
            if check_email_exists(cursor, email):
                email_duplicates += 1
                print(f"[{i+1}/{so_luong}] ⚠ Bỏ qua do email trùng: {email}")
                continue
            
            so_dien_thoai = generate_phone()
            ngay_sinh = generate_ngay_sinh()
            mat_khau = "12345678"  # Mật khẩu mặc định
            google_id = generate_google_id()
            
            # Hash mật khẩu
            matkhau_bam = hash_password(mat_khau)
            
            # Tạo khách hàng
            if google_id:
                cursor.execute("""
                    INSERT INTO khach_hang 
                    (ho_ten, email, gioi_tinh, ngay_sinh, so_dien_thoai, mat_khau, google_id, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
                """, (ho_ten, email, gioi_tinh, ngay_sinh, so_dien_thoai, matkhau_bam, google_id))
            else:
                cursor.execute("""
                    INSERT INTO khach_hang 
                    (ho_ten, email, gioi_tinh, ngay_sinh, so_dien_thoai, mat_khau, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW())
                """, (ho_ten, email, gioi_tinh, ngay_sinh, so_dien_thoai, matkhau_bam))
            
            connection.commit()
            success_count += 1
            gioi_tinh_str = "Nam" if gioi_tinh == 0 else "Nữ"
            google_str = f" (Google ID: {google_id})" if google_id else ""
            print(f"[{i+1}/{so_luong}] ✓ Đã tạo khách hàng: {ho_ten} ({email}) - {gioi_tinh_str}{google_str}")
            
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
    if email_duplicates > 0:
        print(f"Email trùng (đã bỏ qua): {email_duplicates}")
    print(f"{'='*60}")


def main():
    """Hàm chính
    
    Cách sử dụng:
        python fake_khachhang.py [số_lượng]
    
    Ví dụ:
        python fake_khachhang.py 50    # Tạo 50 khách hàng
        python fake_khachhang.py       # Tạo 20 khách hàng (mặc định)
    """
    if len(sys.argv) > 1:
        try:
            so_luong = int(sys.argv[1])
            if so_luong <= 0:
                print("Số lượng phải lớn hơn 0. Sử dụng mặc định: 20")
                so_luong = 20
        except ValueError:
            print("Số lượng không hợp lệ. Sử dụng mặc định: 20")
            so_luong = 20
    else:
        so_luong = 20
    
    print(f"Bắt đầu tạo {so_luong} khách hàng...")
    print(f"Sử dụng {'mysql-connector-python' if USE_MYSQL_CONNECTOR else 'pymysql'}")
    
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
        
        # Tạo khách hàng
        create_khach_hang(cursor, connection, so_luong)
        
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


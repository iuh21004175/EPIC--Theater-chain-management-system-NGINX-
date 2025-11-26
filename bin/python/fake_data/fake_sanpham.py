#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script sinh dữ liệu sản phẩm giả cho hệ thống Epic Cinema

Yêu cầu:
    - Python 3.6+
    - mysql-connector-python: pip install mysql-connector-python (khuyến nghị, hỗ trợ UTF-8 tốt)
    - hoặc pymysql: pip install pymysql

Cách sử dụng:
    1. Cài đặt dependencies (khuyến nghị):
       pip install mysql-connector-python
    
    2. Chạy script:
       python fake_sanpham.py [số_lượng_mỗi_rạp]
    
    3. Ví dụ:
       python fake_sanpham.py 10    # Tạo 10 sản phẩm cho mỗi rạp
       python fake_sanpham.py       # Tạo 5 sản phẩm cho mỗi rạp (mặc định)

Lưu ý:
    - Script sẽ tạo sản phẩm cho TỪNG rạp phim
    - Mỗi rạp sẽ có số lượng sản phẩm riêng
    - Mỗi sản phẩm sẽ được gán ngẫu nhiên vào một danh mục
    - Trạng thái sản phẩm: 80% đang bán, 20% ngừng bán
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

# Danh sách sản phẩm phổ biến trong rạp chiếu phim
SAN_PHAM_TEMPLATES = [
    # Bỏng ngô
    {'ten': 'Bỏng ngô ngọt', 'mo_ta': 'Bỏng ngô ngọt thơm ngon, giòn tan', 'gia': [25000, 35000, 45000]},
    {'ten': 'Bỏng ngô mặn', 'mo_ta': 'Bỏng ngô mặn đậm đà, hấp dẫn', 'gia': [25000, 35000, 45000]},
    {'ten': 'Bỏng ngô phô mai', 'mo_ta': 'Bỏng ngô phô mai béo ngậy, thơm lừng', 'gia': [30000, 40000, 50000]},
    {'ten': 'Bỏng ngô caramel', 'mo_ta': 'Bỏng ngô caramel ngọt ngào, đậm vị', 'gia': [30000, 40000, 50000]},
    
    # Nước uống
    {'ten': 'Coca Cola', 'mo_ta': 'Nước ngọt có ga Coca Cola mát lạnh', 'gia': [20000, 25000, 30000]},
    {'ten': 'Pepsi', 'mo_ta': 'Nước ngọt có ga Pepsi sảng khoái', 'gia': [20000, 25000, 30000]},
    {'ten': '7Up', 'mo_ta': 'Nước ngọt có ga 7Up tươi mát', 'gia': [20000, 25000, 30000]},
    {'ten': 'Nước suối', 'mo_ta': 'Nước suối tinh khiết, mát lạnh', 'gia': [15000, 20000]},
    {'ten': 'Nước cam ép', 'mo_ta': 'Nước cam ép tươi ngon, bổ dưỡng', 'gia': [30000, 35000]},
    {'ten': 'Nước chanh dây', 'mo_ta': 'Nước chanh dây chua ngọt, giải nhiệt', 'gia': [30000, 35000]},
    
    # Snack
    {'ten': 'Khoai tây chiên', 'mo_ta': 'Khoai tây chiên giòn tan, vàng ruộm', 'gia': [35000, 45000]},
    {'ten': 'Bánh quy', 'mo_ta': 'Bánh quy thơm ngon, giòn rụm', 'gia': [25000, 30000]},
    {'ten': 'Snack khoai tây', 'mo_ta': 'Snack khoai tây giòn, đậm vị', 'gia': [20000, 25000]},
    {'ten': 'Bánh gạo', 'mo_ta': 'Bánh gạo giòn, nhẹ nhàng', 'gia': [25000, 30000]},
    
    # Đồ ăn nhanh
    {'ten': 'Hot dog', 'mo_ta': 'Xúc xích hot dog thơm ngon, nóng hổi', 'gia': [40000, 50000]},
    {'ten': 'Bánh mì kẹp', 'mo_ta': 'Bánh mì kẹp thịt nguội, rau củ tươi ngon', 'gia': [35000, 45000]},
    {'ten': 'Sandwich', 'mo_ta': 'Sandwich đầy đủ nhân, thơm ngon', 'gia': [40000, 50000]},
    
    # Kẹo
    {'ten': 'Kẹo mút', 'mo_ta': 'Kẹo mút nhiều vị, ngọt ngào', 'gia': [10000, 15000]},
    {'ten': 'Kẹo dẻo', 'mo_ta': 'Kẹo dẻo mềm mại, nhiều vị', 'gia': [15000, 20000]},
    {'ten': 'Socola', 'mo_ta': 'Socola đắng ngọt, thơm béo', 'gia': [25000, 35000]},
    
    # Đồ uống nóng
    {'ten': 'Cà phê đen', 'mo_ta': 'Cà phê đen đậm đà, thơm nồng', 'gia': [25000, 30000]},
    {'ten': 'Cà phê sữa', 'mo_ta': 'Cà phê sữa ngọt ngào, béo ngậy', 'gia': [30000, 35000]},
    {'ten': 'Trà đá', 'mo_ta': 'Trà đá mát lạnh, giải nhiệt', 'gia': [15000, 20000]},
    {'ten': 'Trà sữa', 'mo_ta': 'Trà sữa thơm ngon, ngọt ngào', 'gia': [35000, 45000]},
]


def get_rapphim_ids(cursor):
    """Lấy danh sách ID rạp phim"""
    cursor.execute("SELECT id, ten FROM rapphim WHERE trang_thai = 1")
    results = cursor.fetchall()
    if USE_MYSQL_CONNECTOR:
        return [(row[0], row[1]) for row in results] if results else []
    else:
        return [(row[0], row[1]) for row in results] if results else []


def get_danhmuc_ids(cursor):
    """Lấy danh sách ID danh mục"""
    cursor.execute("SELECT id FROM danhmuc")
    results = cursor.fetchall()
    if results:
        if USE_MYSQL_CONNECTOR:
            return [row[0] for row in results]
        else:
            return [row[0] for row in results]
    return []


def get_san_pham_da_ton_tai(cursor, id_rapphim):
    """Lấy danh sách tên sản phẩm đã tồn tại trong rạp"""
    cursor.execute("SELECT ten FROM san_pham WHERE id_rapphim = %s", (id_rapphim,))
    results = cursor.fetchall()
    if results:
        if USE_MYSQL_CONNECTOR:
            return {row[0] for row in results}
        else:
            return {row[0] for row in results}
    return set()


def create_san_pham_cho_rap(cursor, connection, id_rapphim, ten_rap, so_luong, danhmuc_ids):
    """Tạo sản phẩm cho một rạp phim cụ thể"""
    success_count = 0
    error_count = 0
    
    # Lấy danh sách sản phẩm đã tồn tại trong rạp để tránh trùng
    san_pham_da_ton_tai = get_san_pham_da_ton_tai(cursor, id_rapphim)
    
    # Trộn danh sách template để đa dạng
    templates = SAN_PHAM_TEMPLATES.copy()
    random.shuffle(templates)
    
    # Set để theo dõi sản phẩm đã tạo trong lần chạy này
    san_pham_da_tao = set()
    
    attempt = 0
    max_attempts = so_luong * 3  # Tối đa thử 3 lần số lượng để tránh vòng lặp vô hạn
    
    while success_count < so_luong and attempt < max_attempts:
        attempt += 1
        try:
            # Chọn template sản phẩm ngẫu nhiên
            template = random.choice(templates)
            
            # Tạo tên sản phẩm
            ten = template['ten']
            
            # Kiểm tra xem tên đã tồn tại chưa (cả trong DB và trong lần chạy này)
            if ten in san_pham_da_ton_tai or ten in san_pham_da_tao:
                # Nếu trùng, thử thêm size để phân biệt
                sizes = ['Nhỏ', 'Vừa', 'Lớn']
                ten_with_size = f"{ten} - {random.choice(sizes)}"
                
                # Kiểm tra lại với tên có size
                if ten_with_size in san_pham_da_ton_tai or ten_with_size in san_pham_da_tao:
                    # Vẫn trùng, bỏ qua và thử template khác
                    continue
                ten = ten_with_size
            
            # Đánh dấu tên đã sử dụng
            san_pham_da_tao.add(ten)
            
            mo_ta = template['mo_ta']
            gia = random.choice(template['gia'])
            
            # Chọn danh mục ngẫu nhiên
            danh_muc_id = random.choice(danhmuc_ids) if danhmuc_ids else None
            
            # Trạng thái: 80% đang bán, 20% ngừng bán
            trang_thai = random.choice([1, 1, 1, 1, 0])  # 80% = 1, 20% = 0
            
            # Hình ảnh (có thể để null hoặc tạo path giả)
            hinh_anh = None  # Có thể để null hoặc tạo path giả
            
            # Tạo sản phẩm
            cursor.execute("""
                INSERT INTO san_pham 
                (ten, mo_ta, gia, hinh_anh, id_rapphim, danh_muc_id, trang_thai, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
            """, (ten, mo_ta, gia, hinh_anh, id_rapphim, danh_muc_id, trang_thai))
            
            connection.commit()
            success_count += 1
            trang_thai_text = "Đang bán" if trang_thai == 1 else "Ngừng bán"
            print(f"  [{success_count}/{so_luong}] ✓ {ten} - {gia:,}đ - {trang_thai_text}")
            
        except Exception as e:
            connection.rollback()
            error_count += 1
            # Kiểm tra loại lỗi
            error_type = type(e).__name__
            error_msg = str(e)
            if 'IntegrityError' in error_type or 'Duplicate' in error_msg or '1062' in error_msg:
                # Nếu trùng, thêm vào set để tránh thử lại
                if 'ten' in locals():
                    san_pham_da_ton_tai.add(ten)
                print(f"  [{success_count + 1}/{so_luong}] ✗ Lỗi trùng dữ liệu: {e}")
            else:
                print(f"  [{success_count + 1}/{so_luong}] ✗ Lỗi: {e}")
            continue
    
    if success_count < so_luong:
        print(f"  ⚠ Cảnh báo: Chỉ tạo được {success_count}/{so_luong} sản phẩm (có thể do trùng hoặc hết template)")
    
    return success_count, error_count


def create_san_pham(cursor, connection, so_luong_moi_rap=5):
    """Tạo sản phẩm giả cho từng rạp phim"""
    # Lấy danh sách rạp phim và danh mục
    rapphim_list = get_rapphim_ids(cursor)
    danhmuc_ids = get_danhmuc_ids(cursor)
    
    if not rapphim_list:
        print("Không tìm thấy rạp phim nào trong database!")
        return
    
    if not danhmuc_ids:
        print("Cảnh báo: Không tìm thấy danh mục nào. Sản phẩm sẽ không có danh mục.")
    
    print(f"Tìm thấy {len(rapphim_list)} rạp phim")
    print(f"Tìm thấy {len(danhmuc_ids)} danh mục")
    print(f"Bắt đầu tạo {so_luong_moi_rap} sản phẩm cho mỗi rạp...\n")
    
    total_success = 0
    total_error = 0
    
    for id_rap, ten_rap in rapphim_list:
        print(f"Rạp: {ten_rap} (ID: {id_rap})")
        success, error = create_san_pham_cho_rap(cursor, connection, id_rap, ten_rap, so_luong_moi_rap, danhmuc_ids)
        total_success += success
        total_error += error
        print()  # Dòng trống giữa các rạp
    
    print(f"{'='*60}")
    print(f"Hoàn thành!")
    print(f"Tổng số rạp: {len(rapphim_list)}")
    print(f"Tổng sản phẩm thành công: {total_success}")
    print(f"Tổng sản phẩm lỗi: {total_error}")
    print(f"{'='*60}")


def main():
    """Hàm chính
    
    Cách sử dụng:
        python fake_sanpham.py [số_lượng_mỗi_rạp]
    
    Ví dụ:
        python fake_sanpham.py 10    # Tạo 10 sản phẩm cho mỗi rạp
        python fake_sanpham.py       # Tạo 5 sản phẩm cho mỗi rạp (mặc định)
    """
    if len(sys.argv) > 1:
        try:
            so_luong = int(sys.argv[1])
            if so_luong <= 0:
                print("Số lượng phải lớn hơn 0. Sử dụng mặc định: 5")
                so_luong = 5
        except ValueError:
            print("Số lượng không hợp lệ. Sử dụng mặc định: 5")
            so_luong = 5
    else:
        so_luong = 5
    
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
        
        # Tạo sản phẩm cho từng rạp
        create_san_pham(cursor, connection, so_luong)
        
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


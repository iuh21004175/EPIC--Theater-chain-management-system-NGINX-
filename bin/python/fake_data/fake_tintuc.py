#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script sinh dữ liệu tin tức thật từ web search cho hệ thống Epic Cinema

Yêu cầu:
    - Python 3.6+
    - mysql-connector-python: pip install mysql-connector-python (khuyến nghị, hỗ trợ UTF-8 tốt)
    - hoặc pymysql: pip install pymysql

Cách sử dụng:
    1. Cài đặt dependencies (khuyến nghị):
       pip install mysql-connector-python
    
    2. Chạy script:
       python fake_tintuc.py [số_lượng_mỗi_rạp]
    
    3. Ví dụ:
       python fake_tintuc.py 10    # Tạo 10 tin tức cho mỗi rạp
       python fake_tintuc.py       # Tạo 10 tin tức cho mỗi rạp (mặc định)

Lưu ý:
    - Script sẽ tạo tin tức cho TỪNG rạp phim
    - Mỗi rạp sẽ có số lượng tin tức riêng
    - Script sẽ tìm tin tức thật từ web về phim, diễn viên, đạo diễn
    - Mỗi tin tức sẽ được gán cho một tác giả từ rạp đó
    - Trạng thái tin tức: 2 (Đã duyệt) để hiển thị ngay
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
import re
from datetime import datetime
from html import unescape

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


def get_phim_info(cursor):
    """Lấy danh sách phim với thông tin diễn viên và đạo diễn"""
    cursor.execute("""
        SELECT id, ten_phim, dao_dien, dien_vien 
        FROM phim 
        WHERE trang_thai IN (0, 1)
        ORDER BY RAND()
        LIMIT 50
    """)
    results = cursor.fetchall()
    if USE_MYSQL_CONNECTOR:
        return [
            {
                'id': row[0],
                'ten_phim': row[1] or '',
                'dao_dien': row[2] or '',
                'dien_vien': row[3] or ''
            }
            for row in results
        ]
    else:
        return [
            {
                'id': row[0],
                'ten_phim': row[1] or '',
                'dao_dien': row[2] or '',
                'dien_vien': row[3] or ''
            }
            for row in results
        ]


def get_rapphim_ids(cursor):
    """Lấy danh sách ID rạp phim"""
    cursor.execute("SELECT id, ten FROM rapphim WHERE trang_thai = 1")
    results = cursor.fetchall()
    if USE_MYSQL_CONNECTOR:
        return [(row[0], row[1]) for row in results] if results else []
    else:
        return [(row[0], row[1]) for row in results] if results else []


def get_tac_gia_by_rap(cursor, id_rapphim):
    """Lấy danh sách tác giả (nhân viên) theo rạp phim"""
    cursor.execute("""
        SELECT id, ten 
        FROM nguoidung_noibo 
        WHERE id_rapphim = %s AND trang_thai = 1
        LIMIT 10
    """, (id_rapphim,))
    results = cursor.fetchall()
    if USE_MYSQL_CONNECTOR:
        return [(row[0], row[1]) for row in results] if results else []
    else:
        return [(row[0], row[1]) for row in results] if results else []


def get_tac_gia_ids(cursor):
    """Lấy danh sách ID tác giả (nhân viên) - tất cả rạp"""
    cursor.execute("""
        SELECT id, ten 
        FROM nguoidung_noibo 
        WHERE trang_thai = 1
        LIMIT 20
    """)
    results = cursor.fetchall()
    if USE_MYSQL_CONNECTOR:
        return [(row[0], row[1]) for row in results] if results else []
    else:
        return [(row[0], row[1]) for row in results] if results else []


def clean_html(text):
    """Làm sạch HTML và format text"""
    if not text:
        return ''
    # Loại bỏ HTML tags
    text = re.sub(r'<[^>]+>', '', text)
    # Decode HTML entities
    text = unescape(text)
    # Loại bỏ khoảng trắng thừa
    text = re.sub(r'\s+', ' ', text).strip()
    return text


def extract_content_from_search_results(search_results):
    """Trích xuất nội dung từ kết quả tìm kiếm"""
    if not search_results:
        return None, None
    
    # Lấy snippet đầu tiên làm nội dung
    snippets = []
    for result in search_results[:3]:  # Lấy 3 kết quả đầu
        snippet = result.get('snippet', '') or result.get('description', '')
        if snippet:
            snippets.append(clean_html(snippet))
    
    # Kết hợp các snippet
    content = ' '.join(snippets)
    
    # Lấy title từ kết quả đầu tiên
    title = None
    if search_results:
        title = search_results[0].get('title', '') or search_results[0].get('name', '')
        title = clean_html(title)
    
    return title, content


def search_news_about_topic(search_term, web_search_func=None):
    """Tìm kiếm tin tức về một chủ đề (sử dụng web_search tool)
    
    Args:
        search_term: Từ khóa tìm kiếm
        web_search_func: Hàm web_search (sẽ được truyền từ bên ngoài)
    
    Returns:
        tuple: (title, content) hoặc (None, None) nếu không tìm thấy
    """
    try:
        # Tạo query tìm kiếm bằng tiếng Việt
        query = f"tin tức {search_term} phim điện ảnh Việt Nam"
        
        if web_search_func:
            # Gọi web_search tool
            search_results = web_search_func(query)
            
            if search_results and len(search_results) > 0:
                # Trích xuất title và content từ kết quả
                title, content = extract_content_from_search_results(search_results)
                return title, content
        
        return None, None
    except Exception as e:
        print(f"  ⚠ Lỗi khi tìm kiếm: {e}")
        return None, None


def create_tin_tuc_cho_rap(cursor, connection, id_rapphim, ten_rap, so_luong, phim_list, web_search_func=None):
    """Tạo tin tức cho một rạp phim cụ thể"""
    # Lấy danh sách tác giả của rạp
    tac_gia_list = get_tac_gia_by_rap(cursor, id_rapphim)
    
    if not tac_gia_list:
        # Nếu không có tác giả trong rạp, lấy từ tất cả rạp
        tac_gia_list = get_tac_gia_ids(cursor)
        if not tac_gia_list:
            tac_gia_list = [(None, 'Hệ thống')]
    
    success_count = 0
    error_count = 0
    
    # Tạo danh sách chủ đề để tìm kiếm
    search_topics = []
    for phim in phim_list:
        # Thêm chủ đề về phim
        if phim['ten_phim']:
            search_topics.append(('phim', phim['ten_phim']))
        
        # Thêm chủ đề về đạo diễn
        if phim['dao_dien']:
            dao_dien_list = [d.strip() for d in phim['dao_dien'].split(',') if d.strip()]
            for dd in dao_dien_list[:2]:
                search_topics.append(('đạo diễn', dd))
        
        # Thêm chủ đề về diễn viên
        if phim['dien_vien']:
            dien_vien_list = [d.strip() for d in phim['dien_vien'].split(',') if d.strip()]
            for dv in dien_vien_list[:3]:
                search_topics.append(('diễn viên', dv))
    
    if not search_topics:
        print(f"  ⚠ Không có chủ đề nào để tìm kiếm cho rạp {ten_rap}")
        return success_count, error_count
    
    # Trộn ngẫu nhiên danh sách chủ đề
    random.shuffle(search_topics)
    search_topics = search_topics[:so_luong * 2]  # Lấy nhiều hơn để có dự phòng
    
    for i in range(so_luong):
        if i >= len(search_topics):
            break
        
        try:
            loai_chu_de, ten_chu_de = search_topics[i]
            
            # Tìm kiếm tin tức từ web
            search_query = f"{loai_chu_de} {ten_chu_de}"
            title, content = search_news_about_topic(search_query, web_search_func)
            
            # Nếu không tìm thấy từ web, tạo dữ liệu mẫu
            if not title or not content or len(content.strip()) < 50:
                title = f"Tin tức mới về {loai_chu_de} {ten_chu_de}"
                content = f"""
                <p>Đây là tin tức về {loai_chu_de} <strong>{ten_chu_de}</strong>.</p>
                <p>Nội dung tin tức sẽ được cập nhật từ các nguồn tin tức điện ảnh uy tín.</p>
                <p>Hãy theo dõi để cập nhật những thông tin mới nhất về {ten_chu_de}.</p>
                """
            else:
                # Format lại content thành HTML nếu chưa có
                if not content.startswith('<'):
                    sentences = content.split('. ')
                    paragraphs = []
                    current_para = []
                    for sentence in sentences:
                        if sentence.strip():
                            current_para.append(sentence.strip())
                            if len(current_para) >= 2:
                                paragraphs.append(' '.join(current_para) + '.')
                                current_para = []
                    if current_para:
                        paragraphs.append(' '.join(current_para) + '.')
                    content = '\n'.join([f'<p>{p}</p>' for p in paragraphs if p.strip()])
            
            if not title or not content:
                error_count += 1
                continue
            
            # Chọn tác giả ngẫu nhiên từ rạp
            id_tac_gia, ten_tac_gia = random.choice(tac_gia_list)
            
            # Tạo tin tức
            ngay_tao = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            
            cursor.execute("""
                INSERT INTO tintuc 
                (id_tac_gia, tieu_de, noi_dung, anh_tin_tuc, tac_gia, trang_thai, created_at, ngay_tao, ngay_cap_nhat)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
            """, (
                id_tac_gia,
                title,
                content,
                None,
                ten_tac_gia,
                2,  # trang_thai: 2 = Đã duyệt
                ngay_tao,
                ngay_tao,
                ngay_tao
            ))
            
            connection.commit()
            success_count += 1
            print(f"  [{i+1}/{so_luong}] ✓ {title[:50]}...")
            
        except Exception as e:
            connection.rollback()
            error_count += 1
            error_type = type(e).__name__
            error_msg = str(e)
            if 'IntegrityError' in error_type or 'Duplicate' in error_msg or '1062' in error_msg:
                print(f"  [{i+1}/{so_luong}] ✗ Lỗi trùng dữ liệu: {e}")
            else:
                print(f"  [{i+1}/{so_luong}] ✗ Lỗi: {e}")
            continue
    
    return success_count, error_count


def create_tin_tuc_from_web(cursor, connection, so_luong_moi_rap=10, web_search_func=None):
    """Tạo tin tức từ web search cho từng rạp phim
    
    Args:
        cursor: Database cursor
        connection: Database connection
        so_luong_moi_rap: Số lượng tin tức cần tạo cho mỗi rạp (mặc định: 10)
        web_search_func: Hàm web_search (có thể là None nếu không có)
    """
    # Lấy danh sách rạp phim và phim
    rapphim_list = get_rapphim_ids(cursor)
    phim_list = get_phim_info(cursor)
    
    if not rapphim_list:
        print("Không tìm thấy rạp phim nào trong database!")
        return
    
    if not phim_list:
        print("Không tìm thấy phim nào trong database!")
        return
    
    print(f"Tìm thấy {len(rapphim_list)} rạp phim")
    print(f"Tìm thấy {len(phim_list)} phim")
    print(f"Bắt đầu tạo {so_luong_moi_rap} tin tức cho mỗi rạp từ web search...\n")
    
    total_success = 0
    total_error = 0
    
    for id_rap, ten_rap in rapphim_list:
        print(f"Rạp: {ten_rap} (ID: {id_rap})")
        success, error = create_tin_tuc_cho_rap(cursor, connection, id_rap, ten_rap, so_luong_moi_rap, phim_list, web_search_func)
        total_success += success
        total_error += error
        print()  # Dòng trống giữa các rạp
    
    print(f"{'='*60}")
    print(f"Hoàn thành!")
    print(f"Tổng số rạp: {len(rapphim_list)}")
    print(f"Tổng tin tức thành công: {total_success}")
    print(f"Tổng tin tức lỗi: {total_error}")
    print(f"{'='*60}")
    
    if web_search_func:
        print("\n✓ Đã sử dụng web_search để tìm tin tức thật từ web.")
    else:
        print("\n⚠ Lưu ý: Script đang sử dụng dữ liệu mẫu.")
        print("Để sử dụng tin tức thật từ web, cần truyền web_search_func vào hàm.")


def main():
    """Hàm chính
    
    Cách sử dụng:
        python fake_tintuc.py [số_lượng_mỗi_rạp]
    
    Ví dụ:
        python fake_tintuc.py 10    # Tạo 10 tin tức cho mỗi rạp
        python fake_tintuc.py       # Tạo 10 tin tức cho mỗi rạp (mặc định)
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
        
        # Tạo tin tức
        # Note: Trong môi trường Cursor, có thể sử dụng web_search tool
        # Tuy nhiên, vì đây là script Python độc lập, cần tích hợp web_search
        # thông qua một wrapper function hoặc API tìm kiếm khác
        
        # Tạm thời sử dụng None, sẽ được cập nhật khi có web_search
        web_search_func = None
        
        # Nếu muốn sử dụng web_search, có thể tạo wrapper như sau:
        # def web_search_wrapper(query):
        #     # Gọi web_search tool và trả về kết quả
        #     # Cần implement dựa trên API tìm kiếm thực tế
        #     pass
        # web_search_func = web_search_wrapper
        
        create_tin_tuc_from_web(cursor, connection, so_luong, web_search_func=web_search_func)
        
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


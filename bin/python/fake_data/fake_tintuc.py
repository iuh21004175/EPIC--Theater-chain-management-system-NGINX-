#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script sinh dữ liệu tin tức thật từ web search cho hệ thống Epic Cinema

Yêu cầu:
    - Python 3.6+
    - mysql-connector-python: pip install mysql-connector-python (khuyến nghị, hỗ trợ UTF-8 tốt)
    - hoặc pymysql: pip install pymysql
    - googlesearch-python: pip install googlesearch-python (để tìm kiếm Google)

Cách sử dụng:
    1. Cài đặt dependencies (khuyến nghị):
       pip install mysql-connector-python
       
       # Chọn 1 trong 2 cách sau:
       # Cách 1: Sử dụng googlesearch-python (không cần API key)
       pip install googlesearch-python
       
       # Cách 2: Sử dụng Google Custom Search API (cần API key)
       pip install google-api-python-client
       # Thêm vào file .env:
       # GOOGLE_API_KEY=your_api_key_here
       # GOOGLE_SEARCH_ENGINE_ID=your_search_engine_id_here
    
    2. Chạy script:
       python fake_tintuc.py [số_lượng_mỗi_rạp]
    
    3. Ví dụ:
       python fake_tintuc.py 10    # Tạo 10 tin tức cho mỗi rạp
       python fake_tintuc.py       # Tạo 10 tin tức cho mỗi rạp (mặc định)

Lưu ý:
    - Script sẽ tạo tin tức cho TỪNG rạp phim
    - Mỗi rạp sẽ có số lượng tin tức riêng
    - Script sử dụng Google Search để tìm tin tức thật về phim, diễn viên, đạo diễn
    - Mỗi tin tức sẽ được gán cho một tác giả từ rạp đó
    - Trạng thái tin tức: 2 (Đã duyệt) để hiển thị ngay
    
    Cấu hình Google Search (chọn 1 trong 2 cách):
    1. Sử dụng googlesearch-python (không cần API key):
       pip install googlesearch-python
    
    2. Sử dụng Google Custom Search API (cần API key, chính xác hơn):
       - Thêm vào file .env:
         GOOGLE_API_KEY=your_api_key_here
         GOOGLE_SEARCH_ENGINE_ID=your_search_engine_id_here
       - Cài đặt: pip install google-api-python-client
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
import time

# Import Google Search
HAS_GOOGLE_SEARCH = False
HAS_GOOGLE_API = False
google_search = None
google_api_service = None

# Thử import googlesearch-python (không cần API key)
try:
    from googlesearch import search as google_search
    HAS_GOOGLE_SEARCH = True
except ImportError:
    try:
        from googlesearch_python import search as google_search
        HAS_GOOGLE_SEARCH = True
    except ImportError:
        pass

# Thử import Google Custom Search API (cần API key)
try:
    from googleapiclient.discovery import build
    HAS_GOOGLE_API = True
except ImportError:
    pass

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

# Cấu hình Google Search API (nếu sử dụng Custom Search API)
GOOGLE_API_KEY = os.getenv('GOOGLE_API_KEY', '')
GOOGLE_SEARCH_ENGINE_ID = os.getenv('GOOGLE_SEARCH_ENGINE_ID', '')

# Khởi tạo Google Custom Search API service (nếu có API key)
if HAS_GOOGLE_API and GOOGLE_API_KEY and GOOGLE_SEARCH_ENGINE_ID:
    try:
        google_api_service = build("customsearch", "v1", developerKey=GOOGLE_API_KEY)
        print(f"✓ Đã khởi tạo Google Custom Search API (sử dụng API key)")
    except Exception as e:
        print(f"⚠ Không thể khởi tạo Google Custom Search API: {e}")
        google_api_service = None
elif GOOGLE_API_KEY or GOOGLE_SEARCH_ENGINE_ID:
    print("⚠ Cảnh báo: Thiếu GOOGLE_API_KEY hoặc GOOGLE_SEARCH_ENGINE_ID trong .env")
    print("   Sẽ sử dụng googlesearch-python (không cần API key) nếu đã cài đặt")


def get_phim_info(cursor):
    """Lấy danh sách phim với thông tin diễn viên, đạo diễn và poster_url"""
    cursor.execute("""
        SELECT id, ten_phim, dao_dien, dien_vien, poster_url 
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
                'dien_vien': row[3] or '',
                'poster_url': row[4] if len(row) > 4 else None
            }
            for row in results
        ]
    else:
        return [
            {
                'id': row[0],
                'ten_phim': row[1] or '',
                'dao_dien': row[2] or '',
                'dien_vien': row[3] or '',
                'poster_url': row[4] if len(row) > 4 else None
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


def extract_content_from_google_api_results(items):
    """Trích xuất nội dung từ kết quả Google Custom Search API
    
    Args:
        items: Danh sách items từ Google Custom Search API
    
    Returns:
        tuple: (title, content, image_url) hoặc (None, None, None) nếu không có
    """
    if not items or len(items) == 0:
        return None, None, None
    
    # Lấy item đầu tiên
    first_item = items[0]
    title = first_item.get('title', 'Tin tức mới')
    snippet = first_item.get('snippet', '')
    link = first_item.get('link', '')
    
    # Lấy ảnh từ pagemap hoặc metatags
    image_url = None
    pagemap = first_item.get('pagemap', {})
    if pagemap:
        # Thử lấy từ cse_image
        cse_images = pagemap.get('cse_image', [])
        if cse_images and len(cse_images) > 0:
            image_url = cse_images[0].get('src', None)
        # Nếu không có, thử lấy từ metatags
        if not image_url:
            metatags = pagemap.get('metatags', [])
            if metatags and len(metatags) > 0:
                image_url = metatags[0].get('og:image') or metatags[0].get('twitter:image')
    
    # Tạo content từ các items - kết hợp nhiều snippet để nội dung phong phú hơn
    content_parts = []
    
    # Thêm snippet từ item đầu tiên (nội dung chính)
    if snippet:
        snippet_cleaned = clean_html(snippet)
        # Chia snippet thành các đoạn nếu dài
        if len(snippet_cleaned) > 200:
            sentences = snippet_cleaned.split('. ')
            paragraphs = []
            current_para = []
            for sentence in sentences:
                if sentence.strip():
                    current_para.append(sentence.strip())
                    if len(' '.join(current_para)) > 200:
                        paragraphs.append(' '.join(current_para) + '.')
                        current_para = []
            if current_para:
                paragraphs.append(' '.join(current_para) + '.')
            for para in paragraphs:
                if para.strip():
                    content_parts.append(f"<p>{para}</p>")
        else:
            content_parts.append(f"<p>{snippet_cleaned}</p>")
    
    # Thêm các snippet từ các items khác để làm phong phú nội dung
    for i, item in enumerate(items[1:4], 2):  # Lấy 3 items tiếp theo
        item_snippet = item.get('snippet', '')
        if item_snippet:
            snippet_cleaned = clean_html(item_snippet)
            if snippet_cleaned and len(snippet_cleaned) > 30:  # Chỉ lấy snippet có nội dung
                content_parts.append(f"<p>{snippet_cleaned}</p>")
    
    content = '\n'.join(content_parts) if content_parts else None
    
    return clean_html(title), content, image_url


def extract_content_from_google_results(urls, search_term=""):
    """Trích xuất nội dung từ kết quả Google Search (googlesearch-python)
    
    Args:
        urls: Danh sách URLs từ Google Search
        search_term: Từ khóa tìm kiếm để tạo nội dung phong phú hơn
    
    Returns:
        tuple: (title, content, image_url) hoặc (None, None, None) nếu không có
    """
    if not urls:
        return None, None, None
    
    # Lấy URL đầu tiên để tạo title
    first_url = urls[0] if urls else None
    if not first_url:
        return None, None, None
    
    # Tạo title từ URL và search_term
    try:
        from urllib.parse import urlparse
        parsed = urlparse(first_url)
        domain = parsed.netloc.replace('www.', '')
        # Tạo title từ search_term nếu có
        if search_term:
            # Tách loại và tên từ search_term (ví dụ: "diễn viên Phiravich")
            parts = search_term.split(' ', 1)
            if len(parts) == 2:
                loai, ten = parts
                title = f"Tin tức mới về {loai} {ten}"
            else:
                title = f"Tin tức về {search_term}"
        else:
            title = f"Tin tức từ {domain}"
    except:
        title = "Tin tức mới"
    
    # Không có ảnh từ googlesearch-python (chỉ có URLs)
    image_url = None
    
    # Tạo content phong phú hơn từ URLs và search_term
    content_parts = []
    
    # Thêm đoạn mở đầu dựa trên search_term
    if search_term:
        parts = search_term.split(' ', 1)
        if len(parts) == 2:
            loai, ten = parts
            content_parts.append(f"<p>Trong thời gian gần đây, {loai} <strong>{ten}</strong> đã thu hút sự chú ý của công chúng và giới truyền thông với những dự án điện ảnh mới.</p>")
            content_parts.append(f"<p>Các nguồn tin tức uy tín đã đưa tin về những hoạt động và thành tựu của {ten} trong ngành công nghiệp điện ảnh.</p>")
    
    # Thêm thông tin về các nguồn
    content_parts.append(f"<p><strong>Các nguồn tin tức liên quan:</strong></p>")
    for i, url in enumerate(urls[:3], 1):
        try:
            from urllib.parse import urlparse
            parsed = urlparse(url)
            domain = parsed.netloc.replace('www.', '')
            path = parsed.path.strip('/').replace('/', ' - ')[:80] if parsed.path else ''
            
            if path:
                content_parts.append(f"<p>- <strong>Nguồn {i}:</strong> {domain} - {path}</p>")
            else:
                content_parts.append(f"<p>- <strong>Nguồn {i}:</strong> {domain}</p>")
        except:
            content_parts.append(f"<p>- <strong>Nguồn {i}:</strong> {url[:50]}</p>")
    
    # Thêm đoạn kết
    if search_term:
        parts = search_term.split(' ', 1)
        if len(parts) == 2:
            loai, ten = parts
            content_parts.append(f"<p>Hãy theo dõi các kênh thông tin chính thức để cập nhật những tin tức mới nhất về {loai} {ten} và các dự án sắp tới.</p>")
    
    content = '\n'.join(content_parts) if content_parts else None
    
    return title, content, image_url


def get_image_url_for_topic(loai_chu_de, ten_chu_de, phim_list=None):
    """Lấy URL ảnh cho chủ đề tin tức
    
    Args:
        loai_chu_de: Loại chủ đề (phim, đạo diễn, diễn viên)
        ten_chu_de: Tên chủ đề
        phim_list: Danh sách phim (để lấy poster_url nếu là phim)
    
    Returns:
        str: URL ảnh hoặc None
    """
    # Nếu là phim, thử lấy poster_url từ phim_list
    if loai_chu_de == 'phim' and phim_list:
        for phim in phim_list:
            if phim.get('ten_phim') == ten_chu_de and phim.get('poster_url'):
                # Trả về poster_url nếu có (đã là đường dẫn bucket/key)
                return phim.get('poster_url')
    
    # Fallback: Sử dụng placeholder hoặc ảnh mẫu
    # Có thể sử dụng Unsplash hoặc Picsum
    return None


def search_news_about_topic(search_term, use_google=True):
    """Tìm kiếm tin tức về một chủ đề sử dụng Google Search
    
    Args:
        search_term: Từ khóa tìm kiếm
        use_google: Có sử dụng Google Search không (mặc định: True)
    
    Returns:
        tuple: (title, content, image_url) hoặc (None, None, None) nếu không tìm thấy
    """
    try:
        # Tạo query tìm kiếm bằng tiếng Việt
        query = f"tin tức {search_term} phim điện ảnh Việt Nam"
        
        if use_google:
            # Ưu tiên sử dụng Google Custom Search API (nếu có API key)
            if google_api_service and GOOGLE_API_KEY and GOOGLE_SEARCH_ENGINE_ID:
                try:
                    print(f"    🔍 Đang tìm kiếm Google API: {query[:60]}...")
                    
                    # Gọi Google Custom Search API
                    res = google_api_service.cse().list(
                        q=query,
                        cx=GOOGLE_SEARCH_ENGINE_ID,
                        num=5,  # Lấy 5 kết quả
                        lr='lang_vi'  # Tiếng Việt
                    ).execute()
                    
                    items = res.get('items', [])
                    if items:
                        # Trích xuất title, content và image_url từ API results
                        title, content, image_url = extract_content_from_google_api_results(items)
                        if title and content:
                            return title, content, image_url
                
                except Exception as e:
                    print(f"    ⚠ Lỗi Google Custom Search API: {e}")
                    # Fallback về googlesearch-python
                    pass
            
            # Sử dụng googlesearch-python (không cần API key)
            if HAS_GOOGLE_SEARCH and google_search:
                try:
                    print(f"    🔍 Đang tìm kiếm Google (googlesearch-python): {query[:60]}...")
                    
                    # Google search trả về URLs
                    # Kiểm tra xem thư viện nào đang được sử dụng để gọi đúng tham số
                    search_results = None
                    
                    # Thử các cách gọi khác nhau tùy theo thư viện
                    try:
                        # Cách 1: Thử với tham số đầy đủ (googlesearch)
                        search_results = list(google_search(
                            query, 
                            num_results=5,
                            lang='vi',
                            pause=1.0
                        ))
                    except TypeError as e1:
                        try:
                            # Cách 2: Không có pause (googlesearch-python)
                            search_results = list(google_search(
                                query, 
                                num_results=5,
                                lang='vi'
                            ))
                        except TypeError as e2:
                            try:
                                # Cách 3: Chỉ có num_results
                                search_results = list(google_search(
                                    query, 
                                    num_results=5
                                ))
                            except TypeError as e3:
                                try:
                                    # Cách 4: Chỉ có num (tên tham số khác)
                                    search_results = list(google_search(query, num=5))
                                except TypeError as e4:
                                    # Cách 5: Chỉ có query
                                    search_results = list(google_search(query))
                    
                    if search_results:
                        # Lấy URLs từ kết quả Google Search
                        urls = list(search_results)[:5]
                        
                        # Trích xuất title, content và image_url từ URLs với search_term để tạo nội dung phong phú hơn
                        title, content, image_url = extract_content_from_google_results(urls, search_term)
                        
                        if title and content and len(content.strip()) > 100:  # Đảm bảo nội dung đủ dài
                            # Thêm delay để tránh rate limit
                            time.sleep(1.0)
                            return title, content, image_url
                    
                    # Thêm delay để tránh rate limit
                    time.sleep(1.0)
                
                except Exception as e:
                    print(f"    ⚠ Lỗi Google Search (googlesearch-python): {e}")
                    # Fallback về dữ liệu mẫu
                    return None, None, None
        
        return None, None, None
    except Exception as e:
        print(f"  ⚠ Lỗi khi tìm kiếm: {e}")
        return None, None, None


def create_tin_tuc_cho_rap(cursor, connection, id_rapphim, ten_rap, so_luong, phim_list, use_google=True):
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
            
            # Tìm kiếm tin tức từ Google
            search_query = f"{loai_chu_de} {ten_chu_de}"
            title, content, image_url = search_news_about_topic(search_query, use_google=use_google)
            
            # Lấy ảnh cho bài viết
            if not image_url:
                # Thử lấy từ phim nếu là chủ đề về phim
                image_url = get_image_url_for_topic(loai_chu_de, ten_chu_de, phim_list)
            
            # Nếu không tìm thấy từ web, tạo dữ liệu mẫu phong phú hơn
            if not title or not content or len(content.strip()) < 50:
                title = f"Tin tức mới về {loai_chu_de} {ten_chu_de}"
                
                # Lấy ảnh nếu chưa có
                if not image_url:
                    image_url = get_image_url_for_topic(loai_chu_de, ten_chu_de, phim_list)
                
                # Tạo nội dung mẫu phong phú hơn dựa trên loại chủ đề
                if loai_chu_de == 'phim':
                    content = f"""
                    <p>Phim <strong>{ten_chu_de}</strong> đang nhận được sự quan tâm đặc biệt từ khán giả và giới phê bình điện ảnh.</p>
                    <p>Bộ phim mang đến những trải nghiệm mới lạ và hấp dẫn cho người xem với cốt truyện độc đáo và dàn diễn viên tài năng.</p>
                    <p>Các nhà phê bình đánh giá cao về chất lượng nghệ thuật và kỹ thuật của phim, đặc biệt là phần hình ảnh và âm thanh.</p>
                    <p>Khán giả đang háo hức chờ đợi để được thưởng thức tác phẩm điện ảnh đầy ấn tượng này trên màn ảnh rộng.</p>
                    <p>Hãy theo dõi các kênh thông tin chính thức để cập nhật những tin tức mới nhất về phim <strong>{ten_chu_de}</strong>.</p>
                    """
                elif loai_chu_de == 'đạo diễn':
                    content = f"""
                    <p>Đạo diễn <strong>{ten_chu_de}</strong> là một trong những tên tuổi nổi bật trong ngành công nghiệp điện ảnh hiện đại.</p>
                    <p>Với phong cách làm phim độc đáo và tầm nhìn nghệ thuật sâu sắc, {ten_chu_de} đã tạo ra nhiều tác phẩm được đánh giá cao.</p>
                    <p>Các dự án gần đây của đạo diễn {ten_chu_de} đang thu hút sự chú ý của công chúng và giới chuyên môn.</p>
                    <p>Nhiều người hâm mộ đang mong chờ những tác phẩm mới từ đạo diễn tài năng này trong thời gian tới.</p>
                    <p>Hãy theo dõi để cập nhật những thông tin mới nhất về đạo diễn <strong>{ten_chu_de}</strong> và các dự án sắp tới.</p>
                    """
                elif loai_chu_de == 'diễn viên':
                    content = f"""
                    <p>Diễn viên <strong>{ten_chu_de}</strong> đang là cái tên được nhắc đến nhiều trong làng giải trí với những vai diễn ấn tượng.</p>
                    <p>Với tài năng diễn xuất đa dạng và khả năng hóa thân vào nhiều nhân vật khác nhau, {ten_chu_de} đã chinh phục được trái tim của đông đảo khán giả.</p>
                    <p>Các dự án phim gần đây của diễn viên {ten_chu_de} đều nhận được những phản hồi tích cực từ phía người xem và giới phê bình.</p>
                    <p>Nhiều người hâm mộ đang háo hức chờ đợi để được thấy diễn viên tài năng này trong những vai diễn mới đầy thách thức.</p>
                    <p>Hãy theo dõi các kênh thông tin chính thức để cập nhật những tin tức mới nhất về diễn viên <strong>{ten_chu_de}</strong>.</p>
                    """
                else:
                    content = f"""
                    <p>Đây là tin tức về {loai_chu_de} <strong>{ten_chu_de}</strong> trong ngành công nghiệp điện ảnh.</p>
                    <p>Nội dung tin tức sẽ được cập nhật từ các nguồn tin tức điện ảnh uy tín và chính thống.</p>
                    <p>Các thông tin mới nhất về {loai_chu_de} {ten_chu_de} đang được cập nhật liên tục trên các phương tiện truyền thông.</p>
                    <p>Hãy theo dõi để cập nhật những thông tin mới nhất về {ten_chu_de} và các hoạt động liên quan.</p>
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
                image_url,  # URL ảnh bài viết (có thể là None)
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


def create_tin_tuc_from_web(cursor, connection, so_luong_moi_rap=10, use_google=True):
    """Tạo tin tức từ Google Search cho từng rạp phim
    
    Args:
        cursor: Database cursor
        connection: Database connection
        so_luong_moi_rap: Số lượng tin tức cần tạo cho mỗi rạp (mặc định: 10)
        use_google: Có sử dụng Google Search không (mặc định: True)
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
    if use_google:
        if google_api_service and GOOGLE_API_KEY:
            print(f"Bắt đầu tạo {so_luong_moi_rap} tin tức cho mỗi rạp từ Google Custom Search API...\n")
        elif HAS_GOOGLE_SEARCH:
            print(f"Bắt đầu tạo {so_luong_moi_rap} tin tức cho mỗi rạp từ Google Search (googlesearch-python)...\n")
        else:
            print(f"Bắt đầu tạo {so_luong_moi_rap} tin tức cho mỗi rạp (sử dụng dữ liệu mẫu)...\n")
    else:
        print(f"Bắt đầu tạo {so_luong_moi_rap} tin tức cho mỗi rạp (sử dụng dữ liệu mẫu)...\n")
    
    total_success = 0
    total_error = 0
    
    for id_rap, ten_rap in rapphim_list:
        print(f"Rạp: {ten_rap} (ID: {id_rap})")
        success, error = create_tin_tuc_cho_rap(cursor, connection, id_rap, ten_rap, so_luong_moi_rap, phim_list, use_google)
        total_success += success
        total_error += error
        print()  # Dòng trống giữa các rạp
        # Thêm delay nhỏ giữa các rạp để tránh rate limit
        if use_google:
            time.sleep(2)  # Chờ 2 giây giữa các rạp
    
    print(f"{'='*60}")
    print(f"Hoàn thành!")
    print(f"Tổng số rạp: {len(rapphim_list)}")
    print(f"Tổng tin tức thành công: {total_success}")
    print(f"Tổng tin tức lỗi: {total_error}")
    print(f"{'='*60}")
    
    if use_google:
        if google_api_service and GOOGLE_API_KEY:
            print("\n✓ Đã sử dụng Google Custom Search API để tìm tin tức thật từ web.")
        elif HAS_GOOGLE_SEARCH:
            print("\n✓ Đã sử dụng Google Search (googlesearch-python) để tìm tin tức thật từ web.")
        else:
            print("\n⚠ Lưu ý: Script đang sử dụng dữ liệu mẫu.")
            print("Để sử dụng Google Search, có 2 cách:")
            print("  1. Cài đặt: pip install googlesearch-python (không cần API key)")
            print("  2. Hoặc cấu hình GOOGLE_API_KEY và GOOGLE_SEARCH_ENGINE_ID trong .env (cần API key)")
    else:
        print("\n⚠ Lưu ý: Script đang sử dụng dữ liệu mẫu.")


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
        
        # Tạo tin tức sử dụng Google Search
        # Ưu tiên sử dụng Google Custom Search API nếu có API key
        # Nếu không, sử dụng googlesearch-python
        use_google = (google_api_service and GOOGLE_API_KEY) or HAS_GOOGLE_SEARCH
        
        if not use_google:
            print("\n⚠ Cảnh báo: Chưa cấu hình Google Search.")
            print("Script sẽ sử dụng dữ liệu mẫu thay vì tìm kiếm thật từ Google.")
            print("\nĐể sử dụng Google Search, có 2 cách:")
            print("  1. Cài đặt: pip install googlesearch-python (không cần API key)")
            print("  2. Hoặc cấu hình trong file .env:")
            print("     GOOGLE_API_KEY=your_api_key_here")
            print("     GOOGLE_SEARCH_ENGINE_ID=your_search_engine_id_here")
            print("     Và cài đặt: pip install google-api-python-client")
            print()
        elif google_api_service and GOOGLE_API_KEY:
            print("\n✓ Sử dụng Google Custom Search API (với API key)")
        elif HAS_GOOGLE_SEARCH:
            print("\n✓ Sử dụng Google Search (googlesearch-python, không cần API key)")
        
        create_tin_tuc_from_web(cursor, connection, so_luong, use_google=use_google)
        
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


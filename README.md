1. Khi mới push về phải chạy 2 lệnh:
  composer install
  npm install
2. Dự án sử dụng tailwind css. Mỗi khi chat AI render giao diện mới thì nên chạy 1 trong 2 lệnh sau:
  npm run build:css:internal --> Dành cho các giao diện trong nội bộ.
  npm run build:css:customer --> Dành cho các giao diện phục vụ cho khách hàng.
  * Để xem chi tiết lệnh thì mở file package.json
3. Lệnh để kết nối database vps (lưu ý là tắt mysql của xampp)
  ssh -L 3306:127.0.0.1:3306 root@103.130.213.112
4. Lệnh để kết nối với minio
  ssh -L 3306:127.0.0.1:3306 -L 9000:127.0.0.1:9000 -L 9001:127.0.0.1:9001 -L 8000:127.0.0.1:8000 root@103.130.213.112
5. Cấu hình minio trên vps
sudo nano /etc/nginx/sites-available/minio.conf
server {
    listen 80;
    server_name example.com;

    # Public MinIO (không cần xác thực)
    location /minio/public/private/ {
        return 403; # ❌ Chặn truy cập hoàn toàn
    }
    location /minio/public/ {
        proxy_pass http://127.0.0.1:9000/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_buffering off;
        proxy_request_buffering off;
    }
    # Private MinIO (cần xác thực)
    location /minio/private/ {
        auth_request /xac-thuc-xem-phim;

        proxy_pass http://127.0.0.1:9000/private/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_buffering off;
        proxy_request_buffering off;
    }

    # Endpoint xác thực nội bộ
    location = /xac-thuc-xem-phim {
        internal;
        proxy_pass http://127.0.0.1:80/xac-thuc-xem-phim.php;
        proxy_pass_request_body off;
        proxy_set_header Content-Length "";
        proxy_set_header Authorization $http_authorization;
        proxy_set_header X-Original-URI $request_uri;
    }
}

sudo ln -s /etc/nginx/sites-available/minio.conf /etc/nginx/sites-enabled/

sudo nginx -t
sudo systemctl reload nginx
sudo systemctl status nginx

6. Port server chroma: 8000
7. 
root@10313021311214950495070:~# curl -fsSL https://dl.min.io/client/mc/release/linux-amd64/mc -o /usr/local/bin/mc
root@10313021311214950495070:~# chmod +x /usr/local/bin/mc
root@10313021311214950495070:~# mc alias set epicminio http://127.0.0.1:9000 admin_epic epic2025 --api S3v4
mc: Configuration written to `/root/.mc/config.json`. Please update your access credentials.
mc: Successfully created `/root/.mc/share`.
mc: Initialized share uploads `/root/.mc/share/uploads.json` file.
mc: Initialized share downloads `/root/.mc/share/downloads.json` file.
Added `epicminio` successfully.
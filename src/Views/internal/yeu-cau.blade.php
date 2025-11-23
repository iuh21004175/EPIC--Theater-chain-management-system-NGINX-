@extends('internal.layout')

@section('title', 'Quản lý yêu cầu')

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-1 text-gray-500 hover:text-gray-700 text-sm font-medium">Quản lý yêu cầu</span>
    </div>
</li>
@endsection

@section('content')
<div class="px-4 py-6 max-w-5xl mx-auto">
    <h2 class="text-2xl font-bold mb-6 text-center text-blue-600">Quản lý yêu cầu</h2>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-4" aria-label="Tabs">
            <button class="tab-btn py-2 px-4 text-blue-600 border-b-2 border-blue-600 font-medium" data-tab="leave">Gửi nghỉ làm</button>
            <button class="tab-btn py-2 px-4 text-gray-500 hover:text-blue-600 border-b-2 border-transparent font-medium" data-tab="article">Gửi viết bài</button>
        </nav>
    </div>

    <div id="leave" class="tab-content">
        <div class="mb-6 p-4 border rounded shadow bg-white">
            <h3 class="font-semibold mb-4">Thông tin yêu cầu nghỉ làm</h3>
            <div class="mb-4">
                <label class="block font-medium mb-1">Chọn ngày & ca</label>
                <select id="leave-shift" class="border rounded px-3 py-2 w-full">
                    <option value="">-- Chọn ca --</option>
                    <option value="0">Ca sáng</option>
                    <option value="1">Ca chiều</option>
                    <option value="2">Ca tối</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Lý do</label>
                <textarea id="leave-reason" rows="3" class="border rounded px-3 py-2 w-full" placeholder="Nhập lý do xin nghỉ"></textarea>
            </div>

            <div class="text-right">
                <button id="send-request" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Gửi yêu cầu</button>
            </div>
        </div>

        <div class="mb-6 p-4 border rounded shadow bg-gray-50">
            <h3 class="font-semibold mb-4">Danh sách yêu cầu đã gửi</h3>
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 border">Ngày</th>
                        <th class="p-2 border">Ca</th>
                        <th class="p-2 border">Lý do</th>
                        <th class="p-2 border">Trạng thái</th>
                    </tr>
                </thead>
                <tbody id="leave-requests"></tbody>
            </table>
        </div>
    </div>

    <div id="article" class="tab-content hidden">
        <div class="mb-6 p-4 border rounded shadow bg-white">
            <h3 class="font-semibold mb-4">Gửi viết bài chờ duyệt</h3>

            <div class="mb-4">
                <label class="block font-medium mb-1">Tiêu đề bài viết</label>
                <input type="text" id="article-title" class="border rounded px-3 py-2 w-full" placeholder="Nhập tiêu đề">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Nội dung bài viết</label>
                <textarea id="article-content" rows="10" class="border rounded px-3 py-2 w-full"></textarea>
            </div>

            <!-- Upload ảnh thumbnail -->
            <div class="mb-4">
                <label class="block font-medium mb-1">Ảnh tin tức (Logo / Thumbnail)</label>
                <input type="file" id="article-image" accept="image/*" class="border rounded px-3 py-2 w-full bg-white cursor-pointer">
                <p class="text-sm text-gray-500 mt-1">Chọn ảnh đại diện cho bài viết (tùy chọn).</p>
                <img id="preview-image" class="hidden mt-3 max-h-48 rounded border">
            </div>

            <div class="text-right">
                <button id="send-article" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Gửi bài</button>
                <button id="update-article" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 hidden">Cập nhật</button>
            </div>
        </div>

        <div class="mb-6 p-4 border rounded shadow bg-gray-50">
            <h3 class="font-semibold mb-4">Danh sách bài viết đã gửi</h3>
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 border">Tiêu đề</th>
                        <th class="p-2 border">Ngày tạo</th>
                        <th class="p-2 border">Trạng thái</th>
                        <th class="p-2 border">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="article-requests"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/tluao2wh6pnxfechhnbj6wumfwolk3sulz86lkh62iu2mmjm/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}";
    const urlMinio = "{{ $_ENV['MINIO_SERVER_URL'] }}";

    // --- Tab switching ---
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('text-blue-600', 'border-blue-600'));
            tabs.forEach(t => t.classList.add('text-gray-500', 'border-transparent'));
            tab.classList.add('text-blue-600', 'border-blue-600');
            tab.classList.remove('text-gray-500', 'border-transparent');
            contents.forEach(c => c.classList.add('hidden'));
            document.getElementById(tab.dataset.tab).classList.remove('hidden');
        });
    });

    // --- Nghỉ làm ---
    const sendBtn = document.getElementById('send-request');
    const leaveShift = document.getElementById('leave-shift');
    const leaveReason = document.getElementById('leave-reason');
    const leaveRequests = document.getElementById('leave-requests');

    async function fetchYeuCauLich() {
        leaveRequests.innerHTML = `<tr><td colspan="4" class="text-center text-gray-500 p-3">Đang tải dữ liệu...</td></tr>`;
        try {
            const res = await fetch(baseUrl + "/api/yeu-cau-lich");
            const data = await res.json();
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                leaveRequests.innerHTML = data.data.map(r => {
                    let trangThaiText = 'Chờ duyệt';
                    let colorClass = 'bg-yellow-500';
                    if (r.trang_thai == 2) {
                        trangThaiText = 'Đã duyệt';
                        colorClass = 'bg-green-500';
                    } else if (r.trang_thai == 3) {
                        trangThaiText = 'Từ chối';
                        colorClass = 'bg-red-500';
                    }
                    return `
                        <tr>
                            <td class="p-2 border">${new Date(r.ngay).toLocaleDateString('vi-VN')}</td>
                            <td class="p-2 border">${r.ca || '-'}</td>
                            <td class="p-2 border text-gray-700">${r.ly_do || '(Không có lý do)'}</td>
                            <td class="p-2 border">
                                <span class="px-2 py-1 rounded text-white ${colorClass}">${trangThaiText}</span>
                            </td>
                        </tr>`;
                }).join('');
            } else {
                leaveRequests.innerHTML = `<tr><td colspan="4" class="text-center text-gray-500 p-3">Không có yêu cầu nào.</td></tr>`;
            }
        } catch (err) {
            console.error("Lỗi khi tải yêu cầu nghỉ:", err);
            leaveRequests.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 p-3">Lỗi khi tải dữ liệu.</td></tr>`;
        }
    }
    fetchYeuCauLich();

    sendBtn.addEventListener('click', async () => {
        if (!leaveShift.value || !leaveReason.value.trim()) {
            alert('Vui lòng chọn ca và nhập lý do!');
            return;
        }

        const payload = {
            ly_do: leaveReason.value.trim(),
            trang_thai: 1
        };

        try {
            const res = await fetch(`${baseUrl}/api/gui-yeu-cau-nghi/${leaveShift.value}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                alert('Gửi yêu cầu nghỉ làm thành công!');
                leaveShift.value = '';
                leaveReason.value = '';
                fetchYeuCauLich();
            } else {
                alert('Lỗi: ' + (data.message || 'Không gửi được yêu cầu.'));
            }
        } catch (err) {
            console.error(err);
            alert('Không thể kết nối tới máy chủ.');
        }
    });

    // --- Soạn thảo bài viết ---
    tinymce.init({
        selector: '#article-content',
        height: 400,
        menubar: true,
        automatic_uploads: true,
        paste_data_images: true,
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
        toolbar: 'undo redo | formatselect | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | image media link | removeformat | code | fullscreen',


        file_picker_types: 'image',
        file_picker_callback: function (cb, value, meta) {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');

            input.onchange = function () {
                const file = this.files[0];
                const reader = new FileReader();
                reader.onload = function () {
                    const id = 'blobid' + (new Date()).getTime();
                    const blobCache = tinymce.activeEditor.editorUpload.blobCache;
                    const base64 = reader.result.split(',')[1];
                    const blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);

                    cb(blobInfo.blobUri(), { title: file.name });
                };
                reader.readAsDataURL(file);
            };

            input.click();
        },

        images_upload_handler: function (blobInfo) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => {
                    // Trả lại ảnh dạng base64 để hiển thị ngay
                    resolve('data:' + blobInfo.blob().type + ';base64,' + blobInfo.base64());
                };
                reader.onerror = () => reject({ message: 'Không thể đọc file ảnh.' });
                reader.readAsDataURL(blobInfo.blob());
            });
        }
    });

    // --- Preview ảnh thumbnail ---
    const previewImg = document.getElementById('preview-image');
    document.getElementById('article-image').addEventListener('change', e => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = ev => {
                previewImg.src = ev.target.result;
                previewImg.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            previewImg.classList.add('hidden');
        }
    });

    const sendArticleBtn = document.getElementById('send-article');
    const articleTitle = document.getElementById('article-title');
    const articleRequests = document.getElementById('article-requests');
    const updateArticleBtn = document.getElementById('update-article');
    const updateBtn = document.getElementById('update-article');

    // --- Lấy danh sách bài viết đã gửi ---
     async function fetchBaiVietDaGui() {
        articleRequests.innerHTML = `<tr><td colspan="4" class="text-center text-gray-500 p-3">Đang tải dữ liệu...</td></tr>`;
        try {
            const res = await fetch(`${baseUrl}/api/yeu-cau-bai-viet`);
            const data = await res.json();
            if (data.success && data.data.length > 0) {
                articleRequests.innerHTML = data.data.map(bv => {
                let status = 'Chờ duyệt', color = 'bg-yellow-500';
                if (bv.trang_thai == 2) { status = 'Đã duyệt'; color = 'bg-green-500'; }
                else if (bv.trang_thai == 3) { status = 'Từ chối'; color = 'bg-red-500'; }

                // Nếu đã duyệt (trạng_thai = 2) thì không hiển thị nút
                const actionBtn = (bv.trang_thai == 2)
                    ? '<span class="text-gray-400 italic">Đã duyệt</span>'
                    : `<button class="text-blue-600 hover:underline" onclick="xemChiTiet(${bv.id})">Xem / Sửa</button>`;

                return `
                    <tr>
                        <td class="p-2 border">${bv.tieu_de}</td>
                        <td class="p-2 border">${new Date(bv.ngay_tao).toLocaleDateString('vi-VN')}</td>
                        <td class="p-2 border text-center"><span class="px-2 py-1 text-white rounded ${color}">${status}</span></td>
                        <td class="p-2 border text-center">${actionBtn}</td>
                    </tr>`;
            }).join('');
            } else {
                articleRequests.innerHTML = `<tr><td colspan="4" class="text-center text-gray-500 p-3">Chưa có bài viết nào.</td></tr>`;
            }
        } catch (err) {
            console.error(err);
            articleRequests.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 p-3">Lỗi khi tải dữ liệu.</td></tr>`;
        }
    }
    fetchBaiVietDaGui();

    // Gửi bài mới
    sendArticleBtn.addEventListener('click', async () => {
        const title = articleTitle.value.trim();
        const content = tinymce.get('article-content').getContent();
        const image = document.getElementById('article-image').files[0];
        if (!title || !content) return alert('Vui lòng nhập tiêu đề và nội dung.');

        const fd = new FormData();
        fd.append('tieu_de', title);
        fd.append('noi_dung', content);
        if (image) fd.append('anh_tin_tuc', image);

        const res = await fetch(`${baseUrl}/api/them-tin-tuc`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert('Gửi bài thành công!');
            articleTitle.value = '';
            tinymce.get('article-content').setContent('');
            document.getElementById('article-image').value = '';
            previewImg.classList.add('hidden');
            fetchBaiVietDaGui();
        } else alert(' ' + data.message);
    });

    // Xem / Sửa bài viết
    window.xemChiTiet = async (id) => {
        try {
            const res = await fetch(`${baseUrl}/api/chi-tiet-tin-tuc/${id}`);
            const data = await res.json();
            if (data.success) {
                const bv = data.data;
                articleTitle.value = bv.tieu_de;
                tinymce.get('article-content').setContent(bv.noi_dung);
                if (bv.anh_tin_tuc) {
                    previewImg.src = `${urlMinio}/${bv.anh_tin_tuc}`;
                    previewImg.classList.remove('hidden');
                }
                currentEditId = id;
                sendArticleBtn.classList.add('hidden');
                updateArticleBtn.classList.remove('hidden');
                // alert('📝 Đang chỉnh sửa bài: ' + bv.tieu_de);
            } else alert(data.message);
        } catch (err) {
            alert('Không thể tải chi tiết bài viết.');
        }
    };

    // Cập nhật bài viết
    updateArticleBtn.addEventListener('click', async () => {
        const title = articleTitle.value.trim();
        const content = tinymce.get('article-content').getContent();
        const image = document.getElementById('article-image').files[0];
        if (!currentEditId) return alert('Không xác định bài để cập nhật.');
        if (!title || !content) return alert('Vui lòng nhập đầy đủ tiêu đề và nội dung.');

        const fd = new FormData();
        fd.append('tieu_de', title);
        fd.append('noi_dung', content);
        if (image) fd.append('anh_tin_tuc', image);
  
        const res = await fetch(`${baseUrl}/api/sua-tin-tuc/${currentEditId}`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert('Cập nhật thành công!');
            sendArticleBtn.classList.remove('hidden');
            updateArticleBtn.classList.add('hidden');
            currentEditId = null;
            articleTitle.value = '';
            tinymce.get('article-content').setContent('');
            previewImg.classList.add('hidden');
            fetchBaiVietDaGui();
        } else alert('' + data.message);
    });
});
</script>
@endsection

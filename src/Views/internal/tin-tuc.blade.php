@extends('internal.layout')

@section('title', 'Quản lý tin tức')

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-1 text-gray-500 hover:text-gray-700 text-sm font-medium">Quản lý tin tức</span>
    </div>
</li>
@endsection

@section('content')
<div class="px-4 py-6 max-w-6xl mx-auto">
    <h2 class="text-2xl font-bold mb-6 text-center text-blue-600">Quản lý tin tức</h2>

    <!-- Form thêm / sửa -->
    <div class="bg-white shadow rounded-lg p-5 mb-8">
        <h3 class="text-lg font-semibold mb-3" id="form-title">Thêm tin tức mới</h3>

        <div class="mb-4">
            <label class="block font-medium mb-1">Tiêu đề</label>
            <input id="title" type="text" class="border rounded px-3 py-2 w-full" placeholder="Nhập tiêu đề bài viết">
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Nội dung</label>
            <textarea id="content" rows="8" class="border rounded px-3 py-2 w-full"></textarea>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Ảnh đại diện</label>
            <input id="image" type="file" accept="image/*" class="border rounded px-3 py-2 w-full bg-white cursor-pointer">
            <img id="preview" class="hidden mt-3 max-h-48 rounded border">
        </div>

        <div class="text-right">
            <button id="saveBtn" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Thêm mới</button>
            <button id="cancelBtn" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500 hidden">Hủy</button>
        </div>
    </div>

    <!-- Danh sách tin tức -->
    <div class="bg-white shadow rounded-lg p-5">
        <h3 class="text-lg font-semibold mb-3">Danh sách tin tức</h3>
        <table class="w-full border-collapse text-left">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-2 border">Tiêu đề</th>
                    <th class="p-2 border">Ngày tạo</th>
                    <th class="p-2 border text-center">Hành động</th>
                </tr>
            </thead>
            <tbody id="news-list">
                <tr><td colspan="3" class="text-center text-gray-500 p-4">Đang tải dữ liệu...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal xem chi tiết -->
<div id="detailModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full p-6 relative">
        <button id="closeModal" class="absolute top-2 right-3 text-gray-500 hover:text-red-600 text-xl">&times;</button>
        <h3 id="modalTitle" class="text-xl font-bold text-blue-600 mb-4"></h3>
        <img id="modalImage" class="w-full max-h-80 object-cover rounded mb-4 hidden">
        <div id="modalContent" class="prose max-w-none"></div>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/tluao2wh6pnxfechhnbj6wumfwolk3sulz86lkh62iu2mmjm/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}";
    const urlMinio = "{{ $_ENV['MINIO_SERVER_URL'] }}";
    let editId = null;
    let isViewing = false;

    const title = document.getElementById('title');
    const image = document.getElementById('image');
    const preview = document.getElementById('preview');
    const saveBtn = document.getElementById('saveBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const newsList = document.getElementById('news-list');

    // --- TinyMCE ---
    tinymce.init({
        selector: '#content',
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
    image.addEventListener('change', e => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = ev => {
                preview.src = ev.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else preview.classList.add('hidden');
    });

    // --- Load danh sách tin tức ---
    async function loadNews() {
        newsList.innerHTML = `<tr><td colspan="3" class="text-center text-gray-500 p-4">Đang tải dữ liệu...</td></tr>`;
        const res = await fetch(`${baseUrl}/api/doc-tin-tuc-theo-rap`);
        const data = await res.json();

        if (data.success && data.data.length > 0) {
            newsList.innerHTML = data.data.map(bv => `
                <tr>
                    <td class="p-2 border">${bv.tieu_de}</td>
                    <td class="p-2 border">${new Date(bv.ngay_tao).toLocaleDateString('vi-VN')}</td>
                    <td class="p-2 border text-center space-x-2">
                        <button class="text-blue-600 hover:underline" onclick="viewNews(${bv.id})">Xem</button>
                        <button class="text-red-600 hover:underline" onclick="deleteNews(${bv.id})">Xóa</button>
                    </td>
                </tr>
            `).join('');
        } else {
            newsList.innerHTML = `<tr><td colspan="3" class="text-center text-gray-500 p-4">Chưa có tin tức nào.</td></tr>`;
        }
    }

    // --- Thêm / Cập nhật tin tức ---
    saveBtn.addEventListener('click', async () => {
        const content = tinymce.get('content').getContent();
        const fd = new FormData();
        fd.append('tieu_de', title.value.trim());
        fd.append('noi_dung', content);
        fd.append('trang_thai', 0);
        if (image.files[0]) fd.append('anh_tin_tuc', image.files[0]);

        if (!title.value.trim() || !content) {
            alert('Vui lòng nhập tiêu đề và nội dung.');
            return;
        }

        const url = editId 
            ? `${baseUrl}/api/sua-tin-tuc/${editId}` 
            : `${baseUrl}/api/them-tin-tuc`;

        const res = await fetch(url, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            alert(editId ? 'Cập nhật thành công!' : 'Thêm mới thành công!');
            resetForm();
            loadNews();
        } else {
            alert('Lỗi: ' + data.message);
        }
    });

    // --- Reset form ---
    function resetForm() {
        editId = null;
        isViewing = false;
        title.value = '';
        tinymce.get('content').setContent('');
        image.value = '';
        preview.classList.add('hidden');
        title.removeAttribute('readonly');
        image.disabled = false;
        saveBtn.textContent = 'Thêm mới';
        saveBtn.classList.remove('hidden');
        cancelBtn.classList.add('hidden');
        document.getElementById('form-title').textContent = 'Thêm tin tức mới';
    }

    cancelBtn.addEventListener('click', resetForm);

    // --- Xem tin tức ---
    window.viewNews = async (id) => {
        try {
            const res = await fetch(`${baseUrl}/api/chi-tiet-tin-tuc/${id}`);
            const data = await res.json();

            if (data.success) {
                const bv = data.data;
                editId = id;
                isViewing = true;

                title.value = bv.tieu_de;
                tinymce.get('content').setContent(bv.noi_dung || '');
                if (bv.anh_tin_tuc) {
                    preview.src = `${urlMinio}/${bv.anh_tin_tuc}`;
                    preview.classList.remove('hidden');
                } else preview.classList.add('hidden');

                title.setAttribute('readonly', true);
                image.disabled = true;
                saveBtn.textContent = 'Sửa bài viết';
                document.getElementById('form-title').textContent = 'Xem chi tiết tin tức';
                cancelBtn.classList.remove('hidden');
                saveBtn.classList.remove('hidden');
            } else {
                alert('Không tải được bài viết.');
            }
        } catch (err) {
            console.error(err);
            alert('Lỗi kết nối khi xem chi tiết bài viết.');
        }
    };

    // --- Chuyển sang chế độ chỉnh sửa ---
    saveBtn.addEventListener('click', async (e) => {
        if (isViewing && editId) {
            e.preventDefault();
            isViewing = false;
            title.removeAttribute('readonly');
            image.disabled = false;
            const editor = tinymce.get('content');
            if (editor) editor.mode.set('design');
            saveBtn.textContent = 'Cập nhật';
            document.getElementById('form-title').textContent = 'Chỉnh sửa tin tức';
            return;
        }
    }, { capture: true });

    // --- Xóa tin tức ---
    window.deleteNews = async (id) => {
        if (!confirm('Bạn có chắc muốn xóa bài viết này?')) return;
        const res = await fetch(`${baseUrl}/api/duyet-tin-tuc/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trang_thai: 4 })
        });
        const data = await res.json();
        if (data.success) {
            alert('Bài viết đã được ẩn.');
            loadNews();
        } else {
            alert('Lỗi khi xóa bài viết: ' + (data.message || 'Không xác định.'));
        }
    };

    // --- Khởi động ---
    loadNews();
});
</script>
@endsection
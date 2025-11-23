import Spinner from './util/spinner.js';

// Hiển thị toast thông báo
function showToast(msg, type = 'success') {
    const toast = document.createElement('div');
    const icon = type === 'success' 
        ? '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>'
        : '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';
    
    toast.className = `fixed top-6 right-6 z-[9999] px-5 py-3 rounded-lg shadow-lg text-white text-sm font-medium transition-all transform translate-x-0 flex items-center gap-3 ${
        type === 'success' ? 'bg-gradient-to-r from-green-500 to-green-600' : 'bg-gradient-to-r from-red-500 to-red-600'
    }`;
    toast.innerHTML = `${icon}<span>${msg}</span>`;
    document.body.appendChild(toast);
    
    // Animation
    setTimeout(() => toast.style.transform = 'translateX(400px)', 2500);
    setTimeout(() => toast.remove(), 3000);
}

// Render danh sách vị trí công việc
async function fetchAndRenderViTri() {
    const listDiv = document.getElementById('vitri-list');
    if (!listDiv) return;
    const spinner = Spinner.show({ target: listDiv, text: 'Đang tải...' });
    try {
        const res = await fetch(`${listDiv.dataset.url}/api/vi-tri-cong-viec`);
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Lỗi lấy danh sách vị trí');
        // Render table
        let html = `<table class="min-w-full border-2 border-gray-300 text-left rounded-lg overflow-hidden shadow-md">
            <thead>
            <tr class="bg-gradient-to-r from-gray-100 to-gray-200">
                <th class="border-2 border-gray-300 px-4 py-3 font-bold text-gray-700 text-center w-16">#</th>
                <th class="border-2 border-gray-300 px-4 py-3 font-bold text-gray-700">Tên vị trí công việc</th>
                <th class="border-2 border-gray-300 px-4 py-3 font-bold text-gray-700 text-center w-32">Thao tác</th>
            </tr></thead><tbody>`;
        data.data.forEach((vt, idx) => {
            html += `<tr class="hover:bg-blue-50 transition-colors">
                <td class="border-2 border-gray-300 px-4 py-3 text-center text-gray-600">${idx + 1}</td>
                <td class="border-2 border-gray-300 px-4 py-3">
                    <span class="vitri-ten font-medium text-gray-800" data-id="${vt.id}" data-ten="${vt.ten}">${vt.ten}</span>
                </td>
                <td class="border-2 border-gray-300 px-4 py-3 text-center">
                    <button class="btn-sua-vitri bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all shadow hover:shadow-md" data-id="${vt.id}" data-ten="${vt.ten}">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Sửa
                    </button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        listDiv.innerHTML = html;
    } catch (e) {
        listDiv.innerHTML = `<div class="text-red-600 py-4">${e.message}</div>`;
    } finally {
        Spinner.hide(spinner);
    }
}

// Thêm vị trí công việc
document.getElementById('vitri-form')?.addEventListener('submit', async e => {
    e.preventDefault();
    const input = document.getElementById('input-vitri');
    const listDiv = document.getElementById('vitri-list');
    if (!input.value.trim()) {
        showToast('Vui lòng nhập tên vị trí', 'error');
        return;
    }
    const btn = e.target.querySelector('button[type=submit]');
    const oldHtml = btn.innerHTML;
    const spinner = Spinner.show({ target: btn, size: 'sm' });
    btn.disabled = true;
    try {
        const res = await fetch(`${listDiv.dataset.url}/api/vi-tri-cong-viec`, {
            method: 'POST',
            body: new URLSearchParams({ ten: input.value.trim() }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Lỗi thêm vị trí');
        showToast('Thêm vị trí thành công');
        input.value = '';
        fetchAndRenderViTri();
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        Spinner.hide(spinner);
        btn.innerHTML = oldHtml;
        btn.disabled = false;
    }
});

// Sửa vị trí công việc (inline)
document.addEventListener('click', async e => {
    if (e.target.classList.contains('btn-sua-vitri')) {
        const id = e.target.dataset.id;
        const oldTen = e.target.dataset.ten;
        const newTen = prompt('Nhập tên vị trí mới:', oldTen);
        const listDiv = document.getElementById('vitri-list');
        if (newTen === null || newTen.trim() === '' || newTen === oldTen) return;
        const spinner = Spinner.show({ target: e.target, size: 'sm' });
        e.target.disabled = true;
        try {
            const res = await fetch(`${listDiv.dataset.url}/api/vi-tri-cong-viec/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ten: newTen.trim() }),
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Lỗi sửa vị trí');
            showToast('Đã sửa vị trí thành công');
            fetchAndRenderViTri();
        } catch (err) {
            showToast(err.message, 'error');
        } finally {
            Spinner.hide(spinner);
            e.target.disabled = false;
        }
    }
});

// Tải danh sách vị trí khi vào tab
document.addEventListener('DOMContentLoaded', () => {
    fetchAndRenderViTri();
    // Nếu dùng tab động, có thể thêm sự kiện để gọi fetchAndRenderViTri khi chuyển tab
});


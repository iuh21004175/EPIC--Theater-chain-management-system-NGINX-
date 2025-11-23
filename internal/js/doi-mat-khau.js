import Spinner from './util/spinner.js';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-doi-mat-khau');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Lấy dữ liệu
        const matKhauCu = form['matkhau_cu'].value.trim();
        const matKhauMoi = form['matkhau_moi'].value.trim();
        const xacNhanMatKhauMoi = form['xacnhan_matkhau_moi'].value.trim();
        const btn = form.querySelector('button[type=submit]');
        const oldBtnHtml = btn.innerHTML;

        // Xóa thông báo cũ
        let oldAlert = document.getElementById('alert-doi-mat-khau');
        if (oldAlert) oldAlert.remove();

        // Kiểm tra xác nhận mật khẩu mới
        if (matKhauMoi !== xacNhanMatKhauMoi) {
            const alert = document.createElement('div');
            alert.id = 'alert-doi-mat-khau';
            alert.className = 'mt-4 p-3 rounded text-sm bg-red-50 text-red-700 border border-red-400';
            alert.innerHTML = 'Xác nhận mật khẩu mới không khớp.';
            form.parentNode.insertBefore(alert, form.nextSibling);
            return;
        }

        btn.disabled = true;
        const spinner = Spinner.show({ target: form, size: 'sm', overlay: true, text: 'Đang xử lý...' });

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    MatKhauCu: matKhauCu,
                    MatKhauMoi: matKhauMoi
                })
            });
            const data = await res.json();

            if (data.success) {form.reset();
                form.reset();
                alert(data.message || 'Đổi mật khẩu thành công!');
                window.location.href = data.href;
            }
            else {
                alert(data.error || 'Có lỗi xảy ra!');
            }
        } catch (err) {
            alert('Không thể gửi yêu cầu. Vui lòng thử lại sau.');
        }
    });
});
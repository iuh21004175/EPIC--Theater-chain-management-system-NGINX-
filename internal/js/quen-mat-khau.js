document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('forgot-password-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = form.email.value.trim();
        const btn = form.querySelector('button[type=submit]');
        const oldBtnHtml = btn.innerHTML;

        // Hiển thị loading
        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin h-5 w-5 inline mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg> Đang gửi...`;

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ email })
            });
            const data = await res.json();

            if (data.success) {
                alert(data.message || 'Thành công!');
                form.reset();
                window.location.href = data.href;
            } else {
                alert(data.error || 'Có lỗi xảy ra!');
            }
        } catch (err) {
            alert('Không thể gửi yêu cầu. Vui lòng thử lại sau.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = oldBtnHtml;
        }
    });
});
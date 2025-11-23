document.addEventListener('DOMContentLoaded', function() {
    const cinemaList = document.getElementById('cinema-list');
    const apiUrl = cinemaList.dataset.url + '/api/rap-phim'; // Đổi lại đúng endpoint API của bạn

    fetch(apiUrl)
        .then(res => res.json())
        .then(data => {
            if (data.success && Array.isArray(data.data)) {
                cinemaList.innerHTML = '';
                data.data.forEach(cinema => {
                    const isHighlight = cinema.so_suat_chua_xem > 0;
                    const badgeClass = isHighlight
                        ? 'bg-red-600 animate-pulse text-white'
                        : 'bg-blue-600 text-white';
                    const icon = isHighlight
                        ? `<svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 20h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/></svg>`
                        : '';
                    const badgeText = `${icon}${cinema.so_suat_chua_duyet} suất chiếu chưa duyệt`;

                    const card = document.createElement('div');
                    card.className = 'border rounded-lg p-4 flex items-center justify-between bg-gray-50 hover:bg-gray-100 cursor-pointer transition';
                    card.innerHTML = `
                        <div>
                            <div class="font-semibold text-lg">${cinema.ten}</div>
                            <div class="text-gray-500 text-sm">${cinema.dia_chi || ''}</div>
                        </div>
                        <div class="flex items-center">
                            <span class="${badgeClass} text-xs font-bold px-3 py-1 rounded-full ml-2">
                                ${badgeText}
                            </span>
                        </div>
                    `;
                    // Chuyển hướng khi click vào card
                    card.addEventListener('click', () => {
                        window.location.href = `${cinemaList.dataset.url}/internal/duyet-suat-chieu/${cinema.id}`;
                    });
                    cinemaList.appendChild(card);
                });
            } else {
                cinemaList.innerHTML = '<div class="col-span-3 text-center text-gray-400 py-8">Không có rạp phim nào cần duyệt suất chiếu.</div>';
            }
        })
        .catch(() => {
            cinemaList.innerHTML = '<div class="col-span-3 text-center text-red-400 py-8">Lỗi khi tải danh sách rạp phim.</div>';
        });
});
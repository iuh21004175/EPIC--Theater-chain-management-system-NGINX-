document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('lich-table-body');
    const appElement = document.getElementById('duyet-lich-goi-video-app');
    const urlBase = appElement?.dataset.url;
    // Fallback: Nếu không có data-urlinternal, dùng urlBase + '/internal'
    const urlInternal = appElement?.dataset.urlinternal;
    
    // Kiểm tra nếu element không tồn tại (không phải trang tư vấn)
    if (!tableBody || !urlBase) {
        console.warn('Missing required elements or data attributes');
        return;
    }
    
    console.log('🔧 Config:', { urlBase, urlInternal });

    // Kết nối Socket.IO
    const socket = io(window.config.socketUrl);

    // Biến để track xem đã load dữ liệu chưa
    let dataLoaded = false;
    let currentPage = 1;
    let isLoading = false;
    let hasMore = true;
    const perPage = 10;

    // Load danh sách lịch với phân trang
    async function loadDanhSachLich(page = 1, append = false) {
        if (isLoading) return;
        
        try {
            isLoading = true;
            const apiUrl = `${urlBase}/api/goi-video/danh-sach-lich?page=${page}&per_page=${perPage}`;
            console.log(`📡 Fetching page ${page}:`, apiUrl);
            const response = await fetch(apiUrl);
            const result = await response.json();
            console.log(`✅ Response:`, result);

            if (result.success) {
                if (append) {
                    appendDanhSachLich(result.data);
                } else {
                    renderDanhSachLich(result.data);
                }
                
                // Cập nhật trạng thái phân trang
                currentPage = result.pagination.current_page;
                hasMore = result.pagination.has_more;
                dataLoaded = true;
                
                console.log(`📊 Pagination - Page: ${currentPage}, Total: ${result.pagination.total}, HasMore: ${hasMore}`);
                
                // Hiển thị/ẩn nút Load More
                updateLoadMoreButton();
                
                // Cập nhật thông tin phân trang trong UI
                updatePaginationInfo(result.pagination);
            } else {
                tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-red-500">${result.message}</td></tr>`;
            }
        } catch (error) {
            console.error('Lỗi load danh sách:', error);
            tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-red-500">Lỗi tải dữ liệu</td></tr>`;
        } finally {
            isLoading = false;
        }
    }

    // Append thêm dữ liệu vào bảng (cho Load More)
    function appendDanhSachLich(danhSach) {
        if (danhSach.length === 0) return;

        const newRows = danhSach.map(lich => `
            <tr>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium">${lich.khachhang.ho_ten}</div>
                    <div class="text-sm text-gray-500">${lich.khachhang.email}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm">${lich.chu_de}</div>
                </td>
                <td class="px-6 py-4 text-sm">${formatDateTime(lich.thoi_gian_dat)}</td>
                <td class="px-6 py-4">${getTrangThaiBadge(lich.trang_thai)}</td>
                <td class="px-6 py-4 text-sm">${lich.nhanvien ? lich.nhanvien.ten : '-'}</td>
                <td class="px-6 py-4 text-right">${getActions(lich)}</td>
            </tr>
        `).join('');

        tableBody.insertAdjacentHTML('beforeend', newRows);
        attachEventListeners();
    }

    // Render danh sách
    function renderDanhSachLich(danhSach) {
        if (danhSach.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Không có lịch gọi video</td></tr>`;
            updateLoadMoreButton();
            return;
        }

        tableBody.innerHTML = danhSach.map(lich => `
            <tr>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium">${lich.khachhang.ho_ten}</div>
                    <div class="text-sm text-gray-500">${lich.khachhang.email}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm">${lich.chu_de}</div>
                </td>
                <td class="px-6 py-4 text-sm">${formatDateTime(lich.thoi_gian_dat)}</td>
                <td class="px-6 py-4">${getTrangThaiBadge(lich.trang_thai)}</td>
                <td class="px-6 py-4 text-sm">${lich.nhanvien ? lich.nhanvien.ten : 'Chưa có'}</td>
                <td class="px-6 py-4 text-right">${getActions(lich)}</td>
            </tr>
        `).join('');

        attachEventListeners();
    }

    function getTrangThaiBadge(trangThai) {
        const badges = {
            1: '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Chờ NV</span>',
            2: '<span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Đã chọn NV</span>',
            3: '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Đang gọi</span>',
            4: '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Hoàn thành</span>'
        };
        return badges[trangThai] || '';
    }

    function getActions(lich) {
        const idNhanVienHienTai = appElement?.dataset.idnhanvien;
        
        if (lich.trang_thai === 1) {
            // Trạng thái "Chờ nhân viên" - Cho phép chọn tư vấn
            return `<button class="btn-chon text-blue-600 hover:text-blue-900 font-medium" data-id="${lich.id}">Chọn tư vấn</button>`;
        } else if (lich.trang_thai === 2) {
            // Trạng thái "Đã chọn NV" - Chỉ nhân viên được phân công mới thấy nút
            if (lich.id_nhanvien && idNhanVienHienTai && lich.id_nhanvien == idNhanVienHienTai) {
                return `<a href="${urlInternal}/video-call?room=${lich.room_id}" 
                           class="inline-block px-4 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700 transition-colors mr-2">
                            <i class="fas fa-video mr-1"></i> Gọi ngay
                        </a>
                        <button class="btn-huy px-4 py-2 bg-red-600 text-white text-sm font-medium rounded hover:bg-red-700 transition-colors" 
                                data-id="${lich.id}">
                            <i class="fas fa-times mr-1"></i> Hủy
                        </button>`;
            } else {
                // Nhân viên khác không được phép tham gia
                return `<span class="text-sm text-gray-500 italic">
                            <i class="fas fa-lock mr-1"></i> Đã được ${lich.nhanvien ? lich.nhanvien.ten : 'nhân viên khác'} nhận
                        </span>`;
            }
        } else if (lich.trang_thai === 3) {
            // Trạng thái "Đang gọi" - Chỉ nhân viên được phân công mới vào lại được
            if (lich.id_nhanvien && idNhanVienHienTai && lich.id_nhanvien == idNhanVienHienTai) {
                return `<a href="${urlInternal}/video-call?room=${lich.room_id}" 
                           class="inline-block px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700 transition-colors">
                            <i class="fas fa-video mr-1"></i> Vào lại
                        </a>`;
            } else {
                // Nhân viên khác không được phép vào
                return `<span class="text-sm text-gray-500 italic">
                            <i class="fas fa-phone mr-1"></i> ${lich.nhanvien ? lich.nhanvien.ten : 'Nhân viên khác'} đang gọi
                        </span>`;
            }
        }
        return '<span class="text-gray-400">-</span>';
    }

    function attachEventListeners() {
        document.querySelectorAll('.btn-chon').forEach(btn => {
            btn.addEventListener('click', async function() {
                if (confirm('Bạn muốn nhận tư vấn cho khách hàng này?')) {
                    await chonTuVan(this.dataset.id);
                }
            });
        });

        document.querySelectorAll('.btn-huy').forEach(btn => {
            btn.addEventListener('click', async function() {
                if (confirm('Bạn muốn hủy tư vấn?')) {
                    await huyTuVan(this.dataset.id);
                }
            });
        });
    }

    async function chonTuVan(idLich) {
        const response = await fetch(`${urlBase}/api/goi-video/${idLich}/chon-tu-van`, { method: 'POST' });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            currentPage = 1;
            hasMore = true;
            loadDanhSachLich(1, false);
        }
    }

    async function huyTuVan(idLich) {
        const response = await fetch(`${urlBase}/api/goi-video/${idLich}/huy`, { method: 'POST' });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            currentPage = 1;
            hasMore = true;
            loadDanhSachLich(1, false);
        }
    }

    function formatDateTime(dt) {
        const d = new Date(dt);
        return `${d.toLocaleDateString('vi-VN')} ${d.toLocaleTimeString('vi-VN')}`;
    }

    // Cập nhật thông tin phân trang trên UI
    function updatePaginationInfo(pagination) {
        let paginationInfo = document.getElementById('pagination-info');
        
        if (!paginationInfo) {
            // Tạo phần tử hiển thị thông tin phân trang
            const tableContainer = tableBody.closest('table');
            if (tableContainer && tableContainer.parentElement) {
                const infoDiv = document.createElement('div');
                infoDiv.id = 'pagination-info';
                infoDiv.className = 'px-6 py-3 bg-gray-50 border-t border-gray-200 text-sm text-gray-600';
                tableContainer.parentElement.appendChild(infoDiv);
                paginationInfo = infoDiv;
            }
        }
        
        if (paginationInfo) {
            const loaded = currentPage * perPage;
            const showing = Math.min(loaded, pagination.total);
            paginationInfo.innerHTML = `Hiển thị <strong>${showing}</strong> / <strong>${pagination.total}</strong> lịch hẹn | Trang <strong>${currentPage}</strong>`;
        }
    }

    // Cập nhật trạng thái nút Load More
    function updateLoadMoreButton() {
        let loadMoreBtn = document.getElementById('load-more-btn');
        
        if (!loadMoreBtn) {
            // Tạo nút Load More nếu chưa có
            const loadMoreRow = document.createElement('tr');
            loadMoreRow.id = 'load-more-row';
            loadMoreRow.innerHTML = `
                <td colspan="6" class="px-6 py-4 text-center">
                    <button id="load-more-btn" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-arrow-down mr-2"></i> Tải thêm
                    </button>
                </td>
            `;
            tableBody.parentElement.appendChild(loadMoreRow);
            loadMoreBtn = document.getElementById('load-more-btn');
            
            // Gắn sự kiện click
            loadMoreBtn.addEventListener('click', function() {
                if (!isLoading && hasMore) {
                    loadDanhSachLich(currentPage + 1, true);
                }
            });
        }
        
        // Hiển thị/ẩn nút dựa trên hasMore
        const loadMoreRow = document.getElementById('load-more-row');
        if (loadMoreRow) {
            loadMoreRow.style.display = hasMore ? '' : 'none';
        }
        
        // Cập nhật text nút khi đang loading
        if (loadMoreBtn) {
            if (isLoading) {
                loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang tải...';
                loadMoreBtn.disabled = true;
            } else {
                loadMoreBtn.innerHTML = '<i class="fas fa-arrow-down mr-2"></i> Tải thêm';
                loadMoreBtn.disabled = false;
            }
        }
    }

    socket.on('lichgoivideo:moi', () => {
        // Reset về trang 1 khi có lịch mới
        currentPage = 1;
        hasMore = true;
        loadDanhSachLich(1, false);
    });

    // Load dữ liệu khi tab video được active
    const btnVideo = document.getElementById('tab-btn-video');
    if (btnVideo) {
        btnVideo.addEventListener('click', function() {
            // Load dữ liệu lần đầu khi click vào tab
            if (!dataLoaded) {
                loadDanhSachLich();
            }
        });
    } else {
        // Nếu không có tab (trang riêng), load ngay
        loadDanhSachLich();
    }
});

import Spinner from "./util/spinner.js";
document.addEventListener('DOMContentLoaded', async function() {
    // Đổi id cho đúng giao diện mới
    const rapList = document.getElementById('rap-list');
    const rapPhimList = document.getElementById('rap-phim-list');
    const selectedRapTitle = document.getElementById('selected-rap-title');
    const movieStockList = document.getElementById('movie-stock-list');
    const movieStockPagination = document.getElementById('movie-stock-pagination');
    const urlWebBase = movieStockList.dataset.url;
    const urlMinio = movieStockList.dataset.urlminio;

    let allPhim = [];
    let allRap = [];
    let currentRapId = null;
    let movieStockPage = 1;
    let movieStockTotalPages = 1;

    // Lấy danh sách rạp
    const rapSpinner = Spinner.show({ target: rapList, overlay: true, text: 'Đang tải danh sách rạp...' });
    const rapRes = await fetch(`${rapList.dataset.url}/api/rap-phim`);
    const rapData = await rapRes.json();
    Spinner.hide(rapSpinner);
    allRap = rapData.data || [];

    // Render danh sách rạp - Cải thiện: chỉ cập nhật phần tử thay đổi
    function renderRapList() {
        const existingItems = new Map();
        rapList.querySelectorAll('.rap-item').forEach(item => {
            const id = item.dataset.id;
            if (id) existingItems.set(id, item);
        });

        const fragment = document.createDocumentFragment();
        allRap.forEach(rap => {
            let item = existingItems.get(String(rap.id));
            if (!item) {
                // Tạo phần tử mới
                item = document.createElement('div');
                item.className = 'rap-item flex items-center px-3 py-2 rounded cursor-pointer transition-all duration-200';
                item.dataset.id = rap.id;
                item.innerHTML = `
                    <input type="hidden" class="mr-2 rap-checkbox" data-id="${rap.id}">
                    <span>${rap.ten}</span>
                `;
                // Gán event listener cho phần tử mới
                item.addEventListener('click', function(e) {
                    if (e.target.classList.contains('rap-checkbox')) return;
                    rapList.querySelectorAll('.rap-item').forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');
                    currentRapId = this.dataset.id;
                    const rapName = this.querySelector('span').textContent.trim();
                    selectedRapTitle.textContent = `Phim được phân phối cho: ${rapName}`;
                    document.getElementById('movie-stock-title').textContent = `Phim chưa phân phối cho rạp: ${rapName}`;
                    
                    // Reset về trang 1 khi chọn rạp mới
                    movieStockPage = 1;
                    
                    // Load danh sách phân phối trước, sau đó mới load danh sách "Phim chưa phân phối" để có thể lọc đúng
                    // Force reload cả hai danh sách khi chọn rạp mới
                    loadPhimOfRap(currentRapId, true).then(() => {
                        // Reload danh sách "Phim chưa phân phối" từ trang 1 với rạp mới được chọn (force reload)
                        loadMovieStockList(1, getMovieStockFilters(), true, true);
                    });
                });
                fragment.appendChild(item);
            } else {
                // Cập nhật phần tử hiện có
                const span = item.querySelector('span');
                if (span && span.textContent !== rap.ten) {
                    span.textContent = rap.ten;
                }
                // Cập nhật trạng thái selected
                if (currentRapId == rap.id) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }
                existingItems.delete(String(rap.id));
            }
        });

        // Xóa các phần tử không còn trong danh sách
        existingItems.forEach((item, id) => {
            item.style.transition = 'opacity 0.2s, transform 0.2s';
            item.style.opacity = '0';
            item.style.transform = 'translateX(-10px)';
            setTimeout(() => item.remove(), 200);
        });

        // Thêm các phần tử mới
        if (fragment.children.length > 0) {
            rapList.appendChild(fragment);
        }
    }


    // Hàm helper: Thêm một phim vào danh sách phân phối
    async function addPhimToRapList(phimId) {
        if (!phimId || !currentRapId) return false;

        // Kiểm tra xem phim đã có trong danh sách phân phối chưa (trong DOM)
        const existingItem = rapPhimList.querySelector(`.phim-phanphoi-item[data-phim-id="${phimId}"]`);
        if (existingItem) {
            console.log('Phim đã được phân phối rồi');
            return false; // Đã tồn tại
        }

        try {
            const res = await fetch(`${rapPhimList.dataset.url}/api/phan-phoi-phim/them`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_rap: currentRapId,
                    phim_id: phimId
                })
            });
            const data = await res.json();
            
            if (!data.success) {
                // Kiểm tra nếu lỗi là duplicate
                if (data.message && (data.message.includes('Duplicate') || data.message.includes('đã được phân phối'))) {
                    console.log('Phim đã được phân phối rồi (từ server)');
                    // Reload danh sách phân phối để đồng bộ
                    await loadPhimOfRap(currentRapId);
                    // Reload danh sách phim chưa phân phối
                    await loadMovieStockList(movieStockPage, getMovieStockFilters(), true);
                    return false;
                }
                alert(data.message || 'Có lỗi xảy ra khi phân phối phim');
                return false;
            }

            // Lấy thông tin phim từ API hoặc từ danh sách phim hiện có
            let phim = allPhim.find(p => String(p.id) === String(phimId));
            if (!phim) {
                // Nếu không tìm thấy trong danh sách hiện có, gọi API để lấy thông tin
                const phimRes = await fetch(`${urlWebBase}/api/phim/${phimId}`);
                const phimData = await phimRes.json();
                if (phimData.success) {
                    phim = phimData.data;
                } else {
                    return false;
                }
            }

            // Xóa empty state nếu có
            const emptyState = rapPhimList.querySelector('.col-span-full.text-gray-400');
            if (emptyState && emptyState.textContent.includes('Chưa có phim')) {
                emptyState.remove();
            }

            // Tạo phần tử mới
            const item = document.createElement('div');
            item.className = 'phim-phanphoi-item flex items-center gap-3 border rounded px-3 py-2 bg-gray-50 transition-all duration-200';
            item.dataset.phimId = phim.id;
            item.style.opacity = '0';
            item.style.transform = 'translateY(-10px)';
            
            const trangThai = getTrangThaiPhim(phim);
            item.innerHTML = `
                <img src="${urlMinio}/${phim.poster_url}" class="w-10 h-14 object-cover rounded" alt="${phim.ten_phim}">
                <div class="flex-1">
                    <div class="font-semibold">${phim.ten_phim}</div>
                    <span class="inline-block px-2 py-0.5 rounded text-xs mt-1"
                        style="background:${trangThai.color};color:${trangThai.textColor}">
                        ${trangThai.text}
                    </span>
                </div>
                <button class="btn-remove-phanphoi text-red-600 hover:text-red-800 transition-colors" data-id="${phim.id}">Gỡ</button>
            `;
            
            // Gán event listener cho nút xóa
            const removeBtn = item.querySelector('.btn-remove-phanphoi');
            if (removeBtn) {
                removeBtn.addEventListener('click', handleRemovePhim);
            }
            
            // Thêm vào danh sách với animation
            rapPhimList.appendChild(item);
            setTimeout(() => {
                item.style.transition = 'opacity 0.3s, transform 0.3s';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, 10);

            // Xóa ngay phim khỏi danh sách "Phim chưa phân phối" với animation
            const movieCard = movieStockList.querySelector(`.movie-card[data-id="${phimId}"]`);
            if (movieCard) {
                movieCard.style.transition = 'opacity 0.2s, transform 0.2s';
                movieCard.style.opacity = '0';
                movieCard.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    movieCard.remove();
                }, 200);
            }

            // Reload danh sách "Phim chưa phân phối" để đảm bảo đồng bộ với server (không dùng spinner)
            // Điều này đảm bảo phim đã phân phối sẽ không còn hiển thị trong danh sách chưa phân phối
            // Empty state sẽ được tạo tự động trong hàm loadMovieStockList nếu cần
            setTimeout(() => {
                loadMovieStockList(movieStockPage, getMovieStockFilters(), true);
            }, 300);

            return true;
        } catch (error) {
            console.error('Error adding phim to rap:', error);
            alert('Có lỗi xảy ra khi phân phối phim');
            return false;
        }
    }

    // Hàm helper: Xóa một phim khỏi danh sách phân phối
    async function removePhimFromRapList(phimId) {
        if (!phimId || !currentRapId) return false;

        try {
            const res = await fetch(`${rapPhimList.dataset.url}/api/phan-phoi-phim/xoa`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_rap: currentRapId,
                    phim_id: phimId
                })
            });
            const data = await res.json();
            
            if (!data.success) {
                alert(data.message || 'Có lỗi xảy ra khi xóa phân phối');
                return false;
            }

            // Tìm và xóa phần tử
            const item = rapPhimList.querySelector(`.phim-phanphoi-item[data-phim-id="${phimId}"]`);
            if (item) {
                item.style.transition = 'opacity 0.2s, transform 0.2s';
                item.style.opacity = '0';
                item.style.transform = 'translateX(10px)';
                setTimeout(() => {
                    item.remove();
                    
                    // Kiểm tra nếu danh sách trống, hiển thị empty state
                    if (rapPhimList.querySelectorAll('.phim-phanphoi-item').length === 0) {
                        const emptyDiv = document.createElement('div');
                        emptyDiv.className = 'col-span-full text-gray-400';
                        emptyDiv.textContent = 'Chưa có phim nào được phân phối cho rạp này.';
                        emptyDiv.style.opacity = '0';
                        rapPhimList.appendChild(emptyDiv);
                        setTimeout(() => {
                            emptyDiv.style.transition = 'opacity 0.3s';
                            emptyDiv.style.opacity = '1';
                        }, 50);
                    }
                }, 200);
            }

            // Reload danh sách "Phim chưa phân phối" để thêm lại phim đã xóa (không dùng spinner)
            setTimeout(() => {
                loadMovieStockList(movieStockPage, getMovieStockFilters(), true);
            }, 250);

            return true;
        } catch (error) {
            console.error('Error removing phim from rap:', error);
            alert('Có lỗi xảy ra khi xóa phân phối');
            return false;
        }
    }

    // Hàm xử lý nút xóa
    async function handleRemovePhim() {
        if (!confirm('Bạn chắc chắn muốn bỏ phân phối phim này khỏi rạp?')) return;
        
        const phimId = this.dataset.id;
        await removePhimFromRapList(phimId);
    }

    // Kéo thả vào cột 3 để phân phối
    rapPhimList.addEventListener('dragover', e => e.preventDefault());
    rapPhimList.addEventListener('drop', async function(e) {
        e.preventDefault();
        const phimId = e.dataTransfer.getData('phimId');
        if (!phimId || !currentRapId) return;

        // Chỉ thêm phần tử mới, không load lại toàn bộ
        await addPhimToRapList(phimId);
    });

    // Load phim đã phân phối cho rạp - Cải thiện: cập nhật mượt mà
    async function loadPhimOfRap(rapId, forceReload = false) {
        const spinner = Spinner.show({ target: rapPhimList, overlay: true, text: 'Đang tải phim của rạp...' });
        
        // Nếu forceReload, xóa hết danh sách cũ trước
        if (forceReload) {
            const existingItems = rapPhimList.querySelectorAll('.phim-phanphoi-item');
            existingItems.forEach(item => {
                item.style.transition = 'opacity 0.2s, transform 0.2s';
                item.style.opacity = '0';
                item.style.transform = 'translateX(10px)';
                setTimeout(() => item.remove(), 200);
            });
            // Xóa empty state nếu có
            const emptyState = rapPhimList.querySelector('.col-span-full.text-gray-400');
            if (emptyState) {
                emptyState.remove();
            }
            // Đợi một chút để các phần tử cũ được xóa xong
            await new Promise(resolve => setTimeout(resolve, 250));
        }
        
        // Hiển thị loading state mượt mà (nếu không force reload)
        const existingItems = rapPhimList.querySelectorAll('.phim-phanphoi-item');
        if (!forceReload) {
            existingItems.forEach(item => {
                item.style.opacity = '0.5';
                item.style.pointerEvents = 'none';
            });
        }

        const res = await fetch(`${rapPhimList.dataset.url}/api/phan-phoi-phim/${rapId}`);
        const data = await res.json();
        Spinner.hide(spinner);

        const phimDaPhanPhoi = data.success && Array.isArray(data.data) ? data.data : [];
        
        if (phimDaPhanPhoi.length === 0) {
            // Xóa tất cả phần tử hiện có với animation
            existingItems.forEach((item, index) => {
                setTimeout(() => {
                    item.style.transition = 'opacity 0.2s, transform 0.2s';
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(10px)';
                    setTimeout(() => item.remove(), 200);
                }, index * 50);
            });
            
            // Hiển thị empty state
            setTimeout(() => {
                if (rapPhimList.querySelectorAll('.phim-phanphoi-item').length === 0) {
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'col-span-full text-gray-400';
                    emptyDiv.textContent = 'Chưa có phim nào được phân phối cho rạp này.';
                    emptyDiv.style.opacity = '0';
                    rapPhimList.appendChild(emptyDiv);
                    setTimeout(() => {
                        emptyDiv.style.transition = 'opacity 0.3s';
                        emptyDiv.style.opacity = '1';
                    }, 50);
                }
            }, existingItems.length * 50 + 200);
            return;
        }

        // Xóa empty state nếu có
        const emptyState = rapPhimList.querySelector('.col-span-full.text-gray-400');
        if (emptyState && emptyState.textContent.includes('Chưa có phim')) {
            emptyState.style.transition = 'opacity 0.2s';
            emptyState.style.opacity = '0';
            setTimeout(() => emptyState.remove(), 200);
        }

        // Tạo map các phần tử hiện có
        const existingMap = new Map();
        rapPhimList.querySelectorAll('.phim-phanphoi-item').forEach(item => {
            const id = item.dataset.phimId;
            if (id) existingMap.set(id, item);
        });

        const fragment = document.createDocumentFragment();
        phimDaPhanPhoi.forEach((phim, index) => {
            let item = existingMap.get(String(phim.id));
            if (!item) {
                // Tạo phần tử mới
                item = document.createElement('div');
                item.className = 'phim-phanphoi-item flex items-center gap-3 border rounded px-3 py-2 bg-gray-50 transition-all duration-200';
                item.dataset.phimId = phim.id;
                item.style.opacity = '0';
                item.style.transform = 'translateY(-10px)';
                
                const trangThai = getTrangThaiPhim(phim);
                item.innerHTML = `
                    <img src="${movieStockList.dataset.urlminio}/${phim.poster_url}" class="w-10 h-14 object-cover rounded" alt="${phim.ten_phim}">
                    <div class="flex-1">
                        <div class="font-semibold">${phim.ten_phim}</div>
                        <span class="inline-block px-2 py-0.5 rounded text-xs mt-1"
                            style="background:${trangThai.color};color:${trangThai.textColor}">
                            ${trangThai.text}
                        </span>
                    </div>
                    <button class="btn-remove-phanphoi text-red-600 hover:text-red-800 transition-colors" data-id="${phim.id}">Gỡ</button>
                `;
                
                // Gán event listener cho nút xóa
                const removeBtn = item.querySelector('.btn-remove-phanphoi');
                if (removeBtn) {
                    removeBtn.addEventListener('click', handleRemovePhim);
                }
                
                fragment.appendChild(item);
                // Animation fade in
                setTimeout(() => {
                    item.style.transition = 'opacity 0.3s, transform 0.3s';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 50);
            } else {
                // Cập nhật phần tử hiện có
                item.style.opacity = '1';
                item.style.pointerEvents = 'auto';
                existingMap.delete(String(phim.id));
            }
        });

        // Xóa các phần tử không còn trong danh sách
        existingMap.forEach((item, id) => {
            item.style.transition = 'opacity 0.2s, transform 0.2s';
            item.style.opacity = '0';
            item.style.transform = 'translateX(10px)';
            setTimeout(() => item.remove(), 200);
        });

        // Thêm các phần tử mới
        if (fragment.children.length > 0) {
            rapPhimList.appendChild(fragment);
        }
    }



    // Khởi tạo giao diện
    renderRapList();

    // Tự động chọn rạp đầu tiên nếu có
    if (allRap.length > 0) {
        rapList.querySelector('.rap-item').click();
    }

    // ================== PHÂN TRANG VÀ TÌM KIẾM PHIM ================== //
    // Load danh sách phim với phân trang và bộ lọc - Cải thiện: cập nhật mượt mà
    async function loadMovieStockList(page = 1, filters = {}, skipSpinner = false, forceReload = false) {
        let spinner = null;
        if (!skipSpinner) {
            spinner = Spinner.show({ target: movieStockList, overlay: true, text: 'Đang tải phim...' });
        }
        
        // Nếu forceReload, xóa hết danh sách cũ trước
        if (forceReload) {
            const existingCards = movieStockList.querySelectorAll('.movie-card');
            existingCards.forEach(card => {
                card.style.transition = 'opacity 0.2s, transform 0.2s';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => card.remove(), 200);
            });
            // Xóa tất cả empty state nếu có
            const emptyStates = movieStockList.querySelectorAll('.text-center.text-gray-400');
            emptyStates.forEach(emptyState => {
                emptyState.remove();
            });
            // Đợi một chút để các phần tử cũ được xóa xong
            await new Promise(resolve => setTimeout(resolve, 250));
        }
        
        // Làm mờ các phần tử hiện có (nếu không force reload)
        const existingCards = movieStockList.querySelectorAll('.movie-card');
        if (!forceReload) {
            existingCards.forEach(card => {
                card.style.opacity = '0.5';
                card.style.pointerEvents = 'none';
            });
        }

        // Xây dựng query string
        const queryParams = { page, ...filters };
        if (currentRapId) {
            queryParams.idRap = currentRapId;
        }
        const params = new URLSearchParams(queryParams).toString();
        const res = await fetch(`${urlWebBase}/api/phim/?${params}`);
        const data = await res.json();

        // Gán lại danh sách phim hiện tại
        allPhim = data.data || [];

        // Lọc bỏ các phim đã có trong danh sách phân phối (client-side filtering)
        // Lấy danh sách ID các phim đã phân phối từ DOM
        const phimDaPhanPhoiIds = new Set();
        if (currentRapId) {
            rapPhimList.querySelectorAll('.phim-phanphoi-item').forEach(item => {
                const phimId = item.dataset.phimId;
                if (phimId) {
                    phimDaPhanPhoiIds.add(String(phimId));
                }
            });
        }

        // Lọc bỏ các phim đã phân phối
        const phimChuaPhanPhoi = data.data.filter(phim => !phimDaPhanPhoiIds.has(String(phim.id)));

        if (!data.success || phimChuaPhanPhoi.length === 0) {
            // Xóa tất cả phần tử hiện có với animation
            existingCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.transition = 'opacity 0.2s, transform 0.2s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => card.remove(), 200);
                }, index * 30);
            });
            
            // Xóa tất cả empty state cũ trước khi tạo mới
            const existingEmptyStates = movieStockList.querySelectorAll('.text-center.text-gray-400');
            existingEmptyStates.forEach(emptyState => {
                emptyState.remove();
            });
            
            setTimeout(() => {
                // Kiểm tra lại xem đã có empty state chưa và không còn movie card nào
                const hasEmptyState = movieStockList.querySelector('.text-center.text-gray-400');
                const hasMovieCards = movieStockList.querySelectorAll('.movie-card').length > 0;
                
                if (!hasEmptyState && !hasMovieCards) {
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'text-center py-8 text-gray-400';
                    emptyDiv.textContent = 'Không có phim nào';
                    emptyDiv.style.opacity = '0';
                    movieStockList.appendChild(emptyDiv);
                    setTimeout(() => {
                        emptyDiv.style.transition = 'opacity 0.3s';
                        emptyDiv.style.opacity = '1';
                    }, 50);
                }
            }, existingCards.length * 30 + 200);
            
            movieStockPagination.innerHTML = '';
            if (spinner) Spinner.hide(spinner);
            return;
        }

        // Xóa tất cả empty state nếu có
        const emptyStates = movieStockList.querySelectorAll('.text-center.text-gray-400');
        emptyStates.forEach(emptyState => {
            if (emptyState.textContent.includes('Không có phim')) {
                emptyState.style.transition = 'opacity 0.2s';
                emptyState.style.opacity = '0';
                setTimeout(() => emptyState.remove(), 200);
            }
        });

        // Tạo map các phần tử hiện có
        const existingMap = new Map();
        existingCards.forEach(card => {
            const id = card.dataset.id;
            if (id) existingMap.set(id, card);
        });

        const fragment = document.createDocumentFragment();
        phimChuaPhanPhoi.forEach((phim, index) => {
            let card = existingMap.get(String(phim.id));
            if (!card) {
                // Tạo card mới
                card = document.createElement('div');
                card.className = 'movie-card';
                card.draggable = true;
                card.dataset.id = phim.id;
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95) translateY(10px)';
                
                const trangThai = phim.trang_thai == 1 ? { text: 'Đang chiếu', bg: '#4CAF50', color: '#fff' } : { text: 'Sắp chiếu', bg: '#FFC107', color: '#000' };
                const theLoaiText = Array.isArray(phim.the_loai) ? phim.the_loai.map(tl => tl.the_loai?.ten).filter(Boolean).join(', ') : '';
                
                card.innerHTML = `
                    <img src="${urlMinio}/${phim.poster_url}" class="poster" alt="${phim.ten_phim}" loading="lazy">
                    <div>
                        <div class="font-semibold">${phim.ten_phim}</div>
                        <div>
                            <span class="inline-block px-2 py-0.5 rounded text-xs" style="background:${trangThai.bg};color:${trangThai.color}">
                                ${trangThai.text}
                            </span>
                            <span class="ml-2 text-xs text-gray-500">${phim.thoi_luong} phút</span>
                        </div>
                        <div class="text-xs text-gray-400">${theLoaiText}</div>
                    </div>
                `;
                
                // Gán drag event
                card.addEventListener('dragstart', function(e) {
                    e.dataTransfer.setData('phimId', this.dataset.id);
                });
                
                fragment.appendChild(card);
                // Animation fade in
                setTimeout(() => {
                    card.style.transition = 'opacity 0.3s, transform 0.3s';
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1) translateY(0)';
                }, index * 30);
            } else {
                // Cập nhật card hiện có
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
                existingMap.delete(String(phim.id));
            }
        });

        // Xóa các card không còn trong danh sách hoặc đã được phân phối
        existingMap.forEach((card, id) => {
            // Kiểm tra xem phim này có trong danh sách phân phối không
            if (phimDaPhanPhoiIds.has(id)) {
                // Phim đã được phân phối, xóa card
                card.style.transition = 'opacity 0.2s, transform 0.2s';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => card.remove(), 200);
            } else {
                // Phim không còn trong danh sách từ API, xóa card
                card.style.transition = 'opacity 0.2s, transform 0.2s';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => card.remove(), 200);
            }
        });

        // Thêm các card mới
        if (fragment.children.length > 0) {
            movieStockList.appendChild(fragment);
        }

        // Phân trang
        movieStockPage = data.pagination?.current_page || 1;
        movieStockTotalPages = data.pagination?.total_pages || 1;
        renderMovieStockPagination();
        if (spinner) Spinner.hide(spinner);
    }

    function renderMovieStockPagination() {
        let html = '';
        // Previous
        html += `<a href="#" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 ${movieStockPage === 1 ? 'pointer-events-none opacity-50' : ''}" data-page="${movieStockPage - 1}">
            <span class="sr-only">Previous</span>
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
        </a>`;

        // Trang đầu
        if (movieStockPage > 3) {
            html += `<a href="#" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold bg-white text-gray-700" data-page="1">1</a>`;
            if (movieStockPage > 4) html += `<span class="px-2">...</span>`;
        }

        // Các trang lân cận
        for (let i = Math.max(1, movieStockPage - 2); i <= Math.min(movieStockTotalPages, movieStockPage + 2); i++) {
            html += `<a href="#" aria-current="${movieStockPage === i ? 'page' : ''}" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold ${movieStockPage === i ? 'bg-red-600 text-white' : 'bg-white text-gray-700'} focus:z-20" data-page="${i}">${i}</a>`;
        }

        // Trang cuối
        if (movieStockPage < movieStockTotalPages - 2) {
            if (movieStockPage < movieStockTotalPages - 3) html += `<span class="px-2">...</span>`;
            html += `<a href="#" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold bg-white text-gray-700" data-page="${movieStockTotalPages}">${movieStockTotalPages}</a>`;
        }

        // Next
        html += `<a href="#" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 ${movieStockPage === movieStockTotalPages ? 'pointer-events-none opacity-50' : ''}" data-page="${movieStockPage + 1}">
            <span class="sr-only">Next</span>
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5-4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
        </a>`;
        movieStockPagination.innerHTML = html;

        // Sự kiện chuyển trang
        movieStockPagination.querySelectorAll('a[data-page]').forEach(a => {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.getAttribute('data-page'));
                if (page >= 1 && page <= movieStockTotalPages && page !== movieStockPage) {
                    loadMovieStockList(page, getMovieStockFilters());
                }
            });
        });
    }

    // Lấy filter từ input
    function getMovieStockFilters() {
        return {
            tuKhoaTimKiem: document.getElementById('search-movie').value.trim(),
            trangThai: document.getElementById('filter-movie-status').value,
            theLoaiId: document.getElementById('filter-movie-genre').value,
            doTuoi: document.getElementById('filter-movie-rating').value
        };
    }

    // Sự kiện filter/tìm kiếm
    document.getElementById('search-movie').addEventListener('input', () => loadMovieStockList(1, getMovieStockFilters()));
    document.getElementById('filter-movie-status').addEventListener('change', () => loadMovieStockList(1, getMovieStockFilters()));
    document.getElementById('filter-movie-genre').addEventListener('change', () => loadMovieStockList(1, getMovieStockFilters()));
    document.getElementById('filter-movie-rating').addEventListener('change', () => loadMovieStockList(1, getMovieStockFilters()));

    // Gọi lần đầu khi vào tab
    loadMovieStockList();

    function getTrangThaiPhim(phim) {
        if (phim.trang_thai == 1) {
            return { text: 'Đang chiếu', color: '#4CAF50', textColor: '#fff' };
        }
        if (phim.trang_thai == 0) {
            return { text: 'Sắp chiếu', color: '#FFC107', textColor: '#000' };
        }
        // -1 hoặc giá trị khác
        return { text: 'Ngừng chiếu', color: '#9E9E9E', textColor: '#fff' };
    }
});
import Spinner from "./util/spinner.js";
document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const addMovieBtn = document.getElementById('btn-add-movie');
    const addMovieModal = document.getElementById('add-movie-modal');
    const addMovieForm = document.getElementById('add-movie-form');
    // Declared but will be used in future implementations
    const editMovieModal = document.getElementById('edit-movie-modal');
    const editMovieForm = document.getElementById('edit-movie-form');
    // Movie list element
    let movieList = document.getElementById('movie-list');
    let paginationInfo = {
        total: 0,
        total_pages: 1,
        current_page: 1,
        page_size: 10
    };
    let moviesCache = [];
    // Modal functions
    function openModal(modalElement) {
        document.body.classList.add('modal-active');
        modalElement.classList.remove('opacity-0', 'pointer-events-none');
    }
    
    function closeModal() {
        document.body.classList.remove('modal-active');
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.add('opacity-0', 'pointer-events-none');
        });
    }
    
    // Add movie button click event
    if (addMovieBtn) {
        addMovieBtn.addEventListener('click', function() {
            renderGenreCheckboxes(); // Luôn gọi API lấy thể loại
            if (addMovieForm) addMovieForm.reset();
            document.querySelectorAll('.text-red-500.text-xs.italic').forEach(errorMsg => errorMsg.classList.add('hidden'));
            openModal(addMovieModal);
        });
    }
    
    // Close buttons click events
    document.querySelectorAll('.modal-close-btn, .modal-close, .modal-overlay').forEach(closeBtn => {
        closeBtn.addEventListener('click', closeModal);
    });
    
    // Close modal when clicking outside the modal content
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    });
    
    // Prevent closing when clicking inside the modal content
    document.querySelectorAll('.modal-container').forEach(container => {
        container.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Hàm lấy giá trị từ checkbox thể loại
    function getSelectedGenres(prefix = '') {
        const selectedGenres = [];
        document.querySelectorAll(`input[name="${prefix}movie-genres[]"]:checked`).forEach(checkbox => {
            selectedGenres.push(checkbox.value);
        });
        return selectedGenres;
    }

    // Hàm kiểm tra URL YouTube hợp lệ
    function isValidYoutubeUrl(url) {
        const pattern = /^(https?:\/\/)?(www\.)?youtube\.com\/watch\?v=.+$/;
        return pattern.test(url);
    }

    // Add movie form submission
    if (addMovieForm) {
        addMovieForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Kiểm tra validation
            let isValid = true;
            
            // Kiểm tra tên phim
            const movieTitle = document.getElementById('movie-title').value.trim();
            if (!movieTitle) {
                document.getElementById('movie-title-error').classList.remove('hidden');
                isValid = false;
            } else {
                document.getElementById('movie-title-error').classList.add('hidden');
            }
            
            // Kiểm tra đạo diễn
            const movieDirector = document.getElementById('movie-director').value.trim();
            if (!movieDirector) {
                document.getElementById('movie-director-error').classList.remove('hidden');
                isValid = false;
            } else {
                document.getElementById('movie-director-error').classList.add('hidden');
            }
            
            // Kiểm tra diễn viên
            const movieActors = document.getElementById('movie-actors').value.trim();
            if (!movieActors) {
                document.getElementById('movie-actors-error').classList.remove('hidden');
                isValid = false;
            } else {
                document.getElementById('movie-actors-error').classList.add('hidden');
            }
            
            // Kiểm tra thể loại
            const selectedGenres = getSelectedGenres();
            if (selectedGenres.length === 0) {
                document.getElementById('movie-genres-error').classList.remove('hidden');
                isValid = false;
            } else {
                document.getElementById('movie-genres-error').classList.add('hidden');
            }
            
            // Kiểm tra thời lượng
            const movieDuration = document.getElementById('movie-duration').value.trim();
            if (!movieDuration || isNaN(movieDuration) || parseInt(movieDuration) <= 0) {
                document.getElementById('movie-duration-error').classList.remove('hidden');
                isValid = false;
            } else {
                document.getElementById('movie-duration-error').classList.add('hidden');
            }
            
            // Kiểm tra phân loại
            const movieRating = document.getElementById('movie-rating').value;
            if (!movieRating) {
                document.getElementById('movie-rating-error').classList.remove('hidden');
                isValid = false;
            } else {
                document.getElementById('movie-rating-error').classList.add('hidden');
            }
            
            // Kiểm tra poster
            const moviePoster = document.getElementById('movie-poster').files[0];
            if (!moviePoster) {
                document.getElementById('movie-poster-error').classList.remove('hidden');
                isValid = false;
            } else {
                document.getElementById('movie-poster-error').classList.add('hidden');
            }
            
            // Kiểm tra mô tả
            const movieDescription = document.getElementById('movie-description').value.trim();
            if (!movieDescription) {
                document.getElementById('movie-description-error').classList.remove('hidden');
                isValid = false;
            } else {
                document.getElementById('movie-description-error').classList.add('hidden');
            }
            
            // Thêm vào phần kiểm tra validation trong addMovieForm.addEventListener('submit', ...)
            const movieReleaseDate = document.getElementById('movie-release-date').value.trim();
            if (!movieReleaseDate) {
                document.getElementById('movie-release-date-error').classList.remove('hidden');
                isValid = false;
            } else {
                document.getElementById('movie-release-date-error').classList.add('hidden');
            }

            const movieCountry = document.getElementById('movie-country').value.trim();
            if (!movieCountry) {
                document.getElementById('movie-country-error').classList.remove('hidden');
                isValid = false;
            } else {
                document.getElementById('movie-country-error').classList.add('hidden');
            }
            
            // Nếu form không hợp lệ, dừng lại
            if (!isValid) {
                return;
            }
            
            // Tạo FormData để gửi lên server
            const formData = new FormData();
            formData.append('ten', movieTitle);
            formData.append('dao_dien', movieDirector);
            formData.append('dien_vien', movieActors);
            formData.append('thoi_luong', movieDuration);
            formData.append('do_tuoi', movieRating);
            formData.append('mo_ta', movieDescription);
            
            // Thêm trailer nếu có
            const movieTrailer = document.getElementById('movie-trailer').value.trim();
            if (movieTrailer) {
                formData.append('trailer_url', movieTrailer);
            }

            // Thêm video nếu có
            const movieVideo = document.getElementById('movie-video').files[0];
            if (movieVideo) {
                formData.append('video', movieVideo);
            }
            
            // Thêm poster
            formData.append('poster', moviePoster);
            
            // Thêm thể loại
            selectedGenres.forEach((genreId, index) => {
                formData.append(`the_loai_ids[${index}]`, genreId);
            });
            
            // Thêm ngày công chiếu - sử dụng ngày hiện tại cho demo
            const today = new Date();
            const dateString = today.toISOString().split('T')[0]; // Format YYYY-MM-DD
            formData.append('ngay_cong_chieu', movieReleaseDate);
            formData.append('quoc_gia', movieCountry);
            
            // Hiển thị spinner
            const spinner = Spinner.show({
                target: addMovieModal,
                text: 'Đang thêm phim...'
            });
            
            // Gửi request POST lên API
            fetch(`${movieList.dataset.url}/api/phim`, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Ẩn spinner
                Spinner.hide(spinner);
                
                if (data.success) {
                    // Đóng modal
                    closeModal();
                    
                    // Hiển thị thông báo thành công
                    showToast('Thêm phim thành công', false);
                    
                    // Tải lại danh sách phim
                    loadMovies(); // Thêm hàm này sau nếu cần
                    
                    // Reset form
                    addMovieForm.reset();
                    
                    // Ẩn phần xem trước trailer nếu có
                    document.getElementById('movie-trailer-preview').classList.add('hidden');
                } else {
                    // Hiển thị thông báo lỗi
                    showToast(data.message || 'Thêm phim thất bại', true);
                    console.error('Error:', data.message);
                }
            })
            .catch(error => {
                // Ẩn spinner
                Spinner.hide(spinner);
                
                // Hiển thị thông báo lỗi
                console.error('Error:', error);
                showToast('Lỗi khi thêm phim: ' + error.message, true);
                console.error('Error:', error.message);
            });
        });
    }

    // Xử lý sự kiện paste cho ô nhập trailer
    const trailerInput = document.getElementById('movie-trailer');
    const editTrailerInput = document.getElementById('edit-movie-trailer');
    
    if (trailerInput) {
        trailerInput.addEventListener('paste', function(event) {
            // Sử dụng setTimeout để đảm bảo giá trị đã được dán vào input
            setTimeout(() => {
                const url = this.value.trim();
                handleYouTubeUrl(url, '');
            }, 100);
        });
    }
    
    if (editTrailerInput) {
        editTrailerInput.addEventListener('paste', function(event) {
            // Sử dụng setTimeout để đảm bảo giá trị đã được dán vào input
            setTimeout(() => {
                const url = this.value.trim();
                handleYouTubeUrl(url, 'edit-');
            }, 100);
        });
        editTrailerInput.addEventListener('input', function() {
            const url = this.value.trim();
            handleYouTubeUrl(url, 'edit-');
        });
    }
    
    // Xử lý input change để bắt URL nhập vào
    if (trailerInput) {
        trailerInput.addEventListener('input', function() {
            const url = this.value.trim();
            handleYouTubeUrl(url, '');
        });
    }
    
    // Hàm xử lý URL YouTube
    function handleYouTubeUrl(url, prefix) {
        if (isValidYoutubeUrl(url)) {
            const videoId = getYouTubeVideoId(url);
            if (videoId) {
                getYouTubeVideoInfo(videoId, prefix);
            }
        } else {
            // Ẩn phần xem trước nếu URL không hợp lệ
            document.getElementById(`${prefix}movie-trailer-preview`).classList.add('hidden');
        }
    }
    
    // Xóa hàm pasteFromClipboard không cần thiết nữa
    
    // Các hàm khác giữ nguyên
    function getYouTubeVideoId(url) {
        const regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/;
        const match = url.match(regExp);
        return (match && match[7].length === 11) ? match[7] : false;
    }
    
    // Hàm lấy thông tin video từ YouTube
    function getYouTubeVideoInfo(videoId, prefix) {
        // URL cho thumbnail chất lượng cao
        const thumbnailUrl = `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
        
        // Cập nhật thumbnail
        document.getElementById(`${prefix}movie-trailer-thumbnail`).src = thumbnailUrl;
        
        // Hiển thị container xem trước
        document.getElementById(`${prefix}movie-trailer-preview`).classList.remove('hidden');
        
        // Lấy thông tin metadata của video từ API noembed
        fetch(`https://noembed.com/embed?url=https://www.youtube.com/watch?v=${videoId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById(`${prefix}movie-trailer-title`).textContent = data.title || 'Video YouTube';
                document.getElementById(`${prefix}movie-trailer-channel`).textContent = data.author_name || 'YouTube Channel';
            })
            .catch(error => {
                console.error('Error fetching video info:', error);
                document.getElementById(`${prefix}movie-trailer-title`).textContent = 'Video YouTube';
                document.getElementById(`${prefix}movie-trailer-channel`).textContent = 'Không thể tải thông tin video';
            });
        
        // Thêm sự kiện click vào container thumbnail
        document.getElementById(`${prefix}movie-trailer-thumbnail-container`).onclick = function() {
            openVideoModal(videoId);
        };
    }
    
    // Mở modal xem video YouTube
    function openVideoModal(videoId) {
        // Cập nhật iframe với video id
        document.getElementById('youtube-iframe').src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
        
        // Mở modal
        openModal(document.getElementById('video-modal'));
    }
    
    // Cập nhật sự kiện đóng modal video
    document.querySelectorAll('#video-modal .modal-close, #video-modal .modal-overlay').forEach(element => {
        element.addEventListener('click', function() {
            // Dừng video khi đóng modal
            document.getElementById('youtube-iframe').src = '';
            closeModal();
        });
    });
    
    // Thay thế đoạn CSS ở cuối file phim.js
    const videoModalStyle = document.createElement('style');
    videoModalStyle.textContent = `
        /* Modal container styles */
        #video-modal {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #video-modal .modal-container {
            width: 85%;
            max-width: 1000px;
            max-height: 80vh;
            background-color: black;
            border-radius: 8px;
            overflow: hidden;
        }
        
        /* Modal content styles */
        #video-modal .modal-content {
            padding: 12px;
            height: auto;
        }
        
        /* Title and close button area */
        #video-modal .flex.justify-between {
            margin-bottom: 8px;
        }
        
        /* Video container with proper aspect ratio */
        .aspect-w-16 {
            position: relative;
            padding-bottom: 56.25%; /* Tỷ lệ 16:9 */
            height: 0;
            overflow: hidden;
        }
        
        .aspect-w-16 iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            max-height: calc(80vh - 80px); /* Trừ đi phần header */
        }
        
        /* Đảm bảo iframe không vượt quá kích thước màn hình */
        #youtube-iframe {
            max-height: calc(80vh - 80px);
        }
        
        /* Responsive adjustments */
        @media (max-height: 600px) {
            #video-modal .modal-container {
                max-height: 90vh;
            }
            
            #video-modal .modal-content {
                padding: 8px;
            }
            
            .aspect-w-16 iframe, #youtube-iframe {
                max-height: calc(90vh - 60px);
            }
        }
    `;
    document.head.appendChild(videoModalStyle);
    
    // Hàm xử lý ảnh lỗi
    function handleImageErrors() {
        // Áp dụng cho tất cả ảnh poster phim
        document.querySelectorAll('.movie-poster-img').forEach(img => {
            img.onerror = function() {
                this.src = `${movieList.dataset.url}/img/placeholder-poster.jpg`;
                this.onerror = null; // Tránh vòng lặp vô hạn nếu placeholder cũng lỗi
            };
        });
        
        // Áp dụng cho ảnh xem trước trailer
        document.querySelectorAll('#movie-trailer-thumbnail, #edit-movie-trailer-thumbnail').forEach(img => {
            img.onerror = function() {
                this.src = `${movieList.dataset.url}/img/video-placeholder.jpg`;
                this.onerror = null;
            };
        });
    }

    // Gọi hàm này sau khi tải danh sách phim
    handleImageErrors();

    // Thêm gọi hàm này trong hàm renderMovies nếu có
    function renderMovies(movies) {
        moviesCache = movies; // Lưu vào cache
    if (!movies || movies.length === 0) {
        movieList.innerHTML = `<tr><td colspan="4" class="text-center text-gray-500 py-4">Không có phim nào</td></tr>`;
        return;
    }
    movieList.innerHTML = movies.map(movie => {
        const theLoaiNames = Array.isArray(movie.the_loai)
            ? movie.the_loai.map(tl => tl.the_loai && tl.the_loai.ten ? tl.the_loai.ten : '').filter(Boolean).join(', ')
            : '';
        return `
            <tr class="movie-item cursor-pointer hover:bg-gray-50" data-id="${movie.id}">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-20 w-14">
                            <img class="h-20 w-14 rounded-sm object-cover movie-poster-img" src="${movieList.dataset.urlminio + '/' + movie.poster_url}" alt="Poster phim">
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${movie.ten_phim}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${theLoaiNames}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${movie.thoi_luong} phút</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${movie.trang_thai == 1 ? 'status-now' : (movie.trang_thai == 0 ? 'status-stopped' : 'status-coming')}">
                        ${movie.trang_thai == 1 ? 'Đang chiếu' : (movie.trang_thai == 0 ? 'Ngừng chiếu' : 'Sắp chiếu')}
                    </span>
                </td>
            </tr>
        `;
    }).join('');
    handleImageErrors();

    // Gắn sự kiện click cho từng dòng phim
    document.querySelectorAll('.movie-item').forEach(row => {
        row.addEventListener('click', function() {
            const movieId = this.getAttribute('data-id');
            openEditMovieModal(movieId);
        });
    });
}

function openEditMovieModal(movieId) {
    const movie = moviesCache.find(m => m.id == movieId);
    if (!movie) {
        showToast('Không tìm thấy thông tin phim', true);
        return;
    }
    const selectedGenreIds = Array.isArray(movie.the_loai)
        ? movie.the_loai.map(tl => tl.theloai_id.toString())
        : [];
    renderEditGenreCheckboxes(selectedGenreIds);

    // Điền thông tin vào form (các trường khác giữ nguyên)
    document.getElementById('edit-movie-id').value = movie.id || '';
    document.getElementById('edit-movie-title').value = movie.ten_phim || '';
    document.getElementById('edit-movie-director').value = movie.dao_dien || '';
    document.getElementById('edit-movie-actors').value = movie.dien_vien || '';
    document.getElementById('edit-movie-duration').value = movie.thoi_luong || '';
    document.getElementById('edit-movie-rating').value = movie.do_tuoi || '';
    document.getElementById('edit-movie-status').value = movie.trang_thai || '';
    document.getElementById('edit-movie-description').value = movie.mo_ta || '';
    document.getElementById('edit-movie-trailer').value = movie.trailer_url || '';
    document.getElementById('edit-movie-release-date').value = movie.ngay_cong_chieu || '';
    document.getElementById('edit-movie-country').value = movie.quoc_gia || '';

    // Hiển thị poster hiện tại
    document.getElementById('current-poster-img').src = movieList.dataset.urlminio + '/' + movie.poster_url;

    // Hiển thị preview trailer nếu có
    if (movie.trailer_url && isValidYoutubeUrl(movie.trailer_url)) {
        handleYouTubeUrl(movie.trailer_url, 'edit-');
    } else {
        document.getElementById('edit-movie-trailer-preview').classList.add('hidden');
    }

    // Hiển thị modal chỉnh sửa
    openModal(editMovieModal);
}
    // Hàm cập nhật phân trang
    function updatePagination() {
        const paginationContainer = document.querySelector('.mt-4.flex.items-center.justify-between.border-t');
        if (!paginationContainer) return;

        // Hiển thị thông tin số phim
        paginationContainer.querySelector('p.text-sm.text-gray-700').innerHTML = `
            Showing
            <span class="font-medium">${(paginationInfo.current_page - 1) * paginationInfo.page_size + 1}</span>
            to
            <span class="font-medium">${Math.min(paginationInfo.current_page * paginationInfo.page_size, paginationInfo.total)}</span>
            of
            <span class="font-medium">${paginationInfo.total}</span>
            results
        `;

        // Hiển thị nút trang
        const nav = paginationContainer.querySelector('nav[aria-label="Pagination"]');
        if (nav) {
            let html = '';
            // Previous
            html += `<a href="#" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 ${paginationInfo.current_page === 1 ? 'pointer-events-none opacity-50' : ''}" data-page="${paginationInfo.current_page - 1}">
                <span class="sr-only">Previous</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
            </a>`;
            // Các trang
            for (let i = 1; i <= paginationInfo.total_pages; i++) {
                html += `<a href="#" aria-current="${paginationInfo.current_page === i ? 'page' : ''}" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold ${paginationInfo.current_page === i ? 'bg-red-600 text-white' : 'bg-white text-gray-700'} focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600" data-page="${i}">${i}</a>`;
            }
            // Next
            html += `<a href="#" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 ${paginationInfo.current_page === paginationInfo.total_pages ? 'pointer-events-none opacity-50' : ''}" data-page="${paginationInfo.current_page + 1}">
                <span class="sr-only">Next</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
            </a>`;
            nav.innerHTML = html;

            // Gắn sự kiện chuyển trang
            nav.querySelectorAll('a[data-page]').forEach(a => {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = parseInt(this.getAttribute('data-page'));
                    if (page >= 1 && page <= paginationInfo.total_pages && page !== paginationInfo.current_page) {
                        loadMovies({ page });
                    }
                });
            });
        }
    }

    // Sửa hàm loadMovies để nhận và cập nhật phân trang
    function loadMovies(params = {}) {
        Spinner.hide();
        movieList.innerHTML = `
            <tr>
                <td colspan="4" class="py-8">
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <div class="epic-spinner" style="
                            width: 32px;
                            height: 32px;
                            border: 4px solid rgba(0,0,0,0.1);
                            border-radius: 50%;
                            border-top: 4px solid #E11D48;
                            animation: epic-spin 1s linear infinite;
                            margin-bottom: 12px;
                        "></div>
                        <span style="color: #374151; font-size: 15px;">Đang tải phim...</span>
                    </div>
                </td>
            </tr>
        `;
        if (!document.getElementById('epic-spinner-style')) {
            const styleElement = document.createElement('style');
            styleElement.id = 'epic-spinner-style';
            styleElement.textContent = `
                @keyframes epic-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(styleElement);
        }
        // Thêm page vào params nếu chưa có
        if (!params.page) params.page = paginationInfo.current_page || 1;

        const query = new URLSearchParams(params).toString();
        const url = `${movieList.dataset.url}/api/phim/${query ? '?' + query : ''}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                Spinner.hide();
                if (data.success) {
                    renderMovies(data.data);
                    // Cập nhật thông tin phân trang
                    if (data.pagination) {
                        paginationInfo.total = data.pagination.total;
                        paginationInfo.total_pages = data.pagination.total_pages;
                        paginationInfo.current_page = data.pagination.current_page;
                        paginationInfo.page_size = Math.ceil(data.pagination.total / data.pagination.total_pages) || 10;
                    }
                    updatePagination();
                } else {
                    movieList.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 py-4">${data.message || 'Không thể tải danh sách phim'}</td></tr>`;
                    paginationInfo.total = 0;
                    paginationInfo.total_pages = 1;
                    paginationInfo.current_page = 1;
                    updatePagination();
                }
            })
            .catch(error => {
                Spinner.hide();
                movieList.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 py-4">Lỗi khi tải phim</td></tr>`;
                paginationInfo.total = 0;
                paginationInfo.total_pages = 1;
                paginationInfo.current_page = 1;
                updatePagination();
                console.error(error);
            });
    }

    // Khi trang vừa load, gọi loadMovies để hiển thị danh sách phim
    loadMovies();

    // Khi tìm kiếm/lọc, luôn reset về trang 1
    document.getElementById('search').addEventListener('input', function() {
        const tuKhoaTimKiem = this.value.trim();
        loadMovies({ tuKhoaTimKiem, page: 1 });
    });
    document.getElementById('filter-status').addEventListener('change', function() {
        const trangThai = this.value;
        loadMovies({ trangThai, page: 1 });
    });
    document.getElementById('filter-genre').addEventListener('change', function() {
        const theLoaiId = this.value;
        loadMovies({ theLoaiId, page: 1 });
    });
    
    // Hàm hiển thị thông báo (toast)
    function showToast(message, isError = false) {
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'fixed bottom-4 right-4 z-50';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `p-4 mb-3 rounded-md shadow-md transform transition-transform duration-300 ease-in-out ${isError ? 'bg-red-500' : 'bg-green-500'} text-white`;
        toast.innerHTML = `
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${isError 
                        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>' 
                        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'}
                </svg>
                <span>${message}</span>
            </div>
        `;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }

    function renderGenreCheckboxes(selectedIds = []) {
        fetch(`${movieList.dataset.url}/api/the-loai-phim`)
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                    const genres = data.data;
                    const container = document.getElementById('movie-genres-container');
                    container.innerHTML = '';
                    genres.forEach(genre => {
                        container.innerHTML += `
                            <div class="flex items-center mb-2">
                                <input id="genre-${genre.id}" type="checkbox" name="movie-genres[]" value="${genre.id}" class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500" ${selectedIds.includes(genre.id.toString()) ? 'checked' : ''}>
                                <label for="genre-${genre.id}" class="ml-2 text-sm font-medium text-gray-700">${genre.ten}</label>
                            </div>
                        `;
                    });
                }
            });
    }
    // Gọi hàm này khi mở modal thêm phim
    if (addMovieBtn) {
        addMovieBtn.addEventListener('click', function() {
            renderGenreCheckboxes(); // Luôn gọi API lấy thể loại
            if (addMovieForm) addMovieForm.reset();
            document.querySelectorAll('.text-red-500.text-xs.italic').forEach(errorMsg => errorMsg.classList.add('hidden'));
            openModal(addMovieModal);
        });
    }

    // Edit movie form submission
    if (editMovieForm) {
        editMovieForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Kiểm tra validation (tương tự như form thêm phim)
            let isValid = true;
            const movieId = document.getElementById('edit-movie-id').value;
            const movieTitle = document.getElementById('edit-movie-title').value.trim();
            const movieDirector = document.getElementById('edit-movie-director').value.trim();
            const movieActors = document.getElementById('edit-movie-actors').value.trim();
            const selectedGenres = getSelectedGenres('edit-');
            const movieDuration = document.getElementById('edit-movie-duration').value.trim();
            const movieRating = document.getElementById('edit-movie-rating').value;
            const movieStatus = document.getElementById('edit-movie-status').value;
            const moviePoster = document.getElementById('edit-movie-poster').files[0];
            const movieDescription = document.getElementById('edit-movie-description').value.trim();
            const movieReleaseDate = document.getElementById('edit-movie-release-date').value.trim();
            const movieCountry = document.getElementById('edit-movie-country').value.trim();
            const movieTrailer = document.getElementById('edit-movie-trailer').value.trim();
            const movieVideo = document.getElementById('edit-movie-video').files[0];

            // Bạn có thể thêm các kiểm tra lỗi tương tự như form thêm phim ở đây nếu muốn

            // Nếu form không hợp lệ, dừng lại
            // if (!isValid) return;

            // Tạo FormData để gửi lên server
            const formData = new FormData();
            formData.append('ten', movieTitle);
            formData.append('dao_dien', movieDirector);
            formData.append('dien_vien', movieActors);
            formData.append('thoi_luong', movieDuration);
            formData.append('do_tuoi', movieRating);
            formData.append('mo_ta', movieDescription);
            formData.append('trang_thai', movieStatus);
            formData.append('ngay_cong_chieu', movieReleaseDate);
            formData.append('quoc_gia', movieCountry);
            if (movieTrailer) {
                formData.append('trailer_url', movieTrailer);
            }
            // Thêm poster nếu có file mới
            if (moviePoster) {
                formData.append('poster', moviePoster);
            }
            if (movieVideo) {
                formData.append('video', movieVideo);
            }
            // Thêm thể loại
            selectedGenres.forEach((genreId, index) => {
                formData.append(`the_loai_ids[${index}]`, genreId);
            });

            // Hiển thị spinner
            const spinner = Spinner.show({
                target: editMovieModal,
                text: 'Đang cập nhật phim...'
            });

            // Gửi request POST lên API sửa phim
            fetch(`${movieList.dataset.url}/api/phim/${movieId}`, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                Spinner.hide(spinner);
                if (data.success) {
                    closeModal();
                    showToast('Cập nhật phim thành công', false);
                    loadMovies(); // Tải lại danh sách phim
                } else {
                    showToast(data.message || 'Cập nhật phim thất bại', true);
                }
            })
            .catch(error => {
                Spinner.hide(spinner);
                showToast('Lỗi khi cập nhật phim: ' + error.message, true);
                console.error('Error:', error.message);
            });
        });
    }

    function renderEditGenreCheckboxes(selectedIds = []) {
        fetch(`${movieList.dataset.url}/api/the-loai-phim`)
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                    const genres = data.data;
                    const container = document.getElementById('edit-movie-genres-container');
                    if (!container) return;
                    container.innerHTML = '';
                    genres.forEach(genre => {
                        container.innerHTML += `
                            <div class="flex items-center mb-2">
                                <input id="edit-genre-${genre.id}" type="checkbox" name="edit-movie-genres[]" value="${genre.id}" class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500" ${selectedIds.includes(genre.id.toString()) ? 'checked' : ''}>
                                <label for="edit-genre-${genre.id}" class="ml-2 text-sm font-medium text-gray-700">${genre.ten}</label>
                            </div>
                        `;
                    });
                }
            });
    }
});   // Implement YouTube API call here if needed
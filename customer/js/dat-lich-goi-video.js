document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = document.getElementById('current-month').dataset.url;
    let currentMonth = new Date();
    let selectedDate = null;
    
    // Kết nối Socket.IO để nhận thông báo real-time
    const socket = io('http://localhost:3000/video');
    
    // Lắng nghe sự kiện khi nhân viên chọn tư vấn
    socket.on('lichgoivideo:dachon', (data) => {
        console.log('Nhân viên đã chọn tư vấn:', data);
        
        // Reload lại danh sách nếu đang xem ngày đó
        if (selectedDate) {
            fetchVideoCallsByDate(selectedDate);
        }
        
        // Hiển thị thông báo
        showSuccessToast('Nhân viên đã xác nhận lịch tư vấn của bạn!');
    });
    
    // Khởi tạo trang
    updateCalendar();
    loadCinemas();
    checkInitialSchedule(); // Kiểm tra lịch cho ngày hiện tại khi load trang
    
    // Event listeners
    document.getElementById('prev-month').addEventListener('click', () => {
        currentMonth.setMonth(currentMonth.getMonth() - 1);
        updateCalendar();
    });
    
    document.getElementById('next-month').addEventListener('click', () => {
        currentMonth.setMonth(currentMonth.getMonth() + 1);
        updateCalendar();
    });
    
    document.getElementById('booking-form').addEventListener('submit', handleFormSubmit);
    
    document.getElementById('close-modal').addEventListener('click', () => {
        document.getElementById('booking-modal').classList.add('hidden');
        document.getElementById('booking-modal').classList.remove('flex');
    });
    
    document.getElementById('close-confirmation').addEventListener('click', () => {
        document.getElementById('confirmation-modal').classList.add('hidden');
        document.getElementById('confirmation-modal').classList.remove('flex');
    });
    
    // Thêm event listener cho nút đặt lịch mới
    document.getElementById('book-appointment-btn').addEventListener('click', () => {
        if (selectedDate) {
            showBookingModal(selectedDate);
        } else {
            showErrorToast('Vui lòng chọn ngày trước khi đặt lịch');
        }
    });
    
    // Kiểm tra login
    function checkLogin(callback) {
        const userid = document.getElementById('userid').value;
        if (!userid) {
            callback(false, null);
            return;
        }
        callback(true, { id: userid });
    }
    
    // Kiểm tra và hiển thị lịch theo ngày khi trang load
    function checkInitialSchedule() {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const formattedToday = formatDateForApi(today);
        selectedDate = formattedToday;
        fetchVideoCallsByDate(formattedToday);
        
        // Hiển thị ngày hiện tại trong khu vực lịch tư vấn
        document.getElementById('selected-date-display').textContent = formatDateDisplay(formattedToday);
        
        // Hiển thị nút đặt lịch
        document.getElementById('book-appointment-btn').classList.remove('hidden');
    }
    
    // Load danh sách rạp
    function loadCinemas() {
        fetch(`${baseUrl}/api/rap-phim-khach`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderCinemas(data.data);
                } else {
                    // Dữ liệu giả nếu API lỗi
                    const cinemaData = [
                    ];
                    renderCinemas(cinemaData);
                }
            })
            .catch(error => {
                console.error('Lỗi khi tải danh sách rạp:', error);
                
                // Dữ liệu giả nếu API lỗi
                const cinemaData = [
                    { id: 1, ten_rap: 'EPIC Cinema - Cầu Giấy' },
                    { id: 2, ten_rap: 'EPIC Cinema - Royal City' },
                    { id: 3, ten_rap: 'EPIC Cinema - Times City' },
                    { id: 4, ten_rap: 'EPIC Cinema - Hà Đông' }
                ];
                renderCinemas(cinemaData);
            });
    }
    
    // Render danh sách rạp
    function renderCinemas(cinemaData) {
        const cinemaSelect = document.getElementById('cinema-select');
        const options = cinemaData.map(cinema => 
            `<option value="${cinema.id}">${cinema.ten}</option>`
        ).join('');
        
        cinemaSelect.innerHTML = '<option value="">Chọn rạp chiếu phim</option>' + options;
    }
    
    // Cập nhật calendar
    function updateCalendar() {
        const firstDayOfMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
        const lastDayOfMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0);
        
        // Cập nhật tiêu đề tháng
        const monthNames = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 
                           'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
        document.getElementById('current-month').textContent = `${monthNames[currentMonth.getMonth()]} ${currentMonth.getFullYear()}`;
        
        // Tính toán số ngày để hiển thị đầy đủ lịch
        let firstDay = firstDayOfMonth.getDay(); // 0 = Chủ Nhật, 1 = Thứ 2
        firstDay = firstDay === 0 ? 7 : firstDay; // Đổi Chủ Nhật thành 7 để phù hợp với thứ tự Thứ 2 - Chủ Nhật
        
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        let calendarHTML = '';
        
        // Thêm các ô trống trước ngày đầu tiên của tháng
        for (let i = 1; i < firstDay; i++) {
            calendarHTML += '<div class="border border-gray-200 p-2 h-24 bg-gray-50"></div>';
        }
        
        // Thêm các ngày trong tháng
        for (let day = 1; day <= lastDayOfMonth.getDate(); day++) {
            const date = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), day);
            const isPast = date < today;
            const isToday = date.toDateString() === today.toDateString();
            
            let dayClass = 'border p-2 h-24 transition-all duration-200';
            let dayContent = `<span class="text-sm font-medium ${isToday ? 'text-red-600' : ''}">${day}</span>`;
            
            if (isPast) {
                dayClass += ' bg-gray-100 text-gray-400 cursor-not-allowed';
            } else if (isToday) {
                dayClass += ' bg-red-50 border-red-200 hover:bg-red-100 cursor-pointer';
            } else {
                dayClass += ' bg-white hover:bg-blue-50 hover:border-blue-300 cursor-pointer';
            }
            
            calendarHTML += `<div class="${dayClass}" data-date="${formatDateForApi(date)}">${dayContent}</div>`;
        }
        
        document.getElementById('calendar-grid').innerHTML = calendarHTML;
        
        // Add event listeners to available days
        document.querySelectorAll('#calendar-grid div:not(.bg-gray-100)').forEach(dayElement => {
            if (!dayElement.classList.contains('cursor-not-allowed')) {
                dayElement.addEventListener('click', () => selectDate(dayElement));
            }
        });
    }
    
    // Chọn ngày
    function selectDate(dayElement) {
        if (!dayElement.dataset.date) return;
        
        selectedDate = dayElement.dataset.date;
        
        // Chỉ tải danh sách cuộc gọi video đã đặt cho ngày này, không hiển thị modal
        fetchVideoCallsByDate(selectedDate);
        
        // Hiển thị ngày đã chọn trong khu vực lịch tư vấn
        document.getElementById('selected-date-display').textContent = formatDateDisplay(selectedDate);
        
        // Hiển thị nút đặt lịch
        document.getElementById('book-appointment-btn').classList.remove('hidden');
    }
    
    // Lấy danh sách cuộc gọi video đã đặt theo ngày
    function fetchVideoCallsByDate(dateString) {
        checkLogin((isLoggedIn, user) => {
            if (!isLoggedIn) {
                const container = document.getElementById('scheduled-calls');
                if (container) {
                    container.innerHTML = '<p class="text-yellow-600 text-center py-4">Vui lòng đăng nhập để xem lịch tư vấn đã đặt</p>';
                }
                return;
            }
            
            fetch(`${baseUrl}/api/lich-goi-video-theo-ngay?ngay=${dateString}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderScheduledVideoCalls(data.data);
                    } else {
                        console.error('Không thể tải lịch gọi video:', data.message);
                        const container = document.getElementById('scheduled-calls');
                        if (container) {
                            container.innerHTML = '<p class="text-red-500 text-center py-4">Không thể tải lịch tư vấn</p>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Lỗi khi tải lịch gọi video:', error);
                    const container = document.getElementById('scheduled-calls');
                    if (container) {
                        container.innerHTML = '<p class="text-red-500 text-center py-4">Lỗi khi tải lịch tư vấn</p>';
                    }
                });
        });
    }
    
    // Hiển thị danh sách cuộc gọi video đã đặt
    function renderScheduledVideoCalls(calls) {
        const container = document.getElementById('scheduled-calls');
        
        if (!container) return;
        
        if (!calls || calls.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center py-4">Không có cuộc hẹn tư vấn nào vào ngày này</p>';
            return;
        }
        
        const callsHTML = calls.map(call => {
            // Xác định màu badge theo trạng thái
            let badgeClass = 'bg-blue-100 text-blue-800';
            if (call.trang_thai === 'Đã xác nhận') {
                badgeClass = 'bg-green-100 text-green-800';
            } else if (call.trang_thai === 'Đang gọi') {
                badgeClass = 'bg-red-100 text-red-800';
            } else if (call.trang_thai === 'Hoàn thành') {
                badgeClass = 'bg-gray-100 text-gray-800';
            }
            
            // Hiển thị nút "Tham gia" nếu trạng thái là "Đã xác nhận" hoặc "Đang gọi"
            let actionButton = '';
            if ((call.trang_thai === 'Đã xác nhận' || call.trang_thai === 'Đang gọi') && call.room_id) {
                actionButton = `
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <a href="${baseUrl}/video-call?room=${call.room_id}" 
                           class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Tham gia cuộc gọi
                        </a>
                    </div>
                `;
            }
            
            return `
                <div class="border border-gray-200 rounded-lg p-4 mb-3 bg-white hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-sm font-medium text-gray-900">Giờ: ${call.gio}</span>
                            <p class="text-sm text-gray-600 mt-1">${call.ten_rap}</p>
                        </div>
                        <div class="${badgeClass} text-xs font-semibold px-2.5 py-0.5 rounded-full">
                            ${call.trang_thai || 'Chờ xác nhận'}
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">
                        <span class="font-medium">Nội dung:</span> ${call.noi_dung || 'Không có mô tả'}
                    </p>
                    ${call.nhan_vien ? `
                        <p class="mt-1 text-sm text-gray-600">
                            <span class="font-medium">Nhân viên:</span> ${call.nhan_vien}
                        </p>
                    ` : ''}
                    ${actionButton}
                </div>
            `;
        }).join('');
        
        container.innerHTML = callsHTML;
    }
    
    // Hiển thị modal đặt lịch
    function showBookingModal(dateString) {
        checkLogin((isLoggedIn, user) => {
            if (!isLoggedIn) {
                showErrorToast('Vui lòng đăng nhập để đặt lịch');
                // Redirect to login or show login modal
                if (document.getElementById('modalLogin')) {
                    document.getElementById('modalLogin').classList.add('is-open');
                    document.body.classList.add('modal-open');
                } 
                return;
            }

            document.getElementById('selected-date').value = formatDateDisplay(dateString);
            
            // Reset form
            document.getElementById('cinema-select').value = '';
            document.getElementById('time-select').value = '';
            document.getElementById('consultation-content').value = '';
            document.getElementById('phone-number').value = '';
            
            // Show modal
            document.getElementById('booking-modal').classList.remove('hidden');
            document.getElementById('booking-modal').classList.add('flex');
        });
    }
    
    // Xử lý submit form
    function handleFormSubmit(e) {
        e.preventDefault();
        
        const cinema = document.getElementById('cinema-select').value;
        const time = document.getElementById('time-select').value;
        const content = document.getElementById('consultation-content').value;
        const phone = document.getElementById('phone-number').value;
        
        // Validate
        if (!cinema) {
            showErrorToast('Vui lòng chọn rạp chiếu phim');
            return;
        }
        
        if (!time) {
            showErrorToast('Vui lòng chọn thời gian');
            return;
        }
        
        if (!content.trim()) {
            showErrorToast('Vui lòng mô tả nội dung tư vấn');
            return;
        }
        
        if (!phone.trim()) {
            showErrorToast('Vui lòng nhập số điện thoại');
            return;
        }
        
        const formData = {
            id_rap: cinema,
            ngay: selectedDate,
            gio: time,
            noi_dung: content,
            so_dien_thoai: phone
        };
        
        // Submit booking
        fetch(`${baseUrl}/api/dat-lich-goi-video`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Hide booking modal
                document.getElementById('booking-modal').classList.add('hidden');
                document.getElementById('booking-modal').classList.remove('flex');
                
                // Reset form
                document.getElementById('booking-form').reset();
                
                // Show success toast
                showSuccessToast('Đặt lịch thành công! Vui lòng chờ nhân viên xác nhận.');
                
                // Reload danh sách lịch đã đặt cho ngày này
                if (selectedDate) {
                    fetchVideoCallsByDate(selectedDate);
                }
            } else {
                showErrorToast(data.message || 'Có lỗi xảy ra khi đặt lịch');
            }
        })
        .catch(error => {
            console.error('Lỗi khi đặt lịch:', error);
            showErrorToast('Có lỗi xảy ra khi đặt lịch');
        });
    }
    
    // Toast notifications
    function showSuccessToast(message) {
        showToast(message, 'success');
    }
    
    function showErrorToast(message) {
        showToast(message, 'error');
    }
    
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} toast`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('opacity-0');
            toast.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 500);
        }, 3000);
    }
    
    // Utility functions
    function formatDateForApi(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    function formatDateDisplay(dateString) {
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }
});
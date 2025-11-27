// Import Spinner từ module utility
import Spinner from './util/spinner.js';
import { socket } from './util/socket.js';
// Biến global để quản lý trạng thái phân trang
let currentPage = 1;
let perPage = 10;
let isLoading = false;
let hasMoreSessions = true;

// Biến global để quản lý phân trang tin nhắn
let currentMessagePage = 1;
let messagesPerPage = 15;
let isLoadingMessages = false;
let hasMoreMessages = true;
let oldestMessageId = null;

document.addEventListener('DOMContentLoaded', function () {
    // Khởi tạo danh sách phiên chat
    const idNhanVien = document.getElementById('tab-chat').dataset.idnhanvien;
    
    // Đảm bảo image preview được khởi tạo đúng
    const imagePreview = document.getElementById('image-preview');
    if (imagePreview) {
    }
    
    loadChatSessions();
    socket.emit('nhan-vien-tham-gia-tu-van', JSON.stringify({ id: idNhanVien }));
    // Xử lý nút làm mới
    const refreshBtn = document.getElementById('btn-refresh-sessions');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            resetPagination();
            loadChatSessions(true);
        });
    }

    // Thiết lập infinite scroll cho danh sách phiên chat
    const sessionList = document.getElementById('session-list');
    if (sessionList) {
        sessionList.addEventListener('scroll', function () {
            // Khi cuộn gần đến cuối danh sách
            if (!isLoading && hasMoreSessions && 
                (sessionList.scrollTop + sessionList.clientHeight >= sessionList.scrollHeight - 100)) {
                loadMoreSessions();
            }
        });
    }
    
    // Thêm xử lý submit form gửi tin nhắn
    setupChatForm();
    
    // Thiết lập socket listener cho tin nhắn từ khách hàng
    setupSocketListeners();
});

// Reset trạng thái phân trang
function resetPagination() {
    currentPage = 1;
    hasMoreSessions = true;
    isLoading = false;
}

// Tải thêm phiên chat khi cuộn
async function loadMoreSessions() {
    currentPage++;
    loadChatSessions(false);
}

// Gọi API lấy danh sách phiên chat
async function loadChatSessions(reset = false) {
    const sessionList = document.getElementById('session-list');
    if (!sessionList) return;
    
    // Đặt trạng thái đang tải
    isLoading = true;
    
    // Nếu reset, xóa toàn bộ danh sách cũ và hiển thị loading
    if (reset) {
        sessionList.innerHTML = '<div class="text-center py-8 text-gray-400">Đang tải...</div>';
    } else {
        // Nếu đang tải thêm, hiển thị chỉ báo tải ở cuối danh sách
        showLoadingIndicator();
    }

    try {
        // Đường dẫn API dựa vào data-url trong DOM hoặc dùng đường dẫn tương đối
        const res = await fetch(`${sessionList.dataset.url}/api/danh-sach-phien-chat?page=${currentPage}&per_page=${perPage}`);
        
        if (!res.ok) {
            throw new Error(`HTTP error ${res.status}`);
        }
        
        const result = await res.json();

        // Xóa loading indicator
        removeLoadingIndicator();
        
        // Nếu reset, xóa toàn bộ nội dung cũ
        if (reset) {
            sessionList.innerHTML = '';
        }

        if (result.success) {
            // Hiển thị phiên chat
            renderChatSessions(result.data, !reset);
            
            // Cập nhật trạng thái phân trang
            hasMoreSessions = result.pagination.has_more;
            
            // Hiển thị thông báo nếu không còn dữ liệu
            if (!hasMoreSessions && !reset && result.data.length > 0) {
                showEndOfListMessage();
            }
            
            // Hiển thị thông báo nếu không có phiên chat nào
            if (reset && (!result.data || result.data.length === 0)) {
                sessionList.innerHTML = `<div class="text-center py-8 text-gray-400">Không có phiên chat nào</div>`;
            }
        } else {
            if (reset) {
                sessionList.innerHTML = `<div class="text-center py-8 text-red-500">${result.message || 'Không thể tải phiên chat'}</div>`;
            }
        }
    } catch (err) {
        removeLoadingIndicator();
        console.error("Lỗi khi tải phiên chat:", err);
        
        if (reset) {
            sessionList.innerHTML = `<div class="text-center py-8 text-red-500">Lỗi kết nối máy chủ</div>`;
        }
    } finally {
        isLoading = false;
    }
}

// Hiển thị loading indicator khi tải thêm
function showLoadingIndicator() {
    const sessionList = document.getElementById('session-list');
    if (!sessionList) return;
    
    const loadingEl = document.getElementById('sessions-loading-indicator');
    if (!loadingEl) {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'sessions-loading-indicator';
        loadingDiv.className = 'text-center py-3 text-gray-500';
        loadingDiv.innerHTML = `
            <div class="inline-flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Đang tải thêm phiên chat...
            </div>
        `;
        sessionList.appendChild(loadingDiv);
    }
}

// Xóa loading indicator
function removeLoadingIndicator() {
    const loadingEl = document.getElementById('sessions-loading-indicator');
    if (loadingEl) {
        loadingEl.remove();
    }
}

// Hiển thị thông báo khi đã hiển thị toàn bộ danh sách
function showEndOfListMessage() {
    const sessionList = document.getElementById('session-list');
    if (!sessionList) return;
    
    // Chỉ hiển thị nếu chưa có thông báo này
    if (!document.getElementById('sessions-end-message')) {
        const endDiv = document.createElement('div');
        endDiv.id = 'sessions-end-message';
        endDiv.className = 'text-center py-3 text-gray-400 text-sm';
        endDiv.innerText = 'Đã hiển thị tất cả phiên chat';
        sessionList.appendChild(endDiv);
    }
}

// Hiển thị danh sách phiên chat
function renderChatSessions(sessions, append = false) {
    const sessionList = document.getElementById('session-list');
    if (!sessionList) return;
    
    // Kiểm tra nếu không có phiên chat và không phải append
    if (!sessions || sessions.length === 0) {
        if (!append) {
            sessionList.innerHTML = `<div class="text-center py-8 text-gray-400">Không có phiên chat nào</div>`;
        }
        return;
    }
    
    // Nếu không phải append, xóa nội dung cũ
    if (!append) {
        sessionList.innerHTML = '';
    }
    
    // Tạo HTML cho từng phiên chat
    sessions.forEach(session => {
        const lastMsg = session.tin_nhan_moi_nhat;
        const kh = session.khachhang;
        
        
        const statusMap = {
            0: { text: 'Chờ phản hồi', class: 'status-waiting bg-yellow-100 text-yellow-600' },
            1: { text: 'Đã trả lời', class: 'status-active bg-green-100 text-green-600' },
            2: { text: 'Đã đóng', class: 'status-closed bg-gray-100 text-gray-500' }
        };
        
        const status = statusMap[session.trang_thai] || statusMap[0];
        
        // Tạo badge số tin nhắn chưa đọc nếu có
        const unreadCount = session.so_tin_nhan_chua_doc || 0;
        const unreadBadge = unreadCount > 0 
            ? `<div class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">${unreadCount > 9 ? '9+' : unreadCount}</div>` 
            : '';
        
        // Thêm class highlight nếu có tin nhắn chưa đọc
        const highlightClass = unreadCount > 0 ? 'border-l-4 border-red-500' : '';

        const item = document.createElement('div');
        item.className = `session-item ${highlightClass} relative`;
        item.dataset.id = session.id;
        item.innerHTML = `
            <div class="flex justify-between items-center">
                <div>
                    <span class="session-topic">${session.chu_de || '(Không có chủ đề)'}</span>
                    <span class="session-id">#${session.id}</span>
                </div>
                <span class="session-status ${status.class}">${status.text}</span>
            </div>
            <div class="text-xs text-gray-500 mt-1">
                ${lastMsg ? `<span class="ml-1">${session.trang_thai == 1 ? 'Khách hàng:' : 'Nhân viên:'} ${truncateText(lastMsg.noi_dung, 40)}</span>` : '<em>(Chưa có tin nhắn)</em>'}
            </div>
            <div class="text-xs text-gray-400 mt-1">${formatTime(session.updated_at)}</div>
            ${unreadBadge}
        `;
        
        // Thêm sự kiện click để mở chat
        item.addEventListener('click', () => openChatSession(session));
        
        // Thêm vào danh sách
        sessionList.appendChild(item);
    });
}

// Mở phiên chat khi nhấn vào item
function openChatSession(session) {
    // Highlight item được chọn
    const items = document.querySelectorAll('.session-item');
    items.forEach(item => {
        item.classList.remove('active');
        if (item.dataset.id == session.id) {
            item.classList.add('active');
            
            // Xóa highlight border khi mở phiên chat
            item.classList.remove('border-l-4');
            item.classList.remove('border-red-500');
            
            // Xóa badge số tin nhắn chưa đọc
            const badge = item.querySelector('.bg-red-500');
            if (badge) {
                badge.remove();
            }
        }
    });
    
    // Hiển thị thông tin khách hàng thay vì rạp phim
    const khachHang = session.khachhang || {};
    document.getElementById('chatbox-cinema').textContent = khachHang.ho_ten || khachHang.email || 'Khách hàng';
    document.getElementById('chatbox-topic').textContent = session.chu_de || 'Không có chủ đề';
    document.getElementById('chatbox-sessionid').textContent = `#${session.id}`;
    
    // Hiển thị form chat
    document.getElementById('chatbox-form').style.display = 'flex';
    
    // Xóa tin nhắn cũ
    const messagesContainer = document.getElementById('chatbox-messages');
    messagesContainer.innerHTML = '';
    
    // Hiển thị spinner trong khi tải tin nhắn
    const spinner = Spinner.show({
        target: messagesContainer,
        text: 'Đang tải tin nhắn...',
        size: 'md',
        color: '#2563eb', // Blue-600
        overlay: false
    });
    
    // Reset phân trang tin nhắn
    currentMessagePage = 1;
    hasMoreMessages = true;
    oldestMessageId = null;
    
    // Cập nhật số tin nhắn chưa đọc về 0 (trong bộ nhớ)
    if (session.so_tin_nhan_chua_doc) {
        session.so_tin_nhan_chua_doc = 0;
    }
    
    // Lưu toàn bộ thông tin session để sử dụng sau này
    window.currentChatSession = session;
    
    // Lưu session ID hiện tại vào data attribute
    document.getElementById('chatbox-form').dataset.sessionId = session.id;
    document.getElementById('chatbox-form').dataset.spinnerRef = spinner.id;
    // Thêm id khách hàng vào data attribute nếu có
    if (session.khachhang) {
        document.getElementById('chatbox-form').dataset.idKhachHang = session.khachhang.id;
    }
    
    // Tải tin nhắn cho phiên chat này
    loadChatMessages(session.id);
    
    // Thiết lập infinite scroll cho tin nhắn
    setupMessageInfiniteScroll();
}

// Tải tin nhắn của phiên chat
async function loadChatMessages(sessionId, loadMore = false) {
    if (isLoadingMessages) return;
    
    isLoadingMessages = true;
    const messagesContainer = document.getElementById('chatbox-messages');
    const form = document.getElementById('chatbox-form');
    
    // Hiển thị loading indicator khi tải thêm tin nhắn cũ
    if (loadMore) {
        showMessageLoadingIndicator();
    } else {
        // Spinner đã được tạo trong openChatSession
        // Không cần tạo thêm spinner ở đây
    }
    
    try {
        // Xây dựng URL với tham số phân trang
        let url = `${document.getElementById('session-list').dataset.url}/api/chi-tiet-phien-chat/${sessionId}?page=${currentMessagePage}&per_page=${messagesPerPage}`;
        
        // Nếu đang tải thêm tin nhắn cũ, sử dụng last_message_id
        if (oldestMessageId && loadMore) {
            url += `&last_message_id=${oldestMessageId}`;
        }
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error ${response.status}`);
        }
        
        const result = await response.json();
        
        // Xóa spinner nếu là lần tải đầu tiên
        if (!loadMore && form.dataset.spinnerRef) {
            Spinner.hide(form.dataset.spinnerRef);
            form.removeAttribute('data-spinner-ref');
        }
        
        // Xóa loading indicator nếu là tải thêm
        if (loadMore) {
            removeMessageLoadingIndicator();
        }
        
        if (result.success) {
            // Cập nhật thông tin phân trang
            hasMoreMessages = result.pagination.has_more;
            oldestMessageId = result.pagination.oldest_message_id;
            
            // Render tin nhắn
            if (loadMore) {
                // Thêm tin nhắn cũ vào đầu danh sách
                prependMessages(result.data);
            } else {
                // Hiển thị tin nhắn mới (thay thế hoàn toàn)
                renderMessages(result.data);
            }
            
            // Hiển thị thông báo nếu không còn tin nhắn cũ để tải
            if (!hasMoreMessages && loadMore) {
                showNoMoreMessagesIndicator();
            }
        } else {
            console.error('Lỗi khi tải tin nhắn:', result.message);
            
            // Xóa spinner nếu còn
            if (form.dataset.spinnerRef) {
                Spinner.hide(form.dataset.spinnerRef);
                form.removeAttribute('data-spinner-ref');
            }
            
            if (!loadMore) {
                messagesContainer.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full text-red-500">
                        <div class="text-4xl mb-2">⚠️</div>
                        <p class="text-sm text-center mb-3">${result.message || 'Không thể tải tin nhắn'}</p>
                        <button onclick="loadChatMessages(${sessionId})" 
                                class="bg-red-500 text-white px-4 py-2 rounded-lg text-xs hover:bg-red-600 transition">
                            Thử lại
                        </button>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Lỗi khi tải tin nhắn:', error);
        
        // Xóa spinner nếu còn
        if (form.dataset.spinnerRef) {
            Spinner.hide(form.dataset.spinnerRef);
            form.removeAttribute('data-spinner-ref');
        }
        
        removeMessageLoadingIndicator();
        
        if (!loadMore) {
            messagesContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-red-500">
                    <div class="text-4xl mb-2">⚠️</div>
                    <p class="text-sm text-center mb-3">Lỗi kết nối máy chủ</p>
                    <button onclick="loadChatMessages(${sessionId})" 
                            class="bg-red-500 text-white px-4 py-2 rounded-lg text-xs hover:bg-red-600 transition">
                        Thử lại
                    </button>
                </div>
            `;
        }
    } finally {
        isLoadingMessages = false;
    }
}

// Thiết lập infinite scroll cho tin nhắn
function setupMessageInfiniteScroll() {
    const messagesContainer = document.getElementById('chatbox-messages');
    
    // Remove existing listener to avoid duplicates
    messagesContainer.removeEventListener('scroll', handleMessageScroll);
    messagesContainer.addEventListener('scroll', handleMessageScroll);
}

// Xử lý sự kiện cuộn tin nhắn
function handleMessageScroll() {
    const messagesContainer = document.getElementById('chatbox-messages');
    
    // Khi cuộn gần đến đầu danh sách tin nhắn, tải thêm tin nhắn cũ
    if (!isLoadingMessages && hasMoreMessages && messagesContainer.scrollTop <= 50) {
        const sessionId = document.getElementById('chatbox-form').dataset.sessionId;
        if (sessionId) {
            currentMessagePage++;
            loadChatMessages(sessionId, true);
        }
    }
}

// Hiển thị loading indicator khi tải thêm tin nhắn
function showMessageLoadingIndicator() {
    const messagesContainer = document.getElementById('chatbox-messages');
    
    // Chỉ tạo nếu chưa tồn tại
    if (!document.getElementById('message-loading-indicator')) {
        const loadingIndicator = document.createElement('div');
        loadingIndicator.id = 'message-loading-indicator';
        loadingIndicator.className = 'text-center py-2 text-sm text-gray-500';
        loadingIndicator.innerHTML = `
            <div class="inline-flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Đang tải tin nhắn cũ...
            </div>
        `;
        
        // Chèn vào đầu danh sách tin nhắn
        messagesContainer.insertBefore(loadingIndicator, messagesContainer.firstChild);
    }
}

// Xóa loading indicator
function removeMessageLoadingIndicator() {
    const loadingIndicator = document.getElementById('message-loading-indicator');
    if (loadingIndicator) {
        loadingIndicator.remove();
    }
}

// Hiển thị thông báo khi không còn tin nhắn để tải
function showNoMoreMessagesIndicator() {
    const messagesContainer = document.getElementById('chatbox-messages');
    
    // Chỉ tạo nếu chưa tồn tại
    if (!document.getElementById('no-more-messages')) {
        const noMoreIndicator = document.createElement('div');
        noMoreIndicator.id = 'no-more-messages';
        noMoreIndicator.className = 'text-center py-2 text-xs text-gray-400';
        noMoreIndicator.innerText = 'Đã hiển thị tất cả tin nhắn';
        
        // Chèn vào đầu danh sách tin nhắn
        messagesContainer.insertBefore(noMoreIndicator, messagesContainer.firstChild);
    }
}

// Hiển thị tin nhắn (thay thế toàn bộ)
function renderMessages(messages) {
    const messagesContainer = document.getElementById('chatbox-messages');
    messagesContainer.innerHTML = '';
    
    if (!messages || messages.length === 0) {
        messagesContainer.innerHTML = `
            <div class="flex items-center justify-center h-full text-gray-400">
                <p>Chưa có tin nhắn nào</p>
            </div>
        `;
        return;
    }
    
    // Nhóm tin nhắn theo ngày
    let currentDay = null;
    
    // Hiển thị tin nhắn theo thứ tự thời gian
    messages.forEach(message => {
        const messageDate = new Date(message.created_at);
        const messageDay = messageDate.toDateString();
        
        // Nếu sang ngày mới, hiển thị dấu phân cách ngày
        if (messageDay !== currentDay) {
            currentDay = messageDay;
            
            // Xác định text hiển thị cho ngày
            let dayText;
            const today = new Date().toDateString();
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            
            if (messageDay === today) {
                dayText = 'Hôm nay';
            } else if (messageDay === yesterday.toDateString()) {
                dayText = 'Hôm qua';
            } else {
                // Format ngày/tháng/năm
                const day = messageDate.getDate();
                const month = messageDate.getMonth() + 1;
                const year = messageDate.getFullYear();
                dayText = `${day}/${month}/${year}`;
            }
            
            // Thêm dấu phân cách ngày
            const dayDivider = document.createElement('div');
            dayDivider.className = 'flex items-center justify-center my-4';
            dayDivider.innerHTML = `
                <div class="bg-gray-200 text-gray-600 text-xs px-3 py-1 rounded-full">
                    ${dayText}
                </div>
            `;
            messagesContainer.appendChild(dayDivider);
        }
        
        // Thêm tin nhắn
        const messageElement = createMessageElement(message);
        messagesContainer.appendChild(messageElement);
    });
    
    // Cuộn xuống tin nhắn cuối cùng
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Thêm tin nhắn cũ vào đầu danh sách
function prependMessages(messages) {
    if (!messages || messages.length === 0) return;
    
    const messagesContainer = document.getElementById('chatbox-messages');
    const scrollHeight = messagesContainer.scrollHeight;
    const scrollTop = messagesContainer.scrollTop;
    
    // Thêm từng tin nhắn vào đầu danh sách (trừ loading indicator)
    const loadingIndicator = document.getElementById('message-loading-indicator');
    const firstMessageElement = loadingIndicator ? loadingIndicator.nextSibling : messagesContainer.firstChild;
    
    // Thêm từng tin nhắn theo thứ tự thời gian
    messages.forEach(message => {
        const messageElement = createMessageElement(message);
        messagesContainer.insertBefore(messageElement, firstMessageElement);
    });
    
    // Giữ nguyên vị trí cuộn
    messagesContainer.scrollTop = messagesContainer.scrollHeight - scrollHeight + scrollTop;
}

// Cập nhật hàm tạo element cho tin nhắn để hỗ trợ hiển thị ảnh
function createMessageElement(message) {
    const messageDiv = document.createElement('div');
    
    // Phân loại tin nhắn: 1 = khách hàng, 2 = nhân viên, null = hệ thống
    let senderClass = 'staff';
    let senderName = 'Nhân viên';
    
    if (message.nguoi_gui === 1) {
        senderClass = 'user';
        senderName = 'Khách hàng';
    } else if (message.nguoi_gui === 2) {
        // Nếu là nhân viên, kiểm tra id nhân viên hiện tại
        const idNhanVienHienTai = document.getElementById('tab-chat')?.dataset?.idnhanvien;
        if (message.id_nhanvien == idNhanVienHienTai || !message.id_nhanvien) {
            senderName = 'Bạn';
        } else {
            senderName = message.ten_nhanvien ? `${message.ten_nhanvien} #${message.ma_nhanvien || ''}` : 'Nhân viên';
        }
        senderClass = 'staff';
    } else if (message.nguoi_gui === null) {
        senderClass = 'system';
        senderName = 'Hệ thống';
    }
    
    messageDiv.className = `chatbox-fb-message ${senderClass}`;
    
    // Format thời gian kiểu Zalo
    const messageTime = formatMessageTime(message.created_at);
    
    // Phần header của tin nhắn
    const headerDiv = document.createElement('div');
    headerDiv.className = 'flex items-center justify-between mb-2 text-xs opacity-70';
    headerDiv.innerHTML = `
        <span>${senderName}</span>
        &nbsp; 
        <span>${messageTime}</span>
    `;
    messageDiv.appendChild(headerDiv);
    
    // Xử lý nội dung tin nhắn
    // Nếu có ảnh
    if (message.has_image || message.image_url || message.loai_noi_dung == 2 || message.is_image) {
        // Tạo container cho ảnh
        const imageContainer = document.createElement('div');
        imageContainer.className = 'message-image-container';
        
        // Tạo phần tử ảnh
        const img = document.createElement('img');
        img.className = 'message-image cursor-pointer';
        img.loading = 'lazy';
        img.alt = 'Hình ảnh';
        // Xác định nguồn ảnh
        if (message.image_url) {
            img.src = message.image_url;
            img.dataset.fullImage = message.image_url;
            img.onclick = () => showFullImage(message.image_url);
            
            imageContainer.appendChild(img);
            messageDiv.appendChild(imageContainer);
        } else if (message.loai_noi_dung == 2 && message.noi_dung) {
            // Cải thiện: Đảm bảo noi_dung được xử lý đúng khi là đường dẫn ảnh
            const imageUrl = message.noi_dung.includes('chat-images/') 
                ? `${document.getElementById('session-list').dataset.urlminio}/hinh-anh/${message.noi_dung}` 
                : message.noi_dung;
                
            img.src = imageUrl;
            img.dataset.fullImage = imageUrl;
            img.onclick = () => showFullImage(imageUrl);
            
            imageContainer.appendChild(img);
            messageDiv.appendChild(imageContainer);
        } else if (message.has_image) {
            // Ảnh đang tải hoặc chưa có URL cụ thể
            img.src = `${document.getElementById('session-list').dataset.url}/images/loading-image.png`;
            img.onclick = () => alert('Ảnh đang được tải lên');
            
            imageContainer.appendChild(img);
            messageDiv.appendChild(imageContainer);
        } else {
            // Không thể hiển thị ảnh
            const errorDiv = document.createElement('div');
            errorDiv.className = 'text-red-500 text-sm italic';
            errorDiv.textContent = 'Không thể hiển thị hình ảnh';
            messageDiv.appendChild(errorDiv);
        }
    } else {
        // Tin nhắn text thông thường
        const contentDiv = document.createElement('div');
        contentDiv.style.lineHeight = '1.4';
        contentDiv.style.wordWrap = 'break-word';
        contentDiv.innerHTML = message.noi_dung;
        messageDiv.appendChild(contentDiv);
    }
    
    return messageDiv;
}

// Thêm hàm hiển thị ảnh đầy đủ
function showFullImage(imageUrl) {
    // Tìm hoặc tạo image viewer
    let imageViewer = document.getElementById('image-viewer');
    
    if (!imageViewer) {
        imageViewer = document.createElement('div');
        imageViewer.id = 'image-viewer';
        imageViewer.className = 'fixed top-0 left-0 w-full h-full bg-black bg-opacity-90 z-50 flex items-center justify-center';
        imageViewer.innerHTML = `
            <button class="absolute top-4 right-4 text-white text-4xl">&times;</button>
            <img id="full-image" class="max-w-[90%] max-h-[90%] object-contain" src="" />
        `;
        
        // Thêm event listener đóng viewer
        imageViewer.addEventListener('click', function(e) {
            if (e.target === imageViewer || e.target.tagName === 'BUTTON') {
                imageViewer.classList.add('hidden');
            }
        });
        
        document.body.appendChild(imageViewer);
    } else {
        imageViewer.classList.remove('hidden');
    }
    
    // Cập nhật nguồn ảnh
    const fullImage = document.getElementById('full-image');
    fullImage.src = imageUrl;
}

// Các hàm tiện ích

// Cắt ngắn text nếu quá dài
function truncateText(text, maxLength) {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

// Format thời gian từ timestamp
function formatTime(timestamp) {
    if (!timestamp) return '';
    try {
        const date = new Date(timestamp);
        return date.toLocaleString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit',
            day: '2-digit',
            month: '2-digit'
        });
    } catch (e) {
        console.error('Lỗi format thời gian:', e);
        return '';
    }
}

// Format thời gian cho tin nhắn (kiểu Zalo: chỉ hiện giờ và phút)
function formatMessageTime(timestamp) {
    if (!timestamp) return '';
    try {
        const date = new Date(timestamp);
        return date.toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        console.error('Lỗi format thời gian tin nhắn:', e);
        return '';
    }
}

// Hàm định dạng kích thước file
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    else if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    else return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

// Thêm biến global để quản lý file đã chọn
let selectedImageFile = null;

// Thiết lập xử lý form gửi tin nhắn
function setupChatForm() {
    const chatForm = document.getElementById('chatbox-form');
    const chatInput = document.getElementById('chatbox-input');
    
    // Xử lý upload ảnh
    const fileInput = document.getElementById('chatbox-upload');
    const imagePreviewContainer = document.getElementById('image-preview');
    const previewImage = document.getElementById('preview-image');
    const imageName = document.getElementById('image-name');
    const imageSize = document.getElementById('image-size');
    const removeImageBtn = document.getElementById('remove-image');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Kiểm tra kích thước file (giới hạn 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Kích thước file không được vượt quá 2MB');
                fileInput.value = '';
                return;
            }
            
            // Kiểm tra loại file
            if (!file.type.match('image.*')) {
                alert('Chỉ chấp nhận file hình ảnh');
                fileInput.value = '';
                return;
            }
            
            // Lưu file đã chọn
            selectedImageFile = file;
            
            // Hiển thị preview - alternative approach with a fixed overlay
            const reader = new FileReader();
            reader.onload = function(e) {
                console.log('Image loaded, setting preview');
                
                // Try a completely different approach - create an overlay
                showImagePreviewOverlay(e.target.result, file.name, formatFileSize(file.size));
                
                // Still update the regular preview elements in case they become visible
                previewImage.src = e.target.result;
                imageName.textContent = file.name;
                imageSize.textContent = formatFileSize(file.size);
                
                // Try to make the original preview visible too
                imagePreviewContainer.style.display = 'flex';
                imagePreviewContainer.style.visibility = 'visible';
                imagePreviewContainer.style.opacity = '1';
                
                console.log('Preview container visibility:', getComputedStyle(imagePreviewContainer).display);
            };
            reader.readAsDataURL(file);
        });
    }
    
    // Xóa ảnh đã chọn
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            console.log('Removing image');
            // Reset file selection
            selectedImageFile = null;
            fileInput.value = '';
            
            // Direct style manipulation - simpler and more reliable
            imagePreviewContainer.style.display = 'none';
            
            console.log('Preview container hidden');
        });
    }
    
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Ngăn form submit mặc định
        
        const message = chatInput.value.trim();
        
        // Kiểm tra xem có tin nhắn text hoặc ảnh không
        if (!message && !selectedImageFile) return; // Không gửi tin nhắn rỗng và không có ảnh
        
        const sessionId = chatForm.dataset.sessionId;
        if (!sessionId) {
            console.error('Không tìm thấy ID phiên chat');
            return;
        }
        
        // Lấy ID nhân viên và ID khách hàng
        const idNhanVien = document.getElementById('tab-chat').dataset.idnhanvien;
        const idKhachHang = chatForm.dataset.idKhachHang || 
                           (window.currentChatSession && window.currentChatSession.khachhang ? 
                            window.currentChatSession.khachhang.id : null);
        
        if (!idKhachHang) {
            console.warn('Không tìm thấy ID khách hàng, tin nhắn có thể không được gửi đến đúng người nhận');
        }
        
        // Nếu có ảnh, sử dụng hàm gửi tin nhắn kèm ảnh
        if (selectedImageFile) {
            sendMessageWithImage(message, selectedImageFile, sessionId, idNhanVien, idKhachHang);
        } else {
            // Gửi tin nhắn text thông thường
            sendMessageViaSocket(sessionId, message, idNhanVien, idKhachHang);
        }
        
        // Xóa nội dung input sau khi gửi
        chatInput.value = '';
        
        // Reset ảnh nếu có
        if (selectedImageFile) {
            selectedImageFile = null;
            fileInput.value = '';
            imagePreviewContainer.classList.add('hidden');
        }
    });
}

// Hàm gửi tin nhắn qua socket và cập nhật UI
function sendMessageViaSocket(sessionId, message, idNhanVien, idKhachHang) {
    try {
        // Tạo object tin nhắn để hiển thị ngay lập tức (optimistic UI)
        const messageObj = {
            id: Date.now(), // Tạm thời dùng timestamp làm ID
            noi_dung: message,
            nguoi_gui: 2, // 2 = nhân viên
            created_at: new Date().toISOString(),
            loai_noi_dung: 1 // 1 = Text
        };
        
        // Hiển thị tin nhắn ngay trên UI (không đợi phản hồi từ server)
        appendNewMessage(messageObj);
        
        // Gửi tin nhắn qua socket với đầy đủ thông tin
        socket.emit('nhan-vien-gui-tin-nhan', JSON.stringify({
            id_phienchat: sessionId,
            idPhienChat: sessionId,
            id_nhanvien: idNhanVien,
            id_khachhang: idKhachHang,
            noi_dung: message,
            noiDung: message,
            loai_noi_dung: 1, // 1 = Text
            loaiNoiDung: 1,
            nguoiGui: 2 // 2 = nhân viên
        }));
        
        // Cập nhật tin nhắn mới nhất trong danh sách phiên chat
        updateLatestMessageInList(sessionId, messageObj);
        
        // Cập nhật trạng thái phiên chat sang "Chờ phản hồi"
        updateSessionStatus(sessionId, 0); // 0 = Chờ phản hồi
        
        // Cập nhật trạng thái trong bộ nhớ nếu đây là phiên chat hiện tại
        if (window.currentChatSession && window.currentChatSession.id == sessionId) {
            window.currentChatSession.trang_thai = 0; // 0 = Chờ phản hồi
        }
        
    } catch (error) {
        console.error('Lỗi khi gửi tin nhắn:', error);
        // Hiển thị thông báo lỗi cho người dùng
        showErrorToast('Không thể gửi tin nhắn, vui lòng thử lại');
    }
}

// Hàm gửi tin nhắn kèm ảnh qua socket
async function sendMessageWithImage(message, imageFile, sessionId, idNhanVien, idKhachHang) {
    if (!sessionId) {
        showErrorToast('Không tìm thấy ID phiên chat');
        return;
    }
    
    const messagesContainer = document.getElementById('chatbox-messages');
    
    // Tạo dữ liệu tin nhắn tạm thời để hiển thị ngay lập tức (optimistic UI)
    const messageData = {
        id: Date.now(),
        noi_dung: message || 'Ảnh đã gửi',
        nguoi_gui: 2, // 2 = nhân viên
        created_at: new Date().toISOString(),
        has_image: true, // Đánh dấu là có ảnh
        loai_noi_dung: 2 // 2 = Hình ảnh
    };
    
    // Thêm tin nhắn vào khu vực chat (optimistic UI)
    const messageDiv = createMessageElement(messageData);
    messagesContainer.appendChild(messageDiv);
    
    // Đánh dấu ảnh đang tải
    const imageContainer = messageDiv.querySelector('.message-image-container');
    if (imageContainer) {
        imageContainer.classList.add('image-uploading');
    }
    
    // Cuộn xuống cuối
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // Cập nhật trạng thái phiên chat sang "Chờ phản hồi"
    updateSessionStatus(sessionId, 0); // 0 = Chờ phản hồi
    
    try {
        console.log('Đang gửi ảnh qua socket...');
        // Chuyển ảnh thành base64 để gửi qua socket
        const base64Image = await convertImageToBase64(imageFile);
        
        // Instead of directly using the base64 string which might be too long
        // Create a Blob object and use URL.createObjectURL for better performance
        const byteCharacters = atob(base64Image);
        const byteNumbers = new Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        const byteArray = new Uint8Array(byteNumbers);
        const blob = new Blob([byteArray], { type: 'image/jpeg' });
        const imageUrl = URL.createObjectURL(blob);
        
        // Chuẩn bị dữ liệu gửi qua socket
        const defaultMessage = message || "Ảnh đã gửi";
        const socketData = {
            // Gửi cả hai format để đảm bảo tương thích
            id_phienchat: sessionId,
            idPhienChat: sessionId,
            id_nhanvien: idNhanVien,
            id_khachhang: idKhachHang,
            noi_dung: defaultMessage,
            noiDung: defaultMessage,
            loai_noi_dung: 2, // 2 = Hình ảnh
            loaiNoiDung: 2,
            nguoiGui: 2, // 2 = nhân viên
            image_data: base64Image,
            file_name: imageFile.name,
            file_type: imageFile.type,
            file_size: imageFile.size
        };
        
        // Gửi tin nhắn qua socket
        socket.emit('nhan-vien-gui-tin-nhan', JSON.stringify(socketData));
        
        // Cập nhật tin nhắn cuối cùng trong danh sách phiên chat
        updateLatestMessageInList(sessionId, messageData);
        
        // Hiển thị hình ảnh đã chọn trong tin nhắn ngay lập tức (không đợi server)
        const imageElement = messageDiv.querySelector('.message-image');
        if (imageElement && imageContainer) {
            // Sử dụng base64Image đã chuyển đổi để hiển thị ảnh
            imageElement.src = imageUrl;
            imageElement.dataset.fullImage = imageUrl;
            // Xóa lớp đang tải
            imageContainer.classList.remove('image-uploading');
        }
        
        // Chúng ta không cần phải đợi phản hồi từ server nên bỏ phần code xử lý imageUploadResult
        console.log('Đã gửi ảnh thành công qua socket');
    } catch (error) {
        console.error('Lỗi khi gửi ảnh qua socket:', error);
        
        // Xử lý lỗi UI
        if (imageContainer) {
            imageContainer.classList.remove('image-uploading');
            imageContainer.classList.add('image-error');
        }
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-500 text-sm mt-1';
        errorDiv.textContent = 'Lỗi khi tải lên ảnh. Vui lòng thử lại.';
        messageDiv.appendChild(errorDiv);
    }
    finally{
        // ẨN IMAGE PREVIEW SAU KHI GỬI THÀNH CÔNG
        const imagePreviewContainer = document.getElementById('image-preview');
        if (imagePreviewContainer) {
            imagePreviewContainer.style.display = 'none';
        }
    }
}

// Thêm tin nhắn mới vào cuối danh sách chat hiện tại
function appendNewMessage(message) {
    const messagesContainer = document.getElementById('chatbox-messages');
    
    // Tạo element tin nhắn
    const messageElement = createMessageElement(message);
    
    // Thêm tin nhắn vào cuối danh sách
    messagesContainer.appendChild(messageElement);
    
    // Cuộn xuống để hiển thị tin nhắn mới
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Cập nhật tin nhắn mới nhất trong danh sách phiên chat
function updateLatestMessageInList(sessionId, message) {
    const sessionItem = document.querySelector(`.session-item[data-id="${sessionId}"]`);
    if (sessionItem) {
        // Cập nhật nội dung tin nhắn hiển thị trong danh sách
        const messageSpan = sessionItem.querySelector('.text-xs.text-gray-500 span:last-child');
        
        // Xác định prefix dựa vào người gửi tin nhắn
        let messagePrefix = '';
        if (message.nguoi_gui === 1) { // Khách hàng
            messagePrefix = 'Khách hàng: ';
        } else if (message.nguoi_gui === 2) { // Nhân viên
            messagePrefix = 'Nhân viên: ';
        } else { // Hệ thống
            messagePrefix = 'Hệ thống: ';
        }
        
        if (messageSpan) {
            messageSpan.textContent = `${messagePrefix}${truncateText(message.noi_dung, 40)}`;
            messageSpan.title = message.noi_dung;
        } else {
            // Nếu không tìm thấy, có thể cần tạo mới phần tử này
            const messageContainer = sessionItem.querySelector('.text-xs.text-gray-500');
            if (messageContainer) {
                const newSpan = document.createElement('span');
                newSpan.className = 'ml-1';
                newSpan.textContent = `${messagePrefix}${truncateText(message.noi_dung, 40)}`;
                newSpan.title = message.noi_dung;
                messageContainer.appendChild(newSpan);
            }
        }
        
        // Cập nhật thời gian
        const timeElement = sessionItem.querySelector('.text-xs.text-gray-400');
        if (timeElement) {
            timeElement.textContent = 'Vừa xong';
        }
        
        // Nếu tin nhắn từ khách hàng, cập nhật trạng thái phiên chat sang "Đã trả lời"
        if (message.nguoi_gui === 1) { // 1 = khách hàng
            const statusElement = sessionItem.querySelector('.session-status');
            if (statusElement) {
                // Cập nhật trạng thái thành "Đã trả lời"
                statusElement.className = 'session-status status-active bg-green-100 text-green-600';
                statusElement.textContent = 'Đã trả lời';
                
                // Cập nhật trạng thái trong window.currentChatSession nếu cần
                if (window.currentChatSession && window.currentChatSession.id == sessionId) {
                    window.currentChatSession.trang_thai = 1; // 1 = Đã trả lời
                }
            }
        } else if (message.nguoi_gui === 2) { // 2 = nhân viên
            const statusElement = sessionItem.querySelector('.session-status');
            if (statusElement) {
                // Cập nhật trạng thái thành "Chờ phản hồi"
                statusElement.className = 'session-status status-waiting bg-yellow-100 text-yellow-600';
                statusElement.textContent = 'Chờ phản hồi';
                
                // Cập nhật trạng thái trong window.currentChatSession nếu cần
                if (window.currentChatSession && window.currentChatSession.id == sessionId) {
                    window.currentChatSession.trang_thai = 0; // 0 = Chờ phản hồi
                }
            }
        }
        
        // Di chuyển phiên chat này lên đầu danh sách
        const sessionList = document.getElementById('session-list');
        if (sessionList && sessionList.firstChild && sessionList.firstChild !== sessionItem) {
            sessionList.insertBefore(sessionItem, sessionList.firstChild);
            
            // Thêm hiệu ứng highlight tạm thời
            sessionItem.classList.add('bg-blue-50');
            setTimeout(() => {
                sessionItem.classList.remove('bg-blue-50');
            }, 2000);
        }
        
        // Xóa chỉ báo tin nhắn mới khi đây là phiên chat đang mở
        if (window.currentChatSession && window.currentChatSession.id == sessionId) {
            // Xóa indicator nếu có
            const indicator = sessionItem.querySelector('.new-message-indicator');
            if (indicator) {
                indicator.remove();
            }
            
            // Xóa badge số tin nhắn chưa đọc
            const badge = sessionItem.querySelector('.bg-red-500');
            if (badge) {
                badge.remove();
            }
            
            // Xóa border highlight
            sessionItem.classList.remove('border-l-4');
            sessionItem.classList.remove('border-red-500');
            
            sessionItem.classList.remove('bg-blue-50');
        }
    } else {
        console.warn("Không tìm thấy phiên chat ID:", sessionId, "trong DOM");
    }
}

// Sửa hàm updateSessionStatus
function updateSessionStatus(sessionId, status) {
    const sessionItem = document.querySelector(`.session-item[data-id="${sessionId}"]`);
    if (sessionItem) {
        const statusElement = sessionItem.querySelector('.session-status');
        if (statusElement) {
            // Đảo ngược logic: Tin nhắn từ khách hàng -> trạng thái "Đã trả lời" (1)
            if (status == 0) { // Chờ phản hồi (bây giờ là trạng thái khi nhân viên gửi)
                statusElement.className = 'session-status status-waiting bg-yellow-100 text-yellow-600';
                statusElement.textContent = 'Chờ phản hồi';
            } else if (status == 1) { // Đã trả lời (bây giờ là trạng thái khi khách hàng gửi)
                statusElement.className = 'session-status status-active bg-green-100 text-green-600';
                statusElement.textContent = 'Đã trả lời';
            }
        }
    }
}

// Thiết lập các listener cho socket
function setupSocketListeners() {
    // Lắng nghe sự kiện khi khách hàng gửi tin nhắn mới
    socket.on("khach-hang-tu-van-gui-tin-nhan", function(data) {
        try {
            // Parse dữ liệu JSON nhận được
            const messageData = JSON.parse(data);
            console.log("Tin nhắn từ khách hàng:", messageData);
            
            // Kiểm tra cấu trúc dữ liệu
            const sessionId = messageData.id; // ID phiên chat
            let messageContent = messageData.msg;
            
            // Tạo đối tượng tin nhắn để hiển thị
            const message = {
                id: Date.now(), // ID tạm thời
                noi_dung: typeof messageContent === 'string' ? messageContent : 
                          (messageContent.noi_dung || JSON.stringify(messageContent)),
                nguoi_gui: 1, // 1 = khách hàng
                // Sửa chỗ này - lấy thuộc tính từ messageData thay vì messageContent
                loai_noi_dung: messageData.loai_noi_dung || 
                         (messageContent && messageContent.loai_noi_dung) || 
                         (messageData.is_image ? 2 : 1),
                is_image: messageData.is_image || 
                     (messageContent && messageContent.is_image) || 
                     false,
                created_at: new Date().toISOString(),
                id_phienchat: sessionId
            };
            
            // Cập nhật trạng thái phiên chat sang "Đã trả lời"
            updateSessionStatus(sessionId, 1); // 1 = Đã trả lời
            
            // Kiểm tra xem tin nhắn có thuộc phiên chat đang mở không
            if (window.currentChatSession && window.currentChatSession.id == sessionId) {
                // Thêm tin nhắn vào khu vực chat hiện tại
                appendNewMessage(message);
                
                // Phát âm thanh thông báo
                playNotificationSound();
                
                // Cập nhật trạng thái phiên chat trong bộ nhớ
                window.currentChatSession.trang_thai = 1; // 1 = Đã trả lời
            } else {
                // Nếu không phải phiên chat hiện tại, tăng số tin nhắn chưa đọc
                incrementUnreadCount(sessionId);
                
                // Hiển thị thông báo có tin nhắn mới
                showNewMessageNotification(sessionId);
            }
            
            // Luôn cập nhật tin nhắn mới nhất trong danh sách phiên chat
            updateLatestMessageInList(sessionId, message);
            
        } catch (error) {
            console.error("Lỗi xử lý tin nhắn từ khách hàng:", error);
        }
    });
    
    // Lắng nghe xác nhận khi gửi tin nhắn thành công
    socket.on("nhan-vien-gui-tin-nhan-thanh-cong", function(data) {
        console.log("Tin nhắn gửi thành công:", data);
    });
    
    // Lắng nghe sự kiện khi có phiên chat mới được tạo
    socket.on("phien-chat-moi", function(data) {
        try {
            const sessionData = JSON.parse(data);
            console.log("Phiên chat mới:", sessionData);
            
            // Làm mới danh sách phiên chat để hiển thị phiên mới
            resetPagination();
            loadChatSessions(true);
            
            // Hiển thị thông báo toast
            showToastNotification("Có phiên chat mới");
            
            // Phát âm thanh thông báo
            playNotificationSound();
        } catch (error) {
            console.error("Lỗi xử lý phiên chat mới:", error);
        }
    });
    
    // Lắng nghe sự kiện khi có lỗi từ server
    socket.on("error", function(error) {
        console.error("Lỗi socket:", error);
    });
}

// Hàm tăng số tin nhắn chưa đọc
function incrementUnreadCount(sessionId) {
    const sessionItem = document.querySelector(`.session-item[data-id="${sessionId}"]`);
    if (sessionItem) {
        // Thêm border highlight nếu chưa có
        if (!sessionItem.classList.contains('border-l-4')) {
            sessionItem.classList.add('border-l-4', 'border-red-500');
        }
        
        // Tìm badge số tin nhắn chưa đọc
        let badge = sessionItem.querySelector('.bg-red-500');
        
        if (badge) {
            // Nếu đã có badge, tăng số lên 1
            let count = parseInt(badge.textContent);
            if (isNaN(count) || count >= 9) {
                badge.textContent = '9+';
            } else {
                badge.textContent = count + 1;
            }
        } else {
            // Nếu chưa có badge, thêm mới với số 1
            badge = document.createElement('div');
            badge.className = 'absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center';
            badge.textContent = '1';
            sessionItem.appendChild(badge);
            
            // Đảm bảo item có position relative để badge định vị đúng
            if (getComputedStyle(sessionItem).position !== 'relative') {
                sessionItem.style.position = 'relative';
            }
        }
    }
}

// Thêm hàm phát âm thanh thông báo khi có tin nhắn mới
function playNotificationSound() {
    try {
        // Kiểm tra xem có thẻ audio cho notification chưa
        let notificationSound = document.getElementById('notification-sound');
        
        // Nếu chưa có, tạo mới
        if (!notificationSound) {
            notificationSound = document.createElement('audio');
            notificationSound.id = 'notification-sound';
            notificationSound.src = `${document.getElementById('session-list').dataset.url}/assets/sounds/notification.mp3`;
            notificationSound.preload = 'auto';
            document.body.appendChild(notificationSound);
        }
        
        // Reset và phát âm thanh
        notificationSound.pause();
        notificationSound.currentTime = 0;
        
        // Kiểm tra xem người dùng có cho phép phát âm thanh không
        const playPromise = notificationSound.play();
        
        // Xử lý lỗi khi browser chặn autoplay
        if (playPromise !== undefined) {
            playPromise.catch(error => {
                console.warn('Không thể phát âm thanh thông báo:', error);
            });
        }
    } catch (error) {
        console.warn('Lỗi khi phát âm thanh thông báo:', error);
    }
}

// Hàm chuyển đổi file ảnh thành base64
function convertImageToBase64(imageFile) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result.split(',')[1]); // Lấy phần base64 sau dấu phẩy
        reader.onerror = error => reject(error);
        reader.readAsDataURL(imageFile);
    });
}


// Thêm hàm hiển thị thông báo khi có tin nhắn mới trong phiên chat khác
function showNewMessageNotification(sessionId) {
    // Tìm phiên chat trong danh sách
    const sessionItem = document.querySelector(`.session-item[data-id="${sessionId}"]`);
    if (!sessionItem) return;
    
    // Thêm indicator nếu chưa có
    if (!sessionItem.querySelector('.new-message-indicator')) {
        const indicator = document.createElement('div');
        indicator.className = 'new-message-indicator absolute -top-2 -right-2 w-3 h-3 bg-red-500 rounded-full animate-ping';
        sessionItem.appendChild(indicator);
    }
    
    // Thêm hiệu ứng nhấp nháy cho phiên chat
    sessionItem.classList.add('bg-blue-50');
    setTimeout(() => {
        sessionItem.classList.remove('bg-blue-50');
        setTimeout(() => {
            sessionItem.classList.add('bg-blue-50');
            setTimeout(() => {
                sessionItem.classList.remove('bg-blue-50');
            }, 500);
        }, 500);
    }, 500);
    
    // Hiển thị thông báo toast
    const chatTitle = sessionItem.querySelector('.session-topic').textContent;
    showToastNotification(`Tin nhắn mới từ ${chatTitle}`);
}

// Thêm hàm hiển thị thông báo toast
function showToastNotification(message) {
    // Kiểm tra xem đã có container toast chưa
    let toastContainer = document.getElementById('toast-container');
    
    if (!toastContainer) {
        // Tạo container cho toast
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'fixed top-4 right-4 z-50 flex flex-col space-y-2';
        document.body.appendChild(toastContainer);
    }
    
    // Tạo toast mới
    const toast = document.createElement('div');
    toast.className = 'bg-blue-600 text-white px-4 py-2 rounded shadow-lg transform transition-all duration-300 translate-x-full opacity-0';
    toast.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>${message}</span>
        </div>
    `;
    
    // Thêm toast vào container
    toastContainer.appendChild(toast);
    
    // Animation hiển thị
    setTimeout(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
    }, 10);
    
    // Tự động ẩn sau 3 giây
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        
        // Xóa khỏi DOM sau khi animation kết thúc
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

// Thêm hàm hiển thị thông báo lỗi
function showErrorToast(message) {
    // Tương tự như showToastNotification nhưng với màu đỏ
    let toastContainer = document.getElementById('toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'fixed top-4 right-4 z-50 flex flex-col space-y-2';
        document.body.appendChild(toastContainer);
    }
    
    const toast = document.createElement('div');
    toast.className = 'bg-red-600 text-white px-4 py-2 rounded shadow-lg transform transition-all duration-300 translate-x-full opacity-0';
    toast.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>${message}</span>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
    }, 10);
    
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 5000); // Hiển thị lâu hơn (5 giây) vì đây là lỗi
}

// Hiển thị preview ảnh trên ô nhập tin nhắn
function showImagePreviewOverlay(imageUrl, fileName, fileSize) {
    // Sử dụng container image-preview có sẵn
    const imagePreviewContainer = document.getElementById('image-preview');
    const previewImage = document.getElementById('preview-image');
    const imageName = document.getElementById('image-name');
    const imageSize = document.getElementById('image-size');
    
    if (!imagePreviewContainer) {
        console.error('Không tìm thấy phần tử image-preview');
        return;
    }
    
    if (!previewImage) {
        console.error('Không tìm thấy phần tử preview-image');
        return;
    }
    
    if (!imageName) {
        console.error('Không tìm thấy phần tử image-name');
        return;
    }
    
    if (!imageSize) {
        console.error('Không tìm thấy phần tử image-size');
        return;
    }
    
    // Cập nhật nội dung của preview
    previewImage.src = imageUrl;
    imageName.textContent = fileName || 'Ảnh đính kèm';
    imageSize.textContent = fileSize || '';
    
    // Hiển thị container preview
    imagePreviewContainer.style.display = 'flex';
    imagePreviewContainer.style.visibility = 'visible';
    imagePreviewContainer.style.opacity = '1';
    
    
    // Cuộn lên để đảm bảo phần xem trước ảnh hiển thị rõ ràng
    imagePreviewContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    console.log('Image preview container hiển thị với ảnh:', fileName);
}
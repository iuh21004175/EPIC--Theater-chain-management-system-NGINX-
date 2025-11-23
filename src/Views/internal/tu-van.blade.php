@extends('internal.layout')

@section('title', 'Tư vấn khách hàng')
@section('head')
<!-- Spinner và toast sẽ được render động -->

<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/chat-truc-tuyen.js"></script>
<script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/duyet-lich-goi-video.js"></script>
<style>
    .chatbox-fb-header {
        background: linear-gradient(90deg, #2563eb 60%, #60a5fa 100%);
        color: #fff;
        border-radius: 1rem 1rem 0 0;
    }

    .chatbox-fb-header span {
        color: #ffffff; /* Đảm bảo tất cả text trong header là màu trắng */
    }

    #chatbox-cinema {
        font-weight: 600;
    }

    #chatbox-topic {
        opacity: 0.9;
        font-size: 0.95rem;
    }

    #chatbox-sessionid {
        opacity: 0.7;
        font-size: 0.85rem;
    }

    .chatbox-fb-messages {
        background: #f3f6fa;
        min-height: 300px;
        max-height: 50vh;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .chatbox-fb-message {
        max-width: 75%;
        padding: 0.75rem 1rem;
        border-radius: 1.25rem;
        font-size: 1rem;
        word-break: break-word;
        margin-bottom: 0.5rem;
        background: #e5e7eb;
        color: #222;
        align-self: flex-start;
        border-bottom-left-radius: 0.25rem;
    }
    .chatbox-fb-message.staff {
        background: #2563eb;
        color: #fff;
        align-self: flex-end;
        border-bottom-right-radius: 0.25rem;
        border-bottom-left-radius: 1.25rem;
    }
    .chatbox-fb-message.user {
        background: #e5e7eb;
        color: #222;
        align-self: flex-start;
        border-bottom-left-radius: 0.25rem;
        border-bottom-right-radius: 1.25rem;
    }
    .chatbox-fb-message.system {
        background: #f3f4f6;
        color: #6b7280;
        font-style: italic;
        border-right: 3px solid #3b82f6;
        align-self: flex-end;
    }
    .chatbox-fb-message > div:last-child {
        line-height: 1.5;
        margin-top: 4px;
    }
    .message-time {
        font-size: 0.7rem;
        opacity: 0.6;
        margin-top: 4px;
    }
    #session-list .session-item {
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        padding: 1rem 1rem 0.75rem 1rem;
        transition: background 0.2s;
    }
    #session-list .session-item:hover, #session-list .session-item.active {
        background: #e0e7ff;
    }
    #session-list .session-topic {
        color: #2563eb;
        font-weight: 500;
        font-size: 0.98rem;
    }
    #session-list .session-id {
        color: #64748b;
        font-size: 0.85rem;
        margin-left: 0.5rem;
    }
    #session-list .session-status {
        font-size: 0.85rem;
        padding: 2px 8px;
        border-radius: 8px;
        margin-left: 0.5rem;
    }
    #session-list .status-waiting { background: #fef9c3; color: #b45309; }
    #session-list .status-active { background: #bbf7d0; color: #166534; }
    #session-list .status-closed { background: #e5e7eb; color: #6b7280; }

    /* Styling cho tin nhắn có ảnh */
.message-with-image {
  max-width: 250px;
}

.message-image-container {
  margin-top: 5px;
  border-radius: 8px;
  overflow: hidden;
  position: relative;
  max-width: 100%;
}

.message-image-container img {
  width: 100%;
  height: auto;
  object-fit: contain;
  max-height: 200px;
  background-color: rgba(0,0,0,0.03);
  transition: all 0.3s ease;
}

.message-image-container img:hover {
  cursor: pointer;
  opacity: 0.9;
}

/* Đánh dấu ảnh đang tải */
.image-uploading {
  position: relative;
}

.image-uploading::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255,255,255,0.7) url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="none" d="M0 0h24v24H0z"/><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z" opacity=".5"/><path d="M20 12h2A10 10 0 0 0 12 2v2a8 8 0 0 1 8 8z"><animateTransform attributeName="transform" dur="1s" from="0 12 12" repeatCount="indefinite" to="360 12 12" type="rotate"/></path></svg>') center no-repeat;
  background-size: 24px;
  border-radius: 8px;
}

/* Viewer cho ảnh lớn khi click */
#image-viewer {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.9);
  display: none;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

#image-viewer img {
  max-width: 90%;
  max-height: 90%;
  object-fit: contain;
}

#image-viewer .close {
  position: absolute;
  top: 20px;
  right: 20px;
  color: white;
  font-size: 30px;
  cursor: pointer;
}

/* Styles for image preview in chat form */
#image-preview.show {
  display: flex !important;
}
</style>
@endsection
@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-4 text-gray-500 font-medium">Tư vấn khách hàng</span>
    </div>
</li>
<li>
    <div class="flex items-center ml-4 space-x-2">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <div class="flex rounded-md shadow-sm">
            <button id="tab-btn-chat" class="tab-btn px-4 py-2 text-sm font-medium rounded-l-md bg-red-600 text-white" aria-current="page">
                Chat
            </button>
            <button id="tab-btn-video" class="tab-btn px-4 py-2 text-sm font-medium rounded-r-md border border-gray-200 bg-white text-gray-700">
                Gọi video
            </button>
        </div>
    </div>
</li>
@endsection

@section('content')
<div class="tab-container">
    <!-- Tab: Chat -->
    <div id="tab-chat" class="tab-content active" data-idNhanVien="{{$_SESSION['UserInternal']['ID']}}">
        <div class="flex flex-col md:flex-row gap-6 h-[70vh]">
            <!-- Danh sách phiên chat -->
            <div class="w-full md:w-1/3 bg-white rounded-xl shadow p-4 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-blue-700">Phiên chat đang mở</h2>
                    <button id="btn-refresh-sessions" class="text-blue-500 hover:text-blue-700 text-sm flex items-center gap-1">
                        <svg class="w-4 h-4 animate-spin hidden" id="session-refresh-spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" class="opacity-75"/>
                        </svg>
                        Làm mới
                    </button>
                </div>
                <div id="session-list" class="flex-1 overflow-y-auto border rounded-lg" data-url="{{$_ENV['URL_WEB_BASE']}}" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}">
                    <!-- Danh sách phiên chat sẽ được render ở đây -->
                </div>
            </div>

            <!-- Khu vực chat -->
            <div class="w-full md:w-2/3 bg-white rounded-xl shadow flex flex-col h-full">
                <div class="chatbox-fb-header flex items-center justify-between px-6 py-4 border-b">
                    <div>
                        <span id="chatbox-cinema" class="font-semibold text-blue-900"></span>
                        <span id="chatbox-topic" class="ml-2 text-sm text-blue-600"></span>
                        <span id="chatbox-sessionid" class="ml-2 text-xs text-gray-400"></span>
                    </div>
                    <button id="btn-close-chat" class="text-gray-400 hover:text-red-500 text-2xl font-bold">&times;</button>
                </div>
                <div id="chatbox-messages" class="chatbox-fb-messages flex-1 overflow-y-auto px-6 py-4 bg-gray-50 relative" style="min-height:300px;">
                    <div class="flex items-center justify-center h-full text-gray-400">Chọn một phiên chat để bắt đầu</div>
                </div>
                <form id="chatbox-form" class="flex flex-col p-3 border-t bg-white" style="display:none;">
                    <!-- Phần xem trước ảnh - simplified version -->
                    <div id="image-preview" style="display: none; margin-bottom: 10px; padding: 12px; background: #EFF6FF; border-radius: 8px; border: 1px solid #ddd; z-index: 100; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center;">
                            <div style="margin-right: 12px; width: 60px; height: 60px; border-radius: 8px; overflow: hidden; background-color: #f3f4f6; border: 1px solid #e5e7eb;">
                                <img id="preview-image" style="width: 100%; height: 100%; object-fit: cover;" src="" alt="Preview">
                            </div>
                            <div>
                                <p id="image-name" style="font-size: 0.875rem; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;">image.jpg</p>
                                <p id="image-size" style="font-size: 0.75rem; color: #6b7280;">0 KB</p>
                            </div>
                        </div>
                        <button id="remove-image" type="button" style="color: #ef4444; cursor: pointer; background: none; border: none; padding: 5px;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px;" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <label for="chatbox-upload" class="upload-btn flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <input id="chatbox-upload" type="file" accept="image/*" class="hidden">
                        </label>
                        
                        <input id="chatbox-input" type="text" class="flex-1 px-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Nhập tin nhắn...">
                        <button type="submit" class="bg-blue-600 text-white p-2 rounded-full w-10 h-10 flex items-center justify-center hover:bg-blue-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Tab: Gọi video -->
    <div id="tab-video" class="tab-content" style="display:none;">
        <div class="bg-white shadow-md rounded-lg" id="duyet-lich-goi-video-app" data-url="{{$_ENV['URL_WEB_BASE']}}" data-urlinternal="{{$_ENV['URL_INTERNAL_BASE']}}" >
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h2 class="text-xl font-semibold text-gray-800">Quản lý lịch gọi video tư vấn</h2>
                <p class="mt-1 text-sm text-gray-600">Danh sách yêu cầu gọi video từ khách hàng</p>
            </div>

            <!-- Danh sách lịch -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách hàng</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chủ đề</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian đặt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nhân viên</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="lich-table-body" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                Đang tải dữ liệu...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Image viewer -->
<div id="image-viewer" class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-90 z-50 hidden flex items-center justify-center">
    <button class="absolute top-4 right-4 text-white text-4xl">&times;</button>
    <img id="full-image" class="max-w-[90%] max-h-[90%] object-contain" src="" />
</div>

<script>
    // Tab switching logic với màu đỏ cho tab active
    document.addEventListener('DOMContentLoaded', function() {
        const tabChat = document.getElementById('tab-chat');
        const tabVideo = document.getElementById('tab-video');
        const btnChat = document.getElementById('tab-btn-chat');
        const btnVideo = document.getElementById('tab-btn-video');

        btnChat.addEventListener('click', function() {
            btnChat.classList.add('bg-red-600', 'text-white');
            btnChat.classList.remove('bg-white', 'text-gray-700');
            btnVideo.classList.remove('bg-red-600', 'text-white');
            btnVideo.classList.add('bg-white', 'text-gray-700');
            tabChat.classList.add('active');
            tabChat.style.display = '';
            tabVideo.classList.remove('active');
            tabVideo.style.display = 'none';
        });

        btnVideo.addEventListener('click', function() {
            btnVideo.classList.add('bg-red-600', 'text-white');
            btnVideo.classList.remove('bg-white', 'text-gray-700');
            btnChat.classList.remove('bg-red-600', 'text-white');
            btnChat.classList.add('bg-white', 'text-gray-700');
            tabVideo.classList.add('active');
            tabVideo.style.display = '';
            tabChat.classList.remove('active');
            tabChat.style.display = 'none';
            
            // Trigger event để duyet-lich-goi-video.js biết tab đã được mở
            const videoTabOpenedEvent = new Event('videoTabOpened');
            document.dispatchEvent(videoTabOpenedEvent);
        });
    });
</script>
@endsection
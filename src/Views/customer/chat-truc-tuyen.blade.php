<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Chat trực tuyến - EPIC CINEMAS</title>
  <link rel="stylesheet" href="{{$_ENV['URL_WEB_BASE']}}/css/tailwind.css">
  <script>
    window.config = {
      url: "{{ $_ENV['URL_WEB_BASE'] }}",
      socketUrl: "{{ $_ENV['URL_SERVER_REALTIME'] }}",
      urlServerMinio: "{{ $_ENV['MINIO_SERVER_URL'] }}",
    }
  </script>
  <script type="module" src="{{$_ENV['URL_WEB_BASE']}}/js/chat-truc-tuyen.js"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">
@include('customer.layout.header')


<main>
  <section class="container mx-auto max-w-screen-xl px-4 py-16">
    <h2 class="text-3xl font-bold text-center mb-10">Chat trực tuyến với rạp</h2>
    <hr class="border-t-2 border-blue-500 w-48 mx-auto mb-10">
    <!-- Modal tạo phiên chat -->
    <div id="modalCreateSession" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
      <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-8 relative">
        <button id="closeModalCreateSession" class="absolute top-3 right-4 text-2xl text-gray-400 hover:text-red-500">&times;</button>
        <h3 class="text-xl font-bold mb-6 text-center">Tạo phiên chat mới</h3>
        
        <div class="space-y-4">
          <div>
            <label for="cinemaSelect" class="block text-sm font-medium text-gray-700 mb-2">Chọn rạp phim</label>
            <select id="cinemaSelect" class="w-full p-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" data-url="{{$_ENV['URL_WEB_BASE']}}">
              <option value="">-- Chọn rạp --</option>
              @foreach($listRapPhim as $rapPhim)
                <option value="{{$rapPhim->id}}">{{$rapPhim->ten}}</option>
              @endforeach
            </select>
          </div>
          
          <div>
            <label for="chatTopic" class="block text-sm font-medium text-gray-700 mb-2">Chủ đề chat</label>
            <input type="text" id="chatTopic" placeholder="VD: Đặt vé phim, Hỏi về ưu đãi, Phản hồi dịch vụ..." 
                   class="w-full p-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   maxlength="100">
            <p class="text-xs text-gray-500 mt-1">Mô tả ngắn gọn nội dung bạn muốn trao đổi</p>
          </div>
        </div>
        
        <button id="startChatBtn" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition w-full mt-6">
          Bắt đầu chat
        </button>
      </div>
    </div>

    <!-- Danh sách phiên chat -->
    <div id="chatSessionList" class="max-w-md mx-auto mb-10">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-semibold">Danh sách phiên chat</h3>
        <button id="openModalCreateSession" class="bg-blue-600 text-white px-4 py-1 rounded-full font-semibold hover:bg-blue-700 transition">+ Tạo phiên mới</button>
      </div>
      <ul id="sessionListUl" class="divide-y divide-gray-200 bg-white rounded-xl shadow" data-url="{{$_ENV['URL_WEB_BASE']}}">
        <!-- Các phiên chat sẽ render ở đây -->
      </ul>
    </div>

    <!-- Chatbox Messenger style -->
    <div id="chatboxFb" class="chatbox-fb" style="display:none;">
      <div class="chatbox-fb-header flex items-center justify-between px-5 py-3 bg-gradient-to-r from-blue-700 to-blue-400 text-white">
        <span id="chatboxTitle" class="font-semibold text-lg">Chat với rạp</span>
        <button id="closeChatBtn" class="text-white text-2xl font-bold hover:text-red-200" title="Đóng">&times;</button>
      </div>
      <div id="chatboxMessages" class="chatbox-fb-messages flex-1 overflow-y-auto bg-gray-50 p-4 flex flex-col gap-2" data-urlminio="{{$_ENV['MINIO_SERVER_URL']}}"></div>
      <!-- Form gửi tin nhắn với chức năng upload ảnh -->
      <form id="chatboxForm" class="border-t bg-white p-3 flex flex-col">
        <!-- Phần xem trước ảnh -->
        <div id="image-preview" class="hidden p-3 flex items-center justify-between border-b">
          <div class="flex items-center">
            <div class="preview-image-container mr-3">
              <img id="preview-image" class="w-full h-full object-cover" src="" alt="Preview">
            </div>
            <div>
              <p id="image-name" class="text-sm font-medium truncate max-w-[200px]">image.jpg</p>
              <p id="image-size" class="text-xs text-gray-500">0 KB</p>
            </div>
          </div>
          <button id="remove-image" type="button" class="text-red-500 hover:text-red-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
        
        <!-- Input và nút gửi -->
        <div class="flex items-center">
          <label for="chatbox-upload" class="upload-btn p-2 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <input id="chatbox-upload" type="file" accept="image/*" class="hidden">
          </label>
          <input id="chatboxInput" type="text" class="flex-1 px-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Nhập tin nhắn...">
          <button type="submit" class="ml-2 bg-blue-600 text-white p-2 rounded-full w-10 h-10 flex items-center justify-center hover:bg-blue-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </div>
      </form>

      <!-- Image viewer -->
      <div id="image-viewer" class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-90 z-50 hidden flex items-center justify-center">
        <button class="absolute top-4 right-4 text-white text-4xl">&times;</button>
        <img id="full-image" class="max-w-[90%] max-h-[90%] object-contain" src="" />
      </div>

      <!-- Hiển thị hình ảnh đã chọn trước khi gửi -->
      <div id="image-preview" class="hidden p-2 border-t bg-gray-50 flex items-center justify-between">
        <div class="flex items-center">
          <div class="w-12 h-12 bg-gray-200 rounded overflow-hidden mr-2">
            <img id="preview-image" class="w-full h-full object-cover" src="#" alt="Preview">
          </div>
          <div>
            <p id="image-name" class="text-sm font-medium truncate max-w-[180px]">image.jpg</p>
            <p id="image-size" class="text-xs text-gray-500">0 KB</p>
          </div>
        </div>
        <button id="remove-image" class="text-red-500 hover:text-red-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Image viewer khi click vào ảnh -->
    <div id="image-viewer">
      <span class="close">&times;</span>
      <img id="full-image" src="">
    </div>
  </section>

  <div class="chat-container" id="notifyBox">
    <div class="chat-header">💬 Tin nhắn mới</div>
    <div id="messages" class="chat-messages"></div>
  </div>

</main>

<style>
          body { font-family: Arial, sans-serif; margin: 0; padding: 0; }

    /* Box mini chat thông báo */
    .chat-container {
      width: 320px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      display: none; /* Ẩn mặc định */
      flex-direction: column;
      overflow: hidden;
      position: fixed;
      bottom: 20px;
      right: 20px;
      opacity: 1;
      transition: opacity 0.5s;
      z-index: 9999;
    }

    .chat-header {
      background: #0084ff;
      color: white;
      padding: 10px;
      font-weight: bold;
      text-align: center;
      font-size: 14px;
    }

    .chat-messages {
      padding: 10px;
      background: #f9f9f9;
      min-height: 60px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .message {
      display: inline-block;
      padding: 8px 12px;
      border-radius: 18px;
      max-width: 80%;
      word-wrap: break-word;
      font-size: 14px;
    }
    .message.left {
      background: #e5e5ea;
      color: black;
      align-self: flex-start;
      border-bottom-left-radius: 4px;
    }
    .message.right {
      background: #0084ff;
      color: white;
      align-self: flex-end;
      border-bottom-right-radius: 4px;
    }
  .chatbox-fb {
    background: #fff;
    border-radius: 1.25rem;
    box-shadow: 0 4px 32px 0 rgba(37,99,235,0.10);
    border: 1px solid #e5e7eb;
    max-width: 420px;
    min-width: 320px;
    height: 600px; /* Thêm chiều cao cố định */
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 50;
    transition: box-shadow 0.2s;
  }
  .chatbox-fb-header {
    background: linear-gradient(90deg, #2563eb 60%, #60a5fa 100%);
    color: #fff;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
    font-size: 1.1rem;
  }
  .chatbox-fb-messages {
    flex: 1;
    overflow-y: auto; /* Giữ nguyên */
    overflow-x: hidden; /* Thêm để tránh scroll ngang */
    background: #f3f6fa;
    padding: 1.25rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    height: calc(100% - 120px); /* Tạo chiều cao tương đối, trừ đi chiều cao của header và form input */
    max-height: 100%; /* Đảm bảo không vượt quá chiều cao của container cha */
  }
  .chatbox-fb-input input {
    flex: 1;
    border: none;
    outline: none;
    background: #f3f6fa;
    border-radius: 1.25rem;
    padding: 0.5rem 1rem;
    font-size: 1rem;
  }
  .chatbox-fb-input button {
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 1.25rem;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    font-size: 1rem;
    transition: background 0.2s;
  }
  .chatbox-fb-input button:hover {
    background: #1d4ed8;
  }
  .chatbox-fb-message {
    max-width: 80%;
    padding: 0.75rem 1rem; /* Increased padding */
    border-radius: 1.25rem;
    font-size: 1rem;
    word-break: break-word;
    display: inline-block;
    margin-bottom: 0.5rem; /* Add margin between messages */
  }
  .chatbox-fb-message.user {
    background: #2563eb;
    color: #fff;
    align-self: flex-end;
    border-bottom-right-radius: 0.25rem;
  }
  .chatbox-fb-message.staff {
    background: #e5e7eb;
    color: #222;
    align-self: flex-start;
    border-bottom-left-radius: 0.25rem;
  }
  .chatbox-fb-message.system {
    background: #f3f4f6;
    color: #6b7280;
    font-style: italic;
    border-left: 3px solid #3b82f6;
  }
  
  /* Better spacing for message content */
  .chatbox-fb-message > div:last-child {
    line-height: 1.5;
    margin-top: 4px;
  }
  
  /* Message timestamp styling */
  .message-time {
    font-size: 0.7rem;
    opacity: 0.6;
    margin-top: 4px;
  }
  
  /* Spinner container inside chatbox */
  .chatbox-fb-messages .epic-customer-spinner-container {
    position: absolute;
    background: rgba(243, 246, 250, 0.9);
    backdrop-filter: blur(2px);
  }
  
  .chatbox-fb-messages .epic-spinner-wrapper {
    background: rgba(255, 255, 255, 0.98);
    padding: 20px;
    min-width: 150px;
  }
  
  /* Error state styling */
  .chatbox-error {
    color: #ef4444;
    text-align: center;
    padding: 20px;
  }

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
</style>

@include('customer.layout.footer')
<script>
    // Không cho hiển thị nút chatbot AI
    document.addEventListener('DOMContentLoaded', function() {
        // Mã JavaScript ở đây
        const btnOpenChat = document.getElementById('btn-open-chat');
        if (btnOpenChat) {
            btnOpenChat.style.display = 'none';
        }
    });
</script>
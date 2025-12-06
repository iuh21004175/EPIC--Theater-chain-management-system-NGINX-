<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đăng ký khuôn mặt</title>
    <link rel="stylesheet" href="{{ $_ENV['URL_INTERNAL_BASE'] }}/css/tailwind.css" />
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen" data-url="{{ $_ENV['URL_WEB_BASE'] }}">
    <main class="max-w-7xl mx-auto py-4 sm:py-6 lg:py-10 px-3 sm:px-4 lg:px-8">
        <!-- Header Card -->
        <div class="mb-4 sm:mb-6 bg-white rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-6 lg:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900">Đăng ký khuôn mặt</h1>
                    <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Thiết lập nhận diện khuôn mặt để chấm công nhanh chóng</p>
                </div>
                <button id="openGuideBtn" aria-label="Xem hướng dẫn" title="Xem hướng dẫn" class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2.5 sm:py-3 rounded-lg sm:rounded-xl bg-gradient-to-r from-green-600 to-green-700 text-white hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 transition-all duration-200 shadow-lg hover:shadow-xl font-medium text-sm sm:text-base">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z" />
                    </svg>
                    <span>Hướng dẫn</span>
                </button>
            </div>
        </div>

        <!-- Guide Modal Template -->
        <template id="infoModalTemplate">
            <div id="infoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full transform transition-all">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-green-100 rounded-xl">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 id="modalTitle" class="text-xl font-bold text-gray-900">Hướng dẫn trước khi đăng ký</h3>
                        </div>
                        
                        <div class="space-y-3 mb-8">
                            <div class="flex items-start gap-3 p-3 bg-blue-50 rounded-lg">
                                <svg class="h-5 w-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm text-gray-700">Chọn nơi có ánh sáng tốt và đều</span>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-green-50 rounded-lg">
                                <svg class="h-5 w-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm text-gray-700">Đảm bảo khuôn mặt được chiếu sáng đều</span>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-yellow-50 rounded-lg">
                                <svg class="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="text-sm text-gray-700">Tránh ánh sáng mạnh chiếu trực tiếp vào camera</span>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-purple-50 rounded-lg">
                                <svg class="h-5 w-5 text-purple-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-sm text-gray-700">Giữ điện thoại ổn định trong quá trình đăng ký</span>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-red-50 rounded-lg">
                                <svg class="h-5 w-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span class="text-sm text-gray-700">Đảm bảo không có vật cản che khuôn mặt</span>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-indigo-50 rounded-lg">
                                <svg class="h-5 w-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-sm text-gray-700">Camera chất lượng cao giúp cải thiện độ chính xác</span>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button id="modalOkBtn" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-semibold transition-all duration-200 shadow-lg">
                                Bắt đầu ngay
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Main Content Grid -->
        <div class=" gap-4 sm:gap-6">
            <!-- Camera Section (Left/Top) -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg overflow-hidden">
                    <!-- Camera Controls -->
                    <div class="p-3 sm:p-4 lg:p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-blue-100">
                        <div class="flex flex-col sm:flex-row sm:items-end gap-2 sm:gap-3">
                            <div class="flex-1">
                                <label for="cameraSelect" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                        <span>Chọn camera</span>
                                    </div>
                                </label>
                                <select id="cameraSelect" class="w-full border-2 border-blue-200 rounded-lg px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                                    <option value="">Tự động</option>
                                </select>
                            </div>
                            <button id="btnSelectCamera" class="px-4 sm:px-6 py-2 sm:py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium transition-all duration-200 shadow-md text-xs sm:text-sm">
                                Sử dụng
                            </button>
                        </div>
                    </div>

                    <!-- Video Container -->
                    <div class="relative bg-gray-900" style="width:100%; aspect-ratio:16/9;">
                        <video id="video" autoplay muted playsinline class="object-contain w-full h-full" playsinline></video>
                        <canvas id="overlay" class="absolute inset-0 w-full h-full pointer-events-none"></canvas>
                        
                        <!-- Camera Frame Overlay -->
                        <div class="absolute inset-0 pointer-events-none">
                            <div class="absolute inset-4 sm:inset-8 lg:inset-12 border-2 border-white/40 rounded-xl sm:rounded-2xl"></div>
                            <div class="absolute top-4 sm:top-8 lg:top-12 left-4 sm:left-8 lg:left-12 w-4 h-4 sm:w-6 sm:h-6 border-t-3 sm:border-t-4 border-l-3 sm:border-l-4 border-green-500 rounded-tl-lg"></div>
                            <div class="absolute top-4 sm:top-8 lg:top-12 right-4 sm:right-8 lg:right-12 w-4 h-4 sm:w-6 sm:h-6 border-t-3 sm:border-t-4 border-r-3 sm:border-r-4 border-green-500 rounded-tr-lg"></div>
                            <div class="absolute bottom-4 sm:bottom-8 lg:bottom-12 left-4 sm:left-8 lg:left-12 w-4 h-4 sm:w-6 sm:h-6 border-b-3 sm:border-b-4 border-l-3 sm:border-l-4 border-green-500 rounded-bl-lg"></div>
                            <div class="absolute bottom-4 sm:bottom-8 lg:bottom-12 right-4 sm:right-8 lg:right-12 w-4 h-4 sm:w-6 sm:h-6 border-b-3 sm:border-b-4 border-r-3 sm:border-r-4 border-green-500 rounded-br-lg"></div>
                        </div>
                    </div>

                    <!-- Face Notification -->
                    <div id="faceNotify" class="p-3 sm:p-4 lg:p-6 bg-gray-50 text-center">
                        <p class="text-sm sm:text-base lg:text-lg font-semibold text-gray-700"></p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="p-3 sm:p-4 lg:p-6 space-y-2 sm:space-y-3 bg-gray-50 border-t border-gray-200">
                        <button id="btnStartCapture" class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-3 sm:py-4 rounded-lg sm:rounded-xl hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-semibold text-base sm:text-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 active:scale-95">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Bắt đầu đăng ký</span>
                            </span>
                        </button>
                        <a href="{{ $_ENV['URL_INTERNAL_BASE'] }}/cham-cong" class="block w-full text-center bg-gray-200 text-gray-700 py-3 sm:py-4 rounded-lg sm:rounded-xl hover:bg-gray-300 focus:outline-none focus:ring-4 focus:ring-gray-300 font-semibold transition-all duration-200 text-sm sm:text-base">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                <span>Quay lại</span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Progress Section (Right/Bottom) -->
            <div class="lg:col-span-1">
                <div id="progressContainer" class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-6 space-y-4 sm:space-y-6 hidden lg:sticky lg:top-6">
                    <div class="flex items-center gap-2 sm:gap-3 pb-3 sm:pb-4 border-b border-gray-200">
                        <div class="p-1.5 sm:p-2 bg-blue-100 rounded-lg">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900">Tiến trình đăng ký</h3>
                    </div>

                    <!-- Quality Progress -->
                    <div class="space-y-2 sm:space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-xs sm:text-sm font-semibold text-gray-700">Chất lượng mẫu</span>
                            <span id="qualityRatio" class="text-xs sm:text-sm font-bold text-blue-600 bg-blue-50 px-2 sm:px-3 py-0.5 sm:py-1 rounded-full">0/100</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 sm:h-3 overflow-hidden shadow-inner">
                            <div id="qualityProgress" class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 sm:h-3 rounded-full transition-all duration-300 shadow-sm" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Face Detection Progress -->
                    <div class="space-y-2 sm:space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-xs sm:text-sm font-semibold text-gray-700">Phát hiện khuôn mặt</span>
                            <span id="faceRatio" class="text-xs sm:text-sm font-bold text-green-600 bg-green-50 px-2 sm:px-3 py-0.5 sm:py-1 rounded-full">0/100</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 sm:h-3 overflow-hidden shadow-inner">
                            <div id="faceProgress" class="bg-gradient-to-r from-green-500 to-green-600 h-2 sm:h-3 rounded-full transition-all duration-300 shadow-sm" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Spoof Detection Progress -->
                    <div class="space-y-2 sm:space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-xs sm:text-sm font-semibold text-gray-700">Phát hiện giả mạo</span>
                            <span id="faceDetected" class="text-xs sm:text-sm font-bold text-purple-600 bg-purple-50 px-2 sm:px-3 py-0.5 sm:py-1 rounded-full">0/1</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 sm:h-3 overflow-hidden shadow-inner">
                            <div id="spoofProgress" class="bg-gradient-to-r from-purple-500 to-purple-600 h-2 sm:h-3 rounded-full transition-all duration-300 shadow-sm" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Data Transfer Progress -->
                    <div class="space-y-2 sm:space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-xs sm:text-sm font-semibold text-gray-700">Chuyển đổi dữ liệu</span>
                            <span id="dataTransferred" class="text-xs sm:text-sm font-bold text-orange-600 bg-orange-50 px-2 sm:px-3 py-0.5 sm:py-1 rounded-full">0/1</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 sm:h-3 overflow-hidden shadow-inner">
                            <div id="dataProgress" class="bg-gradient-to-r from-orange-500 to-orange-600 h-2 sm:h-3 rounded-full transition-all duration-300 shadow-sm" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        (function() {
            const openBtn = document.getElementById('openGuideBtn');

            function attachCloseHandlers(modalEl) {
                const okBtn = modalEl.querySelector('#modalOkBtn');
                okBtn.focus();
                okBtn.addEventListener('click', () => {
                    if (modalEl && modalEl.parentNode) modalEl.parentNode.removeChild(modalEl);
                    document.documentElement.classList.remove('overflow-hidden');
                    document.removeEventListener('keydown', escHandler);
                });

                function escHandler(e) {
                    if (e.key === 'Escape' && document.documentElement.classList.contains('overflow-hidden')) {
                        if (modalEl && modalEl.parentNode) modalEl.parentNode.removeChild(modalEl);
                        document.documentElement.classList.remove('overflow-hidden');
                        document.removeEventListener('keydown', escHandler);
                    }
                }
                document.addEventListener('keydown', escHandler);
            }

            openBtn.addEventListener('click', () => {
                let modal = document.getElementById('infoModal');
                if (modal) {
                    modal.style.display = 'flex';
                    document.documentElement.classList.add('overflow-hidden');
                    const ok = modal.querySelector('#modalOkBtn');
                    if (ok) ok.focus();
                    return;
                }

                const tpl = document.getElementById('infoModalTemplate');
                if (!tpl) return;
                const clone = tpl.content.cloneNode(true);
                document.body.appendChild(clone);
                const newModal = document.getElementById('infoModal');
                if (newModal) {
                    document.documentElement.classList.add('overflow-hidden');
                    attachCloseHandlers(newModal);
                }
            });
            openBtn.click();
        })();
    </script>
    <script type="module" src="{{ $_ENV['URL_INTERNAL_BASE'] }}/js/dang-ky-khuon-mat.js"></script>
</body>
</html>
document.addEventListener('DOMContentLoaded', function() {
    const cinemaList = document.getElementById('cinema-list');
    const apiUrl = cinemaList.dataset.url + '/api/rap-phim';

    fetch(apiUrl)
        .then(res => res.json())
        .then(data => {
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                cinemaList.innerHTML = '';
                data.data.forEach((cinema, index) => {
                    const isHighlight = cinema.so_suat_chua_xem > 0;
                    
                    // Tạo card element
                    const card = document.createElement('div');
                    card.className = 'cinema-card group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl border-2 border-gray-100 hover:border-blue-200 cursor-pointer transition-all duration-300 overflow-hidden';
                    card.style.setProperty('--card-index', index);
                    
                    // Gradient overlay khi hover
                    const gradientOverlay = isHighlight 
                        ? 'from-red-50 to-rose-50'
                        : 'from-blue-50 to-indigo-50';
                    
                    card.innerHTML = `
                        <!-- Header Gradient -->
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${isHighlight ? 'from-red-500 via-rose-500 to-pink-500' : 'from-blue-500 via-indigo-500 to-purple-500'}"></div>
                        
                        <!-- Content -->
                        <div class="p-6">
                            <!-- Cinema Info -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-12 h-12 bg-gradient-to-br ${isHighlight ? 'from-red-100 to-rose-100' : 'from-blue-100 to-indigo-100'} rounded-xl flex items-center justify-center shadow-inner">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ${isHighlight ? 'text-red-600' : 'text-blue-600'}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900 group-hover:text-blue-700 transition-colors">${cinema.ten}</h3>
                                            ${isHighlight ? '<span class="inline-flex items-center gap-1 text-xs font-semibold text-red-600"><span class="w-1.5 h-1.5 bg-red-600 rounded-full animate-pulse"></span>Cần xem</span>' : ''}
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2 text-sm text-gray-600 ml-15">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 flex-shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="line-clamp-2">${cinema.dia_chi || 'Chưa có địa chỉ'}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Divider -->
                            <div class="my-4 border-t border-gray-200"></div>
                            
                            <!-- Badge Section -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Trạng thái</span>
                                </div>
                                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl font-bold text-sm shadow-md transition-all duration-300 ${
                                    isHighlight 
                                        ? 'bg-gradient-to-r from-red-600 to-rose-600 text-white animate-pulse group-hover:scale-105' 
                                        : 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white group-hover:scale-105'
                                }">
                                    ${isHighlight ? `
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    ` : ''}
                                    <span>${cinema.so_suat_chua_duyet} ${isHighlight ? 'suất chưa duyệt' : 'suất chiếu'}</span>
                                </div>
                            </div>
                            
                            <!-- Hover Arrow -->
                            <div class="absolute bottom-6 right-6 opacity-0 group-hover:opacity-100 transform translate-x-2 group-hover:translate-x-0 transition-all duration-300">
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full flex items-center justify-center shadow-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Background Pattern -->
                        <div class="absolute inset-0 bg-gradient-to-br ${gradientOverlay} opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                    `;
                    
                    // Click handler
                    card.addEventListener('click', () => {
                        window.location.href = `${cinemaList.dataset.url}/internal/duyet-suat-chieu/${cinema.id}`;
                    });
                    
                    cinemaList.appendChild(card);
                });
            } else {
                // Empty State
                cinemaList.innerHTML = `
                    <div class="col-span-full text-center py-20">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-3xl mb-6 shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Không có rạp phim nào</h3>
                        <p class="text-gray-600">Hiện tại chưa có rạp phim nào trong hệ thống</p>
                    </div>
                `;
            }
        })
        .catch((error) => {
            console.error('Error loading cinemas:', error);
            // Error State
            cinemaList.innerHTML = `
                <div class="col-span-full text-center py-20">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-red-100 to-rose-100 rounded-3xl mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Lỗi khi tải danh sách</h3>
                    <p class="text-gray-600 mb-6">Không thể tải danh sách rạp phim. Vui lòng thử lại sau.</p>
                    <button onclick="location.reload()" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Tải lại
                    </button>
                </div>
            `;
        });
});
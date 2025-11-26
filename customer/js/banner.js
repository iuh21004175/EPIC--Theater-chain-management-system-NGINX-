async function loadBanners(url) {
    try {
        const response = await fetch(`${url}/api/banner/side-show`);
        const data = await response.json();
        return Array.isArray(data.data) ? data.data : [];
    } catch (error) {
        console.error('Error fetching banners:', error);
        return [];
    }
}

document.addEventListener('DOMContentLoaded', async function () {
    const bannerTrack = document.getElementById('bannerContainer');
    if (!bannerTrack) return;

    const dotsContainer = document.getElementById('bannerDots');
    const prevBtn = document.getElementById('bannerPrev');
    const nextBtn = document.getElementById('bannerNext');

    const baseUrl = bannerTrack.dataset.url;
    const urlMinio = bannerTrack.dataset.urlminio;

    const banners = await loadBanners(baseUrl);
    if (!banners.length) {
        bannerTrack.innerHTML = '';
        return;
    }

    // Clear và render các ảnh - hiển thị trọn vẹn ảnh, canh giữa, không bị phóng to quá
    bannerTrack.innerHTML = '';
    banners.forEach((banner) => {
        const slide = document.createElement('div');
        // Flex để canh giữa ảnh theo cả 2 chiều, nền đen để che phần thừa (letterbox)
        slide.className = 'h-full flex-shrink-0 flex items-center justify-center bg-black';

        const img = document.createElement('img');
        img.src = `${urlMinio}/${banner.anh_url}`;
        img.alt = banner.ten || 'Banner';
        // Hiển thị full ảnh gốc theo tỉ lệ, chiều cao khớp khung, chiều ngang tự co
        img.className = 'h-full w-auto max-w-full object-contain';

        slide.appendChild(img);
        bannerTrack.appendChild(slide);
    });

    const total = banners.length;
    let currentIndex = 0;
    let autoTimer = null;

    // Thiết lập width track theo số slide và set width cho mỗi slide
    bannerTrack.style.width = `${total * 100}%`;
    const slides = bannerTrack.querySelectorAll('div');
    slides.forEach(slide => {
        slide.style.width = `${100 / total}%`;
    });

    // Tạo dots
    const dots = [];
    if (dotsContainer) {
        dotsContainer.innerHTML = '';
        for (let i = 0; i < total; i++) {
            const dot = document.createElement('span');
            dot.className =
                'w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white/70 cursor-pointer transition';
            dot.dataset.index = String(i);
            dot.addEventListener('click', () => {
                goToSlide(i);
                restartAutoSlide();
            });
            dotsContainer.appendChild(dot);
            dots.push(dot);
        }
    }

    function updateDots() {
        if (!dots.length) return;
        dots.forEach((dot, idx) => {
            if (idx === currentIndex) {
                dot.classList.remove('bg-white/40');
                dot.classList.add('bg-white');
            } else {
                dot.classList.remove('bg-white');
                dot.classList.add('bg-white/40');
            }
        });
    }

    function goToSlide(index) {
        if (!total) return;
        currentIndex = (index + total) % total;
        const offset = -(currentIndex * 100 / total);
        bannerTrack.style.transform = `translateX(${offset}%)`;
        updateDots();
    }

    function nextSlide() {
        goToSlide(currentIndex + 1);
    }

    function prevSlide() {
        goToSlide(currentIndex - 1);
    }

    function startAutoSlide() {
        if (autoTimer) return;
        autoTimer = setInterval(nextSlide, 7000); // 7s mỗi slide
    }

    function stopAutoSlide() {
        if (autoTimer) {
            clearInterval(autoTimer);
            autoTimer = null;
        }
    }

    function restartAutoSlide() {
        stopAutoSlide();
        startAutoSlide();
    }

    // Gắn event cho nút điều hướng
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            nextSlide();
            restartAutoSlide();
        });
    }
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            restartAutoSlide();
        });
    }

    // Tạm dừng auto khi hover vào banner
    const bannerSection = bannerTrack.closest('section');
    if (bannerSection) {
        bannerSection.addEventListener('mouseenter', stopAutoSlide);
        bannerSection.addEventListener('mouseleave', startAutoSlide);
    }

    // Khởi tạo slide đầu tiên và auto-play
    goToSlide(0);
    startAutoSlide();
});
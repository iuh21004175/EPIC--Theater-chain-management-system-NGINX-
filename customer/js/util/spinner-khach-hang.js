/**
 * Quản lý hiển thị spinner loading dành cho khách hàng EPIC Cinema
 * Thiết kế đẹp mắt với chủ đề rạp chiếu phim
 */
const CustomerSpinner = {
    /**
     * Tạo và hiển thị spinner cho khách hàng
     * @param {Object} options - Tùy chọn cấu hình spinner
     * @param {string} [options.target=document.body] - Phần tử chứa spinner
     * @param {string} [options.theme='blue'] - Chủ đề màu sắc: blue, red, gold, gradient
     * @param {string} [options.size='md'] - Kích thước: sm, md, lg, xl
     * @param {boolean} [options.overlay=true] - Hiển thị lớp phủ mờ
     * @param {string} [options.text=''] - Văn bản hiển thị
     * @param {string} [options.type='cinema'] - Loại spinner: cinema, film, ticket, popcorn
     * @returns {HTMLElement} Đối tượng spinner đã tạo
     */
    show: function(options = {}) {
        // Cấu hình mặc định
        const config = {
            target: options.target || document.body,
            theme: options.theme || 'blue',
            size: options.size || 'md',
            overlay: options.overlay !== undefined ? options.overlay : true,
            text: options.text || 'Đang tải...',
            type: options.type || 'cinema',
            fullScreen: options.target ? false : true
        };

        // Định nghĩa màu sắc theo chủ đề
        const themes = {
            blue: {
                primary: '#2563eb',
                secondary: '#3b82f6',
                accent: '#60a5fa',
                gradient: 'linear-gradient(135deg, #2563eb, #60a5fa)'
            },
            red: {
                primary: '#dc2626',
                secondary: '#ef4444',
                accent: '#f87171',
                gradient: 'linear-gradient(135deg, #dc2626, #f87171)'
            },
            gold: {
                primary: '#d97706',
                secondary: '#f59e0b',
                accent: '#fbbf24',
                gradient: 'linear-gradient(135deg, #d97706, #fbbf24)'
            },
            gradient: {
                primary: '#8b5cf6',
                secondary: '#a855f7',
                accent: '#c084fc',
                gradient: 'linear-gradient(135deg, #8b5cf6, #06b6d4, #10b981)'
            }
        };

        const currentTheme = themes[config.theme];

        // Xác định kích thước
        let spinnerSize = '48px';
        let iconSize = '24px';
        let fontSize = '14px';
        
        switch(config.size) {
            case 'sm':
                spinnerSize = '32px';
                iconSize = '16px';
                fontSize = '12px';
                break;
            case 'lg':
                spinnerSize = '64px';
                iconSize = '32px';
                fontSize = '16px';
                break;
            case 'xl':
                spinnerSize = '80px';
                iconSize = '40px';
                fontSize = '18px';
                break;
        }

        // Tạo container cho spinner
        const spinnerContainer = document.createElement('div');
        spinnerContainer.id = 'epic-customer-spinner-' + Date.now();
        spinnerContainer.className = 'epic-customer-spinner-container';
        
        // Thiết lập style cho container
        spinnerContainer.style.position = config.fullScreen ? 'fixed' : 'absolute';
        spinnerContainer.style.top = '0';
        spinnerContainer.style.left = '0';
        spinnerContainer.style.width = '100%';
        spinnerContainer.style.height = '100%';
        spinnerContainer.style.display = 'flex';
        spinnerContainer.style.flexDirection = 'column';
        spinnerContainer.style.alignItems = 'center';
        spinnerContainer.style.justifyContent = 'center';
        spinnerContainer.style.zIndex = '9999';
        spinnerContainer.style.fontFamily = '"Inter", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif';
        
        if (config.overlay) {
            spinnerContainer.style.background = 'rgba(0, 0, 0, 0.4)';
            spinnerContainer.style.backdropFilter = 'blur(4px)';
            spinnerContainer.style.pointerEvents = 'auto';
        } else {
            spinnerContainer.style.pointerEvents = 'none';
        }

        // Tạo wrapper với hiệu ứng fade in
        const spinnerWrapper = document.createElement('div');
        spinnerWrapper.className = 'epic-spinner-wrapper';
        spinnerWrapper.style.background = 'rgba(255, 255, 255, 0.95)';
        spinnerWrapper.style.borderRadius = '20px';
        spinnerWrapper.style.padding = '32px';
        spinnerWrapper.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';
        spinnerWrapper.style.display = 'flex';
        spinnerWrapper.style.flexDirection = 'column';
        spinnerWrapper.style.alignItems = 'center';
        spinnerWrapper.style.animation = 'epic-fade-in 0.3s ease-out';
        spinnerWrapper.style.minWidth = '200px';

        // Tạo spinner theo loại
        const spinnerElement = this.createSpinnerByType(config.type, spinnerSize, currentTheme);
        
        // Thêm spinner vào wrapper
        spinnerWrapper.appendChild(spinnerElement);
        
        // Thêm văn bản nếu có
        if (config.text) {
            const textElement = document.createElement('div');
            textElement.className = 'epic-spinner-text';
            textElement.textContent = config.text;
            textElement.style.marginTop = '20px';
            textElement.style.color = '#374151';
            textElement.style.fontSize = fontSize;
            textElement.style.fontWeight = '600';
            textElement.style.textAlign = 'center';
            textElement.style.animation = 'epic-pulse-text 2s ease-in-out infinite';
            spinnerWrapper.appendChild(textElement);
        }

        // Thêm logo EPIC (tùy chọn)
        const logoElement = document.createElement('div');
        logoElement.className = 'epic-spinner-logo';
        logoElement.innerHTML = '🎬';
        logoElement.style.fontSize = '20px';
        logoElement.style.marginBottom = '16px';
        logoElement.style.opacity = '0.7';
        logoElement.style.animation = 'epic-bounce 2s ease-in-out infinite';
        spinnerWrapper.insertBefore(logoElement, spinnerWrapper.firstChild);
        
        spinnerContainer.appendChild(spinnerWrapper);
        
        // Thêm CSS animations
        this.addCustomerSpinnerCSS();
        
        // Thêm vào target
        const targetElement = typeof config.target === 'string' 
            ? document.querySelector(config.target) 
            : config.target;
            
        if (targetElement === document.body) {
            spinnerContainer.style.position = 'fixed';
        } else {
            if (window.getComputedStyle(targetElement).position === 'static') {
                targetElement.style.position = 'relative';
            }
        }
        
        targetElement.appendChild(spinnerContainer);
        return spinnerContainer;
    },

    /**
     * Tạo spinner theo loại
     */
    createSpinnerByType: function(type, size, theme) {
        const container = document.createElement('div');
        container.style.position = 'relative';
        container.style.width = size;
        container.style.height = size;

        switch(type) {
            case 'cinema':
                return this.createCinemaSpinner(container, size, theme);
            case 'film':
                return this.createFilmSpinner(container, size, theme);
            case 'ticket':
                return this.createTicketSpinner(container, size, theme);
            case 'popcorn':
                return this.createPopcornSpinner(container, size, theme);
            default:
                return this.createCinemaSpinner(container, size, theme);
        }
    },

    /**
     * Tạo spinner chủ đề rạp chiếu phim
     */
    createCinemaSpinner: function(container, size, theme) {
        // Vòng tròn ngoài
        const outerRing = document.createElement('div');
        outerRing.style.width = size;
        outerRing.style.height = size;
        outerRing.style.border = `3px solid transparent`;
        outerRing.style.borderTop = `3px solid ${theme.primary}`;
        outerRing.style.borderRight = `3px solid ${theme.secondary}`;
        outerRing.style.borderRadius = '50%';
        outerRing.style.animation = 'epic-spin 1.5s linear infinite';
        outerRing.style.position = 'absolute';

        // Vòng tròn trong
        const innerRing = document.createElement('div');
        const innerSize = parseInt(size) * 0.7 + 'px';
        innerRing.style.width = innerSize;
        innerRing.style.height = innerSize;
        innerRing.style.border = `2px solid transparent`;
        innerRing.style.borderTop = `2px solid ${theme.accent}`;
        innerRing.style.borderLeft = `2px solid ${theme.secondary}`;
        innerRing.style.borderRadius = '50%';
        innerRing.style.animation = 'epic-spin-reverse 1s linear infinite';
        innerRing.style.position = 'absolute';
        innerRing.style.top = '50%';
        innerRing.style.left = '50%';
        innerRing.style.transform = 'translate(-50%, -50%)';

        // Icon giữa
        const centerIcon = document.createElement('div');
        centerIcon.innerHTML = '🎭';
        centerIcon.style.position = 'absolute';
        centerIcon.style.top = '50%';
        centerIcon.style.left = '50%';
        centerIcon.style.transform = 'translate(-50%, -50%)';
        centerIcon.style.fontSize = parseInt(size) * 0.4 + 'px';
        centerIcon.style.animation = 'epic-pulse 2s ease-in-out infinite';

        container.appendChild(outerRing);
        container.appendChild(innerRing);
        container.appendChild(centerIcon);
        return container;
    },

    /**
     * Tạo spinner chủ đề phim
     */
    createFilmSpinner: function(container, size, theme) {
        // Cuộn phim
        const filmReel = document.createElement('div');
        filmReel.style.width = size;
        filmReel.style.height = size;
        filmReel.style.border = `4px solid ${theme.primary}`;
        filmReel.style.borderRadius = '50%';
        filmReel.style.position = 'relative';
        filmReel.style.animation = 'epic-spin 2s linear infinite';

        // Các chấm trên cuộn phim
        for (let i = 0; i < 8; i++) {
            const dot = document.createElement('div');
            dot.style.width = '8px';
            dot.style.height = '8px';
            dot.style.background = theme.accent;
            dot.style.borderRadius = '50%';
            dot.style.position = 'absolute';
            dot.style.top = '50%';
            dot.style.left = '50%';
            dot.style.transform = `translate(-50%, -50%) rotate(${i * 45}deg) translateY(-${parseInt(size)/2 - 12}px)`;
            filmReel.appendChild(dot);
        }

        // Icon camera giữa
        const centerIcon = document.createElement('div');
        centerIcon.innerHTML = '🎥';
        centerIcon.style.position = 'absolute';
        centerIcon.style.top = '50%';
        centerIcon.style.left = '50%';
        centerIcon.style.transform = 'translate(-50%, -50%)';
        centerIcon.style.fontSize = parseInt(size) * 0.3 + 'px';

        container.appendChild(filmReel);
        container.appendChild(centerIcon);
        return container;
    },

    /**
     * Tạo spinner chủ đề vé
     */
    createTicketSpinner: function(container, size, theme) {
        // Vé xoay
        const ticket = document.createElement('div');
        ticket.style.width = parseInt(size) * 0.8 + 'px';
        ticket.style.height = parseInt(size) * 0.5 + 'px';
        ticket.style.background = theme.gradient;
        ticket.style.borderRadius = '8px';
        ticket.style.position = 'absolute';
        ticket.style.top = '50%';
        ticket.style.left = '50%';
        ticket.style.transform = 'translate(-50%, -50%)';
        ticket.style.animation = 'epic-flip 2s ease-in-out infinite';
        ticket.style.boxShadow = `0 4px 12px rgba(0,0,0,0.15)`;

        // Icon vé
        const ticketIcon = document.createElement('div');
        ticketIcon.innerHTML = '🎫';
        ticketIcon.style.position = 'absolute';
        ticketIcon.style.top = '50%';
        ticketIcon.style.left = '50%';
        ticketIcon.style.transform = 'translate(-50%, -50%)';
        ticketIcon.style.fontSize = parseInt(size) * 0.25 + 'px';

        ticket.appendChild(ticketIcon);
        container.appendChild(ticket);
        return container;
    },

    /**
     * Tạo spinner chủ đề bỏng ngô
     */
    createPopcornSpinner: function(container, size, theme) {
        // Container bỏng ngô
        const popcornBox = document.createElement('div');
        popcornBox.style.width = parseInt(size) * 0.7 + 'px';
        popcornBox.style.height = size;
        popcornBox.style.background = theme.gradient;
        popcornBox.style.borderRadius = '0 0 8px 8px';
        popcornBox.style.position = 'relative';
        popcornBox.style.animation = 'epic-bounce 1.5s ease-in-out infinite';

        // Bỏng ngô bay lên
        for (let i = 0; i < 3; i++) {
            const popcorn = document.createElement('div');
            popcorn.innerHTML = '🍿';
            popcorn.style.position = 'absolute';
            popcorn.style.fontSize = '12px';
            popcorn.style.animation = `epic-popcorn-${i} 2s ease-in-out infinite`;
            popcorn.style.animationDelay = `${i * 0.3}s`;
            popcornBox.appendChild(popcorn);
        }

        container.appendChild(popcornBox);
        return container;
    },

    /**
     * Thêm CSS animations tùy chỉnh cho khách hàng
     */
    addCustomerSpinnerCSS: function() {
        if (!document.getElementById('epic-customer-spinner-style')) {
            const styleElement = document.createElement('style');
            styleElement.id = 'epic-customer-spinner-style';
            styleElement.textContent = `
                @keyframes epic-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                @keyframes epic-spin-reverse {
                    0% { transform: translate(-50%, -50%) rotate(360deg); }
                    100% { transform: translate(-50%, -50%) rotate(0deg); }
                }
                
                @keyframes epic-pulse {
                    0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
                    50% { transform: translate(-50%, -50%) scale(1.1); opacity: 0.8; }
                }
                
                @keyframes epic-pulse-text {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.6; }
                }
                
                @keyframes epic-fade-in {
                    0% { opacity: 0; transform: scale(0.9); }
                    100% { opacity: 1; transform: scale(1); }
                }
                
                @keyframes epic-bounce {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-10px); }
                }
                
                @keyframes epic-flip {
                    0% { transform: translate(-50%, -50%) rotateY(0); }
                    50% { transform: translate(-50%, -50%) rotateY(180deg); }
                    100% { transform: translate(-50%, -50%) rotateY(360deg); }
                }
                
                @keyframes epic-popcorn-0 {
                    0% { bottom: 0; opacity: 0; transform: translateX(0); }
                    20% { opacity: 1; }
                    100% { bottom: 100%; opacity: 0; transform: translateX(-10px); }
                }
                
                @keyframes epic-popcorn-1 {
                    0% { bottom: 0; opacity: 0; transform: translateX(0); }
                    20% { opacity: 1; }
                    100% { bottom: 100%; opacity: 0; transform: translateX(0); }
                }
                
                @keyframes epic-popcorn-2 {
                    0% { bottom: 0; opacity: 0; transform: translateX(0); }
                    20% { opacity: 1; }
                    100% { bottom: 100%; opacity: 0; transform: translateX(10px); }
                }
            `;
            document.head.appendChild(styleElement);
        }
    },
    
    /**
     * Ẩn spinner
     * @param {HTMLElement|string} spinner - Đối tượng spinner hoặc ID
     */
    hide: function(spinner) {
        if (!spinner) {
            const spinners = document.querySelectorAll('.epic-customer-spinner-container');
            spinners.forEach(spin => {
                spin.style.animation = 'epic-fade-out 0.3s ease-out';
                setTimeout(() => spin.remove(), 300);
            });
            return;
        }
        
        const spinnerElement = typeof spinner === 'string' 
            ? document.getElementById(spinner) 
            : spinner;
            
        if (spinnerElement) {
            spinnerElement.style.animation = 'epic-fade-out 0.3s ease-out';
            setTimeout(() => spinnerElement.remove(), 300);
        }
    },
    
    /**
     * Hiển thị spinner trong khi thực hiện promise
     * @param {Promise} promise - Promise cần theo dõi
     * @param {Object} options - Tùy chọn spinner
     * @returns {Promise} Promise ban đầu
     */
    async during(promise, options = {}) {
        const defaultOptions = {
            text: 'Đang xử lý...',
            theme: 'blue',
            type: 'cinema'
        };
        
        const spinnerElement = this.show({...defaultOptions, ...options});
        try {
            const result = await promise;
            return result;
        } finally {
            this.hide(spinnerElement);
        }
    }
};

// Thêm fade-out animation
if (!document.getElementById('epic-fade-out-style')) {
    const fadeOutStyle = document.createElement('style');
    fadeOutStyle.id = 'epic-fade-out-style';
    fadeOutStyle.textContent = `
        @keyframes epic-fade-out {
            0% { opacity: 1; transform: scale(1); }
            100% { opacity: 0; transform: scale(0.9); }
        }
    `;
    document.head.appendChild(fadeOutStyle);
}

// Export spinner để sử dụng
export default CustomerSpinner;
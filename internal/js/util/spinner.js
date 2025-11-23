/**
 * Quản lý hiển thị spinner loading trong ứng dụng
 */
const Spinner = {
    /**
     * Tạo và hiển thị spinner
     * @param {Object} options - Tùy chọn cấu hình spinner
     * @param {string} [options.target=document.body] - Phần tử chứa spinner (mặc định là body)
     * @param {string} [options.color='#E11D48'] - Màu sắc spinner (mặc định là red-600)
     * @param {string} [options.size='md'] - Kích thước spinner: sm, md, lg
     * @param {string} [options.overlay=true] - Hiển thị lớp phủ mờ
     * @param {string} [options.text=''] - Văn bản hiển thị dưới spinner
     * @returns {HTMLElement} Đối tượng spinner đã tạo
     */
    show: function(options = {}) {
        // Cấu hình mặc định
        const config = {
            target: options.target || document.body,
            color: options.color || '#E11D48',
            size: options.size || 'md',
            overlay: options.overlay !== undefined ? options.overlay : true,
            text: options.text || '',
            fullScreen: options.target ? false : true
        };

        // Xác định kích thước
        let spinnerSize = '40px';
        let borderWidth = '4px';
        
        switch(config.size) {
            case 'sm':
                spinnerSize = '24px';
                borderWidth = '3px';
                break;
            case 'lg':
                spinnerSize = '60px';
                borderWidth = '6px';
                break;
        }

        // Tạo container cho spinner với ID duy nhất
        const timestamp = Date.now();
        const spinnerContainer = document.createElement('div');
        spinnerContainer.id = 'epic-spinner-' + timestamp;
        spinnerContainer.className = 'epic-spinner-container';
        
        // Thiết lập style cho container
        spinnerContainer.style.position = config.fullScreen ? 'fixed' : 'absolute';
        spinnerContainer.style.top = '0';
        spinnerContainer.style.left = '0';
        spinnerContainer.style.width = '100%';
        spinnerContainer.style.height = '100%';
        spinnerContainer.style.display = 'flex';
        spinnerContainer.style.flexDirection = 'column';
        spinnerContainer.style.alignItems = 'center';
        spinnerContainer.style.justifyContent = 'center'; // căn giữa chiều dọc
        spinnerContainer.style.zIndex = '9999';
        
        if (config.overlay) {
            spinnerContainer.style.backgroundColor = 'rgba(255, 255, 255, 0.8)'; // Tăng độ mờ lên 0.8
            spinnerContainer.style.backdropFilter = 'blur(2px)'; // Thêm hiệu ứng mờ
            spinnerContainer.style.pointerEvents = 'auto'; // Cho phép bắt sự kiện để ngăn tương tác
        } else {
            spinnerContainer.style.pointerEvents = 'none';
        }

        // Tạo container bên trong để cải thiện hiển thị spinner
        const spinnerInner = document.createElement('div');
        spinnerInner.style.position = 'relative';
        spinnerInner.style.display = 'inline-flex';
        spinnerInner.style.alignItems = 'center';
        spinnerInner.style.justifyContent = 'center';
        spinnerInner.style.flexShrink = '0';
        spinnerInner.style.flexGrow = '0';
        
        // Tính toán kích thước container để đảm bảo hình vuông hoàn hảo
        const padding = 15;
        const sizeNum = parseInt(spinnerSize);
        // Với box-sizing: border-box, padding được tính vào width/height
        const innerSize = sizeNum + (padding * 2);
        spinnerInner.style.width = innerSize + 'px';
        spinnerInner.style.height = innerSize + 'px';
        spinnerInner.style.minWidth = innerSize + 'px';
        spinnerInner.style.minHeight = innerSize + 'px';
        spinnerInner.style.maxWidth = innerSize + 'px';
        spinnerInner.style.maxHeight = innerSize + 'px';
        spinnerInner.style.aspectRatio = '1 / 1';
        spinnerInner.style.boxSizing = 'border-box';
        spinnerInner.style.margin = '0';
        spinnerInner.style.padding = padding + 'px';
        spinnerInner.style.borderRadius = '50%';
        spinnerInner.style.boxShadow = '0 0 15px rgba(0, 0, 0, 0.1)';
        spinnerInner.style.backgroundColor = 'white';
        spinnerInner.style.overflow = 'hidden'; // Đảm bảo nội dung không tràn ra ngoài
        
        // Tạo spinner - đảm bảo luôn tròn hoàn hảo
        const spinner = document.createElement('div');
        spinner.className = 'epic-spinner';
        // Sử dụng số nguyên đã tính toán ở trên để tránh subpixel rendering
        spinner.style.width = sizeNum + 'px';
        spinner.style.height = sizeNum + 'px';
        spinner.style.minWidth = sizeNum + 'px';
        spinner.style.minHeight = sizeNum + 'px';
        spinner.style.maxWidth = sizeNum + 'px';
        spinner.style.maxHeight = sizeNum + 'px';
        spinner.style.aspectRatio = '1 / 1';
        spinner.style.boxSizing = 'border-box';
        spinner.style.flexShrink = '0';
        spinner.style.flexGrow = '0';
        spinner.style.margin = '0';
        spinner.style.padding = '0';
        // Set border để đảm bảo tròn hoàn hảo
        spinner.style.border = `${borderWidth} solid rgba(0, 0, 0, 0.1)`;
        spinner.style.borderTop = `${borderWidth} solid ${config.color}`;
        spinner.style.borderRight = `${borderWidth} solid ${config.color}`;
        spinner.style.borderBottom = `${borderWidth} solid rgba(0, 0, 0, 0.1)`;
        spinner.style.borderLeft = `${borderWidth} solid rgba(0, 0, 0, 0.1)`;
        spinner.style.borderRadius = '50%';
        spinner.style.animation = 'epic-spin 1s cubic-bezier(0.55, 0.25, 0.25, 0.7) infinite';
        spinner.style.transform = 'translateZ(0)'; // Force hardware acceleration và đảm bảo render đúng
        spinner.style.display = 'block';
        spinner.style.verticalAlign = 'middle';
        spinner.style.lineHeight = '0';
        spinner.style.willChange = 'transform'; // Tối ưu hiệu năng animation
        spinner.style.backfaceVisibility = 'hidden'; // Đảm bảo render mượt
        spinner.style.WebkitBackfaceVisibility = 'hidden';
        spinner.style.outline = 'none'; // Loại bỏ outline
        spinner.style.overflow = 'hidden'; // Đảm bảo không có gì tràn ra
        
        // Thêm spinner vào container bên trong
        spinnerInner.appendChild(spinner);
        
        // Thêm container bên trong vào container chính
        spinnerContainer.appendChild(spinnerInner);
        
        // Thêm văn bản nếu có
        if (config.text) {
            const textElement = document.createElement('div');
            textElement.className = 'epic-spinner-text';
            textElement.textContent = config.text;
            textElement.style.marginTop = '15px';
            textElement.style.color = '#111827'; // Tối hơn để dễ nhìn
            textElement.style.fontSize = '15px';
            textElement.style.fontWeight = '600';
            textElement.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
            textElement.style.padding = '5px 12px';
            textElement.style.borderRadius = '4px';
            textElement.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.1)';
            spinnerContainer.appendChild(textElement);
        }
        
        // Thêm animation CSS nếu chưa có
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
        
        // Thêm vào target
        const targetElement = typeof config.target === 'string' 
            ? document.querySelector(config.target) 
            : config.target;
            
        if (targetElement === document.body) {
            // Nếu target là body, đảm bảo position relative cho spinner
            spinnerContainer.style.position = 'fixed';
        } else {
            // Đảm bảo container có position để định vị spinner
            if (window.getComputedStyle(targetElement).position === 'static') {
                targetElement.style.position = 'relative';
            }
        }
        
        targetElement.appendChild(spinnerContainer);
        return spinnerContainer;
    },
    
    /**
     * Ẩn spinner theo ID
     * @param {HTMLElement|string} spinner - Đối tượng spinner hoặc ID của spinner
     */
    hide: function(spinner) {
        if (!spinner) {
            // Nếu không chỉ định, xóa tất cả spinner
            const spinners = document.querySelectorAll('.epic-spinner-container');
            spinners.forEach(spin => spin.remove());
            return;
        }
        
        // Xác định đối tượng spinner
        const spinnerElement = typeof spinner === 'string' 
            ? document.getElementById(spinner) 
            : spinner;
            
        if (spinnerElement) {
            spinnerElement.remove();
        }
    },
    
    /**
     * Tạo spinner trong khi thực hiện một promise
     * @param {Promise} promise - Promise cần theo dõi
     * @param {Object} options - Tùy chọn spinner
     * @returns {Promise} Promise ban đầu
     */
    async during(promise, options = {}) {
        const spinnerElement = this.show(options);
        try {
            const result = await promise;
            return result;
        } finally {
            this.hide(spinnerElement);
        }
    }
};

// Export spinner để sử dụng ở các module khác
export default Spinner;
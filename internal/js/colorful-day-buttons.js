// Mã JavaScript để thêm các button ngày màu sắc nổi bật
document.addEventListener('DOMContentLoaded', function() {
    // Thêm CSS động cho các button ngày
    const style = document.createElement('style');
    style.textContent = `
        /* Button Calendar Styling */
        .plan-day-btn {
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        /* Normal state styling */
        .plan-day-btn:not([class*="bg-"]) {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        
        /* Hover effect */
        .plan-day-btn:hover:not([class*="bg-"]) {
            background-color: #f3f4f6;
            border-color: #d1d5db;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Selected button style */
        .plan-day-btn[class*="bg-"] {
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            transform: translateY(-1px);
            font-weight: 600;
        }
        
        /* Style for each day of the week */
        .day-t2 { border-left: 3px solid #3b82f6 !important; }
        .day-t2.active { background-color: #3b82f6 !important; border-color: #3b82f6 !important; }
        
        .day-t3 { border-left: 3px solid #10b981 !important; }
        .day-t3.active { background-color: #10b981 !important; border-color: #10b981 !important; }
        
        .day-t4 { border-left: 3px solid #8b5cf6 !important; }
        .day-t4.active { background-color: #8b5cf6 !important; border-color: #8b5cf6 !important; }
        
        .day-t5 { border-left: 3px solid #f59e0b !important; }
        .day-t5.active { background-color: #f59e0b !important; border-color: #f59e0b !important; }
        
        .day-t6 { border-left: 3px solid #ef4444 !important; }
        .day-t6.active { background-color: #ef4444 !important; border-color: #ef4444 !important; }
        
        .day-t7 { border-left: 3px solid #6366f1 !important; }
        .day-t7.active { background-color: #6366f1 !important; border-color: #6366f1 !important; }
        
        .day-cn { border-left: 3px solid #ec4899 !important; }
        .day-cn.active { background-color: #ec4899 !important; border-color: #ec4899 !important; }
    `;
    document.head.appendChild(style);
    
    // Patch the original renderDaySelector function
    const originalRenderDaySelector = window.renderDaySelector;
    
    if (typeof originalRenderDaySelector === 'function') {
        window.renderDaySelector = function() {
            const daySelector = document.getElementById('plan-day-selector');
            if (!daySelector || !window.currentPlanWeekStart) return;
            
            daySelector.innerHTML = '';
            const days = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
            const dayClasses = ['day-t2', 'day-t3', 'day-t4', 'day-t5', 'day-t6', 'day-t7', 'day-cn'];
            
            for (let i = 0; i < 7; i++) {
                const date = new Date(window.currentPlanWeekStart);
                date.setDate(window.currentPlanWeekStart.getDate() + i);
                const dateStr = window.formatDate(date);
                
                const dayBtn = document.createElement('button');
                dayBtn.type = 'button';
                const isSelected = i === 0;
                
                // Add base classes and day-specific class
                dayBtn.className = `plan-day-btn px-3 py-3 border rounded-lg text-sm shadow-sm ${dayClasses[i]} ${isSelected ? 'active text-white' : ''}`;
                dayBtn.dataset.date = dateStr;
                dayBtn.dataset.dayClass = dayClasses[i];
                
                dayBtn.innerHTML = `
                    <div class="font-medium text-base">${days[i]}</div>
                    <div class="${isSelected ? 'text-white' : 'text-gray-600'} mt-1">${date.getDate()}/${date.getMonth() + 1}</div>
                `;
                
                dayBtn.addEventListener('click', function() {
                    // Remove active class from all buttons
                    document.querySelectorAll('.plan-day-btn').forEach(btn => {
                        btn.classList.remove('active', 'text-white');
                        const dateText = btn.querySelector('div:nth-child(2)');
                        if (dateText) dateText.classList.remove('text-white');
                        if (dateText) dateText.classList.add('text-gray-600');
                    });
                    
                    // Add active class to clicked button
                    this.classList.add('active', 'text-white');
                    const dateText = this.querySelector('div:nth-child(2)');
                    if (dateText) {
                        dateText.classList.remove('text-gray-600');
                        dateText.classList.add('text-white');
                    }
                    
                    // Lưu ngày đã chọn và tải danh sách suất chiếu
                    window.currentSelectedDate = dateStr;
                    window.loadShowtimesByDate(dateStr);
                });
                
                daySelector.appendChild(dayBtn);
            }
            
            // Đặt ngày mặc định là thứ 2
            if (window.currentPlanWeekStart) {
                window.currentSelectedDate = window.formatDate(window.currentPlanWeekStart);
                window.loadShowtimesByDate(window.currentSelectedDate);
            }
        };
    }
});
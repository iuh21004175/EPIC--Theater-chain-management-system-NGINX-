import Spinner from './util/spinner.js';

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const calendarBody = document.getElementById('calendar-body');
    const currentMonthYearElement = document.getElementById('current-month-year');
    const prevMonthButton = document.getElementById('prev-month');
    const nextMonthButton = document.getElementById('next-month');
    const dateModal = document.getElementById('date-modal');
    const closeModalButton = document.getElementById('close-modal');
    const dateForm = document.getElementById('date-form');
    const selectedDateInput = document.getElementById('selected-date');
    const displayDateElement = document.getElementById('display-date');
    const dateTypeSelect = document.getElementById('date-type');
    const specialDayNameContainer = document.getElementById('special-day-name-container');
    const specialDayNameInput = document.getElementById('special-day-name');
    const specialDayError = document.getElementById('special-day-error');
    const saveButton = document.getElementById('save-date');
    const toastNotification = document.getElementById('toast-notification');
    
    // Current date and displayed month/year
    const today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();
    
    // Database of date labels (would be populated from server)
    let dateLabelDatabase = {};
    
    // Initialize calendar
    generateCalendar(currentMonth, currentYear);
    updateMonthYearDisplay();
    
    // Event listeners for navigation
    prevMonthButton.addEventListener('click', showPreviousMonth);
    nextMonthButton.addEventListener('click', showNextMonth);
    
    // Event listener for date type changes
    dateTypeSelect.addEventListener('change', handleDateTypeChange);
    
    // Event listener for save button
    saveButton.addEventListener('click', saveDate);
    
    // Event listener for close modal
    closeModalButton.addEventListener('click', closeModal);
    
    // Function to generate calendar
    function generateCalendar(month, year) {
        // Clear existing calendar
        calendarBody.innerHTML = '';
        
        // Get first day of month and number of days
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        let date = 1;
        const rows = Math.ceil((firstDay + daysInMonth) / 7);
        
        // Build the calendar
        for (let i = 0; i < rows; i++) {
            const row = document.createElement('tr');
            
            for (let j = 0; j < 7; j++) {
                const cell = document.createElement('td');
                cell.classList.add('border', 'p-2', 'h-24', 'w-1/7');
                
                if (i === 0 && j < firstDay) {
                    // Empty cells before the first day of the month
                    cell.classList.add('bg-gray-50');
                } else if (date > daysInMonth) {
                    // Empty cells after the last day of the month
                    cell.classList.add('bg-gray-50');
                } else {
                    // Valid date cells
                    const dateValue = new Date(year, month, date);
                    const dateString = formatDateToYMD(dateValue);
                    const isFutureDate = dateValue > new Date(today.getFullYear(), today.getMonth(), today.getDate());
                    
                    cell.textContent = date;
                    cell.dataset.date = dateString;
                    
                    // Style cell based on date label
                    styleCell(cell, dateString);
                    
                    // Make future dates clickable for label assignment
                    if (isFutureDate) {
                        cell.classList.add('cursor-pointer', 'hover:bg-gray-100');
                        cell.addEventListener('click', function() {
                            openDateModal(dateString);
                        });
                    } else {
                        cell.classList.add('bg-gray-300', 'text-gray-600');
                    }
                    
                    date++;
                }
                
                row.appendChild(cell);
            }
            
            calendarBody.appendChild(row);
        }
    }
    
    // Function to update month and year display
    function updateMonthYearDisplay() {
        const months = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
        currentMonthYearElement.textContent = `${months[currentMonth]}, ${currentYear}`;
    }
    
    // Function to show previous month
    function showPreviousMonth() {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        loadDateLabels();
        updateMonthYearDisplay();
    }
    
    // Function to show next month
    function showNextMonth() {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        loadDateLabels();
        updateMonthYearDisplay();
    }
    
    // Function to style cell based on date label
    function styleCell(cell, dateString) {
        if (dateLabelDatabase[dateString]) {
            const label = dateLabelDatabase[dateString];
            let type = label.type;
            if (type === 'tet' || type === 'Ngày tết') type = 'Ngày tết';
            else if (type === 'holiday' || type === 'Ngày lễ') type = 'Ngày lễ';
            else type = 'Ngày thường';
            if (type === 'Ngày lễ') {
                cell.classList.add('bg-blue-200');
                const tooltip = document.createElement('div');
                tooltip.classList.add('text-xs', 'mt-1', 'text-blue-600', 'font-medium');
                tooltip.textContent = label.name;
                cell.appendChild(tooltip);
            } else if (type === 'Ngày tết') {
                cell.classList.add('bg-red-200');
                const tooltip = document.createElement('div');
                tooltip.classList.add('text-xs', 'mt-1', 'text-red-600', 'font-medium');
                tooltip.textContent = label.name;
                cell.appendChild(tooltip);
            }
        }
    }
    
    // Function to open date modal
    function openDateModal(dateString) {
        const dateObj = new Date(dateString);
        const formattedDate = formatDateDisplay(dateObj);
        selectedDateInput.value = dateString;
        displayDateElement.textContent = formattedDate;
        // Set date type and special day name if exists
        if (dateLabelDatabase[dateString]) {
            let type = dateLabelDatabase[dateString].type;
            if (type === 'tet' || type === 'Ngày tết') type = 'Ngày tết';
            else if (type === 'holiday' || type === 'Ngày lễ') type = 'Ngày lễ';
            else type = 'Ngày thường';
            dateTypeSelect.value = type;
            specialDayNameInput.value = dateLabelDatabase[dateString].name || '';
        } else {
            dateTypeSelect.value = 'Ngày thường';
            specialDayNameInput.value = '';
        }
        handleDateTypeChange();
        dateModal.classList.remove('hidden');
    }
    
    // Function to close date modal
    function closeModal() {
        dateModal.classList.add('hidden');
        specialDayError.classList.add('hidden');
    }
    
    // Function to handle date type change
    function handleDateTypeChange() {
        if (dateTypeSelect.value === 'Ngày lễ' || dateTypeSelect.value === 'Ngày tết') {
            specialDayNameContainer.classList.remove('hidden');
        } else {
            specialDayNameContainer.classList.add('hidden');
        }
    }
    
    // Function to save date
    function saveDate() {
        const dateString = selectedDateInput.value;
        let dateType = dateTypeSelect.value;
        if (dateType === 'Ngày tết') dateType = 'Ngày tết';
        else if (dateType === 'Ngày lễ') dateType = 'Ngày lễ';
        else dateType = 'Ngày thường';
        const specialDayName = specialDayNameInput.value.trim();
        
        // Validate
        if ((dateType === 'Ngày lễ' || dateType === 'Ngày tết') && !specialDayName) {
            specialDayError.classList.remove('hidden');
            return;
        } else {
            specialDayError.classList.add('hidden');
        }
        const spinner = Spinner.show({text: 'Đang lưu...'});
        fetch(`${calendarBody.dataset.url}/api/gan-ngay`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ngay: dateString,
                loai_ngay: dateType,
                dac_biet: specialDayName
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal();
                loadDateLabels();
                showToast();
            } else {
                alert(data.message || 'Có lỗi xảy ra khi lưu ngày');
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra khi lưu ngày');
            console.error('Error:', error);
        })
        .finally(() => {
            Spinner.hide(spinner);
        });
    }
    
    // Function to show success toast
    function showToast() {
        toastNotification.classList.remove('translate-y-20', 'opacity-0');
        
        setTimeout(() => {
            toastNotification.classList.add('translate-y-20', 'opacity-0');
        }, 3000);
    }
    
    // Utility function to format date to YYYY-MM-DD
    function formatDateToYMD(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // Utility function to format date for display
    function formatDateDisplay(date) {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }
    
    // Load date labels from server
    function loadDateLabels() {
        const thang = currentMonth + 1;
        const nam = currentYear;
        const spinner = Spinner.show({text: 'Đang tải dữ liệu...'});
        fetch(`${calendarBody.dataset.url}/api/gan-ngay/${thang}-${nam}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                    dateLabelDatabase = {};
                    data.data.forEach(item => {
                        dateLabelDatabase[item.ngay] = {
                            type: item.loai_ngay,
                            name: item.dac_biet || ''
                        };
                    });
                }
                generateCalendar(currentMonth, currentYear);
            })
            .catch(error => {
                console.error('Error:', error);
                generateCalendar(currentMonth, currentYear);
            })
            .finally(() => {
                Spinner.hide(spinner);
            });
    }
    
    // Initial load of date labels
    loadDateLabels();
});
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('dinh-vi-form');
    const btnSubmit = document.getElementById('btn-submit');
    const btnReset = document.getElementById('btn-reset');
    const spinner = document.getElementById('spinner');
    const btnText = document.getElementById('btn-text');
    const wifiIpInput = document.getElementById('wifiIp');
    const wifiTenInput = document.getElementById('wifiTen');
    const wifiIpError = document.getElementById('wifiIp-error');
    const wifiTenError = document.getElementById('wifiTen-error');
    
    // Lấy URL base từ data attribute của form
    const urlBase = form?.dataset?.url || window.location.origin;
    const apiUrl = `${urlBase}/api/thong-tin-dinh-vi`;
    
    // Lưu giá trị ban đầu để reset
    const initialValues = {
        wifiIp: wifiIpInput.value,
        wifiTen: wifiTenInput.value
    };
    
    // Validate form
    function validateForm() {
        let isValid = true;
        
        // Validate WiFi IP
        if (!wifiIpInput.value.trim()) {
            wifiIpError.classList.remove('hidden');
            isValid = false;
        } else {
            wifiIpError.classList.add('hidden');
        }
        
        // Validate WiFi Name
        if (!wifiTenInput.value.trim()) {
            wifiTenError.classList.remove('hidden');
            isValid = false;
        } else {
            wifiTenError.classList.add('hidden');
        }
        
        return isValid;
    }
    
    // Handle form submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        // Disable button and show loading
        btnSubmit.disabled = true;
        spinner.classList.remove('hidden');
        btnText.textContent = 'Đang cập nhật...';
        
        // Prepare form data
        const formData = new FormData();
        formData.append('wifiIp', wifiIpInput.value.trim());
        formData.append('wifiTen', wifiTenInput.value.trim());
        
        // Make API call
        fetch(apiUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Re-enable button
            btnSubmit.disabled = false;
            spinner.classList.add('hidden');
            btnText.textContent = 'Cập nhật thông tin';
            
            if (data.success) {
                // Show success message
                alert(data.message || 'Cập nhật thông tin định vị thành công!');
                
                // Update initial values
                initialValues.wifiIp = wifiIpInput.value;
                initialValues.wifiTen = wifiTenInput.value;
            } else {
                // Show error message
                alert(data.message || 'Cập nhật thông tin định vị thất bại. Vui lòng thử lại!');
            }
        })
        .catch(error => {
            // Re-enable button
            btnSubmit.disabled = false;
            spinner.classList.add('hidden');
            btnText.textContent = 'Cập nhật thông tin';
            
            // Show error message
            alert('Đã xảy ra lỗi khi kết nối với máy chủ. Vui lòng thử lại sau!');
            console.error('Error:', error);
        });
    });
    
    // Handle reset button
    btnReset.addEventListener('click', function() {
        if (confirm('Bạn có chắc chắn muốn đặt lại các thay đổi không?')) {
            wifiIpInput.value = initialValues.wifiIp;
            wifiTenInput.value = initialValues.wifiTen;
            wifiIpError.classList.add('hidden');
            wifiTenError.classList.add('hidden');
        }
    });
    
    // Real-time validation
    wifiIpInput.addEventListener('blur', function() {
        if (!this.value.trim()) {
            wifiIpError.classList.remove('hidden');
        } else {
            wifiIpError.classList.add('hidden');
        }
    });
    
    wifiTenInput.addEventListener('blur', function() {
        if (!this.value.trim()) {
            wifiTenError.classList.remove('hidden');
        } else {
            wifiTenError.classList.add('hidden');
        }
    });
});


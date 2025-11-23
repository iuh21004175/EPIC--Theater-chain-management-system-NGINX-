import Spinner from './util/spinner.js';

document.addEventListener('DOMContentLoaded', function() {
    // Modals
    const modals = {
        addCinema: document.getElementById('add-cinema-modal'),
        editCinema: document.getElementById('edit-cinema-modal'),
        confirmStatus: document.getElementById('confirm-status-modal')
    };
    
    // Table body element for cinema list
    const tableBody = document.querySelector('table tbody');
    
    // Helper function to extract Google Maps embed URL from iframe HTML or return URL as-is
    function extractMapUrlFromIframe(input) {
        const value = input.trim();
        if (!value) return '';
        
        // If it's already a valid embed URL, return as-is
        if (value.startsWith('https://www.google.com/maps/embed') || value.startsWith('http://www.google.com/maps/embed')) {
            return value;
        }
        
        // Try to extract URL from iframe HTML
        // Match src="..." or src='...' in iframe tag
        const iframeMatch = value.match(/<iframe[^>]+src=["']([^"']+)["'][^>]*>/i);
        if (iframeMatch && iframeMatch[1]) {
            const extractedUrl = iframeMatch[1].trim();
            // Verify it's a Google Maps embed URL
            if (extractedUrl.startsWith('https://www.google.com/maps/embed') || extractedUrl.startsWith('http://www.google.com/maps/embed')) {
                return extractedUrl;
            }
        }
        
        // Try to match src attribute directly (for partial iframe code)
        const srcMatch = value.match(/src=["']([^"']*google\.com\/maps\/embed[^"']*)["']/i);
        if (srcMatch && srcMatch[1]) {
            return srcMatch[1].trim();
        }
        
        // If no match, return original value (might be invalid, but let user see it)
        return value;
    }
    
    // Preview Google Maps - Add Cinema
    const cinemaMapInput = document.getElementById('cinema-map');
    const cinemaMapPreview = document.getElementById('cinema-map-preview');
    const cinemaMapIframe = document.getElementById('cinema-map-iframe');
    
    function handleMapInput(input, iframe, preview) {
        const rawValue = input.value.trim();
        const extractedUrl = extractMapUrlFromIframe(rawValue);
        
        // Update input field with extracted URL if it was an iframe
        if (extractedUrl !== rawValue && extractedUrl) {
            input.value = extractedUrl;
        }
        
        // Show preview if valid URL
        if (extractedUrl && (extractedUrl.startsWith('https://www.google.com/maps/embed') || extractedUrl.startsWith('http://www.google.com/maps/embed'))) {
            iframe.src = extractedUrl;
            preview.classList.remove('hidden');
        } else {
            preview.classList.add('hidden');
            iframe.src = '';
        }
    }
    
    cinemaMapInput.addEventListener('input', function() {
        handleMapInput(this, cinemaMapIframe, cinemaMapPreview);
    });
    
    cinemaMapInput.addEventListener('paste', function(e) {
        // Allow paste to complete, then process
        setTimeout(() => {
            handleMapInput(this, cinemaMapIframe, cinemaMapPreview);
        }, 10);
    });
    
    // Preview Google Maps - Edit Cinema
    const editCinemaMapInput = document.getElementById('edit-cinema-map');
    const editCinemaMapPreview = document.getElementById('edit-cinema-map-preview');
    const editCinemaMapIframe = document.getElementById('edit-cinema-map-iframe');
    
    editCinemaMapInput.addEventListener('input', function() {
        handleMapInput(this, editCinemaMapIframe, editCinemaMapPreview);
    });
    
    editCinemaMapInput.addEventListener('paste', function(e) {
        // Allow paste to complete, then process
        setTimeout(() => {
            handleMapInput(this, editCinemaMapIframe, editCinemaMapPreview);
        }, 10);
    });
    
    // Open Add Cinema Modal
    document.getElementById('btn-add-cinema').addEventListener('click', function() {
        openModal(modals.addCinema);
    });
    
    // Toggle Status Button Click
    document.getElementById('toggle-status-btn').addEventListener('click', function() {
        const cinemaId = document.getElementById('edit-cinema-id').value;
        const currentStatus = document.getElementById('edit-cinema-status').value;
        
        let newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        let statusText = newStatus === 'active' ? 'kích hoạt' : 'ngừng hoạt động';
        
        // Set confirmation message
        document.getElementById('confirm-status-message').textContent = 
            `Bạn có chắc chắn muốn ${statusText} rạp phim này không?`;
        
        // Store cinema ID for later use
        document.getElementById('confirm-status-modal').setAttribute('data-cinema-id', cinemaId);
        document.getElementById('confirm-status-modal').setAttribute('data-new-status', newStatus);
        
        // Show confirmation dialog
        openModal(modals.confirmStatus);
    });
    
    // Confirm status change
    document.getElementById('confirm-status-ok').addEventListener('click', function() {
        const confirmModal = document.getElementById('confirm-status-modal');
        const cinemaId = confirmModal.getAttribute('data-cinema-id');
        const newStatus = confirmModal.getAttribute('data-new-status');
        
        // Show spinner
        const spinner = Spinner.show({
            target: modals.confirmStatus,
            text: 'Đang cập nhật trạng thái...'
        });
        // Make the API call to change status
        fetch(`${tableBody.dataset.url}/api/rap-phim/${cinemaId}/trang-thai`)
        // .then(response => response.text())
        // .then(text => console.log(text)) // Debug: log raw response text
        .then(response => response.json())
        .then(data => {
            // Hide spinner
            Spinner.hide(spinner);
            
            if (data.success) {
                // Update status in edit form
                document.getElementById('edit-cinema-status').value = newStatus;
                
                // Update status indicator in edit form
                const statusIndicator = document.getElementById('status-indicator');
                statusIndicator.classList.remove('status-active', 'status-inactive');
                
                if (newStatus === 'active') {
                    statusIndicator.textContent = 'Đang hoạt động';
                    statusIndicator.classList.add('status-active');
                } else {
                    statusIndicator.textContent = 'Ngừng hoạt động';
                    statusIndicator.classList.add('status-inactive');
                }
                
                // Close confirmation modal
                closeModal(modals.confirmStatus);
                
                // Show success message
                alert(data.message || 'Thay đổi trạng thái thành công');
                
                // Refresh cinema list
                fetchCinemas();
            } else {
                // Show error message
                alert(data.message || 'Thay đổi trạng thái thất bại');
            }
        })
        .catch(error => {
            // Hide spinner
            Spinner.hide(spinner);
            
            // Show error message
            alert('Đã xảy ra lỗi khi kết nối với máy chủ. Vui lòng thử lại sau!');
            console.error('Error:', error);
        });
    });
    
    // Cancel status change
    document.getElementById('confirm-status-cancel').addEventListener('click', function() {
        closeModal(modals.confirmStatus);
    });
    
    // Add Cinema Form Submit
    document.getElementById('add-cinema-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const name = document.getElementById('cinema-name').value;
        const address = document.getElementById('cinema-address').value;
        let mapUrl = document.getElementById('cinema-map').value;
        const hotline = document.getElementById('cinema-hotline').value;
        const description = document.getElementById('cinema-description').value;
        const longitude = document.getElementById('cinema-longitude').value;
        const latitude = document.getElementById('cinema-latitude').value;
        
        // Extract URL from iframe if needed
        mapUrl = extractMapUrlFromIframe(mapUrl);
        // Update input field with extracted URL
        if (mapUrl !== document.getElementById('cinema-map').value) {
            document.getElementById('cinema-map').value = mapUrl;
        }
        
        let isValid = validateCinemaForm(name, address, 'cinema');
        
        if (isValid) {
            // Create form data to send to the API
            const formData = new FormData();
            formData.append('ten', name);
            formData.append('diachi', address);
            formData.append('ban_do', mapUrl);
            formData.append('hotline', hotline);
            formData.append('mota', description);
            if (longitude) formData.append('kinh_do', longitude);
            if (latitude) formData.append('vi_do', latitude);
            
            // Show spinner
            const spinner = Spinner.show({
                target: modals.addCinema,
                text: 'Đang thêm rạp phim...'
            });
            
            // Make the API call
            fetch(e.target.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Hide spinner
                Spinner.hide(spinner);
                
                if (data.success) {
                    // Show success message
                    alert(data.message || 'Thêm rạp phim thành công!');
                    
                    // Close the modal
                    closeModal(modals.addCinema);
                    
                    // Reset the form
                    document.getElementById('add-cinema-form').reset();
                    
                    // Refresh cinema list from API
                    fetchCinemas();
                } else {
                    // Show error message
                    alert(data.message || 'Thêm rạp phim thất bại. Vui lòng thử lại!');
                }
            })
            .catch(error => {
                // Hide spinner
                Spinner.hide(spinner);
                
                // Show error message
                alert('Đã xảy ra lỗi khi kết nối với máy chủ. Vui lòng thử lại sau!');
                console.error('Error:', error);
            });
        }
    });
    
    // Edit Cinema Form Submit
    document.getElementById('edit-cinema-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const cinemaId = document.getElementById('edit-cinema-id').value;
        const name = document.getElementById('edit-cinema-name').value;
        const address = document.getElementById('edit-cinema-address').value;
        let mapUrl = document.getElementById('edit-cinema-map').value;
        const hotline = document.getElementById('edit-cinema-hotline').value;
        const description = document.getElementById('edit-cinema-description').value;
        const longitude = document.getElementById('edit-cinema-longitude').value;
        const latitude = document.getElementById('edit-cinema-latitude').value;
        
        // Extract URL from iframe if needed
        mapUrl = extractMapUrlFromIframe(mapUrl);
        // Update input field with extracted URL
        if (mapUrl !== document.getElementById('edit-cinema-map').value) {
            document.getElementById('edit-cinema-map').value = mapUrl;
        }
        
        let isValid = validateCinemaForm(name, address, 'edit-cinema');
        
        if (isValid) {
            // Create form data
            const formData = new FormData();
            formData.append('ten', name);
            formData.append('diachi', address);
            formData.append('ban_do', mapUrl);
            formData.append('hotline', hotline);
            formData.append('mota', description);
            if (longitude) formData.append('kinh_do', longitude);
            if (latitude) formData.append('vi_do', latitude);
            
            // Show spinner
            const spinner = Spinner.show({
                target: modals.editCinema,
                text: 'Đang cập nhật thông tin...'
            });
            
            // Make the API call to update cinema
            fetch(`${tableBody.dataset.url}/api/rap-phim/${cinemaId}`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Hide spinner
                Spinner.hide(spinner);
                
                if (data.success) {
                    // Show success message
                    alert(data.message || 'Cập nhật thông tin rạp phim thành công!');
                    
                    // Close the modal
                    closeModal(modals.editCinema);
                    
                    // Refresh cinema list
                    fetchCinemas();
                } else {
                    // Show error message
                    alert(data.message || 'Cập nhật thông tin rạp phim thất bại. Vui lòng thử lại!');
                }
            })
            .catch(error => {
                // Hide spinner
                Spinner.hide(spinner);
                
                // Show error message
                alert('Đã xảy ra lỗi khi kết nối với máy chủ. Vui lòng thử lại sau!');
                console.error('Error:', error);
            });
        }
    });
    
    // Close modals with close buttons
    const closeButtons = document.querySelectorAll('.modal-close, .modal-close-btn');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });
    
    // Close modals when clicking on overlay
    const overlays = document.querySelectorAll('.modal-overlay');
    overlays.forEach(overlay => {
        overlay.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });
    
    
    // Fetch cinemas from API
    function fetchCinemas() {
        // Clear existing data
        tableBody.innerHTML = '';
        
        // Show spinner in the table
        const spinner = Spinner.show({
            target: tableBody.closest('.shadow'),
            text: 'Đang tải dữ liệu...'
        });
        
        // Fetch data from API
        fetch(tableBody.dataset.url+'/api/rap-phim')
            // .then(response => response.text())
            // .then(text => console.log(tableBody.dataset.url)) // Debug: log raw response text
            .then(response => response.json())
            .then(data => {
                // Hide spinner
                Spinner.hide(spinner);
                
                if (data.success) {
                    if (data.data && data.data.length > 0) {
                        // Render cinema list
                        renderCinemas(data.data);
                    } else {
                        // Show empty state
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    Chưa có rạp phim nào. Hãy thêm rạp phim mới!
                                </td>
                            </tr>
                        `;
                    }
                } else {
                    // Show error
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-red-500">
                                Không thể tải dữ liệu. Vui lòng thử lại sau!
                            </td>
                        </tr>
                    `;
                    console.error('API Error:', data.message);
                }
            })
            .catch(error => {
                // Hide spinner
                Spinner.hide(spinner);
                
                // Show error
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-red-500">
                            Lỗi kết nối máy chủ. Vui lòng thử lại sau!
                        </td>
                    </tr>
                `;
                console.error('Fetch Error:', error);
            });
    }
    
    // Render cinema list
    function renderCinemas(cinemas) {
        tableBody.innerHTML = '';
        
        cinemas.forEach(cinema => {
            // Convert status from numeric to text
            const statusValue = cinema.trang_thai === 1 ? 'active' : 'inactive';
            const statusText = cinema.trang_thai === 1 ? 'Đang hoạt động' : 'Ngừng hoạt động';
            
            const row = document.createElement('tr');
            row.className = 'cinema-item cursor-pointer hover:bg-gray-50';
            row.setAttribute('data-id', cinema.id);
            row.setAttribute('data-status', statusValue);
            
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${cinema.ten}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-500">${cinema.dia_chi}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-${statusValue}">
                        ${statusText}
                    </span>
                </td>
            `;
            
            tableBody.appendChild(row);
            
            // Add click event to show edit modal
            row.addEventListener('click', function() {
                const cinemaId = this.getAttribute('data-id');
                // Highlight selected row
                document.querySelectorAll('.cinema-item').forEach(row => row.classList.remove('bg-gray-100'));
                this.classList.add('bg-gray-100');
                
                // Load cinema data and show edit modal
                loadCinemaData(cinemaId, cinema);
                openModal(modals.editCinema);
            });
        });
    }
    
    // Helper Functions
    function openModal(modal) {
        document.body.classList.add('modal-active');
        modal.classList.add('opacity-100');
        modal.classList.remove('opacity-0', 'pointer-events-none');
    }
    
    function closeModal(modal) {
        document.body.classList.remove('modal-active');
        modal.classList.add('opacity-0', 'pointer-events-none');
        modal.classList.remove('opacity-100');
        
        // Reset map previews when closing modal
        if (modal.id === 'add-cinema-modal') {
            document.getElementById('cinema-map-preview').classList.add('hidden');
            document.getElementById('cinema-map-iframe').src = '';
        } else if (modal.id === 'edit-cinema-modal') {
            document.getElementById('edit-cinema-map-preview').classList.add('hidden');
            document.getElementById('edit-cinema-map-iframe').src = '';
        }
    }
    
    function validateCinemaForm(name, address, prefix) {
        let isValid = true;
        
        // Validate name (required)
        if (!name) {
            document.getElementById(`${prefix}-name-error`).classList.remove('hidden');
            isValid = false;
        } else {
            document.getElementById(`${prefix}-name-error`).classList.add('hidden');
        }
        
        // Validate address (required)
        if (!address) {
            document.getElementById(`${prefix}-address-error`).classList.remove('hidden');
            isValid = false;
        } else {
            document.getElementById(`${prefix}-address-error`).classList.add('hidden');
        }
        
        return isValid;
    }
    
    function loadCinemaData(cinemaId, cinemaData) {
        // Set the cinema ID in the form
        document.getElementById('edit-cinema-id').value = cinemaId;
        
        // Get data from API or row data
        const cinemaRow = document.querySelector(`.cinema-item[data-id="${cinemaId}"]`);
        const status = cinemaRow.getAttribute('data-status');
        document.getElementById('edit-cinema-status').value = status;
        
        // Update status indicator
        const statusIndicator = document.getElementById('status-indicator');
        statusIndicator.classList.remove('status-active', 'status-inactive');
        statusIndicator.classList.add(`status-${status}`);
        statusIndicator.textContent = status === 'active' ? 'Đang hoạt động' : 'Ngừng hoạt động';
        
        // If we have cinema data from the API call
        if (cinemaData) {
            document.getElementById('edit-cinema-name').value = cinemaData.ten;
            document.getElementById('edit-cinema-address').value = cinemaData.dia_chi;
            document.getElementById('edit-cinema-map').value = cinemaData.ban_do || '';
            document.getElementById('edit-cinema-hotline').value = cinemaData.hotline || '';
            document.getElementById('edit-cinema-description').value = cinemaData.mo_ta || '';
            document.getElementById('edit-cinema-longitude').value = cinemaData.kinh_do || '';
            document.getElementById('edit-cinema-latitude').value = cinemaData.vi_do || '';
            
            // Show map preview if URL exists
            if (cinemaData.ban_do) {
                const editCinemaMapIframe = document.getElementById('edit-cinema-map-iframe');
                const editCinemaMapPreview = document.getElementById('edit-cinema-map-preview');
                editCinemaMapIframe.src = cinemaData.ban_do;
                editCinemaMapPreview.classList.remove('hidden');
            }
        } 
        // Otherwise get it from the row (for backward compatibility)
        else {
            document.getElementById('edit-cinema-name').value = cinemaRow.querySelector('td:first-child div').textContent;
            document.getElementById('edit-cinema-address').value = cinemaRow.querySelector('td:nth-child(2) div').textContent;
            document.getElementById('edit-cinema-map').value = '';
            document.getElementById('edit-cinema-map-preview').classList.add('hidden');
        }
    }
    
    // Initialize - Fetch cinema data when page loads
    fetchCinemas();
});
import Spinner from './util/spinner.js';

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const addGenreBtn = document.getElementById('btn-add-genre');
    const addGenreModal = document.getElementById('add-genre-modal');
    const editGenreModal = document.getElementById('edit-genre-modal');
    const addGenreForm = document.getElementById('add-genre-form');
    const editGenreForm = document.getElementById('edit-genre-form');
    const genreNameError = document.getElementById('genre-name-error');
    const editGenreNameError = document.getElementById('edit-genre-name-error');
    const genresTable = document.querySelector('#tab-theloai table tbody');
    const closeModalBtns = document.querySelectorAll('.modal-close-btn, .modal-close');
    
    // State
    let genres = [];
    
    // Load genres on page load
    loadGenres();
    
    // Tab switching
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.id.replace('tab-btn-', '');
            
            // Update button styles
            tabButtons.forEach(btn => {
                btn.classList.remove('bg-red-600', 'text-white');
                btn.classList.add('bg-white', 'text-gray-700');
            });
            this.classList.remove('bg-white', 'text-gray-700');
            this.classList.add('bg-red-600', 'text-white');
            
            // Show/hide tabs
            tabContents.forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(`tab-${tabId}`).classList.add('active');
        });
    });
    
    // Modal functions
    function openModal(modalElement) {
        document.body.classList.add('modal-active');
        modalElement.classList.remove('opacity-0', 'pointer-events-none');
    }
    
    function closeModal() {
        document.body.classList.remove('modal-active');
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.add('opacity-0', 'pointer-events-none');
        });
    }
    
    // Close modals
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', closeModal);
    });
    
    // Open add genre modal
    if (addGenreBtn) {
        addGenreBtn.addEventListener('click', function() {
            // Reset form
            addGenreForm.reset();
            genreNameError.classList.add('hidden');
            
            // Open modal
            openModal(addGenreModal);
        });
    }
    
    // Handle add genre form submission
    if (addGenreForm) {
        addGenreForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const genreName = document.getElementById('genre-name').value.trim();
            
            // Validate
            if (!genreName) {
                genreNameError.textContent = 'Vui lòng nhập tên thể loại';
                genreNameError.classList.remove('hidden');
                return;
            }
            
            // Hide error message
            genreNameError.classList.add('hidden');
            
            // Show spinner
            const spinner = Spinner.show({
                target: addGenreModal,
                text: 'Đang thêm thể loại...'
            });
            
            // Create FormData object
            const formData = new FormData();
            formData.append('ten', genreName);
            
            // Call API to add genre
            fetch(`${genresTable.dataset.url}/api/the-loai-phim`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Hide spinner
                Spinner.hide(spinner);
                
                if (data.success) {
                    // Close modal
                    closeModal();
                    
                    // Show success message
                    showToast(data.message || 'Thêm thể loại thành công');
                    
                    // Reload genres
                    loadGenres();
                } else {
                    // Show error message
                    showToast(data.message || 'Thêm thể loại thất bại', true);
                }
            })
            .catch(error => {
                // Hide spinner
                Spinner.hide(spinner);
                
                // Show error message
                showToast('Lỗi kết nối: ' + error.message, true);
                console.error('Error:', error);
            });
        });
    }
    
    // Load genres from API
    function loadGenres() {
        // Show spinner in genres table
        const container = genresTable.closest('.overflow-hidden');
        const spinner = Spinner.show({
            target: container,
            text: 'Đang tải danh sách thể loại...'
        });
        
        fetch(`${genresTable.dataset.url}/api/the-loai-phim`)
            .then(response => response.json())
            .then(data => {
                // Hide spinner
                Spinner.hide(spinner);
                
                if (data.success && data.data) {
                    // Store genres
                    genres = data.data;
                    
                    // Clear table
                    genresTable.innerHTML = '';
                    
                    // Populate table
                    if (genres.length === 0) {
                        genresTable.innerHTML = `
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-center text-gray-500">
                                    Chưa có thể loại nào. Hãy thêm thể loại mới.
                                </td>
                            </tr>
                        `;
                    } else {
                        genres.forEach(genre => {
                            // Count movies of this genre (if data available)
                            const movieCount = genre.so_phim || 0;
                            
                            const row = document.createElement('tr');
                            // Add cursor-pointer and hover effect to indicate clickable row
                            row.classList.add('cursor-pointer', 'hover:bg-gray-50');
                            row.innerHTML = `
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">${genre.ten}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">${movieCount}</div>
                                </td>
                            `;
                            
                            // Add click event to open edit modal
                            row.addEventListener('click', () => openEditGenreModal(genre));
                            
                            genresTable.appendChild(row);
                        });
                    }
                    
                    // Also update genre options in movie form dropdowns
                    updateGenreDropdowns();
                } else {
                    showToast('Không thể tải danh sách thể loại', true);
                }
            })
            .catch(error => {
                // Hide spinner
                Spinner.hide(spinner);
                
                // Show error message
                showToast('Lỗi kết nối: ' + error.message, true);
                console.error('Error:', error);
            });
    }
    
    // Update genre dropdowns in movie forms
    function updateGenreDropdowns() {
        const genreDropdowns = document.querySelectorAll('#movie-genres, #edit-movie-genres');
        
        genreDropdowns.forEach(dropdown => {
            // Save currently selected values
            const selectedValues = Array.from(dropdown.selectedOptions).map(option => option.value);
            
            // Clear dropdown
            dropdown.innerHTML = '';
            
            // Add genre options
            genres.forEach(genre => {
                const option = document.createElement('option');
                option.value = genre.id;
                option.textContent = genre.ten;
                
                // Re-select previously selected values
                if (selectedValues.includes(String(genre.id))) {
                    option.selected = true;
                }
                
                dropdown.appendChild(option);
            });
        });
        
        // Update filter dropdown in movies tab
        const filterGenre = document.getElementById('filter-genre');
        if (filterGenre) {
            // Save currently selected value
            const selectedValue = filterGenre.value;
            
            // Clear dropdown
            filterGenre.innerHTML = '<option value="">Tất cả thể loại</option>';
            
            // Add genre options
            genres.forEach(genre => {
                const option = document.createElement('option');
                option.value = genre.id;
                option.textContent = genre.ten;
                
                // Re-select previously selected value
                if (selectedValue === String(genre.id)) {
                    option.selected = true;
                }
                
                filterGenre.appendChild(option);
            });
        }
    }
    
    // Toast notification function
    function showToast(message, isError = false) {
        // Create toast element if it doesn't exist
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'fixed bottom-4 right-4 z-50';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast
        const toast = document.createElement('div');
        toast.className = `p-4 mb-3 rounded-md shadow-md transform transition-transform duration-300 ease-in-out ${isError ? 'bg-red-500' : 'bg-green-500'} text-white`;
        toast.innerHTML = `
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    ${isError 
                        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>' 
                        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'}
                </svg>
                <span>${message}</span>
            </div>
        `;
        
        // Add toast to container
        toastContainer.appendChild(toast);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }
    
    // Open edit genre modal with data
    function openEditGenreModal(genre) {
        console.log("Opening edit modal for genre:", genre);
        console.log("Edit modal element:", editGenreModal);
        
        try {
            // Set genre ID and name in form
            document.getElementById('edit-genre-id').value = genre.id;
            document.getElementById('edit-genre-name').value = genre.ten;
            
            // Clear any previous error messages
            editGenreNameError.classList.add('hidden');
            
            // Open the modal
            openModal(editGenreModal);
            console.log("Modal should be open now");
        } catch(err) {
            console.error("Error opening edit modal:", err);
        }
    }
    
    // Handle edit genre form submission
    if (editGenreForm) {
        editGenreForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const genreId = document.getElementById('edit-genre-id').value;
            const genreName = document.getElementById('edit-genre-name').value.trim();
            
            // Validate
            if (!genreName) {
                editGenreNameError.textContent = 'Vui lòng nhập tên thể loại';
                editGenreNameError.classList.remove('hidden');
                return;
            }
            
            // Hide error message
            editGenreNameError.classList.add('hidden');
            
            // Show spinner
            const spinner = Spinner.show({
                target: editGenreModal,
                text: 'Đang cập nhật thể loại...'
            });
            
            // Create payload
            const payload = {
                ten: genreName
            };
            
            // Call API to update genre
            fetch(`${genresTable.dataset.url}/api/the-loai-phim/${genreId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                // Hide spinner
                Spinner.hide(spinner);
                
                if (data.success) {
                    // Close modal with a slight delay to ensure spinner is gone
                    setTimeout(() => {
                        closeModal();
                    }, 100);
                    
                    // Show success message
                    showToast(data.message || 'Cập nhật thể loại thành công');
                    
                    // Reload genres to refresh the list
                    loadGenres();
                } else {
                    // Show error message
                    showToast(data.message || 'Cập nhật thể loại thất bại', true);
                }
            })
            .catch(error => {
                // Hide spinner
                Spinner.hide(spinner);
                
                // Show error message
                showToast('Lỗi kết nối: ' + error.message, true);
                console.error('Error:', error);
            });
        });
    }
});
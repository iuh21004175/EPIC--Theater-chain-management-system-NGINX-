import Spinner from "./util/spinner.js";

document.addEventListener('DOMContentLoaded', function() {
    // Load combos data
    function loadCombos() {
        const spinner = Spinner.show({text: 'Đang tải danh sách combo...'});
        
        // In a real application, you would fetch from API
        // For demo purposes, we'll simulate loading
        setTimeout(() => {
            Spinner.hide(spinner);
            console.log('Combos loaded');
            // Here you would update the combos grid with fresh data
        }, 1000);
    }

    // Show success toast
    function showSuccessToast(message) {
        const toast = document.getElementById('success-toast');
        const toastMessage = document.getElementById('toast-message');
        
        if (toast && toastMessage) {
            toastMessage.textContent = message;
            toast.classList.remove('opacity-0', 'translate-y-full');
            toast.classList.add('opacity-100', 'translate-y-0');
            
            setTimeout(() => {
                hideToast();
            }, 3000);
        }
    }

    function hideToast() {
        const toast = document.getElementById('success-toast');
        if (toast) {
            toast.classList.add('opacity-0', 'translate-y-full');
            toast.classList.remove('opacity-100', 'translate-y-0');
        }
    }

    // Export loadCombos function for use in san-pham-an-uong.js
    window.loadCombos = loadCombos;

    // Mở modal thêm combo khi nhấn nút
    const btnAddCombo = document.getElementById('btn-add-combo');
    const addComboModal = document.getElementById('add-combo-modal');
    if (btnAddCombo && addComboModal) {
        btnAddCombo.addEventListener('click', function() {
            document.body.classList.add('modal-active');
            addComboModal.classList.add('opacity-100');
            addComboModal.classList.remove('opacity-0', 'pointer-events-none');
        });
    }
    // Mở modal cập nhật combo khi click vào combo trong danh sách
    const editComboModal = document.getElementById('edit-combo-modal');
    const comboItems = document.querySelectorAll('.combo-item');
    comboItems.forEach(item => {
        item.addEventListener('click', function() {
            const comboId = this.getAttribute('data-id');
            if (editComboModal) {
                document.body.classList.add('modal-active');
                editComboModal.classList.add('opacity-100');
                editComboModal.classList.remove('opacity-0', 'pointer-events-none');
                // Gọi hàm loadComboData nếu có để load thông tin combo
                if (window.loadComboData) {
                    window.loadComboData(comboId);
                }
            }
        });
    });
});
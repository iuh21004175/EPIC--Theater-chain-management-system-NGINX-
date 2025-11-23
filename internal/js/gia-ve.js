import Spinner from './util/spinner.js';
document.addEventListener('DOMContentLoaded', function() {
        // CRUD Rule UI logic
        const rules = [];

        function renderRules() {
            const tbody = document.getElementById('rules-list');
            tbody.innerHTML = '';
            if (rules.length === 0) {
                tbody.innerHTML = '<tr><td colspan=7 class="text-center py-6 text-gray-400">Chưa có quy tắc nào</td></tr>';
                return;
            }
            rules.forEach((rule, idx) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap font-medium">${rule.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${actionLabel(rule.action)}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${rule.value.toLocaleString()} VND</td>
                    <td class="px-6 py-4 whitespace-nowrap">${rule.priority}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${statusLabel(rule.status)}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${renderConditions(rule.conditions)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <button class="edit-rule-btn text-blue-600 hover:underline mr-2" data-idx="${idx}">Sửa</button>
                        <button class="delete-rule-btn text-red-600 hover:underline" data-idx="${idx}">Xóa</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
        function actionLabel(action) {
            if (action === 'set_price') return 'Thiết lập giá';
            if (action === 'add_surcharge') return 'Cộng thêm tiền';
            return '';
        }
        function statusLabel(status) {
            if (status === 'active') return 'Kích hoạt';
            if (status === 'disabled') return 'Vô hiệu hóa';
            return '';
        }
        function renderConditions(conds) {
            if (!conds || conds.length === 0) return '<span class="text-gray-400">Không có</span>';
            return conds.map(c => `${conditionLabel(c.type)} = <span class='font-semibold'>${c.value}</span>`).join('<br>');
        }
        function conditionLabel(type) {
            switch(type) {
                case 'day_type': return 'Loại ngày';
                case 'time_range': return 'Khung giờ';
                case 'seat_type': return 'Loại ghế';
                case 'movie_format': return 'Định dạng phim';
                default: return type;
            }
        }

        // Modal logic
        const ruleModal = document.getElementById('rule-modal');
        const ruleForm = document.getElementById('rule-form');
        const modalTitle = document.getElementById('modal-title');
        let editingIdx = null;

        function openModal(editIdx = null) {
            ruleModal.classList.remove('hidden');
            document.body.classList.add('modal-active');
            ruleForm.reset();
            document.querySelectorAll('.invalid-feedback').forEach(el => el.classList.add('hidden'));
            document.getElementById('conditions-list').innerHTML = '';
            editingIdx = editIdx;
            if (editIdx !== null) {
                modalTitle.textContent = 'Sửa quy tắc giá vé';
                const rule = rules[editIdx];
                document.getElementById('rule-name').value = rule.name;
                document.getElementById('rule-action').value = rule.action;
                document.getElementById('rule-value').value = rule.value;
                document.getElementById('rule-priority').value = rule.priority;
                document.getElementById('rule-status').value = rule.status;
                rule.conditions.forEach(cond => addConditionRow(cond.type, cond.value));
            } else {
                modalTitle.textContent = 'Thêm mới quy tắc giá vé';
                document.getElementById('rule-priority').value = 1;
                document.getElementById('rule-status').value = 'active';
            }
        }
        function closeModal() {
            ruleModal.classList.add('hidden');
            document.body.classList.remove('modal-active');
            editingIdx = null;
        }
        document.getElementById('add-rule-btn').addEventListener('click', () => openModal());
        document.getElementById('close-modal-btn').addEventListener('click', closeModal);

        // Add condition row
        const conditionTypes = [
            { value: 'day_type', label: 'Loại ngày', options: ['Ngày thường', 'Cuối tuần', 'Ngày lễ', 'Tết'] },
            { value: 'time_range', label: 'Khung giờ', options: ['Sáng', 'Chiều', 'Tối'] },
            { value: 'seat_type', label: 'Loại ghế', options: ['Thường', 'VIP', 'Đôi', 'Premium'] },
            { value: 'movie_format', label: 'Định dạng phim', options: ['2d', '3d', 'imax-2d', 'imax-3d'] }
        ];
        function addConditionRow(type = '', value = '') {
            const idx = Date.now() + Math.random();
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2';
            div.innerHTML = `
                <select class="condition-type rounded border-gray-300" style="min-width:120px">
                    <option value="">-- Loại điều kiện --</option>
                    ${conditionTypes.map(opt => `<option value="${opt.value}" ${type===opt.value?'selected':''}>${opt.label}</option>`).join('')}
                </select>
                <select class="condition-value rounded border-gray-300" style="min-width:120px">
                    <option value="">-- Giá trị --</option>
                </select>
                <button type="button" class="remove-condition-btn text-red-600 hover:underline">Xóa</button>
            `;
            document.getElementById('conditions-list').appendChild(div);
            // Populate value options
            const typeSelect = div.querySelector('.condition-type');
            const valueSelect = div.querySelector('.condition-value');
            function updateValueOptions() {
                const selectedType = typeSelect.value;
                valueSelect.innerHTML = '<option value="">-- Giá trị --</option>';
                const found = conditionTypes.find(opt => opt.value === selectedType);
                if (found) {
                    valueSelect.innerHTML += found.options.map(v => `<option value="${v}" ${v===value?'selected':''}>${v}</option>`).join('');
                }
            }
            typeSelect.addEventListener('change', updateValueOptions);
            updateValueOptions();
            valueSelect.value = value;
            div.querySelector('.remove-condition-btn').addEventListener('click', () => div.remove());
        }
        document.getElementById('add-condition-btn').addEventListener('click', () => addConditionRow());

        // Fetch rules from server
        function fetchRules() {
            const spinner = Spinner.show({text: 'Đang tải quy tắc...'});
            fetch(`${document.querySelector('#rules-list').dataset.url}/api/quy-tac-gia-ve`)
                .then(response => response.json())
                .then(data => {
                    Spinner.hide(spinner);
                    if (data.success && Array.isArray(data.data)) {
                        rules.length = 0;
                        data.data.forEach(item => {
                            let conditions = [];
                            if (typeof item.dieu_kien === 'string') {
                                try {
                                    conditions = JSON.parse(item.dieu_kien);
                                } catch (e) {
                                    conditions = [];
                                }
                            } else if (Array.isArray(item.dieu_kien)) {
                                conditions = item.dieu_kien;
                            }
                            rules.push({
                                id: item.id,
                                name: item.ten,
                                action: item.loai_hanhdong,
                                value: item.gia_tri,
                                priority: item.do_uu_tien,
                                status: item.trang_thai,
                                conditions: conditions
                            });
                        });
                        renderRules();
                    }
                })
                .catch(error => {
                    Spinner.hide(spinner);
                    renderRules();
                    console.error('Error:', error);
                });
        }

        // Modal event listeners
        document.getElementById('add-rule-btn').addEventListener('click', () => openModal());
        document.getElementById('close-modal-btn').addEventListener('click', closeModal);

        // Form submit
        ruleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            document.querySelectorAll('.invalid-feedback').forEach(el => el.classList.add('hidden'));
            // Validate
            const name = document.getElementById('rule-name').value.trim();
            const action = document.getElementById('rule-action').value;
            const value = document.getElementById('rule-value').value;
            const priority = document.getElementById('rule-priority').value;
            const status = document.getElementById('rule-status').value;
            let valid = true;
            if (!name) {
                document.getElementById('rule_name_error').classList.remove('hidden');
                valid = false;
            }
            if (!action) {
                document.getElementById('rule_action_error').classList.remove('hidden');
                valid = false;
            }
            if (!value || value <= 0) {
                document.getElementById('rule_value_error').classList.remove('hidden');
                valid = false;
            }
            if (!priority || priority < 1) {
                document.getElementById('rule_priority_error').classList.remove('hidden');
                valid = false;
            }
            // Collect conditions
            const conds = [];
            document.querySelectorAll('#conditions-list > div').forEach(div => {
                const type = div.querySelector('.condition-type').value;
                const val = div.querySelector('.condition-value').value;
                if (type && val) conds.push({ type, value: val });
            });
            if (!valid) return;
            const ruleObj = {
                ten: name,
                loai_hanhdong: action,
                gia_tri: Number(value),
                dieu_kien: conds,
                trang_thai: status,
                do_uu_tien: Number(priority)
            };
            const spinner = Spinner.show({text: editingIdx !== null ? 'Đang cập nhật quy tắc...' : 'Đang lưu quy tắc...'});
            let url = `${document.querySelector('#rules-list').dataset.url}/api/quy-tac-gia-ve`;
            let method = 'POST';
            if (editingIdx !== null) {
                if (rules[editingIdx].id) {
                    url += `/${rules[editingIdx].id}`;
                } else {
                    showSuccessToast('Không tìm thấy id quy tắc để sửa');
                    Spinner.hide(spinner);
                    return;
                }
                method = 'PUT';
            }
            console.log('Submitting rule:', ruleObj, 'to', url, 'with method', method);
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(ruleObj),
            })
            .then(response => response.json())
            .then(data => {
                Spinner.hide(spinner);
                if (data.success) {
                    fetchRules();
                    closeModal();
                    if (method === 'PUT') {
                        showSuccessToast('Cập nhật quy tắc thành công');
                    } else {
                        showSuccessToast('Thêm quy tắc thành công');
                    }
                } else {
                    if (method === 'PUT') {
                        showSuccessToast(data.message || 'Cập nhật quy tắc thất bại');
                    } else {
                        showSuccessToast(data.message || 'Thêm quy tắc thất bại');
                    }
                }
            })
            .catch(error => {
                Spinner.hide(spinner);
                showSuccessToast('Có lỗi khi gửi dữ liệu');
                console.error('Error:', error);
            });
        });

        // Edit & Delete
        function attachRuleActions() {
            document.querySelectorAll('.edit-rule-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    openModal(Number(btn.dataset.idx));
                });
            });
            document.querySelectorAll('.delete-rule-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const idx = Number(btn.dataset.idx);
                    if (confirm('Bạn có chắc muốn xóa quy tắc này?')) {
                        rules.splice(idx, 1);
                        renderRules();
                        showSuccessToast('Xóa quy tắc thành công');
                    }
                });
            });
        }
        // Re-attach after render
        const observer = new MutationObserver(attachRuleActions);
        observer.observe(document.getElementById('rules-list'), { childList: true });
        renderRules();

        // Success toast handling
        function showSuccessToast(message) {
            const toast = document.getElementById('success-toast');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            toast.classList.remove('opacity-0', 'translate-y-full');
            toast.classList.add('opacity-100', 'translate-y-0');
            
            setTimeout(() => {
                hideToast();
            }, 5000);
        }
        
        function hideToast() {
            const toast = document.getElementById('success-toast');
            toast.classList.add('opacity-0', 'translate-y-full');
            toast.classList.remove('opacity-100', 'translate-y-0');
        }
        
        document.getElementById('close-toast').addEventListener('click', hideToast);
        fetchRules();
    });
/**
 * 现代化模态框系统
 */
class Modal {
    constructor() {
        this.createModalContainer();
    }

    createModalContainer() {
        if (document.getElementById('modal-container')) return;
        
        const container = document.createElement('div');
        container.id = 'modal-container';
        container.innerHTML = `
            <!-- 确认对话框 -->
            <div class="modal fade" id="confirmModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">确认操作</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p id="confirmMessage"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="button" class="btn btn-primary" id="confirmOk">确定</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 输入对话框 -->
            <div class="modal fade" id="promptModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="promptTitle">输入信息</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p id="promptMessage"></p>
                            <input type="text" class="form-control" id="promptInput" placeholder="">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="button" class="btn btn-primary" id="promptOk">确定</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 多输入对话框 -->
            <div class="modal fade" id="multiInputModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">创建许可证</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="licenseCount" class="form-label">许可证数量</label>
                                <input type="number" class="form-control" id="licenseCount" min="1" max="100" value="1">
                                <div class="form-text">请输入1-100之间的数量</div>
                            </div>
                            <div class="mb-3">
                                <label for="licenseDays" class="form-label">有效期（天）</label>
                                <input type="number" class="form-control" id="licenseDays" min="1" max="3650" value="365">
                                <div class="form-text">请输入1-3650天之间的有效期</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="button" class="btn btn-primary" id="createLicenseOk">创建许可证</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 警告对话框 -->
            <div class="modal fade" id="alertModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="alertTitle">提示</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p id="alertMessage"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">确定</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(container);
    }

    // 现代化的确认对话框
    confirm(message, title = '确认操作') {
        return new Promise((resolve) => {
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            document.getElementById('confirmMessage').textContent = message;
            document.querySelector('#confirmModal .modal-title').textContent = title;
            
            const okBtn = document.getElementById('confirmOk');
            const newOkBtn = okBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOkBtn, okBtn);
            
            newOkBtn.addEventListener('click', () => {
                modal.hide();
                resolve(true);
            });
            
            document.getElementById('confirmModal').addEventListener('hidden.bs.modal', () => {
                resolve(false);
            }, { once: true });
            
            modal.show();
        });
    }

    // 现代化的输入对话框
    prompt(message, defaultValue = '', title = '输入信息') {
        return new Promise((resolve) => {
            const modal = new bootstrap.Modal(document.getElementById('promptModal'));
            document.getElementById('promptMessage').textContent = message;
            document.getElementById('promptTitle').textContent = title;
            const input = document.getElementById('promptInput');
            input.value = defaultValue;
            
            const okBtn = document.getElementById('promptOk');
            const newOkBtn = okBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOkBtn, okBtn);
            
            newOkBtn.addEventListener('click', () => {
                modal.hide();
                resolve(input.value);
            });
            
            document.getElementById('promptModal').addEventListener('hidden.bs.modal', () => {
                resolve(null);
            }, { once: true });
            
            // 显示后聚焦输入框
            document.getElementById('promptModal').addEventListener('shown.bs.modal', () => {
                input.focus();
                input.select();
            }, { once: true });
            
            modal.show();
        });
    }

    // 现代化的警告对话框
    alert(message, title = '提示', type = 'info') {
        return new Promise((resolve) => {
            const modal = new bootstrap.Modal(document.getElementById('alertModal'));
            document.getElementById('alertMessage').textContent = message;
            document.getElementById('alertTitle').textContent = title;
            
            // 根据类型设置样式
            const modalContent = document.querySelector('#alertModal .modal-content');
            modalContent.className = 'modal-content';
            if (type === 'error') {
                modalContent.classList.add('border-danger');
                document.getElementById('alertTitle').className = 'modal-title text-danger';
            } else if (type === 'success') {
                modalContent.classList.add('border-success');
                document.getElementById('alertTitle').className = 'modal-title text-success';
            } else {
                document.getElementById('alertTitle').className = 'modal-title text-info';
            }
            
            document.getElementById('alertModal').addEventListener('hidden.bs.modal', () => {
                resolve();
            }, { once: true });
            
            modal.show();
        });
    }

    // 创建许可证的专用对话框
    createLicenseDialog() {
        return new Promise((resolve) => {
            const modal = new bootstrap.Modal(document.getElementById('multiInputModal'));
            const countInput = document.getElementById('licenseCount');
            const daysInput = document.getElementById('licenseDays');
            
            const okBtn = document.getElementById('createLicenseOk');
            const newOkBtn = okBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOkBtn, okBtn);
            
            newOkBtn.addEventListener('click', () => {
                const count = parseInt(countInput.value);
                const days = parseInt(daysInput.value);
                
                if (!count || !days || count < 1 || count > 100 || days < 1 || days > 3650) {
                    this.alert('输入的参数不正确，请检查数量和有效期范围', '参数错误', 'error');
                    return;
                }
                
                modal.hide();
                resolve({ count, days });
            });
            
            document.getElementById('multiInputModal').addEventListener('hidden.bs.modal', () => {
                resolve(null);
            }, { once: true });
            
            modal.show();
        });
    }
}

// 创建全局实例
window.modernModal = new Modal();

// 兼容旧的API
window.confirm = (message) => window.modernModal.confirm(message);
window.prompt = (message, defaultValue) => window.modernModal.prompt(message, defaultValue);
window.alert = (message) => window.modernModal.alert(message);


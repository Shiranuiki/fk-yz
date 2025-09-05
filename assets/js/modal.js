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

            <!-- 创建许可证对话框 -->
            <div class="modal fade" id="createLicenseModal" tabindex="-1">
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
                // 先移除焦点再关闭模态框
                newOkBtn.blur();
                modal.hide();
                resolve(true);
            });
            
            document.getElementById('confirmModal').addEventListener('hidden.bs.modal', () => {
                // 确保焦点被清除
                if (document.activeElement) {
                    document.activeElement.blur();
                }
                resolve(false);
            }, { once: true });
            
            modal.show();
        });
    }

    // 现代化的输入对话框
    prompt(message, defaultValue = '', title = '请输入') {
        return new Promise((resolve) => {
            const modal = new bootstrap.Modal(document.getElementById('promptModal'));
            const input = document.getElementById('promptInput');
            
            document.getElementById('promptMessage').textContent = message;
            document.getElementById('promptTitle').textContent = title;
            input.value = defaultValue;
            
            const okBtn = document.getElementById('promptOk');
            const newOkBtn = okBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOkBtn, okBtn);
            
            newOkBtn.addEventListener('click', () => {
                const value = input.value.trim();
                newOkBtn.blur();
                input.blur();
                modal.hide();
                resolve(value || null);
            });
            
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const value = input.value.trim();
                    input.blur();
                    modal.hide();
                    resolve(value || null);
                }
            });
            
            document.getElementById('promptModal').addEventListener('hidden.bs.modal', () => {
                // 确保焦点被清除
                if (document.activeElement) {
                    document.activeElement.blur();
                }
                resolve(null);
            }, { once: true });
            
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
            // 动态创建模态框HTML
            this.createLicenseModalHTML();
            
            // 等待DOM更新后再获取元素
            setTimeout(() => {
                const modalElement = document.getElementById('createLicenseModal');
                
                if (!modalElement) {
                    console.error('Modal element not found');
                    resolve(null);
                    return;
                }
                
                const modal = new bootstrap.Modal(modalElement);
                const countInput = document.getElementById('licenseCount');
                const daysInput = document.getElementById('licenseDays');
                const prefixInput = document.getElementById('licensePrefix');
                const lengthInput = document.getElementById('licenseLength');
                const charsetSelect = document.getElementById('licenseCharset');
                const customFormatDiv = document.getElementById('customFormatDiv');
                const useDefaultCheckbox = document.getElementById('useDefaultFormat');
                
                // 检查基本元素是否存在
                if (!countInput || !daysInput) {
                    console.error('Basic modal elements not found');
                    resolve(null);
                    return;
                }
                
                // 重置输入值
                countInput.value = '1';
                daysInput.value = '365';
                if (prefixInput) prefixInput.value = 'zz';
                if (lengthInput) lengthInput.value = '18';
                if (charsetSelect) charsetSelect.value = 'abcdefghijklmnopqrstuvwxyz0123456789';
                if (useDefaultCheckbox) useDefaultCheckbox.checked = true;
                if (customFormatDiv) customFormatDiv.style.display = 'none';
                
                // 处理默认格式复选框变化
                if (useDefaultCheckbox && customFormatDiv) {
                    useDefaultCheckbox.addEventListener('change', (e) => {
                        customFormatDiv.style.display = e.target.checked ? 'none' : 'block';
                    });
                }
                
                const okBtn = document.getElementById('createLicenseOk');
                const newOkBtn = okBtn.cloneNode(true);
                okBtn.parentNode.replaceChild(newOkBtn, okBtn);
                
                newOkBtn.addEventListener('click', () => {
                const count = parseInt(countInput.value);
                const days = parseInt(daysInput.value);
                
                if (!count || !days || count < 1 || count > 100 || days < 1 || days > 3650) {
                    alert('输入的参数不正确，请检查数量和有效期范围');
                    return;
                }
                
                // 检查自定义格式
                let formatOptions = null;
                const useDefaultCheckbox = document.getElementById('useDefaultFormat');
                const prefixInput = document.getElementById('licensePrefix');
                const lengthInput = document.getElementById('licenseLength');
                const charsetSelect = document.getElementById('licenseCharset');
                
                if (useDefaultCheckbox && !useDefaultCheckbox.checked && prefixInput && lengthInput && charsetSelect) {
                    const prefix = prefixInput.value.trim();
                    const length = parseInt(lengthInput.value);
                    const charset = charsetSelect.value;
                    
                    if (!prefix || length < 8 || length > 64 || !charset) {
                        alert('自定义格式参数不正确，请检查前缀、长度和字符集');
                        return;
                    }
                    
                    formatOptions = { prefix, length, charset };
                }
                
                    newOkBtn.blur();
                    countInput.blur();
                    daysInput.blur();
                    if (prefixInput) prefixInput.blur();
                    if (lengthInput) lengthInput.blur();
                    if (charsetSelect) charsetSelect.blur();
                    modal.hide();
                    resolve({ count, days, formatOptions });
                });
                
                document.getElementById('createLicenseModal').addEventListener('hidden.bs.modal', () => {
                    // 确保焦点被清除
                    if (document.activeElement) {
                        document.activeElement.blur();
                    }
                    resolve(null);
                }, { once: true });
                
                modal.show();
            }, 10); // 给DOM一点时间更新
        });
    }

    // 创建许可证模态框HTML
    createLicenseModalHTML() {
        // 如果模态框已存在，先删除旧的
        const existingModal = document.getElementById('createLicenseModal');
        if (existingModal) {
            existingModal.remove();
        }
        const modalHTML = `
        <div class="modal fade" id="createLicenseModal" tabindex="-1" aria-labelledby="createLicenseModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createLicenseModalLabel">创建许可证</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="licenseCount" class="form-label">数量</label>
                                    <input type="number" class="form-control" id="licenseCount" min="1" max="100" value="1">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="licenseDays" class="form-label">有效期(天)</label>
                                    <input type="number" class="form-control" id="licenseDays" min="1" max="3650" value="365">
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="useDefaultFormat" checked>
                                <label class="form-check-label" for="useDefaultFormat">
                                    使用默认格式 (zz开头18位小写字母数字)
                                </label>
                            </div>
                        </div>
                        
                        <div id="customFormatDiv" style="display: none;">
                            <h6 class="text-muted mb-3">自定义格式</h6>
                            <div class="row">
                                <div class="col-4">
                                    <div class="mb-3">
                                        <label for="licensePrefix" class="form-label">前缀</label>
                                        <input type="text" class="form-control" id="licensePrefix" value="zz" placeholder="例如: zz, LIC-">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="mb-3">
                                        <label for="licenseLength" class="form-label">总长度</label>
                                        <input type="number" class="form-control" id="licenseLength" min="8" max="64" value="18">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="mb-3">
                                        <label for="licenseCharset" class="form-label">字符集</label>
                                        <select class="form-control" id="licenseCharset">
                                            <option value="abcdefghijklmnopqrstuvwxyz0123456789">小写字母+数字</option>
                                            <option value="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789">大写字母+数字</option>
                                            <option value="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789">大小写字母+数字</option>
                                            <option value="0123456789">仅数字</option>
                                            <option value="ABCDEFGHIJKLMNOPQRSTUVWXYZ">仅大写字母</option>
                                            <option value="abcdefghijklmnopqrstuvwxyz">仅小写字母</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" id="createLicenseOk">创建</button>
                    </div>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
}

// 创建全局实例
window.modernModal = new Modal();

// 兼容旧的API
window.confirm = (message) => window.modernModal.confirm(message);
window.prompt = (message, defaultValue) => window.modernModal.prompt(message, defaultValue);
window.alert = (message) => window.modernModal.alert(message);
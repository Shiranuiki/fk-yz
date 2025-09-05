/**
 * 自动消失的通知系统
 */
class NotificationSystem {
    constructor() {
        this.createNotificationContainer();
        this.initAutoClose();
    }

    createNotificationContainer() {
        if (document.getElementById('notification-container')) return;
        
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(container);
    }

    // 显示通知
    show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        const alertClass = this.getAlertClass(type);
        const icon = this.getIcon(type);
        
        notification.className = `alert ${alertClass} alert-dismissible fade show`;
        notification.style.cssText = `
            margin-bottom: 10px;
            animation: slideInRight 0.3s ease-out;
        `;
        
        notification.innerHTML = `
            <i class="bi ${icon} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <div class="progress" style="height: 2px; margin-top: 8px;">
                <div class="progress-bar" style="width: 100%; transition: width ${duration}ms linear;"></div>
            </div>
        `;
        
        document.getElementById('notification-container').appendChild(notification);
        
        // 启动进度条动画
        setTimeout(() => {
            const progressBar = notification.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = '0%';
            }
        }, 100);
        
        // 自动关闭
        if (duration > 0) {
            setTimeout(() => {
                this.dismiss(notification);
            }, duration);
        }
        
        return notification;
    }

    // 获取警告样式类
    getAlertClass(type) {
        const classes = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        };
        return classes[type] || 'alert-info';
    }

    // 获取图标
    getIcon(type) {
        const icons = {
            'success': 'bi-check-circle-fill',
            'error': 'bi-exclamation-triangle-fill',
            'warning': 'bi-exclamation-triangle',
            'info': 'bi-info-circle-fill'
        };
        return icons[type] || 'bi-info-circle-fill';
    }

    // 关闭通知
    dismiss(notification) {
        if (notification && notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }

    // 初始化自动关闭现有的警告
    initAutoClose() {
        // 等待DOM加载完成
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.processExistingAlerts());
        } else {
            this.processExistingAlerts();
        }
    }

    // 处理页面中现有的警告框
    processExistingAlerts() {
        const alerts = document.querySelectorAll('.alert:not(.alert-dismissible)');
        alerts.forEach(alert => {
            // 添加关闭按钮
            alert.classList.add('alert-dismissible');
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close';
            closeBtn.setAttribute('data-bs-dismiss', 'alert');
            alert.appendChild(closeBtn);
            
            // 添加进度条
            const progressContainer = document.createElement('div');
            progressContainer.className = 'progress';
            progressContainer.style.cssText = 'height: 2px; margin-top: 8px;';
            
            const progressBar = document.createElement('div');
            progressBar.className = 'progress-bar';
            progressBar.style.cssText = 'width: 100%; transition: width 5000ms linear;';
            
            progressContainer.appendChild(progressBar);
            alert.appendChild(progressContainer);
            
            // 启动进度条动画
            setTimeout(() => {
                progressBar.style.width = '0%';
            }, 100);
            
            // 5秒后自动关闭
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.animation = 'fadeOut 0.3s ease-out';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 300);
                }
            }, 5000);
        });
    }

    // 成功通知
    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }

    // 错误通知
    error(message, duration = 8000) {
        return this.show(message, 'error', duration);
    }

    // 警告通知
    warning(message, duration = 6000) {
        return this.show(message, 'warning', duration);
    }

    // 信息通知
    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }
}

// 添加CSS动画
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
        }
    }
    
    #notification-container .alert {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: none;
    }
`;
document.head.appendChild(style);

// 创建全局实例
window.notify = new NotificationSystem();

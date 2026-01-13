@if(session('success') || session('error') || session('info') || session('warning') || $errors->any())
<div id="notification-container" style="
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 99999;
    max-width: 400px;
" x-data="{ notifications: true }">
    @if(session('success'))
    <div class="notification notification-success" role="alert">
        <div class="notification-icon">✓</div>
        <div class="notification-content">
            <div class="notification-title">Success</div>
            <div class="notification-message">{{ session('success') }}</div>
        </div>
        <button class="notification-close" onclick="closeNotification(this)">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="notification notification-error" role="alert">
        <div class="notification-icon">✕</div>
        <div class="notification-content">
            <div class="notification-title">Error</div>
            <div class="notification-message">{{ session('error') }}</div>
        </div>
        <button class="notification-close" onclick="closeNotification(this)">&times;</button>
    </div>
    @endif

    @if(session('info'))
    <div class="notification notification-info" role="alert">
        <div class="notification-icon">ℹ</div>
        <div class="notification-content">
            <div class="notification-title">Information</div>
            <div class="notification-message">{{ session('info') }}</div>
        </div>
        <button class="notification-close" onclick="closeNotification(this)">&times;</button>
    </div>
    @endif

    @if(session('warning'))
    <div class="notification notification-warning" role="alert">
        <div class="notification-icon">⚠</div>
        <div class="notification-content">
            <div class="notification-title">Warning</div>
            <div class="notification-message">{{ session('warning') }}</div>
        </div>
        <button class="notification-close" onclick="closeNotification(this)">&times;</button>
    </div>
    @endif

    @if($errors->any())
    <div class="notification notification-error" role="alert">
        <div class="notification-icon">✕</div>
        <div class="notification-content">
            <div class="notification-title">Validation Error</div>
            <div class="notification-message">
                @if($errors->count() === 1)
                    {{ $errors->first() }}
                @else
                    <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        <button class="notification-close" onclick="closeNotification(this)">&times;</button>
    </div>
    @endif
</div>

<style>
    .notification {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        margin-bottom: 12px;
        border-left: 4px solid;
        animation: slideInRight 0.3s ease-out, fadeOut 0.3s ease-out 4.7s forwards;
        position: relative;
        overflow: hidden;
    }

    .notification::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        background: currentColor;
        animation: progress 5s linear forwards;
    }

    @keyframes progress {
        from { width: 100%; }
        to { width: 0%; }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeOut {
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }

    .notification-success {
        border-left-color: #4caf50;
        color: #2e7d32;
    }

    .notification-success::before {
        background: #4caf50;
    }

    .notification-error {
        border-left-color: #f44336;
        color: #c62828;
    }

    .notification-error::before {
        background: #f44336;
    }

    .notification-info {
        border-left-color: #2196f3;
        color: #1565c0;
    }

    .notification-info::before {
        background: #2196f3;
    }

    .notification-warning {
        border-left-color: #ff9800;
        color: #e65100;
    }

    .notification-warning::before {
        background: #ff9800;
    }

    .notification-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
        flex-shrink: 0;
    }

    .notification-success .notification-icon {
        background: #e8f5e9;
        color: #4caf50;
    }

    .notification-error .notification-icon {
        background: #ffebee;
        color: #f44336;
    }

    .notification-info .notification-icon {
        background: #e3f2fd;
        color: #2196f3;
    }

    .notification-warning .notification-icon {
        background: #fff3e0;
        color: #ff9800;
    }

    .notification-content {
        flex: 1;
        min-width: 0;
    }

    .notification-title {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .notification-message {
        font-size: 13px;
        opacity: 0.9;
        line-height: 1.5;
    }

    .notification-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: currentColor;
        opacity: 0.5;
        transition: opacity 0.2s;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        line-height: 1;
    }

    .notification-close:hover {
        opacity: 1;
    }

    @media (max-width: 640px) {
        #notification-container {
            left: 20px;
            right: 20px;
            max-width: none;
        }

        .notification {
            padding: 14px;
        }

        .notification-icon {
            width: 24px;
            height: 24px;
            font-size: 14px;
        }
    }
</style>

<script>
    function closeNotification(button) {
        const notification = button.closest('.notification');
        notification.style.animation = 'fadeOut 0.3s ease-out forwards';
        setTimeout(() => {
            notification.remove();

            // Remove container if no notifications left
            const container = document.getElementById('notification-container');
            if (container && container.querySelectorAll('.notification').length === 0) {
                container.remove();
            }
        }, 300);
    }

    // Auto-remove notifications after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            setTimeout(() => {
                if (notification.parentElement) {
                    closeNotification(notification.querySelector('.notification-close'));
                }
            }, 5000);
        });
    });
</script>
@endif

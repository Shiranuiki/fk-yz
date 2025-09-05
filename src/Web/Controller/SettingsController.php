<?php

namespace AuthSystem\Web\Controller;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Config\Config;
use AuthSystem\Core\Logger\Logger;
use AuthSystem\Core\Session\SessionManager;
use AuthSystem\Models\AdminLog;
use AuthSystem\Models\Admin;

/**
 * Web设置控制器
 * 
 * @package AuthSystem\Web\Controller
 */
class SettingsController
{
    private Config $config;
    private Logger $logger;
    private AdminLog $adminLogModel;
    private Admin $adminModel;

    public function __construct(Config $config, Logger $logger, AdminLog $adminLogModel, Admin $adminModel)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->adminLogModel = $adminLogModel;
        $this->adminModel = $adminModel;
    }

    /**
     * 显示设置页面
     */
    public function index(Request $request): Response
    {
        try {
            // 检查管理员是否已登录
            if (!SessionManager::isLoggedIn()) {
                return Response::redirect('/login?error=' . urlencode('请先登录'));
            }

            $settings = $this->getCurrentSettings();
            $systemName = $settings['system_name'] ?? '网络验证系统';
            $brandHtml = $this->config->getBrandHtml();
            $html = $this->renderSettingsPage($settings, $systemName, $brandHtml);
            return Response::html($html);

        } catch (\Exception $e) {
            $this->logger->error('Settings page error', [
                'error' => $e->getMessage(),
            ]);

            return Response::html('<h1>错误</h1><p>加载设置页面时发生错误</p>');
        }
    }

    /**
     * 获取当前设置
     */
    private function getCurrentSettings(): array
    {
        return [
            'system_name' => $_ENV['SYSTEM_NAME'] ?? '网络验证系统',
            'website_url' => $_ENV['WEBSITE_URL'] ?? 'http://localhost',
            'website_logo' => $_ENV['WEBSITE_LOGO'] ?? '',
            'api_secret_key' => $_ENV['API_SECRET_KEY'] ?? '',
            'api_encrypt_method' => $_ENV['API_ENCRYPT_METHOD'] ?? 'AES-256-CBC',
            'client_auth_required' => $_ENV['CLIENT_AUTH_REQUIRED'] ?? 'true',
            'api_key_required' => $_ENV['API_KEY_REQUIRED'] ?? 'false',
            'db_host' => $_ENV['DB_HOST'] ?? 'localhost',
            'db_port' => $_ENV['DB_PORT'] ?? '3306',
            'db_name' => $_ENV['DB_NAME'] ?? 'auth_system',
            'jwt_expiry' => $_ENV['JWT_EXPIRY'] ?? '3600',
            'rate_limit_verify_max' => $_ENV['RATE_LIMIT_VERIFY_MAX'] ?? '10',
            'rate_limit_verify_per' => $_ENV['RATE_LIMIT_VERIFY_PER'] ?? '60',
            'rate_limit_login_max' => $_ENV['RATE_LIMIT_LOGIN_MAX'] ?? '5',
            'rate_limit_login_per' => $_ENV['RATE_LIMIT_LOGIN_PER'] ?? '60',
        ];
    }

    /**
     * 渲染设置页面
     */
    private function renderSettingsPage(array $settings, string $systemName = '网络验证系统', string $brandHtml = ''): string
    {
        if (empty($brandHtml)) {
            $brandHtml = '<i class="bi bi-shield-check"></i> ' . htmlspecialchars($systemName);
        }
        
        // 处理成功/错误消息 - 使用现代化通知系统
        $notificationScript = '';
        if (isset($_GET['success'])) {
            $message = htmlspecialchars($_GET['success']);
            $notificationScript = "<script>document.addEventListener('DOMContentLoaded', function() { notify.success('{$message}'); });</script>";
        } elseif (isset($_GET['error'])) {
            $message = htmlspecialchars($_GET['error']);
            $notificationScript = "<script>document.addEventListener('DOMContentLoaded', function() { notify.error('{$message}'); });</script>";
        } elseif (isset($_GET['info'])) {
            $message = htmlspecialchars($_GET['info']);
            $notificationScript = "<script>document.addEventListener('DOMContentLoaded', function() { notify.info('{$message}'); });</script>";
        }
        $alertHtml = '';
        
        // 获取系统信息
        $phpVersion = PHP_VERSION;
        $serverTime = date('Y-m-d H:i:s');
        
        // 处理Logo显示
        $logoPreview = '';
        if (!empty($settings['website_logo'])) {
            $logoPreview = '<div class="mt-2"><img src="' . htmlspecialchars($settings['website_logo']) . '" alt="当前Logo" style="max-height: 50px;" class="img-thumbnail"></div>';
        }
        
        // 处理选择框选中状态
        $aes256Selected = $settings['api_encrypt_method'] === 'AES-256-CBC' ? 'selected' : '';
        $aes128Selected = $settings['api_encrypt_method'] === 'AES-128-CBC' ? 'selected' : '';
        $desSelected = $settings['api_encrypt_method'] === 'DES-EDE3-CBC' ? 'selected' : '';
        
        // 处理复选框选中状态
        $authRequiredChecked = $settings['client_auth_required'] === 'true' ? 'checked' : '';
        $apiKeyRequiredChecked = $settings['api_key_required'] === 'true' ? 'checked' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - {$systemName}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">
{$brandHtml}
            </a>
            <div class="navbar-nav">
                <a class="nav-link" href="/">
                    <i class="bi bi-house"></i> 首页
                </a>
                <a class="nav-link" href="/licenses">
                    <i class="bi bi-key"></i> 许可证管理
                </a>
                <a class="nav-link" href="/logs">
                    <i class="bi bi-list-ul"></i> 日志查看
                </a>
                <a class="nav-link active" href="/settings">
                    <i class="bi bi-gear"></i> 设置
                </a>
                <a class="nav-link" href="/logout">
                    <i class="bi bi-box-arrow-right"></i> 退出
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 mb-4">系统设置</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- 基本设置 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-gear"></i> 基本设置
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/settings/save" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="system_name" class="form-label">系统名称</label>
                                <input type="text" class="form-control" id="system_name" name="SYSTEM_NAME" value="{$settings['system_name']}" required>
                                <div class="form-text">当前系统的显示名称</div>
                            </div>
                            <div class="mb-3">
                                <label for="website_url" class="form-label">网站URL</label>
                                <input type="url" class="form-control" id="website_url" name="WEBSITE_URL" value="{$settings['website_url']}" required>
                                <div class="form-text">网站的完整URL地址，例如http://example.com</div>
                            </div>
                            <div class="mb-3">
                                <label for="website_logo" class="form-label">网站Logo</label>
                                <div class="mb-2">
                                    <input type="url" class="form-control" id="website_logo_url" name="WEBSITE_LOGO" value="{$settings['website_logo']}" placeholder="https://example.com/logo.png">
                                    <div class="form-text">输入Logo图片的URL地址</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">或上传本地Logo文件</label>
                                    <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/*">
                                    <div class="form-text">支持 JPG, PNG, GIF, SVG 格式，建议尺寸 200x50px</div>
                                </div>
                                {$logoPreview}
                            </div>
                            <div class="mb-3">
                                <label for="jwt_expiry" class="form-label">JWT令牌有效期 (秒)</label>
                                <input type="number" class="form-control" id="jwt_expiry" name="JWT_EXPIRY" value="{$settings['jwt_expiry']}" min="300" max="86400">
                                <div class="form-text">管理员登录令牌的有效期（300-86400秒）</div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> 保存基本设置
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 数据库设置 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-database"></i> 数据库设置
                        </h5>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="db_host" class="form-label">数据库主机</label>
                                        <input type="text" class="form-control" id="db_host" value="{$settings['db_host']}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="db_port" class="form-label">端口</label>
                                        <input type="text" class="form-control" id="db_port" value="{$settings['db_port']}" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="db_name" class="form-label">数据库名</label>
                                <input type="text" class="form-control" id="db_name" value="{$settings['db_name']}" readonly>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 限流设置 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-speedometer2"></i> 限流设置
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/settings/save">
                            <input type="hidden" name="section" value="rate_limit">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rate_limit_verify_max" class="form-label">验证接口最大请求数</label>
                                        <input type="number" class="form-control" id="rate_limit_verify_max" name="RATE_LIMIT_VERIFY_MAX" value="{$settings['rate_limit_verify_max']}" min="1" max="1000">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rate_limit_verify_per" class="form-label">时间窗口 (秒)</label>
                                        <input type="number" class="form-control" id="rate_limit_verify_per" name="RATE_LIMIT_VERIFY_PER" value="{$settings['rate_limit_verify_per']}" min="10" max="3600">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rate_limit_login_max" class="form-label">登录接口最大请求数</label>
                                        <input type="number" class="form-control" id="rate_limit_login_max" name="RATE_LIMIT_LOGIN_MAX" value="{$settings['rate_limit_login_max']}" min="1" max="100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rate_limit_login_per" class="form-label">时间窗口 (秒)</label>
                                        <input type="number" class="form-control" id="rate_limit_login_per" name="RATE_LIMIT_LOGIN_PER" value="{$settings['rate_limit_login_per']}" min="10" max="3600">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-speedometer2"></i> 保存限流设置
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 管理员账号设置 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-person-gear"></i> 管理员账号管理
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/settings/change-password">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">当前密码</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <div class="form-text">请输入当前管理员密码以验证身份</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="new_username" class="form-label">新用户名</label>
                                        <input type="text" class="form-control" id="new_username" name="new_username" placeholder="留空则不修改">
                                        <div class="form-text">修改管理员用户名（可选）</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">新密码</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                        <div class="form-text">至少6位字符，留空则不修改</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">确认新密码</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
                                        <div class="form-text">请再次输入新密码确认</div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-key"></i> 修改管理员账号
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 安全设置 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-shield-lock"></i> 安全设置
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/settings/save">
                            <input type="hidden" name="section" value="security">
                            <div class="mb-3">
                                <label for="api_secret_key" class="form-label">API通信密钥</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="api_secret_key" name="API_SECRET_KEY" value="{$settings['api_secret_key']}" minlength="16" maxlength="64">
                                    <button type="button" class="btn btn-outline-secondary" onclick="generateApiKey()">
                                        <i class="bi bi-arrow-clockwise"></i> 生成
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('api_secret_key')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">用于客户端与服务器通信加密的密钥（16-64位字符）</div>
                            </div>
                            <div class="mb-3">
                                <label for="api_encrypt_method" class="form-label">加密算法</label>
                                <select class="form-select" id="api_encrypt_method" name="API_ENCRYPT_METHOD">
                                    <option value="AES-256-CBC" {$aes256Selected}>AES-256-CBC (推荐)</option>
                                    <option value="AES-128-CBC" {$aes128Selected}>AES-128-CBC</option>
                                    <option value="DES-EDE3-CBC" {$desSelected}>3DES-CBC</option>
                                </select>
                                <div class="form-text">选择客户端通信使用的加密算法</div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="CLIENT_AUTH_REQUIRED" value="false">
                                    <input class="form-check-input" type="checkbox" id="client_auth_required" name="CLIENT_AUTH_REQUIRED" value="true" {$authRequiredChecked}>
                                    <label class="form-check-label" for="client_auth_required">
                                        强制客户端身份验证
                                    </label>
                                </div>
                                <div class="form-text">启用后，所有API请求都必须包含有效的身份验证信息</div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="API_KEY_REQUIRED" value="false">
                                    <input class="form-check-input" type="checkbox" id="api_key_required" name="API_KEY_REQUIRED" value="true" {$apiKeyRequiredChecked}>
                                    <label class="form-check-label" for="api_key_required">
                                        启用API密钥验证
                                    </label>
                                </div>
                                <div class="form-text">启用后，所有API请求都必须包含正确的API密钥</div>
                            </div>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>重要提醒：</strong>修改安全设置后，现有客户端可能需要更新配置才能继续正常工作。
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-shield-lock"></i> 保存安全设置
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- 系统信息 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-info-circle"></i> 系统信息
                        </h5>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-6">PHP版本:</dt>
                            <dd class="col-sm-6">{$phpVersion}</dd>
                            
                            <dt class="col-sm-6">服务器时间:</dt>
                            <dd class="col-sm-6" id="server-time">{$serverTime}</dd>
                            
                            <dt class="col-sm-6">系统负载:</dt>
                            <dd class="col-sm-6">
                                <span class="badge bg-success">正常</span>
                            </dd>
                        </dl>
                    </div>
                </div>

                <!-- 操作面板 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-tools"></i> 操作面板
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="clearCache()">
                                <i class="bi bi-arrow-clockwise"></i> 清理缓存
                            </button>
                            <button class="btn btn-outline-warning" onclick="exportData()">
                                <i class="bi bi-download"></i> 导出数据
                            </button>
                            <button class="btn btn-outline-info" onclick="checkUpdate()">
                                <i class="bi bi-cloud-download"></i> 检查更新
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/modal.js"></script>
    <script src="/assets/js/notifications.js"></script>
    <script>
        async function clearCache() {
            const confirmed = await modernModal.confirm('确定要清理系统缓存吗？', '清理缓存');
            if (confirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/settings/clear-cache';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        async function exportData() {
            const confirmed = await modernModal.confirm('确定要导出系统数据吗？这可能需要一些时间。', '导出数据');
            if (confirmed) {
                // 直接跳转到导出页面
                window.location.href = '/settings/export-data';
            }
        }
        
        async function checkUpdate() {
            const confirmed = await modernModal.confirm('确定要检查系统更新吗？', '检查更新');
            if (confirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/settings/check-update';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // 实时更新服务器时间
        function updateServerTime() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            const timeString = `\${year}-\${month}-\${day} \${hours}:\${minutes}:\${seconds}`;
            const timeElement = document.getElementById('server-time');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // 生成API密钥
        function generateApiKey() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
            let result = '';
            for (let i = 0; i < 32; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('api_secret_key').value = result;
        }
        
        // 切换密码显示/隐藏
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
        
        // 页面加载完成后开始更新时间
        document.addEventListener('DOMContentLoaded', function() {
            updateServerTime(); // 立即更新一次
            setInterval(updateServerTime, 1000); // 每秒更新一次
        });
    </script>
    {$notificationScript}
</body>
</html>
HTML;
    }

    /**
     * 保存设置
     */
    public function save(Request $request): Response
    {
        try {
            // 检查管理员是否已登录
            if (!SessionManager::isLoggedIn()) {
                return Response::redirect('/login?error=' . urlencode('请先登录'));
            }

            $data = $request->all();
            
            // 处理Logo文件上传
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                $logoPath = $this->handleLogoUpload($_FILES['logo_file']);
                if ($logoPath) {
                    $data['WEBSITE_LOGO'] = $logoPath;
                }
            }
            $envPath = PROJECT_ROOT . '/.env';
            
            // 处理复选框字段（未选中时不会发送）
            // 由于有隐藏字段，需要检查值而不是键是否存在
            $data['CLIENT_AUTH_REQUIRED'] = (isset($data['CLIENT_AUTH_REQUIRED']) && $data['CLIENT_AUTH_REQUIRED'] === 'true') ? 'true' : 'false';
            $data['API_KEY_REQUIRED'] = (isset($data['API_KEY_REQUIRED']) && $data['API_KEY_REQUIRED'] === 'true') ? 'true' : 'false';

            // 验证输入数据
            $validationErrors = $this->validateSettings($data);
            if (!empty($validationErrors)) {
                return Response::redirect('/settings?error=' . urlencode(implode(', ', $validationErrors)));
            }
            
            // 读取现有的.env文件
            $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';
            $envVars = $this->parseEnvFile($envContent);
            
            // 更新允许修改的配置项
            $allowedSettings = [
                'SYSTEM_NAME' => '系统名称',
                'WEBSITE_URL' => '网站URL',
                'WEBSITE_LOGO' => '网站Logo',
                'API_SECRET_KEY' => 'API通信密钥',
                'API_ENCRYPT_METHOD' => '加密算法',
                'CLIENT_AUTH_REQUIRED' => '客户端身份验证',
                'API_KEY_REQUIRED' => 'API密钥验证',
                'JWT_EXPIRY' => 'JWT有效期',
                'RATE_LIMIT_VERIFY_MAX' => '验证接口限流',
                'RATE_LIMIT_VERIFY_PER' => '验证接口时间窗口',
                'RATE_LIMIT_LOGIN_MAX' => '登录接口限流',
                'RATE_LIMIT_LOGIN_PER' => '登录接口时间窗口',
            ];
            
            $updated = [];
            foreach ($allowedSettings as $key => $name) {
                if (isset($data[$key]) && $data[$key] !== ($envVars[$key] ?? '')) {
                    $envVars[$key] = $data[$key];
                    $updated[] = $name;
                }
            }
            
            if (empty($updated)) {
                return Response::redirect('/settings?info=' . urlencode('没有配置项被修改'));
            }
            
            // 重新构建.env文件内容
            $newEnvContent = $this->buildEnvContent($envVars);
            
            // 备份原文件
            if (file_exists($envPath)) {
                copy($envPath, $envPath . '.backup.' . date('YmdHis'));
            }
            
            // 写入新的.env文件
            if (file_put_contents($envPath, $newEnvContent) === false) {
                return Response::redirect('/settings?error=' . urlencode('保存配置失败，请检查文件权限'));
            }
            
            // 记录操作日志
            $this->adminLogModel->logAction(
                '修改系统设置',
                '修改了以下配置: ' . implode(', ', $updated),
                $request->getClientIp(),
                $request->getUserAgent()
            );
            
            $this->logger->info('Settings updated', [
                'updated_settings' => $updated,
                'ip' => $request->getClientIp(),
            ]);
            
            return Response::redirect('/settings?success=' . urlencode('设置保存成功，部分配置需要重启服务器生效'));
            
        } catch (\Exception $e) {
            $this->logger->error('Save settings error', [
                'error' => $e->getMessage(),
            ]);
            
            return Response::redirect('/settings?error=' . urlencode('保存设置时发生错误: ' . $e->getMessage()));
        }
    }

    /**
     * 验证设置数据
     */
    private function validateSettings(array $data): array
    {
        $errors = [];
        
        // 验证系统名称
        if (isset($data['SYSTEM_NAME'])) {
            if (empty($data['SYSTEM_NAME']) || strlen($data['SYSTEM_NAME']) > 100) {
                $errors[] = '系统名称不能为空且长度不能超过100字符';
            }
        }
        
        // 验证网站URL
        if (isset($data['WEBSITE_URL'])) {
            if (empty($data['WEBSITE_URL']) || !filter_var($data['WEBSITE_URL'], FILTER_VALIDATE_URL)) {
                $errors[] = '网站URL格式不正确';
            }
        }
        
        // 验证网站Logo
        if (isset($data['WEBSITE_LOGO']) && !empty($data['WEBSITE_LOGO'])) {
            $logo = $data['WEBSITE_LOGO'];
            // 检查是否为完整URL或本地路径
            if (!filter_var($logo, FILTER_VALIDATE_URL) && !preg_match('/^\/[a-zA-Z0-9\/_.-]+\.(jpg|jpeg|png|gif|svg)$/i', $logo)) {
                $errors[] = '网站Logo必须是有效的URL或本地图片路径';
            }
        }
        
        // 验证API密钥
        if (isset($data['API_SECRET_KEY'])) {
            if (!empty($data['API_SECRET_KEY'])) {
                if (strlen($data['API_SECRET_KEY']) < 16 || strlen($data['API_SECRET_KEY']) > 64) {
                    $errors[] = 'API通信密钥长度必须在16-64位字符之间';
                }
                if (!preg_match('/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{}|;:,.<>?]+$/', $data['API_SECRET_KEY'])) {
                    $errors[] = 'API通信密钥只能包含字母、数字和常见符号';
                }
            }
        }
        
        // 验证加密算法
        if (isset($data['API_ENCRYPT_METHOD'])) {
            $allowedMethods = ['AES-256-CBC', 'AES-128-CBC', 'DES-EDE3-CBC'];
            if (!in_array($data['API_ENCRYPT_METHOD'], $allowedMethods)) {
                $errors[] = '不支持的加密算法';
            }
        }
        
        // 验证JWT有效期
        if (isset($data['JWT_EXPIRY'])) {
            $expiry = (int)$data['JWT_EXPIRY'];
            if ($expiry < 300 || $expiry > 86400) {
                $errors[] = 'JWT有效期必须在300-86400秒之间';
            }
        }
        
        // 验证限流设置
        $rateLimitFields = [
            'RATE_LIMIT_VERIFY_MAX' => [1, 1000, '验证接口最大请求数'],
            'RATE_LIMIT_VERIFY_PER' => [10, 3600, '验证接口时间窗口'],
            'RATE_LIMIT_LOGIN_MAX' => [1, 100, '登录接口最大请求数'],
            'RATE_LIMIT_LOGIN_PER' => [10, 3600, '登录接口时间窗口'],
        ];
        
        foreach ($rateLimitFields as $field => [$min, $max, $name]) {
            if (isset($data[$field])) {
                $value = (int)$data[$field];
                if ($value < $min || $value > $max) {
                    $errors[] = "{$name}必须在{$min}-{$max}之间";
                }
            }
        }
        
        return $errors;
    }

    /**
     * 解析.env文件
     */
    private function parseEnvFile(string $content): array
    {
        $envVars = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $envVars[trim($key)] = trim($value, '"\'');
            }
        }
        
        return $envVars;
    }

    /**
     * 构建.env文件内容
     */
    private function buildEnvContent(array $envVars): string
    {
        $content = "# 网络验证系统配置文件\n";
        $content .= "# 生成时间: " . date('Y-m-d H:i:s') . "\n\n";
        
        // 按组织结构输出
        $groups = [
            '# 应用配置' => ['SYSTEM_NAME', 'WEBSITE_URL', 'WEBSITE_LOGO', 'JWT_SECRET', 'JWT_EXPIRY', 'JWT_ALGORITHM'],
            '# 数据库配置' => ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET'],
            '# 安全配置' => ['API_SECRET_KEY', 'API_ENCRYPT_METHOD', 'CLIENT_AUTH_REQUIRED', 'API_KEY_REQUIRED'],
            '# 限流配置' => ['RATE_LIMIT_VERIFY_MAX', 'RATE_LIMIT_VERIFY_PER', 'RATE_LIMIT_LOGIN_MAX', 'RATE_LIMIT_LOGIN_PER'],
        ];
        
        foreach ($groups as $groupTitle => $keys) {
            $content .= $groupTitle . "\n";
            foreach ($keys as $key) {
                if (isset($envVars[$key])) {
                    $value = $envVars[$key];
                    // 如果值包含空格或特殊字符，用引号包围
                    if (preg_match('/[\s#]/', $value)) {
                        $value = '"' . $value . '"';
                    }
                    $content .= "{$key}={$value}\n";
                }
            }
            $content .= "\n";
        }
        
        // 添加其他未分组的变量
        foreach ($envVars as $key => $value) {
            $found = false;
            foreach ($groups as $keys) {
                if (in_array($key, $keys)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                if (preg_match('/[\s#]/', $value)) {
                    $value = '"' . $value . '"';
                }
                $content .= "{$key}={$value}\n";
            }
        }
        
        return $content;
    }

    /**
     * 修改管理员密码
     */
    public function changePassword(Request $request): Response
    {
        try {
            $data = $request->all();
            
            // 验证必需字段
            if (empty($data['current_password'])) {
                return Response::redirect('/settings?error=' . urlencode('请输入当前密码'));
            }
            
            // 验证新密码（如果提供）
            if (!empty($data['new_password'])) {
                if (strlen($data['new_password']) < 6) {
                    return Response::redirect('/settings?error=' . urlencode('新密码至少需要6位字符'));
                }
                
                if ($data['new_password'] !== $data['confirm_password']) {
                    return Response::redirect('/settings?error=' . urlencode('两次输入的新密码不一致'));
                }
            }
            
            // 获取当前登录的管理员ID
            $currentAdminId = SessionManager::getAdminId();
            if (!$currentAdminId) {
                return Response::redirect('/login?error=' . urlencode('请先登录'));
            }
            
            // 获取当前管理员信息
            $currentAdmin = $this->adminModel->find($currentAdminId);
            if (!$currentAdmin) {
                return Response::redirect('/settings?error=' . urlencode('管理员信息不存在'));
            }
            
            $changes = [];
            
            // 验证当前密码
            $currentPasswordValid = $this->adminModel->verifyPassword(
                $data['current_password'], 
                $currentAdmin['password_hash']
            );
            
            if (!$currentPasswordValid) {
                return Response::redirect('/settings?error=' . urlencode('当前密码错误'));
            }
            
            // 如果提供了新用户名
            if (!empty($data['new_username']) && $data['new_username'] !== $currentAdmin['username']) {
                $updateData = ['username' => $data['new_username']];
                $this->adminModel->update($currentAdminId, $updateData);
                $changes[] = '用户名';
            }
            
            // 如果提供了新密码
            if (!empty($data['new_password'])) {
                $this->adminModel->updatePassword($currentAdminId, $data['new_password']);
                $changes[] = '密码';
            }
            
            if (empty($changes)) {
                return Response::redirect('/settings?info=' . urlencode('没有任何修改'));
            }
            
            // 记录操作日志
            $this->adminLogModel->logAction(
                '修改管理员账号',
                '修改了: ' . implode(', ', $changes),
                $request->getClientIp(),
                $request->getUserAgent()
            );
            
            return Response::redirect('/settings?success=' . urlencode('管理员账号修改成功: ' . implode(', ', $changes)));
            
        } catch (\Exception $e) {
            $this->logger->error('Change password error', [
                'error' => $e->getMessage(),
            ]);
            
            return Response::redirect('/settings?error=' . urlencode('修改密码时发生错误: ' . $e->getMessage()));
        }
    }

    /**
     * 清理缓存
     */
    public function clearCache(Request $request): Response
    {
        try {
            $clearedItems = [];
            
            // 清理会话文件
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
                $clearedItems[] = '用户会话';
            }
            
            // 清理临时文件
            $tempDir = PROJECT_ROOT . '/storage/temp';
            if (is_dir($tempDir)) {
                $files = glob($tempDir . '/*');
                $deletedFiles = 0;
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < time() - 3600) { // 删除1小时前的临时文件
                        unlink($file);
                        $deletedFiles++;
                    }
                }
                if ($deletedFiles > 0) {
                    $clearedItems[] = "临时文件({$deletedFiles}个)";
                }
            }
            
            // 清理日志文件（保留最近7天）
            $logDir = PROJECT_ROOT . '/storage/logs';
            if (is_dir($logDir)) {
                $logFiles = glob($logDir . '/*.log');
                $deletedLogs = 0;
                foreach ($logFiles as $logFile) {
                    if (filemtime($logFile) < time() - 7 * 24 * 3600) { // 删除7天前的日志
                        unlink($logFile);
                        $deletedLogs++;
                    }
                }
                if ($deletedLogs > 0) {
                    $clearedItems[] = "旧日志文件({$deletedLogs}个)";
                }
            }
            
            // 记录操作
            $this->adminLogModel->logAction(
                '清理系统缓存',
                '清理了: ' . implode(', ', $clearedItems),
                $request->getClientIp(),
                $request->getUserAgent()
            );
            
            if (empty($clearedItems)) {
                return Response::redirect('/settings?info=' . urlencode('没有需要清理的缓存项'));
            }
            
            return Response::redirect('/settings?success=' . urlencode('缓存清理成功: ' . implode(', ', $clearedItems)));
            
        } catch (\Exception $e) {
            $this->logger->error('Clear cache error', [
                'error' => $e->getMessage(),
            ]);
            
            return Response::redirect('/settings?error=' . urlencode('清理缓存失败: ' . $e->getMessage()));
        }
    }

    /**
     * 导出数据
     */
    public function exportData(Request $request): Response
    {
        try {
            // 创建数据库连接
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $_ENV['DB_CONNECTION'] ?? 'mysql',
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_PORT'] ?? '3306',
                $_ENV['DB_NAME'] ?? 'auth_system',
                $_ENV['DB_CHARSET'] ?? 'utf8mb4'
            );

            $pdo = new \PDO(
                $dsn,
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASS'] ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            // 导出所有表的数据
            $tables = ['licenses', 'usage_logs', 'admin_logs', 'admin_settings'];
            $exportData = [];

            foreach ($tables as $table) {
                try {
                    $stmt = $pdo->query("SELECT * FROM {$table}");
                    $exportData[$table] = $stmt->fetchAll();
                } catch (\Exception $e) {
                    $exportData[$table] = ['error' => $e->getMessage()];
                }
            }

            // 生成JSON格式的导出文件
            $jsonData = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            // 记录导出操作
            $this->adminLogModel->logAction(
                '导出系统数据',
                '导出了所有系统数据',
                $request->getClientIp(),
                $request->getUserAgent()
            );

            $filename = 'system_export_' . date('Y-m-d_H-i-s') . '.json';
            
            return new Response($jsonData, 200, [
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Content-Length' => strlen($jsonData)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Export data error', [
                'error' => $e->getMessage(),
            ]);

            return Response::redirect('/settings?error=' . urlencode('数据导出失败: ' . $e->getMessage()));
        }
    }

    /**
     * 检查更新
     */
    public function checkUpdate(Request $request): Response
    {
        try {
            $currentVersion = '2.0.0';
            $updateInfo = [];
            
            // 检查系统文件完整性
            $coreFiles = [
                'index.php',
                'src/Core/Application.php',
                'src/Core/Router/Router.php',
                'src/Core/Container/Container.php',
            ];
            
            $missingFiles = [];
            foreach ($coreFiles as $file) {
                if (!file_exists(PROJECT_ROOT . '/' . $file)) {
                    $missingFiles[] = $file;
                }
            }
            
            // 检查数据库结构
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $_ENV['DB_CONNECTION'] ?? 'mysql',
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_PORT'] ?? '3306',
                $_ENV['DB_NAME'] ?? 'auth_system',
                $_ENV['DB_CHARSET'] ?? 'utf8mb4'
            );

            $pdo = new \PDO(
                $dsn,
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASS'] ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $requiredTables = ['licenses', 'usage_logs', 'admin_logs', 'admin_settings'];
            $missingTables = [];
            
            foreach ($requiredTables as $table) {
                try {
                    $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
                } catch (\Exception $e) {
                    $missingTables[] = $table;
                }
            }
            
            // 生成检查报告
            $updateInfo[] = "当前版本: {$currentVersion}";
            $updateInfo[] = "检查时间: " . date('Y-m-d H:i:s');
            $updateInfo[] = "PHP版本: " . PHP_VERSION;
            
            if (empty($missingFiles) && empty($missingTables)) {
                $updateInfo[] = "✅ 系统文件完整";
                $updateInfo[] = "✅ 数据库结构正常";
                $status = 'success';
                $message = '系统检查完成，一切正常！';
            } else {
                if (!empty($missingFiles)) {
                    $updateInfo[] = "❌ 缺失文件: " . implode(', ', $missingFiles);
                }
                if (!empty($missingTables)) {
                    $updateInfo[] = "❌ 缺失数据表: " . implode(', ', $missingTables);
                }
                $status = 'error';
                $message = '系统检查发现问题，请联系管理员';
            }
            
            // 记录检查操作
            $this->adminLogModel->logAction(
                '检查系统更新',
                implode('; ', $updateInfo),
                $request->getClientIp(),
                $request->getUserAgent()
            );

            return Response::redirect("/settings?{$status}=" . urlencode($message . "\n\n详细信息:\n" . implode("\n", $updateInfo)));

        } catch (\Exception $e) {
            $this->logger->error('Check update error', [
                'error' => $e->getMessage(),
            ]);

            return Response::redirect('/settings?error=' . urlencode('检查更新失败: ' . $e->getMessage()));
        }
    }

    /**
     * 处理Logo文件上传
     */
    private function handleLogoUpload(array $file): ?string
    {
        try {
            // 验证文件类型
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new \Exception('只支持 JPG, PNG, GIF, SVG 格式的图片文件');
            }

            // 验证文件大小 (2MB max)
            $maxSize = 2 * 1024 * 1024; // 2MB
            if ($file['size'] > $maxSize) {
                throw new \Exception('文件大小不能超过 2MB');
            }

            // 生成唯一文件名
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '_' . uniqid() . '.' . $extension;
            
            // 确保上传目录存在
            $uploadDir = PROJECT_ROOT . '/storage/uploads/logos';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // 移动文件到目标位置
            $targetPath = $uploadDir . '/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // 返回相对于网站根目录的路径
                return '/storage/uploads/logos/' . $filename;
            } else {
                throw new \Exception('文件上传失败');
            }

        } catch (\Exception $e) {
            $this->logger->error('Logo upload error', [
                'error' => $e->getMessage(),
                'file' => $file['name'] ?? 'unknown'
            ]);
            return null;
        }
    }
}

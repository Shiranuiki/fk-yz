<?php

namespace AuthSystem\Web\Controller;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Models\Admin;
use AuthSystem\Models\AdminLog;
use AuthSystem\Core\Logger\Logger;
use AuthSystem\Core\Session\SessionManager;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Web认证控制器
 * 
 * @package AuthSystem\Web\Controller
 */
class AuthController
{
    private Admin $adminModel;
    private AdminLog $adminLogModel;
    private Logger $logger;
    private string $jwtSecret;

    public function __construct(Admin $adminModel, AdminLog $adminLogModel, Logger $logger)
    {
        $this->adminModel = $adminModel;
        $this->adminLogModel = $adminLogModel;
        $this->logger = $logger;
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'default-secret';
    }

    /**
     * 显示登录页面
     */
    public function showLogin(Request $request): Response
    {
        // 如果已经登录，重定向到首页
        if (SessionManager::isLoggedIn()) {
            return Response::redirect('/');
        }
        
        $error = $request->get('error', '');
        $html = $this->renderLoginPage($error);
        return Response::html($html);
    }

    /**
     * 处理登录
     */
    public function login(Request $request): Response
    {
        try {
            $data = $request->all();
            
            if (!isset($data['username']) || !isset($data['password'])) {
                return $this->showLogin($request, '用户名和密码不能为空');
            }

            $username = $data['username'];
            $password = $data['password'];

            // 查找管理员
            $admin = $this->adminModel->findByUsername($username);
            
            if (!$admin || !$this->adminModel->verifyPassword($password, $admin['password_hash'])) {
                // 记录登录失败
                $this->adminLogModel->logAction(
                    '登录失败',
                    "用户名: {$username}",
                    $request->getClientIp(),
                    $request->getUserAgent()
                );

                return Response::redirect('/login?error=' . urlencode('用户名或密码错误'));
            }

            // 更新最后登录时间
            $this->adminModel->updateLastLogin($admin['id']);

            // 设置登录会话
            SessionManager::setAdminLogin($admin['id'], $admin['username']);

            // 记录登录成功
            $this->adminLogModel->logAction(
                '登录成功',
                "管理员: {$username}",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            $this->logger->info('Web admin login successful', [
                'username' => $username,
                'ip' => $request->getClientIp(),
            ]);

            return Response::redirect('/');

        } catch (\Exception $e) {
            $this->logger->error('Web login error', [
                'error' => $e->getMessage(),
            ]);

            return Response::redirect('/login?error=' . urlencode('登录时发生错误，请稍后再试'));
        }
    }

    /**
     * 处理登出
     */
    public function logout(Request $request): Response
    {
        try {
            $username = SessionManager::getAdminUsername();
            
            // 记录登出
            $this->adminLogModel->logAction(
                '登出',
                "管理员 {$username} 登出",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            $this->logger->info('Web admin logout', [
                'username' => $username,
                'ip' => $request->getClientIp(),
            ]);

            // 清除会话
            SessionManager::clearAdminLogin();

            return Response::redirect('/login');

        } catch (\Exception $e) {
            $this->logger->error('Web logout error', [
                'error' => $e->getMessage(),
            ]);

            // 即使出错也要清除会话
            SessionManager::clearAdminLogin();
            return Response::redirect('/login');
        }
    }

    /**
     * 渲染登录页面
     */
    private function renderLoginPage(string $error = ''): string
    {
        // 处理URL参数中的错误消息 - 使用现代化通知系统
        if (empty($error) && isset($_GET['error'])) {
            $error = $_GET['error'];
        }
        
        $notificationScript = '';
        if (!empty($error)) {
            $escapedError = htmlspecialchars($error);
            $notificationScript = "<script>document.addEventListener('DOMContentLoaded', function() { notify.error('{$escapedError}'); });</script>";
        }
        $errorHtml = '';

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 网络验证系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            text-align: center;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card">
                    <div class="login-header">
                        <i class="bi bi-shield-check fs-1 mb-3"></i>
                        <h3>网络验证系统</h3>
                        <p class="mb-0">管理员登录</p>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="/login">
                            <div class="mb-3">
                                <label for="username" class="form-label">用户名</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">密码</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-box-arrow-in-right"></i> 登录
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/modal.js"></script>
    <script src="/assets/js/notifications.js"></script>
    {$notificationScript}
</body>
</html>
HTML;
    }
}

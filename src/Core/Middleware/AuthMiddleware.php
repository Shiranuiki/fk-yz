<?php

namespace AuthSystem\Core\Middleware;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Session\SessionManager;

/**
 * 认证中间件
 * 
 * @package AuthSystem\Core\Middleware
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * 需要认证的路径
     */
    private array $protectedPaths = [
        '/',
        '/licenses',
        '/logs',
        '/settings',
    ];

    public function handle(Request $request, callable $next): Response
    {
        $path = $request->getUri();
        $path = strtok($path, '?'); // 移除查询字符串

        // 检查是否需要认证
        if ($this->needsAuth($path)) {
            // 检查会话是否过期
            if (SessionManager::isExpired(3600)) { // 1小时过期
                SessionManager::clearAdminLogin();
                return Response::redirect('/login?error=' . urlencode('会话已过期，请重新登录'));
            }

            // 检查是否已登录
            if (!SessionManager::isLoggedIn()) {
                return Response::redirect('/login?error=' . urlencode('请先登录'));
            }

            // 更新活动时间
            SessionManager::updateActivity();
        }

        return $next($request);
    }

    /**
     * 检查路径是否需要认证
     */
    private function needsAuth(string $path): bool
    {
        // 只有登录页面不需要认证
        if ($path === '/login' || strpos($path, '/api/') === 0) {
            return false;
        }

        // 检查是否在保护路径中
        foreach ($this->protectedPaths as $protectedPath) {
            if ($path === $protectedPath || strpos($path, $protectedPath . '/') === 0) {
                return true;
            }
        }

        // logout需要认证（用户必须先登录才能登出）
        if ($path === '/logout') {
            return true;
        }

        return false;
    }
}

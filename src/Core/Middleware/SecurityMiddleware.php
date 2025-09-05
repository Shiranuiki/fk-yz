<?php

namespace AuthSystem\Core\Middleware;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;

/**
 * 安全中间件
 * 
 * @package AuthSystem\Core\Middleware
 */
class SecurityMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // 设置安全头
        $response = $next($request);
        
        $this->addSecurityHeaders($response);
        
        return $response;
    }

    /**
     * 添加安全头
     */
    private function addSecurityHeaders(Response $response): void
    {
        // 防止点击劫持
        $response->setHeader('X-Frame-Options', 'DENY');
        
        // 防止MIME类型嗅探
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        
        // XSS保护
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        
        // 严格传输安全（仅在HTTPS时）
        if ($this->isHttps()) {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        
        // 内容安全策略
        $response->setHeader('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' cdn.jsdelivr.net;");
        
        // 引用者策略
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // 权限策略
        $response->setHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }

    /**
     * 检查是否为HTTPS
     */
    private function isHttps(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            $_SERVER['SERVER_PORT'] == 443 ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );
    }
}

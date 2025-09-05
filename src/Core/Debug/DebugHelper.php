<?php

namespace AuthSystem\Core\Debug;

use AuthSystem\Core\Logger\Logger;

/**
 * 调试助手类
 * 
 * @package AuthSystem\Core\Debug
 */
class DebugHelper
{
    private static ?Logger $logger = null;
    
    /**
     * 设置日志记录器
     */
    public static function setLogger(Logger $logger): void
    {
        self::$logger = $logger;
    }
    
    /**
     * 记录调试信息
     */
    public static function log(string $message, array $context = []): void
    {
        if (self::$logger) {
            self::$logger->debug($message, $context);
        }
        
        if (defined('DEBUG') && DEBUG) {
            error_log("[DEBUG] {$message} " . json_encode($context));
        }
    }
    
    /**
     * 记录路由信息
     */
    public static function logRoute(string $method, string $uri, string $handler): void
    {
        self::log("Route matched", [
            'method' => $method,
            'uri' => $uri,
            'handler' => $handler
        ]);
    }
    
    /**
     * 记录中间件执行
     */
    public static function logMiddleware(string $middleware, string $action = 'executing'): void
    {
        self::log("Middleware {$action}", [
            'middleware' => $middleware
        ]);
    }
    
    /**
     * 记录请求信息
     */
    public static function logRequest(string $method, string $uri, array $data = []): void
    {
        self::log("Request received", [
            'method' => $method,
            'uri' => $uri,
            'data_keys' => array_keys($data)
        ]);
    }
    
    /**
     * 记录响应信息
     */
    public static function logResponse(int $statusCode, string $type = 'html'): void
    {
        self::log("Response sent", [
            'status_code' => $statusCode,
            'type' => $type
        ]);
    }
    
    /**
     * 记录会话信息
     */
    public static function logSession(string $action, array $data = []): void
    {
        self::log("Session {$action}", $data);
    }
}

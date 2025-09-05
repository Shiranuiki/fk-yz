<?php

namespace AuthSystem\Core\Session;

use AuthSystem\Core\Debug\DebugHelper;

/**
 * 会话管理器
 * 
 * @package AuthSystem\Core\Session
 */
class SessionManager
{
    private static bool $started = false;

    /**
     * 启动会话
     */
    public static function start(): void
    {
        if (!self::$started) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            self::$started = true;
        }
    }

    /**
     * 设置会话变量
     */
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * 获取会话变量
     */
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * 检查会话变量是否存在
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * 删除会话变量
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * 清空所有会话数据
     */
    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
    }

    /**
     * 销毁会话
     */
    public static function destroy(): void
    {
        self::start();
        session_destroy();
        self::$started = false;
    }

    /**
     * 重新生成会话ID
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    /**
     * 检查用户是否已登录
     */
    public static function isLoggedIn(): bool
    {
        return self::has('admin_id') && self::has('admin_username');
    }

    /**
     * 获取当前登录的管理员ID
     */
    public static function getAdminId(): ?int
    {
        return self::get('admin_id');
    }

    /**
     * 获取当前登录的管理员用户名
     */
    public static function getAdminUsername(): ?string
    {
        return self::get('admin_username');
    }

    /**
     * 设置管理员登录状态
     */
    public static function setAdminLogin(int $adminId, string $username): void
    {
        self::regenerate(); // 防止会话固定攻击
        self::set('admin_id', $adminId);
        self::set('admin_username', $username);
        self::set('login_time', time());
        
        DebugHelper::logSession('admin_login', [
            'admin_id' => $adminId,
            'username' => $username
        ]);
    }

    /**
     * 清除管理员登录状态
     */
    public static function clearAdminLogin(): void
    {
        $username = self::get('admin_username');
        
        self::remove('admin_id');
        self::remove('admin_username');
        self::remove('login_time');
        
        DebugHelper::logSession('admin_logout', [
            'username' => $username
        ]);
    }

    /**
     * 检查会话是否过期
     */
    public static function isExpired(int $maxLifetime = 3600): bool
    {
        $loginTime = self::get('login_time');
        if (!$loginTime) {
            return true;
        }
        
        return (time() - $loginTime) > $maxLifetime;
    }

    /**
     * 更新会话活动时间
     */
    public static function updateActivity(): void
    {
        self::set('last_activity', time());
    }

    /**
     * 设置Flash消息（一次性消息）
     */
    public static function setFlashMessage(string $type, string $message): void
    {
        self::start();
        $_SESSION['flash_messages'][$type] = $message;
    }

    /**
     * 获取Flash消息（获取后自动删除）
     */
    public static function getFlashMessage(string $type): ?string
    {
        self::start();
        $message = $_SESSION['flash_messages'][$type] ?? null;
        if ($message) {
            unset($_SESSION['flash_messages'][$type]);
        }
        return $message;
    }

    /**
     * 检查是否有Flash消息
     */
    public static function hasFlashMessage(string $type): bool
    {
        self::start();
        return isset($_SESSION['flash_messages'][$type]);
    }

    /**
     * 清除所有Flash消息
     */
    public static function clearFlashMessages(): void
    {
        self::start();
        unset($_SESSION['flash_messages']);
    }
}

<?php

namespace AuthSystem\Core\Http;

/**
 * HTTP请求类
 * 
 * @package AuthSystem\Core\Http
 */
class Request
{
    private array $get;
    private array $post;
    private array $files;
    private array $server;
    private array $headers;
    private string $method;
    private string $uri;
    private string $body;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->body = file_get_contents('php://input');
        $this->headers = $this->parseHeaders();
    }

    /**
     * 从全局变量创建请求实例
     */
    public static function createFromGlobals(): self
    {
        return new self();
    }

    /**
     * 获取请求方法
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * 获取请求URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * 获取GET参数
     */
    public function get(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }

    /**
     * 获取POST参数
     */
    public function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    /**
     * 获取所有请求数据（GET + POST）
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    /**
     * 获取文件
     */
    public function files(string $key = null)
    {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? null;
    }

    /**
     * 获取服务器变量
     */
    public function server(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->server;
        }
        return $this->server[$key] ?? $default;
    }

    /**
     * 获取请求头
     */
    public function header(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }
        return $this->headers[$key] ?? $default;
    }

    /**
     * 获取请求体
     */
    public function getBody(): string
    {
        return $this->body;
    }
    
    /**
     * 设置请求体
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }
    
    /**
     * 获取HTTP头
     */
    public function getHeader(string $name): ?string
    {
        $name = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$name] ?? null;
    }

    /**
     * 获取JSON数据
     */
    public function json(): ?array
    {
        $data = json_decode($this->body, true);
        return $data ?: null;
    }

    /**
     * 获取客户端IP
     */
    public function getClientIp(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($this->server[$key])) {
                $ip = $this->server[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * 获取用户代理
     */
    public function getUserAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * 检查是否为AJAX请求
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * 检查是否为JSON请求
     */
    public function isJson(): bool
    {
        return strpos($this->header('Content-Type', ''), 'application/json') !== false;
    }

    /**
     * 检查是否为POST请求
     */
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * 检查是否为GET请求
     */
    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    /**
     * 检查是否为PUT请求
     */
    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }

    /**
     * 检查是否为DELETE请求
     */
    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }

    /**
     * 解析请求头
     */
    private function parseHeaders(): array
    {
        $headers = [];
        
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $header = ucwords(strtolower($header), '-');
                $headers[$header] = $value;
            }
        }
        
        return $headers;
    }
}

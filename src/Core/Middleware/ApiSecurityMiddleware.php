<?php

namespace AuthSystem\Core\Middleware;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Config\Config;
use AuthSystem\Core\Logger\Logger;

/**
 * API安全中间件
 * 
 * @package AuthSystem\Core\Middleware
 */
class ApiSecurityMiddleware implements MiddlewareInterface
{
    private Config $config;
    private Logger $logger;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * 处理API安全验证
     */
    public function handle(Request $request, callable $next): Response
    {
        try {
            // 检查是否需要客户端身份验证
            if ($this->isClientAuthRequired()) {
                if (!$this->validateClientAuth($request)) {
                    $this->logger->warning('API Client authentication failed', [
                        'ip' => $request->getClientIp(),
                        'user_agent' => $request->getUserAgent(),
                        'path' => $request->getUri()
                    ]);
                    
                    return Response::unauthorized('需要客户端身份验证');
                }
            }

            // 检查API密钥（如果启用了API密钥验证）
            if ($this->isApiKeyRequired()) {
                $apiSecret = $this->config->get('security.api_secret_key', '');
                if (!empty($apiSecret)) {
                    if (!$this->validateApiKey($request, $apiSecret)) {
                        $this->logger->warning('API Key validation failed', [
                            'ip' => $request->getClientIp(),
                            'user_agent' => $request->getUserAgent(),
                            'path' => $request->getUri()
                        ]);
                        
                        return Response::unauthorized('API密钥无效');
                    }
                } else {
                    // API密钥验证已启用但密钥为空
                    $this->logger->warning('API Key validation enabled but key is empty', [
                        'ip' => $request->getClientIp(),
                        'path' => $request->getUri()
                    ]);
                    
                    return Response::unauthorized('API密钥验证已启用但未配置密钥');
                }
            }

            // 处理加密请求（如果启用）
            $request = $this->handleEncryption($request);

            // 继续处理请求
            $response = $next($request);

            // 处理响应加密
            return $this->handleResponseEncryption($response);

        } catch (\Exception $e) {
            $this->logger->error('API Security middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Response::error('安全验证失败', 500);
        }
    }

    /**
     * 检查是否需要客户端身份验证
     */
    private function isClientAuthRequired(): bool
    {
        $required = $_ENV['CLIENT_AUTH_REQUIRED'] ?? 'false';
        return filter_var($required, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * 检查是否需要API密钥验证
     */
    private function isApiKeyRequired(): bool
    {
        $required = $_ENV['API_KEY_REQUIRED'] ?? 'false';
        return filter_var($required, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * 验证客户端身份
     */
    private function validateClientAuth(Request $request): bool
    {
        // 检查客户端证书或签名
        $clientId = $request->getHeader('X-Client-ID');
        $clientSignature = $request->getHeader('X-Client-Signature');
        $timestamp = $request->getHeader('X-Timestamp');

        if (empty($clientId) || empty($clientSignature) || empty($timestamp)) {
            return false;
        }

        // 验证时间戳（防止重放攻击）
        $currentTime = time();
        $requestTime = (int)$timestamp;
        if (abs($currentTime - $requestTime) > 300) { // 5分钟有效期
            return false;
        }

        // 这里可以实现更复杂的客户端验证逻辑
        // 例如：验证客户端签名、检查白名单等
        return true;
    }

    /**
     * 验证API密钥
     */
    private function validateApiKey(Request $request, string $expectedKey): bool
    {
        // 从Header获取API密钥
        $apiKey = $request->getHeader('X-API-Key') 
               ?? $request->getHeader('Authorization') 
               ?? $request->get('api_key');

        // 清理Bearer token格式
        if ($apiKey && strpos($apiKey, 'Bearer ') === 0) {
            $apiKey = substr($apiKey, 7);
        }

        return hash_equals($expectedKey, $apiKey ?? '');
    }

    /**
     * 处理请求加密
     */
    private function handleEncryption(Request $request): Request
    {
        $encryptMethod = $_ENV['API_ENCRYPT_METHOD'] ?? '';
        if (empty($encryptMethod) || $encryptMethod === 'none') {
            return $request;
        }

        // 检查是否有加密数据
        $encryptedData = $request->getHeader('X-Encrypted-Data');
        if (empty($encryptedData)) {
            return $request;
        }

        try {
            // 解密数据
            $decryptedData = $this->decryptData($encryptedData, $encryptMethod);
            if ($decryptedData) {
                // 更新请求体
                $request->setBody($decryptedData);
            }
        } catch (\Exception $e) {
            $this->logger->error('Request decryption failed', [
                'error' => $e->getMessage(),
                'method' => $encryptMethod
            ]);
        }

        return $request;
    }

    /**
     * 处理响应加密
     */
    private function handleResponseEncryption(Response $response): Response
    {
        $encryptMethod = $_ENV['API_ENCRYPT_METHOD'] ?? '';
        if (empty($encryptMethod) || $encryptMethod === 'none') {
            return $response;
        }

        try {
            $body = $response->getBody();
            $encryptedBody = $this->encryptData($body, $encryptMethod);
            
            if ($encryptedBody) {
                $response->setBody($encryptedBody);
                $response->setHeader('X-Encrypted-Response', 'true');
                $response->setHeader('X-Encryption-Method', $encryptMethod);
            }
        } catch (\Exception $e) {
            $this->logger->error('Response encryption failed', [
                'error' => $e->getMessage(),
                'method' => $encryptMethod
            ]);
        }

        return $response;
    }

    /**
     * 加密数据
     */
    private function encryptData(string $data, string $method): ?string
    {
        $apiSecret = $_ENV['API_SECRET_KEY'] ?? '';
        if (empty($apiSecret)) {
            return null;
        }

        try {
            switch ($method) {
                case 'AES-256-CBC':
                    $iv = openssl_random_pseudo_bytes(16);
                    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $apiSecret, 0, $iv);
                    return base64_encode($iv . $encrypted);
                    
                case 'AES-128-CBC':
                    $iv = openssl_random_pseudo_bytes(16);
                    $encrypted = openssl_encrypt($data, 'aes-128-cbc', substr($apiSecret, 0, 16), 0, $iv);
                    return base64_encode($iv . $encrypted);
                    
                case 'DES-EDE3-CBC':
                    $iv = openssl_random_pseudo_bytes(8);
                    $encrypted = openssl_encrypt($data, 'des-ede3-cbc', substr($apiSecret, 0, 24), 0, $iv);
                    return base64_encode($iv . $encrypted);
                    
                default:
                    return null;
            }
        } catch (\Exception $e) {
            $this->logger->error('Encryption failed', [
                'error' => $e->getMessage(),
                'method' => $method
            ]);
            return null;
        }
    }

    /**
     * 解密数据
     */
    private function decryptData(string $encryptedData, string $method): ?string
    {
        $apiSecret = $_ENV['API_SECRET_KEY'] ?? '';
        if (empty($apiSecret)) {
            return null;
        }

        try {
            $data = base64_decode($encryptedData);
            
            switch ($method) {
                case 'AES-256-CBC':
                    $iv = substr($data, 0, 16);
                    $encrypted = substr($data, 16);
                    return openssl_decrypt($encrypted, 'aes-256-cbc', $apiSecret, 0, $iv);
                    
                case 'AES-128-CBC':
                    $iv = substr($data, 0, 16);
                    $encrypted = substr($data, 16);
                    return openssl_decrypt($encrypted, 'aes-128-cbc', substr($apiSecret, 0, 16), 0, $iv);
                    
                case 'DES-EDE3-CBC':
                    $iv = substr($data, 0, 8);
                    $encrypted = substr($data, 8);
                    return openssl_decrypt($encrypted, 'des-ede3-cbc', substr($apiSecret, 0, 24), 0, $iv);
                    
                default:
                    return null;
            }
        } catch (\Exception $e) {
            $this->logger->error('Decryption failed', [
                'error' => $e->getMessage(),
                'method' => $method
            ]);
            return null;
        }
    }
}

<?php

namespace AuthSystem\Api\Controller;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Models\Admin;
use AuthSystem\Models\AdminLog;
use AuthSystem\Core\Logger\Logger;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * 认证API控制器
 * 
 * @package AuthSystem\Api\Controller
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
     * 管理员登录
     */
    public function login(Request $request): Response
    {
        try {
            $data = $request->json();
            
            if (!$data || !isset($data['username']) || !isset($data['password'])) {
                return Response::validationError([
                    'username' => ['用户名是必需的'],
                    'password' => ['密码是必需的'],
                ]);
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

                return Response::unauthorized('用户名或密码错误');
            }

            // 更新最后登录时间
            $this->adminModel->updateLastLogin($admin['id']);

            // 生成JWT令牌
            $token = $this->generateJwtToken($admin);

            // 记录登录成功
            $this->adminLogModel->logAction(
                '登录成功',
                "管理员: {$username}",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            $this->logger->info('Admin login successful', [
                'username' => $username,
                'ip' => $request->getClientIp(),
            ]);

            return Response::success([
                'token' => $token,
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'email' => $admin['email'] ?? null,
                ],
            ], '登录成功');

        } catch (\Exception $e) {
            $this->logger->error('Admin login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Response::error('服务器内部错误', 500);
        }
    }

    /**
     * 管理员登出
     */
    public function logout(Request $request): Response
    {
        try {
            // 记录登出
            $this->adminLogModel->logAction(
                '登出',
                '管理员登出',
                $request->getClientIp(),
                $request->getUserAgent()
            );

            $this->logger->info('Admin logout', [
                'ip' => $request->getClientIp(),
            ]);

            return Response::success([], '登出成功');

        } catch (\Exception $e) {
            $this->logger->error('Admin logout error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('服务器内部错误', 500);
        }
    }

    /**
     * 生成JWT令牌
     */
    private function generateJwtToken(array $admin): string
    {
        $payload = [
            'iss' => 'auth-system',
            'aud' => 'auth-system',
            'iat' => time(),
            'exp' => time() + ($_ENV['JWT_EXPIRY'] ?? 3600),
            'sub' => $admin['id'],
            'username' => $admin['username'],
        ];

        return JWT::encode($payload, $this->jwtSecret, $_ENV['JWT_ALGORITHM'] ?? 'HS256');
    }

    /**
     * 验证JWT令牌
     */
    public function verifyToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, $_ENV['JWT_ALGORITHM'] ?? 'HS256'));
            return (array)$decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
}

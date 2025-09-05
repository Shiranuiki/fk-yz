<?php

namespace AuthSystem\Models;

use PDO;

/**
 * 管理员模型
 * 
 * @package AuthSystem\Models
 */
class Admin extends BaseModel
{
    protected string $table = 'admin_settings';
    protected array $fillable = [
        'username',
        'password_hash',
        'email',
        'last_login_at',
    ];
    protected array $casts = [
        'last_login_at' => 'datetime',
    ];

    /**
     * 根据用户名查找管理员
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * 验证密码
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * 创建密码哈希
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * 更新密码
     */
    public function updatePassword(int $id, string $newPassword): bool
    {
        $hash = $this->hashPassword($newPassword);
        return $this->update($id, ['password_hash' => $hash]);
    }

    /**
     * 更新最后登录时间
     */
    public function updateLastLogin(int $id): bool
    {
        return $this->update($id, ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * 创建默认管理员
     */
    public function createDefaultAdmin(): int
    {
        $data = [
            'username' => 'admin',
            'password_hash' => $this->hashPassword('password'),
            'email' => 'admin@example.com',
        ];

        return $this->create($data);
    }

    /**
     * 检查管理员是否存在
     */
    public function adminExists(): bool
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        return (int)$stmt->fetchColumn() > 0;
    }
}

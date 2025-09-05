<?php

namespace AuthSystem\Models;

use PDO;

/**
 * 管理员操作日志模型
 * 
 * @package AuthSystem\Models
 */
class AdminLog extends BaseModel
{
    protected string $table = 'admin_logs';
    protected array $fillable = [
        'action',
        'detail',
        'ip_address',
        'user_agent',
    ];
    protected array $timestamps = ['created_at']; // 只有created_at，没有updated_at
    protected array $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * 记录管理员操作
     */
    public function logAction(string $action, string $detail = null, string $ipAddress = null, string $userAgent = null): int
    {
        $data = [
            'action' => $action,
            'detail' => $detail,
            'ip_address' => $ipAddress ?: $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $userAgent ?: $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        return $this->create($data);
    }

    /**
     * 获取操作历史
     */
    public function getActionHistory(int $limit = 100): array
    {
        return $this->query()
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * 按操作类型统计
     */
    public function getActionStats(int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT action, COUNT(*) as count 
            FROM {$this->table} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) 
            GROUP BY action 
            ORDER BY count DESC
        ");
        $stmt->execute([$days]);
        
        return $stmt->fetchAll();
    }

    /**
     * 清理旧日志
     */
    public function cleanOldLogs(int $days = 180): int
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        
        return $stmt->rowCount();
    }
}

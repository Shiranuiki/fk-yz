<?php

namespace AuthSystem\Models;

use PDO;

/**
 * 使用日志模型
 * 
 * @package AuthSystem\Models
 */
class UsageLog extends BaseModel
{
    protected string $table = 'usage_logs';
    protected array $fillable = [
        'license_key',
        'machine_code',
        'status',
        'ip_address',
        'user_agent',
    ];
    protected array $timestamps = ['created_at']; // 只有created_at，没有updated_at
    protected array $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * 记录使用日志
     */
    public function logUsage(string $licenseKey, string $machineCode, string $status, string $ipAddress = null, string $userAgent = null): int
    {
        $data = [
            'license_key' => $licenseKey,
            'machine_code' => $machineCode,
            'status' => $status,
            'ip_address' => $ipAddress ?: $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $userAgent ?: $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        return $this->create($data);
    }

    /**
     * 获取许可证的使用历史
     */
    public function getLicenseHistory(string $licenseKey, int $limit = 50): array
    {
        return $this->query()
            ->where('license_key', $licenseKey)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取机器码的使用历史
     */
    public function getMachineHistory(string $machineCode, int $limit = 50): array
    {
        return $this->query()
            ->where('machine_code', $machineCode)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取统计信息
     */
    public function getStats(int $days = 30): array
    {
        $stats = [];
        
        // 总请求数
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total_requests'] = (int)$stmt->fetchColumn();
        
        // 最近N天的请求数
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        $stats['recent_requests'] = (int)$stmt->fetchColumn();
        
        // 成功请求数
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = '验证成功'");
        $stats['successful_requests'] = (int)$stmt->fetchColumn();
        
        // 失败请求数
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status != '验证成功'");
        $stats['failed_requests'] = (int)$stmt->fetchColumn();
        
        // 按状态分组统计
        $stmt = $this->db->query("SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status ORDER BY count DESC");
        $stats['by_status'] = $stmt->fetchAll();
        
        // 按日期分组统计（最近7天）
        $stmt = $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM {$this->table} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
            GROUP BY DATE(created_at) 
            ORDER BY date DESC
        ");
        $stats['by_date'] = $stmt->fetchAll();
        
        return $stats;
    }

    /**
     * 清理旧日志
     */
    public function cleanOldLogs(int $days = 90): int
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        
        return $stmt->rowCount();
    }

    /**
     * 导出日志为CSV
     */
    public function exportToCsv(int $limit = 1000): string
    {
        $logs = $this->query()
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
        
        $csv = "时间,许可证密钥,机器码,状态,IP地址,用户代理\n";
        
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s\n",
                $log['created_at'],
                $log['license_key'],
                $log['machine_code'],
                $log['status'],
                $log['ip_address'],
                str_replace(',', ';', $log['user_agent'])
            );
        }
        
        return $csv;
    }
}

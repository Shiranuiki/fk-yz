<?php

namespace AuthSystem\Models;

use PDO;

/**
 * 许可证模型
 * 
 * @package AuthSystem\Models
 */
class License extends BaseModel
{
    protected string $table = 'licenses';
    protected array $fillable = [
        'license_key',
        'status',
        'machine_code',
        'duration_days',
        'expires_at',
        'last_used_at',
        'machine_note',
    ];
    protected array $casts = [
        'status' => 'int',
        'duration_days' => 'int',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    // 状态常量
    public const STATUS_UNUSED = 0;
    public const STATUS_USED = 1;
    public const STATUS_DISABLED = 2;

    /**
     * 根据许可证密钥查找
     */
    public function findByLicenseKey(string $licenseKey): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE license_key = ?");
        $stmt->execute([$licenseKey]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * 生成新的许可证密钥
     */
    public function generateLicenseKey(string $prefix = null, int $length = null, string $charset = null): string
    {
        // 使用传入参数或默认值
        $prefix = $prefix ?? ($_ENV['LICENSE_PREFIX'] ?? 'zz');
        $length = $length ?? (int)($_ENV['LICENSE_LENGTH'] ?? 18);
        $charset = $charset ?? ($_ENV['LICENSE_CHARSET'] ?? 'abcdefghijklmnopqrstuvwxyz0123456789');
        
        // 计算随机部分长度
        $randomLength = $length - strlen($prefix);
        if ($randomLength <= 0) {
            $randomLength = 10; // 确保至少有10位随机字符
        }
        
        // 生成随机字符串
        $randomPart = '';
        $charsetLength = strlen($charset);
        
        for ($i = 0; $i < $randomLength; $i++) {
            $randomPart .= $charset[random_int(0, $charsetLength - 1)];
        }
        
        return $prefix . $randomPart;
    }

    /**
     * 验证许可证密钥格式
     */
    public function validateLicenseKeyFormat(string $licenseKey): bool
    {
        $prefix = $_ENV['LICENSE_PREFIX'] ?? 'zz';
        $length = (int)($_ENV['LICENSE_LENGTH'] ?? 18);
        $charset = $_ENV['LICENSE_CHARSET'] ?? 'abcdefghijklmnopqrstuvwxyz0123456789';
        
        // 检查长度
        if (strlen($licenseKey) !== $length) {
            return false;
        }
        
        // 检查前缀
        if (!str_starts_with($licenseKey, $prefix)) {
            return false;
        }
        
        // 检查字符集
        $randomPart = substr($licenseKey, strlen($prefix));
        for ($i = 0; $i < strlen($randomPart); $i++) {
            if (strpos($charset, $randomPart[$i]) === false) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * 获取可重用的最小ID
     */
    public function getReusableId(): ?int
    {
        // 查找已删除的ID中最小的一个
        $stmt = $this->db->query("
            SELECT t1.id + 1 as gap_start
            FROM {$this->table} t1
            LEFT JOIN {$this->table} t2 ON t1.id + 1 = t2.id
            WHERE t2.id IS NULL AND t1.id < (SELECT MAX(id) FROM {$this->table})
            ORDER BY gap_start
            LIMIT 1
        ");
        
        $result = $stmt->fetch();
        return $result ? (int)$result['gap_start'] : null;
    }

    /**
     * 重置AUTO_INCREMENT到最小可用值
     */
    public function resetAutoIncrement(): bool
    {
        try {
            // 获取最大ID
            $stmt = $this->db->query("SELECT MAX(id) as max_id FROM {$this->table}");
            $result = $stmt->fetch();
            $maxId = $result ? (int)$result['max_id'] : 0;
            
            // 重置AUTO_INCREMENT
            $nextId = $maxId + 1;
            $this->db->exec("ALTER TABLE {$this->table} AUTO_INCREMENT = {$nextId}");
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 重新整理所有许可证ID，消除空隙
     */
    public function reorderAllIds(): array
    {
        try {
            $this->db->beginTransaction();
            
            // 获取所有许可证，按ID排序
            $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY id ASC");
            $licenses = $stmt->fetchAll();
            
            if (empty($licenses)) {
                $this->db->rollback();
                return ['success' => false, 'message' => '没有许可证需要整理'];
            }
            
            // 创建临时表
            $this->db->exec("CREATE TEMPORARY TABLE temp_licenses LIKE {$this->table}");
            
            // 将数据插入临时表，从ID=1开始连续编号
            $newId = 1;
            $reorderedCount = 0;
            foreach ($licenses as $license) {
                $columns = array_keys($license);
                $placeholders = str_repeat('?,', count($columns) - 1) . '?';
                
                // 替换ID
                $license['id'] = $newId;
                $values = array_values($license);
                
                $columnsStr = implode(',', $columns);
                $stmt = $this->db->prepare("INSERT INTO temp_licenses ({$columnsStr}) VALUES ({$placeholders})");
                $stmt->execute($values);
                
                $reorderedCount++;
                $newId++;
            }
            
            // 删除原表数据
            $this->db->exec("DELETE FROM {$this->table}");
            
            // 将数据从临时表复制回原表
            $this->db->exec("INSERT INTO {$this->table} SELECT * FROM temp_licenses");
            
            // 重置AUTO_INCREMENT
            $this->db->exec("ALTER TABLE {$this->table} AUTO_INCREMENT = {$newId}");
            
            // 删除临时表
            $this->db->exec("DROP TEMPORARY TABLE temp_licenses");
            
            $this->db->commit();
            
            return [
                'success' => true, 
                'message' => "成功整理了 {$reorderedCount} 个许可证的ID",
                'reordered_count' => $reorderedCount,
                'new_max_id' => $newId - 1
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => '整理ID失败: ' . $e->getMessage()];
        }
    }

    /**
     * 创建许可证
     */
    public function createLicense(int $durationDays, string $licenseKey = null, string $prefix = null, int $length = null, string $charset = null): int
    {
        $data = [
            'license_key' => $licenseKey ?: $this->generateLicenseKey($prefix, $length, $charset),
            'status' => self::STATUS_UNUSED,
            'duration_days' => $durationDays,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$durationDays} days")),
        ];

        // 尝试重用已删除的ID
        $reusableId = $this->getReusableId();
        if ($reusableId) {
            return $this->createWithSpecificId($reusableId, $data);
        }

        return $this->create($data);
    }

    /**
     * 使用指定ID创建许可证
     */
    private function createWithSpecificId(int $id, array $data): int
    {
        // 构建SQL语句
        $columns = array_keys($data);
        $placeholders = str_repeat('?,', count($columns) - 1) . '?';
        $columnsStr = implode(',', $columns);
        
        // 插入指定ID的记录
        $sql = "INSERT INTO {$this->table} (id, {$columnsStr}) VALUES (?, {$placeholders})";
        $values = array_merge([$id], array_values($data));
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        
        return $id;
    }

    /**
     * 批量创建许可证
     */
    public function createMultipleLicenses(int $count, int $durationDays, string $prefix = null, int $length = null, string $charset = null): array
    {
        $licenseIds = [];
        
        for ($i = 0; $i < $count; $i++) {
            $licenseIds[] = $this->createLicense($durationDays, null, $prefix, $length, $charset);
        }
        
        return $licenseIds;
    }

    /**
     * 验证许可证
     */
    public function verifyLicense(string $licenseKey, string $machineCode): array
    {
        $license = $this->findByLicenseKey($licenseKey);
        
        if (!$license) {
            return ['success' => false, 'message' => '许可证不存在'];
        }
        
        // 检查状态
        if ($license['status'] === self::STATUS_DISABLED) {
            return ['success' => false, 'message' => '许可证已被禁用'];
        }
        
        // 检查是否过期
        if (strtotime($license['expires_at']) < time()) {
            return ['success' => false, 'message' => '许可证已过期'];
        }
        
        // 检查机器码绑定
        if ($license['machine_code'] === null) {
            // 首次使用，绑定机器码
            $this->update($license['id'], [
                'machine_code' => $machineCode,
                'status' => self::STATUS_USED,
                'last_used_at' => date('Y-m-d H:i:s'),
            ]);
            
            // 计算剩余天数
            $remainingDays = max(0, ceil((strtotime($license['expires_at']) - time()) / 86400));
            
            return [
                'success' => true, 
                'message' => '验证成功，已绑定设备',
                'data' => [
                    'license_id' => $license['id'],
                    'expires_at' => $license['expires_at'],
                    'remaining_days' => $remainingDays,
                    'status' => 'bound'
                ]
            ];
        }
        
        if ($license['machine_code'] === $machineCode) {
            // 机器码匹配，更新最后使用时间
            $this->update($license['id'], [
                'last_used_at' => date('Y-m-d H:i:s'),
            ]);
            
            // 计算剩余天数
            $remainingDays = max(0, ceil((strtotime($license['expires_at']) - time()) / 86400));
            
            return [
                'success' => true, 
                'message' => '验证成功',
                'data' => [
                    'license_id' => $license['id'],
                    'expires_at' => $license['expires_at'],
                    'remaining_days' => $remainingDays,
                    'status' => 'verified'
                ]
            ];
        }
        
        return ['success' => false, 'message' => '机器码不匹配，请使用绑定的设备'];
    }

    /**
     * 禁用许可证
     */
    public function disableLicense(int $id): bool
    {
        return $this->update($id, ['status' => self::STATUS_DISABLED]);
    }

    /**
     * 启用许可证
     */
    public function enableLicense(int $id): bool
    {
        return $this->update($id, ['status' => self::STATUS_USED]);
    }

    /**
     * 解绑设备
     */
    public function unbindDevice(int $id): bool
    {
        return $this->update($id, [
            'machine_code' => null,
            'status' => self::STATUS_UNUSED,
            'last_used_at' => null,
        ]);
    }

    /**
     * 延长有效期
     */
    public function extendExpiry(int $id, int $days): bool
    {
        $license = $this->find($id);
        if (!$license) {
            return false;
        }
        
        $newExpiry = date('Y-m-d H:i:s', strtotime($license['expires_at'] . " +{$days} days"));
        
        return $this->update($id, ['expires_at' => $newExpiry]);
    }

    /**
     * 获取统计信息
     */
    public function getStats(): array
    {
        $stats = [];
        
        // 总数
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = (int)$stmt->fetchColumn();
        
        // 各状态数量
        $statuses = [self::STATUS_UNUSED, self::STATUS_USED, self::STATUS_DISABLED];
        foreach ($statuses as $status) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = ?");
            $stmt->execute([$status]);
            $stats['status_' . $status] = (int)$stmt->fetchColumn();
        }
        
        // 即将过期的许可证（7天内）
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)");
        $stats['expiring_soon'] = (int)$stmt->fetchColumn();
        
        return $stats;
    }

    /**
     * 获取状态文本
     */
    public function getStatusText(int $status): string
    {
        $statusTexts = [
            self::STATUS_UNUSED => '未使用',
            self::STATUS_USED => '已使用',
            self::STATUS_DISABLED => '已禁用',
        ];
        
        return $statusTexts[$status] ?? '未知';
    }
}

<?php

namespace AuthSystem\Web\Controller;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Session\SessionManager;
use AuthSystem\Core\Config\Config;
use AuthSystem\Models\UsageLog;
use AuthSystem\Models\AdminLog;
use AuthSystem\Core\Logger\Logger;

/**
 * Web日志控制器
 * 
 * @package AuthSystem\Web\Controller
 */
class LogController
{
    private UsageLog $usageLogModel;
    private AdminLog $adminLogModel;
    private Logger $logger;
    private Config $config;

    public function __construct(UsageLog $usageLogModel, AdminLog $adminLogModel, Logger $logger, Config $config)
    {
        $this->usageLogModel = $usageLogModel;
        $this->adminLogModel = $adminLogModel;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * 显示日志页面
     */
    public function index(Request $request): Response
    {
        try {
            // 检查管理员是否已登录
            if (!SessionManager::isLoggedIn()) {
                return Response::redirect('/login?error=' . urlencode('请先登录'));
            }
            $type = $request->get('type', 'usage'); // usage 或 admin
            $page = (int)($request->get('page', 1));
            $search = $request->get('search');
            $perPage = 20;

            if ($type === 'admin') {
                $query = $this->adminLogModel->query();
                
                // 搜索功能
                if ($search) {
                    $searchTerm = "%{$search}%";
                    $query->where('action', 'LIKE', $searchTerm)
                          ->orWhere('detail', 'LIKE', $searchTerm)
                          ->orWhere('ip_address', 'LIKE', $searchTerm)
                          ->orWhere('user_agent', 'LIKE', $searchTerm);
                }
                
                $result = $query->orderBy('created_at', 'DESC')->paginate($page, $perPage);
            } else {
                $query = $this->usageLogModel->query();
                
                // 搜索功能
                if ($search) {
                    $searchTerm = "%{$search}%";
                    $query->where('license_key', 'LIKE', $searchTerm)
                          ->orWhere('machine_code', 'LIKE', $searchTerm)
                          ->orWhere('ip_address', 'LIKE', $searchTerm)
                          ->orWhere('status', 'LIKE', $searchTerm);
                }
                
                $result = $query->orderBy('created_at', 'DESC')->paginate($page, $perPage);
            }

            $systemName = $this->config->get('app.name');
            $brandHtml = $this->config->getBrandHtml();
            $html = $this->renderLogPage($result, $type, $search, $systemName, $brandHtml);
            return Response::html($html);

        } catch (\Exception $e) {
            $this->logger->error('Log page error', [
                'error' => $e->getMessage(),
            ]);

            return Response::html('<h1>错误</h1><p>加载日志页面时发生错误</p>');
        }
    }

    /**
     * 渲染日志页面
     */
    private function renderLogPage(array $result, string $type, ?string $search = null, string $systemName = '网络验证系统', string $brandHtml = ''): string
    {
        $logs = $result['data'];
        $pagination = $this->renderPagination($result, $type, $search);
        
        $usageActive = $type === 'usage' ? 'active' : '';
        $adminActive = $type === 'admin' ? 'active' : '';
        $searchValue = htmlspecialchars($search ?? '');
        
        // 根据日志类型设置搜索提示
        if ($type === 'admin') {
            $searchPlaceholder = '搜索操作、详情、IP地址...';
        } else {
            $searchPlaceholder = '搜索许可证密钥、设备码、IP地址、状态...';
        }
        
        // 构建搜索查询字符串用于链接
        $searchQuery = $search ? '&search=' . urlencode($search) : '';
        
        if (empty($brandHtml)) {
            $brandHtml = '<i class="bi bi-shield-check"></i> ' . htmlspecialchars($systemName);
        }

        $logsHtml = '';
        if ($type === 'admin') {
            foreach ($logs as $log) {
                $createdAt = date('Y-m-d H:i:s', strtotime($log['created_at']));
                $logsHtml .= <<<HTML
                    <tr>
                        <td>{$log['id']}</td>
                        <td><span class="badge bg-info">{$log['action']}</span></td>
                        <td>{$log['detail']}</td>
                        <td><code>{$log['ip_address']}</code></td>
                        <td>{$createdAt}</td>
                    </tr>
HTML;
            }
        } else {
            foreach ($logs as $log) {
                $createdAt = date('Y-m-d H:i:s', strtotime($log['created_at']));
                $statusBadge = $this->getStatusBadge($log['status']);
                $logsHtml .= <<<HTML
                    <tr>
                        <td>{$log['id']}</td>
                        <td><code>{$log['license_key']}</code></td>
                        <td><code>{$log['machine_code']}</code></td>
                        <td>{$statusBadge}</td>
                        <td><code>{$log['ip_address']}</code></td>
                        <td>{$createdAt}</td>
                    </tr>
HTML;
            }
        }

        $tableHeaders = $type === 'admin' 
            ? '<th>ID</th><th>操作</th><th>详情</th><th>IP地址</th><th>时间</th>'
            : '<th>ID</th><th>许可证</th><th>机器码</th><th>状态</th><th>IP地址</th><th>时间</th>';

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>日志查看 - {$systemName}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">
{$brandHtml}
            </a>
            <div class="navbar-nav">
                <a class="nav-link" href="/">
                    <i class="bi bi-house"></i> 首页
                </a>
                <a class="nav-link" href="/licenses">
                    <i class="bi bi-key"></i> 许可证管理
                </a>
                <a class="nav-link active" href="/logs">
                    <i class="bi bi-list-ul"></i> 日志查看
                </a>
                <a class="nav-link" href="/settings">
                    <i class="bi bi-gear"></i> 设置
                </a>
                <a class="nav-link" href="/logout">
                    <i class="bi bi-box-arrow-right"></i> 退出
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 mb-4">日志查看</h1>
            </div>
        </div>

        <!-- 日志类型切换 -->
        <div class="row mb-3">
            <div class="col-12">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link {$usageActive}" href="/logs?type=usage{$searchQuery}">
                            <i class="bi bi-activity"></i> 使用日志
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {$adminActive}" href="/logs?type=admin{$searchQuery}">
                            <i class="bi bi-person-gear"></i> 管理日志
                        </a>
                    </li>
                    <li class="nav-item ms-auto">
                        <div class="d-flex gap-2">
                            <a class="nav-link" href="/logs/export?type={$type}{$searchQuery}" onclick="return confirm('确定要导出当前日志数据吗？')">
                                <i class="bi bi-download"></i> 导出数据
                            </a>
                            <button class="nav-link btn btn-link text-warning" onclick="deleteLogRange('{$type}')" title="删除历史日志">
                                <i class="bi bi-clock-history"></i> 清理日志
                            </button>
                            <button class="nav-link btn btn-link text-danger" onclick="clearLogs('{$type}')" title="清空旧日志">
                                <i class="bi bi-trash"></i> 清空日志
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- 搜索功能 -->
        <div class="row mb-3">
            <div class="col-md-8">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="type" value="{$type}">
                    <input type="text" class="form-control me-2" name="search" placeholder="{$searchPlaceholder}" value="{$searchValue}">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-search"></i> 搜索
                    </button>
                </form>
            </div>
        </div>

        <!-- 日志列表 -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                {$tableHeaders}
                            </tr>
                        </thead>
                        <tbody>
                            {$logsHtml}
                        </tbody>
                    </table>
                </div>
                
                {$pagination}
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/modal.js"></script>
    <script src="/assets/js/notifications.js"></script>
    
    <script>
        async function clearLogs(type) {
            const typeName = type === 'admin' ? '管理日志' : '使用日志';
            const retainDays = type === 'admin' ? '7天' : '30天';
            
            const confirmed = await modernModal.confirm(
                '确定要清空' + typeName + '吗？\\n\\n此操作将删除' + retainDays + '前的所有日志记录，保留最近' + retainDays + '的记录。\\n\\n此操作不可撤销！', 
                '清空日志确认'
            );
            
            if (confirmed) {
                try {
                    const response = await fetch('/logs/clear?type=' + type, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        notify.success(result.message);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        notify.error(result.error || '清空日志失败');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    notify.error('清空日志失败：网络错误');
                }
            }
        }
        
        async function deleteLogRange(type) {
            const typeName = type === 'admin' ? '管理日志' : '使用日志';
            
            const days = await modernModal.prompt(
                '请输入要删除多少天前的' + typeName + '记录（1-365天）：', 
                '90', 
                '删除历史日志'
            );
            
            if (days === null) return;
            
            const daysNum = parseInt(days);
            if (!daysNum || daysNum < 1 || daysNum > 365) {
                await modernModal.alert('天数必须在1-365之间', '参数错误', 'error');
                return;
            }
            
            const confirmed = await modernModal.confirm(
                '确定要删除' + daysNum + '天前的所有' + typeName + '记录吗？\\n\\n此操作不可撤销！', 
                '删除历史日志确认'
            );
            
            if (confirmed) {
                try {
                    const response = await fetch('/logs/delete-range', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            type: type,
                            days: daysNum
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        notify.success(result.message);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        notify.error(result.error || '删除日志失败');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    notify.error('删除日志失败：网络错误');
                }
            }
        }
    </script>
</body>
</html>
HTML;
    }

    /**
     * 获取状态徽章
     */
    private function getStatusBadge(string $status): string
    {
        if (strpos($status, '成功') !== false) {
            return '<span class="badge bg-success">' . htmlspecialchars($status) . '</span>';
        } else {
            return '<span class="badge bg-danger">' . htmlspecialchars($status) . '</span>';
        }
    }

    /**
     * 渲染分页
     */
    private function renderPagination(array $result, string $type, ?string $search = null): string
    {
        $currentPage = $result['current_page'];
        $lastPage = $result['last_page'];
        $total = $result['total'];
        $perPage = $result['per_page'];

        if ($lastPage <= 1) {
            return '';
        }

        // 构建查询参数
        $queryParams = "type={$type}";
        if ($search) {
            $queryParams .= "&search=" . urlencode($search);
        }

        $html = '<nav aria-label="日志分页"><ul class="pagination justify-content-center">';
        
        // 上一页
        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"?{$queryParams}&page={$prevPage}\">上一页</a></li>";
        }

        // 页码
        for ($i = max(1, $currentPage - 2); $i <= min($lastPage, $currentPage + 2); $i++) {
            $active = ($i == $currentPage) ? 'active' : '';
            $html .= "<li class=\"page-item {$active}\"><a class=\"page-link\" href=\"?{$queryParams}&page={$i}\">{$i}</a></li>";
        }

        // 下一页
        if ($currentPage < $lastPage) {
            $nextPage = $currentPage + 1;
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"?{$queryParams}&page={$nextPage}\">下一页</a></li>";
        }

        $html .= '</ul></nav>';
        
        $html .= "<div class=\"text-center text-muted mt-2\">共 {$total} 条记录，每页 {$perPage} 条</div>";

        return $html;
    }

    /**
     * 导出日志数据
     */
    public function export(Request $request): Response
    {
        try {
            // 检查管理员是否已登录
            if (!SessionManager::isLoggedIn()) {
                return Response::redirect('/login?error=' . urlencode('请先登录'));
            }
            $type = $request->get('type', 'usage');
            $search = $request->get('search');
            $limit = 1000; // 限制导出数量
            
            if ($type === 'admin') {
                $query = $this->adminLogModel->query();
                
                // 应用搜索条件
                if ($search) {
                    $searchTerm = "%{$search}%";
                    $query->where('action', 'LIKE', $searchTerm)
                          ->orWhere('detail', 'LIKE', $searchTerm)
                          ->orWhere('ip_address', 'LIKE', $searchTerm)
                          ->orWhere('user_agent', 'LIKE', $searchTerm);
                }
                
                $logs = $query->orderBy('created_at', 'DESC')->limit($limit)->get();
                
                $filename = 'admin_logs_' . date('Y-m-d') . '.csv';
                $csv = "时间,操作,详情,IP地址\n";
                
                foreach ($logs as $log) {
                    $csv .= sprintf(
                        "%s,%s,%s,%s\n",
                        $log['created_at'],
                        str_replace(',', ';', $log['action']),
                        str_replace(',', ';', $log['detail']),
                        $log['ip_address']
                    );
                }
            } else {
                $query = $this->usageLogModel->query();
                
                // 应用搜索条件
                if ($search) {
                    $searchTerm = "%{$search}%";
                    $query->where('license_key', 'LIKE', $searchTerm)
                          ->orWhere('machine_code', 'LIKE', $searchTerm)
                          ->orWhere('ip_address', 'LIKE', $searchTerm)
                          ->orWhere('status', 'LIKE', $searchTerm);
                }
                
                $logs = $query->orderBy('created_at', 'DESC')->limit($limit)->get();
                
                $filename = 'usage_logs_' . date('Y-m-d') . '.csv';
                $csv = "时间,许可证密钥,机器码,状态,IP地址\n";
                
                foreach ($logs as $log) {
                    $csv .= sprintf(
                        "%s,%s,%s,%s,%s\n",
                        $log['created_at'],
                        $log['license_key'],
                        $log['machine_code'],
                        str_replace(',', ';', $log['status']),
                        $log['ip_address']
                    );
                }
            }
            
            // 记录导出操作
            $this->adminLogModel->logAction(
                '导出日志',
                "导出{$type}日志数据，共" . count($logs) . "条记录",
                $request->getClientIp(),
                $request->getUserAgent()
            );
            
            // 返回CSV文件
            $response = new Response($csv, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Content-Length' => strlen($csv)
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            $this->logger->error('Export logs error', [
                'error' => $e->getMessage(),
            ]);
            
            return Response::redirect('/logs?error=' . urlencode('导出日志失败'));
        }
    }

    /**
     * 清空日志
     */
    public function clear(Request $request): Response
    {
        try {
            // 检查管理员是否已登录
            if (!SessionManager::isLoggedIn()) {
                return Response::json(['error' => '请先登录'], 401);
            }

            $type = $request->get('type', 'usage');
            
            if ($type === 'admin') {
                // 清空管理日志，但保留最近7天的记录
                $cutoffDate = date('Y-m-d H:i:s', strtotime('-7 days'));
                $oldLogs = $this->adminLogModel->query()
                    ->where('created_at', '<', $cutoffDate)
                    ->get();
                
                $count = count($oldLogs);
                
                // 删除符合条件的记录
                foreach ($oldLogs as $log) {
                    $this->adminLogModel->delete($log['id']);
                }
                
                $message = "已清空{$count}条管理日志（保留最近7天记录）";
                
            } else {
                // 清空使用日志，但保留最近30天的记录
                $cutoffDate = date('Y-m-d H:i:s', strtotime('-30 days'));
                $oldLogs = $this->usageLogModel->query()
                    ->where('created_at', '<', $cutoffDate)
                    ->get();
                
                $count = count($oldLogs);
                
                // 删除符合条件的记录
                foreach ($oldLogs as $log) {
                    $this->usageLogModel->delete($log['id']);
                }
                
                $message = "已清空{$count}条使用日志（保留最近30天记录）";
            }

            // 记录清空操作
            $this->adminLogModel->logAction(
                '清空日志',
                $message,
                $request->getClientIp(),
                $request->getUserAgent()
            );

            return Response::json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Clear logs error', [
                'error' => $e->getMessage(),
            ]);
            
            return Response::json(['error' => '清空日志失败'], 500);
        }
    }

    /**
     * 删除指定时间范围的日志
     */
    public function deleteRange(Request $request): Response
    {
        try {
            // 检查管理员是否已登录
            if (!SessionManager::isLoggedIn()) {
                return Response::json(['error' => '请先登录'], 401);
            }

            $data = $request->all();
            $type = $data['type'] ?? 'usage';
            $days = (int)($data['days'] ?? 30);
            
            if ($days < 1 || $days > 365) {
                return Response::json(['error' => '天数范围必须在1-365之间'], 400);
            }

            if ($type === 'admin') {
                $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
                $oldLogs = $this->adminLogModel->query()
                    ->where('created_at', '<', $cutoffDate)
                    ->get();
                
                $count = count($oldLogs);
                
                // 删除符合条件的记录
                foreach ($oldLogs as $log) {
                    $this->adminLogModel->delete($log['id']);
                }
                
                $message = "已删除{$days}天前的{$count}条管理日志";
                
            } else {
                $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
                $oldLogs = $this->usageLogModel->query()
                    ->where('created_at', '<', $cutoffDate)
                    ->get();
                
                $count = count($oldLogs);
                
                // 删除符合条件的记录
                foreach ($oldLogs as $log) {
                    $this->usageLogModel->delete($log['id']);
                }
                
                $message = "已删除{$days}天前的{$count}条使用日志";
            }

            // 记录删除操作
            $this->adminLogModel->logAction(
                '删除历史日志',
                $message,
                $request->getClientIp(),
                $request->getUserAgent()
            );

            return Response::json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Delete logs error', [
                'error' => $e->getMessage(),
            ]);
            
            return Response::json(['error' => '删除日志失败'], 500);
        }
    }
}

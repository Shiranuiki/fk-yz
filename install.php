<?php
/**
 * 网络验证系统 - 现代化安装引导
 * 
 * 功能：
 * 1. 环境检测（PHP版本、扩展、权限等）
 * 2. 数据库连接测试和配置
 * 3. 依赖安装指导
 * 4. 管理员账号设置
 * 5. 系统初始化
 */

// 防止重复安装
if (file_exists(__DIR__ . '/config/installed.lock')) {
    die('系统已安装，如需重新安装请删除 config/installed.lock 文件');
}

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 开始会话
session_start();

// 简单的环境变量加载函数（不依赖Dotenv）
function loadEnv($file) {
    if (!file_exists($file)) {
        return;
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // 移除引号
            if (($value[0] ?? '') === '"' && ($value[-1] ?? '') === '"') {
                $value = substr($value, 1, -1);
            }
            
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// 加载环境变量（如果存在）
loadEnv(__DIR__ . '/.env');

// 获取当前步骤
$step = $_GET['step'] ?? 'welcome';
$allowedSteps = ['welcome', 'environment', 'database', 'dependencies', 'admin', 'install', 'complete'];

if (!in_array($step, $allowedSteps)) {
    $step = 'welcome';
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 'database':
            // 跳过数据库测试请求，让database.php自己处理
            if (!isset($_GET['test_db'])) {
                // 如果数据库已经配置过，直接跳转到下一步
                if (isset($_SESSION['db_connected']) && $_SESSION['db_connected'] === true) {
                    $_SESSION['success'] = '数据库已配置，正在跳转到依赖安装...';
                    header('Location: ?step=dependencies');
                    exit;
                }
                handleDatabaseConfig();
            }
            break;
        case 'dependencies':
            handleDependencyInstall();
            break;
        case 'admin':
            handleAdminSetup();
            break;
        case 'install':
            handleInstallation();
            break;
    }
}

function handleDatabaseConfig() {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbPort = $_POST['db_port'] ?? '3306';
    $dbName = $_POST['db_name'] ?? 'auth_system';
    $dbUser = $_POST['db_user'] ?? 'root';
    $dbPass = $_POST['db_pass'] ?? '';
    
    try {
    // 测试数据库连接
        $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // 连接到已存在的数据库（应该在堡塔面板中已创建）
        $pdo->exec("USE `{$dbName}`");
        
        // 保存配置到会话
        $_SESSION['db_config'] = [
            'host' => $dbHost,
            'port' => $dbPort,
            'name' => $dbName,
            'user' => $dbUser,
            'pass' => $dbPass
        ];
        
        $_SESSION['db_connected'] = true;
        $_SESSION['success'] = '数据库连接成功！';
        header('Location: ?step=dependencies');
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = '数据库连接失败: ' . $e->getMessage();
    }
}

function handleDependencyInstall() {
    $results = installDependencies();
    $_SESSION['install_results'] = $results;
    
    // 检查是否所有依赖都安装成功
    $allSuccess = true;
    foreach ($results as $result) {
        if ($result['status'] !== 'success') {
            $allSuccess = false;
            break;
        }
    }
    
    if ($allSuccess) {
        $_SESSION['success'] = '所有依赖安装成功！';
        header('Location: ?step=admin');
        exit;
    } else {
        $_SESSION['error'] = '部分依赖安装失败，请查看详细信息';
    }
}

function handleAdminSetup() {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (empty($username) || empty($password) || empty($email)) {
        $_SESSION['error'] = '请填写完整的管理员信息';
        return;
    }
    
    if (strlen($password) < 6) {
        $_SESSION['error'] = '密码长度至少6位';
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = '请输入有效的邮箱地址';
        return;
    }
    
    // 保存管理员信息到会话
    $_SESSION['admin_config'] = [
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'email' => $email
    ];
    
    $_SESSION['success'] = '管理员信息配置成功！';
    header('Location: ?step=install');
    exit;
}

function handleInstallation() {
    if (performInstallation()) {
        $_SESSION['success'] = '系统安装完成！';
        header('Location: ?step=complete');
        exit;
    }
}

function performInstallation() {
    try {
        // 1. 创建 .env 文件
        error_log("开始创建 .env 文件");
        createEnvFile();
        error_log(".env 文件创建完成");
        
        // 2. 运行数据库迁移
        error_log("开始运行数据库迁移");
        runDatabaseMigrations();
        error_log("数据库迁移完成");
        
        // 3. 创建管理员账号
        error_log("开始创建管理员账号");
        createAdminAccount();
        error_log("管理员账号创建完成");
        
        // 4. 创建安装锁定文件
        error_log("开始创建安装锁定文件");
        createInstallLock();
        error_log("安装锁定文件创建完成");
        
        error_log("系统安装完全成功");
        return true;
    } catch (Exception $e) {
        $errorMsg = '安装失败: ' . $e->getMessage();
        error_log("安装失败: " . $e->getMessage());
        error_log("错误堆栈: " . $e->getTraceAsString());
        $_SESSION['error'] = $errorMsg;
        return false;
    }
}

function createEnvFile() {
    $dbConfig = $_SESSION['db_config'];
    
    // 生成随机JWT密钥
    $jwtSecret = bin2hex(random_bytes(32));
    
    $envContent = "# 网络验证系统配置文件
# 生成时间: " . date('Y-m-d H:i:s') . "

# 应用配置
SYSTEM_NAME=网络验证系统
JWT_SECRET={$jwtSecret}
JWT_EXPIRY=3600
JWT_ALGORITHM=HS256

# 数据库配置
DB_CONNECTION=mysql
DB_HOST={$dbConfig['host']}
DB_PORT={$dbConfig['port']}
DB_NAME={$dbConfig['name']}
DB_USER={$dbConfig['user']}
DB_PASS={$dbConfig['pass']}
DB_CHARSET=utf8mb4

# 限流配置
RATE_LIMIT_VERIFY_MAX=10
RATE_LIMIT_VERIFY_PER=60
RATE_LIMIT_LOGIN_MAX=5
RATE_LIMIT_LOGIN_PER=60
";

    $envPath = __DIR__ . '/.env';
    if (!file_put_contents($envPath, $envContent)) {
        throw new Exception('无法创建.env文件，请检查目录权限。路径：' . $envPath);
    }
}

function runDatabaseMigrations() {
    $dbConfig = $_SESSION['db_config'];
    
    try {
        // 首先连接到MySQL服务器（不指定数据库）
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 30
        ]);
        
        // 连接到堡塔面板已创建的数据库
        $dbName = $dbConfig['name'];
        $pdo->exec("USE `{$dbName}`");
        
        // 读取SQL文件并拆分为单独的语句
        $sqlFile = __DIR__ . '/init_database_complete.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception('数据库初始化文件不存在');
        }
        
        $sqlContent = file_get_contents($sqlFile);
        
        // 清理SQL内容：移除注释行，但保留多行结构
        $lines = explode("\n", $sqlContent);
        $cleanLines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            // 跳过注释行和空行
            if (!empty($line) && !preg_match('/^--/', $line)) {
                $cleanLines[] = $line;
            }
        }
        $cleanSql = implode("\n", $cleanLines);
        
        // 按分号分割SQL语句，保持完整性
        $sqlStatements = array_filter(
            array_map('trim', explode(';', $cleanSql)),
            function($sql) {
                $sql = trim($sql);
                return !empty($sql) && 
                       !preg_match('/^USE\s+/i', $sql) && 
                       !preg_match('/^DROP\s+DATABASE/i', $sql) && 
                       !preg_match('/^CREATE\s+DATABASE/i', $sql) &&
                       !preg_match('/^SELECT.*as\s+(message|.*_count)/i', $sql);
            }
        );
        
        // 逐条执行SQL语句
        $executedCount = 0;
        foreach ($sqlStatements as $index => $sql) {
            if (trim($sql)) {
                try {
                    error_log("执行SQL #{$index}: " . substr($sql, 0, 100) . "...");
                    $pdo->exec($sql);
                    $executedCount++;
                    error_log("SQL #{$index} 执行成功");
                } catch (PDOException $e) {
                    // 记录失败的SQL语句用于调试
                    error_log("SQL执行失败 #{$index}: " . $sql . " - 错误: " . $e->getMessage());
                    
                    // 如果是表已存在的错误，可以忽略
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw new Exception("SQL执行失败 #{$index}: " . $e->getMessage() . "\nSQL: " . $sql);
                    } else {
                        error_log("SQL #{$index} 表已存在，跳过");
                    }
                }
            }
        }
        error_log("数据库迁移完成，共执行 {$executedCount} 条SQL语句");
        
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        
        // 提供更友好的错误信息
        if (strpos($errorMsg, 'Access denied') !== false) {
            throw new Exception('数据库访问被拒绝。请检查：1) 数据库用户名和密码是否正确 2) 用户是否有CREATE、ALTER、INSERT、UPDATE、DELETE、SELECT权限 3) 在堡塔面板数据库管理中确认用户权限设置');
        } elseif (strpos($errorMsg, 'Connection refused') !== false) {
            throw new Exception('无法连接到数据库服务器，请检查MySQL服务是否已启动');
        } elseif (strpos($errorMsg, 'Unknown database') !== false) {
            throw new Exception('指定的数据库不存在且无法创建，请检查用户是否有CREATE权限');
        } else {
            throw new Exception('数据库迁移失败: ' . $errorMsg);
        }
    }
}

function createAdminAccount() {
    $dbConfig = $_SESSION['db_config'];
    $adminConfig = $_SESSION['admin_config'];
    
    try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
        // 插入管理员账号
        $stmt = $pdo->prepare("
            INSERT INTO admin_settings (id, username, password_hash, email) 
            VALUES (1, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            username = VALUES(username), 
            password_hash = VALUES(password_hash), 
            email = VALUES(email)
        ");
        
        $stmt->execute([
            $adminConfig['username'],
            $adminConfig['password'],
            $adminConfig['email']
        ]);
        
    } catch (PDOException $e) {
        throw new Exception('创建管理员账号失败: ' . $e->getMessage());
    }
}

function createInstallLock() {
    $lockDir = __DIR__ . '/config';
    if (!is_dir($lockDir)) {
        if (!mkdir($lockDir, 0755, true)) {
            throw new Exception('无法创建config目录');
        }
    }
    
    $lockContent = json_encode([
        'installed_at' => date('Y-m-d H:i:s'),
    'version' => '2.0.0',
        'installer_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ], JSON_PRETTY_PRINT);
    
    $lockFile = $lockDir . '/installed.lock';
    if (!file_put_contents($lockFile, $lockContent)) {
        throw new Exception('无法创建安装锁定文件，请检查目录权限。路径：' . $lockFile);
    }
}

function checkEnvironment() {
    $checks = [
        [
        'name' => 'PHP版本',
            'required' => 'PHP 7.4+',
        'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'success' : 'error'
        ],
        [
            'name' => 'PDO扩展',
            'required' => '必需',
            'current' => extension_loaded('pdo') ? '已安装' : '未安装',
            'status' => extension_loaded('pdo') ? 'success' : 'error'
        ],
        [
            'name' => 'PDO MySQL',
            'required' => '必需',
            'current' => extension_loaded('pdo_mysql') ? '已安装' : '未安装',
            'status' => extension_loaded('pdo_mysql') ? 'success' : 'error'
        ],
        [
            'name' => 'OpenSSL扩展',
            'required' => '必需',
            'current' => extension_loaded('openssl') ? '已安装' : '未安装',
            'status' => extension_loaded('openssl') ? 'success' : 'error'
        ],
        [
            'name' => 'JSON扩展',
            'required' => '必需',
            'current' => extension_loaded('json') ? '已安装' : '未安装',
            'status' => extension_loaded('json') ? 'success' : 'error'
        ],
        [
            'name' => '根目录写权限',
            'required' => '可写',
            'current' => is_writable(__DIR__) ? '可写' : '不可写',
            'status' => is_writable(__DIR__) ? 'success' : 'error'
        ],
        [
            'name' => 'storage目录写权限',
            'required' => '可写',
            'current' => is_writable(__DIR__ . '/storage') ? '可写' : '不可写',
            'status' => is_writable(__DIR__ . '/storage') ? 'success' : 'warning'
        ]
    ];
    
    return $checks;
}

function checkDependencies() {
    $dependencies = [];
    
    // 检查vendor目录
    if (!is_dir(__DIR__ . '/vendor')) {
        $dependencies[] = [
            'name' => 'Composer依赖',
            'status' => 'missing',
            'command' => 'composer install --no-dev --optimize-autoloader',
            'description' => '安装项目依赖包'
        ];
    } else {
        $dependencies[] = [
            'name' => 'Composer依赖',
            'status' => 'success',
            'description' => '依赖包已安装'
        ];
    }
    
    return $dependencies;
}

function installDependencies() {
    $results = [];
    
    // 安装Composer依赖
    if (!is_dir(__DIR__ . '/vendor')) {
        $output = [];
        $returnCode = 0;
        
        // 检查Composer是否可用
        exec('composer --version 2>&1', $output, $returnCode);
        if ($returnCode !== 0) {
            $results[] = [
                'name' => 'Composer依赖',
                'status' => 'error',
                'message' => 'Composer未安装或不可用，请先安装Composer'
            ];
        } else {
            // 执行composer install
            $output = [];
            exec('cd ' . escapeshellarg(__DIR__) . ' && composer install --no-dev --optimize-autoloader 2>&1', $output, $returnCode);
            $results[] = [
                'name' => 'Composer依赖',
                'status' => $returnCode === 0 ? 'success' : 'error',
                'message' => $returnCode === 0 ? '安装成功' : '安装失败: ' . implode("\n", $output)
            ];
        }
        } else {
            $results[] = [
            'name' => 'Composer依赖',
            'status' => 'success',
            'message' => '依赖已存在，跳过安装'
        ];
    }
    
    return $results;
}

function getStepProgress($currentStep) {
    $steps = ['welcome', 'environment', 'database', 'dependencies', 'admin', 'install', 'complete'];
    $currentIndex = array_search($currentStep, $steps);
    return [
        'current' => $currentIndex + 1,
        'total' => count($steps),
        'percentage' => round(($currentIndex + 1) / count($steps) * 100)
    ];
}

$progress = getStepProgress($step);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网络验证系统 - 现代化安装引导</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .install-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        
        .install-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .install-header {
            background: var(--success-gradient);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }
        
        .install-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }
        
        .install-header h1 {
            position: relative;
            z-index: 1;
            margin: 0;
            font-weight: 300;
            font-size: 2.5rem;
        }
        
        .install-header .subtitle {
            position: relative;
            z-index: 1;
            opacity: 0.9;
            margin-top: 0.5rem;
        }
        
        .progress-section {
            padding: 1.5rem 2rem 0;
            background: #f8f9fa;
        }
        
        .progress-bar-custom {
            height: 8px;
            border-radius: 10px;
            background: #e9ecef;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--success-gradient);
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }
        
        .step-item {
            display: flex;
            align-items: center;
            color: #6c757d;
            font-weight: 500;
        }
        
        .step-item.active {
            color: #0066cc;
            font-weight: 600;
        }
        
        .step-item.completed {
            color: #28a745;
        }
        
        .step-item i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }
        
        .install-content {
            padding: 2.5rem;
        }
        
        .feature-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
            transition: transform 0.2s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-2px);
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            color: white;
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .btn-modern {
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary.btn-modern {
            background: var(--primary-gradient);
        }
        
        .btn-success.btn-modern {
            background: var(--success-gradient);
        }
        
        .btn-modern:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .alert-modern {
            border: none;
            border-radius: 15px;
            padding: 1rem 1.5rem;
        }
        
        .form-control-modern {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control-modern:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .check-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .check-table th {
            background: #f8f9fa;
            border: none;
            padding: 1rem;
            font-weight: 600;
            color: #495057;
        }
        
        .check-table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="container">
            <div class="install-card fade-in">
                <div class="install-header">
                    <i class="bi bi-shield-check mb-3" style="font-size: 3rem;"></i>
                    <h1>网络验证系统</h1>
                    <p class="subtitle mb-0">现代化安装引导 v2.0</p>
                            </div>
                            
                <div class="progress-section">
                    <div class="step-indicator">
                        <div class="step-item <?= $step === 'welcome' ? 'active' : ($progress['current'] > 1 ? 'completed' : '') ?>">
                            <i class="bi bi-house-door"></i> 欢迎
                                </div>
                        <div class="step-item <?= $step === 'environment' ? 'active' : ($progress['current'] > 2 ? 'completed' : '') ?>">
                            <i class="bi bi-gear"></i> 环境
                                </div>
                        <div class="step-item <?= $step === 'database' ? 'active' : ($progress['current'] > 3 ? 'completed' : '') ?>">
                            <i class="bi bi-database"></i> 数据库
                                </div>
                        <div class="step-item <?= $step === 'dependencies' ? 'active' : ($progress['current'] > 4 ? 'completed' : '') ?>">
                            <i class="bi bi-box"></i> 依赖
                                </div>
                        <div class="step-item <?= $step === 'admin' ? 'active' : ($progress['current'] > 5 ? 'completed' : '') ?>">
                            <i class="bi bi-person-gear"></i> 管理员
                                        </div>
                        <div class="step-item <?= $step === 'install' ? 'active' : ($progress['current'] > 6 ? 'completed' : '') ?>">
                            <i class="bi bi-download"></i> 安装
                                    </div>
                        <div class="step-item <?= $step === 'complete' ? 'active' : '' ?>">
                            <i class="bi bi-check-circle"></i> 完成
                                    </div>
                                </div>
                                
                    <div class="progress-bar-custom">
                        <div class="progress-fill" style="width: <?= $progress['percentage'] ?>%"></div>
                                </div>
                                
                    <div class="text-center text-muted">
                        步骤 <?= $progress['current'] ?> / <?= $progress['total'] ?> (<?= $progress['percentage'] ?>%)
                                    </div>
                                </div>
                                
                <div class="install-content">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-modern">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($_SESSION['error']) ?>
                                </div>
                        <?php unset($_SESSION['error']); ?>
                                            <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-modern">
                            <i class="bi bi-check-circle me-2"></i>
                            <?= htmlspecialchars($_SESSION['success']) ?>
                                </div>
                        <?php unset($_SESSION['success']); ?>
                            <?php endif; ?>

                    <?php
                    switch ($step) {
                        case 'welcome':
                            include 'install_steps/welcome.php';
                            break;
                        case 'environment':
                            include 'install_steps/environment.php';
                            break;
                        case 'database':
                            include 'install_steps/database.php';
                            break;
                        case 'dependencies':
                            include 'install_steps/dependencies.php';
                            break;
                        case 'admin':
                            include 'install_steps/admin.php';
                            break;
                        case 'install':
                            include 'install_steps/install.php';
                            break;
                        case 'complete':
                            include 'install_steps/complete.php';
                            break;
                        default:
                            echo '<div class="text-center"><h3>未知步骤</h3></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 添加一些交互效果
        document.addEventListener('DOMContentLoaded', function() {
            // 表单验证
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="loading-spinner me-2"></span>处理中...';
                    }
                });
            });
            
            // 输入框焦点效果
            const inputs = document.querySelectorAll('.form-control-modern');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                    this.parentElement.style.transition = 'transform 0.2s ease';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });
        
        // 测试数据库连接
        function testConnection() {
            const form = document.querySelector('#database-form');
            const formData = new FormData(form);
            
            fetch('?step=database&test=1', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const alert = document.createElement('div');
                alert.className = `alert alert-${data.success ? 'success' : 'danger'} alert-modern`;
                alert.innerHTML = `<i class="bi bi-${data.success ? 'check-circle' : 'exclamation-triangle'} me-2"></i>${data.message}`;
                
                const existingAlert = document.querySelector('.test-result');
                if (existingAlert) {
                    existingAlert.remove();
                }
                
                alert.classList.add('test-result');
                form.insertBefore(alert, form.firstChild);
            });
        }
    </script>
</body>
</html>

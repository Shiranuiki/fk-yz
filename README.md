# 🛡️ 网络许可证验证系统 v2.0

> 现代化的企业级许可证验证解决方案，为您的软件产品提供可靠的授权管理

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/Build-Passing-brightgreen.svg)](.)
[![Version](https://img.shields.io/badge/Version-2.0.0-orange.svg)](.)

## 📋 目录

- [✨ 功能特点](#-功能特点)
- [🏗️ 系统架构](#️-系统架构)
- [🚀 快速部署](#-快速部署)
- [📦 系统要求](#-系统要求)
- [🔧 详细安装](#-详细安装)
- [⚙️ 服务器配置](#️-服务器配置)
- [🎯 使用说明](#-使用说明)
- [🔌 客户端集成](#-客户端集成)
- [🛡️ 安全配置](#️-安全配置)
- [📊 API文档](#-api文档)
- [🐛 故障排除](#-故障排除)
- [📄 许可证](#-许可证)

## ✨ 功能特点

### 🎨 现代化管理界面
- **响应式设计** - 完美适配桌面端和移动端设备
- **Bootstrap 5.3** - 现代化的UI组件和设计语言
- **优雅动画** - 流畅的用户交互体验和视觉反馈
- **实时通知** - 自动消失的操作反馈和状态提示
- **暗黑模式** - 支持浅色/深色主题切换

### 🔐 企业级安全
- **JWT认证** - 基于JSON Web Token的安全认证机制
- **设备绑定** - 防止许可证被非法复制和滥用
- **IP限制** - 可选的IP地址绑定验证功能
- **请求限流** - 防止暴力破解和DDoS攻击
- **数据加密** - 敏感数据加密存储和传输
- **审计日志** - 完整的操作记录和安全追踪

### ⚡ 高性能架构
- **毫秒级响应** - 优化的数据库查询和索引设计
- **PSR-4规范** - 现代化的PHP自动加载机制
- **依赖注入** - 灵活的服务容器和依赖管理
- **中间件系统** - 可扩展的请求处理管道
- **缓存机制** - 多层缓存减少数据库负载
- **异步处理** - 非阻塞的操作流程

### 📊 智能数据分析
- **实时监控** - 许可证使用情况实时展示
- **详细统计** - 丰富的数据分析和图表展示
- **使用日志** - 完整的验证记录和操作审计
- **导出功能** - 支持Excel、CSV等多种格式
- **自动报表** - 定期生成使用情况报告

### 🔌 多语言客户端
- **PHP客户端** - 完整的PHP SDK和示例代码
- **Python客户端** - 功能丰富的Python验证库
- **RESTful API** - 标准的REST接口，支持任何语言
- **示例代码** - 详细的集成示例和最佳实践

## 🏗️ 系统架构

### 核心组件
```
网络验证系统 v2.0
├── 核心框架 (Core)
│   ├── 应用程序 (Application)
│   ├── 路由系统 (Router)
│   ├── 中间件 (Middleware)
│   ├── 依赖注入 (Container)
│   ├── 配置管理 (Config)
│   └── 日志系统 (Logger)
├── API控制器 (Api/Controller)
│   ├── 验证控制器 (VerificationController)
│   ├── 许可证控制器 (LicenseController)
│   ├── 认证控制器 (AuthController)
│   └── 日志控制器 (LogController)
├── Web控制器 (Web/Controller)
│   ├── 仪表板 (DashboardController)
│   ├── 许可证管理 (LicenseController)
│   ├── 系统设置 (SettingsController)
│   └── 用户认证 (AuthController)
├── 数据模型 (Models)
│   ├── 许可证模型 (License)
│   ├── 使用日志 (UsageLog)
│   ├── 管理员模型 (Admin)
│   └── 管理日志 (AdminLog)
└── 客户端SDK (Client)
    ├── PHP客户端
    └── Python客户端
```

### 技术栈
- **后端框架**: 自研轻量级PHP框架
- **数据库**: MySQL 5.7+ / MariaDB 10.3+
- **前端技术**: Bootstrap 5.3 + jQuery + Chart.js
- **认证机制**: JWT (JSON Web Token)
- **API标准**: RESTful API
- **代码规范**: PSR-4, PSR-12

## 🚀 快速部署

### 一键部署（堡塔面板推荐）

1. **创建站点**
   ```bash
   # 在堡塔面板中创建新站点
   # 域名：your-domain.com
   # 根目录：/www/wwwroot/your-site
   # PHP版本：8.1+
   ```

2. **上传代码**
   ```bash
   # 将项目文件上传到站点根目录
   cd /www/wwwroot/your-site
   # 上传或克隆项目代码
   ```

3. **设置运行目录**
   ```bash
   # 安装阶段：运行目录设为根目录 /
   # 安装完成后：运行目录改为 /public
   ```

4. **执行安装**
   ```bash
   # 访问：http://your-domain.com/install.php
   # 按照向导完成安装配置
   ```

5. **生产配置**
   ```bash
   # 安装完成后务必修改运行目录为 /public
   # 删除安装文件以提高安全性
   ```

## 📦 系统要求

### 服务器环境
- **PHP版本**: >= 8.1 (推荐 8.2+)
- **Web服务器**: Nginx (推荐) 或 Apache 2.4+
- **数据库**: MySQL 5.7+ 或 MariaDB 10.3+
- **内存**: 最低 512MB，推荐 1GB+
- **磁盘空间**: 最低 200MB，推荐 1GB+

### PHP扩展要求
```bash
# 必需扩展
php-pdo          # 数据库连接
php-json         # JSON处理
php-session      # 会话管理
php-mbstring     # 多字节字符串
php-curl         # HTTP请求
php-openssl      # 加密功能
php-zip          # 压缩文件处理

# 推荐扩展
php-redis        # Redis缓存
php-opcache      # 代码缓存
php-gd           # 图像处理
```

### 环境检查
```bash
# 检查PHP版本
php -v

# 检查必需扩展
php -m | grep -E "(pdo|json|session|mbstring|curl|openssl)"

# Web环境检查
curl -I http://your-domain.com/install.php
```

## 🔧 详细安装

### 1. 环境准备

#### 堡塔面板安装
```bash
# CentOS/RHEL/Rocky Linux
wget -O install.sh https://download.bt.cn/install/install_6.0.sh
bash install.sh ed8484bec

# Ubuntu/Debian
wget -O install.sh https://download.bt.cn/install/install-ubuntu_6.0.sh
bash install.sh ed8484bec
```

#### LNMP环境安装
```bash
# 在堡塔面板软件商店安装：
# - Nginx 1.20+
# - PHP 8.1+
# - MySQL 5.7+
# - phpMyAdmin (可选)
```

### 2. 项目部署

#### 下载项目
```bash
# 方法1：直接下载压缩包
cd /www/wwwroot/your-site
wget https://github.com/your-repo/license-system/archive/main.zip
unzip main.zip && mv license-system-main/* . && rm -rf license-system-main main.zip

# 方法2：Git克隆（推荐）
git clone https://github.com/your-repo/license-system.git .
```

#### 设置权限
```bash
# 设置所有者
chown -R www:www /www/wwwroot/your-site

# 设置基础权限
find /www/wwwroot/your-site -type d -exec chmod 755 {} \;
find /www/wwwroot/your-site -type f -exec chmod 644 {} \;

# 设置可写目录
chmod -R 777 /www/wwwroot/your-site/storage
chmod -R 777 /www/wwwroot/your-site/config
```

### 3. 数据库配置

#### 创建数据库
```sql
-- 在phpMyAdmin或命令行中执行
CREATE DATABASE license_system 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- 创建专用用户（推荐）
CREATE USER 'license_user'@'localhost' 
IDENTIFIED BY 'your_strong_password';

GRANT ALL PRIVILEGES ON license_system.* 
TO 'license_user'@'localhost';

FLUSH PRIVILEGES;
```

### 4. 安装向导

#### 访问安装页面
```bash
# 浏览器访问
http://your-domain.com/install.php
```

#### 安装步骤
1. **环境检测** - 自动检查服务器环境和PHP扩展
2. **数据库配置** - 配置数据库连接参数
3. **依赖安装** - 自动安装Composer依赖包
4. **管理员设置** - 创建系统管理员账户
5. **完成安装** - 生成配置文件和数据表

#### 安装后配置
```bash
# 重要：修改运行目录
# 堡塔面板 → 网站 → 设置 → 运行目录 → 改为 /public

# 删除安装文件（可选，推荐）
rm -f /www/wwwroot/your-site/install.php
rm -rf /www/wwwroot/your-site/install_steps
```

## ⚙️ 服务器配置

### Nginx配置（推荐）
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    root /www/wwwroot/your-site/public;
    index index.php index.html;

    # 安全配置
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # 主要路由
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    # PHP处理
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-81.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # 安全参数
        fastcgi_param PHP_ADMIN_VALUE "open_basedir=$document_root/:/tmp/:/proc/";
        fastcgi_param PHP_VALUE "session.cookie_httponly=1";
        fastcgi_param PHP_VALUE "session.cookie_secure=1";
    }

    # 静态资源缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # 安全配置 - 拒绝访问敏感目录
    location ~ ^/(config|src|storage|vendor|install_steps)/ {
        deny all;
        return 404;
    }

    # 安全配置 - 拒绝访问敏感文件
    location ~ \.(env|lock|log|sql|md)$ {
        deny all;
        return 404;
    }

    # 拒绝访问隐藏文件
    location ~ /\. {
        deny all;
        return 404;
    }
}
```

### SSL配置（强烈推荐）
```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;
    
    # SSL证书配置
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    
    # SSL安全配置
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # HSTS
    add_header Strict-Transport-Security "max-age=63072000" always;
    
    # ... 其他配置同HTTP
}

# HTTP重定向到HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}
```

### 堡塔面板配置
```bash
# 网站设置
运行目录: /public
PHP版本: 8.1
伪静态: Laravel规则

# SSL设置
证书类型: Let's Encrypt (免费)
强制HTTPS: 开启
HSTS: 开启

# 防火墙
端口: 80, 443 (开放)
IP白名单: 根据需要配置
```

## 🎯 使用说明

### 管理员登录
```
URL: https://your-domain.com/
用户名: 安装时设置的用户名
密码: 安装时设置的密码
```

### 许可证管理

#### 创建许可证
1. 进入"许可证管理"页面
2. 点击"创建许可证"按钮
3. 设置许可证参数：
   - 数量：要创建的许可证数量
   - 有效期：许可证有效天数
   - 备注：许可证用途说明

#### 许可证操作
- **查看详情** - 查看许可证使用情况
- **编辑信息** - 修改备注和设置
- **延长有效期** - 延长许可证使用时间
- **禁用/启用** - 控制许可证状态
- **解绑设备** - 解除设备绑定
- **删除许可证** - 永久删除许可证

#### 批量操作
- **批量创建** - 一次创建多个许可证
- **批量导出** - 导出许可证列表
- **批量延期** - 批量延长有效期
- **批量操作** - 批量启用/禁用

### 系统监控

#### 仪表板
- **实时统计** - 许可证总数、活跃数、今日验证次数
- **使用趋势** - 验证次数趋势图表
- **状态分布** - 许可证状态饼图
- **最近活动** - 最新的验证记录

#### 日志管理
- **使用日志** - 查看所有验证记录
- **管理日志** - 查看管理员操作记录
- **错误日志** - 查看系统错误信息
- **日志导出** - 导出日志数据
- **日志清理** - 清理过期日志

### 系统设置

#### 基础设置
- **站点信息** - 网站名称、描述、LOGO
- **邮件配置** - SMTP邮件发送设置
- **安全设置** - 密码策略、登录限制
- **缓存设置** - 缓存开关和清理

#### 高级设置
- **API配置** - API密钥和访问控制
- **备份还原** - 数据备份和还原
- **系统更新** - 检查和安装更新
- **开发工具** - 调试模式、日志级别

## 🔌 客户端集成

### PHP客户端示例
```php
<?php
// 引入客户端文件
require_once 'client/php/AuthClient.php';

// 创建客户端实例
$client = new AuthClient('https://your-domain.com');

// 生成设备标识
$deviceId = $client->generateDeviceId();

// 验证许可证
$result = $client->verify([
    'license_key' => 'LIC-ABCD1234EFGH5678',
    'machine_code' => $deviceId,
    'app_version' => '1.0.0'
]);

// 处理验证结果
if ($result['success']) {
    echo "✅ 验证成功！\n";
    echo "到期时间: " . $result['data']['expires_at'] . "\n";
    echo "剩余天数: " . $result['data']['remaining_days'] . " 天\n";
    
    // 您的软件逻辑...
    
} else {
    echo "❌ 验证失败: " . $result['message'] . "\n";
    // 处理验证失败...
    exit(1);
}
?>
```

### Python客户端示例
```python
#!/usr/bin/env python3
import sys
sys.path.append('client/python')
from verification_client_v2 import LicenseClient

# 创建客户端实例
client = LicenseClient('https://your-domain.com')

# 验证许可证
result = client.verify_license(
    license_key='LIC-ABCD1234EFGH5678',
    auto_save=True  # 自动保存验证结果
)

# 处理验证结果
if result['success']:
    print("✅ 验证成功！")
    print(f"到期时间: {result['data']['expires_at']}")
    print(f"剩余天数: {result['data']['remaining_days']} 天")
    
    # 您的软件逻辑...
    
else:
    print(f"❌ 验证失败: {result['message']}")
    # 处理验证失败...
    sys.exit(1)
```

### JavaScript客户端示例
```javascript
// 浏览器环境或Node.js环境
class LicenseClient {
    constructor(serverUrl) {
        this.serverUrl = serverUrl;
    }
    
    async verify(licenseKey, machineCode) {
        try {
            const response = await fetch(`${this.serverUrl}/api/verify`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    license_key: licenseKey,
                    machine_code: machineCode
                })
            });
            
            return await response.json();
        } catch (error) {
            return {
                success: false,
                message: '网络连接失败'
            };
        }
    }
    
    generateMachineCode() {
        // 简单的机器码生成（实际应用中应该更复杂）
        return 'WEB-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    }
}

// 使用示例
const client = new LicenseClient('https://your-domain.com');
const machineCode = client.generateMachineCode();

client.verify('LIC-ABCD1234EFGH5678', machineCode)
    .then(result => {
        if (result.success) {
            console.log('✅ 验证成功！', result.data);
            // 您的应用逻辑...
        } else {
            console.log('❌ 验证失败:', result.message);
            // 处理验证失败...
        }
    });
```

### 自定义集成
```bash
# RESTful API接口
POST /api/verify
Content-Type: application/json

{
    "license_key": "LIC-ABCD1234EFGH5678",
    "machine_code": "MACHINE-UNIQUE-ID",
    "app_version": "1.0.0",
    "extra_data": {}
}

# 成功响应
{
    "success": true,
    "message": "验证成功",
    "data": {
        "license_key": "LIC-ABCD1234EFGH5678",
        "expires_at": "2024-12-31 23:59:59",
        "remaining_days": 365,
        "status": "active"
    }
}
```

## 🛡️ 安全配置

### 生产环境安全检查清单

#### 1. 文件权限
```bash
# 检查关键文件权限
ls -la /www/wwwroot/your-site/.env
ls -la /www/wwwroot/your-site/config/

# 确保敏感文件不可写
chmod 644 /www/wwwroot/your-site/.env
chmod -R 644 /www/wwwroot/your-site/config/*.php
```

#### 2. 删除开发文件
```bash
# 删除安装文件
rm -f /www/wwwroot/your-site/install.php
rm -rf /www/wwwroot/your-site/install_steps/

# 删除开发工具
rm -f /www/wwwroot/your-site/phpinfo.php
rm -f /www/wwwroot/your-site/test.php
```

#### 3. 环境变量配置
```bash
# 生产环境 .env 配置
APP_ENV=production
APP_DEBUG=false
DB_PASSWORD=strong_random_password
JWT_SECRET=your_jwt_secret_key
API_SECRET=your_api_secret_key
```

#### 4. 数据库安全
```sql
-- 删除默认数据库用户
DROP USER IF EXISTS 'test'@'localhost';

-- 修改root密码
ALTER USER 'root'@'localhost' IDENTIFIED BY 'new_strong_password';

-- 删除匿名用户
DELETE FROM mysql.user WHERE User='';

-- 刷新权限
FLUSH PRIVILEGES;
```

#### 5. Web服务器安全
```nginx
# 隐藏服务器版本信息
server_tokens off;

# 限制请求大小
client_max_body_size 10M;

# 设置超时时间
client_body_timeout 12;
client_header_timeout 12;
keepalive_timeout 15;
send_timeout 10;

# 限制连接数
limit_conn_zone $binary_remote_addr zone=conn_limit_per_ip:10m;
limit_conn conn_limit_per_ip 20;

# 限制请求频率
limit_req_zone $binary_remote_addr zone=req_limit_per_ip:10m rate=5r/s;
limit_req zone=req_limit_per_ip burst=10 nodelay;
```

### 定期维护

#### 1. 日志轮转
```bash
# 创建logrotate配置
cat > /etc/logrotate.d/license-system << EOF
/www/wwwroot/your-site/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www www
}
EOF
```

#### 2. 数据备份
```bash
#!/bin/bash
# 数据库备份脚本
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/license-system"
DB_NAME="license_system"

# 创建备份目录
mkdir -p $BACKUP_DIR

# 数据库备份
mysqldump -u root -p$DB_PASSWORD $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# 文件备份
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /www/wwwroot/your-site

# 清理30天前的备份
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

#### 3. 系统监控
```bash
# 创建监控脚本
#!/bin/bash
# 检查服务状态
systemctl is-active nginx
systemctl is-active mysql
systemctl is-active php8.1-fpm

# 检查磁盘空间
df -h | grep -E '/(|www)' | awk '{print $5}' | sed 's/%//' | while read usage; do
    if [ $usage -gt 80 ]; then
        echo "警告：磁盘使用率超过80%"
    fi
done

# 检查内存使用
free -m | awk 'NR==2{printf "内存使用率: %.2f%%\n", $3*100/$2}'
```

## 📊 API文档

完整的API文档请查看：[API文档](docs/api.md)

### 核心接口

#### 许可证验证
```http
POST /api/verify
Content-Type: application/json

{
    "license_key": "LIC-ABCD1234EFGH5678",
    "machine_code": "MACHINE-UNIQUE-ID"
}
```

#### 管理接口（需要认证）
```http
# 获取许可证列表
GET /api/licenses
Authorization: Bearer {token}

# 创建许可证
POST /api/licenses
Authorization: Bearer {token}
Content-Type: application/json

{
    "count": 10,
    "duration_days": 365,
    "note": "企业版许可证"
}
```

### 错误码说明
- `200` - 请求成功
- `400` - 请求参数错误
- `401` - 未授权访问
- `403` - 权限不足
- `404` - 资源不存在
- `429` - 请求频率超限
- `500` - 服务器内部错误

## 🐛 故障排除

### 常见问题及解决方案

#### 1. 安装问题

**问题：访问install.php显示404**
```bash
解决：
1. 检查文件是否存在
2. 确认Web服务器配置正确
3. 检查运行目录设置
```

**问题：数据库连接失败**
```bash
解决：
1. 检查数据库服务状态：systemctl status mysql
2. 验证用户名密码是否正确
3. 确认数据库已创建
4. 检查防火墙设置
```

#### 2. 运行问题

**问题：访问首页显示"系统已安装"**
```bash
解决：
1. 在堡塔面板中将运行目录改为 /public
2. 重启PHP服务
3. 清除浏览器缓存
```

**问题：API验证失败**
```bash
解决：
1. 检查.env配置文件
2. 验证API密钥设置
3. 查看错误日志：tail -f storage/logs/app.log
4. 确认许可证状态正常
```

#### 3. 性能问题

**问题：响应速度慢**
```bash
解决：
1. 启用OPcache：在php.ini中设置opcache.enable=1
2. 配置Redis缓存
3. 优化数据库索引
4. 检查服务器资源使用情况
```

**问题：内存使用过高**
```bash
解决：
1. 增加PHP内存限制
2. 优化查询语句
3. 清理过期日志
4. 重启PHP-FPM服务
```

### 日志查看

```bash
# 应用日志
tail -f /www/wwwroot/your-site/storage/logs/app.log

# Nginx访问日志
tail -f /var/log/nginx/access.log

# Nginx错误日志
tail -f /var/log/nginx/error.log

# PHP错误日志
tail -f /var/log/php8.1-fpm.log

# MySQL错误日志
tail -f /var/log/mysql/error.log
```

### 调试模式

```bash
# 开启调试模式（仅限开发环境）
# 修改 .env 文件
APP_DEBUG=true
DEBUG=true

# 查看详细错误信息
# 修改 index.php 和 public/index.php
error_reporting(E_ALL);
ini_set('display_errors', '1');
```

## 📈 生产环境优化

### 服务器配置建议

#### 最小配置
- **CPU**: 1核心
- **内存**: 1GB
- **存储**: 20GB SSD
- **带宽**: 5Mbps
- **适用**: 1000个许可证以内

#### 推荐配置
- **CPU**: 2核心
- **内存**: 4GB
- **存储**: 50GB SSD
- **带宽**: 10Mbps
- **适用**: 10000个许可证以内

#### 高性能配置
- **CPU**: 4核心+
- **内存**: 8GB+
- **存储**: 100GB+ NVMe SSD
- **带宽**: 20Mbps+
- **适用**: 大规模企业部署

### 性能优化

#### PHP优化
```ini
# php.ini 优化配置
memory_limit = 512M
max_execution_time = 60
max_input_vars = 3000
post_max_size = 64M
upload_max_filesize = 64M

# OPcache配置
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.save_comments = 1
```

#### MySQL优化
```ini
# my.cnf 优化配置
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
query_cache_size = 128M
query_cache_type = 1
```

#### Nginx优化
```nginx
# nginx.conf 优化配置
worker_processes auto;
worker_connections 1024;
keepalive_timeout 65;
gzip on;
gzip_comp_level 6;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

# 开启HTTP/2
listen 443 ssl http2;
```

### 扩展性方案

#### 负载均衡
```nginx
upstream license_backend {
    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
    server 127.0.0.1:8003;
}

server {
    location / {
        proxy_pass http://license_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

#### Redis缓存
```php
# .env 配置
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password
```

#### CDN配置
```bash
# 静态资源CDN
# 将 public/assets/ 目录上传到CDN
# 修改模板中的资源链接
```

## 📞 技术支持

- **API参考**: [API接口文档](docs/api.md)

## 📄 许可证

本项目采用 [MIT许可证](LICENSE)，您可以自由使用、修改和分发。

### 开源协议
- ✅ 商业使用
- ✅ 修改代码
- ✅ 分发代码
- ✅ 私人使用
- ❗ 需要保留版权声明
- ❗ 不提供责任担保

## 🎉 致谢

感谢以下开源项目的支持：
- [PHP](https://php.net) - 强大的服务器端脚本语言
- [Bootstrap](https://getbootstrap.com) - 现代化的CSS框架
- [jQuery](https://jquery.com) - 简化JavaScript开发
- [Chart.js](https://chartjs.org) - 美观的图表库
- [Firebase JWT](https://github.com/firebase/php-jwt) - JWT认证库

---

## 🚀 快速开始

**新用户5分钟快速部署：**

1. **创建站点** → 上传代码 → 设置权限
2. **访问** `http://your-domain.com/install.php`
3. **按向导完成安装** → 修改运行目录为 `/public`
4. **开始使用** → 创建许可证 → 集成到您的软件

**需要帮助？** 查看上方详细文档或联系技术支持 📞

---

<p align="center">
  <strong>🛡️ 让您的软件更安全，让授权管理更简单</strong>
</p>
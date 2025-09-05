# 🚀 部署清单

## 部署前检查

### 1. 环境要求
- [ ] PHP 8.1+ 已安装
- [ ] MySQL/MariaDB 已配置
- [ ] Web服务器 (Nginx/Apache) 已配置
- [ ] 必需PHP扩展已安装
- [ ] SSL证书已配置 (推荐)

### 2. 文件准备
- [ ] 代码已上传到服务器
- [ ] 文件权限已正确设置 (755/644)
- [ ] storage/ 和 config/ 目录可写 (755)
- [ ] .env 文件已配置
- [ ] composer 依赖已安装

### 3. 数据库配置
- [ ] 数据库已创建
- [ ] 数据库用户已创建并授权
- [ ] 数据库连接参数正确

### 4. 安全配置
- [ ] 生产环境错误报告已关闭
- [ ] 敏感目录访问已禁止
- [ ] 安全头部已配置
- [ ] 防火墙规则已设置

## 部署步骤

### 1. 上传代码
```bash
# 上传所有文件到网站根目录
# 确保保持目录结构完整
```

### 2. 🚨 配置Web服务器DocumentRoot
```bash
# 重要：将Web服务器的文档根目录设置为 public 目录

# 宝塔面板
# 网站设置 → 网站目录 → 运行目录 → /public

# PHPStudy
# 虚拟主机 → 域名管理 → 修改网站目录指向 public 文件夹

# Apache虚拟主机配置
DocumentRoot "/www/wwwroot/your-site/public"

# Nginx配置
root /www/wwwroot/your-site/public;
```

### 3. 设置权限
```bash
# 设置用户和组（根据服务器环境调整）
chown -R www:www /www/wwwroot/your-site

# 目录权限 755，文件权限 644
find /www/wwwroot/your-site -type d -exec chmod 755 {} \;
find /www/wwwroot/your-site -type f -exec chmod 644 {} \;

# 存储目录需要写权限（755足够，避免使用777）
chmod -R 755 /www/wwwroot/your-site/storage
chmod 755 /www/wwwroot/your-site/config

# 日志目录写权限
chmod 755 /www/wwwroot/your-site/storage/logs
```

### 4. 安装依赖
```bash
cd /www/wwwroot/your-site
composer install --no-dev --optimize-autoloader
```

### 5. 配置环境
```bash
# 复制环境配置文件
cp env.example .env

# 编辑 .env 文件，设置生产环境参数
nano .env
```

### 5. 运行安装
```bash
# 访问安装页面
http://your-domain.com/install.php

# 按向导完成安装
```

### 6. 生产配置
```bash
# 修改运行目录为 /public
# 在堡塔面板或Web服务器配置中设置

# 删除安装文件 (可选)
rm -f install.php
rm -rf install_steps/
```

## 部署后检查

### 1. 功能测试
- [ ] 管理员登录正常
- [ ] 许可证创建功能正常
- [ ] API验证接口正常
- [ ] 客户端验证正常

### 2. 性能测试
- [ ] 页面加载速度 < 2秒
- [ ] API响应时间 < 500ms
- [ ] 数据库查询优化

### 3. 安全测试
- [ ] 敏感文件无法直接访问
- [ ] SQL注入防护有效
- [ ] XSS防护有效
- [ ] CSRF防护有效

### 4. 监控配置
- [ ] 错误日志监控
- [ ] 性能监控
- [ ] 安全监控
- [ ] 备份计划

## 生产环境维护

### 日常维护
- 定期检查日志文件
- 监控系统资源使用
- 定期备份数据库和文件
- 更新系统和软件包

### 安全维护
- 定期更新密码
- 检查访问日志异常
- 监控系统漏洞
- 更新SSL证书

### 性能优化
- 开启OPcache
- 配置Redis缓存
- 优化数据库查询
- 启用CDN加速

## 故障处理

### 常见问题

#### 🔄 重定向循环错误 (重定向次数过多)
**问题**: 访问网站时出现"重定向次数过多"错误

**原因**: 
- DocumentRoot 未正确设置为 `public` 目录
- 存在多个 `.htaccess` 文件冲突
- `config/installed.lock` 文件状态异常

**解决方案**:
```bash
# 1. 检查并设置 DocumentRoot
# 确保Web服务器DocumentRoot指向项目的 public 目录

# 2. 删除根目录的 .htaccess 文件（如果存在）
rm /www/wwwroot/your-site/.htaccess

# 3. 检查安装锁定文件
ls -la /www/wwwroot/your-site/config/installed.lock

# 4. 如需重新安装，删除锁定文件
rm /www/wwwroot/your-site/config/installed.lock
```

#### 🔑 数据库连接错误
**问题**: `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: NO)`

**解决方案**:
```bash
# 1. 检查数据库配置
cat .env | grep DB_

# 2. 确保数据库密码正确填写
# 编辑 .env 文件，确保 DB_PASSWORD 不为空（如果数据库有密码）

# 3. 测试数据库连接
mysql -h localhost -u root -p your_database_name
```

#### 🛡️ 其他常见问题
1. **404错误** - 检查运行目录和伪静态配置
2. **500错误** - 查看错误日志，检查权限设置
3. **安装文件无法访问** - 确保 `public/install.php` 存在
4. **验证失败** - 检查API配置和许可证状态

### 应急处理
1. **备份恢复** - 从最近备份恢复数据
2. **服务重启** - 重启Web服务器和数据库
3. **日志分析** - 分析错误日志定位问题
4. **联系支持** - 紧急情况联系技术支持

## 联系支持

- **技术支持**: support@your-domain.com
- **紧急热线**: 400-xxx-xxxx
- **在线文档**: https://docs.your-domain.com
- **社区论坛**: https://forum.your-domain.com

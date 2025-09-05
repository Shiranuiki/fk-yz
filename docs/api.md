# API 接口文档

## 📋 接口概述

网络许可证验证系统提供完整的RESTful API接口，支持许可证验证、管理等功能。

### 基础信息
- **Base URL**: `https://your-domain.com/api`
- **认证方式**: Bearer Token (管理接口) / 无需认证 (验证接口)
- **数据格式**: JSON
- **字符编码**: UTF-8
- **请求方式**: POST/GET/PUT/DELETE

## 🔑 许可证验证接口

### 验证许可证
验证许可证的有效性和状态，这是系统的核心接口。

**接口地址**
```http
POST /api/verify
```

**请求参数**
```json
{
    "license_key": "LIC-ABCD1234EFGH5678",
    "machine_code": "MACHINE-XXXXXXXXXXXX",
    "app_version": "1.0.0",
    "extra_data": {}
}
```

**参数说明**
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| license_key | string | 是 | 许可证密钥 |
| machine_code | string | 是 | 机器码/设备唯一标识 |
| app_version | string | 否 | 应用程序版本号 |
| extra_data | object | 否 | 额外的自定义数据 |

**成功响应**
```json
{
    "success": true,
    "message": "验证成功",
    "data": {
        "license_key": "LIC-ABCD1234EFGH5678",
        "status": "active",
        "expires_at": "2024-12-31 23:59:59",
        "remaining_days": 365,
        "machine_code": "MACHINE-XXXXXXXXXXXX",
        "last_used_at": "2024-01-01 12:00:00",
        "created_at": "2024-01-01 00:00:00"
    }
}
```

**失败响应**
```json
{
    "success": false,
    "message": "许可证无效或已过期",
    "error_code": "INVALID_LICENSE"
}
```

## 🔐 认证接口

### 获取访问令牌
管理接口需要先获取访问令牌。

**接口地址**
```http
POST /api/auth/login
```

**请求参数**
```json
{
    "username": "admin",
    "password": "your_password"
}
```

**成功响应**
```json
{
    "success": true,
    "message": "登录成功",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 3600,
        "user": {
            "id": 1,
            "username": "admin",
            "created_at": "2024-01-01 00:00:00"
        }
    }
}
```

### 退出登录
```http
POST /api/auth/logout
Authorization: Bearer {access_token}
```

## 📊 许可证管理接口

> 以下接口需要在请求头中携带访问令牌：`Authorization: Bearer {access_token}`

### 获取许可证列表
```http
GET /api/licenses
Authorization: Bearer {access_token}
```

**查询参数**
| 参数名 | 类型 | 说明 |
|--------|------|------|
| page | int | 页码，默认1 |
| per_page | int | 每页数量，默认20 |
| status | int | 状态筛选：0=未使用，1=已使用，2=已禁用 |
| search | string | 搜索关键词（许可证密钥或备注） |

**响应示例**
```json
{
    "success": true,
    "data": {
        "licenses": [
            {
                "id": 1,
                "license_key": "LIC-ABCD1234EFGH5678",
                "status": 1,
                "status_text": "已使用",
                "machine_code": "MACHINE-XXXXXXXXXXXX",
                "machine_note": "办公电脑",
                "duration_days": 365,
                "created_at": "2024-01-01 00:00:00",
                "expires_at": "2024-12-31 23:59:59",
                "last_used_at": "2024-01-01 12:00:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 100,
            "last_page": 5
        }
    }
}
```

### 创建许可证
```http
POST /api/licenses
Authorization: Bearer {access_token}
```

**请求参数**
```json
{
    "count": 10,
    "duration_days": 365,
    "note": "企业版许可证批次"
}
```

**参数说明**
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| count | int | 是 | 创建数量（1-100） |
| duration_days | int | 是 | 有效天数 |
| note | string | 否 | 批次备注说明 |

**响应示例**
```json
{
    "success": true,
    "message": "许可证创建成功",
    "data": {
        "license_keys": [
            "LIC-ABCD1234EFGH5678",
            "LIC-EFGH5678IJKL9012",
            "LIC-IJKL9012MNOP3456"
        ],
        "count": 3,
        "duration_days": 365,
        "expires_at": "2024-12-31 23:59:59"
    }
}
```

### 更新许可证
```http
PUT /api/licenses/{id}
Authorization: Bearer {access_token}
```

**请求参数**
```json
{
    "machine_note": "新的设备备注",
    "status": 1
}
```

### 删除许可证
```http
DELETE /api/licenses/{id}
Authorization: Bearer {access_token}
```

### 延长许可证有效期
```http
POST /api/licenses/{id}/extend
Authorization: Bearer {access_token}
```

**请求参数**
```json
{
    "days": 30
}
```

### 禁用/启用许可证
```http
POST /api/licenses/{id}/disable
POST /api/licenses/{id}/enable
Authorization: Bearer {access_token}
```

### 解绑设备
```http
POST /api/licenses/{id}/unbind
Authorization: Bearer {access_token}
```

## 📈 统计和日志接口

### 获取系统统计
```http
GET /api/stats
Authorization: Bearer {access_token}
```

**响应示例**
```json
{
    "success": true,
    "data": {
        "total_licenses": 1000,
        "active_licenses": 800,
        "expired_licenses": 150,
        "disabled_licenses": 50,
        "unused_licenses": 200,
        "today_verifications": 5000,
        "total_verifications": 500000,
        "recent_activity": [
            {
                "date": "2024-01-07",
                "verifications": 1200
            },
            {
                "date": "2024-01-06",
                "verifications": 1100
            }
        ]
    }
}
```

### 获取使用日志
```http
GET /api/logs
Authorization: Bearer {access_token}
```

**查询参数**
| 参数名 | 类型 | 说明 |
|--------|------|------|
| page | int | 页码，默认1 |
| per_page | int | 每页数量，默认50 |
| license_key | string | 按许可证密钥筛选 |
| start_date | string | 开始日期 (YYYY-MM-DD) |
| end_date | string | 结束日期 (YYYY-MM-DD) |
| ip_address | string | 按IP地址筛选 |

**响应示例**
```json
{
    "success": true,
    "data": {
        "logs": [
            {
                "id": 1,
                "license_key": "LIC-ABCD1234EFGH5678",
                "machine_code": "MACHINE-XXXXXXXXXXXX",
                "ip_address": "192.168.1.100",
                "user_agent": "MyApp/1.0.0",
                "result": "验证成功",
                "created_at": "2024-01-01 12:00:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 50,
            "total": 10000,
            "last_page": 200
        }
    }
}
```

## 🚨 错误码说明

### HTTP状态码
| 状态码 | 说明 |
|--------|------|
| 200 | 请求成功 |
| 400 | 请求参数错误 |
| 401 | 未授权访问 |
| 403 | 权限不足 |
| 404 | 资源不存在 |
| 422 | 数据验证失败 |
| 429 | 请求频率超限 |
| 500 | 服务器内部错误 |

### 业务错误码
| 错误码 | 说明 |
|--------|------|
| INVALID_LICENSE | 许可证无效 |
| EXPIRED_LICENSE | 许可证已过期 |
| DISABLED_LICENSE | 许可证已禁用 |
| MACHINE_MISMATCH | 设备码不匹配 |
| MACHINE_ALREADY_BOUND | 设备已绑定其他许可证 |
| LICENSE_NOT_FOUND | 许可证不存在 |
| RATE_LIMIT_EXCEEDED | 请求频率超限 |
| INVALID_CREDENTIALS | 用户名或密码错误 |
| TOKEN_EXPIRED | 访问令牌已过期 |
| INSUFFICIENT_PERMISSIONS | 权限不足 |

### 错误响应格式
```json
{
    "success": false,
    "message": "错误描述信息",
    "error_code": "ERROR_CODE",
    "errors": {
        "field_name": ["具体的字段错误信息"]
    }
}
```

## 🔄 请求限制

### 频率限制
- **验证接口** (`/api/verify`): 每分钟最多 100 次请求
- **管理接口**: 每分钟最多 1000 次请求
- **认证接口**: 每分钟最多 10 次请求

### 并发限制
- 每IP同时最多 20 个连接
- 单个令牌同时最多 10 个请求

### 超出限制响应
```json
{
    "success": false,
    "message": "请求频率超限，请稍后再试",
    "error_code": "RATE_LIMIT_EXCEEDED",
    "retry_after": 60
}
```

## 📝 客户端示例代码

### PHP 示例
```php
<?php
class LicenseClient {
    private $baseUrl;
    private $token;
    
    public function __construct($baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    // 验证许可证
    public function verify($licenseKey, $machineCode) {
        $data = [
            'license_key' => $licenseKey,
            'machine_code' => $machineCode
        ];
        
        return $this->request('POST', '/api/verify', $data);
    }
    
    // 管理员登录
    public function login($username, $password) {
        $data = [
            'username' => $username,
            'password' => $password
        ];
        
        $result = $this->request('POST', '/api/auth/login', $data);
        if ($result['success']) {
            $this->token = $result['data']['access_token'];
        }
        
        return $result;
    }
    
    // 获取许可证列表
    public function getLicenses($page = 1, $perPage = 20) {
        return $this->request('GET', "/api/licenses?page={$page}&per_page={$perPage}");
    }
    
    // 创建许可证
    public function createLicenses($count, $durationDays, $note = '') {
        $data = [
            'count' => $count,
            'duration_days' => $durationDays,
            'note' => $note
        ];
        
        return $this->request('POST', '/api/licenses', $data);
    }
    
    private function request($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        $headers = ['Content-Type: application/json'];
        
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $data ? json_encode($data) : null,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

// 使用示例
$client = new LicenseClient('https://your-domain.com');

// 验证许可证
$result = $client->verify('LIC-ABCD1234EFGH5678', 'MACHINE-XXXXXXXXXXXX');
if ($result['success']) {
    echo "验证成功！到期时间：" . $result['data']['expires_at'];
} else {
    echo "验证失败：" . $result['message'];
}

// 管理功能
$client->login('admin', 'password');
$licenses = $client->getLicenses();
$newLicenses = $client->createLicenses(10, 365, '测试批次');
?>
```

### Python 示例
```python
import requests
import json

class LicenseClient:
    def __init__(self, base_url):
        self.base_url = base_url.rstrip('/')
        self.token = None
        self.session = requests.Session()
        self.session.timeout = 30
    
    def verify(self, license_key, machine_code, app_version=None):
        """验证许可证"""
        data = {
            'license_key': license_key,
            'machine_code': machine_code
        }
        if app_version:
            data['app_version'] = app_version
            
        return self._request('POST', '/api/verify', data)
    
    def login(self, username, password):
        """管理员登录"""
        data = {
            'username': username,
            'password': password
        }
        
        result = self._request('POST', '/api/auth/login', data)
        if result['success']:
            self.token = result['data']['access_token']
            self.session.headers.update({
                'Authorization': f"Bearer {self.token}"
            })
        
        return result
    
    def get_licenses(self, page=1, per_page=20, status=None):
        """获取许可证列表"""
        params = {'page': page, 'per_page': per_page}
        if status is not None:
            params['status'] = status
            
        return self._request('GET', '/api/licenses', params=params)
    
    def create_licenses(self, count, duration_days, note=''):
        """创建许可证"""
        data = {
            'count': count,
            'duration_days': duration_days,
            'note': note
        }
        
        return self._request('POST', '/api/licenses', data)
    
    def _request(self, method, endpoint, data=None, params=None):
        """发送HTTP请求"""
        url = self.base_url + endpoint
        
        try:
            if method.upper() == 'GET':
                response = self.session.get(url, params=params)
            elif method.upper() == 'POST':
                response = self.session.post(url, json=data, params=params)
            elif method.upper() == 'PUT':
                response = self.session.put(url, json=data, params=params)
            elif method.upper() == 'DELETE':
                response = self.session.delete(url, params=params)
            else:
                raise ValueError(f"Unsupported HTTP method: {method}")
            
            return response.json()
            
        except requests.RequestException as e:
            return {
                'success': False,
                'message': f'网络请求失败: {str(e)}'
            }
        except json.JSONDecodeError:
            return {
                'success': False,
                'message': '服务器响应格式错误'
            }

# 使用示例
if __name__ == '__main__':
    client = LicenseClient('https://your-domain.com')
    
    # 验证许可证
    result = client.verify('LIC-ABCD1234EFGH5678', 'MACHINE-XXXXXXXXXXXX')
    if result['success']:
        print(f"验证成功！到期时间：{result['data']['expires_at']}")
    else:
        print(f"验证失败：{result['message']}")
    
    # 管理功能
    login_result = client.login('admin', 'password')
    if login_result['success']:
        # 获取许可证列表
        licenses = client.get_licenses()
        print(f"许可证总数：{licenses['data']['pagination']['total']}")
        
        # 创建新许可证
        new_licenses = client.create_licenses(5, 365, 'API测试批次')
        if new_licenses['success']:
            print(f"成功创建 {len(new_licenses['data']['license_keys'])} 个许可证")
```

### JavaScript 示例
```javascript
class LicenseClient {
    constructor(baseUrl) {
        this.baseUrl = baseUrl.replace(/\/$/, '');
        this.token = null;
    }
    
    // 验证许可证
    async verify(licenseKey, machineCode, appVersion = null) {
        const data = {
            license_key: licenseKey,
            machine_code: machineCode
        };
        
        if (appVersion) {
            data.app_version = appVersion;
        }
        
        return await this.request('POST', '/api/verify', data);
    }
    
    // 管理员登录
    async login(username, password) {
        const data = {
            username: username,
            password: password
        };
        
        const result = await this.request('POST', '/api/auth/login', data);
        if (result.success) {
            this.token = result.data.access_token;
        }
        
        return result;
    }
    
    // 获取许可证列表
    async getLicenses(page = 1, perPage = 20) {
        const params = new URLSearchParams({
            page: page.toString(),
            per_page: perPage.toString()
        });
        
        return await this.request('GET', `/api/licenses?${params}`);
    }
    
    // 创建许可证
    async createLicenses(count, durationDays, note = '') {
        const data = {
            count: count,
            duration_days: durationDays,
            note: note
        };
        
        return await this.request('POST', '/api/licenses', data);
    }
    
    // 发送HTTP请求
    async request(method, endpoint, data = null) {
        const url = this.baseUrl + endpoint;
        const headers = {
            'Content-Type': 'application/json'
        };
        
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        const options = {
            method: method,
            headers: headers
        };
        
        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, options);
            return await response.json();
        } catch (error) {
            return {
                success: false,
                message: `网络请求失败: ${error.message}`
            };
        }
    }
}

// 使用示例
(async () => {
    const client = new LicenseClient('https://your-domain.com');
    
    // 验证许可证
    const verifyResult = await client.verify('LIC-ABCD1234EFGH5678', 'MACHINE-XXXXXXXXXXXX');
    if (verifyResult.success) {
        console.log(`验证成功！到期时间：${verifyResult.data.expires_at}`);
    } else {
        console.log(`验证失败：${verifyResult.message}`);
    }
    
    // 管理功能
    const loginResult = await client.login('admin', 'password');
    if (loginResult.success) {
        // 获取许可证列表
        const licenses = await client.getLicenses();
        console.log(`许可证总数：${licenses.data.pagination.total}`);
        
        // 创建新许可证
        const newLicenses = await client.createLicenses(5, 365, 'JS测试批次');
        if (newLicenses.success) {
            console.log(`成功创建 ${newLicenses.data.license_keys.length} 个许可证`);
        }
    }
})();
```

## 📚 更多信息

### 版本信息
- **当前版本**: v2.0.0

### 技术支持
如果您在使用API过程中遇到问题，请参考以下资源：
- 检查请求格式是否正确
- 确认访问令牌是否有效
- 查看错误响应中的详细信息
- 检查网络连接和服务器状态

### 更新日志
- **v2.0.0**: 完整的RESTful API接口
- **v1.5.0**: 增加批量操作接口
- **v1.0.0**: 基础验证接口

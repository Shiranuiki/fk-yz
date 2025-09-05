<?php

namespace AuthSystem\Core;

use AuthSystem\Core\Container\Container;
use AuthSystem\Core\Router\Router;
use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Exception\Handler;
use AuthSystem\Core\Middleware\MiddlewareStack;
use AuthSystem\Core\Config\Config;
use AuthSystem\Core\Logger\Logger;
use AuthSystem\Core\Debug\DebugHelper;

/**
 * 应用程序主类
 * 
 * @package AuthSystem\Core
 */
class Application
{
    private Container $container;
    private Router $router;
    private MiddlewareStack $middleware;
    private Config $config;
    private Logger $logger;

    public function __construct()
    {
        $this->container = new Container();
        $this->config = new Config();
        $this->logger = new Logger($this->config);
        $this->router = new Router($this->container);
        $this->middleware = new MiddlewareStack($this->container);
        
        // 初始化调试助手
        DebugHelper::setLogger($this->logger);
        
        $this->registerServices();
        $this->registerRoutes();
        $this->registerMiddleware();
        
        DebugHelper::log("Application initialized successfully");
    }

    /**
     * 运行应用程序
     */
    public function run(): void
    {
        try {
            $request = Request::createFromGlobals();
            $response = $this->handleRequest($request);
            $response->send();
        } catch (\Throwable $e) {
            $handler = new Handler($this->logger);
            $response = $handler->handle($e);
            $response->send();
        }
    }

    /**
     * 处理请求
     */
    private function handleRequest(Request $request): Response
    {
        // 执行中间件
        $response = $this->middleware->handle($request, function ($req) {
            return $this->router->dispatch($req);
        });

        return $response;
    }

    /**
     * 注册服务到容器
     */
    private function registerServices(): void
    {
        $this->container->singleton(Config::class, function () {
            return $this->config;
        });

        $this->container->singleton(Logger::class, function () {
            return $this->logger;
        });

        $this->container->singleton(\PDO::class, function () {
            return $this->createDatabaseConnection();
        });

        // 注册模型类
        $this->container->singleton(\AuthSystem\Models\License::class, function ($container) {
            return new \AuthSystem\Models\License($container->make(\PDO::class));
        });

        $this->container->singleton(\AuthSystem\Models\UsageLog::class, function ($container) {
            return new \AuthSystem\Models\UsageLog($container->make(\PDO::class));
        });

        $this->container->singleton(\AuthSystem\Models\AdminLog::class, function ($container) {
            return new \AuthSystem\Models\AdminLog($container->make(\PDO::class));
        });

        $this->container->singleton(\AuthSystem\Models\Admin::class, function ($container) {
            return new \AuthSystem\Models\Admin($container->make(\PDO::class));
        });
        
        // 注册中间件
        $this->container->singleton(\AuthSystem\Core\Middleware\ApiSecurityMiddleware::class, function ($container) {
            return new \AuthSystem\Core\Middleware\ApiSecurityMiddleware(
                $container->make(Config::class),
                $container->make(Logger::class)
            );
        });

    }

    /**
     * 注册路由
     */
    private function registerRoutes(): void
    {
        // API 路由组
        $this->registerApiRoutes();
        
        // Web 路由组
        $this->registerWebRoutes();
    }

    /**
     * 注册API路由
     */
    private function registerApiRoutes(): void
    {
        // API路由组，应用API安全中间件
        $this->router->group('/api', function ($router) {
            // 公开的验证API（许可证验证）
            $router->post('/verify', 'AuthSystem\\Api\\Controller\\VerificationController@verify');
            
            // 需要认证的API
            $router->post('/auth/login', 'AuthSystem\\Api\\Controller\\AuthController@login');
            $router->post('/auth/logout', 'AuthSystem\\Api\\Controller\\AuthController@logout');
            $router->get('/licenses', 'AuthSystem\\Api\\Controller\\LicenseController@index');
            $router->post('/licenses', 'AuthSystem\\Api\\Controller\\LicenseController@store');
            $router->put('/licenses/{id}', 'AuthSystem\\Api\\Controller\\LicenseController@update');
            $router->delete('/licenses/{id}', 'AuthSystem\\Api\\Controller\\LicenseController@destroy');
            $router->get('/logs', 'AuthSystem\\Api\\Controller\\LogController@index');
        }, [\AuthSystem\Core\Middleware\ApiSecurityMiddleware::class]);
    }

    /**
     * 注册Web路由
     */
    private function registerWebRoutes(): void
    {
        // 认证相关路由
        $this->router->get('/login', 'AuthSystem\\Web\\Controller\\AuthController@showLogin');
        $this->router->post('/login', 'AuthSystem\\Web\\Controller\\AuthController@login');
        $this->router->get('/logout', 'AuthSystem\\Web\\Controller\\AuthController@logout');
        
        // 主要页面路由
        $this->router->get('/', 'AuthSystem\\Web\\Controller\\DashboardController@index');
        
        // 许可证管理路由组
        $this->registerLicenseRoutes();
        
        // 日志管理路由
        $this->router->get('/logs', 'AuthSystem\\Web\\Controller\\LogController@index');
        $this->router->get('/logs/export', 'AuthSystem\\Web\\Controller\\LogController@export');
        $this->router->post('/logs/clear', 'AuthSystem\\Web\\Controller\\LogController@clear');
        $this->router->post('/logs/delete-range', 'AuthSystem\\Web\\Controller\\LogController@deleteRange');
        
        // 系统设置路由
        $this->router->get('/settings', 'AuthSystem\\Web\\Controller\\SettingsController@index');
        $this->router->post('/settings/save', 'AuthSystem\\Web\\Controller\\SettingsController@save');
        $this->router->post('/settings/change-password', 'AuthSystem\\Web\\Controller\\SettingsController@changePassword');
        $this->router->post('/settings/clear-cache', 'AuthSystem\\Web\\Controller\\SettingsController@clearCache');
        $this->router->get('/settings/export-data', 'AuthSystem\\Web\\Controller\\SettingsController@exportData');
        $this->router->post('/settings/system-diagnosis', 'AuthSystem\\Web\\Controller\\SettingsController@systemDiagnosis');
    }

    /**
     * 注册许可证相关路由
     */
    private function registerLicenseRoutes(): void
    {
        $this->router->get('/licenses', 'AuthSystem\\Web\\Controller\\LicenseController@index');
        $this->router->post('/licenses/create', 'AuthSystem\\Web\\Controller\\LicenseController@create');
        $this->router->post('/licenses/{id}/edit', 'AuthSystem\\Web\\Controller\\LicenseController@edit');
        $this->router->post('/licenses/{id}/delete', 'AuthSystem\\Web\\Controller\\LicenseController@delete');
        $this->router->post('/licenses/{id}/disable', 'AuthSystem\\Web\\Controller\\LicenseController@disable');
        $this->router->post('/licenses/{id}/enable', 'AuthSystem\\Web\\Controller\\LicenseController@enable');
        $this->router->post('/licenses/{id}/unbind', 'AuthSystem\\Web\\Controller\\LicenseController@unbind');
        $this->router->post('/licenses/{id}/extend', 'AuthSystem\\Web\\Controller\\LicenseController@extend');
        $this->router->post('/licenses/reorder-ids', 'AuthSystem\\Web\\Controller\\LicenseController@reorderIds');
    }

    /**
     * 注册中间件
     */
    private function registerMiddleware(): void
    {
        // 认证中间件应该首先执行
        $this->middleware->add(\AuthSystem\Core\Middleware\AuthMiddleware::class);
        $this->middleware->add(\AuthSystem\Core\Middleware\CorsMiddleware::class);
        $this->middleware->add(\AuthSystem\Core\Middleware\RateLimitMiddleware::class);
        $this->middleware->add(\AuthSystem\Core\Middleware\SecurityMiddleware::class);
    }

    /**
     * 创建数据库连接
     */
    private function createDatabaseConnection(): \PDO
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $_ENV['DB_CONNECTION'] ?? 'mysql',
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_PORT'] ?? '3306',
            $_ENV['DB_NAME'] ?? 'auth_system',
            $_ENV['DB_CHARSET'] ?? 'utf8mb4'
        );

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        return new \PDO(
            $dsn,
            $_ENV['DB_USER'] ?? 'root',
            $_ENV['DB_PASS'] ?? '',
            $options
        );
    }
}

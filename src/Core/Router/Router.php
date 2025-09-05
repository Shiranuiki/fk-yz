<?php

namespace AuthSystem\Core\Router;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Container\Container;
use AuthSystem\Core\Debug\DebugHelper;

/**
 * 路由器类
 * 
 * @package AuthSystem\Core\Router
 */
class Router
{
    private array $routes = [];
    private array $groups = [];
    private Container $container;

    public function __construct(Container $container = null)
    {
        $this->container = $container ?? new Container();
    }

    /**
     * 添加GET路由
     */
    public function get(string $path, $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * 添加POST路由
     */
    public function post(string $path, $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * 添加PUT路由
     */
    public function put(string $path, $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * 添加DELETE路由
     */
    public function delete(string $path, $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * 添加路由组
     */
    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $this->groups[] = ['prefix' => $prefix, 'middleware' => $middleware];
        $callback($this);
        array_pop($this->groups);
    }

    /**
     * 添加路由
     */
    private function addRoute(string $method, string $path, $handler): void
    {
        $prefix = '';
        $middleware = [];
        
        // 收集所有组的前缀和中间件
        foreach ($this->groups as $group) {
            $prefix .= $group['prefix'];
            $middleware = array_merge($middleware, $group['middleware']);
        }
        
        $fullPath = $prefix . $path;
        
        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'pattern' => $this->convertToPattern($fullPath),
            'middleware' => $middleware,
        ];
    }

    /**
     * 分发请求
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = $request->getUri();
        
        // 移除查询字符串
        $uri = strtok($uri, '?');
        
        DebugHelper::logRequest($method, $uri, $request->all());
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                DebugHelper::logRoute($method, $uri, $route['handler']);
                
                // 执行中间件
                if (!empty($route['middleware'])) {
                    $response = $this->executeMiddleware($route['middleware'], $request, function() use ($route, $matches, $request) {
                        return $this->callHandler($route['handler'], $matches, $request);
                    });
                    return $response;
                } else {
                    return $this->callHandler($route['handler'], $matches, $request);
                }
            }
        }
        
        DebugHelper::log("Route not found", [
            'method' => $method,
            'uri' => $uri,
            'available_routes' => array_map(function($r) {
                return $r['method'] . ' ' . $r['path'];
            }, $this->routes)
        ]);
        
        return Response::notFound('Route not found');
    }

    /**
     * 执行中间件
     */
    private function executeMiddleware(array $middleware, Request $request, callable $next): Response
    {
        if (empty($middleware)) {
            return $next();
        }
        
        $middlewareClass = array_shift($middleware);
        $remainingMiddleware = $middleware; // 保存剩余的中间件
        $middlewareInstance = $this->container->make($middlewareClass);
        
        return $middlewareInstance->handle($request, function() use ($remainingMiddleware, $request, $next) {
            return $this->executeMiddleware($remainingMiddleware, $request, $next);
        });
    }

    /**
     * 调用处理器
     */
    private function callHandler($handler, array $matches, Request $request): Response
    {
        if (is_string($handler)) {
            // 格式: "Controller@method"
            if (strpos($handler, '@') !== false) {
                [$controller, $method] = explode('@', $handler, 2);
                $controller = $this->container->make($controller);
                return $controller->$method($request, $matches);
            }
            
            // 格式: "function"
            if (function_exists($handler)) {
                return $handler($request, $matches);
            }
        }
        
        if (is_callable($handler)) {
            return $handler($request, $matches);
        }
        
        return Response::error('Invalid handler');
    }

    /**
     * 将路径转换为正则表达式
     */
    private function convertToPattern(string $path): string
    {
        // 直接替换参数占位符，避免转义问题
        $pattern = str_replace('/', '\/', $path); // 手动转义斜杠
        $pattern = preg_replace('/\{[^}]+\}/', '([^\/]+)', $pattern); // 替换参数为捕获组
        
        return '/^' . $pattern . '$/';
    }

    /**
     * 获取所有路由
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}

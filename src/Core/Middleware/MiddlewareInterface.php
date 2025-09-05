<?php

namespace AuthSystem\Core\Middleware;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;

/**
 * 中间件接口
 * 
 * @package AuthSystem\Core\Middleware
 */
interface MiddlewareInterface
{
    /**
     * 处理请求
     */
    public function handle(Request $request, callable $next): Response;
}

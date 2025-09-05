<?php

namespace AuthSystem\Core\Exception;

use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Logger\Logger;
use AuthSystem\Core\Config\Config;

/**
 * 异常处理器
 * 
 * @package AuthSystem\Core\Exception
 */
class Handler
{
    private Logger $logger;
    private Config $config;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->config = new Config();
    }

    /**
     * 处理异常
     */
    public function handle(\Throwable $e): Response
    {
        // 记录异常日志
        $this->logger->error($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        // 根据异常类型返回不同响应
        if ($e instanceof ValidationException) {
            return Response::validationError($e->getErrors());
        }

        if ($e instanceof AuthenticationException) {
            return Response::unauthorized($e->getMessage());
        }

        if ($e instanceof AuthorizationException) {
            return Response::forbidden($e->getMessage());
        }

        if ($e instanceof NotFoundException) {
            return Response::notFound($e->getMessage());
        }

        if ($e instanceof MethodNotAllowedException) {
            return Response::methodNotAllowed($e->getMessage());
        }

        if ($e instanceof TooManyRequestsException) {
            return Response::tooManyRequests($e->getMessage());
        }

        // 默认错误响应
        if ($this->config->get('app.debug', false)) {
            return Response::error($e->getMessage(), 500);
        }

        return Response::error('Internal Server Error', 500);
    }
}

/**
 * 验证异常
 */
class ValidationException extends \Exception
{
    private array $errors;

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

/**
 * 认证异常
 */
class AuthenticationException extends \Exception
{
    public function __construct(string $message = 'Authentication required')
    {
        parent::__construct($message);
    }
}

/**
 * 授权异常
 */
class AuthorizationException extends \Exception
{
    public function __construct(string $message = 'Access denied')
    {
        parent::__construct($message);
    }
}

/**
 * 未找到异常
 */
class NotFoundException extends \Exception
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message);
    }
}

/**
 * 方法不允许异常
 */
class MethodNotAllowedException extends \Exception
{
    public function __construct(string $message = 'Method not allowed')
    {
        parent::__construct($message);
    }
}

/**
 * 请求过多异常
 */
class TooManyRequestsException extends \Exception
{
    public function __construct(string $message = 'Too many requests')
    {
        parent::__construct($message);
    }
}

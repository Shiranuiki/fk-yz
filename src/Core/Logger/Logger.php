<?php

namespace AuthSystem\Core\Logger;

use AuthSystem\Core\Config\Config;

/**
 * 日志记录器
 * 
 * @package AuthSystem\Core\Logger
 */
class Logger
{
    private Config $config;
    private string $logFile;
    private string $logLevel;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->logFile = $config->get('log.file', 'storage/logs/app.log');
        $this->logLevel = $config->get('log.level', 'info');
        
        $this->ensureLogDirectory();
    }

    /**
     * 记录调试信息
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * 记录信息
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * 记录警告
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * 记录错误
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * 记录严重错误
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * 记录日志
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $record = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'memory' => memory_get_usage(true),
            'file' => $this->getCallerFile(),
            'line' => $this->getCallerLine(),
        ];

        $this->writeLog($record);
    }

    /**
     * 检查是否应该记录该级别的日志
     */
    private function shouldLog(string $level): bool
    {
        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3, 'critical' => 4];
        $currentLevel = $levels[$this->logLevel] ?? 1;
        $messageLevel = $levels[$level] ?? 1;
        
        return $messageLevel >= $currentLevel;
    }

    /**
     * 写入日志文件
     */
    private function writeLog(array $record): void
    {
        $logEntry = sprintf(
            "[%s] %s: %s %s %s:%d\n",
            $record['timestamp'],
            $record['level'],
            $record['message'],
            !empty($record['context']) ? json_encode($record['context'], JSON_UNESCAPED_UNICODE) : '',
            basename($record['file']),
            $record['line']
        );

        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // 轮转日志文件
        $this->rotateLogs();
    }

    /**
     * 轮转日志文件
     */
    private function rotateLogs(): void
    {
        if (!file_exists($this->logFile)) {
            return;
        }

        $maxSize = 10 * 1024 * 1024; // 10MB
        $maxFiles = $this->config->get('log.max_files', 30);

        if (filesize($this->logFile) > $maxSize) {
            $this->rotateLogFile($maxFiles);
        }
    }

    /**
     * 轮转单个日志文件
     */
    private function rotateLogFile(int $maxFiles): void
    {
        $logDir = dirname($this->logFile);
        $logName = basename($this->logFile, '.log');
        
        // 移动现有日志文件
        for ($i = $maxFiles - 1; $i > 0; $i--) {
            $oldFile = $logDir . '/' . $logName . '.' . $i . '.log';
            $newFile = $logDir . '/' . $logName . '.' . ($i + 1) . '.log';
            
            if (file_exists($oldFile)) {
                rename($oldFile, $newFile);
            }
        }
        
        $rotatedFile = $logDir . '/' . $logName . '.1.log';
        rename($this->logFile, $rotatedFile);
    }

    /**
     * 确保日志目录存在
     */
    private function ensureLogDirectory(): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * 获取调用者文件
     */
    private function getCallerFile(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        return $trace[2]['file'] ?? 'unknown';
    }

    /**
     * 获取调用者行号
     */
    private function getCallerLine(): int
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        return $trace[2]['line'] ?? 0;
    }
}

<?php

namespace WeiJuKeJi\LaravelIam\Exceptions;

use Exception;

/**
 * IAM 扩展包基础异常类
 */
class IamException extends Exception
{
    /**
     * 创建异常实例
     */
    public static function make(string $message, int $code = 0, ?\Throwable $previous = null): static
    {
        return new static($message, $code, $previous);
    }
}

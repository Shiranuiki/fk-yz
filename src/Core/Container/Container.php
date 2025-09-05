<?php

namespace AuthSystem\Core\Container;

/**
 * 简单的依赖注入容器
 * 
 * @package AuthSystem\Core\Container
 */
class Container
{
    private array $bindings = [];
    private array $instances = [];

    /**
     * 绑定服务到容器
     */
    public function bind(string $abstract, $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = $concrete;
    }

    /**
     * 绑定单例服务
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null;
    }

    /**
     * 解析服务
     */
    public function make(string $abstract)
    {
        // 如果是单例且已实例化，直接返回
        if (isset($this->instances[$abstract]) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;

        // 如果是闭包，执行闭包
        if ($concrete instanceof \Closure) {
            $instance = $concrete($this);
        } else {
            // 如果是类名，实例化类
            $instance = $this->build($concrete);
        }

        // 如果是单例，保存实例
        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * 构建类实例
     */
    private function build(string $concrete)
    {
        $reflector = new \ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Class {$concrete} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete;
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters());

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * 解析依赖
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType();

            if ($dependency === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception("Cannot resolve class dependency {$parameter->getName()}");
                }
            } elseif ($dependency instanceof \ReflectionNamedType && !$dependency->isBuiltin()) {
                // 只处理非内置类型（即类和接口）
                $dependencies[] = $this->make($dependency->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new \Exception("Cannot resolve builtin type dependency {$dependency->getName()} for parameter {$parameter->getName()}");
            }
        }

        return $dependencies;
    }

    /**
     * 检查是否已绑定
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }

    /**
     * 获取所有绑定
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}

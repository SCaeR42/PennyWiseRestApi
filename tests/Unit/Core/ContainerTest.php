<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Container;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    public function testAutowiresConstructorDependencies(): void
    {
        $container = new Container();

        $service = $container->make(ContainerTestConsumer::class);

        self::assertInstanceOf(ContainerTestConsumer::class, $service);
        self::assertInstanceOf(ContainerTestDependency::class, $service->dependency);
    }

    public function testSingletonBindingIsReusedAcrossResolutions(): void
    {
        $container = new Container();
        $container->singleton(ContainerTestDependency::class, static fn () => new ContainerTestDependency());

        $first = $container->make(ContainerTestDependency::class);
        $second = $container->make(ContainerTestDependency::class);

        self::assertSame($first, $second);
    }

    public function testInstanceBindingIsReturnedAsIs(): void
    {
        $container = new Container();
        $dependency = new ContainerTestDependency();
        $container->instance(ContainerTestDependency::class, $dependency);

        self::assertSame($dependency, $container->make(ContainerTestDependency::class));
    }
}

final class ContainerTestDependency
{
}

final class ContainerTestConsumer
{
    public function __construct(public readonly ContainerTestDependency $dependency)
    {
    }
}

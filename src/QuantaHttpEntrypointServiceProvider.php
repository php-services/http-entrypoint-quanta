<?php declare(strict_types=1);

namespace Services\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Interop\Container\ServiceProviderInterface;

use Quanta\HttpEntrypoint;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;
use Zend\HttpHandlerRunner\Emitter\EmitterStack;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

final class QuantaHttpEntrypointServiceProvider implements ServiceProviderInterface
{
    public function getFactories()
    {
        return [
            ServerRequestCreatorInterface::class => [self::class, 'getServerRequestCreatorInterface'],
            EmitterInterface::class => [self::class, 'getEmitterInterface'],
            HttpEntrypoint::class => [self::class, 'getHttpEntrypoint'],
            EmitterStack::class => [self::class, 'getEmitterStack'],
            ServerRequestCreator::class => [self::class, 'getServerRequestCreator'],
        ];
    }

    public function getExtensions()
    {
        return [];
    }

    public static function getServerRequestCreatorInterface(ContainerInterface $container): ServerRequestCreatorInterface
    {
        return $container->get(ServerRequestCreator::class);
    }

    public static function getEmitterInterface(ContainerInterface $container): EmitterInterface
    {
        return $container->get(EmitterStack::class);
    }

    public static function getHttpEntrypoint(ContainerInterface $container): HttpEntrypoint
    {
        $creator = $container->get(ServerRequestCreatorInterface::class);
        $handler = $container->get(RequestHandlerInterface::class);
        $emitter = $container->get(EmitterInterface::class);

        /**
         * Phpstan ...
         * @var callable
         */
        $callable = [$emitter, 'emit'];

        return new HttpEntrypoint($creator, $handler, $callable);
    }

    public static function getEmitterStack(): EmitterStack
    {
        $stack = new EmitterStack;

        $stack->push(new SapiEmitter);

        return $stack;
    }

    public static function getServerRequestCreator(ContainerInterface $container): ServerRequestCreator
    {
        $factories[] = $container->get(ServerRequestFactoryInterface::class);
        $factories[] = $container->get(UriFactoryInterface::class);
        $factories[] = $container->get(UploadedFileFactoryInterface::class);
        $factories[] = $container->get(StreamFactoryInterface::class);

        return new ServerRequestCreator(...$factories);
    }
}

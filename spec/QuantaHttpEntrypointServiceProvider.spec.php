<?php

use function Eloquent\Phony\Kahlan\stub;
use function Eloquent\Phony\Kahlan\mock;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Interop\Container\ServiceProviderInterface;

use Quanta\Container;
use Quanta\HttpEntrypoint;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;
use Zend\HttpHandlerRunner\Emitter\EmitterStack;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;
use Services\Http\QuantaHttpEntrypointServiceProvider;

describe('QuantaHttpEntrypointServiceProvider', function () {

    beforeEach(function () {

        $this->provider = new QuantaHttpEntrypointServiceProvider;

    });

    it('should implement ServiceProviderInterface', function () {

        expect($this->provider)->toBeAnInstanceOf(ServiceProviderInterface::class);

    });

    describe('->getFactories()', function () {

        it('should return an array of length 5', function () {

            $test = $this->provider->getFactories();

            expect($test)->toBeAn('array');
            expect($test)->toHaveLength(5);

        });

        it('should provide a ServerRequestCreatorInterface entry aliasing the ServerRequestCreator one', function () {

            $creator = mock(ServerRequestCreatorInterface::class);

            $container = new Container(array_merge($this->provider->getFactories(), [
                ServerRequestCreator::class => stub()->returns($creator),
            ]));

            $test = $container->get(ServerRequestCreatorInterface::class);

            expect($test)->toBe($creator->get());

        });

        it('should provide a EmitterInterface entry aliasing the EmitterStack one', function () {

            $emitter = mock(EmitterInterface::class);

            $container = new Container(array_merge($this->provider->getFactories(), [
                EmitterStack::class => stub()->returns($emitter),
            ]));

            $test = $container->get(EmitterInterface::class);

            expect($test)->toBe($emitter->get());

        });

        it('should provide a HttpEntrypoint entry', function () {

            $creator = mock(ServerRequestCreatorInterface::class);
            $handler = mock(RequestHandlerInterface::class);
            $emitter = mock(EmitterInterface::class);

            $container = new Container(array_merge($this->provider->getFactories(), [
                ServerRequestCreatorInterface::class => stub()->returns($creator),
                RequestHandlerInterface::class => stub()->returns($handler),
                EmitterInterface::class => stub()->returns($emitter),
            ]));

            $test = $container->get(HttpEntrypoint::class);

            expect($test)->toEqual(new HttpEntrypoint(...[
                $creator->get(),
                $handler->get(),
                [$emitter->get(), 'emit'],
            ]));

        });

        it('should provide an EmitterStack entry', function () {

            $container = new Container($this->provider->getFactories());

            $test = $container->get(EmitterStack::class);

            $expected = new EmitterStack;

            $expected->push(new SapiEmitter);

            expect($test)->toEqual($expected);

        });

        it('should provide a ServerRequestCreator entry', function () {

            $factories[0] = mock(ServerRequestFactoryInterface::class);
            $factories[1] = mock(UriFactoryInterface::class);
            $factories[2] = mock(UploadedFileFactoryInterface::class);
            $factories[3] = mock(StreamFactoryInterface::class);

            $container = new Container(array_merge($this->provider->getFactories(), [
                ServerRequestFactoryInterface::class => stub()->returns($factories[0]),
                UriFactoryInterface::class => stub()->returns($factories[1]),
                UploadedFileFactoryInterface::class => stub()->returns($factories[2]),
                StreamFactoryInterface::class => stub()->returns($factories[3]),
            ]));

            $test = $container->get(ServerRequestCreator::class);

            expect($test)->toEqual(new ServerRequestCreator(...[
                $factories[0]->get(),
                $factories[1]->get(),
                $factories[2]->get(),
                $factories[3]->get(),
            ]));

        });

    });

    describe('->getExtensions()', function () {

        it('should return an empty array', function () {

            $test = $this->provider->getExtensions();

            expect($test)->toBeAn('array');
            expect($test)->toHaveLength(0);

        });

    });

});

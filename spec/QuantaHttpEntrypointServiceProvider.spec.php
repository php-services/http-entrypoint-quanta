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

use Quanta\HttpEntrypoint;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;
use Zend\HttpHandlerRunner\Emitter\EmitterStack;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;
use Services\Http\QuantaHttpEntrypointServiceProvider;

describe('QuantaHttpEntrypointServiceProvider', function () {

    beforeEach(function () {

        $this->container = mock(ContainerInterface::class);

        $this->provider = new QuantaHttpEntrypointServiceProvider;

    });

    it('should implement ServiceProviderInterface', function () {

        expect($this->provider)->toBeAnInstanceOf(ServiceProviderInterface::class);

    });

    describe('->getFactories()', function () {

        beforeEach(function () {

            $this->factories = $this->provider->getFactories();

        });

        it('should return an array of length 5', function () {

            expect($this->factories)->toBeAn('array');
            expect($this->factories)->toHaveLength(5);

        });

        it('should provide a ServerRequestCreatorInterface entry aliasing the ServerRequestCreator one', function () {

            $creator = new ServerRequestCreator(...[
                mock(ServerRequestFactoryInterface::class)->get(),
                mock(UriFactoryInterface::class)->get(),
                mock(UploadedFileFactoryInterface::class)->get(),
                mock(StreamFactoryInterface::class)->get(),
            ]);

            $this->container->get->with(ServerRequestCreator::class)->returns($creator);

            $factory = $this->factories[ServerRequestCreatorInterface::class];

            $test = $factory($this->container->get());

            expect($test)->toBe($creator);

        });

        it('should provide a EmitterInterface entry aliasing the EmitterStack one', function () {

            $emitter = new EmitterStack;

            $this->container->get->with(EmitterStack::class)->returns($emitter);

            $factory = $this->factories[EmitterInterface::class];

            $test = $factory($this->container->get());

            expect($test)->toBe($emitter);

        });

        it('should provide a HttpEntrypoint entry', function () {

            $creator = mock(ServerRequestCreatorInterface::class);
            $handler = mock(RequestHandlerInterface::class);
            $emitter = mock(EmitterInterface::class);

            $this->container->get->with(ServerRequestCreatorInterface::class)->returns($creator);
            $this->container->get->with(RequestHandlerInterface::class)->returns($handler);
            $this->container->get->with(EmitterInterface::class)->returns($emitter);

            $factory = $this->factories[HttpEntrypoint::class];

            $test = $factory($this->container->get());

            expect($test)->toEqual(new HttpEntrypoint(...[
                $creator->get(),
                $handler->get(),
                [$emitter->get(), 'emit'],
            ]));

        });

        it('should provide an EmitterStack entry with a SapiEmitter', function () {

            $factory = $this->factories[EmitterStack::class];

            $test = $factory($this->container->get());

            $emitter = new EmitterStack;

            $emitter->push(new SapiEmitter);

            expect($test)->toEqual($emitter);

        });

        it('should provide a ServerRequestCreator entry', function () {

            $request = mock(ServerRequestFactoryInterface::class);
            $uri = mock(UriFactoryInterface::class);
            $uploaded = mock(UploadedFileFactoryInterface::class);
            $stream = mock(StreamFactoryInterface::class);

            $this->container->get->with(ServerRequestFactoryInterface::class)->returns($request);
            $this->container->get->with(UriFactoryInterface::class)->returns($uri);
            $this->container->get->with(UploadedFileFactoryInterface::class)->returns($uploaded);
            $this->container->get->with(StreamFactoryInterface::class)->returns($stream);

            $factory = $this->factories[ServerRequestCreator::class];

            $test = $factory($this->container->get());

            expect($test)->toEqual(new ServerRequestCreator(...[
                $request->get(),
                $uri->get(),
                $uploaded->get(),
                $stream->get(),
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

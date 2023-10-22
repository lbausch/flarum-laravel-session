<?php

declare(strict_types=1);

namespace Tests;

use Bausch\FlarumLaravelSession\FlarumSessionMiddleware;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class FlarumMiddlewareTest extends TestCase
{
    public function testExceptionIsThrownIfSessionCouldNotBeFound()
    {
        $exception_was_thrown = false;

        try {
            $response = (new FlarumSessionMiddleware())->handle(new Request(), function ($request) {});
        } catch (HttpException $exception) {
            $this->assertEquals(403, $exception->getStatusCode());

            $exception_was_thrown = true;
        }

        $this->assertTrue($exception_was_thrown);
    }

    public function testInvalidSessionCookieResultsInRedirectToFlarum()
    {
        $request = new Request($query = [], $request = [], $attributes = [], $cookies = [
            'flarum_session' => 'foobar',
        ]);

        $response = (new FlarumSessionMiddleware())->handle($request, function ($request) {});

        $this->assertEquals(Config::get('flarum.url'), $response->getTargetUrl());
    }

    public function testMiddlewareExecutesNextRequest()
    {
        $session_id = $this->generateSessionId();
        $session_file = Config::get('flarum.session.path').'/'.$session_id;

        // Mock Filesystem
        $this->partialMock(Filesystem::class, function ($mock) use ($session_file) {
            $mock->shouldReceive('isFile')
                ->once()
                ->with($session_file)
                ->andReturn(true);

            $mock->shouldReceive('lastModified')
                ->once()
                ->with($session_file)
                ->andReturn(time());

            $mock->shouldReceive('sharedGet')
                ->once()
                ->with($session_file)
                ->andReturn(serialize(['access_token' => 'foobar']));
        });

        $request = new Request($query = [], $request = [], $attributes = [], $cookies = [
            'flarum_session' => $session_id,
        ]);

        $next_request_executed = false;

        $response = (new FlarumSessionMiddleware())->handle($request, function ($request) use (&$next_request_executed) {
            $next_request_executed = true;
        });

        $this->assertTrue($next_request_executed);

        // Verify user was created
        $users = DB::table('users')->get();

        $this->assertEquals(1, $users->count());

        $user = $users->first();

        $this->assertEquals(1, $user->flarum_id);
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('testuser@flarum.tld', $user->email);
    }

    public function testUsersDontGetCreatedTwice()
    {
        $session_id = $this->generateSessionId();
        $session_file = Config::get('flarum.session.path').'/'.$session_id;

        // Mock Filesystem
        $this->partialMock(Filesystem::class, function ($mock) use ($session_file) {
            $mock->shouldReceive('isFile')
                ->once()
                ->with($session_file)
                ->andReturn(true);

            $mock->shouldReceive('lastModified')
                ->once()
                ->with($session_file)
                ->andReturn(time());

            $mock->shouldReceive('sharedGet')
                ->once()
                ->with($session_file)
                ->andReturn(serialize(['access_token' => 'foobar']));
        });

        $request = new Request($query = [], $request = [], $attributes = [], $cookies = [
            'flarum_session' => $session_id,
        ]);

        $next_request_executed = false;

        $response = (new FlarumSessionMiddleware())->handle($request, function ($request) use (&$next_request_executed) {
            $next_request_executed = true;
        });

        $this->assertTrue($next_request_executed);

        $users = DB::table('users')->get();

        $this->assertEquals(1, $users->count());

        $user = $users->first();

        $this->assertEquals(1, $user->flarum_id);
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('testuser@flarum.tld', $user->email);
    }

    public function testMiddlewareExecutesNextRequestEarly()
    {
        $session_id = $this->generateSessionId();
        $session_file = Config::get('flarum.session.path').'/'.$session_id;

        // Mock Filesystem
        $this->partialMock(Filesystem::class, function ($mock) use ($session_file) {
            $mock->shouldReceive('isFile')
                ->once()
                ->with($session_file)
                ->andReturn(true);

            $mock->shouldReceive('lastModified')
                ->once()
                ->with($session_file)
                ->andReturn(time());

            $mock->shouldReceive('sharedGet')
                ->once()
                ->with($session_file)
                ->andReturn(serialize(['access_token' => 'foobar']));
        });

        $request = new Request($query = [], $request = [], $attributes = [], $cookies = [
            'flarum_session' => $session_id,
        ]);

        $next_request_executed = false;

        $response = (new FlarumSessionMiddleware())->handle($request, function ($request) use (&$next_request_executed) {
            $next_request_executed = true;
        });

        $this->assertTrue($next_request_executed);

        // Mock Filesystem
        $this->partialMock(Filesystem::class, function ($mock) {
            $mock->shouldReceive('isFile')
                ->never();

            $mock->shouldReceive('lastModified')
                ->never();

            $mock->shouldReceive('sharedGet')
                ->never();
        });

        $next_request_executed = false;

        $response = (new FlarumSessionMiddleware())->handle($request, function ($request) use (&$next_request_executed) {
            $next_request_executed = true;
        });

        $this->assertTrue($next_request_executed);
    }

    public function testMiddlewareRedirectsIfUserWasNotFound()
    {
        $session_id = $this->generateSessionId();
        $session_file = Config::get('flarum.session.path').'/'.$session_id;

        // Mock Filesystem
        $this->partialMock(Filesystem::class, function ($mock) use ($session_file) {
            $mock->shouldReceive('isFile')
                ->once()
                ->with($session_file)
                ->andReturn(true);

            $mock->shouldReceive('lastModified')
                ->once()
                ->with($session_file)
                ->andReturn(time());

            $mock->shouldReceive('sharedGet')
                ->once()
                ->with($session_file)
                ->andReturn(serialize(['access_token' => 'loremipsum']));
        });

        $request = new Request($query = [], $request = [], $attributes = [], $cookies = [
            'flarum_session' => $session_id,
        ]);

        $exception_was_thrown = false;

        try {
            $response = (new FlarumSessionMiddleware())->handle($request, function ($request) {});
        } catch (HttpException $exception) {
            $this->assertEquals(403, $exception->getStatusCode());

            $exception_was_thrown = true;
        }

        $this->assertTrue($exception_was_thrown);
    }

    /**
     * Generate session id.
     */
    protected function generateSessionId(): string
    {
        return Str::random(40);
    }
}

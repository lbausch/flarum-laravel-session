<?php

declare(strict_types=1);

namespace Tests;

use Bausch\FlarumLaravelSession\FlarumMiddleware;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class MiddlewareTest extends TestCase
{
    protected $session_id = 'ED43rG2UJAoXngkRmufC0xKHueYIyK796ztr57B7';

    public function testExceptionIsThrownIfSessionCouldNotBeFound()
    {
        $exception_was_thrown = false;

        try {
            $response = (new FlarumMiddleware())->handle(new Request(), function ($request) {});
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

        $response = (new FlarumMiddleware())->handle($request, function ($request) {});

        $this->assertEquals(Config::get('flarum.url'), $response->getTargetUrl());
    }

    public function testMiddlewareExecutesNextRequest()
    {
        $session_file = Config::get('flarum.session.path').'/'.$this->session_id;

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
                ->andReturn(serialize(['user_id' => 1]));
        });

        // This ultimately should because of the missing database connection
        $request = new Request($query = [], $request = [], $attributes = [], $cookies = [
            'flarum_session' => $this->session_id,
        ]);

        $next_request_executed = false;

        $response = (new FlarumMiddleware())->handle($request, function ($request) use (&$next_request_executed) {
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

        // Make sure users don't get created twice
        $next_request_executed = false;

        $response = (new FlarumMiddleware())->handle($request, function ($request) use (&$next_request_executed) {
            $next_request_executed = true;
        });

        $users = DB::table('users')->get();

        $this->assertEquals(1, $users->count());

        $user = $users->first();

        $this->assertEquals(1, $user->flarum_id);
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('testuser@flarum.tld', $user->email);
    }
}

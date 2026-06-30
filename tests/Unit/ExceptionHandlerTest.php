<?php

namespace Tests\Unit;

use App\Exceptions\Handler;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Http\Request;
use Tests\TestCase;

class ExceptionHandlerTest extends TestCase
{
    public function test_render_returns_json_403_for_permission_denied(): void
    {
        $handler = new Handler($this->app);
        $request = Request::create('/api/test');

        $exception = new PermissionDeniedException('No tienes permiso');

        $response = $handler->render($request, $exception);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('No tienes permiso', $response->getData(true)['message']);
    }

    public function test_render_returns_default_message_when_permission_denied_has_no_message(): void
    {
        $handler = new Handler($this->app);
        $request = Request::create('/api/test');

        $exception = new PermissionDeniedException('');

        $response = $handler->render($request, $exception);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Forbidden', $response->getData(true)['message']);
    }
}

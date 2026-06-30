<?php

namespace Tests\Unit\Admin\User;

use App\Http\Actions\Admin\User\GetUserAction;
use App\Commands\Admin\User\GetUserCommand;
use Illuminate\Http\Request;
use Tests\TestCase;

class GetUserActionTest extends TestCase
{
    public function test_execute_returns_200_when_user_found(): void
    {
        $userData = [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'roles' => [],
            'user_permissions' => [],
            'effective_permissions' => [],
        ];

        $command = $this->createMock(GetUserCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->with(1)
            ->willReturn($userData);

        $action = new GetUserAction($command);
        $response = $action->execute(1);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($userData, $response->getData(true));
    }

    public function test_execute_returns_404_when_user_not_found(): void
    {
        $command = $this->createMock(GetUserCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->with(999)
            ->willReturn(null);

        $action = new GetUserAction($command);
        $response = $action->execute(999);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Usuario no encontrado', $response->getData(true)['message']);
    }

    public function test_invoke_delegates_to_execute(): void
    {
        $userData = ['id' => 2, 'name' => 'Another User', 'email' => 'another@example.com'];

        $command = $this->createMock(GetUserCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->with(2)
            ->willReturn($userData);

        $action = new GetUserAction($command);

        $request = Request::create('/admin/users/2', 'GET');
        $response = $action->__invoke($request, '2');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($userData, $response->getData(true));
    }
}

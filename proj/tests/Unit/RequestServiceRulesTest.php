<?php

namespace Tests\Unit;

use App\Application\Requests\RequestService;
use App\Domain\Enums\RequestStatus;
use App\Domain\Enums\UserRole;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RequestServiceRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_cancel_done_request(): void
    {
        $dispatcher = User::query()->create([
            'name' => 'Dispatcher',
            'email' => 'dispatcher@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::Dispatcher->value,
        ]);

        $req = ServiceRequest::query()->create([
            'clientName' => 'Client',
            'phone' => '+100',
            'address' => 'Addr',
            'problemText' => 'Problem',
            'status' => RequestStatus::Done,
            'assignedTo' => null,
            'version' => 1,
        ]);

        $this->actingAs($dispatcher);

        $service = app(RequestService::class);

        $this->expectException(ValidationException::class);
        $service->cancel($req);
    }
}

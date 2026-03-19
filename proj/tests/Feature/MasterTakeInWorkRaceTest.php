<?php

namespace Tests\Feature;

use App\Domain\Enums\RequestStatus;
use App\Domain\Enums\UserRole;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterTakeInWorkRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_take_in_work_is_protected_from_race_condition(): void
    {
        $master = User::query()->create([
            'name' => 'Master',
            'email' => 'master@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::Master->value,
        ]);

        $req = ServiceRequest::query()->create([
            'clientName' => 'Client',
            'phone' => '+100',
            'address' => 'Addr',
            'problemText' => 'Problem',
            'status' => RequestStatus::Assigned,
            'assignedTo' => $master->id,
            'version' => 1,
        ]);

        $this->actingAs($master);

        // "Параллельность" моделируем двумя запросами, которые отправляют одинаковую ожидаемую version=1.
        $r1 = $this->postJson(route('master.requests.take', $req), ['version' => 1]);
        $r1->assertOk();
        $r1->assertJsonPath('status', RequestStatus::InProgress->value);

        $r2 = $this->postJson(route('master.requests.take', $req), ['version' => 1]);
        $r2->assertStatus(409);
        $r2->assertSeeText('Заявка уже взята в работу');
    }
}

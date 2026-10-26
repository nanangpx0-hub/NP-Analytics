<?php

namespace Tests\Feature;

use App\Livewire\SyncButton;
use App\Services\SyncException;
use App\Services\SyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class SyncButtonTest extends TestCase
{
    use RefreshDatabase;

    private function bindService(callable $configure): void
    {
        $service = Mockery::mock(SyncService::class);
        $service->shouldReceive('getLastSyncAt')->andReturn(null);
        $service->shouldReceive('getLastServerVersion')->andReturn(12);

        $configure($service);

        $this->app->instance(SyncService::class, $service);
    }

    public function test_summary_uses_utf8_middle_dot_and_warns_when_conflicts_exist(): void
    {
        $this->bindService(function (Mockery\MockInterface $service): void {
            $service->shouldReceive('sync')->once()->andReturn([
                'pushed' => ['categories' => 1, 'indicators' => 2, 'phenomena' => 0],
                'pulled' => ['categories' => 0, 'indicators' => 1, 'phenomena' => 0],
                'conflicts' => [['type' => 'indicator', 'uuid' => 'abc']],
                'errors' => [],
            ]);
        });

        Livewire::test(SyncButton::class)
            ->call('syncNow')
            ->assertSet('status', 'warning')
            ->assertSet('message', 'Push K1 I2 F0 '."\u{00B7}".' Pull K0 I1 F0 '."\u{00B7}".' Conflict 1')
            ->assertSee("\u{00B7}");
    }

    public function test_already_running_sync_shows_friendly_message(): void
    {
        $this->bindService(function (Mockery\MockInterface $service): void {
            $service->shouldReceive('sync')->once()->andThrow(SyncException::alreadyRunning());
        });

        Livewire::test(SyncButton::class)
            ->call('syncNow')
            ->assertSet('status', 'error')
            ->assertSee('Sinkronisasi lain sedang berjalan');
    }
}

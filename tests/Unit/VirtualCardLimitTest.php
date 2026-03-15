<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\VirtualCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VirtualCardLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_have_more_than_three_open_virtual_cards(): void
    {
        $user = User::factory()->create();
        $service = app(VirtualCardService::class);

        $service->createForUser($user, ['label' => 'Card 1', 'brand' => 'shell-sa']);
        $service->createForUser($user, ['label' => 'Card 2', 'brand' => 'bp-southern-africa']);
        $service->createForUser($user, ['label' => 'Card 3', 'brand' => 'engen']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('up to 3');

        $service->createForUser($user, ['label' => 'Card 4', 'brand' => 'sasol']);
    }

    public function test_user_cannot_have_two_open_cards_for_same_brand(): void
    {
        $user = User::factory()->create();
        $service = app(VirtualCardService::class);

        $service->createForUser($user, ['label' => 'Shell', 'brand' => 'shell-sa']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('brand');

        $service->createForUser($user, ['label' => 'Shell Again', 'brand' => 'shell-sa']);
    }
}

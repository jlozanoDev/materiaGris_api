<?php

namespace Tests\Unit;

use App\Providers\RouteServiceProvider;
use Tests\TestCase;

class RouteServiceProviderTest extends TestCase
{
    public function test_provider_can_be_instantiated(): void
    {
        $provider = new RouteServiceProvider($this->app);

        $this->assertInstanceOf(RouteServiceProvider::class, $provider);
    }

    public function test_register_does_not_throw(): void
    {
        $provider = new RouteServiceProvider($this->app);

        $provider->register();

        $this->assertTrue(true);
    }

    public function test_boot_does_not_throw(): void
    {
        $provider = new RouteServiceProvider($this->app);

        $provider->boot();

        $this->assertTrue(true);
    }
}

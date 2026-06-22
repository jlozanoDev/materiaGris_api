<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LlmConfigTest extends TestCase
{
    #[Test]
    public function llm_config_has_expected_structure(): void
    {
        $config = config('llm');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('provider', $config);
        $this->assertArrayHasKey('api_key', $config);
        $this->assertArrayHasKey('model', $config);
        $this->assertArrayHasKey('base_url', $config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('retry_attempts', $config);
    }

    #[Test]
    public function llm_config_returns_defaults_when_env_not_set(): void
    {
        $this->assertEquals('openai', config('llm.provider'));
        $this->assertEquals('', config('llm.api_key'));
        $this->assertEquals('gpt-4o', config('llm.model'));
        $this->assertEquals('https://api.openai.com/v1', config('llm.base_url'));
        $this->assertEquals(30, config('llm.timeout'));
        $this->assertEquals(1, config('llm.retry_attempts'));
    }
}

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
    public function llm_config_values_match_environment(): void
    {
        $this->assertEquals(env('LLM_PROVIDER', 'openai'), config('llm.provider'));
        $this->assertEquals(env('LLM_API_KEY', ''), config('llm.api_key'));
        $this->assertEquals(env('LLM_MODEL', 'gpt-4o'), config('llm.model'));
        $this->assertEquals(env('LLM_BASE_URL', 'https://api.openai.com/v1'), config('llm.base_url'));
        $this->assertEquals((int) env('LLM_TIMEOUT', 30), config('llm.timeout'));
        $this->assertEquals((int) env('LLM_RETRY_ATTEMPTS', 1), config('llm.retry_attempts'));
    }
}

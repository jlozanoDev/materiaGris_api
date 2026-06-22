<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\LlmTimeoutException;
use App\Exceptions\LlmResponseException;
use App\Exceptions\LlmUnavailableException;
use App\Exceptions\TemplateNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LlmExceptionsTest extends TestCase
{
    #[Test]
    public function llm_timeout_exception_has_http_code_500(): void
    {
        $exception = new LlmTimeoutException();
        $this->assertEquals(500, $exception->getHttpCode());
        $this->assertEquals('LLM request timed out', $exception->getMessage());
    }

    #[Test]
    public function llm_response_exception_has_http_code_500(): void
    {
        $exception = new LlmResponseException('Malformed JSON response');
        $this->assertEquals(500, $exception->getHttpCode());
        $this->assertEquals('Malformed JSON response', $exception->getMessage());
    }

    #[Test]
    public function llm_unavailable_exception_has_http_code_503(): void
    {
        $exception = new LlmUnavailableException('Service unavailable');
        $this->assertEquals(503, $exception->getHttpCode());
        $this->assertEquals('Service unavailable', $exception->getMessage());
    }

    #[Test]
    public function template_not_found_exception_has_http_code_400(): void
    {
        $exception = new TemplateNotFoundException('Template not found');
        $this->assertEquals(400, $exception->getHttpCode());
        $this->assertEquals('Template not found', $exception->getMessage());
    }

    #[Test]
    public function all_exceptions_extend_exception(): void
    {
        $this->assertInstanceOf(\Exception::class, new LlmTimeoutException());
        $this->assertInstanceOf(\Exception::class, new LlmResponseException());
        $this->assertInstanceOf(\Exception::class, new LlmUnavailableException());
        $this->assertInstanceOf(\Exception::class, new TemplateNotFoundException());
    }
}

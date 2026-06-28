<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiResponseException;
use App\Exceptions\AiUnavailableException;
use App\Exceptions\TemplateNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiExceptionsTest extends TestCase
{
    #[Test]
    public function ai_timeout_exception_has_http_code_500(): void
    {
        $exception = new AiTimeoutException();
        $this->assertEquals(500, $exception->getHttpCode());
        $this->assertEquals('AI request timed out', $exception->getMessage());
    }

    #[Test]
    public function ai_response_exception_has_http_code_500(): void
    {
        $exception = new AiResponseException('Malformed JSON response');
        $this->assertEquals(500, $exception->getHttpCode());
        $this->assertEquals('Malformed JSON response', $exception->getMessage());
    }

    #[Test]
    public function ai_unavailable_exception_has_http_code_503(): void
    {
        $exception = new AiUnavailableException('Service unavailable');
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
        $this->assertInstanceOf(\Exception::class, new AiTimeoutException());
        $this->assertInstanceOf(\Exception::class, new AiResponseException());
        $this->assertInstanceOf(\Exception::class, new AiUnavailableException());
        $this->assertInstanceOf(\Exception::class, new TemplateNotFoundException());
    }
}

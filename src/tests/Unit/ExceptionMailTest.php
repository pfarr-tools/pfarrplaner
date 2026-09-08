<?php

namespace Tests\Unit;

use App\Exceptions\ExceptionMail;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class ExceptionMailTest extends TestCase
{
    public function testMalformedUtf8CannotBreakQueuedExceptionMailPayload(): void
    {
        $exception = FlattenException::createFromThrowable(new RuntimeException("invalid \xFF input"));
        $mail = new ExceptionMail($exception, [
            'context' => ['input' => "invalid \xFF input"],
        ]);

        $payload = json_encode(['data' => serialize($mail)]);

        $this->assertNotFalse($payload);
    }
}

<?php
declare(strict_types=1);

namespace EonX\EasyWebhook\Tests\Middleware;

use EonX\EasyWebhook\Interfaces\WebhookInterface;
use EonX\EasyWebhook\Interfaces\WebhookResultInterface;
use EonX\EasyWebhook\Middleware\StatusAndAttemptMiddleware;
use EonX\EasyWebhook\Tests\AbstractMiddlewareTestCase;
use EonX\EasyWebhook\Webhook;
use EonX\EasyWebhook\WebhookResult;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpClient\Response\MockResponse;

final class StatusAndAttemptMiddlewareTest extends AbstractMiddlewareTestCase
{
    /**
     * @see testProcess
     */
    public static function providerTestProcess(): iterable
    {
        yield 'successful' => [new WebhookResult(new Webhook(), new MockResponse()), WebhookInterface::STATUS_SUCCESS];

        yield 'failed pending retry' => [
            new WebhookResult(Webhook::fromArray([
                WebhookInterface::OPTION_MAX_ATTEMPT => 2,
            ]), null, new Exception()),
            WebhookInterface::STATUS_FAILED_PENDING_RETRY,
        ];

        yield 'failed' => [
            new WebhookResult(Webhook::fromArray([
                WebhookInterface::OPTION_MAX_ATTEMPT => 1,
            ]), null, new Exception()),
            WebhookInterface::STATUS_FAILED,
        ];
    }

    #[DataProvider('providerTestProcess')]
    public function testProcess(WebhookResultInterface $webhookResult, string $status): void
    {
        $middleware = new StatusAndAttemptMiddleware();

        $result = $this->process($middleware, new Webhook(), $webhookResult);

        self::assertEquals($status, $result->getWebhook()->getStatus());
    }
}

<?php

declare(strict_types=1);

namespace EonX\EasyWebhook;

use EonX\EasyWebhook\Interfaces\WebhookInterface;
use EonX\EasyWebhook\Interfaces\WebhookResultHandlerInterface;
use EonX\EasyWebhook\Interfaces\WebhookResultInterface;
use EonX\EasyWebhook\Interfaces\WebhookStoreInterface;
use EonX\EasyWebhook\RetryStrategies\NullWebhookRetryStrategy;

final class WebhookResultHandler implements WebhookResultHandlerInterface
{
    /**
     * @var \EonX\EasyWebhook\Interfaces\WebhookStoreInterface
     */
    private $store;

    public function __construct(WebhookStoreInterface $store)
    {
        $this->store = $store;
    }

    public function handle(WebhookResultInterface $webhookResult): void
    {
        $webhook = $webhookResult->getWebhook();
        $response = $webhookResult->getResponse();
        $throwable = $webhookResult->getThrowable();

        $webhook->setCurrentAttempt($webhook->getCurrentAttempt() + 1);

        switch ($webhookResult->isSuccessful()) {
            case true:
                $webhook->setStatus(WebhookInterface::STATUS_SUCCESS);
                break;
            case false:
                $webhook->setStatus(
                    $webhook->getCurrentAttempt() >= $webhook->getMaxAttempt()
                        ? WebhookInterface::STATUS_FAILED
                        : WebhookInterface::STATUS_FAILED_PENDING_RETRY
                );
        }

        // Merge extra so each of them is separate column
        $data = \array_merge($webhook->getExtra() ?? [], $webhook->toArray());

        // Add class to be able to instantiate when fetching from store
        $data['class'] = \get_class($webhook);

        if ($response !== null) {
            $data['response'] = [
                'content' => $response->getContent(),
                'headers' => $response->getHeaders(),
                'info' => $response->getInfo(),
                'status_code' => $response->getStatusCode(),
            ];
        }

        if ($throwable !== null) {
            $data['throwable'] = [
                'code' => $throwable->getCode(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'message' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ];
        }

        $this->store->store($data, $webhook->getId());
    }
}

<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Webhook;

use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class GithubWebhookParser extends AbstractRequestParser
{
    public function __construct(private readonly string $webhookSecret)
    {
    }

    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            new MethodRequestMatcher(['POST']),
        ]);
    }

    protected function doParse(Request $request, string $secret): ?RemoteEvent
    {
        /** @var string $payload */
        $payload = $request->getContent();

        $signatureFromPayload = $request->headers->get('X_HUB_SIGNATURE_256', null);
        if (null === $signatureFromPayload) {
            throw new RejectWebhookException(Response::HTTP_NOT_ACCEPTABLE, 'Signature is missing');
        }

        $signature = 'sha256='.hash_hmac('sha256', $payload, $this->webhookSecret);
        if (!hash_equals($signature, $signatureFromPayload)) {
            throw new RejectWebhookException(Response::HTTP_FORBIDDEN, 'Access denied');
        }

        /** @var array<mixed> $payloadAsArray */
        $payloadAsArray = json_decode($payload, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Error on json');
        }
        $payloadAsArray['event-type'] = $request->headers->get('X-GitHub-Event');

        return new RemoteEvent('github.event', 'event-id', $payloadAsArray);
    }
}

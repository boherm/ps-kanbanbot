<?php

declare(strict_types=1);

namespace App\Shared\Webhook;

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
        $payload = $request->toArray();

        $signatureFromPayload = $request->headers->get('X_HUB_SIGNATURE_256', null);
        if (null === $signatureFromPayload) {
            throw new RejectWebhookException(Response::HTTP_NOT_ACCEPTABLE, 'Signature is missing');
        }

        /** @var string $payloadAsString */
        $payloadAsString = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $payloadAsString, $this->webhookSecret);
        if (!hash_equals($signature, $signatureFromPayload)) {
            throw new RejectWebhookException(Response::HTTP_FORBIDDEN, 'Access denied');
        }

        $payload['event-type'] = $request->headers->get('X-GitHub-Event');

        return new RemoteEvent('github.event', 'event-id', $payload);
    }
}

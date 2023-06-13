<?php

declare(strict_types=1);

namespace App\Shared\Webhook;

use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\HostRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class GithubWebhookParser extends AbstractRequestParser
{
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            /*new HostRequestMatcher('github.com'),
            new IsJsonRequestMatcher(),
            new MethodRequestMatcher('POST'),*/
        ]);
    }

    protected function doParse(Request $request, string $secret): ?RemoteEvent
    {
        $content = $request->toArray();

        $content['event-type'] = $request->headers->get('X-GitHub-Event');

        /*if (!isset($content['signature']['token'])) {
            throw new RejectWebhookException(406, 'Payload is malformed.');
        }*/

        return new RemoteEvent('github.event', 'event-id', $content);
    }
}

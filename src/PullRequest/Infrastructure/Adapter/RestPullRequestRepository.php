<?php

declare(strict_types=1);

namespace App\PullRequest\Infrastructure\Adapter;

use App\PullRequest\Domain\Aggregate\PullRequest\Approval;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequest;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestDiff;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestId;
use App\PullRequest\Domain\Gateway\PullRequestRepositoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

class RestPullRequestRepository implements PullRequestRepositoryInterface
{
    public function __construct(
        private readonly HttpClientInterface $githubClient,
        private readonly Environment $twig,
    ) {
    }

    public function find(PullRequestId $pullRequestId): ?PullRequest
    {
        $response = $this->githubClient->request('GET', '/repos/'.$pullRequestId->repositoryOwner.'/'.$pullRequestId->repositoryName.'/pulls/'.$pullRequestId->pullRequestNumber);
        $responseArr = $response->toArray();

        return PullRequest::create(
            $pullRequestId,
            array_map(
                static function (array $label): string {
                    return $label['name'];
                },
                $responseArr['labels']
            ),
            $this->getApprovals($pullRequestId),
            $responseArr['base']['ref']
        );
    }

    public function update(PullRequest $pullRequest): void
    {
        $this->githubClient->request(
            'PATCH',
            '/repos/'.$pullRequest->getId()->repositoryOwner.'/'.$pullRequest->getId()->repositoryName.'/issues/'.$pullRequest->getId()->pullRequestNumber,
            [
                'json' => [
                    'labels' => $pullRequest->getLabels(),
                ],
            ]
        );
    }

    /**
     * @return Approval[]
     */
    public function getApprovals(PullRequestId $pullRequestId): array
    {
        $reviews = $this->githubClient->request('GET', '/repos/'.$pullRequestId->repositoryOwner.'/'.$pullRequestId->repositoryName.'/pulls/'.$pullRequestId->pullRequestNumber.'/reviews')->toArray();

        $approvals = [];
        foreach ($reviews as $review) {
            if ('APPROVED' === $review['state']) {
                $approvals[$review['user']['login']] = new Approval($review['user']['login']);
            } elseif ('CHANGES_REQUESTED' === $review['state'] || 'DISMISSED' === $review['state']) {
                unset($approvals[$review['user']['login']]);
            }
        }

        return $approvals;
    }

    public function getDiff(PullRequestId $pullRequestId): PullRequestDiff
    {
        $diff = $this->githubClient->request(
            'GET',
            '/repos/'.$pullRequestId->repositoryOwner.'/'.$pullRequestId->repositoryName.'/pulls/'.$pullRequestId->pullRequestNumber,
            ['headers' => ['Accept' => 'application/vnd.github.v3.diff']]
        );

        return PullRequestDiff::parseDiff($pullRequestId, $diff->getContent());
    }

    /**
     * @param array<string, array<int, string|null>> $newTranslations
     * @param string[]                               $newDomains
     */
    public function addTranslationsComment(PullRequestId $pullRequestId, array $newTranslations, array $newDomains): void
    {
        // First, we need to check if the comment already exists.
        $t9nCommentIdExist = false;
        $validatedWordings = [];
        $comments = $this->githubClient->request('GET', '/repos/'.$pullRequestId->repositoryOwner.'/'.$pullRequestId->repositoryName.'/issues/'.$pullRequestId->pullRequestNumber.'/comments')->toArray();

        // if we have found one PR WORDING comment, in all comments:
        foreach ($comments as $comment) {
            if (false !== strpos($comment['body'], '<!-- PR_WORDING -->')) {
                $t9nCommentIdExist = $comment['id'];
                // We need to determine if the comment is already validated with a tick.
                $matches = [];
                preg_match_all('/^- (?:\[(x| )\].*)?\s*`(.*)`((?:\s{4,}- \[.\] .*)+)/mi', $comment['body'], $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $wordings = [];
                    preg_match_all('/^\s+- \[x\] `(.*)`/mi', $match[3], $wordings, PREG_SET_ORDER);
                    foreach ($wordings as $wording) {
                        $validatedWordings[$match[2]][] = $wording[1];
                    }
                }
                break;
            }
        }

        // Then we need to format the new comment with the new translations keys.
        $comment = $this->twig->render('pr_comments/wordings.html.twig', [
            'alreadyValidatedWordings' => $validatedWordings,
            'newTranslations' => $newTranslations,
            'newDomains' => $newDomains,
        ]);

        // Finally, we need to add or edit the comment to the PR.
        if ($t9nCommentIdExist) {
            // Edit $t9nCommentIdExist comment
            $this->githubClient->request('PATCH', '/repos/'.$pullRequestId->repositoryOwner.'/'.$pullRequestId->repositoryName.'/issues/comments/'.$t9nCommentIdExist, [
                'json' => ['body' => $comment],
            ]);
        } else {
            // add new comment
            $this->githubClient->request('POST', '/repos/'.$pullRequestId->repositoryOwner.'/'.$pullRequestId->repositoryName.'/issues/'.$pullRequestId->pullRequestNumber.'/comments', [
                'json' => ['body' => $comment],
            ]);
        }
    }
}

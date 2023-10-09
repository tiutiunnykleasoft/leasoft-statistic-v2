<?php

namespace App\Http\SlackClient;

interface SlackClient
{
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $redirectUrl,
        string $endpoint
    );

    public function oauthAccess(string $code): array;

    public function conversationsHistory(string $channel, string $oldest, $latest = null): array;

    public function conversationsReplies(string $channel, string $ts): array;

    public function userDetails(string $userId): array;

    public function chatPostMessage(string $channel, string $text = null, array $blocks = []): array;

    public function send(HttpMethod $method, string $path, array $data = null, string $authToken = null, array $options = []): string|array|bool|int|null|object|float;
}

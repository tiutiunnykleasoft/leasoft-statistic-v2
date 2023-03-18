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

    public function conversationsHistory(string $token, string $channel, string $oldest, $latest = null): array;

    public function conversationsReplies(string $token, string $channel, string $ts): array;

    public function chatPostMessage(string $token, string $channel, string $text = null, array $blocks = []): array;

    public function send(HttpMethod $method, string $path, array $data = null, string $authToken = null, array $options = []): string|array|bool|int|null|object|float;
}

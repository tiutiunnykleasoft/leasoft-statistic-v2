<?php

namespace App\Http\SlackClient;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HttpSlackClient implements SlackClient
{
    /**
     * @var \GuzzleHttp\Client
     */
    private Client $guzzleClient;
    private string $token;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUrl,
        private readonly string $endpoint
    )
    {
        $this->guzzleClient = new Client([
            'keepalive' => false,
            'timeout' => 5,
        ]);
    }

    public function setToken(string $token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @throws \App\Http\SlackClient\SlackException
     */
    public function oauthAccess(string $code): array
    {
        return $this->send(
            method: HttpMethod::POST,
            path: 'oauth.v2.access',
            data: [
                "client_id" => $this->clientId,
                "client_secret" => $this->clientSecret,
                "redirect_uri" => $this->redirectUrl,
                "code" => $code,
                "grant_type" => 'authorization_code'
            ]
        );
    }

    public function conversationsHistory(string $channel, string $oldest, $latest = null): array
    {
        return $this->send(
            method: HttpMethod::GET,
            path: 'conversations.history',
            data: [
                'channel' => $channel,
                'oldest' => $oldest,
                'latest' => $latest
            ],
            authToken: $this->token
        )['messages'];
    }

    public function conversationsReplies(string $channel, string $ts): array
    {
        return $this->send(
            method: HttpMethod::GET,
            path: 'conversations.replies',
            data: [
                'channel' => $channel,
                'ts' => $ts
            ],
            authToken: $this->token
        )['messages'];
    }

    public function userDetails(string $userId): array
    {
        return $this->send(
            method: HttpMethod::GET,
            path: 'users.info',
            data: [
                'user' => $userId,
            ],
            authToken: $this->token
        );
    }

    public function chatPostMessage(string $channel, string $threadTs = null, string $text = null, array $blocks = []): array
    {
        return $this->send(
            method: HttpMethod::POST,
            path: 'chat.postMessage',
            data: array_filter(array_filter([
                'thread_ts' => $threadTs,
                'channel' => $channel,
                'text' => $text,
                'blocks' => json_encode($blocks)
            ])),
            authToken: $this->token
        );
    }

    /**
     * @param \App\Http\SlackClient\HttpMethod $method
     * @param string $path
     * @param array|null $data
     * @param string|null $authToken
     * @return string|array|bool|int|object|float|null
     * @throws \App\Http\SlackClient\SlackException
     * [
     * "content-type" => "specify custom content-type"; (string)
     * "absolute-path" => "specify $path variable as absolutely path instead of path to resource"; (bool),
     * "data-type" => "specify type of data"; (string)['json','query']
     * ]
     * @var array $options
     */
    public function send(HttpMethod $method, string $path, array $data = null, string $authToken = null, array $options = []): string|array|bool|int|null|object|float
    {
        $headers = array_filter([
            'Content-Type' => $options['content-type'] ?? 'application/x-www-form-urlencoded',
            'Authorization' => isset($authToken) ? 'Bearer ' . $authToken : null
        ]);

        $requestedPath = implode('/', [$this->endpoint, rtrim($path, '/')]);
        if (isset($options['absolute-path']) && $options['absolute-path']) {
            $requestedPath = $path;
        }

        $dataType = $options['data-type'] ?? 'query';

        try {
            $rawResponse = $this->guzzleClient->request(
                $method->value,
                $requestedPath,
                array_filter([
                    "headers" => $headers,
                    $dataType => !is_null($data) ? $data : null
                ]),
            );
        } catch (GuzzleException $exception) {
            echo $exception->getMessage();
            return false;
        }

        $parsedResponse = json_decode($rawResponse->getBody()->getContents());

        if (!is_null($parsedResponse) && !$parsedResponse->ok) {
            throw new SlackException(
                sprintf('error: %s; warning: %s', $parsedResponse->error, $parsedResponse->warning ?? 'no warning')
            );
        }

        return json_decode(json_encode($parsedResponse), true);
    }
}

<?php

namespace App\Http\SlackClient;

final class BlocksConstructorKit
{
    public static function header(string $text): array
    {
        return [
            "type" => "header",
            "text" => [
                "type" => "plain_text",
                "text" => $text,
                "emoji" => true
            ]
        ];
    }

    public static function markdown(string $text): array
    {
        return [
            'type' => 'mrkdwn',
            'text' => $text
        ];
    }

    public static function section(array $text, array $accessory = []): array
    {
        return array_filter([
            "type" => 'section',
            "text" => $text,
            "accessory" => $accessory
        ]);
    }

    public static function contextMarkdown(array $elements): array
    {
        return [
            "type" => "context",
            "elements" => $elements
        ];
    }

    public static function build(array $blocks): array
    {
        return [
            "blocks" => $blocks
        ];
    }
}

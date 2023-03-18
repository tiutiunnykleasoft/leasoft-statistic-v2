<?php

namespace App\Http\Controllers;

use App\Http\SlackClient\SlackClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class AuthController
{
    public function finishAuth(SlackClient $slackClient): array
    {
        $response = $slackClient->oauthAccess(
            code: $_GET['code']
        );

        return App::call(callback: 'App\Http\Controllers\UserSlackController@store',
            parameters: [
                'request' => new Request([
                    'user_id' => $response['authed_user']['id'],
                    'team_id' => $response['team']['id'],
                    'bot_token' => $response['access_token'],
                    'user_token' => $response['authed_user']['access_token'],
                ])]);
    }

    public function nextAuth(): RedirectResponse
    {
        return redirect('https://slack.com/oauth/v2/authorize?client_id=1332658603060.1401171869121&scope=app_mentions:read,channels:history,channels:read,chat:write,chat:write.customize,commands,files:write,groups:history,groups:read,im:history,im:read,incoming-webhook,mpim:read,users.profile:read,users:read&user_scope=channels:history,channels:read,channels:write,chat:write');
    }
}

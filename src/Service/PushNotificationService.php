<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PushNotificationService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Sends a push notification to all given Expo push tokens.
     *
     * @param string[] $pushTokens
     */
    public function send(array $pushTokens, string $title, string $body): void
    {
        if (empty($pushTokens)) {
            return;
        }

        $messages = array_map(fn(string $token) => [
            'to'    => $token,
            'sound' => 'default',
            'title' => $title,
            'body'  => $body,
        ], $pushTokens);

        try {
            $this->client->post('https://exp.host/--/api/v2/push/send', [
                'json'    => $messages,
                'headers' => [
                    'Accept'          => 'application/json',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Content-Type'    => 'application/json',
                ],
            ]);
        } catch (GuzzleException) {
            // Swallow — notifications are best-effort, don't fail the request
        }
    }
}

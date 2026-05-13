<?php
// src/Service/ImageModerationService.php

namespace App\Service;

use CURLFile;

class ImageModerationService
{
    private string $apiUser;
    private string $apiSecret;

    public function __construct(string $apiUser, string $apiSecret)
    {
        $this->apiUser = $apiUser;
        $this->apiSecret = $apiSecret;
    }

    /**
     * Checks whether the given image is safe
     *
     * @param string $filePath Absolute path to the uploaded file
     * @return bool true if safe, false if contains nudity/violence
     */
    public function isImageSafe(string $filePath): bool
    {
        $params = [
            'media' => new CURLFile($filePath),
            'models' => 'nudity-2.1,alcohol,offensive-2.0,violence,self-harm,gore-2.0',
            'api_user' => $this->apiUser,
            'api_secret' => $this->apiSecret,
        ];

        $ch = curl_init('https://api.sightengine.com/1.0/check.json');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        curl_close($ch);

        $output = json_decode($response, true);

        if (!$output || !isset($output['nudity'], $output['nudity']['sexual_activity'])) {
            // API unreachable or returned unexpected data — fail open
            return true;
        }

        $nudityScore =
            ($output['nudity']['sexual_activity'] ?? 0) +
            ($output['nudity']['sexual_display'] ?? 0) +
            ($output['nudity']['erotica'] ?? 0);

        $violenceScore = $output['violence']['prob'] ?? 0;

        return $nudityScore <= 0.5 && $violenceScore <= 0.5;
    }
}

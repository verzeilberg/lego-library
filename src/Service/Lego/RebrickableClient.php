<?php
// src/Service/RebrickableClient.php
namespace App\Service\Lego;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RebrickableClient
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private string $apiBaseUrl;

    public function __construct(HttpClientInterface $httpClient, string $apiKey, string $apiBaseUrl)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/');
    }

    /**
     * @param string $endpoint
     * @param array $params
     * @param int $maxRetries
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function request(string $endpoint, array $params = [], int $maxRetries = 3): array
    {
        $attempt = 0;
        while ($attempt < $maxRetries) {
            $attempt++;

            try {
                $response = $this->httpClient->request('GET', $this->apiBaseUrl . $endpoint, [
                    'headers' => [
                        'Authorization' => 'key ' . $this->apiKey,
                    ],
                    'query' => $params,
                ]);

                $status = $response->getStatusCode(false);

                // Handle rate limiting (429)
                if ($status === 429) {
                    // Check Retry-After header if present
                    $retryAfter = $response->getHeaders(false)['retry-after'][0] ?? 20; // default 20s
                    sleep((int)$retryAfter);
                    continue; // retry
                }

                // For any other non-2xx response, throw as usual
                return $response->toArray();

            } catch (TransportExceptionInterface|ServerExceptionInterface|ClientExceptionInterface $e) {
                // Optional: retry on network/server errors
                if ($attempt >= $maxRetries) {
                    throw $e;
                }
                // exponential backoff (optional)
                sleep($attempt * 2);
            }
        }

        // Should never reach here
        throw new \RuntimeException('Failed after max retries');
    }

    /**
     * @param array $params
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getSets(array $params = []): array
    {
        return $this->request('/sets/', $params);
    }

    /**
     * Retrieves details for a specific set identified by its set number.
     *
     * If the provided set number does not contain a dash, it appends "-1"
     * as the default variant before making the request.
     *
     * @param string $setNum The identifier of the set to fetch details for.
     *
     * @return array The details of the requested set.
     */
    public function getSetById(string $setNum): array
    {
        // Check if the set number already contains a dash
        $parts = explode('-', $setNum);
        if (end($parts) !== '-1') {
            $setNum .= '-1';
        }

        return $this->request("/sets/{$setNum}/");
    }

    public function getPartsBySetId(string $setNum): array
    {
        // Check if the set number already contains a dash
        $parts = explode('-', $setNum);
        if (end($parts) !== '-1') {
            $setNum .= '-1';
        }

        return $this->request("/sets/{$setNum}/parts/");
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getPartByPartNumberAndColorId(string $partNumber, string $colorId): array
    {
        return $this->request("/parts/{$partNumber}/colors/{$colorId}");
    }

    /**
     * @param string $setNum
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMiniFigsBySetNumber(string $setNum): array
    {

        // Check if the set number already contains a dash
        $parts = explode('-', $setNum);
        if (end($parts) !== '-1') {
            $setNum .= '-1';
        }

        return $this->request("/sets/{$setNum}/minifigs/");
    }

    /**
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getThemes(): array
    {
        return $this->request("/themes/");
    }

    /**
     * @return array
     */
    public function getThemeById(int $themeId): array
    {
        return $this->request("/themes/{$themeId}");
    }

}

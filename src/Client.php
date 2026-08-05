<?php

namespace Hiotech\PaysortaPhp;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Hiotech\PaysortaPhp\Exceptions\PaysortaException;

class Client
{
    protected const DEFAULT_SDK_URL = 'https://sdk.paysorta.com';

    protected HttpClient $http;

    public function __construct(string $apiSecret, ?string $baseUrl = null)
    {
        $baseUri = str_starts_with($apiSecret, 'sk_') ? getenv('PAYSORTA_BASE_URL')."".$baseUrl : getenv('PAYSORTA_TEST_BASE_URL')."".$baseUrl;

        if ($baseUrl) {
            throw new PaysortaException('Paysorta base URL is not set. Pass it explicitly or define PAYSORTA_BASE_URL.');
        }

        $this->http = new HttpClient([
            'base_uri' => rtrim($baseUri, '/') . '/',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                 'Authorization' => 'Bearer ' . $apiSecret,
            ],
        ]);
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * URL of the client-side Omni Payment Gateway JS SDK, for embedding on a checkout page:
     *   <script src="{{ Client::getSdkUrl() }}"></script>
     */
    public static function getSdkUrl(): string
    {
        return getenv('PAYSORTA_SDK_URL') ?: self::DEFAULT_SDK_URL;
    }

    protected function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->http->request($method, ltrim($endpoint, '/'), $options);

            $body = (string) $response->getBody();

            return $body === '' ? [] : json_decode($body, true);

        } catch (GuzzleRequestException $e) {
            // Some static analyzers may not recognize getResponse on the exception instance,
            // so guard the call to avoid "undefined method" issues.
            $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
            $statusCode = $response ? $response->getStatusCode() : 0;
            $body = $response ? json_decode((string) $response->getBody(), true) : [];

            throw new PaysortaException(
                $body['message'] ?? $e->getMessage(),
                $statusCode,
                $body ?? [],
                $e
            );
        } catch (GuzzleException $e) {
            throw new PaysortaException($e->getMessage(), 0, [], $e);
        }
    }
}

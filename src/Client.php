<?php

namespace Hiotech\PaysortaPhp;

use Dotenv\Dotenv;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Hiotech\PaysortaPhp\Exceptions\PaysortaException;

class Client
{
    protected const DEFAULT_SDK_URL = 'https://sdk.paysorta.com';

    protected static bool $envLoaded = false;

    protected HttpClient $http;

    public function __construct(string $apiSecret, ?string $baseUrl = null)
    {
        if (! self::$envLoaded) {
            Dotenv::createImmutable(getcwd())->safeLoad();
            self::$envLoaded = true;
        }

        $baseUri = $baseUrl ?: (str_starts_with($apiSecret, 'sk_') ? self::env('PAYSORTA_BASE_URL') : self::env('PAYSORTA_TEST_BASE_URL'));

        if (! $baseUri) {
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
        return self::env('PAYSORTA_SDK_URL') ?: self::DEFAULT_SDK_URL;
    }

    /**
     * phpdotenv v5 writes loaded values to $_ENV/$_SERVER, not the process
     * environment, so getenv() alone won't see them.
     */
    protected static function env(string $key): ?string
    {
        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        return $_ENV[$key] ?? $_SERVER[$key] ?? null;
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

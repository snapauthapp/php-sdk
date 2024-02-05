<?php

declare(strict_types=1);

namespace SnapAuth;

use JsonException;
use SensitiveParameter;

use function assert;
use function curl_close;
use function curl_errno;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt_array;
use function curl_version;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;
use function strlen;

use const CURLE_OK;
use const CURLINFO_RESPONSE_CODE;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;
use const JSON_THROW_ON_ERROR;

/**
 * SDK Prototype. This makes no attempt to short-circuit the network for
 * internal use, forcing a completely dogfooded experience.
 *
 * TODO: make testable, presumably via PSR-18
 * TODO: make an inteface so the enture client can be mocked by developers
 */
class Client
{
    private const DEFAULT_API_HOST = 'https://api.snapauth.app';

    private const VERSION = '0.0.1';

    public function __construct(
        #[SensitiveParameter] private string $secretKey,
        private string $apiHost = self::DEFAULT_API_HOST,
    ) {
    }

    /**
     * return array{
     *   user: array{
     *     id: string,
     *     handle: string,
     *   },
     * }
     */
    public function verifyAuthToken(string $authToken): AuthResponse
    {
        return $this->makeApiCall(
            route: '/auth/verify',
            data: [
                'token' => $authToken,
            ],
            type: AuthResponse::class,
        );
    }

    /**
     * @param array{
     *   handle: string,
     *   id?: string,
     * } $user
     */
    public function attachRegistration(string $regToken, array $user): AttachResponse
    {
        return $this->makeApiCall(
            route: '/registration/attach',
            data: [
                'token' => $regToken,
                'user' => $user,
            ],
            type: AttachResponse::class
        );
    }

    /**
     * @template T of object
     * @param mixed[] $data
     * @param class-string<T> $type
     * @return T
     */
    private function makeApiCall(string $route, array $data, string $type): object
    {
        // TODO: PSR-xx
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => sprintf('%s%s', $this->apiHost, $route),
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $json,
            // CURLOPT_VERBOSE => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode(':' . $this->secretKey),
                'Accept: application/json',
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json),
                sprintf(
                    'User-Agent: php-sdk/%s curl/%s php/%s',
                    self::VERSION,
                    curl_version()['version'] ?? 'unknown',
                    PHP_VERSION,
                ),
            ],
        ]);

        try {
            $response = curl_exec($ch);
            $errno = curl_errno($ch);
            $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

            if ($response === false || $errno !== CURLE_OK) {
                $this->error();
            }

            if ($code >= 300) {
                $this->error();
            }
            // Handle non-200s, non-JSON (severe upstream error)
            assert(is_string($response));
            $decoded = json_decode($response, true, flags: JSON_THROW_ON_ERROR);
            assert(is_array($decoded));
            return new $type($decoded['result']);
        } catch (JsonException) {
            $this->error();
        } finally {
            curl_close($ch);
        }
    }

    /**
     * TODO: specific error info!
     */
    private function error(): never
    {
        throw new ApiError();
        // TODO: also make this more specific
    }
}

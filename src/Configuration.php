<?php
namespace webignition\Url\Resolver;

use GuzzleHttp\Client as HttpClient;

class Configuration
{
    const CONFIG_KEY_HTTP_CLIENT = 'http-client';
    const CONFIG_KEY_FOLLOW_META_REDIRECTS = 'follow-meta-redirects';
    const CONFIG_KEY_RETRY_WITH_URL_ENCODING_DISABLED = 'retry-with-url-encoding-disabled';
    const CONFIG_KEY_TIMEOUT_MS = 'timeout-ms';

    const DEFAULT_TIMEOUT_MS = 0; // no timeout

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var bool
     */
    private $followMetaRedirects = true;

    /**
     * @var int
     */
    private $timeoutMs;

    /**
     * @param array $configurationValues
     */
    public function __construct($configurationValues = [])
    {
        if (!isset($configurationValues[self::CONFIG_KEY_HTTP_CLIENT])) {
            $configurationValues[self::CONFIG_KEY_HTTP_CLIENT] = new HttpClient();
        }

        if (!isset($configurationValues[self::CONFIG_KEY_TIMEOUT_MS])) {
            $configurationValues[self::CONFIG_KEY_TIMEOUT_MS] = self::DEFAULT_TIMEOUT_MS;
        }

        $this->httpClient = $configurationValues[self::CONFIG_KEY_HTTP_CLIENT];
        $this->timeoutMs = $configurationValues[self::CONFIG_KEY_TIMEOUT_MS];

        if (isset($configurationValues[self::CONFIG_KEY_FOLLOW_META_REDIRECTS])) {
            $this->followMetaRedirects = $configurationValues[self::CONFIG_KEY_FOLLOW_META_REDIRECTS];
        }

        if (isset($configurationValues[self::CONFIG_KEY_RETRY_WITH_URL_ENCODING_DISABLED])) {
            $this->retryWithUrlEncodingDisabled =
                $configurationValues[self::CONFIG_KEY_RETRY_WITH_URL_ENCODING_DISABLED];
        }
    }

    /**
     * @return bool
     */
    public function getFollowMetaRedirects()
    {
        return $this->followMetaRedirects;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @return int
     */
    public function getTimeoutMs()
    {
        return $this->timeoutMs;
    }
}

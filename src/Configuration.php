<?php
namespace webignition\Url\Resolver;

use GuzzleHttp\Client as HttpClient;

class Configuration
{
    const CONFIG_KEY_HTTP_CLIENT = 'http-client';
    const CONFIG_KEY_FOLLOW_META_REDIRECTS = 'follow-meta-redirects';
    const CONFIG_KEY_RETRY_WITH_URL_ENCODING_DISABLED = 'retry-with-url-encoding-disabled';

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var bool
     */
    private $followMetaRedirects = true;

    /**
     * @var bool
     */
    private $retryWithUrlEncodingDisabled = false;

    /**
     * @param array $configurationValues
     */
    public function __construct($configurationValues = [])
    {
        if (!isset($configurationValues[self::CONFIG_KEY_HTTP_CLIENT])) {
            $configurationValues[self::CONFIG_KEY_HTTP_CLIENT] = new HttpClient();
        }

        $this->httpClient = $configurationValues[self::CONFIG_KEY_HTTP_CLIENT];

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
     * @return bool
     */
    public function getRetryWithUrlEncodingDisabled()
    {
        return $this->retryWithUrlEncodingDisabled;
    }
}

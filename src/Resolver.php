<?php

namespace webignition\Url\Resolver;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\NormalisedUrl\NormalisedUrl;
use webignition\WebPageInspector\WebPageInspector;
use webignition\WebResource\WebPage\WebPage;

class Resolver
{
    const DEFAULT_FOLLOW_META_REDIRECTS = true;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var bool
     */
    private $followMetaRedirects = self::DEFAULT_FOLLOW_META_REDIRECTS;

    public function __construct(HttpClient $httpClient, bool $followMetaRedirects = self::DEFAULT_FOLLOW_META_REDIRECTS)
    {
        $this->httpClient = $httpClient;
        $this->setFollowMetaRedirects($followMetaRedirects);
    }

    public function setFollowMetaRedirects(bool $followMetaRedirects)
    {
        $this->followMetaRedirects = $followMetaRedirects;
    }

    /**
     * @param string $url
     *
     * @return string
     *
     * @throws GuzzleException
     */
    public function resolve(string $url): string
    {
        $request = new Request('GET', $url);
        $lastRequestUri = $request->getUri();

        try {
            $response = $this->httpClient->send($request, [
                'on_stats' => function (TransferStats $stats) use (&$requestUri) {
                    if ($stats->hasResponse()) {
                        $requestUri = $stats->getEffectiveUri();
                    }
                },
            ]);
        } catch (TooManyRedirectsException $tooManyRedirectsException) {
            return $requestUri;
        } catch (BadResponseException $badResponseException) {
            $response = $badResponseException->getResponse();
        }

        if ($this->followMetaRedirects) {
            $metaRedirectUrl = $this->getMetaRedirectUrlFromResponse($response, $lastRequestUri);

            if (!empty($metaRedirectUrl) && !$this->isLastResponseUrl($metaRedirectUrl, $lastRequestUri)) {
                return $this->resolve($metaRedirectUrl);
            }
        }

        return $requestUri;
    }

    private function isLastResponseUrl(string $url, UriInterface $lastRequestUri): bool
    {
        $lastResponseUrl = new NormalisedUrl($lastRequestUri);
        $comparator = new NormalisedUrl($url);

        return (string)$lastResponseUrl == (string)$comparator;
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * @param ResponseInterface $response
     * @param UriInterface $lastRequestUri
     *
     * @return string|null
     */
    private function getMetaRedirectUrlFromResponse(ResponseInterface $response, UriInterface $lastRequestUri)
    {
        /* @var WebPage $webPage */
        try {
            $webPage = Webpage::createFromResponse($lastRequestUri, $response);
        } catch (\Exception $exception) {
            return null;
        }

        $redirectUrl = null;
        $selector = 'meta[http-equiv=refresh]';

        /** @noinspection PhpUnhandledExceptionInspection */
        $inspector = new WebPageInspector($webPage);

        /* @var \DOMElement[] $metaRefreshElements */
        $metaRefreshElements = $inspector->querySelectorAll($selector);


        foreach ($metaRefreshElements as $metaRefreshElement) {
            if ($metaRefreshElement->hasAttribute('content')) {
                $contentAttribute = $metaRefreshElement->getAttribute('content');
                $urlMarkerPosition = stripos($contentAttribute, 'url=');

                if ($urlMarkerPosition !== false) {
                    $redirectUrl = substr($contentAttribute, $urlMarkerPosition + strlen('url='));
                }
            }
        }

        if (empty($redirectUrl)) {
            return null;
        }

        $absoluteUrlDeriver = new AbsoluteUrlDeriver($redirectUrl, $lastRequestUri);

        return (string) $absoluteUrlDeriver->getAbsoluteUrl();
    }
}

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
use webignition\Uri\Normalizer;
use webignition\Uri\Uri;
use webignition\WebPageInspector\WebPageInspector;
use webignition\WebResource\WebPage\WebPage;

class Resolver
{
    const DEFAULT_FOLLOW_META_REDIRECTS = true;

    private $httpClient;
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
     * @param UriInterface $uri
     *
     * @return UriInterface
     *
     * @throws GuzzleException
     */
    public function resolve(UriInterface $uri): UriInterface
    {
        $request = new Request('GET', $uri);
        $lastRequestUri = $request->getUri();
        $requestUri = new Uri('');

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
            $metaRedirectUri = $this->getMetaRedirectUriFromResponse($response, $lastRequestUri);

            if (!empty($metaRedirectUri) && !$this->isLastResponseUrl($metaRedirectUri, $lastRequestUri)) {
                return $this->resolve($metaRedirectUri);
            }
        }

        return $requestUri;
    }

    private function isLastResponseUrl(UriInterface $uri, UriInterface $lastRequestUri): bool
    {
        $uri = Normalizer::normalize($uri);
        $lastRequestUri = Normalizer::normalize($lastRequestUri);

        return (string) $lastRequestUri === (string) $uri;
    }

    /**
     * @param ResponseInterface $response
     * @param UriInterface $lastRequestUri
     *
     * @return UriInterface|null
     */
    private function getMetaRedirectUriFromResponse(
        ResponseInterface $response,
        UriInterface $lastRequestUri
    ): ?UriInterface {
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

        return AbsoluteUrlDeriver::derive($lastRequestUri, new Uri($redirectUrl));
    }
}

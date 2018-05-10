<?php

namespace webignition\Url\Resolver;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use QueryPath\Exception as QueryPathException;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\NormalisedUrl\NormalisedUrl;
use webignition\WebResource\Exception\InvalidContentTypeException;
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

    /**
     * @param HttpClient $httpClient
     * @param bool $followMetaRedirects
     */
    public function __construct(HttpClient $httpClient, $followMetaRedirects = self::DEFAULT_FOLLOW_META_REDIRECTS)
    {
        $this->httpClient = $httpClient;
        $this->setFollowMetaRedirects($followMetaRedirects);
    }

    /**
     * @param bool $followMetaRedirects
     */
    public function setFollowMetaRedirects($followMetaRedirects)
    {
        $this->followMetaRedirects = $followMetaRedirects;
    }

    /**
     * @param string $url
     *
     * @return string
     *
     * @throws QueryPathException
     * @throws GuzzleException
     */
    public function resolve($url)
    {
        return $this->resolveRequest(new Request('GET', $url));
    }

    /**
     * @param RequestInterface $request
     *
     * @return string
     *
     * @throws QueryPathException
     * @throws GuzzleException
     */
    private function resolveRequest(RequestInterface $request)
    {
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

    /**
     * @param string $url
     * @param UriInterface $lastRequestUri
     *
     * @return bool
     */
    private function isLastResponseUrl($url, UriInterface $lastRequestUri)
    {
        $lastResponseUrl = new NormalisedUrl($lastRequestUri);
        $comparator = new NormalisedUrl($url);

        return (string)$lastResponseUrl == (string)$comparator;
    }

    /**
     * @param ResponseInterface $response
     * @param UriInterface $lastRequestUri
     *
     * @return string|null
     */
    private function getMetaRedirectUrlFromResponse(ResponseInterface $response, UriInterface $lastRequestUri)
    {
        try {
            $webPage = new WebPage($response);
            if (empty($webPage)) {
                return null;
            }
        } catch (InvalidContentTypeException $invalidContentTypeException) {
            return null;
        } catch (InternetMediaTypeParseException $internetMediaTypeParseException) {
            return null;
        }

        $redirectUrl = null;
        $selector = 'meta[http-equiv=refresh]';

        try {
            $webPage->find($selector)->each(function ($index, \DOMElement $domElement) use (&$redirectUrl) {
                unset($index);

                if ($domElement->hasAttribute('content')) {
                    $contentAttribute = $domElement->getAttribute('content');
                    $urlMarkerPosition = stripos($contentAttribute, 'url=');

                    if ($urlMarkerPosition !== false) {
                        $redirectUrl = substr($contentAttribute, $urlMarkerPosition + strlen('url='));
                    }
                }
            });
        } catch (QueryPathException $queryPathException) {
            return $redirectUrl = null;
        }

        if (empty($redirectUrl)) {
            return null;
        }

        $absoluteUrlDeriver = new AbsoluteUrlDeriver($redirectUrl, $lastRequestUri);

        return (string)$absoluteUrlDeriver->getAbsoluteUrl();
    }
}

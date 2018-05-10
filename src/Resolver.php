<?php

namespace webignition\Url\Resolver;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Subscriber\History as HttpHistorySubscriber;
use QueryPath\Exception as QueryPathException;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\NormalisedUrl\NormalisedUrl;
use webignition\WebResource\Exception as WebResourceException;
use webignition\WebResource\WebPage\WebPage;

class Resolver
{
    const DEFAULT_FOLLOW_META_REDIRECTS = true;
    const DEFAULT_TIMEOUT_MS = 0;
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var bool
     */
    private $followMetaRedirects = self::DEFAULT_FOLLOW_META_REDIRECTS;

    /**
     * @var int
     */
    private $timeoutMs = self::DEFAULT_TIMEOUT_MS;

    /**
     * @var ResponseInterface
     */
    private $lastResponse = null;

    /**
     * @var HttpHistorySubscriber
     */
    private $httpHistorySubscriber;

    /**
     * @param HttpClient $httpClient
     * @param bool $followMetaRedirects
     * @param int $timeoutMs
     */
    public function __construct(
        HttpClient $httpClient,
        $followMetaRedirects = self::DEFAULT_FOLLOW_META_REDIRECTS,
        $timeoutMs = self::DEFAULT_TIMEOUT_MS
    ) {
        $this->httpClient = $httpClient;
        $this->setFollowMetaRedirects($followMetaRedirects);
        $this->setTimeoutMs($timeoutMs);
    }

    /**
     * @param bool $followMetaRedirects
     */
    public function setFollowMetaRedirects($followMetaRedirects)
    {
        $this->followMetaRedirects = $followMetaRedirects;
    }

    /**
     * @param int $timeoutMs
     */
    public function setTimeoutMs($timeoutMs)
    {
        $this->timeoutMs = $timeoutMs;
    }

    /**
     * @param string $url
     *
     * @return string
     *
     * @throws QueryPathException
     */
    public function resolve($url)
    {
        $requestOptions = [];

        if (!empty($this->timeoutMs)) {
            $requestOptions['timeout'] = $this->timeoutMs / 1000;
        }

        $httpClient = $this->httpClient;
        $request = $httpClient->createRequest('GET', $url, $requestOptions);

        return $this->resolveRequest($request);
    }

    /**
     * @param RequestInterface $request
     *
     * @return string
     *
     * @throws QueryPathException
     */
    private function resolveRequest(RequestInterface $request)
    {
        $httpClient = $this->httpClient;

        try {
            $this->lastResponse = $httpClient->send($request);
        } catch (TooManyRedirectsException $tooManyRedirectsException) {
            $httpHistory = $this->getRequestHistory();

            if (!empty($httpHistory)) {
                $this->lastResponse = $httpHistory->getLastResponse();
            } else {
                return $request->getUrl();
            }
        } catch (BadResponseException $badResponseException) {
            $this->lastResponse = $badResponseException->getResponse();
        }

        if ($this->followMetaRedirects) {
            $metaRedirectUrl = $this->getMetaRedirectUrlFromLastResponse();

            if (!is_null($metaRedirectUrl) && !$this->isLastResponseUrl($metaRedirectUrl)) {
                return $this->resolve($metaRedirectUrl);
            }
        }

        return $this->lastResponse->getEffectiveUrl();
    }

    /**
     * @return HttpHistorySubscriber
     */
    private function getRequestHistory()
    {
        if (empty($this->httpHistorySubscriber)) {
            $httpClient = $this->httpClient;
            $completeListenersCollection = $httpClient->getEmitter()->listeners('complete');

            if (!empty($completeListenersCollection)) {
                $completeListeners = $completeListenersCollection[0];

                foreach ($completeListeners as $listener) {
                    if ($listener instanceof HttpHistorySubscriber) {
                        $this->httpHistorySubscriber = $listener;
                    }
                }
            }
        }

        return $this->httpHistorySubscriber;
    }


    /**
     * @param string $url
     *
     * @return bool
     */
    private function isLastResponseUrl($url)
    {
        $lastResponseUrl = new NormalisedUrl($this->lastResponse->getEffectiveUrl());
        $comparator = new NormalisedUrl($url);

        return (string)$lastResponseUrl == (string)$comparator;
    }

    /**
     * @return string|null
     *
     * @throws QueryPathException
     */
    private function getMetaRedirectUrlFromLastResponse()
    {
        $webPage = $this->getWebPageFromLastResponse();
        if (empty($webPage)) {
            return null;
        }

        $redirectUrl = null;
        $selector = 'meta[http-equiv=refresh]';

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

        if (empty($redirectUrl)) {
            return null;
        }

        $absoluteUrlDeriver = new AbsoluteUrlDeriver($redirectUrl, $this->lastResponse->getEffectiveUrl());

        return (string)$absoluteUrlDeriver->getAbsoluteUrl();
    }

    /**
     * @return null|WebPage
     */
    private function getWebPageFromLastResponse()
    {
        if (!$this->lastResponse->hasHeader('Content-Type')) {
            return null;
        }

        try {
            $webPage = new WebPage();
            $webPage->setHttpResponse($this->lastResponse);
            return $webPage;
        } catch (WebResourceException $webResourceException) {
            return null;
        }
    }
}

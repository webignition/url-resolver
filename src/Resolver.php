<?php
namespace webignition\Url\Resolver;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Subscriber\History as HttpHistorySubscriber;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\NormalisedUrl\NormalisedUrl;
use webignition\WebResource\Exception as WebResourceException;
use webignition\WebResource\WebPage\WebPage;

class Resolver
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var ResponseInterface
     */
    private $lastResponse = null;

    /**
     * @var HttpHistorySubscriber
     */
    private $httpHistorySubscriber;

    /**
     * @var bool
     */
    private $hasTriedWithUrlEncodingDisabled = false;

    /**
     * @param Configuration|null $configuration
     */
    public function __construct($configuration = null)
    {
        if (empty($configuration)) {
            $configuration = new Configuration();
        }

        $this->configuration = $configuration;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function resolve($url)
    {
        $httpClient = $this->configuration->getHttpClient();
        $request = $httpClient->createRequest('GET', $url);

        return $this->resolveRequest($request);
    }

    /**
     * @param RequestInterface $request
     *
     * @return string
     */
    private function resolveRequest(RequestInterface $request)
    {
        $httpClient = $this->configuration->getHttpClient();

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
            if ($this->configuration->getRetryWithUrlEncodingDisabled() && !$this->hasTriedWithUrlEncodingDisabled) {
                $this->hasTriedWithUrlEncodingDisabled = true;

                return $this->resolveRequest($this->deEncodeRequestUrl($request));
            } else {
                $this->lastResponse = $badResponseException->getResponse();
            }
        }

        $this->hasTriedWithUrlEncodingDisabled = false;

        if ($this->configuration->getFollowMetaRedirects()) {
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
            $httpClient = $this->configuration->getHttpClient();
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
     * @param RequestInterface $request
     *
     * @return RequestInterface
     */
    private function deEncodeRequestUrl(RequestInterface $request)
    {
        $request->getQuery()->setEncodingType(false);

        return $request;
    }

    /**
     * @return string|null
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

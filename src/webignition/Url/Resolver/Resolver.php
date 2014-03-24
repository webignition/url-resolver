<?php
namespace webignition\Url\Resolver;

use webignition\Url\Resolver\Configuration\Configuration;

/**
 * 
 */
class Resolver {
    
    /**
     *
     * @var Configuration
     */
    private $configuration;
    
    
    /**
     *
     * @var \Guzzle\Http\Message\Response
     */
    private $lastResponse = null;

    

    /**
     * 
     * @return \webignition\Url\Resolver\Configuration\Configuration
     */
    public function getConfiguration() {
        if (is_null($this->configuration)) {
            $this->configuration = new Configuration();
        }
        
        return $this->configuration;
    }    
    
    
    /**
     * 
     * @return boolean
     */
    public function hasConfiguration() {
        return !is_null($this->getConfiguration());
    }
    
    
    /**
     * 
     * @param string $url
     * @return string
     */
    public function resolve($url) {
        $request = clone $this->getConfiguration()->getBaseRequest();
        $request->setUrl($url);
        
        $this->setRequestCookies($request);
        
        return $this->resolveRequest($request);
    }
    
    
    private function setRequestCookies(\Guzzle\Http\Message\Request $request) {
        if (!is_null($request->getCookies())) {
            foreach ($request->getCookies() as $name => $value) {
                $request->removeCookie($name);
            }
        }
        
        
        $cookieUrlMatcher = new \webignition\Cookie\UrlMatcher\UrlMatcher();
        
        foreach ($this->getConfiguration()->getCookies() as $cookie) {
            if ($cookieUrlMatcher->isMatch($cookie, $request->getUrl())) {
                $request->addCookie($cookie['name'], $cookie['value']);
            }
        } 
    }    
    
    
    private function resolveRequest(\Guzzle\Http\Message\Request $request) {        
        try {
            $this->lastResponse = $request->send();
        } catch (\Guzzle\Http\Exception\TooManyRedirectsException $tooManyRedirectsException) {            
            if ($this->hasRequestHistory($request)) {
                $this->lastResponse = $this->getRequestHistory($request)->getLastResponse(); 
            } else {
                return $request->getUrl();
            }                      
        } catch (\Guzzle\Http\Exception\BadResponseException $badResponseException) {                                    
            if ($this->getConfiguration()->getRetryWithUrlEncodingDisabled() && !$this->getConfiguration()->getHasRetriedWithUrlEncodingDisabled()) {
                $this->getConfiguration()->setHasRetriedWithUrlEncodingDisabled(true);
                return $this->resolveRequest($this->deEncodeRequestUrl($request));
            } else {
                $this->lastResponse = $badResponseException->getResponse();
            }            
        }
        
        if ($this->getConfiguration()->getHasRetriedWithUrlEncodingDisabled()) {
            $this->getConfiguration()->setHasRetriedWithUrlEncodingDisabled(false);
        }
        
        if ($this->getConfiguration()->getFollowMetaRedirects()) {
            $metaRedirectUrl = $this->getMetaRedirectUrlFromLastResponse();
            if (!is_null($metaRedirectUrl) && !$this->isLastResponseUrl($metaRedirectUrl)) {
                return $this->resolve($metaRedirectUrl);
            }
        }
        
        return $this->lastResponse->getEffectiveUrl();        
    }
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Request $request
     * @return boolean
     */
    private function hasRequestHistory(\Guzzle\Http\Message\Request $request) {
        return !is_null($this->getRequestHistory($request));
    }
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Request $request
     * @return \Guzzle\Plugin\History\HistoryPlugin|null
     */
    private function getRequestHistory(\Guzzle\Http\Message\Request $request) {
        $requestSentListeners = $request->getClient()->getEventDispatcher()->getListeners('request.sent');
        
        foreach ($requestSentListeners as $requestSentListener) {
            if ($requestSentListener[0] instanceof \Guzzle\Plugin\History\HistoryPlugin) {
                return $requestSentListener[0];
            }
        }            
        
        return null;
    }
    
    
    /**
     * 
     * @param string $url
     * @return boolean
     */
    public function isLastResponseUrl($url) {
        $lastResponseUrl = new \webignition\NormalisedUrl\NormalisedUrl($this->getLastResponse()->getEffectiveUrl());
        $comparator = new \webignition\NormalisedUrl\NormalisedUrl($url);
        
        return (string)$lastResponseUrl == (string)$comparator;
    }
    
    
    /**
     * 
     * @return \Guzzle\Http\Message\Response
     */
    public function getLastResponse() {
        return $this->lastResponse;
    }
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Request $request
     * @return \Guzzle\Http\Message\Request
     */
    private function deEncodeRequestUrl(\Guzzle\Http\Message\Request $request) {
        // Intentionally not a one-liner to make the process easier to understand
        $requestUrl = $request->getUrl(true);
        $requestQuery = $requestUrl->getQuery(true);
        $requestQuery->useUrlEncoding(false);

        $requestUrl->setQuery($requestQuery);
        $request->setUrl($requestUrl);

        return $request;
      
    }
    
    
    private function getMetaRedirectUrlFromLastResponse() {
        $webPage = $this->getWebPageFromLastResponse();
        
        if (is_null($webPage)) {
            return null;
        }
        
        $redirectUrl = null;
        
        $webPage->find('meta[http-equiv=refresh]')->each(function ($index, \DOMElement $domElement) use (&$redirectUrl) {                       
            if ($domElement->hasAttribute('content')) {
                $contentAttribute = $domElement->getAttribute('content');                
                $urlMarkerPosition = stripos($contentAttribute, 'url=');
                
                if ($urlMarkerPosition !== false) {
                    $redirectUrl = substr($contentAttribute, $urlMarkerPosition + strlen('url='));
                }
            }
        });
        
        if (is_null($redirectUrl)) {
            return null;
        }
        
        $absoluteUrlDeriver = new \webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver($redirectUrl, $this->getLastResponse()->getEffectiveUrl());
        return (string)$absoluteUrlDeriver->getAbsoluteUrl();   
    }
    
    
    private function getWebPageFromLastResponse() {        
        if (!$this->getLastResponse()->hasHeader('Content-Type')) {
            return null;
        }
        
        try {
            $webPage = new \webignition\WebResource\WebPage\WebPage;
            $webPage->setContentType($this->getLastResponse()->getHeader('Content-Type'));
            $webPage->setContent($this->getLastResponse()->getBody(true));
            return $webPage;
        } catch (\webignition\WebResource\Exception $webResourceException) {
            return null;
        }        
        
        return null;
    }
    
}
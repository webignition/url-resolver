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
        
        try {
            $this->lastResponse = $request->send();
        } catch (\Guzzle\Http\Exception\BadResponseException $badResponseException) {                                    
            if ($this->getConfiguration()->getRetryWithUrlEncodingDisabled() && !$this->getConfiguration()->getHasRetriedWithUrlEncodingDisabled()) {
                $this->getConfiguration()->setHasRetriedWithUrlEncodingDisabled(true);
                $this->lastResponse = $this->deEncodeRequestUrl($request)->send();             
            } else {
                $this->lastResponse = $badResponseException->getResponse();
            }            
        }
        
        if ($this->getConfiguration()->getHasRetriedWithUrlEncodingDisabled()) {
            $this->getConfiguration()->setHasRetriedWithUrlEncodingDisabled(false);
        }
        
        return $this->lastResponse->getEffectiveUrl();
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
    
}
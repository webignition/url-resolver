<?php
namespace webignition\Url\Resolver\Configuration;

/**
 * 
 */
class Configuration {
    
    /**
     *
     * @var \Guzzle\Http\Message\Request
     */
    private $baseRequest = null;    
    
    
    /**
     *
     * @var boolean
     */
    private $followMetaRedirects = true;    
    
    
    /**
     *
     * @var boolean
     */
    private $retryWithUrlEncodingDisabled = false;   
    
    
    /**
     *
     * @var boolean
     */
    private $hasTriedWithUrlEncodingDisabled = false; 
    
    
    /**
     *
     * @var array
     */
    private $cookies = array();      
    
    
    /**
     * 
     * @return \webignition\Url\Resolver\Configuration\Configuration
     */
    public function enableFollowMetaRedirects() {
        $this->followMetaRedirects = true;
        return $this;
    }
    
    
    /**
     * 
     * @return \webignition\Url\Resolver\Configuration\Configuration
     */
    public function disableFollowMetaRedirects() {
        $this->followMetaRedirects = false;
        return $this;
    }  
    
    
    /**
     * 
     * @return boolean
     */
    public function getFollowMetaRedirects() {
        return $this->followMetaRedirects;
    }
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Request $request
     * @return \webignition\Url\Resolver\Configuration\Configuration
     */
    public function setBaseRequest(\Guzzle\Http\Message\Request $request) {
        $this->baseRequest = $request;
        return $this;
    }    
    
    
    /**
     * 
     * @return \Guzzle\Http\Message\Request $request
     */
    public function getBaseRequest() {
        if (is_null($this->baseRequest)) {
            $client = new \Guzzle\Http\Client;            
            $this->baseRequest = $client->get();
        }
        
        return $this->baseRequest;
    } 
    
    
    /**
     * 
     * @return \webignition\Url\Resolver\Configuration\Configuration
     */
    public function enableRetryWithUrlEncodingDisabled() {
        $this->retryWithUrlEncodingDisabled = true;
        return $this;
    }
    
    
    /**
     * 
     * @return \webignition\Url\Resolver\Configuration\Configuration
     */
    public function disableRetryWithUrlEncodingDisabled() {
        $this->retryWithUrlEncodingDisabled = false;
        return $this;
    } 
    
    
    /**
     * 
     * @return boolean
     */
    public function getRetryWithUrlEncodingDisabled() {
        return $this->retryWithUrlEncodingDisabled;
    }    
    
    
    /**
     * 
     * @param boolean $hasRetried
     * @return \webignition\Url\Resolver\Configuration\Configuration
     */
    public function setHasRetriedWithUrlEncodingDisabled($hasRetried) {
        $this->hasTriedWithUrlEncodingDisabled = $hasRetried;
        return $this;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function getHasRetriedWithUrlEncodingDisabled() {
        return $this->hasTriedWithUrlEncodingDisabled;
    }    
    
    
    /**
     * 
     * @param array $cookies
     * @return \webignition\Url\Resolver\Configuration\Configuration
     */
    public function setCookies($cookies) {
        $this->cookies = $cookies;
        return $this;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getCookies() {
        return $this->cookies;
    }      

}
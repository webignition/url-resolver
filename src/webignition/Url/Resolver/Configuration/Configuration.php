<?php
namespace webignition\Url\Resolver\Configuration;

/**
 * 
 */
class Configuration {
    
    /**
     *
     * @var boolean
     */
    private $followMetaRedirects = true;
    
    
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

}
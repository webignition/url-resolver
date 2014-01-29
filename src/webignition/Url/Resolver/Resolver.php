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
        return $url;
    }
    
}
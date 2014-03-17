<?php

namespace webignition\Tests\Url\Resolver\Resolve\CookiesTest;

class SecureTest extends CookieIsSetTest { 
    
    protected function getCookies() {
        return array(
            array(
                'domain' => '.example.com',
                'secure' => true,
                'name' => 'name1',
                'value' => 'value1'
            )                       
        );         
    }
    
}
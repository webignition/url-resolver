<?php

namespace webignition\Tests\Url\Resolver\Resolve\CookiesTest;

class DomainTest extends CookieIsSetTest { 
    
    protected function getCookies() {
        return array(
            array(
                'domain' => '.example.com',
                'name' => 'name1',
                'value' => 'value1'
            )                       
        );         
    }
    
}
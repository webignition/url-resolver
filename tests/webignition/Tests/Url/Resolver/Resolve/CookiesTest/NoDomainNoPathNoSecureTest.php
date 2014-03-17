<?php

namespace webignition\Tests\Url\Resolver\Resolve\CookiesTest;

class NoDomainNoPathNoSecureTest extends CookieNotSetTest { 
    
    protected function getCookies() {
        return array(
            array(
                'name' => 'name1',
                'value' => 'value1'
            )                       
        );         
    }
    
}
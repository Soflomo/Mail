<?php
/**
 * Copyright (c) 2012-2014 Soflomo.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of the
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author      Jurian Sluiman <jurian@soflomo.com>
 * @copyright   2012-2014 Soflomo.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 */

namespace SoflomoTest\Mail\Service;

use PHPUnit_Framework_TestCase as TestCase;
use SoflomoTest\Mail\Util\ServiceManagerFactory;

class DefaultMessageFactoryTest extends TestCase
{
    public function testFactoryUsesAliasToDefaultMessage()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setInvokableClass('Soflomo\Mail\DefaultMessage', 'Zend\Mail\Message');
        $serviceManager->setAllowOverride(false);
        $transport      = $serviceManager->get('Soflomo\Mail\Message');

        $this->assertInstanceOf('Zend\Mail\Message', $transport);
    }

    public function testFactoryCreatesMessage()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();
        $message        = $serviceManager->get('Soflomo\Mail\DefaultMessage');

        $this->assertInstanceOf('Zend\Mail\Message', $message);
    }

    public function testFactorySetsEncodingFromConfig()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();

        $config = $serviceManager->get('config');
        $config['soflomo_mail']['message']['encoding'] = 'Foo';
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('config', $config);
        $serviceManager->setAllowOverride(false);

        $message = $serviceManager->get('Soflomo\Mail\Message');
        $this->assertEquals('Foo', $message->getEncoding());
    }

    public function testFactorySetsFromAddressFromConfig()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();

        $config = $serviceManager->get('config');
        $config['soflomo_mail']['message']['from'] = 'bob@acme.org';
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('config', $config);
        $serviceManager->setAllowOverride(false);

        $message = $serviceManager->get('Soflomo\Mail\Message');
        $this->assertEquals('bob@acme.org', $message->getFrom()->current()->getEmail());
    }

    public function testFactorySetsFromNameFromConfig()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();

        $config = $serviceManager->get('config');
        $config['soflomo_mail']['message']['from']      = 'bob@acme.org';
        $config['soflomo_mail']['message']['from_name'] = 'Bob';
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('config', $config);
        $serviceManager->setAllowOverride(false);

        $message = $serviceManager->get('Soflomo\Mail\Message');
        $this->assertEquals('bob@acme.org', $message->getFrom()->current()->getEmail());
        $this->assertEquals('Bob', $message->getFrom()->current()->getName());
    }
}

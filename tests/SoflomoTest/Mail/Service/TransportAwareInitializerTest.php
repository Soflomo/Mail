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
use Soflomo\Mail\Service\TransportAwareInitializer;
use SoflomoTest\Mail\Util\ServiceManagerFactory;

class TransportAwareInitializerTest extends TestCase
{
    protected $serviceManager;
    protected $initializer;

    public function setUp()
    {
        $this->serviceManager = ServiceManagerFactory::getServiceManager();
        $this->initializer    = new TransportAwareInitializer;
        $this->serviceManager->addInitializer($this->initializer);
        $this->serviceManager->setInvokableClass('TestService', 'SoflomoTest\Mail\Asset\TransportAwareService');

        // Set the transport to be specifically the simple asset transport
        $this->serviceManager->setAllowOverride(true);
        $this->serviceManager->setInvokableClass('Soflomo\Mail\Transport', 'SoflomoTest\Mail\Asset\SimpleTransport');
        $this->serviceManager->setAllowOverride(false);
    }

    public function testInitializerInjectsTransport()
    {
        $instance = $this->serviceManager->get('TestService');
        $this->assertInstanceOf('Soflomo\Mail\Mail\TransportAwareInterface', $instance);

        $transport = $instance->getLastTransport();
        $this->assertInstanceOf('Zend\Mail\Transport\TransportInterface', $transport);
    }

    public function testInitializerUsesSharedTransport()
    {
        $this->serviceManager->setInvokableClass('TestService2', 'SoflomoTest\Mail\Asset\TransportAwareService');

        $instance1 = $this->serviceManager->get('TestService');
        $transport1  = $instance1->getLastTransport();

        $instance2 = $this->serviceManager->get('TestService2');
        $transport2  = $instance2->getLastTransport();

        $this->assertInstanceOf('Zend\Mail\Transport\TransportInterface', $transport1);
        $this->assertInstanceOf('Zend\Mail\Transport\TransportInterface', $transport2);

        $this->assertEquals(spl_object_hash($transport1), spl_object_hash($transport2));
    }
}

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
use Soflomo\Mail\Service\MailService;
use SoflomoTest\Mail\Util\ServiceManagerFactory;
use SoflomoTest\Mail\Util\TestTransport;
use Zend\Mail\Message;

class MailServiceTest extends TestCase
{
    protected $renderer;
    protected $transport;
    protected $defaultOptions;

    public function setUp()
    {
        $this->renderer  = $this->getMock('Zend\View\Renderer\RendererInterface');
        $this->transport = new TestTransport;
        $this->defaultOptions = array(
            'to'       => 'john@acme.org',
            'subject'  => 'This is a test',
            'template' => 'foo/bar/baz'
        );
    }

    public function testCanCreateInstance()
    {
        $service = new MailService($this->transport, $this->renderer);

        $this->assertInstanceOf('Soflomo\Mail\Service\MailService', $service);
    }

    public function testServiceSendsMessageWithTransport()
    {
        $service = new MailService($this->transport, $this->renderer);
        $service->send($this->defaultOptions);

        $message = $this->transport->getLastMessage();
        $this->assertInstanceOf('Zend\Mail\Message', $message);
    }

    public function testClonesDefaultMessageFromConstructor()
    {
        $defaultMessage = new Message;
        $defaultMessage->setFrom('alice@acme.org', 'Alice');
        $service        = new MailService($this->transport, $this->renderer, $defaultMessage);
        $service->send($this->defaultOptions);

        $message = $this->transport->getLastMessage();

        // We compare the equality of objects, since they should not be the same instance
        $this->assertEquals($defaultMessage, $message);

        $equals = (spl_object_hash($defaultMessage) === spl_object_hash($message));
        $this->assertFalse($equals);
    }

    public function testUsesDefaultMessageFromSendMethod()
    {
        $defaultMessage = new Message;
        $service        = new MailService($this->transport, $this->renderer);
        $service->send($this->defaultOptions, array(), $defaultMessage);

        $message = $this->transport->getLastMessage();
        $this->assertEquals(spl_object_hash($defaultMessage), spl_object_hash($message));
    }

    public function testServiceSetsMessageToAddress()
    {
        $service = new MailService($this->transport, $this->renderer);
        $service->send($this->defaultOptions);

        $message = $this->transport->getLastMessage();
        $this->assertEquals('john@acme.org', $message->getTo()->current()->getEmail());

        $service->send($this->defaultOptions + array(
            'to_name' => 'John Doe',
        ));

        $message = $this->transport->getLastMessage();
        $this->assertEquals('john@acme.org', $message->getTo()->current()->getEmail());
        $this->assertEquals('John Doe', $message->getTo()->current()->getName());
    }

    public function testServiceHandlesMultipleToAddresses()
    {
        $service = new MailService($this->transport, $this->renderer);
        $service->send(array(
            'to' => array('bob@acme.org' => 'Bob', 'alice@acme.org' => 'Alice'),
            'subject'  => 'This is a test',
            'template' => 'foo/bar/baz'
        ));

        $message = $this->transport->getLastMessage();
        $this->assertEquals(2, $message->getTo()->count());

        $address1 = $message->getTo()->rewind();
        $this->assertEquals('bob@acme.org', $address1->getEmail());
        $this->assertEquals('Bob', $address1->getName());

        $address2 = $message->getTo()->next();
        $this->assertEquals('alice@acme.org', $address2->getEmail());
        $this->assertEquals('Alice', $address2->getName());
    }

    public function testServiceSetsSubjectLineToMessage()
    {
        $service = new MailService($this->transport, $this->renderer);
        $service->send($this->defaultOptions);

        $message = $this->transport->getLastMessage();
        $this->assertEquals('This is a test', $message->getSubject());
    }

    public function testServiceRendersTemplateByRenderer()
    {
        $this->renderer->expects($this->once())
                       ->method('render')
                       ->with('foo/bar/baz')
                       ->will($this->returnValue('Hello World'));

        $service = new MailService($this->transport, $this->renderer);
        $service->send($this->defaultOptions);

        $message = $this->transport->getLastMessage();
        $this->assertContains('Hello World', $message->getBody());
    }
}

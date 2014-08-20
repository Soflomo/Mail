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
use SoflomoTest\Mail\Asset\SimpleTransport;
use Zend\Mail\Message;

class MailServiceTest extends TestCase
{
    protected $renderer;
    protected $transport;
    protected $defaultOptions;

    public function setUp()
    {
        $this->renderer  = $this->getMock('Zend\View\Renderer\RendererInterface');
        $this->transport = new SimpleTransport;
        $this->defaultOptions = array(
            'to'       => 'john@acme.org',
            'subject'  => 'This is a test',
            'template' => 'foo/bar/baz'
        );
        $this->service = new MailService($this->transport, $this->renderer);
    }

    public function testCanCreateInstance()
    {
        $service = $this->service;
        $this->assertInstanceOf('Soflomo\Mail\Service\MailService', $service);
    }

    public function testServiceSendsMessageWithTransport()
    {
        $service = $this->service;
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
        $service = $this->service;
        $service->send($this->defaultOptions, array(), $defaultMessage);

        $message = $this->transport->getLastMessage();
        $this->assertEquals(spl_object_hash($defaultMessage), spl_object_hash($message));
    }

    public function testServiceRequiresToOption()
    {
        $this->setExpectedException('Soflomo\Mail\Exception\InvalidArgumentException');

        $service = $this->service;
        $options = $this->defaultOptions;
        unset($options['to']);
        $service->send($options);
    }

    /**
     * @todo Test from/to/cc/bcc address
     */
    public function testServiceSetsMessageToAddress()
    {
        $service = $this->service;
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

    /**
     * @todo Test from/to/cc/bcc addresses
     */
    public function testServiceHandlesMultipleToAddresses()
    {
        $service = $this->service;
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

    public function testServiceRequiresSubjectOption()
    {
        $this->setExpectedException('Soflomo\Mail\Exception\InvalidArgumentException');

        $service = $this->service;
        $options = $this->defaultOptions;
        unset($options['subject']);
        $service->send($options);
    }

    public function testServiceSetsSubjectLineToMessage()
    {
        $service = $this->service;
        $service->send($this->defaultOptions);

        $message = $this->transport->getLastMessage();
        $this->assertEquals('This is a test', $message->getSubject());
    }

    public function testServiceRequiresTemplateOption()
    {
        $this->setExpectedException('Soflomo\Mail\Exception\InvalidArgumentException');

        $service = $this->service;
        $options = $this->defaultOptions;
        unset($options['template']);
        $service->send($options);
    }

    public function testServiceRendersTemplateByRenderer()
    {
        $this->renderer->expects($this->once())
                       ->method('render')
                       ->with('foo/bar/baz')
                       ->will($this->returnValue('Hello World'));

        $service = $this->service;
        $service->send($this->defaultOptions);

        $message = $this->transport->getLastMessage();
        $this->assertContains('Hello World', $message->getBody());
    }

    public function testServiceCreatesMultiMimeMessageForTextAndHtml()
    {
        $this->renderer->expects($this->at(0))
                       ->method('render')
                       ->with('foo/bar/baz')
                       ->will($this->returnValue('<p>Hello World</p>'));

        $this->renderer->expects($this->at(1))
                       ->method('render')
                       ->with('foo/bar/baz_text')
                       ->will($this->returnValue('Hello World'));

        $service = $this->service;
        $service->send($this->defaultOptions + array('template_text' => 'foo/bar/baz_text'));

        $message = $this->transport->getLastMessage();
        $body    = $message->getBody();
        $this->assertInstanceOf('Zend\Mime\Message', $body);

        $parts = $body->getParts();
        $text  = $parts[0];
        $html  = $parts[1];

        $this->assertInstanceOf('Zend\Mime\Part', $text);
        $this->assertInstanceOf('Zend\Mime\Part', $html);

        $this->assertEquals('text/plain', $text->type);
        $this->assertEquals('text/html', $html->type);

        $this->assertEquals('Hello World', $text->getRawContent());
        $this->assertEquals('<p>Hello World</p>', $html->getRawContent());
    }

    public function testServiceAddsCustomHeader()
    {
        $service = $this->service;
        $service->send($this->defaultOptions + array(
            'headers' => array(
                'X-Foo' => 'Bar',
            ),
        ));

        $message = $this->transport->getLastMessage();
        $headers = $message->getHeaders();

        $this->assertTrue($headers->has('X-Foo'));
        $this->assertEquals('Bar', $headers->get('X-Foo')->getFieldValue());
    }

    public function testServiceRequiresHeadersToBeAnArray()
    {
        $this->setExpectedException('Soflomo\Mail\Exception\InvalidArgumentException');

        $service = $this->service;
        $service->send($this->defaultOptions + array('headers' => 'string'));
    }
}

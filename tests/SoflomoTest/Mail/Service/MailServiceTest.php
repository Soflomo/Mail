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
     * @dataProvider singleAddressProvider
     */
    public function testServiceSetsMessageToAddress($options, $method, $expectedEmail, $expectedName)
    {
        $service = $this->service;
        $service->send($this->defaultOptions + $options);

        $message = $this->transport->getLastMessage();
        $this->assertEquals($expectedEmail, $message->$method()->current()->getEmail());

        if (null === $expectedName) {
            return;
        }

        $service->send($this->defaultOptions + $options);

        $message = $this->transport->getLastMessage();
        $this->assertEquals($expectedEmail, $message->$method()->current()->getEmail());
        $this->assertEquals($expectedName, $message->$method()->current()->getName());
    }

    /**
     * @dataProvider multipleAddressProvider
     */
    public function testServiceHandlesMultipleToAddresses($options, $method, $expectedEmail1, $expectedEmail2, $expectedName1, $expectedName2)
    {
        $service = $this->service;
        $service->send($options + $this->defaultOptions);

        $message = $this->transport->getLastMessage();
        $this->assertEquals(2, $message->$method()->count());

        $address1 = $message->$method()->rewind();
        $this->assertEquals($expectedEmail1, $address1->getEmail());
        $this->assertEquals($expectedName1, $address1->getName());

        $address2 = $message->$method()->next();
        $this->assertEquals($expectedEmail2, $address2->getEmail());
        $this->assertEquals($expectedName2, $address2->getName());
    }

    public function singleAddressProvider()
    {
        return array(
            array(array('to' => 'john@acme.org'), 'getTo', 'john@acme.org', null),
            array(array('to' => 'john@acme.org', 'to_name' => 'John Doe'), 'getTo', 'john@acme.org', 'John Doe'),

            array(array('cc' => 'alice@acme.org'), 'getCc', 'alice@acme.org', null),
            array(array('cc' => 'alice@acme.org', 'cc_name' => 'Alice Dane'), 'getCc', 'alice@acme.org', 'Alice Dane'),

            array(array('bcc' => 'alice@acme.org'), 'getBcc', 'alice@acme.org', null),
            array(array('bcc' => 'alice@acme.org', 'bcc_name' => 'Alice Dane'), 'getBcc', 'alice@acme.org', 'Alice Dane'),

            array(array('from' => 'alice@acme.orgg'), 'getFrom', 'alice@acme.orgg', null),
            array(array('from' => 'alice@acme.orgg', 'from_name' => 'Alice Dane'), 'getFrom', 'alice@acme.orgg', 'Alice Dane'),

            array(array('reply_to' => 'alice@acme.orgg'), 'getReplyTo', 'alice@acme.orgg', null),
            array(array('reply_to' => 'alice@acme.orgg', 'reply_to_name' => 'Alice Dane'), 'getReplyTo', 'alice@acme.orgg', 'Alice Dane'),
        );
    }

    public function multipleAddressProvider()
    {
        return array(
            array(array('to' => array('bob@acme.org' => 'Bob', 'alice@acme.org' => 'Alice')), 'getTo', 'bob@acme.org', 'alice@acme.org', 'Bob', 'Alice'),
            array(array('cc' => array('bob@acme.org' => 'Bob', 'alice@acme.org' => 'Alice')), 'getCc', 'bob@acme.org', 'alice@acme.org', 'Bob', 'Alice'),
            array(array('bcc' => array('bob@acme.org' => 'Bob', 'alice@acme.org' => 'Alice')), 'getBcc', 'bob@acme.org', 'alice@acme.org', 'Bob', 'Alice'),
            array(array('reply_to' => array('bob@acme.org' => 'Bob', 'alice@acme.org' => 'Alice')), 'getReplyTo', 'bob@acme.org', 'alice@acme.org', 'Bob', 'Alice'),
        );
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

    public function testServiceThrowsExceptionForUnimplementedAttachment()
    {
        $this->setExpectedException('Soflomo\Mail\Exception\NotImplementedException');

        $service = $this->service;
        $service->send($this->defaultOptions + array('attachments' => array()));
    }
}

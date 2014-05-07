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

namespace Soflomo\Mail\Service;

use Soflomo\Mail\Exception\InvalidArgumentException;
use Soflomo\Mail\Exception\RuntimeException;

use Zend\Mime\Part    as MimePart;
use Zend\Mime\Message as MimeMessage;

use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;
use Zend\View\Renderer\RendererInterface;

/**
 * Mail service class
 *
 * The mail service simplifies the usage of Zend\Mail by a facade
 * for setting message variables, rendering the body based on a
 * template and then send the mail with the given transport.
 */
class MailService
{
	protected $transport;
	protected $renderer;
	protected $defaultMessage;

	/**
	 * Constructor
	 *
	 * @param  TransportInterface $transport
	 * @param  RendererInterface $renderer
	 * @param  Message|null $defaultMessage
	 * @return MailService
	 */
	public function __construct(TransportInterface $transport, RendererInterface $renderer, Message $defaultMessage = null)
	{
		$this->transport = $transport;
		$this->renderer  = $renderer;

		if (null !== $defaultMessage) {
			$this->defaultMessage = $defaultMessage;
		}
	}

	/**
	 * Send a message with given variables for the body
	 *
	 * If no message object is set a default message object is
	 * used. In the options array at least a "to", "subject" and
	 * "template" key must be available to send the message.
	 *
	 * Valid options are:
	 * - to:              the email address to send the message to (required)
	 * - subject:         the subject of the message (required)
	 * - template:        the view name of the (html) template (required)
	 * - to_name:         the name of the user to send to
	 * - cc:              an email address to send a cc to
	 * - cc_name:         the name of the user to cc
	 * - bcc:             an email address to send a bcc to
	 * - bcc_name:        the name of the user to bcc
	 * - from:            the email address the message came from
	 * - from_name:       the name of the user from the from address
	 * - reply_to:        the email address to reply to
	 * - reply_to_name:   the name of the user from the reply to address
	 * - template_text:   the plain text version of the template
	 * - attachments:     an array of attachments (not implemented currently)
	 * - headers:         a key/value array of additional headers to set
	 *
	 * All address fields (to, cc, bcc, from, reply_to) can also be an array with
	 * key/value pairs of multiple addresses. All keys in the array are considered
	 * email addresses, all values (null allowed) are the corresponding names.
	 *
	 * @param  array $options   Additional options to set
	 * @param  array $variables Variables to use for the view
	 * @param  Message|null $message Optional message object to use
	 * @return void
	 */
	public function send(array $options, array $variables, Message $message = null)
	{
		if (null === $message) {
			$message = $this->getDefaultMessage();
		}

		$this->prepareMessage($message, $options);
		$this->renderBody($message, $options,$variables);

		if (array_key_exists('attachments', $options)) {
			$this->addAttachments($message, $options);
		}
		if (array_key_exists('headers', $options)) {
			$this->addCustomHeaders($message, $options);
		}

		$this->sendMessage($message);
	}

	/**
	 * Set all the basic message fields
	 *
	 * @param  Message $message
	 * @param  array $options
	 * @return void
	 */
	protected function prepareMessage(Message $message, array $options)
	{
		if (!array_key_exists('to', $options)) {
			throw new InvalidArgumentException('"to" parameter is missing from options');
		}
		if (!array_key_exists('subject', $options)) {
			throw new InvalidArgumentException('"subject" parameter is missing from options');
		}

		$message->setSubject($options['subject']);

		$this->addAddress($message, $options, 'to');
		$this->addAddress($message, $options, 'cc');
		$this->addAddress($message, $options, 'bcc');
		$this->addAddress($message, $options, 'from');
		$this->addAddress($message, $options, 'reply_to');
	}

	/**
	 * Set an address field of the message
	 *
	 * @param  Message $message
	 * @param  array $options
	 * @param  string $type
	 * @return void
	 */
	protected function addAddress(Message $message, array $options, $type)
	{
		if (!array_key_exists($type, $options)) {
			return;
		}

		$address = $options[$type];
		$method  = 'set' . ucfirst($type);

		// We only have a single address in the list
		if (!is_array($address)) {
			$key  = $type . '_name';
			$name = array_key_exists($key, $options) ? $options[$key] : null;
			$message->$method($address, $name);
			return;
		}

		// We have a list of addresses, assume 'email' => 'name'
		foreach ($address as $email => $name) {
			$message->$method($email, $name);
		}
	}

	/**
	 * Render the body of the message
	 *
	 * @param  Message $message
	 * @param  array $options
	 * @param  array $variables
	 * @return void
	 */
	protected function renderBody(Message $message, array $options, array $variables)
	{
		if (!array_key_exists('template', $options)) {
			throw new InvalidArgumentException('"template" parameter is missing from options');
		}

		$html = $this->getRenderer()->render($options['template'], $variables);

		// We only need to set the HTML view
		if (!array_key_exists('template_text', $options)) {
			$message->getHeaders()->addHeaderLine('Content-Type', 'text/html');
			$message->setBody($html);
			return;
		}

		$text = $this->getRenderer()->render($options['template_text'], $variables);


		$htmlPart = new MimePart($html);
		$htmlPart->type = 'text/html';

		$textPart = new MimePart($text);
		$textPart->type = 'text/plain';

		$body = new MimeMessage;
		$body->setParts(array($textPart, $htmlPart));

		$message->setBody($body);
	}

	/**
	 * Add attachments to the message
	 *
	 * @param  Message $message
	 * @param  array $options
	 * @return void
	 */
	protected function addAttachments(Message $message, array $options)
	{
		throw new NotImplementedException(
			'Attachments are not supported yet (why don\'t you send a pull request?)'
		);
	}

	/**
	 * Add custom headers to the message object
	 *
	 * @param  Message $message
	 * @param  array $options
	 * @return void
	 */
	protected function addCustomHeaders(Message $message, array $options)
	{
		$headers = $message->getHeaders();

		if (!is_array($options['headers'])) {
			throw new InvalidArgumentException(
				'Header options must be an array of header name => value'
			);
		}

		foreach ($options['headers'] as $name => $value) {
			$headers->addHeaderLine($name, $value);
		}
	}

	/**
	 * Send the given message
	 *
	 * @param  $message Message
	 * @return void
	 */
	protected function sendMessage(Message $message)
	{
		$this->getTransport()->send($message);
	}

	/**
	 * Get the email transport
	 *
	 * @return TransportInterface
	 */
	protected function getTransport()
	{
		return $this->transport;
	}

	/**
	 * Get the view renderer
	 *
	 * @return RendererInterface
	 */
	protected function getRenderer()
	{
		return $this->renderer;
	}

	/**
	 * Get a new default message to use
	 *
	 * The default message can be set to serve as a base for the
	 * service to send emails. The default message is always cloned,
	 * as the service hold state for the default message and every
	 * send message must be configured independently.
	 *
	 * @return Message
	 */
	protected function getDefaultMessage()
	{
		if (null === $this->defaultMessage) {
			$this->defaultMessage = new Message;
		}

		return clone $this->defaultMessage;
	}
}
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

use Zend\Mail\Message;

/**
 * Mail service interface
 *
 * The mail service simplifies the usage of Zend\Mail by a facade
 * for setting message variables, rendering the body based on a
 * template and then send the mail with the given transport.
 */
interface MailServiceInterface
{
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
    public function send(array $options, array $variables = array(), Message $message = null);
}

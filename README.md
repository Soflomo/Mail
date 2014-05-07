Soflomo\Mail
============

Soflomo\Mail is a facade module that integrates various components to send
e-mails in Zend Framework 2. It allows users to compose messages, render
templates and configure transports in one call. Every component is loosly
coupled and can be replaced at runtime.

Soflomo\Mail offers:

 1. A single `send()` to configure a message, render the templates and send it
 2. A default message objects with pre-filled variables like the "From" field
 2. A default transport which can by configured inside your configuration files
 3. All parts can be replaced by any of your own

Installation
------------

Soflomo\Mail works with [Composer](https://getcomposer.org). Make sure you have
the composer.phar downloaded and you have a `composer.json` file at the root of
your project. To install it, add the following line into your `composer.json`
file:

```
"require": {
    "soflomo/mail": "~0.2"
}
```

After installation of the package, you need to complete the following steps to
use Soflomo\Mail:

 1. Enable the module by adding `Soflomo\Mail` to your `application.config.php`
 file.
 2. Copy the `soflomo_mail.global.php.dist` (you can find this file in the
 `config` folder of Soflomo\Mail) into your config/autoload folder and apply any
 setting you want.

Requirements
------------

 1. Zend Framework 2: the `Zend\Mail` component
 2. Zend Framework 2: the `Zend\ServiceManager` component

Usage
-----

Soflomo\Mail uses a central `MailService` facade. This service exposes a single
`send()` method to send an email based on some variables:

```php
// $serviceLocator is an instance of Zend\Service\ServiceManager

$service = $serviceLocator->get('Soflomo\Mail\Service\MailService');
$service->send(array(
  'to'       => 'bob@acme.com',
  'subject'  => 'Just want to say hi',
  'template' => 'email/test',
));
```

The three keys are required to send an email. In the above example, `template`
is the name of the template which is resolved by the PhpRenderer and set as body
in the message.

The `send()` configures the message, renders the `email/test` template and sends
the message with a [configured transport](#smtp-sending-with-the-default-transport).

For controllers, a controller plugin exist to proxy to the email service:

```php
public function sendAction()
{
    $this->email(array(
        'to'       => 'bob@acme.com',
        'subject'  => 'Just want to say hi',
        'template' => 'email/test',
    ));
}
```

### Additional options

You can use, besides `to`, also `from`, `cc`, `bcc` and `reply_to`. For every
addressee you can suffix the option with `_name` to set the name of the address
part.

```php
// $serviceLocator is an instance of Zend\Service\ServiceManager

$service = $serviceLocator->get('Soflomo\Mail\Service\MailService');
$service->send(array(
  'to'            => 'bob@acme.com',
  'subject'       => 'Just want to say hi',
  'template'      => 'email/test',

  'to_name'       => 'Bob',
  'from'          => 'alice@acme.com',
  'from_name'     => 'Alice',

  'cc'            => 'mike@acme.com',
  'cc_name'       => 'Mike',

  'bcc'           => 'john@acme.com'
  'bcc_name'      => 'John',

  'reply_to'      => 'internals@acme.com',
  'reply_to_name' => 'ACME Corp internals mailing list'
));
```

### Send to multiple recipients

You can make all of the addressees a key/value array. This allows you to send
to multiple persons in one `send()` call.

```php
// $serviceLocator is an instance of Zend\Service\ServiceManager

$service = $serviceLocator->get('Soflomo\Mail\Service\MailService');
$service->send(array(
  'to'       => array(
    'bob@acme.com' => 'Bob', 'alice@acme.com' => 'Alice'
  ),
  'subject'  => 'Just want to say hi',
  'template' => 'email/test',
));
```

At this moment, the array must be a key/value pair. Non-associative arrays are
not recognized. If you want to leave the name blank, use `null`:

    array('bob@acme.com' => null, 'alice@acme.com' => null)

### Send custom headers

You can add additional headers to the email message object with the `headers`
key.

```php
// $serviceLocator is an instance of Zend\Service\ServiceManager

$service = $serviceLocator->get('Soflomo\Mail\Service\MailService');
$service->send(array(
  'to'       => 'bob@acme.com',
  'subject'  => 'Just want to say hi',
  'template' => 'email/test',
  'headers'  => array(
    'X-Send-By' => 'MyCustomApp'
  ),
));
```

### Add attachments

Attachment support is planned and not yet implemented. Attachments will be send
via the `attachments` key:

```php
// $serviceLocator is an instance of Zend\Service\ServiceManager

$service = $serviceLocator->get('Soflomo\Mail\Service\MailService');
$service->send(array(
  'to'          => 'bob@acme.com',
  'subject'     => 'Just want to say hi',
  'template'    => 'email/test',
  'attachments' => array(
    // ...
  ),
));
```

### Use template variables

The `send()` method accepts a second paramter to inject variables in the view
template.

```php
// Your template
<p>Welcome <?= $name?></p>
```

```php
// $serviceLocator is an instance of Zend\Service\ServiceManager

$service = $serviceLocator->get('Soflomo\Mail\Service\MailService');
$service->send(array(
  'to'       => 'bob@acme.com',
  'subject'  => 'Just want to say hi',
  'template' => 'email/test',
), array(
  'name'     => 'Bob',
));
```

### Use your own message object

If you happen to have a message object already, you can set it as the third
parameter. You can have a message object which you have already configured
partially or you need to reuse an instantiated message.

```php
// $messaga is an instance of Zend\Mail\Message
// $serviceLocator is an instance of Zend\Service\ServiceManager

$message->setFrom('alice@acme.com');
$service = $serviceLocator->get('Soflomo\Mail\Service\MailService');
$service->send(array(
  'to'       => 'bob@acme.com',
  'subject'  => 'Just want to say hi',
  'template' => 'email/test',
), array(), $message);
```

Configuration
-------------

Soflomo\Mail is completely configurable to your needs. The module utilizes
dependency injection. This allows any user to drop in their own parts replacing
the default services from Soflomo\Mail. In the usage section, some examples are
given for these situations.

### SMTP sending with the default transport

The transport Soflomo\Mail uses (called `Soflomo\Mail\Transport`) is an alias for
the default transport (`Soflomo\Mail\DefaultTransport`). You can use the default
transport for configuration-based SMTP transports. Just copy this to your local
config file in the `config/autoload` directory:

```php
'soflomo_mail' => array(
    'transport' => array(
        'type'    => 'smtp',
        'options' => array(
            'name' => 'myserver.com',
            'host' => 'smtp.myserver.com',
            'connection_class'  => 'login',
            'connection_config' => array(
                'ssl'      => 'tls',
                'username' => 'my-username',
                'password' => 'my-password',
            ),
        ),
    ),
),
```

The 'type' can be a class from `Zend\Mail\Transport\*` (so either "file", "smtp"
or "sendmail"). The `options` array is used to instantiate an options object
corresponding to the type (for "smtp" an `SmtpOptions` class is used).

Alternatively, give the type a FQCN and it utilizes that class for the transport.
Be aware this FQCN is a simple solution and cannot implement dependency injection.
For more advanced usage, see how to configure your
[own custom transport](#use-your-custom-transport-factory).

### Use an existing alternative transport service

[SlmMail](http://github.com/juriansluiman/SlmMail) is a module which implements
the API for various third party email providers like Mailgun, Postmark and
Amazon SES.

All SlmMail transports are services in the service manager which you can use to
inject in any other class. For Soflomo\Mail, set the alias to the SlmMail
transport you use and it's automatically injected.

```php
'service_manager' => array(
    'aliases' => array(
        'Soflomo\Mail\Transport' => 'SlmMail\Mail\Transport\SendGridTransport',
    ),
),
```

### Use your custom transport factory

...
Soflomo\Mail
===
`Soflomo\Mail` is a small module that provides a drop-in configuration, instantiation and initialization of a `Zend\Mail\Transport` and `Zend\Mail\Message` object. Its purpose it to have email transportation enabled in your Zend Framework 2 application with only a small configuration file. The module is opinionated with the configuration of transport, but you are free to configure your own.

Installation
---
`Soflomo\Mail` is available through composer. Add "soflomo/mail" to your composer.json list. During development of `Soflomo\Mail`, you can specify the latest available version:

```
"soflomo/mail": "dev-master"
```

Enable the module in your `config/application.config.php` file. Add an entry `Soflomo\Mail` to the list of enabled modules. Logging should work out of the box.
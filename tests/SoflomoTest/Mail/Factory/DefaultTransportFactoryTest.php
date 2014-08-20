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
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

class DefaultTransportFactoryTest extends TestCase
{
    public function testFactoryUsesAliasToDefaultTransport()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setInvokableClass('Soflomo\Mail\DefaultTransport', 'SoflomoTest\Mail\Asset\SimpleTransport');
        $serviceManager->setAllowOverride(false);
        $transport      = $serviceManager->get('Soflomo\Mail\Transport');

        $this->assertInstanceOf('Zend\Mail\Transport\TransportInterface', $transport);
    }

    public function testFactoryRequiresTypeFromConfig()
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotCreatedException');

        $serviceManager = ServiceManagerFactory::getServiceManager();
        $transport      = $serviceManager->get('Soflomo\Mail\Transport');
    }

    public function testFactoryThrowsRuntimeExceptionForMissingType()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();

        try {
            $transport = $serviceManager->get('Soflomo\Mail\Transport');
        } catch (ServiceNotCreatedException $e) {
            $previous = $e->getPrevious();
            $this->assertInstanceOf('Soflomo\Mail\Exception\RuntimeException', $previous);
        }
    }

    public function testFactorySetsTypeFromConfig()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();

        $config = $serviceManager->get('config');
        $config['soflomo_mail']['transport']['type'] = 'Sendmail';
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('config', $config);
        $serviceManager->setAllowOverride(false);

        $transport = $serviceManager->get('Soflomo\Mail\Transport');
        $this->assertInstanceof('Zend\Mail\Transport\Sendmail', $transport);
    }

    public function testFactoryUsesOptionsFromConfig()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();

        $config  = $serviceManager->get('config');
        $options = array('name' => 'Foo');
        $config['soflomo_mail']['transport']['type']    = 'Smtp';
        $config['soflomo_mail']['transport']['options'] = $options;
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('config', $config);
        $serviceManager->setAllowOverride(false);

        $transport = $serviceManager->get('Soflomo\Mail\Transport');
        $this->assertInstanceof('Zend\Mail\Transport\Smtp', $transport);

        $options = $transport->getOptions();
        $this->assertEquals('Foo', $options->getName());
    }

    public function testFactoryAllowsVariablesInOptions()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();

        $config    = $serviceManager->get('config');
        $options   = array('name' => '%FOO%');
        $variables = array('foo'  => 'Bar');
        $config['soflomo_mail']['transport']['type']      = 'Smtp';
        $config['soflomo_mail']['transport']['options']   = $options;
        $config['soflomo_mail']['transport']['variables'] = $variables;
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('config', $config);
        $serviceManager->setAllowOverride(false);

        $transport = $serviceManager->get('Soflomo\Mail\Transport');
        $this->assertInstanceof('Zend\Mail\Transport\Smtp', $transport);

        $options = $transport->getOptions();
        $this->assertEquals('Bar', $options->getName());
    }

    public function testFactoryAllowsFqcnAsType()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();

        $config  = $serviceManager->get('config');
        $type    = 'SoflomoTest\Mail\Asset\SimpleTransport';
        $config['soflomo_mail']['transport']['type'] = $type;
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('config', $config);
        $serviceManager->setAllowOverride(false);

        $transport = $serviceManager->get('Soflomo\Mail\Transport');
        $this->assertInstanceof('SoflomoTest\Mail\Asset\SimpleTransport', $transport);
    }
}

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

namespace Soflomo\Mail\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DefaultTransportFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config');
        $config = $config['soflomo_mail'];
        $name   = $config['transport']['type'];

        // Allow type as FQCN, defaults to Zend\Mail\Transport\* type
        if (!class_exists($name)) {
            $name = 'Zend\Mail\Transport\\' . ucfirst($name);
        }

        $transport = new $name;

        // Set options if present
        if (!empty($config['transport']['options'])) {
            $options = $config['transport']['options'];

            if (!empty($config['transport']['variables'])) {
                $variables = $config['transport']['variables'];

                // Make sure every key in variables is %KEY_NAME% format
                $variables = array_flip($variables);
                $variables = array_map(function($value) {
                    return '%' . strtoupper($value) . '%';
                }, $variables);
                $variables = array_flip($variables);

                $options   = $this->replace($options, $variables);
            }

            $name = $name . 'Options';
            $optionsClass = new $name($options);

            $transport->setOptions($optionsClass);
        }

        return $transport;
    }

    /**
     * Replace values of an array if they match variables key
     *
     * This mimicks "template" behaviour. Example code to show result:
     *
     * <code>
     * $options   => array('my_key' => '%FOO%');
     * $variables => array('%FOO%'  => 'Bar');
     *
     * // Result:
     * array('my_key' => 'Bar');
     * </code>
     *
     * @param  array $options     Original base array
     * @param  array $variables   Variables array
     *
     * @return array              New replaced base array
     */
    public function replace($options, $variables)
    {
        foreach ($options as $name => $value) {
            if (is_array($value)) {
                $options[$name] = $this->replace($value, $variables);
                continue;
            }

            if (array_key_exists($value, $variables)) {
                $options[$name] = $variables[$value];
            }
        }

        return $options;
    }
}

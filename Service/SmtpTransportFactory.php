<?php

namespace Vt\Mail\Service;

use Zend\Mail\Transport\Smtp;
use Zend\Mail\Transport\SmtpOptions;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SmtpTransportFactory implements FactoryInterface
{
    /**
     * @param  ServiceLocatorInterface $serviceLocator
     * @return Smtp
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config  = $serviceLocator->get('applicationconfig');

        $options = new SmtpOptions($config['mailer']['smtp_options']);
        $smtpTransport = new Smtp($options);

        return $smtpTransport;
    }
}
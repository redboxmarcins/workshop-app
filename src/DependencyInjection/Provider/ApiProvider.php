<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Bootstrap\DependencyInjection\Provider;

use PhpAmqpLib\Connection\AMQPLazyConnection;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Seven\Component\MessageBusClient\Client;
use Seven\Component\MessageBusClient\Encoder\JsonEncoder;
use Seven\Component\MessageBusClient\Protocol\AMQP;

class ApiProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $pimple)
    {
        $pimple['bootstrap.amqp.connection'] = function () use ($pimple) {
            $config = $pimple['bootstrap.config']['amqp'];

            return new AMQPLazyConnection(
                $config['host'],
                $config['port'],
                $config['user'],
                $config['password'],
                $config['vhost']
            );
        };

        $pimple['bootstrap.amqp.channel'] = function () use ($pimple) {
            return $pimple['bootstrap.amqp.connection']->channel();
        };

        $pimple['bootstrap.json_encoder'] = function () use ($pimple) {
            return new JsonEncoder();
        };

        $pimple['bootstrap.amqp_driver'] = function () use ($pimple) {
            return new AMQP\Driver(
                $pimple['bootstrap.amqp.channel'],
                'outbox',
                $pimple['bootstrap.json_encoder']
            );
        };

        $pimple['bootstrap.api_client'] = function () use ($pimple) {
            return new Client($pimple['bootstrap.amqp_driver']);
        };

        $pimple['bootstrap.amqp.consumer'] = function () use ($pimple) {
            return new AMQP\Consumer($pimple['bootstrap.amqp.channel'], $pimple['bootstrap.api_service']);
        };
    }
}

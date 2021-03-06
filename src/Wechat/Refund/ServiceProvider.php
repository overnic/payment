<?php
namespace OverNick\Payment\Wechat\Refund;

use function foo\func;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Class ServiceProvider
 * @package OverNick\Payment\Wechat\Refund
 */
class ServiceProvider implements ServiceProviderInterface
{

    public function register(Container $app)
    {
        $app['refund'] = function () use($app){
            return new Client($app);
        };
    }

}
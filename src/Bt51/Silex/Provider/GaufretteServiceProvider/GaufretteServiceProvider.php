<?php

/*
 * This file is part of GaufretteServiceProvider
 *
 * (c) Ben Tollakson <btollakson.os@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bt51\Silex\Provider\GaufretteServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Cache;

class GaufretteServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        if (! isset($app['gaufrette.adapter.class'])) {
            $app['gaufrette.adapter.class'] = 'Local';
        }

	    $app['gaufrette.adapter'] = function () use ($app) {
            $options = (isset($app['gaufrette.options']) ? $app['gaufrette.options'] : array());
            $class = sprintf('\\Gaufrette\\Adapter\\%s', $app['gaufrette.adapter.class']);
            $adapter = new \ReflectionClass($class);
            return $adapter->newInstanceArgs($options);

};
        
	$app['gaufrette.adapter.cache'] = function () use ($app) {
            if (! isset($app['gaufrette.adapter.cache.class'])) {
                return false;
            }
            $options = (isset($app['gaufrette.cache.options']) ? $app['gaufrette.cache.options'] : array());
            $class = sprintf('\\Gaufrette\\Adapter\\%s', $app['gaufrette.adapter.cache.class']);
            $adapter = new \ReflectionClass($class);
            return $adapter->newInstanceArgs($options);
        };  
    }
    
    public function boot(Application $app)
    {
        if ($app['gaufrette.adapter.cache']) {
            $app['gaufrette.cache'] = $app->share(function ($app) {
                $ttl = isset($app['gaufrette.cache.ttl']) ? $app['gaufrette.cache.ttl'] : 0;
                return new Cache($app['gaufrette.adapter'], $app['gaufrette.adapter.cache'], $ttl);
            });
        }
        
        $app['gaufrette.filesystem'] = $app->share(function ($app) {
            if (isset($app['gaufrette.cache'])) {
                return new Filesystem($app['gaufrette.cache']);
            }
            
            return new Filesystem($app['gaufrette.adapter']);
        }); 
    }
}

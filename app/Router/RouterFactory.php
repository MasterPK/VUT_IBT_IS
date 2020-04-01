<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;
        $router->addRoute("api/<action>", "Api:default");
        $router->addRoute("[<locale=cs cs|en>/]<module>/<presenter>/<action>", "Visitor:Login:default");
        $router->addRoute("[<locale=cs cs|en>/]<presenter>/<action>", "Visitor:Login:default");


		return $router;
	}
}

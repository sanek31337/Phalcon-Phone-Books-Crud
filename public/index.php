<?php
declare(strict_types=1);

use Phalcon\Di\FactoryDefault;

ini_set("display_errors", "1");
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

try {
    include_once BASE_PATH . '/vendor/autoload.php';

    /**
     * The FactoryDefault Dependency Injector automatically registers
     * the services that provide a full stack framework.
     */
    $di = new FactoryDefault();

    /**
     * Read services
     */
    include APP_PATH . '/config/services.php';

    /**
     * Handle routes
     */
    include APP_PATH . '/config/router.php';

    /**
     * Get config service for use in inline setup below
     */
    /** @var \Phalcon\Config $config $di */
    $config = $di->getConfig();

    /**
     * Include Autoloader
     */
    include APP_PATH . '/config/loader.php';

    /**
     * Handle the request
     */
    $application = new \Phalcon\Mvc\Application($di);

    echo $application->handle($_SERVER['REQUEST_URI'])->getContent();
}
catch (\App\Exceptions\PhoneBookItemException|Exception $exception)
{
    $payload = [
        'status' => 'fail',
        'message' => $exception->getMessage()
    ];

    $response = new Phalcon\Http\Response(json_encode($payload), $exception->getHttpStatusCode());
    $response->send();
}
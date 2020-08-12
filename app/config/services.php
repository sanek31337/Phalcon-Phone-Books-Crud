<?php
declare(strict_types=1);

use App\Repositories\AccessTokenRepository;
use App\Repositories\ClientRepository;
use App\Repositories\ScopeRepository;
use Phalcon\Cache\Adapter\Stream;
use Phalcon\Escaper;
use Phalcon\Flash\Direct as Flash;
use Phalcon\Logger\AdapterFactory;
use Phalcon\Logger\LoggerFactory;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Php as PhpEngine;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Session\Adapter\Stream as SessionAdapter;
use Phalcon\Session\Manager as SessionManager;
use Phalcon\Storage\SerializerFactory;
use Phalcon\Url as UrlResolver;
use GuzzleHttp\Client;

/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->setShared('url', function () {
    $config = $this->getConfig();

    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
});

/**
 * Setting up the view component
 */
$di->setShared('view', function () {
    $config = $this->getConfig();

    $view = new View();
    $view->setDI($this);
    $view->setViewsDir($config->application->viewsDir);

    $view->registerEngines([
        '.volt' => function ($view) {
            $config = $this->getConfig();

            $volt = new VoltEngine($view, $this);

            $volt->setOptions([
                'path' => $config->application->cacheDir,
                'separator' => '_'
            ]);

            return $volt;
        },
        '.phtml' => PhpEngine::class

    ]);

    return $view;
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function () {
    $config = $this->getConfig();

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
    $params = [
        'host'     => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname'   => $config->database->dbname,
        'charset'  => $config->database->charset
    ];

    if ($config->database->adapter == 'Postgresql') {
        unset($params['charset']);
    }

    return new $class($params);
});


/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->setShared('modelsMetadata', function () {
    return new MetaDataAdapter();
});

/**
 * Register the session flash service with the Twitter Bootstrap classes
 */
$di->set('flash', function () {
    $escaper = new Escaper();
    $flash = new Flash($escaper);
    $flash->setImplicitFlush(false);
    $flash->setCssClasses([
        'error'   => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice'  => 'alert alert-info',
        'warning' => 'alert alert-warning'
    ]);

    return $flash;
});

/**
 * Start the session the first time some component request the session service
 */
$di->setShared('session', function () {
    $session = new SessionManager();
    $files = new SessionAdapter([
        'savePath' => sys_get_temp_dir(),
    ]);
    $session->setAdapter($files);
    $session->start();

    return $session;
});

$di->setShared('response', function(){
    $response = new Phalcon\Http\Response();

    $response->setContentType('application/json', 'utf-8');

    return $response;
});

$di->setShared('logger', function(){
    $config = $this->getConfig();

    $adapterFactory = new AdapterFactory();
    $loggerFactory  = new LoggerFactory($adapterFactory);

    return $loggerFactory->load($config);
});

$di->setShared('http_request', function(){
    return new Client();
});

$di->setShared('oauth2Server', function () {
    $config = $this->getConfig();

    $clientRepository = new ClientRepository();
    $scopeRepository = new ScopeRepository();
    $accessTokenRepository = new AccessTokenRepository();

    // Setup the authorization server
    $server = new \League\OAuth2\Server\AuthorizationServer(
        $clientRepository,
        $accessTokenRepository,
        $scopeRepository,
        new \League\OAuth2\Server\CryptKey($_ENV['PRIVATE_KEY_PATH'], null, false),
        $_ENV['ENCRYPTION_KEY']
    );

    // Enable the client credentials grant on the server
    $server->enableGrantType(new \League\OAuth2\Server\Grant\ClientCredentialsGrant(), $config->oauth->access_token_lifespan);

    // Enable the implicit grant on the server
    $server->enableGrantType(
        new \League\OAuth2\Server\Grant\ImplicitGrant($config->oauth->access_token_lifespan),
        $config->oauth->access_token_lifespan
    );

    return $server;
});

$di->set(
    "dispatcher",
    function () use ($di) {
        $dispatcher = new \Phalcon\Mvc\Dispatcher();

        $eventsManager = $di->getShared("eventsManager");

        $eventsManager->attach(
            "dispatch:beforeExecuteRoute",
            function(\Phalcon\Events\Event $event) use ($di)
            {
                /** @var \Phalcon\Mvc\Dispatcher $source */
                $source = $event->getSource();

                if ($source->getActionName() !== 'authorize')
                {
                    $middleware = new \App\Services\OAuthTokenMiddleware($di->get('response'));
                    $middleware->authenticate();
                }
            }
        );

        $dispatcher->setEventsManager($eventsManager);

        return $dispatcher;
    },
    true
);

$di->setShared('cache', function(){
    $serializerFactory = new SerializerFactory();

    $options = [
        'defaultSerializer' => 'Json',
        'lifetime'          => 7200,
        'storageDir'        => BASE_PATH . '/cache'
    ];

    $adapter = new Stream($serializerFactory, $options);

    return new Phalcon\Cache($adapter);
});
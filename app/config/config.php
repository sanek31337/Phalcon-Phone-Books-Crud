<?php

/*
 * Modified: prepend directory path of current file, because of this file own different ENV under between Apache and command line.
 * NOTE: please remove this comment.
 */
defined('BASE_PATH') || define('BASE_PATH', getenv('BASE_PATH') ?: realpath(dirname(__FILE__) . '/../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');

use josegonzalez\Dotenv\Loader;

try {
    $oneMonthInterval = new \DateInterval('P1M');
    $oneHourInterval = new \DateInterval('PT1H');
    $tenMinutesInterval = new \DateInterval('PT10M');
}
catch (Exception $e){}

$baseConfig =  new \Phalcon\Config([
    'application' => [
        'appDir'         => APP_PATH . '/',
        'controllersDir' => APP_PATH . '/controllers/',
        'modelsDir'      => APP_PATH . '/models/',
        'migrationsDir'  => APP_PATH . '/migrations/',
        'viewsDir'       => APP_PATH . '/views/',
        'pluginsDir'     => APP_PATH . '/plugins/',
        'exceptionsDir'  => APP_PATH . '/exceptions/',
        'servicesDir'    => APP_PATH . '/services/',
        'entitiesDir'    => APP_PATH . '/entities/',
        'libraryDir'     => APP_PATH . '/library/',
        'repositoriesDir' => APP_PATH . '/repositories/',
        'interfacesDir' => APP_PATH . '/interfaces',
        'cacheDir'       => BASE_PATH . '/cache/',
        'baseUri'        => '/',
    ],
    "name"        => 'logger',
    "adapters"    => [
        "main"  => [
            "adapter" => "stream",
            "name"    => APP_PATH . "/storage/logs/main.log",
            "options" => []
        ]
    ],
    'oauth' => [
        'refresh_token_lifespan' => $oneMonthInterval,
        'access_token_lifespan' => $oneHourInterval,
        'auth_code_lifespan' => $tenMinutesInterval,
        'always_include_client_scopes' => true,
    ],
]);

$loader = (new josegonzalez\Dotenv\Loader(APP_PATH . '/env/.env'))
    ->parse()
    ->toEnv();

$envConfig = new \Phalcon\Config(
    [
        'oauth_data' => [
            'application_env' => $_ENV['APPLICATION_ENV'],
            'encryption_key' => $_ENV['ENCRYPTION_KEY'],
            'public_key_path' => $_ENV['PUBLIC_KEY_PATH'],
            'private_key_path' => $_ENV['PRIVATE_KEY_PATH']
        ],
        'database' => [
            'adapter'     => 'Mysql',
            'host'        => $_ENV['DB_HOST'],
            'dbname'      => $_ENV['DB_NAME'],
            'username'    => $_ENV['DB_USER'],
            'password'    => $_ENV['DB_PASSWORD'],
            'charset'     => 'utf8',
        ],
    ]
);

$baseConfig->merge($envConfig);

return $baseConfig;
<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
    [
        $config->application->controllersDir,
        $config->application->modelsDir,
        $config->application->exceptionsDir,
        $config->application->servicesDir,
        $config->application->entitiesDir,
        $config->application->libraryDir,
        $config->application->repositoriesDir,
        $config->application->interfacesDir
    ]
)->register();

$loader->registerNamespaces(
    [
        'App\Exceptions' => $config->application->exceptionsDir,
        'App\Entities'   => $config->application->entitiesDir,
        'App\Services'   => $config->application->servicesDir,
        'App\Library'    => $config->application->libraryDir,
        'App\Repositories' => $config->application->repositoriesDir,
        'App\CInterface' => $config->application->interfacesDir,
        'App\Models'    => $config->application->modelsDir
    ]
)->register();
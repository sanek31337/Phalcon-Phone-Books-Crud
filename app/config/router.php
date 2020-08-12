<?php

use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\Group;

/** @var Router $router */
$router = $di->getRouter(false);

$router->removeExtraSlashes(false);

//auth routes
$auth = new Group([
    'controller' => 'Auth'
]);

$auth->setPrefix('/api/v1/oauth');
$auth->addGet('/authorize', ['action' => 'authorize']);
$router->mount($auth);

// Define your routes here
$phoneBooks = new Group(['controller' => 'PhoneBook']);
$phoneBooks->setPrefix('/phoneBook');

// Added route to get all phone book items
$phoneBooks->addGet(
    '/items/:params',
    [
        'action' => 'list'
    ]
);

// Added route to get the specific item by id
$phoneBooks->addGet(
    '/items/{id}/',
    [
        'action' => 'view'
    ]
);

// Added route to create new phone book item
$phoneBooks->addPost(
    '/items/',
    [
        'action' => 'addItem'
    ]
);

// Added route to update phone book item by id
$phoneBooks->addPut(
    '/items/{id}/',
    [
        'action' => 'updateItem'
    ]
);

// Delete phone book item by id
$phoneBooks->addDelete(
    '/items/{id}/',
    [
        'action' => 'deleteItem'
    ]
);

$router->mount($phoneBooks);

$router->setDefaultController('phoneBook');
$router->setDefaultAction('list');

$router->notFound(
    [
        'controller' => 'PhoneBook',
        'action'     => 'route404'
    ]
);

$router->handle($_SERVER['REQUEST_URI']);
<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

service('auth')->routes($routes);
$routes->get('/profile', 'ProfileController::index', ['filter' => 'session']);
// Formun post edileceği ve AJAX isteğinin yapılacağı rotalar
$routes->post('/profile/update', 'ProfileController::update', ['filter' => 'session']);
$routes->get('/profile/get-districts/(:num)', 'ProfileController::getDistricts/$1', ['filter' => 'session']);

/**
 * --------------------------------------------------------------------
 * Admin Routes
 * --------------------------------------------------------------------
 */
$routes->group('admin', ['filter' => 'group:admin,except:profile*'], static function ($routes) {
    /**
     * User Management Routes
     */
    $routes->get('users', 'Admin\UserController::index', ['as' => 'admin.users.index']);
    $routes->get('users/new', 'Admin\UserController::new', ['as' => 'admin.users.new']);
    $routes->post('users/create', 'Admin\UserController::create', ['as' => 'admin.users.create']);
    $routes->get('users/show/(:num)', 'Admin\UserController::show/$1', ['as' => 'admin.users.show']); // Bu satırı ekle
    $routes->get('users/edit/(:num)', 'Admin\UserController::edit/$1', ['as' => 'admin.users.edit']);
    $routes->post('users/update/(:num)', 'Admin\UserController::update/$1', ['as' => 'admin.users.update']);
    $routes->post('users/delete/(:num)', 'Admin\UserController::delete/$1', ['as' => 'admin.users.delete']);
    $routes->get('logs', 'Admin\LogController::index', ['as' => 'admin.logs.index']);
    $routes->get('students/import', 'Admin\StudentController::importView');
    $routes->post('students/import', 'Admin\StudentController::import');
    $routes->resource('students', ['controller' => 'Admin\StudentController']);
});


<?php

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'host'     => '',
        'user'     => '',
        'password' => '',
        'dbname'   => '',
    ),
));

$app['validator'] = new \Particle\Validator\Validator();
$app['validator']->required('name')->allowEmpty(false);
$app['validator']->optional('message');
$app['validator']->optional('password');
$app['validator']->optional('email')->email();
$app['validator']->required('lat')->allowEmpty(false);
$app['validator']->required('lon')->allowEmpty(false);

$app['salt'] = 'jrgerijgbeslrgjbealrjgbearlgjebglj';
$app['default_approval_status'] = 'approved';
$app['bing_api_key'] = '';

$app['maps'] = new \Energy\Maps($app['bing_api_key']);

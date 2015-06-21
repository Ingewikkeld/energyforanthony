<?php

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => $dbconfig,
));

$app->register(new Silex\Provider\SessionServiceProvider());

$app['validator'] = new \Particle\Validator\Validator();
$app['validator']->required('name')->allowEmpty(false);
$app['validator']->optional('message');
$app['validator']->optional('password');
$app['validator']->optional('email')->email();
$app['validator']->required('lat')->allowEmpty(false);
$app['validator']->required('lon')->allowEmpty(false);

$app['updateValidator'] = new \Particle\Validator\Validator();
$app['updateValidator']->required('lat')->allowEmpty(false);
$app['updateValidator']->required('lon')->allowEmpty(false);
$app['updateValidator']->required('name')->allowEmpty(false);
$app['updateValidator']->optional('message');

$app['maps'] = new \Energy\Maps($app['bing_api_key']);

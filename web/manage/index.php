<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app['debug'] = true;

require_once __DIR__.'/../../config/config.php';

$app->get('/about', function() use ($app) {
    return $app['twig']->render('about.twig', []);
});

$app->get('/add', function () use ($app) {
    return $app['twig']->render('add.twig', []);
});

$app->post('/add', function (Request $request) use ($app) {
    $data = [
        'name' => $request->get('name'),
        'message' => $request->get('message'),
        'lat' => $request->get('lat'),
        'lon' => $request->get('lon'),
        'password' => $request->get('password'),
        'email' => $request->get('email')
    ];
    $result = $app['validator']->validate($data);

    if (false === $result) {
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/manage/add');
    }

    $data['password'] = md5($app['salt'] . $data['password']);
    $data['approval_status'] = $app['default_approval_status'];

    // save to the database
    /** @var \Doctrine\DBAL\Connection $db */
    $db = $app['db'];
    $db->insert('energy', $data);

    return $app['twig']->render('added.twig', []);
});

$app->get('/api/json', function () use ($app) {
    $array = [];
    $array['error'] = null;
    $array['list'] = [];
    $array['list']['name'] = 'Energy for Anthony';
    $array['list']['description'] = 'Energy for Anthony';
    $array['list']['id'] = 1;

    $array['groups'] = [];

    /** @var \Doctrine\DBAL\Connection $db */
    $db = $app['db'];
    $result = $db->fetchAll("SELECT * FROM energy WHERE approval_status = 'approved'", []);

    foreach($result as $item) {
        $group = [];
        $group['id'] = $item['id'];
        $group['name'] = $item['name'];
        $group['shortname'] = $item['name'];
        $group['latitude'] = $item['lat'];
        $group['longitude'] = $item['lon'];
        $group['message'] = $item['message'];

        $array['groups'][] = $group;
    }

    return new \Symfony\Component\HttpFoundation\JsonResponse($array);
});

$app->post('/api/address', function (Request $request) use ($app) {
    $result = $app['maps']->search($request->get('address'));

    return $app['twig']->render('address-search.twig', ['results' => $result]);
});

$app->run();

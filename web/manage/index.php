<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application([
    'debug' => false
]);

require_once __DIR__.'/../../config/config.php';
require_once __DIR__.'/../../config/bootstrap.php';

$app->get('/about', function() use ($app) {
    return $app['twig']->render('about.twig', []);
});

$app->get('/add', function () use ($app) {
    return $app['twig']->render('add.twig', []);
});

$app->get('/list', function () use ($app) {
    $db = $app['db'];
    $result = $db->fetchAll("SELECT `name`, `message` FROM energy WHERE approval_status = 'approved'", []);

    return $app['twig']->render('list.twig', [ 'messages' => $result]);
});


$app->post('/add', function (Request $request) use ($app) {
    $data = [
        'name' => $request->get('name'),
        'message' => $request->get('message'),
        'lat' => $request->get('lat'),
        'lon' => $request->get('lon'),
        'password' => $request->get('password'),
        'email' => $request->get('email'),
        'line_color' => $request->get('line_color')
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

$app->get('/edit/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('energy')) {
        return $app['twig']->render('login.twig', ['id' => $id]);
    }

    if ($id !== $app['session']->get('energy')['id']) {
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/');
    }

    $errors = [];
    if ($app['session']->has('errors')) {
        $errors = $app['session']->get('errors');
        $app['session']->remove('errors');
    }

    return $app['twig']->render('edit.twig', [
        'id' => $app['session']->get('energy')['id'],
        'energy' => $app['session']->get('energy'),
        'errors' => $errors
    ]);
});

$app->post('/edit/{id}', function (Request $request, $id) use ($app) {
    if (null === $user = $app['session']->get('energy')) {
        return $app['twig']->render('login.twig', ['id' => $id]);
    }

    if ($id !== $app['session']->get('energy')['id']) {
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/');
    }

    /** @var \Doctrine\DBAL\Connection $db */
    $db = $app['db'];

    $data = [
        'name' => $request->get('name'),
        'message' => $request->get('message'),
        'lat' => $request->get('lat'),
        'lon' => $request->get('lon'),
        'line_color' => $request->get('line_color')
    ];

    if ($app['updateValidator']->validate($data)) {
        $energy = $app['session']->get('energy');
        $energy['name'] = $request->get('name');
        $energy['message'] = $request->get('message');
        $energy['lat'] = $request->get('lat');
        $energy['lon'] = $request->get('lon');
        $energy['line_color'] = $request->get('line_color');
        $app['session']->set('energy', $energy);

        $db->update('energy', $data, ['id' => $app['session']->get('energy')['id']]);
    } else {
        $app['session']->set('errors', $app['updateValidator']->getMessages());
    }

    return new \Symfony\Component\HttpFoundation\RedirectResponse('/manage/edit/' . $app['session']->get('energy')['id']);
});

$app->post('/delete/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('energy')) {
        return $app['twig']->render('login.twig', ['id' => $id]);
    }

    if ($id !== $app['session']->get('energy')['id']) {
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/');
    }

    /** @var \Doctrine\DBAL\Connection $db */
    $db = $app['db'];

    $db->delete('energy', ['id' => $app['session']->get('energy')['id']]);
    $app['session']->clear();

    return new \Symfony\Component\HttpFoundation\RedirectResponse('/');
});

$app->post('/login/{id}', function (Request $request, $id) use ($app) {
    /** @var \Doctrine\DBAL\Connection $db */
    $db = $app['db'];
    $result = $db->fetchAssoc("SELECT * FROM energy WHERE id=:id", ['id' => $id]);
    if (
        $result['email'] == $request->get('email') &&
        $result['password'] == md5($app['salt'] . $request->get('password'))
    ) {
        $app['session']->set('energy', $result);
    }

    return new \Symfony\Component\HttpFoundation\RedirectResponse('/manage/edit/' . $result['id']);
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
        $group['line_color'] = $item['line_color'];

        $array['groups'][] = $group;
    }

    return new \Symfony\Component\HttpFoundation\JsonResponse($array);
});

$app->post('/api/address', function (Request $request) use ($app) {
    $result = $app['maps']->search($request->get('address'));

    return $app['twig']->render('address-search.twig', ['results' => $result]);
});

$app->run();

<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

$app->get('/', function() use ($app) {
    $homepageData = $app['show_service']->getHomePageData();
    return $app['twig']->render('index.html.twig', array(
        'results' => $homepageData,
    ));
});

$app->get('/show/{name}', function($name) use ($app) {
    $episodes = $app['show_service']->getEpisodes($name);
    return $app['twig']->render('show.html.twig', array(
        'name' => $name,
        'results' => $episodes,
    ));
})->bind('show');

$app->get('/jobs', function() use ($app) {
    $jobs = $app['job_queue']->getAll();
    var_dump($jobs);
    return "<p>That was the jobs</p>";
});

$app->post('/submit', function(Request $request) use ($app) {
	$id = $request->get('id');
	$app['job_queue']->add($id);
	return new JsonResponse(true);
});

$app->post('/image', function(Request $request) use ($app) {
	$title = $request->get('title');
	$imageService = $app['image_service'];
	$base64 = $imageService->getBase64($title . ' tv');
	return new JsonResponse(array('base64' => $base64));
});

$app->get('/image/{title}', function($title) use ($app) {
        header('Content-Type: image/png');
	$imageService = $app['image_service'];
	$png = $imageService->getPNG($title . ' tv');
	return $png;
});

$app->error(function (\Exception $e, $code) use ($app) {
	if (in_array($code, array(404, 405))) {
		$page = '404.html.twig';
	} else {
		$page = '500.html.twig';
	}
    return new Response($app['twig']->render($page, array('code' => $code)), $code);
});

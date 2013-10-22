<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app->get('/', function() use ($app) {
	$showResults = $app['db']->fetchAll('SELECT * FROM shows ORDER BY show_title, episode_number, date');

	return $app['twig']->render('index.html.twig', array(
		'results' => $showResults,
	));
});
$app->post('/submit', function(Request $request) use ($app) {
	$id = $request->get('id');
	$data = array('apple', 'orange', $id);
	return new JsonResponse($data);
});
$app->post('/image', function(Request $request) use ($app) {
	$title = $request->get('title');
	$imageService = $app['image_service'];
	$base64 = $imageService->getBase64($title . " tv");
	return new JsonResponse(array('base64' => $base64));
});
$app->error(function (\Exception $e, $code) use ($app) {
    if (in_array($code, array(404, 405))) {
		$page = '404.html.twig';
	} else {
		$page = '500.html.twig';
	}
    return new Response($app['twig']->render($page, array('code' => $code)), $code);
});

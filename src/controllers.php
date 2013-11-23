<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app->get('/', function() use ($app) {    
    $showResults = $app['show_data']->getCurrent();
    return $app['twig']->render('index.html.twig', array(
        'results' => $showResults,
    ));
});
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
$app->error(function (\Exception $e, $code) use ($app) {
	if (in_array($code, array(404, 405))) {
		$page = '404.html.twig';
	} else {
		$page = '500.html.twig';
	}
    return new Response($app['twig']->render($page, array('code' => $code)), $code);
});

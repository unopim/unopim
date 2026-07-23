<?php

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Webkul\Core\Exceptions\Handler;

function renderHttpException(HttpException $exception): Response
{
    config(['app.debug' => false]);

    $handler = new Handler(app());
    $handler->register();

    $request = Request::create('/admin/configuration/webhook/create', 'GET');
    $request->headers->set('Accept', 'application/json');

    return $handler->render($request, $exception);
}

it('renders a method mismatch as 405 instead of a generic 500', function () {
    $response = renderHttpException(new MethodNotAllowedHttpException(['POST']));

    expect($response->getStatusCode())->toBe(405);
    expect($response->headers->get('Allow'))->toBe('POST');
});

it('keeps the translated 405 copy in the payload', function () {
    $payload = json_decode(renderHttpException(new MethodNotAllowedHttpException(['POST']))->getContent(), true);

    expect($payload['error'])->toBe(trans('admin::app.errors.405.title'));
    expect($payload['description'])->toBe(trans('admin::app.errors.405.description'));
});

it('renders an upload that is too large as 413', function () {
    expect(renderHttpException(new HttpException(413))->getStatusCode())->toBe(413);
});

it('falls back to 500 for a status with no translated page', function () {
    expect(renderHttpException(new HttpException(418))->getStatusCode())->toBe(500);
});

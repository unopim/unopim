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

it('keeps the thrown status for a code with no translated page', function () {
    expect(renderHttpException(new HttpException(418))->getStatusCode())->toBe(418);
});

it('borrows the generic 500 copy for a code with no translated page', function () {
    $payload = json_decode(renderHttpException(new HttpException(418))->getContent(), true);

    expect($payload['error'])->toBe(trans('admin::app.errors.500.title'));
});

it('renders a validation abort as 422, not as a crash', function () {
    expect(renderHttpException(new HttpException(422, 'Axis is not configurable.'))->getStatusCode())->toBe(422);
});

it('passes the abort reason through on 422', function () {
    $payload = json_decode(renderHttpException(new HttpException(422, 'Axis is not configurable.'))->getContent(), true);

    expect($payload['description'])->toBe('Axis is not configurable.');
});

it('never echoes the exception message on a server error', function () {
    $payload = json_decode(renderHttpException(new HttpException(500, 'Connection string leaked here.'))->getContent(), true);

    expect($payload['description'])->toBe(trans('admin::app.errors.500.description'));
});

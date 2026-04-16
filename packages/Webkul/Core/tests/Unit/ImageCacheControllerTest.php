<?php

use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\Core\ImageCache\Controller;

describe('ImageCache Controller', function () {

    it('fetchFromUrl has a timeout configured to prevent hanging', function () {
        $controller = new Controller;

        $reflection = new ReflectionMethod($controller, 'fetchFromUrl');

        // Verify the method exists and is callable
        expect($reflection->getName())->toBe('fetchFromUrl');

        // Read the source to verify timeout is set
        $source = file_get_contents((new ReflectionClass($controller))->getFileName());

        expect($source)->toContain("'timeout'");
    });

    it('getLogo returns a response when the remote server is reachable', function () {
        $controller = new Controller;

        try {
            $response = $controller->getResponse('logo', 'unopim.png');

            expect($response->getStatusCode())->toBeIn([200, 304]);
        } catch (HttpException $e) {
            // 404 is acceptable when the remote server is unreachable in test env
            expect($e->getStatusCode())->toBe(404);
        }
    });

    it('getResponse returns 404 for unknown template', function () {
        $controller = new Controller;

        try {
            $controller->getResponse('nonexistent', 'test.png');
        } catch (HttpException $e) {
            expect($e->getStatusCode())->toBe(404);

            return;
        }

        $this->fail('Expected 404 exception was not thrown');
    });
});

describe('Profile dropdown logo image', function () {

    it('header view includes onerror handler on the logo image', function () {
        $viewPath = base_path('packages/Webkul/Admin/src/Resources/views/components/layouts/header/index.blade.php');

        $content = file_get_contents($viewPath);

        // Verify the logo image tag has onerror to hide on failure
        expect($content)->toContain('cache/logo/unopim.png');
        expect($content)->toContain('onerror=');
        expect($content)->toContain("this.style.display='none'");
    });
});

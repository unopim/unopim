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

    it('getLogo returns a 200 response when fetchFromUrl succeeds', function () {
        $controller = new class extends Controller
        {
            protected function fetchFromUrl(string $url): string
            {
                return 'fake-image-bytes';
            }
        };

        $response = $controller->getResponse('logo', 'unopim.png');

        expect($response->getStatusCode())->toBeIn([200, 304]);
    });

    it('getLogo returns 404 when fetchFromUrl fails', function () {
        $controller = new class extends Controller
        {
            protected function fetchFromUrl(string $url): string
            {
                throw new Exception('Simulated failure');
            }
        };

        try {
            $controller->getResponse('logo', 'unopim.png');
        } catch (HttpException $e) {
            expect($e->getStatusCode())->toBe(404);

            return;
        }

        $this->fail('Expected 404 exception was not thrown');
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

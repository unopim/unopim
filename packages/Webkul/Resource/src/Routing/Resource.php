<?php

namespace Webkul\Resource\Routing;

use Illuminate\Support\Facades\Route;

class Resource
{
    /**
     * Register the six standard admin CRUD routes for a resource.
     */
    public static function routes(string $name, string $controller): void
    {
        Route::middleware(['admin'])->group(function () use ($name, $controller) {
            Route::get($name, [$controller, 'index'])->name("admin.{$name}.index");
            Route::get("{$name}/create", [$controller, 'create'])->name("admin.{$name}.create");
            Route::post($name, [$controller, 'store'])->name("admin.{$name}.store");
            Route::get("{$name}/{id}/edit", [$controller, 'edit'])->name("admin.{$name}.edit")->whereNumber('id');
            Route::put("{$name}/{id}", [$controller, 'update'])->name("admin.{$name}.update")->whereNumber('id');
            Route::delete("{$name}/{id}", [$controller, 'destroy'])->name("admin.{$name}.destroy")->whereNumber('id');
        });
    }
}

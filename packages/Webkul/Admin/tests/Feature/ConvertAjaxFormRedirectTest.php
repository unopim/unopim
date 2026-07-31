<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    Route::middleware('web')->group(function () {
        Route::post('_test/ajax/same-path', function () {
            session()->flash('success', 'Saved successfully');

            return redirect()->to('_test/ajax/same-path');
        });

        Route::post('_test/ajax/create', function () {
            session()->flash('success', 'Created successfully');

            return redirect()->to('_test/ajax/edit/1');
        });

        Route::post('_test/ajax/soft-error', function () {
            session()->flash('warning', 'Not allowed');

            return back();
        });

        Route::post('_test/ajax/validation', function () {
            $validator = Validator::make([], ['name' => 'required']);

            return back()->withErrors($validator);
        });

        Route::post('_test/ajax/json', fn () => response()->json(['ok' => true]));
    });
});

it('converts a same-page redirect into a json message without a redirect url', function () {
    $this->postJson('_test/ajax/same-path', [], ['X-Ajax-Form' => 'true'])
        ->assertOk()
        ->assertJson(['message' => 'Saved successfully'])
        ->assertJsonMissing(['redirect_url' => '']);
});

it('passes the target url through when the redirect points to a different page', function () {
    $response = $this->postJson('_test/ajax/create', [], ['X-Ajax-Form' => 'true'])
        ->assertOk()
        ->assertJson(['message' => 'Created successfully']);

    expect($response->json('redirect_url'))->toContain('_test/ajax/edit/1');
});

it('returns a 422 with the flashed message for a soft error redirect', function () {
    $this->postJson('_test/ajax/soft-error', [], ['X-Ajax-Form' => 'true'])
        ->assertUnprocessable()
        ->assertJson(['message' => 'Not allowed']);
});

it('returns a 422 with the errors bag for a redirect carrying validation errors', function () {
    $this->postJson('_test/ajax/validation', [], ['X-Ajax-Form' => 'true'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('leaves the redirect untouched when the ajax form header is absent', function () {
    $this->post('_test/ajax/same-path')
        ->assertRedirect('_test/ajax/same-path')
        ->assertSessionHas('success', 'Saved successfully');
});

it('leaves non-redirect responses untouched even with the header present', function () {
    $this->postJson('_test/ajax/json', [], ['X-Ajax-Form' => 'true'])
        ->assertOk()
        ->assertExactJson(['ok' => true]);
});

<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Middleware\AuthenticateAdminFromApiGuard;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

/**
 * OAuth discovery + dynamic client registration endpoints for remote
 * MCP clients (e.g. claude.ai custom connectors).
 */
Mcp::oauthRoutes();

/**
 * Passport's authorize flow redirects guests to the framework-default
 * `login` route, which UnoPim does not define — alias it to admin login.
 */
Route::get('login', fn () => redirect()->route('admin.session.create'))->name('login');

/**
 * The MCP endpoint authenticates via Passport (api guard), but UnoPim's
 * bouncer() ACL reads the admin session guard — bridge the two so MCP
 * tool calls pass permission checks.
 */
app()->booted(function () {
    foreach (app('router')->getRoutes() as $route) {
        if ($route->uri() === 'mcp/unopim' && in_array('POST', $route->methods())) {
            $route->middleware(AuthenticateAdminFromApiGuard::class);
        }
    }
});

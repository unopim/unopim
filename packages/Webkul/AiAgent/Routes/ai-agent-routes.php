<?php

use Illuminate\Support\Facades\Route;
use Webkul\AiAgent\Http\Controllers\AgentController;
use Webkul\AiAgent\Http\Controllers\ChatController;
use Webkul\AiAgent\Http\Controllers\ConversationController;
use Webkul\AiAgent\Http\Controllers\DashboardController;
use Webkul\AiAgent\Http\Controllers\ExecutionController;
use Webkul\AiAgent\Http\Controllers\GenerateController;

// Route middleware: ['admin'] only — NOT ['web', 'admin']
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {

    Route::prefix('ai-agent')->name('ai-agent.')->group(function () {

        // ── AI Settings (redirects to Magic AI configuration) ─
        Route::get('settings', fn () => redirect()->route('admin.configuration.edit', ['general', 'magic_ai']))
            ->name('settings');

        // ── Agents ───────────────────────────────────────────
        Route::get('agents', [AgentController::class, 'index'])
            ->name('agents.index');

        Route::get('agents/create', [AgentController::class, 'create'])
            ->name('agents.create');

        Route::post('agents', [AgentController::class, 'store'])
            ->name('agents.store');

        Route::get('agents/get', [AgentController::class, 'get'])
            ->name('agents.get');

        Route::get('agents/{id}/edit', [AgentController::class, 'edit'])
            ->name('agents.edit');

        Route::put('agents/{id}', [AgentController::class, 'update'])
            ->name('agents.update');

        Route::delete('agents/{id}', [AgentController::class, 'destroy'])
            ->name('agents.destroy');

        // ── Generate (Image → Product) ─────────────────────
        Route::get('generate', [GenerateController::class, 'index'])
            ->name('generate.index');

        Route::post('generate', [GenerateController::class, 'process'])
            ->name('generate.process');

        // ── Execution ────────────────────────────────────────
        Route::post('execute', [ExecutionController::class, 'execute'])
            ->name('execute');

        // ── Chat Widget ──────────────────────────────────────
        Route::post('chat', [ChatController::class, 'send'])
            ->middleware('throttle:30,1')
            ->name('chat.send');

        Route::post('chat/stream', [ChatController::class, 'stream'])
            ->middleware('throttle:30,1')
            ->name('chat.stream');

        Route::post('chat/rate', [ChatController::class, 'rate'])
            ->middleware('throttle:60,1')
            ->name('chat.rate');

        Route::get('chat/magic-ai-config', [ChatController::class, 'magicAiConfig'])
            ->name('chat.magic-ai-config');

        // ── Conversations (persistent sessions) ────────────
        Route::get('conversations', [ConversationController::class, 'index'])
            ->name('conversations.index');

        Route::get('conversations/{id}', [ConversationController::class, 'show'])
            ->name('conversations.show');

        Route::post('conversations', [ConversationController::class, 'store'])
            ->name('conversations.store');

        Route::delete('conversations/{id}', [ConversationController::class, 'destroy'])
            ->name('conversations.destroy');

        // ── Dashboard & Analytics ──────────────────────────
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('dashboard/analytics', [DashboardController::class, 'analytics'])
                ->name('dashboard.analytics');

            Route::get('dashboard/audit-trail', [DashboardController::class, 'auditTrail'])
                ->name('dashboard.audit-trail');

            Route::post('dashboard/rollback/{id}', [DashboardController::class, 'rollback'])
                ->name('dashboard.rollback');

            Route::get('dashboard/notifications', [DashboardController::class, 'notifications'])
                ->name('dashboard.notifications');

            Route::post('dashboard/notifications/{id}/dismiss', [DashboardController::class, 'dismissNotification'])
                ->name('dashboard.notifications.dismiss');
        });

    });

});

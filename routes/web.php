<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\Http\Controllers\CheckMessageCompatibilityController;
use HosmelQ\MessagePreview\Http\Controllers\CheckMessageLinksController;
use HosmelQ\MessagePreview\Http\Controllers\FingerprintController;
use HosmelQ\MessagePreview\Http\Controllers\HomeController;
use HosmelQ\MessagePreview\Http\Controllers\MessageController;
use HosmelQ\MessagePreview\Http\Controllers\PreviewAttachmentController;
use HosmelQ\MessagePreview\Http\Controllers\PreviewController;
use HosmelQ\MessagePreview\Http\Controllers\PreviewEmlController;
use HosmelQ\MessagePreview\Http\Controllers\PreviewHtmlController;
use HosmelQ\MessagePreview\Http\Controllers\PreviewTextController;
use HosmelQ\MessagePreview\Http\Controllers\SendMessageController;
use HosmelQ\MessagePreview\Http\Middleware\EnsureLocalEnvironment;
use Illuminate\Support\Facades\Route;

Route::prefix(config()->string('message-preview.route.path'))
    ->as('message-preview.')
    ->middleware(EnsureLocalEnvironment::class)
    ->group(function (): void {
        Route::get('/', HomeController::class)->name('index');

        Route::prefix('messages/{message}/previews/{variant}')
            ->as('previews.')
            ->group(function (): void {
                Route::get('attachments/{index}', [PreviewAttachmentController::class, 'show'])
                    ->whereNumber('index')
                    ->name('attachment');
                Route::get('eml', [PreviewEmlController::class, 'show'])->name('eml');
                Route::get('html', [PreviewHtmlController::class, 'show'])->name('html');
                Route::get('text', [PreviewTextController::class, 'show'])->name('text');
            });

        Route::prefix('api')->as('api.')->group(function (): void {
            Route::post('compatibility', CheckMessageCompatibilityController::class)
                ->name('compatibility');
            Route::get('fingerprint', [FingerprintController::class, 'show'])->name('fingerprint');
            Route::post('links', CheckMessageLinksController::class)->name('links');
            Route::get('messages', [MessageController::class, 'index'])->name('messages');
            Route::get('messages/{message}/previews/{variant}', [PreviewController::class, 'show'])
                ->name('previews.show');
            Route::post('messages/{message}/previews/{variant}/send', SendMessageController::class)
                ->name('previews.send');
        });
    });

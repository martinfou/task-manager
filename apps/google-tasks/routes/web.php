<?php

use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\TasksController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/health', function (): JsonResponse {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
    ]);
})->name('health');

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');

Route::get('/sw.js', function () {
    return response()->file(public_path('sw.js'), [
        'Content-Type' => 'application/javascript; charset=UTF-8',
    ]);
})->name('pwa.sw');

Route::get('/auth/google', [GoogleOAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleOAuthController::class, 'callback'])->name('google.callback');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tasks', [TasksController::class, 'index'])->name('tasks.index');

    Route::middleware('google.tasks')->prefix('tasks/data')->group(function () {
        Route::get('/task-lists', [TasksController::class, 'taskLists'])->name('tasks.data.task-lists');
        Route::get('/list-order', [TasksController::class, 'listOrder'])->name('tasks.data.list-order');
        Route::put('/list-order', [TasksController::class, 'saveListOrder'])->name('tasks.data.list-order.save');
        Route::patch('/list-order/{listId}/pin', [TasksController::class, 'toggleListPin'])->name('tasks.data.list-order.toggle-pin');
        Route::get('/search', [TasksController::class, 'search'])->name('tasks.data.search');
        Route::post('/search/reindex', [TasksController::class, 'reindexSearchEmbeddings'])->name('tasks.data.search.reindex');
        Route::get('/duplicates', [TasksController::class, 'duplicates'])->name('tasks.data.duplicates');
        Route::get('/views/today', [TasksController::class, 'todayView'])->name('tasks.data.views.today');
        Route::get('/views/inbox', [TasksController::class, 'inboxView'])->name('tasks.data.views.inbox');
        Route::get('/views/all', [TasksController::class, 'allListsView'])->name('tasks.data.views.all');
        Route::get('/{taskList}/tasks', [TasksController::class, 'tasks'])->name('tasks.data.tasks');
        Route::post('/{taskList}/tasks', [TasksController::class, 'storeTask'])->name('tasks.data.tasks.store');
        Route::post('/{taskList}/tasks/{task}/move', [TasksController::class, 'moveTask'])->name('tasks.data.tasks.move');
        Route::patch('/{taskList}/tasks/{task}', [TasksController::class, 'updateTask'])->name('tasks.data.tasks.update');
        Route::delete('/{taskList}/tasks/{task}', [TasksController::class, 'destroyTask'])->name('tasks.data.tasks.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/tasks-preferences', [ProfileController::class, 'updateTasksPreferences'])
        ->name('profile.tasks-preferences.update');
    Route::post('/profile/google/disconnect', [ProfileController::class, 'disconnectGoogle'])->name('profile.google.disconnect');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

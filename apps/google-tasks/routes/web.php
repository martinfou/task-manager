<?php

use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TasksController;
use Illuminate\Foundation\Application;
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

Route::get('/auth/google', [GoogleOAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleOAuthController::class, 'callback'])->name('google.callback');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tasks', [TasksController::class, 'index'])->name('tasks.index');

    Route::middleware('google.tasks')->prefix('tasks/data')->group(function () {
        Route::get('/task-lists', [TasksController::class, 'taskLists'])->name('tasks.data.task-lists');
        Route::get('/search', [TasksController::class, 'search'])->name('tasks.data.search');
        Route::get('/views/today', [TasksController::class, 'todayView'])->name('tasks.data.views.today');
        Route::get('/views/inbox', [TasksController::class, 'inboxView'])->name('tasks.data.views.inbox');
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
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

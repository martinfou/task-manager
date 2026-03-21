<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGoogleTasksConnected
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || ! $user->hasGoogleTasksConnection()) {
            return response()->json([
                'message' => 'Google Tasks is not connected for this account.',
            ], 403);
        }

        return $next($request);
    }
}

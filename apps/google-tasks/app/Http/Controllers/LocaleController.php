<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:en,fr'],
        ]);

        $locale = $validated['locale'];

        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        return back()->withCookie(cookie('locale', $locale, 60 * 24 * 365));
    }
}

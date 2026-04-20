<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale')
            ?? auth()->user()?->language
            ?? config('app.locale', 'fr');

        if (in_array($locale, ['fr', 'ar', 'en'])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}

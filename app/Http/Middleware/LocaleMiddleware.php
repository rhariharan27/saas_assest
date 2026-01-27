<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
  /**
   * Handle an incoming request.
   *
   * @param Closure(Request): (Response) $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    // Check session first, then cookie
    $locale = null;
    
    if (session()->has('locale') && in_array(session()->get('locale'), ['en', 'fr', 'ar', 'de'])) {
      $locale = session()->get('locale');
    } elseif ($request->hasCookie('appLocale') && in_array($request->cookie('appLocale'), ['en', 'fr', 'ar', 'de'])) {
      $locale = $request->cookie('appLocale');
      // Sync cookie locale back to session
      session()->put('locale', $locale);
    }
    
    if ($locale) {
      app()->setLocale($locale);
    }

    return $next($request);
  }
}

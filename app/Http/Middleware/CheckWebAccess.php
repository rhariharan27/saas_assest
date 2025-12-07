<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckWebAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Allow access to login pages and logout
        if ($request->routeIs('auth.login') || $request->routeIs('auth.loginPost') || $request->routeIs('auth.logout')) {
            return $next($request);
        }

        // Check if we're on a central domain - skip middleware for central domain
        $centralDomains = config('tenancy.central_domains', []);
        $currentHost = $request->getHost();
        
        if (in_array($currentHost, $centralDomains)) {
            // Skip web access check for central domain
            return $next($request);
        }

        // If user is not authenticated, let other middleware handle it
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Check if user has web access permission (only for tenant domains)
        if (!$user->hasWebAccess()) {
            // Log the unauthorized access attempt
            Log::warning('Unauthorized web access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent()
            ]);

            // Logout the user and redirect to login with error
            Auth::logout();
            
            return redirect()->route('auth.login')
                ->with('error', 'You do not have permission to access the web application. Please contact your administrator.');
        }

        return $next($request);
    }
}

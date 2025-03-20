<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // For preflight OPTIONS requests, return a successful response immediately
        if ($request->isMethod('OPTIONS')) {
            $response = new Response('', 200);
        } else {
            $response = $next($request);
        }
        
        // Get allowed origins from environment or use defaults
        $allowedOrigins = explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:5173,http://localhost:8080'));
        
        // Get the origin from the request
        $origin = $request->header('Origin');
        
        // Check if the origin is allowed
        if ($origin && (in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins))) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        } else {
            // Default to the first allowed origin if the request origin is not in the allowed list
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigins[0]);
        }
        
        // Add other CORS headers
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, X-CSRF-TOKEN, X-XSRF-TOKEN, X-API-KEY, Cache-Control');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400'); // 24 hours
        
        // Add Vary header to ensure browsers respect the CORS headers properly
        $response->headers->set('Vary', 'Origin');
        
        return $response;
    }
}

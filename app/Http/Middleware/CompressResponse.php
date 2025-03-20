<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CompressResponse
{
    /**
     * Content types that should be compressed
     */
    protected $compressibleTypes = [
        'application/json',
        'text/html',
        'text/plain',
        'text/css',
        'text/javascript',
        'application/javascript',
        'application/x-javascript',
    ];

    /**
     * Minimum size in bytes before compression is applied
     */
    protected $compressionThreshold = 1024; // 1KB

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Process the request
        $response = $next($request);

        // Only compress if the client accepts gzip encoding
        if (!$this->shouldCompress($request, $response)) {
            return $response;
        }

        // Get the response content
        $content = $response->getContent();
        
        // Only compress if content is larger than threshold
        if (strlen($content) < $this->compressionThreshold) {
            return $response;
        }

        // Generate a cache key for the compressed content
        $cacheKey = 'compressed_response_' . md5($content);
        
        // Try to get compressed content from cache
        $compressedContent = Cache::remember($cacheKey, 60, function () use ($content) {
            return $this->compressContent($content);
        });

        // Set the compressed content
        $response->setContent($compressedContent);
        
        // Add appropriate headers
        $response->header('Content-Encoding', 'gzip');
        $response->header('Vary', 'Accept-Encoding');
        
        // Update content length if it exists
        if ($response->headers->has('Content-Length')) {
            $response->header('Content-Length', strlen($compressedContent));
        }

        // Add performance headers
        $response->header('X-Response-Time', round(microtime(true) - LARAVEL_START, 3) . 's');
        
        // Add cache control headers for better browser caching
        if (!$response->headers->has('Cache-Control')) {
            // Default cache control for API responses
            $response->header('Cache-Control', 'private, max-age=60');
        }

        return $response;
    }

    /**
     * Determine if the response should be compressed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response|\Illuminate\Http\JsonResponse  $response
     * @return bool
     */
    protected function shouldCompress(Request $request, $response)
    {
        // Check if client accepts gzip encoding
        if (!$request->header('Accept-Encoding') || !str_contains($request->header('Accept-Encoding'), 'gzip')) {
            return false;
        }

        // Only compress GET requests for better performance
        if (!$request->isMethod('GET')) {
            return false;
        }

        // Don't compress responses that are already compressed
        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        // Check if the content type is compressible
        $contentType = $response->headers->get('Content-Type');
        if ($contentType) {
            foreach ($this->compressibleTypes as $type) {
                if (str_contains($contentType, $type)) {
                    return true;
                }
            }
        }

        // For JSON responses without explicit content type
        return $response instanceof JsonResponse;
    }

    /**
     * Compress the content using gzip.
     *
     * @param  string  $content
     * @return string
     */
    protected function compressContent($content)
    {
        // Use highest compression level (9) for smallest size
        return gzencode($content, 9);
    }
}

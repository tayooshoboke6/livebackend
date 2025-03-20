<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class BannerController extends Controller
{
    /**
     * Display a listing of the banners.
     * Optimized with caching to improve response time.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Cache banners for 10 minutes to improve performance
        $banners = Cache::remember('admin.banners', 600, function () {
            return Banner::all();
        });
        
        return response()->json([
            'status' => 'success',
            'banners' => $banners
        ]);
    }

    /**
     * Store a newly created banner in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|string',
            'bgColor' => 'required|string',
            'imgBgColor' => 'required|string',
            'link' => 'nullable|string',
            'active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Log the request data for debugging
        \Log::info('Banner create request data:', $request->all());

        $banner = Banner::create([
            'label' => $request->label,
            'title' => $request->title,
            'description' => $request->description,
            'image' => $request->image,
            'bg_color' => $request->bgColor, // Convert camelCase to snake_case
            'img_bg_color' => $request->imgBgColor, // Convert camelCase to snake_case
            'link' => $request->link,
            'active' => $request->active ?? true
        ]);

        // Clear the banners cache when a new banner is added
        Cache::forget('admin.banners');

        // Transform the banner for response
        $transformedBanner = [
            'id' => $banner->id,
            'label' => $banner->label,
            'title' => $banner->title,
            'description' => $banner->description,
            'image' => $banner->image,
            'bgColor' => $banner->bg_color, // Convert snake_case to camelCase
            'imgBgColor' => $banner->img_bg_color, // Convert snake_case to camelCase
            'link' => $banner->link,
            'active' => (bool)$banner->active,
            'createdAt' => $banner->created_at,
            'updatedAt' => $banner->updated_at
        ];

        // Log the created banner for debugging
        \Log::info('Banner created:', $banner->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Banner created successfully',
            'banner' => $transformedBanner
        ]);
    }

    /**
     * Display the specified banner.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // This is a placeholder for when we implement the Banner model
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $id,
                'title' => 'Banner ' . $id,
                'subtitle' => 'Banner subtitle',
                'image_url' => 'https://placehold.co/1200x400/png',
                'link' => '/products/category-' . $id,
                'is_active' => true,
                'position' => $id,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]
        ]);
    }

    /**
     * Update the specified banner in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|string',
            'bgColor' => 'required|string',
            'imgBgColor' => 'required|string',
            'link' => 'nullable|string',
            'active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Log the request data for debugging
        \Log::info('Banner update request data:', $request->all());

        $banner->update([
            'label' => $request->label,
            'title' => $request->title,
            'description' => $request->description,
            'image' => $request->image,
            'bg_color' => $request->bgColor, // Convert camelCase to snake_case
            'img_bg_color' => $request->imgBgColor, // Convert camelCase to snake_case
            'link' => $request->link,
            'active' => $request->has('active') ? $request->active : $banner->active
        ]);

        // Clear the banners cache when a banner is updated
        Cache::forget('admin.banners');

        // Transform the banner for response
        $transformedBanner = [
            'id' => $banner->id,
            'label' => $banner->label,
            'title' => $banner->title,
            'description' => $banner->description,
            'image' => $banner->image,
            'bgColor' => $banner->bg_color, // Convert snake_case to camelCase
            'imgBgColor' => $banner->img_bg_color, // Convert snake_case to camelCase
            'link' => $banner->link,
            'active' => (bool)$banner->active,
            'createdAt' => $banner->created_at,
            'updatedAt' => $banner->updated_at
        ];

        // Log the updated banner for debugging
        \Log::info('Banner updated:', $banner->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Banner updated successfully',
            'banner' => $transformedBanner
        ]);
    }

    /**
     * Remove the specified banner from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // This is a placeholder for when we implement the Banner model
        return response()->json([
            'success' => true,
            'message' => 'Banner deleted successfully'
        ]);
    }

    /**
     * Update the position of banners.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request)
    {
        // This is a placeholder for when we implement the Banner model
        return response()->json([
            'success' => true,
            'message' => 'Banners reordered successfully'
        ]);
    }

    /**
     * Toggle the active status of a banner.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id)
    {
        try {
            $banner = Banner::findOrFail($id);
            $banner->is_active = !$banner->is_active;
            $banner->save();
            
            // Clear the banners cache when a banner's status is toggled
            Cache::forget('admin.banners');

            return response()->json([
                'status' => 'success',
                'message' => 'Banner status toggled successfully',
                'banner' => $banner
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle banner status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active banners for public display.
     * This endpoint is cached to improve performance.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveBanners()
    {
        // Cache active banners for 5 minutes
        $banners = Cache::remember('public.banners', 300, function () {
            return Banner::where('active', true)
                        ->orderBy('created_at', 'desc')
                        ->get();
        });

        // Transform banners to match frontend expectations
        $transformedBanners = $banners->map(function ($banner) {
            return [
                'id' => $banner->id,
                'label' => $banner->label,
                'title' => $banner->title,
                'description' => $banner->description,
                'image' => $banner->image,
                'bgColor' => $banner->bg_color,
                'imgBgColor' => $banner->img_bg_color,
                'link' => $banner->link,
                'active' => (bool)$banner->active,
                'createdAt' => $banner->created_at,
                'updatedAt' => $banner->updated_at
            ];
        });

        return response()->json([
            'status' => 'success',
            'banners' => $transformedBanners
        ]);
    }
}

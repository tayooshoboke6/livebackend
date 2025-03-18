<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductMeasurement;
use App\Models\Category;
use App\Models\ProductImage;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected $cloudinaryService;

    /**
     * Create a new controller instance.
     *
     * @param CloudinaryService $cloudinaryService
     * @return void
     */
    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    /**
     * Display a listing of the products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'measurements'])
            ->when($request->has('category_id'), function ($q) use ($request) {
                return $q->where('category_id', $request->category_id);
            })
            ->when($request->has('featured'), function ($q) use ($request) {
                return $q->where('featured', $request->boolean('featured'));
            })
            ->when($request->has('search'), function ($q) use ($request) {
                return $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            })
            ->when($request->has('min_price'), function ($q) use ($request) {
                return $q->where('price', '>=', $request->min_price);
            })
            ->when($request->has('max_price'), function ($q) use ($request) {
                return $q->where('price', '<=', $request->max_price);
            });

        // Default to active products for non-admin users
        if (!$request->has('include_inactive') || !$request->user() || !$request->user()->hasRole('admin')) {
            $query->where('is_active', true);
        }

        $products = $query->orderBy($request->sort_by ?? 'created_at', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($products);
    }

    /**
     * Store a newly created product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'sku' => 'required|string|max:100|unique:products',
                'category_id' => 'required|exists:categories,id',
                'image' => 'nullable|string',
                'images' => 'nullable|array',
                'images.*' => 'nullable|string',
                'measurements' => 'nullable|array',
                'measurements.*.name' => 'required|string|max:100',
                'measurements.*.value' => 'required|string|max:100',
                'measurements.*.unit' => 'nullable|string|max:20',
                'measurements.*.price_adjustment' => 'nullable|numeric',
                'measurements.*.stock' => 'nullable|integer|min:0',
                'measurements.*.is_default' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            // Generate slug
            $slug = Str::slug($request->name);

            // Handle main product image
            $productImage = $request->image;
            if (!$productImage) {
                // Generate placeholder if no image provided
                $productImage = $this->cloudinaryService->generatePlaceholderUrl($request->name);
            } else if (strpos($productImage, 'data:image') === 0) {
                // Handle base64 encoded image
                $productImage = $this->uploadBase64Image($productImage, $request->sku);
            }

            // Create product
            $product = Product::create([
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'base_price' => $request->price,
                'sale_price' => $request->sale_price,
                'stock_quantity' => $request->stock,
                'sku' => $request->sku,
                'category_id' => $request->category_id,
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('featured', false),
                'image' => $productImage,
            ]);

            // Create measurements if provided
            if ($request->has('measurements')) {
                foreach ($request->measurements as $measurementData) {
                    $product->measurements()->create([
                        'name' => $measurementData['name'],
                        'value' => $measurementData['value'],
                        'unit' => $measurementData['unit'] ?? null,
                        'price' => $product->base_price + ($measurementData['price_adjustment'] ?? 0),
                        'sale_price' => $product->sale_price ? ($product->sale_price + ($measurementData['price_adjustment'] ?? 0)) : null,
                        'stock_quantity' => $measurementData['stock'] ?? $product->stock_quantity,
                        'sku' => $product->sku . '-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $measurementData['name']), 0, 3)),
                        'is_default' => $measurementData['is_default'] ?? false,
                        'is_active' => true,
                    ]);
                }
            }

            // Create additional images if provided
            if ($request->has('images')) {
                foreach ($request->images as $imageUrl) {
                    // Handle image URL or base64
                    $finalImageUrl = $imageUrl;
                    if (strpos($imageUrl, 'data:image') === 0) {
                        // Handle base64 encoded image
                        $finalImageUrl = $this->uploadBase64Image($imageUrl, $request->sku . '-' . uniqid());
                    }
                    
                    $product->images()->create([
                        'image_path' => $finalImageUrl,
                        'is_primary' => false,
                        'sort_order' => 0
                    ]);
                }
            }

            return response()->json(['message' => 'Product created successfully', 'product' => $product->load('category', 'measurements', 'images')], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified product.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Debug log to trace the request
        Log::info('Product show method called', [
            'id' => $id,
            'is_numeric' => is_numeric($id),
            'request_path' => request()->path(),
            'is_admin' => request()->is('api/admin/*')
        ]);

        // Allow lookup by ID or slug
        $product = is_numeric($id) 
            ? Product::with(['category', 'measurements', 'images'])->findOrFail($id)
            : Product::with(['category', 'measurements', 'images'])->where('slug', $id)->firstOrFail();

        // Log the response for debugging
        Log::info('Product detail response for ID: ' . $id, [
            'product' => $product->toArray()
        ]);

        // Format the response to match what the frontend expects
        return response()->json([
            'success' => true,
            'product' => $product
        ]);
    }

    /**
     * Update the specified product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'short_description' => 'sometimes|string|nullable',
            'base_price' => 'sometimes|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:base_price',
            'stock_quantity' => 'sometimes|integer|min:0',
            'sku' => 'sometimes|string|max:100|unique:products,sku,' . $product->id,
            'category_id' => 'sometimes|exists:categories,id',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'expiry_date' => 'sometimes|nullable|date',
            'brand' => 'sometimes|nullable|string',
            'image' => 'nullable|string',
            'is_hot_deal' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_expiring_soon' => 'boolean',
            'is_clearance' => 'boolean',
            'is_recommended' => 'boolean',
            'measurements' => 'sometimes|array',
            'measurements.*.id' => 'sometimes|exists:product_measurements,id',
            'measurements.*.name' => 'required|string|max:100',
            'measurements.*.value' => 'required|string|max:100',
            'measurements.*.unit' => 'nullable|string|max:20',
            'measurements.*.price_adjustment' => 'nullable|numeric',
            'measurements.*.stock' => 'nullable|integer|min:0',
            'measurements.*.is_default' => 'boolean',
            'images' => 'sometimes|array',
            'images.*.id' => 'sometimes|exists:product_images,id',
            'images.*.url' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Update slug if name changes
            if ($request->has('name') && $request->name !== $product->name) {
                $slug = Str::slug($request->name);
                $originalSlug = $slug;
                $count = 1;

                // Ensure slug is unique
                while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }

                $request->merge(['slug' => $slug]);
            }

            // Update basic product info
            $updateData = $request->only([
                'name', 'slug', 'description', 'short_description', 'base_price', 'sale_price', 
                'stock_quantity', 'sku', 'category_id', 'is_active', 'is_featured', 'image',
                'expiry_date', 'brand', 'is_hot_deal', 'is_best_seller', 'is_expiring_soon',
                'is_clearance', 'is_recommended'
            ]);
            
            // Filter out null values but keep 0 values, empty strings, and boolean false
            $updateData = array_filter($updateData, function($value) {
                return $value !== null;
            });
            
            $product->update($updateData);
            
            // If no image was provided, generate a placeholder based on the product name
            if (!$request->has('image') && !$product->image) {
                $product->update(['image' => $this->cloudinaryService->generatePlaceholderUrl($product->name)]);
            }

            // Update or create measurements if provided
            if ($request->has('measurements')) {
                $existingMeasurementIds = [];

                foreach ($request->measurements as $measurementData) {
                    if (isset($measurementData['id']) && $measurementData['id'] > 0) {
                        $measurement = $product->measurements()->find($measurementData['id']);
                        if ($measurement) {
                            $measurement->update([
                                'name' => $measurementData['name'] ?? $measurement->name,
                                'value' => $measurementData['value'] ?? $measurement->value,
                                'unit' => $measurementData['unit'] ?? $measurement->unit,
                                'price' => $product->base_price + ($measurementData['price_adjustment'] ?? 0),
                                'sale_price' => $product->sale_price ? ($product->sale_price + ($measurementData['price_adjustment'] ?? 0)) : null,
                                'stock_quantity' => $measurementData['stock'] ?? $measurement->stock_quantity,
                                'is_default' => $measurementData['is_default'] ?? $measurement->is_default,
                            ]);
                            $existingMeasurementIds[] = $measurement->id;
                        }
                    } else {
                        $measurement = $product->measurements()->create([
                            'name' => $measurementData['name'] ?? null,
                            'value' => $measurementData['value'],
                            'unit' => $measurementData['unit'] ?? null,
                            'price' => $product->base_price + ($measurementData['price_adjustment'] ?? 0),
                            'sale_price' => $product->sale_price ? ($product->sale_price + ($measurementData['price_adjustment'] ?? 0)) : null,
                            'stock_quantity' => $measurementData['stock'] ?? $product->stock_quantity,
                            'sku' => $product->sku . '-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $measurementData['name']), 0, 3)),
                            'is_default' => $measurementData['is_default'] ?? false,
                            'is_active' => true,
                        ]);
                        $existingMeasurementIds[] = $measurement->id;
                    }
                }

                // Delete measurements that were not included in the request
                $product->measurements()->whereNotIn('id', $existingMeasurementIds)->delete();
            }

            // Update images if provided
            if ($request->has('images')) {
                $existingImageIds = [];

                foreach ($request->images as $imageData) {
                    if (isset($imageData['id']) && $imageData['id'] > 0) {
                        $image = $product->images()->find($imageData['id']);
                        if ($image) {
                            $image->update([
                                'image_path' => $imageData['url'],
                                'is_primary' => $imageData['is_primary'] ?? $image->is_primary,
                                'sort_order' => $imageData['sort_order'] ?? $image->sort_order
                            ]);
                            $existingImageIds[] = $image->id;
                        }
                    } else {
                        $image = $product->images()->create([
                            'image_path' => is_array($imageData) ? $imageData['url'] : $imageData,
                            'is_primary' => is_array($imageData) && isset($imageData['is_primary']) ? $imageData['is_primary'] : false,
                            'sort_order' => is_array($imageData) && isset($imageData['sort_order']) ? $imageData['sort_order'] : 0
                        ]);
                        $existingImageIds[] = $image->id;
                    }
                }

                // Delete images not in the request
                $product->images()->whereNotIn('id', $existingImageIds)->delete();
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product->fresh(['category', 'measurements', 'images']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified product from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        DB::beginTransaction();

        try {
            // Delete related measurements
            $product->measurements()->delete();

            // Delete related images
            $product->images()->delete();

            // Delete the product
            $product->delete();

            DB::commit();

            return response()->json([
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload a base64 encoded image to Cloudinary.
     *
     * @param string $base64Image
     * @param string $publicId
     * @return string
     */
    private function uploadBase64Image(string $base64Image, string $publicId): string
    {
        return $this->cloudinaryService->uploadBase64Image($base64Image, $publicId);
    }

    /**
     * Generate a placeholder image URL based on product name.
     *
     * @param string $productName
     * @return string
     */
    private function generatePlaceholderImage(string $productName): string
    {
        // Format the product name for the placeholder text
        $text = str_replace(' ', '\n', $productName);
        
        // Generate a placeholder image URL with the product name as text
        return $this->cloudinaryService->generatePlaceholderUrl($text);
    }
}

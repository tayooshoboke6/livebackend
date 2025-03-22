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
                return $q->where('is_featured', $request->boolean('featured'));
            })
            ->when($request->has('search'), function ($q) use ($request) {
                return $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            })
            ->when($request->has('min_price'), function ($q) use ($request) {
                return $q->where('base_price', '>=', $request->min_price);
            })
            ->when($request->has('max_price'), function ($q) use ($request) {
                return $q->where('base_price', '<=', $request->max_price);
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
            // Log the incoming request data for debugging
            Log::info('Product creation request data:', [
                'request_data' => $request->all()
            ]);

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
                'measurements.*.name' => 'nullable|string|max:100',
                'measurements.*.value' => 'nullable|string|max:100',
                'measurements.*.unit' => 'nullable|string|max:20',
                'measurements.*.price_adjustment' => 'nullable|numeric',
                'measurements.*.stock' => 'nullable|integer|min:0',
                'measurements.*.is_default' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                Log::error('Product validation failed:', [
                    'errors' => $validator->errors()->toArray()
                ]);
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
                'base_price' => $request->price, // Map price to base_price
                'sale_price' => $request->sale_price,
                'stock_quantity' => $request->stock, // Map stock to stock_quantity
                'sku' => $request->sku,
                'category_id' => $request->category_id,
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('is_featured', false),
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
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'short_description' => $product->short_description,
                'base_price' => $product->base_price,
                'price' => $product->base_price, // Alias for frontend compatibility
                'sale_price' => $product->sale_price,
                'stock_quantity' => $product->stock_quantity,
                'stock' => $product->stock_quantity, // Alias for frontend compatibility
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'is_featured' => $product->is_featured,
                'featured' => $product->is_featured, // Alias for frontend compatibility
                'is_active' => $product->is_active,
                'is_new_arrival' => $product->is_new_arrival,
                'is_hot_deal' => $product->is_hot_deal,
                'is_best_seller' => $product->is_best_seller,
                'is_expiring_soon' => $product->is_expiring_soon,
                'is_clearance' => $product->is_clearance,
                'is_recommended' => $product->is_recommended,
                'category_id' => $product->category_id,
                'category' => $product->category,
                'category_name' => $product->category ? $product->category->name : null,
                'brand' => $product->brand,
                'expiry_date' => $product->expiry_date,
                'meta_data' => $product->meta_data,
                'total_sold' => $product->total_sold,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'images' => $product->images->map(function($image) {
                    return $image->image_path;
                }),
                'image' => $product->image,
                'image_url' => $product->image_url,
                'measurements' => $product->measurements,
                'is_on_sale' => $product->is_on_sale,
                'is_in_stock' => $product->is_in_stock,
                'discount_percentage' => $product->discount_percentage
            ]
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
        try {
            // Log the incoming request data for debugging
            Log::info('Product update request data:', [
                'request_data' => $request->all()
            ]);
            
            // Find the product
            $product = Product::findOrFail($id);
            
            // Map frontend field names to backend field names if needed
            $requestData = $request->all();
            
            // Handle price field (frontend might send 'price' instead of 'base_price')
            if (isset($requestData['price']) && !isset($requestData['base_price'])) {
                $requestData['base_price'] = $requestData['price'];
            }
            
            // Handle stock field (frontend might send 'stock' instead of 'stock_quantity')
            if (isset($requestData['stock']) && !isset($requestData['stock_quantity'])) {
                $requestData['stock_quantity'] = $requestData['stock'];
            }
            
            // Validate request
            $validator = Validator::make($requestData, [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'base_price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
                'sku' => 'required|string|max:100|unique:products,sku,' . $id,
                'category_id' => 'required|exists:categories,id',
                'image' => 'nullable|string',
                'images' => 'nullable|array',
                'images.*' => 'nullable|string',
                'measurements' => 'nullable|array',
                'measurements.*.name' => 'nullable|string|max:100',
                'measurements.*.value' => 'nullable|string|max:100',
                'measurements.*.unit' => 'nullable|string|max:20',
                'measurements.*.price_adjustment' => 'nullable|numeric',
                'measurements.*.stock' => 'nullable|integer|min:0',
                'measurements.*.is_default' => 'nullable|boolean',
            ]);
            
            if ($validator->fails()) {
                Log::error('Product update validation failed:', [
                    'errors' => $validator->errors()->toArray()
                ]);
                return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            // Generate slug if name changed
            $slug = $product->slug;
            if ($request->name !== $product->name) {
                $slug = Str::slug($request->name);
            }
            
            // Handle product image
            $productImage = $product->image;
            if ($request->has('image')) {
                $imageData = $request->image;
                if (strpos($imageData, 'data:image') === 0) {
                    // Handle base64 encoded image
                    $productImage = $this->uploadBase64Image($imageData, $request->sku);
                } else if ($imageData) {
                    $productImage = $imageData;
                }
            }
            
            // Update product
            $slug = Str::slug($request->name);
            
            // Make sure base_price is not null
            $basePrice = $requestData['base_price'] ?? $request->price ?? $product->base_price;
            if (!$basePrice && $basePrice !== 0) {
                return response()->json([
                    'message' => 'Validation failed', 
                    'errors' => ['base_price' => ['The price field is required and cannot be null.']]
                ], 422);
            }
            
            // Make sure stock_quantity is not null
            $stockQuantity = $requestData['stock_quantity'] ?? $request->stock ?? $product->stock_quantity;
            if (!$stockQuantity && $stockQuantity !== 0) {
                return response()->json([
                    'message' => 'Validation failed', 
                    'errors' => ['stock_quantity' => ['The stock field is required and cannot be null.']]
                ], 422);
            }
            
            // Update product with validated data
            $product->update([
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'base_price' => $basePrice,
                'sale_price' => $request->sale_price,
                'stock_quantity' => $stockQuantity,
                'sku' => $request->sku,
                'category_id' => $request->category_id,
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('is_featured', false),
                'image' => $request->image ?? $product->image
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

            return response()->json(['message' => 'Product updated successfully', 'product' => $product->load('category', 'measurements', 'images')]);
        } catch (\Exception $e) {
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

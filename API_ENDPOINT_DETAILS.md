# M-Mart+ API Endpoint Details

## API Endpoints Summary

The M-Mart+ application has **52 distinct API endpoints** in total, organized into the following categories:

## Endpoint Categories

### 1. Authentication (7 endpoints)
- `POST /api/register` - Register a new user
- `POST /api/login` - Login user
- `POST /api/auth/google` - Google authentication
- `POST /api/auth/apple` - Apple authentication
- `POST /api/forgot-password` - Request password reset
- `POST /api/reset-password` - Reset password
- `POST /api/logout` - Logout user (authenticated)

### 2. Products & Categories (7 endpoints)
- `GET /api/products` - List all products
- `GET /api/products/{product}` - Get product details
- `GET /api/categories` - List all categories
- `GET /api/categories/tree` - Get category tree
- `GET /api/categories/{category}` - Get category details
- `GET /api/categories/{category}/products` - Get products by category
- `POST /api/coupons/validate` - Validate coupon

### 2a. Product Sections (2 endpoints)
- `GET /api/product-sections` - List all active product sections with their products
- `GET /api/products/by-type` - Get products by section type (featured, hot_deals, etc.)

### 3. User Profile (2 endpoints)
- `GET /api/user` - Get current user profile (authenticated)
- `PUT /api/user/profile` - Update user profile (authenticated)

### 4. Cart Management (5 endpoints)
- `GET /api/cart` - Get cart (authenticated)
- `POST /api/cart/add` - Add item to cart (authenticated)
- `PUT /api/cart/update/{item}` - Update cart item (authenticated)
- `DELETE /api/cart/remove/{item}` - Remove cart item (authenticated)
- `DELETE /api/cart/clear` - Clear cart (authenticated)

### 5. Orders (5 endpoints)
- `POST /api/orders` - Create order (authenticated)
- `GET /api/orders` - List user orders (authenticated)
- `GET /api/orders/{order}` - Get order details (authenticated)
- `POST /api/orders/{order}/cancel` - Cancel order (authenticated)
- `GET /api/orders/{order}/pickup-details` - Get order pickup details (authenticated)

### 6. Payments (5 endpoints)
- `GET /api/payments/methods` - Get payment methods (authenticated)
- `POST /api/orders/{order}/payment` - Process payment (authenticated)
- `GET /api/payments/{payment}/verify` - Verify payment (authenticated)
- `GET /api/payments/callback` - Payment callback
- `POST /api/webhooks/flutterwave` - Flutterwave webhook

### 7. Locations (4 endpoints)
- `GET /api/locations` - List all store locations
- `GET /api/locations/nearby` - Find nearby store locations
- `POST /api/admin/locations` - Create location (admin)
- `PUT /api/admin/locations/{location}` - Update location (admin)

### 8. Admin Endpoints (15 endpoints)
- `GET /api/admin/check-auth` - Check admin authentication (admin)

#### Admin Product Management
- `POST /api/admin/products` - Create product (admin)
- `PUT /api/admin/products/{product}` - Update product (admin)
- `DELETE /api/admin/products/{product}` - Delete product (admin)

#### Admin Category Management
- `GET /api/admin/categories` - List categories (admin)
- `POST /api/admin/categories` - Create category (admin)
- `GET /api/admin/categories/{category}` - Get category (admin)
- `PUT /api/admin/categories/{category}` - Update category (admin)
- `DELETE /api/admin/categories/{category}` - Delete category (admin)
- `GET /api/admin/categories-tree` - Get category tree (admin)
- `POST /api/admin/categories-reorder` - Reorder categories (admin)

#### Admin Order Management
- `GET /api/admin/orders` - List all orders (admin)
- `PUT /api/admin/orders/{order}/status` - Update order status (admin)

#### Admin Coupon Management
- `GET /api/admin/coupons` - List all coupons (admin)
- `POST /api/admin/coupons` - Create coupon (admin)
- `PUT /api/admin/coupons/{coupon}` - Update coupon (admin)
- `DELETE /api/admin/coupons/{coupon}` - Delete coupon (admin)

## Authentication

All authenticated endpoints require a valid Bearer token in the Authorization header:

```
Authorization: Bearer {token}
```

Tokens are obtained through the login or social authentication endpoints.

## Admin Access

Admin endpoints require both authentication and admin role. The role check is handled by the `CheckRole` middleware.

## API Documentation

For detailed documentation of each endpoint, including request parameters, response formats, and example usage, please refer to the [API_ENDPOINTS.md](./API_ENDPOINTS.md) file.

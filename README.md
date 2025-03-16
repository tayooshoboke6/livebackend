# M-Mart+ Backend API

The M-Mart+ Backend API provides a comprehensive set of RESTful endpoints for the M-Mart+ eCommerce platform. This API supports user authentication, product management, category management, cart and checkout processes, coupons and discounts, and store pickup and delivery features.

## API Features

### User Authentication
- Multiple authentication methods: Email/Password, Google, and Apple Sign-In
- Token-based authentication using Laravel Sanctum
- Password reset functionality
- User profile management

### Product Management
- CRUD operations for products
- Support for dynamic product measurements
- Product categorization
- Product search and filtering

### Category Management
- Parent-child category structure
- Category-based product filtering

### Cart & Checkout
- Cart management (add, update, remove items)
- Order processing
- Multiple payment methods (Credit Card, PayPal, Stripe, Cash on Delivery)
- Order status tracking

### Coupons & Discounts
- Coupon validation and application
- Percentage and fixed amount discounts
- Product and category-specific coupons

### Store Pickup & Delivery
- Geo-fencing for nearby store locations
- Store pickup option with pickup codes
- Delivery address management
- Enhanced location search with filtering options
- Real-time store open/closed status

## API Endpoints

### Public Routes

#### Authentication
- `POST /api/register` - Register a new user
- `POST /api/login` - Login with email and password
- `POST /api/auth/google` - Authenticate with Google
- `POST /api/auth/apple` - Authenticate with Apple
- `POST /api/forgot-password` - Send password reset link
- `POST /api/reset-password` - Reset password with token

#### Products & Categories
- `GET /api/products` - List all products
- `GET /api/products/{product}` - Get product details
- `GET /api/categories` - List all categories
- `GET /api/categories/{category}` - Get category details
- `GET /api/categories/{category}/products` - Get products in a category

#### Coupons
- `POST /api/coupons/validate` - Validate a coupon code

#### Store Locations
- `GET /api/locations` - List all store locations
- `GET /api/locations/{id}` - Get details for a specific location
- `GET /api/locations/nearby` - Find nearby store locations with filtering options

### Protected Routes (Requires Authentication)

#### User Profile
- `GET /api/user` - Get current user details
- `PUT /api/user/profile` - Update user profile
- `POST /api/logout` - Logout (revoke token)

#### Cart
- `GET /api/cart` - View cart contents
- `POST /api/cart/add` - Add item to cart
- `PUT /api/cart/update/{item}` - Update cart item
- `DELETE /api/cart/remove/{item}` - Remove item from cart
- `DELETE /api/cart/clear` - Clear the cart

#### Orders
- `POST /api/orders` - Create a new order
- `GET /api/orders` - List user's orders
- `GET /api/orders/{order}` - Get order details
- `POST /api/orders/{order}/cancel` - Cancel an order
- `GET /api/orders/{order}/pickup-details` - Get pickup details for an order

#### Payments
- `GET /api/payments/methods` - Get available payment methods
- `POST /api/orders/{order}/payments` - Process payment for an order
- `GET /api/orders/{order}/payments/verify` - Verify payment status

### Admin Routes (Requires Admin Role)

#### Product Management
- `POST /api/admin/products` - Create a product
- `PUT /api/admin/products/{product}` - Update a product
- `DELETE /api/admin/products/{product}` - Delete a product

#### Category Management
- `POST /api/admin/categories` - Create a category
- `PUT /api/admin/categories/{category}` - Update a category
- `DELETE /api/admin/categories/{category}` - Delete a category

#### Order Management
- `GET /api/admin/orders` - List all orders
- `PUT /api/admin/orders/{order}/status` - Update order status

#### Coupon Management
- `GET /api/admin/coupons` - List all coupons
- `POST /api/admin/coupons` - Create a coupon
- `PUT /api/admin/coupons/{coupon}` - Update a coupon
- `DELETE /api/admin/coupons/{coupon}` - Delete a coupon

#### Location Management
- `POST /api/admin/locations` - Create a location
- `PUT /api/admin/locations/{location}` - Update a location
- `DELETE /api/admin/locations/{location}` - Delete a location
- `PUT /api/admin/locations/radius` - Update search radius

#### Payment Management
- `GET /api/admin/orders/{order}/payments` - View payment details
- `PUT /api/admin/orders/{order}/payments/status` - Update payment status

## Admin Routes Authentication

The admin routes in M-Mart+ are protected by role-based authentication using Laravel Sanctum. These routes allow administrators to manage the comprehensive category hierarchy, products, orders, and payment settings.

### Authentication Flow

1. Admin users must first obtain an authentication token by logging in:
   ```
   POST /api/login
   {
     "email": "admin@mmart.com",
     "password": "password123"
   }
   ```

2. The response will include a token that must be included in subsequent requests:
   ```
   {
     "token": "12|w99lx5zdfPLEy0MG8h4RwzDHQbF214MY8fDaz7uF9f9121c9"
   }
   ```

3. All admin routes require the following headers:
   ```
   Authorization: Bearer YOUR_TOKEN_HERE
   Accept: application/json
   ```

### Admin Route Middleware

Admin routes are protected by two middleware layers:
- `auth:sanctum` - Verifies that the request includes a valid authentication token
- `\App\Http\Middleware\CheckRole::class . ':admin'` - Verifies that the authenticated user has the 'admin' role

### Available Admin Routes

- `GET /api/admin/categories-tree` - Get the full category hierarchy with all 12 main categories
- `GET /api/admin/categories` - List all categories
- `POST /api/admin/categories` - Create a new category
- `PUT /api/admin/categories/{id}` - Update an existing category
- `DELETE /api/admin/categories/{id}` - Delete a category
- `POST /api/admin/categories-reorder` - Reorder categories

- `POST /api/admin/products` - Create a new product
- `PUT /api/admin/products/{id}` - Update an existing product
- `DELETE /api/admin/products/{id}` - Delete a product

## Recent Fixes

- Fixed admin routes authentication by properly configuring the role middleware
- Ensured correct Bearer token format in Authorization header for API requests
- Implemented proper role-based access control for admin routes
- Added comprehensive documentation for admin authentication flow
- Fixed 404 error for `/api/categories/tree` endpoint by implementing proper route registration
- Added missing service providers (RouteServiceProvider, AuthServiceProvider, EventServiceProvider)
- Ensured proper route order to handle specific routes before dynamic parameter routes
- Implemented caching for category tree to improve performance
- Enhanced Location API with improved nearby search and open status filtering
- Fixed Brevo email integration for password reset functionality:
  - Implemented custom `BrevoChannel` class to handle email notifications via Brevo API
  - Ensured recipient names are included in email requests to comply with Brevo's API requirements
  - Created a professionally designed HTML email template with M-Mart+ branding
  - Added detailed logging for email sending process to improve debugging
  - Fixed password reset email delivery by properly formatting HTML content

## Email Notifications

M-Mart+ uses Brevo (formerly Sendinblue) for sending transactional emails, including password reset notifications. The integration includes:

### Features

1. **Custom Email Channel**
   - Implemented a `BrevoChannel` class that integrates with Laravel's notification system
   - Properly formats email content according to Brevo API requirements

2. **Branded Email Templates**
   - Professional HTML templates with M-Mart+ branding
   - Responsive design that works on all devices
   - Clear call-to-action buttons for user interactions

3. **Configuration**
   - API-based integration with Brevo
   - SMTP fallback configuration available:
     - Server: smtp-relay.brevo.com
     - Port: 587
     - Encryption: TLS
   - Configured via environment variables:
   ```
   BREVO_API_KEY=your_api_key
   MAIL_FROM_ADDRESS=noreply@mmartplus.com
   MAIL_FROM_NAME="M-Mart+ Support"
   ```

4. **Supported Notification Types**
   - Password reset emails
   - Account verification
   - Order confirmations
   - Shipping updates

### Implementation Details

The email system is implemented using Laravel's notification system with a custom channel for Brevo integration. This ensures:

- Proper HTML formatting for all emails
- Inclusion of recipient names as required by Brevo
- Reliable delivery of transactional emails
- Comprehensive error logging for troubleshooting

## Location API

The M-Mart+ Location API provides comprehensive functionality for managing and querying store locations:

### Features

1. **List All Locations**
   - Retrieve all active store locations
   - Admin users can view both active and inactive locations

2. **Single Location Retrieval**
   - Get detailed information about a specific store location
   - Includes address, contact information, and opening hours

3. **Nearby Location Search**
   - Find stores near a specified geographic location
   - Filter by distance (radius)

4. **Advanced Filtering**
   - Filter by store status (open/closed)
   - Filter by available services
   - Sort by distance or popularity

### API Endpoints

#### Public Endpoints

- `GET /api/locations` - List all active locations
- `GET /api/locations/{id}` - Get details for a specific location
- `GET /api/locations/nearby` - Find nearby locations with parameters:
  - `lat` - Latitude (required)
  - `lng` - Longitude (required)
  - `radius` - Search radius in kilometers (optional, default: 10)
  - `open` - Filter by open status (optional, values: 1 or 0)
  - `services` - Filter by available services (optional, comma-separated list)

#### Admin Endpoints

- `POST /api/admin/locations` - Create a new location
- `PUT /api/admin/locations/{id}` - Update a location
- `DELETE /api/admin/locations/{id}` - Delete a location
- `PUT /api/admin/locations/radius` - Update the default search radius

### Location Model

The Location model includes:

- Geographic coordinates (latitude/longitude)
- Address details (street, city, state, postal code)
- Contact information (phone, email)
- Opening hours for each day of the week
- Available services
- Status (active/inactive)

## Payment Integration

M-Mart+ integrates with Flutterwave for payment processing, supporting multiple payment methods including card payments, bank transfers, and mobile money.

### Features

1. **Multiple Payment Methods**
   - Credit/Debit Cards
   - Bank Transfers
   - Mobile Money
   - USSD

2. **Secure Transactions**
   - PCI-DSS compliant
   - Fraud detection and prevention
   - Encrypted payment information

3. **Seamless Integration**
   - Redirect and inline payment options
   - Webhook support for payment notifications
   - Comprehensive transaction reporting

4. **Multi-currency Support**
   - Support for NGN, USD, GHS, KES, and more
   - Automatic currency conversion

### Implementation

The Flutterwave integration is implemented using the official Flutterwave PHP SDK and includes:

1. **Payment Initialization**
   - Create a payment session with order details
   - Generate a secure payment link

2. **Payment Verification**
   - Verify payment status after completion
   - Update order status based on payment result

3. **Webhook Processing**
   - Process payment notifications from Flutterwave
   - Update order status in real-time

4. **Transaction Management**
   - View and manage transaction history
   - Process refunds when necessary

5. **Configuration via environment variables:**
   ```
   FLUTTERWAVE_PUBLIC_KEY=your_public_key
   FLUTTERWAVE_SECRET_KEY=your_secret_key
   FLUTTERWAVE_ENCRYPTION_KEY=your_encryption_key
   ```

## API Authentication

M-Mart+ uses Laravel Sanctum for API authentication, providing a secure and flexible authentication system for both the web and mobile applications.

### Features

1. **Token-based Authentication**
   - Secure API token generation
   - Token expiration and revocation
   - Multiple tokens per user for different devices

2. **Role-based Access Control**
   - User roles (customer, admin, manager)
   - Permission-based access to resources
   - Middleware for protecting routes

3. **Social Authentication**
   - Google Sign-In integration
   - Apple Sign-In integration
   - Social account linking

### Authentication Flow

1. **Registration**
   - Create a new user account
   - Verify email address
   - Set up profile information

2. **Login**
   - Authenticate with email/password or social provider
   - Receive API token
   - Use token for subsequent requests

3. **Password Reset**
   - Request password reset link
   - Verify reset token
   - Set new password

4. **Token Management**
   - View active sessions
   - Revoke specific tokens
   - Logout (revoke current token)

## Category Structure

M-Mart+ implements a comprehensive category structure with 12 main categories, each with multiple subcategories:

1. Fruits & Vegetables - Fresh produce, organic options
2. Meat & Seafood - Poultry, beef, fish, shellfish
3. Dairy & Eggs - Milk, cheese, yogurt, butter
4. Bakery & Bread - Fresh bread, pastries, cakes
5. Beverages - Soft drinks, juices, water, coffee, tea
6. Snacks & Confectionery - Chips, cookies, chocolates, candies
7. Grains, Rice & Pasta - Various types of rice, pasta, noodles
8. Canned & Packaged Foods - Soups, sauces, canned vegetables
9. Frozen Foods - Frozen meals, vegetables, desserts
10. Personal Care & Health - Toiletries, health supplements
11. Traditional Nigerian Foods & Local Delicacies - Local seasonings, snacks, fermented foods
12. Home & Kitchen Essentials - Cookware, appliances, storage, tableware

Each category has multiple subcategories with detailed descriptions, implemented in a database seeder with proper parent-child relationships.

## Installation & Setup

### Requirements

- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Laravel 9.x
- Node.js and NPM (for frontend assets)

### Installation Steps

1. Clone the repository:
   ```
   git clone https://github.com/your-username/mmart-plus.git
   cd mmart-plus/backend
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Set up environment variables:
   ```
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure database connection in `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=mmart_plus
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Configure Brevo email settings in `.env` file:
   ```
   BREVO_API_KEY=your_api_key
   MAIL_FROM_ADDRESS=noreply@mmartplus.com
   MAIL_FROM_NAME="M-Mart+ Support"
   ```

6. Run migrations and seeders:
   ```
   php artisan migrate --seed
   ```

7. Start the development server:
   ```
   php artisan serve
   ```

## Contributing

Please read the [CONTRIBUTING.md](CONTRIBUTING.md) file for details on our code of conduct and the process for submitting pull requests.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

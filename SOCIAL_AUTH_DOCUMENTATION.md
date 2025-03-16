# M-Mart+ Social Authentication Documentation

## Overview

M-Mart+ supports social authentication via Google and Apple, allowing users to sign up or log in using their existing social accounts. This document outlines the implementation details, API endpoints, and testing procedures.

## Table of Contents

1. [Setup and Configuration](#setup-and-configuration)
2. [API Endpoints](#api-endpoints)
3. [Request/Response Format](#request-response-format)
4. [Testing](#testing)
5. [Troubleshooting](#troubleshooting)

## Setup and Configuration

### Environment Variables

Add the following variables to your `.env` file:

```
# Google Authentication
GOOGLE_CLIENT_ID=608587869148-iv4domm1d4h198027feaplts05iae.apps.googleusercontent.com

# Apple Authentication
APPLE_CLIENT_ID=your-apple-client-id
APPLE_TEAM_ID=your-apple-team-id
APPLE_KEY_ID=your-apple-key-id
APPLE_PRIVATE_KEY=your-apple-private-key
```

### Service Configuration

The service configuration is defined in `config/services.php`:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
],

'apple' => [
    'client_id' => env('APPLE_CLIENT_ID'),
    'team_id' => env('APPLE_TEAM_ID'),
    'key_id' => env('APPLE_KEY_ID'),
    'private_key' => env('APPLE_PRIVATE_KEY'),
],
```

### Required Packages

- Google API Client: `composer require google/apiclient`
- Firebase JWT: `composer require firebase/php-jwt`

## API Endpoints

### Google Authentication

**Endpoint:** `POST /api/auth/google`

**Description:** Authenticates a user with a Google ID token. If the user doesn't exist, a new account is created.

**Request Parameters:**
- `id_token` (required): A valid Google ID token obtained from the Google Sign-In API

**Response:**
- Success (200 OK):
  ```json
  {
    "message": "Google authentication successful",
    "user": {
      "id": 5,
      "name": "User Name",
      "email": "user@example.com",
      "role": "customer",
      "created_at": "2025-03-12T03:09:11.000000Z",
      "updated_at": "2025-03-12T03:09:11.000000Z"
    },
    "token": "auth_token_string"
  }
  ```
- Error (401 Unauthorized):
  ```json
  {
    "message": "Invalid Google token"
  }
  ```

### Apple Authentication

**Endpoint:** `POST /api/auth/apple`

**Description:** Authenticates a user with an Apple identity token. If the user doesn't exist, a new account is created.

**Request Parameters:**
- `identity_token` (required): A valid Apple identity token
- `name` (optional): User's name (only provided during first sign-in)

**Response:**
- Success (200 OK):
  ```json
  {
    "message": "Apple authentication successful",
    "user": {
      "id": 6,
      "name": "User Name",
      "email": "user@privaterelay.appleid.com",
      "role": "customer",
      "created_at": "2025-03-12T03:09:11.000000Z",
      "updated_at": "2025-03-12T03:09:11.000000Z"
    },
    "token": "auth_token_string"
  }
  ```
- Error (401 Unauthorized):
  ```json
  {
    "message": "Invalid Apple token"
  }
  ```

### Test Endpoint (Development Only)

**Endpoint:** `POST /api/auth/google/test`

**Description:** A test endpoint that simulates Google authentication without requiring a valid Google token. This endpoint is for development and testing purposes only.

**Request Parameters:**
- `email` (required): User's email address
- `name` (required): User's name

**Response:**
- Success (200 OK):
  ```json
  {
    "message": "Test Google authentication successful",
    "user": {
      "id": 5,
      "name": "Tayo Oshoboke",
      "email": "tayooshoboke6@gmail.com",
      "role": "customer",
      "created_at": "2025-03-12T03:09:11.000000Z",
      "updated_at": "2025-03-12T03:09:11.000000Z"
    },
    "token": "auth_token_string"
  }
  ```

## Testing

### Testing with Postman

#### Google Authentication (Production)

1. Obtain a Google ID token:
   - Use the Google Sign-In button on a web page
   - Use the OAuth 2.0 Playground (with your client ID configured)

2. Send a POST request to `/api/auth/google` with:
   ```json
   {
     "id_token": "YOUR_GOOGLE_ID_TOKEN"
   }
   ```

#### Google Authentication (Development)

For development and testing, use the test endpoint:

1. Send a POST request to `/api/auth/google/test` with:
   ```json
   {
     "email": "user@example.com",
     "name": "User Name"
   }
   ```

#### Apple Authentication

1. Obtain an Apple identity token from an iOS device or web authentication
2. Send a POST request to `/api/auth/apple` with:
   ```json
   {
     "identity_token": "YOUR_APPLE_IDENTITY_TOKEN",
     "name": "User Name" // Optional, only for first sign-in
   }
   ```

### Using the Authentication Token

After successful authentication, use the returned token in the Authorization header for subsequent API requests:

```
Authorization: Bearer YOUR_AUTH_TOKEN
```

## Troubleshooting

### Common Issues

1. **Invalid Google Token**
   - Ensure the token is fresh (Google tokens expire after ~1 hour)
   - Verify that the client ID used to generate the token matches the one in your `.env` file
   - Check that the token was generated for your application

2. **Invalid Apple Token**
   - Ensure the token is fresh
   - Verify that the Apple client ID in your `.env` file is correct
   - Check that the token was generated for your application

### Debugging

The application logs detailed information about token verification attempts and errors. Check the Laravel logs for more information:

```
php artisan tail
```

Look for entries related to Google or Apple authentication to diagnose issues.

## Security Considerations

1. **Token Verification**
   - All tokens are verified with their respective providers before authentication
   - The application validates the token's issuer, audience, and expiration

2. **User Creation**
   - Users created through social authentication have a random secure password
   - Email addresses are verified by the social provider

3. **Token Storage**
   - Authentication tokens are stored securely using Laravel Sanctum
   - Tokens can be revoked by logging out

## Production Setup

For production deployment:

1. Configure proper OAuth consent screens for Google and Apple
2. Set up authorized domains and redirect URIs
3. Implement proper frontend integration with the social login buttons
4. Remove or disable the test endpoint (`/api/auth/google/test`)

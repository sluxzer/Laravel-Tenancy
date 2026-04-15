# SaaS Tenancy Starter - API Documentation

## Table of Contents

- [Overview](#overview)
- [Authentication](#authentication)
- [Authentication (Tenant)](#authentication-tenant)
- [Billing](#billing)
  - [Subscriptions](#subscriptions)
  - [Invoices](#invoices)
  - [Payments](#payments)
  - [Transactions](#transactions)
  - [Refunds](#refunds)
- [Management](#management)
- [Analytics](#analytics)
- [Audit Logs](#audit-logs)
- [Webhooks](#webhooks)
- [Usage](#usage)
- [Reports](#reports)
- [Export/Import](#exportimport)
- [GDPR](#gdpr)
- [Dashboard](#dashboard)
- [Admin](#admin)
- [External Webhook Handlers](#external-webhook-handlers)

---

## Overview

### Base URL

```
https://your-domain.com/api
```

### Version

Current API Version: 1.0

### Response Format

All API responses follow this standard format:

**Success Response:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Success message (optional)"
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error description",
  "error": "error_code",
  "errors": { ... } // Validation errors (if applicable)
}
```

### Pagination

Paginated responses include:

```json
{
  "success": true,
  "data": [ ... ],
  "pagination": {
    "total": 100,
    "per_page": 20,
    "current_page": 1,
    "last_page": 5
  }
}
```

### Common Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `per_page` | integer | Items per page (default: 20) |
| `page` | integer | Page number (default: 1) |
| `sort` | string | Sort field |
| `order` | string | Sort direction: `asc` or `desc` |
| `search` | string | Search query |

### Rate Limiting

- Authentication endpoints: 5 requests per minute
- Password reset endpoints: 3 requests per minute
- Webhook endpoints: 60 requests per minute
- Other endpoints: No explicit limit (configurable)

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Internal Server Error |

---

## Authentication

### Register (Global)

Register a new user account. This endpoint creates a new tenant and admin user.

```
POST /api/auth/register
```

**Rate Limit:** 5 requests/minute

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "company_name": "Acme Inc"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": { ... },
    "tenant": { ... }
  }
}
```

### Login

Authenticate with email and password.

```
POST /api/auth/login
```

**Rate Limit:** 5 requests/minute

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "Password123!"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "access_token_string",
    "user": { ... }
  }
}
```

### Logout

Invalidate the current access token.

```
POST /api/auth/logout
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

### Forgot Password

Request a password reset link.

```
POST /api/auth/forgot-password
```

**Rate Limit:** 3 requests/minute

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password reset link sent"
}
```

### Reset Password

Reset password using the token from the email.

```
POST /api/auth/reset-password
```

**Rate Limit:** 3 requests/minute

**Request Body:**
```json
{
  "token": "reset_token",
  "email": "john@example.com",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

### Verify Email

Request email verification notification.

```
POST /api/auth/verify-email
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Verification link sent"
}
```

### Confirm Password

Confirm the user's password for sensitive operations.

```
POST /api/auth/confirm-password
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "password": "Password123!"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "confirmed": true
  }
}
```

### Two-Factor Authentication

#### Enable 2FA

```
POST /api/auth/two-factor/enable
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "2FA enabled",
  "data": {
    "recovery_codes": ["code1", "code2", ...]
  }
}
```

#### Disable 2FA

```
POST /api/auth/two-factor/disable
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "2FA disabled"
}
```

#### Get QR Code

```
GET /api/auth/two-factor/qrcode
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
Returns SVG QR code for authenticator app.

#### Get Recovery Codes

```
GET /api/auth/two-factor/recovery-codes
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "codes": ["code1", "code2", ...]
  }
}
```

#### Regenerate Recovery Codes

```
POST /api/auth/two-factor/recovery-codes
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Recovery Code

```
DELETE /api/auth/two-factor/recovery-codes/{code}
```

**Headers:** `Authorization: Bearer {token}`

---

## Authentication (Tenant)

All tenant-scoped authentication endpoints are prefixed with `/{tenant}`.

### Register (Tenant)

```
POST /api/{tenant}/auth/register
```

**Rate Limit:** 5 requests/minute

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

### Login (Tenant)

```
POST /api/{tenant}/auth/login
```

**Rate Limit:** 5 requests/minute

### Logout (Tenant)

```
POST /api/{tenant}/auth/logout
```

**Headers:** `Authorization: Bearer {token}`

---

## Billing

All billing endpoints are tenant-scoped under `/{tenant}/billing`.

### Subscriptions

#### List Subscriptions

```
GET /api/{tenant}/billing/subscriptions
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `status` (optional): Filter by status (`active`, `trialing`, `paused`, `cancelled`)
- `per_page` (optional): Items per page (default: 20)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "status": "active",
      "billing_cycle": "monthly",
      "current_period_start": "2024-01-01T00:00:00Z",
      "current_period_end": "2024-02-01T00:00:00Z",
      "trial_ends_at": "2024-01-15T00:00:00Z",
      "grace_period_ends_at": null,
      "cancelled_at": null,
      "cancellation_reason": null,
      "metadata": {},
      "is_active": true,
      "is_trialing": true,
      "is_paused": false,
      "is_cancelled": false,
      "can_pause": true,
      "can_cancel": true,
      "can_upgrade": true,
      "can_downgrade": true,
      "days_remaining": 15,
      "plan": { ... },
      "user": { ... },
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

#### Get Current Subscription

```
GET /api/{tenant}/billing/subscriptions/{subscription}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Subscription

```
POST /api/{tenant}/billing/subscriptions
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "plan_id": 1,
  "billing_cycle": "monthly",
  "user_id": 5,
  "metadata": {}
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Subscription created successfully",
  "data": { ... }
}
```

#### Update Subscription

```
PUT /api/{tenant}/billing/subscriptions/{subscription}
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "metadata": {}
}
```

#### Upgrade Subscription

```
POST /api/{tenant}/billing/subscriptions/{subscription}/upgrade
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "plan_id": 2
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Subscription upgraded successfully",
  "data": { ... }
}
```

#### Downgrade Subscription

```
POST /api/{tenant}/billing/subscriptions/{subscription}/downgrade
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "plan_id": 1
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Subscription downgrade scheduled",
  "data": { ... }
}
```

#### Pause Subscription

```
POST /api/{tenant}/billing/subscriptions/{subscription}/pause
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Subscription paused successfully",
  "data": { ... }
}
```

#### Resume Subscription

```
POST /api/{tenant}/billing/subscriptions/{subscription}/resume
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Subscription resumed successfully",
  "data": { ... }
}
```

#### Cancel Subscription

```
POST /api/{tenant}/billing/subscriptions/{subscription}/cancel
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "reason": "No longer needed"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Subscription cancelled successfully",
  "data": { ... }
}
```

#### Renew Subscription

```
POST /api/{tenant}/billing/subscriptions/{subscription}/renew
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Subscription renewed successfully",
  "data": { ... }
}
```

#### Apply Voucher

```
POST /api/{tenant}/billing/subscriptions/{subscription}/apply-voucher
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "code": "VOUCHER20"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Voucher applied successfully"
}
```

---

### Invoices

#### List Invoices

```
GET /api/{tenant}/billing/invoices
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `paid`, `overdue`, `cancelled`)
- `search` (optional): Search by invoice number
- `per_page` (optional): Items per page (default: 20)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "number": "INV-2024-001",
      "tenant_id": 1,
      "user_id": 5,
      "subscription_id": 1,
      "status": "pending",
      "due_date": "2024-02-15T00:00:00Z",
      "subtotal": 100.00,
      "tax_amount": 10.00,
      "total_amount": 110.00,
      "currency": "USD",
      "notes": null,
      "items": [
        {
          "id": 1,
          "invoice_id": 1,
          "description": "Monthly subscription",
          "quantity": 1,
          "unit_price": 100.00,
          "total_price": 100.00
        }
      ],
      "subscription": { ... },
      "transactions": [],
      "created_at": "2024-01-15T00:00:00Z",
      "updated_at": "2024-01-15T00:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

#### Get Invoice

```
GET /api/{tenant}/billing/invoices/{invoice}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Invoice

```
POST /api/{tenant}/billing/invoices
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "user_id": 5,
  "subscription_id": 1,
  "due_date": "2024-02-15",
  "items": [
    {
      "description": "Service fee",
      "quantity": 1,
      "unit_price": 100.00
    }
  ],
  "currency": "USD",
  "notes": "Optional notes"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Invoice created successfully",
  "data": { ... }
}
```

#### Update Invoice

```
PUT /api/{tenant}/billing/invoices/{invoice}
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "due_date": "2024-02-20",
  "status": "pending",
  "notes": "Updated notes"
}
```

#### Delete Invoice

```
DELETE /api/{tenant}/billing/invoices/{invoice}
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Invoice deleted successfully"
}
```

**Note:** Cannot delete paid invoices.

#### Add Invoice Item

```
POST /api/{tenant}/billing/invoices/{id}/items
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "description": "Additional service",
  "quantity": 1,
  "unit_price": 50.00
}
```

#### Remove Invoice Item

```
DELETE /api/{tenant}/billing/invoices/{id}/items/{itemId}
```

**Headers:** `Authorization: Bearer {token}`

#### Download Invoice

```
GET /api/{tenant}/billing/invoices/{id}/download
```

**Headers:** `Authorization: Bearer {token}`

**Response:** PDF file

#### Send Invoice

```
POST /api/{tenant}/billing/invoices/{id}/send
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Invoice sent successfully"
}
```

---

### Payments

#### List Payments

```
GET /api/{tenant}/billing/payments
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `status` (optional): Filter by status
- `provider` (optional): Filter by payment provider
- `per_page` (optional): Items per page (default: 20)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "user_id": 5,
      "invoice_id": 1,
      "subscription_id": 1,
      "type": "payment",
      "provider": "stripe",
      "provider_transaction_id": "pi_123456",
      "amount": 110.00,
      "currency": "USD",
      "status": "completed",
      "description": "Invoice payment",
      "metadata": {},
      "invoice": { ... },
      "subscription": { ... },
      "created_at": "2024-01-15T00:00:00Z",
      "updated_at": "2024-01-15T00:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

#### Get Payment

```
GET /api/{tenant}/billing/payments/{payment}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Payment

```
POST /api/{tenant}/billing/payments
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "invoice_id": 1,
  "subscription_id": 1,
  "amount": 110.00,
  "currency": "USD",
  "gateway": "stripe",
  "transaction_id": "pi_123456",
  "description": "Payment for invoice",
  "metadata": {},
  "payment_token": "pm_123456"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Payment created successfully",
  "data": { ... }
}
```

#### Process Payment

```
POST /api/{tenant}/billing/payments/{payment}/process
```

**Headers:** `Authorization: Bearer {token}`

#### Cancel Payment

```
DELETE /api/{tenant}/billing/payments/{payment}
```

**Headers:** `Authorization: Bearer {token}`

---

### Payment Methods

#### Get Payment Methods

```
GET /api/{tenant}/billing/payments/methods
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "pm_123456",
      "type": "card",
      "brand": "visa",
      "last4": "4242",
      "expiry": "12/25",
      "is_default": true
    }
  ]
}
```

#### Add Payment Method

```
POST /api/{tenant}/billing/payments/methods
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "payment_method": "card",
  "payment_token": "pm_123456",
  "is_default": false
}
```

#### Remove Payment Method

```
DELETE /api/{tenant}/billing/payments/methods/{methodId}
```

**Headers:** `Authorization: Bearer {token}`

#### Set Default Payment Method

```
POST /api/{tenant}/billing/payments/methods/{methodId}/default
```

**Headers:** `Authorization: Bearer {token}`

---

### Transactions

#### List Transactions

```
GET /api/{tenant}/billing/transactions
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `type` (optional): Filter by type (`charge`, `refund`, `credit`, `debit`, `payment`)
- `status` (optional): Filter by status
- `per_page` (optional): Items per page (default: 20)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "user_id": 5,
      "invoice_id": 1,
      "subscription_id": 1,
      "type": "payment",
      "provider": "stripe",
      "provider_transaction_id": "pi_123456",
      "amount": 110.00,
      "currency": "USD",
      "status": "completed",
      "description": "Payment",
      "metadata": {},
      "invoice": { ... },
      "subscription": { ... },
      "created_at": "2024-01-15T00:00:00Z",
      "updated_at": "2024-01-15T00:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

#### Get Transaction

```
GET /api/{tenant}/billing/transactions/{transaction}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Transaction

```
POST /api/{tenant}/billing/transactions
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "invoice_id": 1,
  "subscription_id": 1,
  "type": "payment",
  "provider": "stripe",
  "provider_transaction_id": "pi_123456",
  "amount": 110.00,
  "currency": "USD",
  "description": "Payment",
  "status": "completed",
  "metadata": {}
}
```

#### Update Transaction

```
PUT /api/{tenant}/billing/transactions/{transaction}
```

**Headers:** `Authorization: Bearer {token}`

#### Get Transaction Summary

```
GET /api/{tenant}/billing/transactions/summary
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `start_date` (optional): ISO 8601 date
- `end_date` (optional): ISO 8601 date

**Response (200):**
```json
{
  "success": true,
  "data": {
    "summary": [
      {
        "type": "payment",
        "total_amount": 1000.00,
        "count": 10,
        "by_status": [
          {
            "status": "completed",
            "total_amount": 900.00,
            "count": 9
          }
        ]
      }
    ],
    "period": {
      "start": "2024-01-01T00:00:00Z",
      "end": "2024-01-31T23:59:59Z"
    }
  }
}
```

---

### Refunds

#### List Refunds

```
GET /api/{tenant}/billing/refunds
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `status` (optional): Filter by status
- `per_page` (optional): Items per page (default: 20)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "user_id": 5,
      "transaction_id": 1,
      "invoice_id": 1,
      "amount": 110.00,
      "currency": "USD",
      "status": "pending",
      "reason": "Service not provided",
      "notes": null,
      "processed_by_id": null,
      "processed_at": null,
      "provider_refund_id": null,
      "transaction": { ... },
      "invoice": { ... },
      "processed_by": null,
      "created_at": "2024-01-15T00:00:00Z",
      "updated_at": "2024-01-15T00:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

#### Get Refund

```
GET /api/{tenant}/billing/refunds/{refund}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Refund

```
POST /api/{tenant}/billing/refunds
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "transaction_id": 1,
  "amount": 110.00,
  "reason": "Service issue",
  "notes": "Optional notes"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Refund created successfully",
  "data": { ... }
}
```

#### Process Refund

```
POST /api/{tenant}/billing/refunds/{id}/process
```

**Headers:** `Authorization: Bearer {token}`

#### Cancel Refund

```
POST /api/{tenant}/billing/refunds/{id}/cancel
```

**Headers:** `Authorization: Bearer {token}`

#### Get Refund Summary

```
GET /api/{tenant}/billing/refunds/summary
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `start_date` (optional): ISO 8601 date
- `end_date` (optional): ISO 8601 date

---

### Currency & Tax

#### Get Exchange Rate

```
GET /api/{tenant}/billing/currency/exchange-rate
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `from` (required): Base currency code (e.g., USD)
- `to` (required): Target currency code (e.g., EUR)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "from": "USD",
    "to": "EUR",
    "rate": 0.85,
    "updated_at": "2024-01-15T00:00:00Z"
  }
}
```

#### Convert Currency

```
POST /api/{tenant}/billing/currency/convert
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "amount": 100.00,
  "from": "USD",
  "to": "EUR"
}
```

#### Get Tax Settings

```
GET /api/{tenant}/billing/tax/settings
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "country": "US",
    "region": "CA",
    "tax_rate": 0.0825,
    "tax_id": null
  }
}
```

#### Update Tax Settings

```
POST /api/{tenant}/billing/tax/settings
```

**Headers:** `Authorization: Bearer {token}`

#### Calculate Tax

```
POST /api/{tenant}/billing/tax/calculate
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "amount": 100.00,
  "country": "US",
  "region": "CA"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "subtotal": 100.00,
    "tax_rate": 0.0825,
    "tax_amount": 8.25,
    "total": 108.25
  }
}
```

#### Create Tax From Country

```
POST /api/{tenant}/billing/tax/create-from-country
```

**Headers:** `Authorization: Bearer {token}`

---

## Management

### User Management

#### Get Current User

```
GET /api/{tenant}/user
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": null,
    "avatar": null,
    "roles": ["admin"],
    "permissions": ["create", "read", "update", "delete"]
  }
}
```

#### Update Profile

```
PUT /api/{tenant}/user
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "phone": "+1234567890",
  "avatar": "https://example.com/avatar.jpg"
}
```

#### Change Password

```
POST /api/{tenant}/user/change-password
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "current_password": "OldPassword123!",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

#### Get API Tokens

```
GET /api/{tenant}/user/tokens
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "My App",
      "abilities": ["*"],
      "last_used_at": "2024-01-15T00:00:00Z",
      "created_at": "2024-01-01T00:00:00Z",
      "expires_at": "2024-12-31T23:59:59Z"
    }
  ]
}
```

#### Create API Token

```
POST /api/{tenant}/user/tokens
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "name": "My App",
  "abilities": ["*"],
  "expires_at": "2024-12-31"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Token created successfully",
  "data": {
    "token": "access_token_here",
    "abilities": ["*"],
    "expires_at": "2024-12-31T23:59:59Z"
  }
}
```

#### Delete API Token

```
DELETE /api/{tenant}/user/tokens/{id}
```

**Headers:** `Authorization: Bearer {token}`

#### Get Roles & Permissions

```
GET /api/{tenant}/user/roles-permissions
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "roles": ["admin"],
    "permissions": ["create", "read", "update", "delete"]
  }
}
```

#### Check Permission

```
GET /api/{tenant}/user/has-permission?permission=create
```

**Headers:** `Authorization: Bearer {token}`

#### Check Role

```
GET /api/{tenant}/user/has-role?role=admin
```

**Headers:** `Authorization: Bearer {token}`

---

### Notifications

#### List Notifications

```
GET /api/{tenant}/management/notifications
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `read` (optional): Filter by read status
- `type` (optional): Filter by type
- `per_page` (optional): Items per page (default: 20)

#### Get Notification

```
GET /api/{tenant}/management/notifications/{notification}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Notification

```
POST /api/{tenant}/management/notifications
```

**Headers:** `Authorization: Bearer {token}`

#### Send Notification

```
POST /api/{tenant}/management/notifications/{id}/send
```

**Headers:** `Authorization: Bearer {token}`

#### Mark as Read

```
POST /api/{tenant}/management/notifications/{id}/read
```

**Headers:** `Authorization: Bearer {token}`

#### Mark All as Read

```
POST /api/{tenant}/management/notifications/read-all
```

**Headers:** `Authorization: Bearer {token}`

#### Get Unread Count

```
GET /api/{tenant}/management/notifications/unread-count
```

**Headers:** `Authorization: Bearer {token}`

#### Bulk Send

```
POST /api/{tenant}/management/notifications/bulk-send
```

**Headers:** `Authorization: Bearer {token}`

---

### Notification Preferences

#### List Preferences

```
GET /api/{tenant}/management/notification-preferences
```

**Headers:** `Authorization: Bearer {token}`

#### Create Preference

```
POST /api/{tenant}/management/notification-preferences
```

**Headers:** `Authorization: Bearer {token}`

#### Update Preference

```
PUT /api/{tenant}/management/notification-preferences/{notification_preference}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Preference

```
DELETE /api/{tenant}/management/notification-preferences/{notification_preference}
```

**Headers:** `Authorization: Bearer {token}`

#### Update Global Preferences

```
PUT /api/{tenant}/management/notification-preferences/global
```

**Headers:** `Authorization: Bearer {token}`

#### Bulk Update

```
POST /api/{tenant}/management/notification-preferences/bulk-update
```

**Headers:** `Authorization: Bearer {token}`

---

### Activities

#### List Activities

```
GET /api/{tenant}/management/activities
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `type` (optional): Filter by activity type
- `per_page` (optional): Items per page (default: 20)

#### Get Activity

```
GET /api/{tenant}/management/activities/{activity}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Activity

```
POST /api/{tenant}/management/activities
```

**Headers:** `Authorization: Bearer {token}`

#### Get Activity Feed

```
GET /api/{tenant}/management/activities/feed
```

**Headers:** `Authorization: Bearer {token}`

#### Get Recent Activities

```
GET /api/{tenant}/management/activities/recent
```

**Headers:** `Authorization: Bearer {token}`

#### Get Activities by Type

```
GET /api/{tenant}/management/activities/type/{type}
```

**Headers:** `Authorization: Bearer {token}`

#### Get Summary

```
GET /api/{tenant}/management/activities/summary
```

**Headers:** `Authorization: Bearer {token}`

#### Export Activities

```
POST /api/{tenant}/management/activities/export
```

**Headers:** `Authorization: Bearer {token}`

---

### Invitations

#### List Invitations

```
GET /api/{tenant}/management/invitations
```

**Headers:** `Authorization: Bearer {token}`

#### Create Invitation

```
POST /api/{tenant}/management/invitations
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "email": "user@example.com",
  "role": "member",
  "expires_at": "2024-02-15"
}
```

#### Get Invitation

```
GET /api/{tenant}/management/invitations/{invitation}
```

**Headers:** `Authorization: Bearer {token}`

#### Accept Invitation

```
GET /api/{tenant}/management/invitations/accept/{token}
```

#### Resend Invitation

```
POST /api/{tenant}/management/invitations/{id}/resend
```

**Headers:** `Authorization: Bearer {token}`

#### Cancel Invitation

```
POST /api/{tenant}/management/invitations/{id}/cancel
```

**Headers:** `Authorization: Bearer {token}`

---

### Feature Flags

#### List Feature Flags

```
GET /api/{tenant}/management/feature-flags
```

**Headers:** `Authorization: Bearer {token}`

#### Create Feature Flag

```
POST /api/{tenant}/management/feature-flags
```

**Headers:** `Authorization: Bearer {token}`

#### Get Feature Flag

```
GET /api/{tenant}/management/feature-flags/{feature_flag}
```

**Headers:** `Authorization: Bearer {token}`

#### Update Feature Flag

```
PUT /api/{tenant}/management/feature-flags/{feature_flag}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Feature Flag

```
DELETE /api/{tenant}/management/feature-flags/{feature_flag}
```

**Headers:** `Authorization: Bearer {token}`

#### Check Feature Flag

```
POST /api/{tenant}/management/feature-flags/check
```

**Headers:** `Authorization: Bearer {token}`

#### Batch Check

```
POST /api/{tenant}/management/feature-flags/batch-check
```

**Headers:** `Authorization: Bearer {token}`

#### Get Enabled

```
GET /api/{tenant}/management/feature-flags/enabled
```

**Headers:** `Authorization: Bearer {token}`

---

## Analytics

### List Events

```
GET /api/{tenant}/analytics/events
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `type` (optional): Filter by event type
- `start_date` (optional): ISO 8601 date
- `end_date` (optional): ISO 8601 date
- `per_page` (optional): Items per page (default: 20)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "user_id": 5,
      "name": "user_login",
      "type": "action",
      "data": { ... },
      "created_at": "2024-01-15T00:00:00Z",
      "updated_at": "2024-01-15T00:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

#### Get Event

```
GET /api/{tenant}/analytics/events/{event}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Event

```
POST /api/{tenant}/analytics/events
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "name": "user_login",
  "type": "action",
  "data": {
    "ip": "192.168.1.1",
    "user_agent": "Mozilla/5.0..."
  }
}
```

#### Update Event

```
PUT /api/{tenant}/analytics/events/{event}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Event

```
DELETE /api/{tenant}/analytics/events/{event}
```

**Headers:** `Authorization: Bearer {token}`

#### Get Summary

```
GET /api/{tenant}/analytics/events/summary
```

**Headers:** `Authorization: Bearer {token}`

#### Get Events by Name

```
GET /api/{tenant}/analytics/events/type/{type}
```

**Headers:** `Authorization: Bearer {token}`

#### Get Event Names

```
GET /api/{tenant}/analytics/events/names
```

**Headers:** `Authorization: Bearer {token}`

#### Export Events

```
POST /api/{tenant}/analytics/events/export
```

**Headers:** `Authorization: Bearer {token}`

#### Track Event

```
POST /api/{tenant}/analytics/track
```

**Headers:** `Authorization: Bearer {token}`

#### Batch Track

```
POST /api/{tenant}/analytics/track/batch
```

**Headers:** `Authorization: Bearer {token}`

---

## Audit Logs

### List Logs

```
GET /api/{tenant}/audit/logs
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `user_id` (optional): Filter by user
- `model` (optional): Filter by model type
- `action` (optional): Filter by action
- `per_page` (optional): Items per page (default: 20)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "user_id": 5,
      "model_type": "App\\Models\\User",
      "model_id": 5,
      "action": "update",
      "changes": { ... },
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2024-01-15T00:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

#### Get Log

```
GET /api/{tenant}/audit/logs/{log}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Log

```
POST /api/{tenant}/audit/logs
```

**Headers:** `Authorization: Bearer {token}`

#### Update Log

```
PUT /api/{tenant}/audit/logs/{log}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Log

```
DELETE /api/{tenant}/audit/logs/{log}
```

**Headers:** `Authorization: Bearer {token}`

#### Get Summary

```
GET /api/{tenant}/audit/logs/summary
```

**Headers:** `Authorization: Bearer {token}`

#### Get Logs for Model

```
GET /api/{tenant}/audit/logs/model?model=User&id=5
```

**Headers:** `Authorization: Bearer {token}`

#### Get Recent Logs

```
GET /api/{tenant}/audit/logs/recent
```

**Headers:** `Authorization: Bearer {token}`

#### Export Logs

```
POST /api/{tenant}/audit/logs/export
```

**Headers:** `Authorization: Bearer {token}`

---

## Webhooks

### List Webhooks

```
GET /api/{tenant}/webhooks/webhooks
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `event` (optional): Filter by event type
- `per_page` (optional): Items per page (default: 20)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "name": "Payment Webhook",
      "url": "https://example.com/webhook",
      "events": ["payment.completed", "payment.failed"],
      "is_active": true,
      "secret": "whsec_abc123...",
      "created_at": "2024-01-15T00:00:00Z",
      "updated_at": "2024-01-15T00:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

#### Get Webhook

```
GET /api/{tenant}/webhooks/webhooks/{webhook}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Webhook

```
POST /api/{tenant}/webhooks/webhooks
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "name": "Payment Webhook",
  "url": "https://example.com/webhook",
  "events": ["payment.completed", "payment.failed"]
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Webhook created successfully",
  "data": {
    "id": 1,
    "name": "Payment Webhook",
    "url": "https://example.com/webhook",
    "events": ["payment.completed", "payment.failed"],
    "is_active": true,
    "secret": "whsec_abc123..."
  }
}
```

#### Update Webhook

```
PUT /api/{tenant}/webhooks/webhooks/{webhook}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Webhook

```
DELETE /api/{tenant}/webhooks/webhooks/{webhook}
```

**Headers:** `Authorization: Bearer {token}`

#### Toggle Webhook

```
POST /api/{tenant}/webhooks/webhooks/{webhook}/toggle
```

**Headers:** `Authorization: Bearer {token}`

#### Test Webhook

```
POST /api/{tenant}/webhooks/webhooks/{webhook}/test
```

**Headers:** `Authorization: Bearer {token}`

#### Regenerate Secret

```
POST /api/{tenant}/webhooks/webhooks/{webhook}/regenerate-secret
```

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Secret regenerated successfully",
  "data": {
    "secret": "whsec_xyz789..."
  }
}
```

#### Get Webhook Events

```
GET /api/{tenant}/webhooks/webhooks/{webhook}/events
```

**Headers:** `Authorization: Bearer {token}`

#### Retry Event

```
POST /api/{tenant}/webhooks/webhooks/{webhook}/events/{eventId}/retry
```

**Headers:** `Authorization: Bearer {token}`

---

## Usage

### Metrics

#### List Metrics

```
GET /api/{tenant}/usage/metrics
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `type` (optional): Filter by type
- `per_page` (optional): Items per page (default: 20)

#### Get Metric

```
GET /api/{tenant}/usage/metrics/{metric}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Metric

```
POST /api/{tenant}/usage/metrics
```

**Headers:** `Authorization: Bearer {token}`

#### Update Metric

```
PUT /api/{tenant}/usage/metrics/{metric}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Metric

```
DELETE /api/{tenant}/usage/metrics/{metric}
```

**Headers:** `Authorization: Bearer {token}`

#### Get Summary

```
GET /api/{tenant}/usage/metrics/summary
```

**Headers:** `Authorization: Bearer {token}`

#### Get Metrics by Type

```
GET /api/{tenant}/usage/metrics/type/{type}
```

**Headers:** `Authorization: Bearer {token}`

#### Bulk Store

```
POST /api/{tenant}/usage/metrics/bulk
```

**Headers:** `Authorization: Bearer {token}`

---

### Pricing

#### List Pricing

```
GET /api/{tenant}/usage/pricing
```

**Headers:** `Authorization: Bearer {token}`

#### Get Pricing

```
GET /api/{tenant}/usage/pricing/{pricing}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Pricing

```
POST /api/{tenant}/usage/pricing
```

**Headers:** `Authorization: Bearer {token}`

#### Update Pricing

```
PUT /api/{tenant}/usage/pricing/{pricing}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Pricing

```
DELETE /api/{tenant}/usage/pricing/{pricing}
```

**Headers:** `Authorization: Bearer {token}`

#### Calculate

```
POST /api/{tenant}/usage/pricing/calculate
```

**Headers:** `Authorization: Bearer {token}`

---

### Alerts

#### List Alerts

```
GET /api/{tenant}/usage/alerts
```

**Headers:** `Authorization: Bearer {token}`

#### Get Alert

```
GET /api/{tenant}/usage/alerts/{alert}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Alert

```
POST /api/{tenant}/usage/alerts
```

**Headers:** `Authorization: Bearer {token}`

#### Update Alert

```
PUT /api/{tenant}/usage/alerts/{alert}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Alert

```
DELETE /api/{tenant}/usage/alerts/{alert}
```

**Headers:** `Authorization: Bearer {token}`

#### Check

```
POST /api/{tenant}/usage/alerts/check
```

**Headers:** `Authorization: Bearer {token}`

#### Trigger

```
POST /api/{tenant}/usage/alerts/{id}/trigger
```

**Headers:** `Authorization: Bearer {token}`

#### Reset

```
POST /api/{tenant}/usage/alerts/{id}/reset
```

**Headers:** `Authorization: Bearer {token}`

---

## Reports

### Custom Reports

#### List Custom Reports

```
GET /api/{tenant}/reports/custom-reports
```

**Headers:** `Authorization: Bearer {token}`

#### Get Custom Report

```
GET /api/{tenant}/reports/custom-reports/{custom_report}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Custom Report

```
POST /api/{tenant}/reports/custom-reports
```

**Headers:** `Authorization: Bearer {token}`

#### Update Custom Report

```
PUT /api/{tenant}/reports/custom-reports/{custom_report}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Custom Report

```
DELETE /api/{tenant}/reports/custom-reports/{custom_report}
```

**Headers:** `Authorization: Bearer {token}`

#### Run Custom Report

```
POST /api/{tenant}/reports/custom-reports/{id}/run
```

**Headers:** `Authorization: Bearer {token}`

#### Schedule Custom Report

```
POST /api/{tenant}/reports/custom-reports/{id}/schedule
```

**Headers:** `Authorization: Bearer {token}`

#### Duplicate Custom Report

```
POST /api/{tenant}/reports/custom-reports/{id}/duplicate
```

**Headers:** `Authorization: Bearer {token}`

---

### Report Templates

#### List Templates

```
GET /api/{tenant}/reports/report-templates
```

**Headers:** `Authorization: Bearer {token}`

#### Get Template

```
GET /api/{tenant}/reports/report-templates/{report_template}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Template

```
POST /api/{tenant}/reports/report-templates
```

**Headers:** `Authorization: Bearer {token}`

#### Update Template

```
PUT /api/{tenant}/reports/report-templates/{report_template}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Template

```
DELETE /api/{tenant}/reports/report-templates/{report_template}
```

**Headers:** `Authorization: Bearer {token}`

#### Create from Template

```
POST /api/{tenant}/reports/report-templates/{id}/create-report
```

**Headers:** `Authorization: Bearer {token}`

---

### Report Runs

#### List Runs

```
GET /api/{tenant}/reports/report-runs
```

**Headers:** `Authorization: Bearer {token}`

#### Get Run

```
GET /api/{tenant}/reports/report-runs/{report_run}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Run

```
POST /api/{tenant}/reports/report-runs
```

**Headers:** `Authorization: Bearer {token}`

#### Update Run

```
PUT /api/{tenant}/reports/report-runs/{report_run}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Run

```
DELETE /api/{tenant}/reports/report-runs/{report_run}
```

**Headers:** `Authorization: Bearer {token}`

#### Get Results

```
GET /api/{tenant}/reports/report-runs/{id}/results
```

**Headers:** `Authorization: Bearer {token}`

#### Download

```
POST /api/{tenant}/reports/report-runs/{id}/download
```

**Headers:** `Authorization: Bearer {token}`

#### Cancel

```
POST /api/{tenant}/reports/report-runs/{id}/cancel
```

**Headers:** `Authorization: Bearer {token}`

#### Get Stats

```
GET /api/{tenant}/reports/report-runs/stats
```

**Headers:** `Authorization: Bearer {token}`

---

### Scheduled Reports

#### List Scheduled Reports

```
GET /api/{tenant}/reports/scheduled-reports
```

**Headers:** `Authorization: Bearer {token}`

#### Get Scheduled Report

```
GET /api/{tenant}/reports/scheduled-reports/{scheduled_report}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Scheduled Report

```
POST /api/{tenant}/reports/scheduled-reports
```

**Headers:** `Authorization: Bearer {token}`

#### Update Scheduled Report

```
PUT /api/{tenant}/reports/scheduled-reports/{scheduled_report}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Scheduled Report

```
DELETE /api/{tenant}/reports/scheduled-reports/{scheduled_report}
```

**Headers:** `Authorization: Bearer {token}`

#### Run Now

```
POST /api/{tenant}/reports/scheduled-reports/{id}/run
```

**Headers:** `Authorization: Bearer {token}`

#### Pause

```
POST /api/{tenant}/reports/scheduled-reports/{id}/pause
```

**Headers:** `Authorization: Bearer {token}`

#### Resume

```
POST /api/{tenant}/reports/scheduled-reports/{id}/resume
```

**Headers:** `Authorization: Bearer {token}`

---

## Export/Import

### Exports

#### List Exports

```
GET /api/{tenant}/export/exports
```

**Headers:** `Authorization: Bearer {token}`

#### Get Export

```
GET /api/{tenant}/export/exports/{export}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Export

```
POST /api/{tenant}/export/exports
```

**Headers:** `Authorization: Bearer {token}`

#### Update Export

```
PUT /api/{tenant}/export/exports/{export}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Export

```
DELETE /api/{tenant}/export/exports/{export}
```

**Headers:** `Authorization: Bearer {token}`

#### Get Status

```
GET /api/{tenant}/export/exports/{id}/status
```

**Headers:** `Authorization: Bearer {token}`

#### Download

```
GET /api/{tenant}/export/exports/{id}/download
```

**Headers:** `Authorization: Bearer {token}`

#### Cancel

```
POST /api/{tenant}/export/exports/{id}/cancel
```

**Headers:** `Authorization: Bearer {token}`

#### Get Stats

```
GET /api/{tenant}/export/exports/stats
```

**Headers:** `Authorization: Bearer {token}`

---

### Imports

#### Validate

```
POST /api/{tenant}/import/validate
```

**Headers:** `Authorization: Bearer {token}`

#### Preview

```
POST /api/{tenant}/import/preview
```

**Headers:** `Authorization: Bearer {token}`

#### Import

```
POST /api/{tenant}/import/import
```

**Headers:** `Authorization: Bearer {token}`

#### Get Import Job

```
GET /api/{tenant}/import/jobs/{id}
```

**Headers:** `Authorization: Bearer {token}`

#### Cancel Import Job

```
POST /api/{tenant}/import/jobs/{id}/cancel
```

**Headers:** `Authorization: Bearer {token}`

#### Get Templates

```
GET /api/{tenant}/import/templates
```

**Headers:** `Authorization: Bearer {token}`

#### Download Template

```
GET /api/{tenant}/import/templates/download
```

**Headers:** `Authorization: Bearer {token}`

#### Get History

```
GET /api/{tenant}/import/history
```

**Headers:** `Authorization: Bearer {token}`

---

## GDPR

### Export User Data

```
GET /api/{tenant}/gdpr/export-user-data
```

**Headers:** `Authorization: Bearer {token}`

### Download Exported Data

```
GET /api/{tenant}/gdpr/export-user-data/download
```

**Headers:** `Authorization: Bearer {token}`

### Request Deletion

```
POST /api/{tenant}/gdpr/request-deletion
```

**Headers:** `Authorization: Bearer {token}`

### Confirm Deletion

```
POST /api/{tenant}/gdpr/deletion/confirm/{token}
```

### Cancel Deletion

```
POST /api/{tenant}/gdpr/deletion/cancel
```

**Headers:** `Authorization: Bearer {token}`

### Get Deletion Status

```
GET /api/{tenant}/gdpr/deletion/status
```

**Headers:** `Authorization: Bearer {token}`

### Anonymize

```
POST /api/{tenant}/gdpr/anonymize
```

**Headers:** `Authorization: Bearer {token}`

### Get Consent Status

```
GET /api/{tenant}/gdpr/consent-status
```

**Headers:** `Authorization: Bearer {token}`

### Update Consent

```
PUT /api/{tenant}/gdpr/consent
```

**Headers:** `Authorization: Bearer {token}`

---

## Dashboard

### Health Check

```
GET /api/{tenant}/health
```

**Response (200):**
```json
{
  "status": "ok",
  "tenant_id": 1,
  "version": "1.0.0",
  "timestamp": "2024-01-15T00:00:00Z"
}
```

### Get Dashboard

```
GET /api/{tenant}
```

**Headers:** `Authorization: Bearer {token}`

### Get Stats

```
GET /api/{tenant}/stats
```

**Headers:** `Authorization: Bearer {token}`

### Get Recent Activities

```
GET /api/{tenant}/recent-activities
```

**Headers:** `Authorization: Bearer {token}`

---

## Admin

All admin endpoints require `admin` role and are prefixed with `/admin`.

### Authentication

Admin endpoints use the same authentication flow as regular users, but require admin role.

### Plans

#### List Plans

```
GET /api/admin/plans
```

**Headers:** `Authorization: Bearer {token}`

#### Get Plan

```
GET /api/admin/plans/{plan}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Plan

```
POST /api/admin/plans
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "name": "Pro Plan",
  "description": "Professional features",
  "price_monthly": 29.99,
  "price_yearly": 299.99,
  "features": ["feature1", "feature2"],
  "max_users": 10,
  "max_storage": 10737418240
}
```

#### Update Plan

```
PUT /api/admin/plans/{plan}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Plan

```
DELETE /api/admin/plans/{plan}
```

**Headers:** `Authorization: Bearer {token}`

---

### Tenants

#### List Tenants

```
GET /api/admin/tenants
```

**Headers:** `Authorization: Bearer {token}`

#### Get Tenant

```
GET /api/admin/tenants/{tenant}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Tenant

```
POST /api/admin/tenants
```

**Headers:** `Authorization: Bearer {token}`

#### Update Tenant

```
PUT /api/admin/tenants/{tenant}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Tenant

```
DELETE /api/admin/tenants/{tenant}
```

**Headers:** `Authorization: Bearer {token}`

#### Activate Tenant

```
POST /api/admin/tenants/{id}/activate
```

**Headers:** `Authorization: Bearer {token}`

#### Suspend Tenant

```
POST /api/admin/tenants/{id}/suspend
```

**Headers:** `Authorization: Bearer {token}`

#### Get Tenant Stats

```
GET /api/admin/tenants/{id}/stats
```

**Headers:** `Authorization: Bearer {token}`

#### Get Tenant Users

```
GET /api/admin/tenants/{id}/users
```

**Headers:** `Authorization: Bearer {token}`

---

### Currencies

#### List Currencies

```
GET /api/admin/currencies
```

**Headers:** `Authorization: Bearer {token}`

#### Get Currency

```
GET /api/admin/currencies/{currency}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Currency

```
POST /api/admin/currencies
```

**Headers:** `Authorization: Bearer {token}`

#### Update Currency

```
PUT /api/admin/currencies/{currency}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Currency

```
DELETE /api/admin/currencies/{currency}
```

**Headers:** `Authorization: Bearer {token}`

---

### Exchange Rates

#### List Exchange Rates

```
GET /api/admin/exchange-rates
```

**Headers:** `Authorization: Bearer {token}`

#### Get Exchange Rate

```
GET /api/admin/exchange-rates/{exchange_rate}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Exchange Rate

```
POST /api/admin/exchange-rates
```

**Headers:** `Authorization: Bearer {token}`

#### Update Exchange Rate

```
PUT /api/admin/exchange-rates/{exchange_rate}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Exchange Rate

```
DELETE /api/admin/exchange-rates/{exchange_rate}
```

**Headers:** `Authorization: Bearer {token}`

---

### Tax Rates

#### List Tax Rates

```
GET /api/admin/tax-rates
```

**Headers:** `Authorization: Bearer {token}`

#### Get Tax Rate

```
GET /api/admin/tax-rates/{tax_rate}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Tax Rate

```
POST /api/admin/tax-rates
```

**Headers:** `Authorization: Bearer {token}`

#### Update Tax Rate

```
PUT /api/admin/tax-rates/{tax_rate}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Tax Rate

```
DELETE /api/admin/tax-rates/{tax_rate}
```

**Headers:** `Authorization: Bearer {token}`

#### Calculate Tax

```
POST /api/admin/tax/calculate
```

**Headers:** `Authorization: Bearer {token}`

#### Get Supported Countries

```
GET /api/admin/tax/supported-countries
```

**Headers:** `Authorization: Bearer {token}`

---

### Feature Flags (Admin)

#### List Feature Flags

```
GET /api/admin/feature-flags
```

**Headers:** `Authorization: Bearer {token}`

#### Get Feature Flag

```
GET /api/admin/feature-flags/{feature_flag}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Feature Flag

```
POST /api/admin/feature-flags
```

**Headers:** `Authorization: Bearer {token}`

#### Update Feature Flag

```
PUT /api/admin/feature-flags/{feature_flag}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Feature Flag

```
DELETE /api/admin/feature-flags/{feature_flag}
```

**Headers:** `Authorization: Bearer {token}`

---

### Vouchers

#### List Vouchers

```
GET /api/admin/vouchers
```

**Headers:** `Authorization: Bearer {token}`

#### Get Voucher

```
GET /api/admin/vouchers/{voucher}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Voucher

```
POST /api/admin/vouchers
```

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "code": "SUMMER20",
  "type": "percentage",
  "value": 20,
  "max_uses": 100,
  "expires_at": "2024-12-31",
  "min_amount": 50.00
}
```

#### Update Voucher

```
PUT /api/admin/vouchers/{voucher}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Voucher

```
DELETE /api/admin/vouchers/{voucher}
```

**Headers:** `Authorization: Bearer {token}`

#### Bulk Generate Vouchers

```
POST /api/admin/vouchers/bulk-generate
```

**Headers:** `Authorization: Bearer {token}`

#### Validate Voucher

```
POST /api/admin/vouchers/{id}/validate
```

**Headers:** `Authorization: Bearer {token}`

---

### Invitations (Admin)

#### List Invitations

```
GET /api/admin/invitations
```

**Headers:** `Authorization: Bearer {token}`

#### Get Invitation

```
GET /api/admin/invitations/{invitation}
```

**Headers:** `Authorization: Bearer {token}`

#### Create Invitation

```
POST /api/admin/invitations
```

**Headers:** `Authorization: Bearer {token}`

#### Update Invitation

```
PUT /api/admin/invitations/{invitation}
```

**Headers:** `Authorization: Bearer {token}`

#### Delete Invitation

```
DELETE /api/admin/invitations/{invitation}
```

**Headers:** `Authorization: Bearer {token}`

#### Resend Invitation

```
POST /api/admin/invitations/{id}/resend
```

**Headers:** `Authorization: Bearer {token}`

#### Cancel Invitation

```
POST /api/admin/invitations/{id}/cancel
```

**Headers:** `Authorization: Bearer {token}`

#### Accept Invitation

```
GET /api/admin/invitations/accept/{token}
```

---

## External Webhook Handlers

These endpoints handle webhooks from external payment providers.

### Stripe Webhook

```
POST /api/webhooks/stripe
```

**Headers:** `X-API-Key: {api_key}`

**Rate Limit:** 60 requests/minute

### Xendit Webhook

```
POST /api/webhooks/xendit
```

**Headers:** `X-API-Key: {api_key}`

**Rate Limit:** 60 requests/minute

### PayPal Webhook

```
POST /api/webhooks/paypal
```

**Headers:** `X-API-Key: {api_key}`

**Rate Limit:** 60 requests/minute

### Generic Webhook Handler

```
POST /api/webhooks/{provider}
```

**Headers:** `X-API-Key: {api_key}`

**Rate Limit:** 60 requests/minute

---

## Error Responses

### Authentication Errors

**401 Unauthorized**
```json
{
  "success": false,
  "message": "Unauthenticated",
  "error": "unauthenticated"
}
```

### Validation Errors

**422 Unprocessable Entity**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Not Found

**404 Not Found**
```json
{
  "success": false,
  "message": "Resource not found",
  "error": "not_found"
}
```

### Tenant Not Found

**404 Not Found (Tenant)**
```json
{
  "success": false,
  "message": "Tenant not found or invalid",
  "error": "tenant_not_found"
}
```

### Forbidden

**403 Forbidden**
```json
{
  "success": false,
  "message": "You don't have permission to access this resource",
  "error": "forbidden"
}
```

### Rate Limit Exceeded

**429 Too Many Requests**
```json
{
  "success": false,
  "message": "Too many requests",
  "error": "rate_limit_exceeded"
}
```

### Server Error

**500 Internal Server Error**
```json
{
  "success": false,
  "message": "Internal server error",
  "error": "server_error"
}
```

---

## Postman Collection Setup

### Environment Variables

Create a new Postman environment with these variables:

| Variable | Initial Value | Description |
|----------|---------------|-------------|
| `base_url` | `https://your-domain.com/api` | API Base URL |
| `tenant_id` | `your-tenant-id` | Your tenant identifier |
| `access_token` | `{{login_response.body.data.token}}` | Authentication token |

### Authentication

1. **Login Request:**
   - Method: POST
   - URL: `{{base_url}}/auth/login`
   - Body: JSON with email and password
   - Tests (to save token):
   ```javascript
   if (responseCode.code === 200) {
       pm.environment.set("access_token", response.json().data.token);
   }
   ```

2. **Auth Header for Tenant Requests:**
   - Key: `Authorization`
   - Value: `Bearer {{access_token}}`

---

## Testing with curl

### Login
```bash
curl -X POST https://your-domain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"Password123!"}'
```

### Get Subscriptions
```bash
curl -X GET https://your-domain.com/api/{tenant}/billing/subscriptions \
  -H "Authorization: Bearer {access_token}"
```

### Create Subscription
```bash
curl -X POST https://your-domain.com/api/{tenant}/billing/subscriptions \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{"plan_id":1,"billing_cycle":"monthly"}'
```

---

## Changelog

- **v1.0.0** - Initial API documentation

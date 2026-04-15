# API Documentation

## Files

This directory contains complete API documentation for the SaaS Tenancy Starter backend:

### 📖 API-DOCUMENTATION.md
Comprehensive API documentation covering:
- Authentication (global and tenant-scoped)
- Billing (subscriptions, invoices, payments, transactions, refunds)
- User management
- Webhooks
- Analytics & audit logs
- Usage metrics & pricing
- Reports (custom, templates, runs, scheduled)
- Export/Import functionality
- GDPR compliance
- Dashboard & stats
- Admin endpoints
- External webhook handlers
- Error responses
- Rate limiting

### 📬 Postman Collection (postman-collection.json)
Ready-to-import Postman collection with:
- All endpoints organized by module
- Environment variables for easy configuration
- Auto-save authentication token on login
- Sample request bodies

## Getting Started

### Using the Documentation

1. Open `API-DOCUMENTATION.md` to view all available endpoints
2. Each endpoint includes:
   - HTTP method and URL
   - Authentication requirements
   - Request/Response examples
   - Query parameters
   - Error codes

### Using Postman

#### Import the Collection

1. Open Postman
2. Click "Import" (top left)
3. Select `postman-collection.json`
4. The collection will be imported with all endpoints organized

#### Configure Environment

Create a new environment in Postman with these variables:

| Variable | Value | Description |
|----------|-------|-------------|
| `base_url` | `https://your-domain.com/api` | API base URL |
| `tenant_id` | `your-tenant-id` | Your tenant identifier |
| `access_token` | *(auto-filled)* | Authentication token |

#### Quick Start

1. **Login** - Go to Authentication → Login
   - Enter your email and password
   - Send request
   - Token is automatically saved to environment

2. **Make Authenticated Requests** - All other requests will use the saved token

### Testing with cURL

```bash
# Login
curl -X POST https://your-domain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Use the token
curl -X GET https://your-domain.com/api/{tenant}/billing/subscriptions \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## API Structure

### Tenant-Scoped Endpoints

Most endpoints are tenant-scoped under `/{tenant}/`:

```
https://your-domain.com/api/{tenant}/billing/subscriptions
https://your-domain.com/api/{tenant}/user/tokens
https://your-domain.com/api/{tenant}/webhooks/webhooks
```

Replace `{tenant}` with your actual tenant ID.

### Global Endpoints

Authentication and admin endpoints are global:

```
https://your-domain.com/api/auth/login
https://your-domain.com/api/admin/plans
```

### External Webhooks

Payment provider webhooks use API key authentication:

```
POST https://your-domain.com/api/webhooks/stripe
Headers: X-API-Key: your_api_key
```

## Authentication

### Bearer Token

Most endpoints require a bearer token in the Authorization header:

```
Authorization: Bearer your_access_token_here
```

### API Key

External webhook handlers use API keys:

```
X-API-Key: your_api_key_here
```

## Response Format

### Success

```json
{
  "success": true,
  "data": { ... },
  "message": "Success message (optional)"
}
```

### Error

```json
{
  "success": false,
  "message": "Error description",
  "error": "error_code",
  "errors": { ... }
}
```

### Pagination

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

## Common Error Codes

| Code | Meaning |
|------|----------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Internal Server Error |

## Rate Limiting

- Authentication endpoints: 5 requests/minute
- Password reset endpoints: 3 requests/minute
- Webhook endpoints: 60 requests/minute

Rate limit exceeded returns a 429 status code.

## Modules

### Billing
- **Subscriptions**: Create, update, upgrade, downgrade, pause, resume, cancel, renew
- **Invoices**: Create, update, send, download, add/remove items
- **Payments**: Create, list, manage payment methods
- **Transactions**: Track all financial transactions
- **Refunds**: Create, process, cancel refunds

### Management
- **User**: Profile, password, API tokens, roles/permissions
- **Notifications**: Create, send, mark read, manage preferences
- **Activities**: Track and manage user activities
- **Invitations**: Invite users to tenant
- **Feature Flags**: Manage feature flags

### Analytics & Audit
- **Events**: Track and analyze user events
- **Audit Logs**: Track all changes in the system

### Webhooks
- **Webhooks**: Create and manage webhook endpoints
- **Events**: View and retry failed webhook deliveries

### Usage
- **Metrics**: Track usage metrics
- **Pricing**: Define usage-based pricing
- **Alerts**: Set up usage alerts

### Reports
- **Custom Reports**: Create and run custom reports
- **Templates**: Define reusable report templates
- **Report Runs**: View report execution history
- **Scheduled Reports**: Automate report generation

### Export/Import
- **Exports**: Export data in various formats
- **Imports**: Import data with validation

### GDPR
- **Export Data**: Download user data
- **Delete**: Request account deletion
- **Consent**: Manage consent settings

### Admin
- **Plans**: Manage subscription plans
- **Tenants**: Manage all tenants
- **Currencies**: Manage supported currencies
- **Exchange Rates**: Manage currency exchange rates
- **Tax Rates**: Manage tax rates by country
- **Vouchers**: Create and manage discount vouchers
- **Invitations**: Manage global invitations

## Support

For issues or questions:
1. Check the API documentation for endpoint details
2. Review error messages and codes
3. Ensure proper authentication
4. Verify tenant ID is correct

---

**Last Updated**: 2024-01-15
**API Version**: 1.0.0

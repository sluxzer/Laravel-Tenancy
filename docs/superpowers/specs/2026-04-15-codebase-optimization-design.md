# Codebase Optimization Design Document

**Date:** 2026-04-15
**Project:** SaaS Tenancy Starter - Backend
**Type:** Complete Architectural Refactoring

## Overview

This document outlines a complete architectural refactoring of the SaaS Tenancy Starter backend application to implement Clean Architecture principles, SOLID design patterns, and best practices for maintainability, testability, and performance.

## Current State

**Issues Identified:**
- Inline validation in most controllers (only 13 Form Requests exist)
- No repository pattern - direct Model queries in controllers
- Duplicate tenant scoping logic repeated everywhere
- Mixed service usage - some controllers use services, some don't
- Potential N+1 query issues
- No API Resources - manual JSON response formatting
- Business logic in controllers (validation, status checks, etc.)

**Scope:**
- 28 Controllers across modules (Billing, Management, Admin, Settings, Usage, Reports, GDPR, Import/Export, Auth, Audit, Analytics, Webhooks)
- 13 existing Form Requests need ~50+ more
- 18+ Services exist but not consistently used
- No Repository layer exists

## Target Architecture

### Directory Structure

```
app/
├── Http/
│   ├── Controllers/          # Thin HTTP layer (28 controllers)
│   │   ├── Admin/
│   │   ├── Billing/
│   │   ├── Management/
│   │   ├── Settings/
│   │   ├── Usage/
│   │   ├── Report/
│   │   ├── Gdpr/
│   │   ├── Export/
│   │   ├── Import/
│   │   ├── Analytics/
│   │   ├── Audit/
│   │   ├── Webhook/
│   │   └── Auth/
│   ├── Requests/             # One FormRequest per action (~60+ files)
│   │   ├── Billing/
│   │   ├── Management/
│   │   └── [other modules]
│   └── Resources/            # API Resources for transformation
│       ├── Billing/
│       ├── Management/
│       └── [other modules]
├── Services/                 # All business logic (18+ services)
│   ├── Billing/
│   ├── Management/
│   ├── Admin/
│   └── [other modules]
├── Repositories/
│   ├── Contracts/            # Interfaces (20+ interfaces)
│   └── Eloquent/             # Implementations
│       ├── BaseRepository.php
│       └── [entity repositories]
└── Models/                   # Eloquent models only (data layer)
```

### Request Flow

```
HTTP Request
    ↓
Controller (thin, validates via FormRequest)
    ↓
FormRequest (validation)
    ↓
Service (business logic)
    ↓
Repository (data access)
    ↓
Model (Eloquent)
    ↓
API Resource (transform)
    ↓
JSON Response
```

## Layer Responsibilities

### Controllers
- **Responsibility:** HTTP handling only
- **Max Lines:** 10-20 per method
- **No:** Business logic, validation, data access
- **Yes:** Request/response handling, HTTP status codes

### Form Requests
- **Responsibility:** Validation and authorization
- **Per Action:** One FormRequest per controller method
- **Contains:** Validation rules, custom messages, authorization logic
- **Provides:** Helper methods to get validated objects

### Services
- **Responsibility:** All business logic
- **Stateless:** Inject dependencies via constructor
- **Uses:** Repositories for data access, other services for orchestration
- **Transactions:** Handle multi-step operations in DB transactions
- **Exceptions:** Throw domain-specific exceptions

### Repositories
- **Responsibility:** Data access abstraction
- **Pattern:** Hybrid (BaseRepository + specific extensions)
- **Base:** Common CRUD methods (find, create, update, delete, paginate)
- **Specific:** Domain-specific query methods
- **Interfaces:** Contract for dependency inversion

### Models
- **Responsibility:** Data structure and relationships
- **No:** Business logic, queries
- **Yes:** Relationships, casts, fillable, mutators

### API Resources
- **Responsibility:** Transform models to JSON
- **Benefits:** Consistent API format, conditional loading, type safety
- **Includes:** Computed attributes, nested resources, pagination

## SOLID Principles

### Single Responsibility Principle (SRP)
Each class has one reason to change:
- Controllers: HTTP concerns only
- Services: Business logic only
- Repositories: Data access only
- Requests: Validation only
- Resources: Transformation only

### Open/Closed Principle (OCP)
Open for extension, closed for modification:
- Abstract interfaces for extensible components
- Factory pattern for selecting implementations
- Payment processors as example

### Liskov Substitution Principle (LSP)
Implementations are interchangeable via interfaces
- Repository implementations (Eloquent, Cache)
- Service implementations can be swapped

### Interface Segregation Principle (ISP)
Small, focused interfaces
- Separate interfaces for different concerns
- Clients only depend on what they need

### Dependency Inversion Principle (DIP)
Depend on abstractions, not concretions
- Services depend on interfaces
- Service Provider binds interfaces to implementations
- Easy to swap implementations

## Performance Optimization

### Database Queries
1. **Eager Loading** - Prevent N+1 queries
2. **Query Caching** - Use QueryCache trait
3. **Database Indexes** - Add for common queries
4. **Select Only Needed Fields** - Minimal data retrieval
5. **Chunk Processing** - For bulk operations

### Caching Strategy
- Tenant-scoped cache keys
- Tag-based cache invalidation
- TTL-based expiration

### Queue Strategy
- Long-running operations to queues
- Retry logic with backoff
- Failure handling and logging

### Response Optimization
- Compression in production
- Minimal payload sizes
- Pagination for large datasets

## Security Improvements

1. **Input Validation** - Strict FormRequest rules
2. **SQL Injection** - Eloquent ORM (parameter binding)
3. **XSS Prevention** - API Resources auto-escape
4. **Authorization** - Policies for each model
5. **Rate Limiting** - Stricter limits for sensitive operations
6. **Tenant Isolation** - Middleware enforcement
7. **Sensitive Data** - Never log, encrypt in database
8. **Authentication** - Sanctum token-based auth
9. **CSRF Protection** - Enabled for web routes
10. **Secure Headers** - HSTS, XSS protection, etc.

## Exception Handling

### Domain Exceptions
- Base `DomainException` class
- Specific exceptions (SubscriptionException, PaymentException, etc.)
- HTTP status codes and error codes
- Static factory methods for common scenarios

### Global Handler
- Domain exceptions → formatted JSON response
- Laravel validation exceptions → formatted JSON response
- 404 exceptions → formatted JSON response
- Generic exceptions → debug info in dev, generic in prod

## Implementation Phases

### Phase 1: Foundation
1. Create BaseRepository
2. Create Repository contracts for all entities
3. Create Repository implementations
4. Register bindings in ServiceProvider

### Phase 2: Services
1. Create service interfaces
2. Implement all services with repository usage
3. Move business logic from controllers to services
4. Add exception handling

### Phase 3: Form Requests
1. Create FormRequest for each controller action
2. Move validation from controllers
3. Add authorization logic
4. Add helper methods

### Phase 4: API Resources
1. Create API Resources for all models
2. Transform controller responses
3. Add computed attributes
4. Handle nested resources

### Phase 5: Controllers
1. Refactor controllers to use FormRequests
2. Inject services via constructor
3. Use API Resources for responses
4. Remove all business logic

### Phase 6: Testing
1. Update existing tests to work with new architecture
2. Add tests for new layers
3. Ensure all tests pass

### Phase 7: Performance & Security
1. Add database indexes
2. Implement caching strategies
3. Add rate limiting
4. Add secure headers
5. Review and optimize queries

## Testing Strategy

**Controller is Source of Truth**
- Tests define expected behavior
- After implementation, tests will be updated to match new architecture
- All existing tests must pass after refactoring

**Test Coverage**
- Controller tests → HTTP endpoints
- Service tests → Business logic
- Repository tests → Data access
- Request tests → Validation rules

## Migration Strategy

Since the application is not live:
- Full architectural overhaul is acceptable
- Breaking changes are acceptable
- Can refactor completely without backward compatibility

## Success Criteria

1. ✅ All controllers are thin (max 20 lines per method)
2. ✅ All validation in FormRequests (one per action)
3. ✅ All business logic in services
4. ✅ All data access through repositories
5. ✅ All responses use API Resources
6. ✅ SOLID principles applied
7. ✅ All existing tests pass (after updates)
8. ✅ Performance optimized (caching, indexing, eager loading)
9. ✅ Security hardened (validation, authorization, rate limiting)
10. ✅ Clean, maintainable code structure

## Estimated Scope

- **Controllers:** 28 (refactor)
- **Form Requests:** ~60+ (create)
- **Services:** 18+ (refactor/create)
- **Repository Interfaces:** 20+ (create)
- **Repository Implementations:** 20+ (create)
- **API Resources:** ~30+ (create)
- **Exception Classes:** ~10 (create)
- **Tests:** Update existing, add new

## Notes

- Use existing QueryCacheService for caching
- Use existing event/listener system where appropriate
- Follow Laravel Boost guidelines
- Use Pint for code formatting
- Use Pest for testing
- Follow existing naming conventions

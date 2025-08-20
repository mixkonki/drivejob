# API Permissions Map

## Overview

Αυτό το έγγραφο περιγράφει τα permissions και τους ρόλους που απαιτούνται για κάθε API endpoint στο DriveJob platform.

## Authentication Methods

### Session-based Authentication
- Χρησιμοποιείται για web requests
- Session cookies με CSRF protection

### Bearer Token Authentication
- Χρησιμοποιείται για API requests
- JWT tokens με payload: `{sub, role, name, email, is_verified, iat, exp}`
- Header format: `Authorization: Bearer <token>`

## API Endpoints & Permissions

### Public Endpoints (No Authentication Required)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/auth/login` | POST | User authentication |

### Admin Endpoints

| Endpoint | Method | Required Role | Description |
|----------|--------|---------------|-------------|
| `/api/admin/users` | GET | admin | List all users with pagination |
| `/api/admin/users?type={role}` | GET | admin | Filter users by role |
| `/api/matching/batch` | POST | admin | Trigger batch matching for all jobs |

### Company Endpoints

| Endpoint | Method | Required Role | Description |
|----------|--------|---------------|-------------|
| `/api/companies/profile` | GET | company | Get company profile |
| `/api/companies/profile` | PUT | company | Update company profile |
| `/api/matching/job/candidates` | GET | company | Get job candidates with matching scores |

### Driver Endpoints

| Endpoint | Method | Required Role | Description |
|----------|--------|---------------|-------------|
| `/api/drivers/profile` | GET | driver | Get driver profile |
| `/api/drivers/profile` | PUT | driver | Update driver profile |
| `/api/matching/driver/matches` | GET | driver | Get job matches for driver |

### Mixed Role Endpoints

| Endpoint | Method | Required Roles | Description |
|----------|--------|----------------|-------------|
| `/api/matching/calculate` | GET | driver, company | Calculate match score between driver and job |
| `/api/matching/insights` | GET | driver, company | Get match insights and recommendations |
| `/api/messages/send` | POST | driver, company | Send message |

## Role Definitions

### Admin
- Full system access
- User management
- System monitoring
- Batch operations

### Company
- Company profile management
- Job listing management
- View job candidates
- Messaging with drivers

### Driver
- Driver profile management
- View job matches
- Apply to jobs
- Messaging with companies

## Security Features

### CSRF Protection
- **Enabled for**: Web forms and non-JSON requests
- **Bypassed for**: `/api/*` endpoints and `Content-Type: application/json` requests
- **Implementation**: `CsrfMiddleware::handle()`

### Role-Based Access Control (RBAC)
- **Implementation**: `AuthenticationMiddleware` with `RoleManager`
- **Features**:
  - Role-based access control
  - Permission-based access control (future enhancement)
  - Resource ownership validation
  - Bearer token support

### API Response Format

#### Success Response
```json
{
  "success": true,
  "data": {
    // Response data
  }
}
```

#### Error Response
```json
{
  "error": {
    "code": 401|403|404|500,
    "message": "Error description",
    "details": {
      // Optional additional details
    }
  }
}
```

## Implementation Details

### AuthenticationMiddleware Methods

| Method | Purpose | Usage |
|--------|---------|-------|
| `requireLogin($isApiRequest)` | Basic authentication check | All protected endpoints |
| `requireAdmin($isApiRequest)` | Admin-only access | Admin endpoints |
| `requireDriver($isApiRequest)` | Driver-only access | Driver endpoints |
| `requireCompany($isApiRequest)` | Company-only access | Company endpoints |
| `requireDriverOrCompany($isApiRequest)` | Mixed role access | Shared endpoints |
| `getCurrentUser()` | Get current user info | User context |

### Bearer Token Flow

1. User logs in via `/api/auth/login`
2. Server generates JWT token with user info
3. Client includes token in `Authorization: Bearer <token>` header
4. `AuthenticationMiddleware::requireLogin()` validates token and hydrates session
5. Role-specific methods check user permissions

## Migration from ApiAuthMiddleware

### Before (Legacy)
```php
if (!ApiAuthMiddleware::check(['driver'])) {
    return;
}
$user = ApiAuthMiddleware::getUser();
```

### After (New)
```php
if (!Auth::requireDriver(true)) {
    return;
}
$user = Auth::getCurrentUser();
```

## Testing

### Newman Collection
- Collection: `postman/drivejob_rbac.postman_collection.json`
- Environment: `postman/drivejob_local.postman_environment.json`

### Test Scenarios
1. **Authentication Tests**
   - Login with valid credentials → 200 + token
   - Login with invalid credentials → 401
   - Access protected endpoint without token → 401

2. **Authorization Tests**
   - Admin access admin endpoint → 200
   - Driver access admin endpoint → 403
   - Company access driver endpoint → 403

3. **Resource Ownership Tests**
   - User access own profile → 200
   - User access other user's profile → 403

## Future Enhancements

### Granular Permissions
- Transition from role-based to permission-based access control
- Example permissions:
  - `matching.view` - View matching results
  - `jobs.create` - Create job listings
  - `profiles.edit` - Edit profiles
  - `admin.users` - Manage users

### Permission Format
```
module.action
```

Examples:
- `matching.*` - All matching permissions
- `jobs.create` - Create jobs only
- `profiles.edit` - Edit profiles only

### Implementation
```php
// Future permission-based access
if (!Auth::requireModulePermission('matching.view', true)) {
    return;
}
```

## Security Considerations

1. **Token Expiration**: JWT tokens expire after 24 hours
2. **HTTPS Only**: All API endpoints should use HTTPS in production
3. **Rate Limiting**: Consider implementing rate limiting for API endpoints
4. **Input Validation**: All inputs are validated and sanitized
5. **Error Handling**: Sensitive information is not exposed in error messages

## Troubleshooting

### Common Issues

1. **401 Unauthorized**
   - Check if token is included in Authorization header
   - Verify token is not expired
   - Ensure user exists and is active

2. **403 Forbidden**
   - Check user role matches required role
   - Verify user has necessary permissions
   - Check resource ownership for user-specific resources

3. **CSRF Token Mismatch**
   - Ensure API requests use `Content-Type: application/json`
   - Verify `/api/*` endpoints are properly configured

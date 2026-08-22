Authentication Module

Endpoints:
POST /api/register/client
POST /api/register/agency
POST /api/login
GET  /api/me
POST /api/logout

Authentication:
Laravel Sanctum / Bearer Token

Roles:
client
agency
admin

Agency:
pending
approved
rejected

Security:
- Password hashing
- Request validation
- Protected routes
- Role-based authorization
- Suspended account handling
- Agency approval middleware

Main HTTP responses:
200 Success
201 Created
401 Unauthenticated / invalid credentials
403 Forbidden
422 Validation error
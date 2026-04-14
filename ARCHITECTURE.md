# Project Architecture: Core + Modules

This project is structured so that **Core modules** can be reused across multiple systems, and **Feature modules** under `/modules` contain project-specific business logic.

## Directory Layout

```
/core                    # Reusable across all projects (no project-specific logic)
  /auth                  # Login, logout, session, change password
    /controllers
    /services
  /users                 # Current user profile (me), avatar
    /controllers
    /services
  /roles                 # Role CRUD, role-permission mapping
    /controllers
    /services
  /permissions           # Permission checks, middleware
    /services
    /middleware
  /settings              # System settings, email config, SettingsLoader
    /services
    /controllers
  /notifications         # Generic notification abstractions
  /logs                  # Activity log service
  /files                 # File upload (avatar, generic)
    /services
    /controllers
  /config                # Core database config
  bootstrap.php          # Session, DB, CORS, $method, $input, $scopeUserId

/modules                 # System-specific feature modules
  /members               # Example feature module (members directory)
    /controllers
    /services
    /models
    /routes
    /views
  # Add more: budget, contributions, vendors, etc.

/config                  # Application-level configuration (env, app settings)

/middleware              # Global middleware entry points (wrapper around core middleware)

/api                     # HTTP entry points (thin layer)
  /connect
    header.php           # Includes core/bootstrap.php
  /auth/login.php        # -> core/auth
  /me.php                # -> core/users
  /roles.php             # -> core/roles
  /change-password.php   # -> core/auth
  /upload-avatar.php     # -> core/files
  /email-config.php      # -> core/settings
  /members.php           # -> modules/members
  ...

/database
  /migrations            # SQL migrations (core tables: users, roles, system_settings, activity_log; module tables: members, etc.)
```

## Core Modules

- **Authentication**: Handled only in Core. Login, logout, session, change password. No duplication in modules.
- **Users**: Current user profile (GET/PUT me), avatar upload. Centralized user management.
- **Roles**: CRUD for roles; each role has a JSON array of permission keys.
- **Permissions**: Middleware-based. `core_require_auth()` and `core_require_permission($dbh, 'permission.key')`. Role–permission mapping via `roles.permissions` and (optionally) `member_roles` for module-scoped users.
- **Settings**: `SettingsLoader` for global key-value (e.g. `system_settings` table). Email (SMTP) config in Core.
- **Notifications**: Email/notification abstractions. Concrete implementations can live under `api/` or modules.
- **Activity Logs**: `ActivityLogService` for writing/reading activity (optional `activity_log` table).
- **File Manager**: `FileUploadService` and file-related controllers for avatar and generic uploads.

Core modules must stay free of project-specific assumptions so they can be copied between projects.

## Feature Modules

- Each feature module under `/modules/<name>` has: `controllers/`, `services/`, `models/`, `routes/`, `views/`.
- API entry points under `api/*.php` include `connect/header.php` then require the appropriate Core or Module controller.
- Modules use `$scopeUserId` (account/tenant owner) and Core permissions for access control.

## Creating a New Project or Module

1. **Copy the Core**  
   Copy the `core/` directory and `api/connect/header.php` (and optionally `api/connect/connect.php` if you keep it for backward compatibility). Ensure `core/bootstrap.php` and `core/config/database.php` are used.

2. **Configure database**  
   Use `.env` or `core/config/database.php` for DB_HOST, DB_NAME, DB_USER, DB_PASS.

3. **Run core migrations**  
   Run migrations for `users`, `roles`, `system_settings`, `activity_log`, `email_config` as needed.

4. **Add feature modules**  
   Create `modules/<name>/` with controllers, services, models, routes, views, and register API entry points under `api/`.

5. **Wire API entry points**  
   Create `api/<path>.php` that includes `connect/header.php` and requires the Core or Module controller. Use `core_require_auth()` and `core_require_permission($dbh, 'key')` where needed.

## Frontend (Vue)

- **Core** (`src/core/`): API client, auth store re-export, permissions (PERMISSION_KEYS, ROUTE_PERMISSIONS), router guards (`requireAuth`, `canAccessRoute`).
- **Modules** (`src/app/modules/`): Route registry (`appModuleRoutes`) merged into the main router. Sidebar and links use `ROUTE_PERMISSIONS` and `authStore.can(permission)` for visibility.

Authentication and permission checks are centralized in Core; feature modules only define routes, permission keys, and business logic.

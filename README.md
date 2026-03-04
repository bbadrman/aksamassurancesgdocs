# Document Management System - Architecture & Product Analysis

## 📋 Overview

This is a **Document Management & Client Relationship System** built with modern Symfony 7.4 framework. It's designed to manage clients, their documents, user authentication, and maintain detailed activity logs with a granular permission system.

**Technology Stack:**
- **Framework:** Symfony 7.4
- **API:** API Platform 4.2
- **Database:** Doctrine ORM 3.6
- **Authentication:** Custom Form-based + Password Reset via Email
- **File Management:** Vich Uploader Bundle
- **Frontend:** Twig Templates + Stimulus.js
- **Real-time:** Symfony UX Turbo

---

## 🏗️ Architecture Overview

### High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                          Client Layer                            │
│  (Web Browser - Twig Templates + Stimulus.js + Turbo AJAX)      │
└────────────────┬────────────────────────────────────────────────┘
                 │
┌────────────────┴────────────────────────────────────────────────┐
│                     HTTP/REST API Layer                          │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ Controllers (Web & API)                                  │  │
│  │  - Auth: Login, Registration, Password Reset            │  │
│  │  - Dashboard: Overview                                   │  │
│  │  - Client: CRUD operations                               │  │
│  │  - Document: Upload, Download, Edit, Delete             │  │
│  │  - Admin: User management                                │  │
│  └──────────────────────────────────────────────────────────┘  │
└────────────────┬────────────────────────────────────────────────┘
                 │
┌────────────────┴────────────────────────────────────────────────┐
│                   Business Logic Layer                           │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ Services                                                 │  │
│  │  - PasswordResetService: Token generation & validation   │  │
│  │  - ActivityLogger: Audit trail management                │  │
│  └──────────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ Security Layer                                           │  │
│  │  - LoginFormAuthenticator: Custom authentication         │  │
│  │  - Permission System: Granular access control            │  │
│  │  - AccessDeniedHandler: Custom error handling            │  │
│  └──────────────────────────────────────────────────────────┘  │
└────────────────┬────────────────────────────────────────────────┘
                 │
┌────────────────┴────────────────────────────────────────────────┐
│                    Data Layer (ORM/Database)                     │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ Doctrine Entities & Repositories                         │  │
│  │  - User (Authentication & Roles)                         │  │
│  │  - Client (Contact information & relations)              │  │
│  │  - Document (File storage with metadata)                 │  │
│  │  - ActivityLog (Audit trail)                             │  │
│  │  - PasswordReset (Token-based verification)              │  │
│  └──────────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ File Storage (Physical)                                  │  │
│  │  - var/uploads/: Document files                          │  │
│  └──────────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────────┘
```

---

## 📦 Core Components

### 1. **Authentication & Security**

#### Login System (`LoginFormAuthenticator`)
- Email-based login
- Password hashing with Symfony's built-in password hashers
- Session-based user provider
- Custom access denied handler

#### Password Reset Flow
- **Secure Token Generation:** 32-character random tokens
- **Token Expiry:** 30 minutes
- **Rate Limiting:** Max 3 requests per hour
- **Email Delivery:** Twig-templated reset emails
- **One-time Tokens:** Hashed with SHA-256, automatically invalidated after use

#### Permission System (`Permission` Enum)
Granular permissions organized by resource:

**Client Permissions:**
- `clients.view_list` - View list of clients
- `clients.view_details` - View individual client details
- `clients.create` - Create new clients
- `clients.edit` - Edit client information
- `clients.delete` - Delete clients
- `clients.view_documents_column` - See documents column
- `clients.view_actions_column` - See action buttons

**Document Permissions:**
- `documents.view_list` - List documents
- `documents.view_details` - View document metadata
- `documents.create_upload` - Upload new documents
- `documents.edit` - Edit document info
- `documents.delete` - Soft delete documents
- `documents.download` - Download document files

**User Management:**
- `users.view` - View users
- `users.create` - Create new users
- `users.edit` - Edit user accounts
- `users.delete` - Delete users
- `users.assign_roles` - Assign roles to users

---

### 2. **Entity Model**

#### User Entity
```php
- id: Unique identifier
- email: Unique, used for login
- username: Optional unique username
- password: Hashed password
- roles: Array of role names
- permissions: JSON array of permission strings
- isVerified: Boolean flag for email verification
```

#### Client Entity
```php
- id: Primary key
- firstName: Client's first name
- prenom: Client's last name
- email: Contact email
- phone: Contact phone number
- address: Physical address
- createdAt: Creation timestamp
- documents: OneToMany relationship with Document
```

#### Document Entity
```php
- id: Primary key
- title: Document name
- description: Document description (optional)
- fileName: Generated filename (by Vich)
- originalName: Original uploaded filename
- mimeType: File MIME type (e.g., application/pdf)
- size: File size in bytes
- createdAt: Upload timestamp
- updatedAt: Last modification timestamp
- deletedAt: Soft delete timestamp (NULL if active)
- client: ManyToOne relationship with Client
- file: File object (handled by Vich Uploader)
```

**Special Features:**
- **Soft Delete:** Documents marked deleted maintain history
- **Vich Uploader Integration:** Automatic file handling
- **API Platform:** Documents exposed as REST resources

#### ActivityLog Entity
```php
- id: Primary key
- action: Type of action (upload, delete, download, etc.)
- user: ManyToOne relationship with User (who performed action)
- document: ManyToOne relationship with Document (null for non-document actions)
- client: ManyToOne relationship with Client (null for non-client actions)
- details: Additional JSON metadata
- createdAt: When action occurred

Action Types:
- ACTION_UPLOAD: Document uploaded
- ACTION_DELETE: Document soft deleted
- ACTION_DOWNLOAD: Document downloaded
- ACTION_EDIT: Document metadata edited
- ACTION_RESTORE: Soft-deleted document restored
- ACTION_PERMANENT_DELETE: Document permanently deleted
- ACTION_CLIENT_CREATE: New client created
- ACTION_CLIENT_EDIT: Client information edited
- ACTION_CLIENT_DELETE: Client deleted
```

**Indexes:**
- `idx_activity_created_at` - For time-range queries
- `idx_activity_action` - For action filtering

#### PasswordReset Entity
```php
- id: Primary key
- user: ManyToOne relationship with User
- tokenHash: SHA-256 hashed token (stored, not token itself)
- expiresAt: Token expiration datetime
- isUsed: Boolean flag (set after reset)
- requestedAt: When token was created
- usedAt: When token was used (null if unused)
```

---

### 3. **Service Layer**

#### PasswordResetService
**Purpose:** Complete password reset workflow management

**Key Methods:**
- `requestReset($email)`: Creates reset token and sends email
  - Returns always same message to prevent email enumeration
  - Validates rate limiting (3 requests/hour max)
  - Invalidates previous tokens
  
- `validateToken($user, $token)`: Verifies token validity
  - Checks token existence and hash
  - Validates expiration time
  - Prevents reuse
  
- `completeReset($user, $token, $newPassword)`: Finalizes password change
  - Hashes new password
  - Marks token as used
  - Updates user entity

**Features:**
- CSRF protection via token verification
- Email enumeration prevention (always confirms)
- Rate limiting to prevent abuse
- Detailed logging for audit trail

#### ActivityLogger
**Purpose:** Centralized audit logging

**Logged Actions:**
- Document uploads with file metadata
- Document deletions (soft and permanent)
- Document downloads
- Document edits
- Document restoration
- Client CRUD operations

**Features:**
- Automatic user tracking
- Timestamped entries
- Queryable by action type
- Detailed change metadata

---

### 4. **Controllers**

#### Web Controllers

**SecurityController** (`src/Controller/SecurityController.php`)
- `login()`: Login page & form processing
- `logout()`: Session termination

**RegistrationController** (`src/Controller/RegistrationController.php`)
- `register()`: User registration form
- Account creation with email

**ForgotPasswordController** (`src/Controller/ForgotPasswordController.php`)
- `requestPasswordReset()`: Email input page
- `confirmEmail()`: Email verification
- `resetPassword()`: New password form

**DashboardController** (`src/Controller/DashboardController.php`)
- `index()`: User dashboard overview
- Statistics and recent activities

**ClientController** (`src/Controller/ClientController.php`)
- `index()`: List all clients (paginated)
- `show()`: Client details with documents
- `new()`: Create client form
- `edit()`: Edit client information
- `delete()`: Delete client

**DocumentController** (`src/Controller/DocumentController.php`)
- `upload()`: File upload form/handler
- `download()`: File download stream
- `edit()`: Document metadata editor
- `delete()`: Soft delete document
- `restore()`: Restore soft-deleted document

**ProfileController** (`src/Controller/ProfileController.php`)
- `index()`: User profile page
- Account settings & preferences

#### API Controllers

**ForgotPasswordApiController** (`src/Controller/Api/Auth/ForgotPasswordApiController.php`)
- `requestPasswordReset()`: POST `/api/auth/forgot-password`
  - JSON request/response
  - Input validation
  - Returns generic success message

**Admin Controllers**
- User management endpoints
- Role and permission assignment

---

### 5. **API Platform Integration**

**Document API Resource**
```
GET    /api/documents              List documents
GET    /api/documents/{id}         Fetch single document
PUT    /api/documents/{id}         Update document
DELETE /api/documents/{id}         Delete document
```

**Features:**
- OpenAPI/Swagger documentation auto-generated
- Filtering and pagination support
- CORS handling via NelmioCorsBundle

---

### 6. **Frontend Technology Stack**

#### Templates (Twig)
- `base.html.twig` - Main layout
- `admin/` - Admin panel templates
- `client/` - Client management views
- `document/` - Document management pages
- `security/` - Authentication pages
- `emails/` - Email templates for password reset

#### Static Assets (Via Symfony Asset Mapper)
- `assets/app.js` - Main JavaScript entry
- `assets/stimulus_bootstrap.js` - Stimulus controller initialization
- `assets/styles/app.css` - Global styles

#### Stimulus Controllers
**Stimulus Framework** - Lightweight reactive controller framework
- `csrf_protection_controller.js` - CSRF token handling
- `hello_controller.js` - Example controller
- Custom controllers can be added for interactive features

#### UX Turbo Integration
- Turbo Drive - Turbo navigation (partial page loads)
- Real-time page transitions without full reloads
- Configured in `assets/ux_turbo.yaml`

---

## 🔧 Key Features & Workflows

### Document Management Workflow
```
User Upload
    ↓
Form Validation
    ↓
Vich Uploader → File System (var/uploads/)
    ↓
Document Entity Creation
    ↓
ActivityLog Created
    ↓
Email Notification (optional)
```

### Client-Document Relationship
```
Client (1) ──────── (Many) Documents
  ├─ nom
  ├─ prenom
  └─ documents[]
         ├─ title
         ├─ fileName
         └─ deletedAt (soft delete support)
```

### Authentication Flow
```
Login Form
    ↓
Email + Password Submission
    ↓
LoginFormAuthenticator validates
    ↓
Session created with User object
    ↓
Permissions checked on each request
```

### Password Reset Flow
```
User requests reset
    ↓
Email validation
    ↓
PasswordResetService generates token
    ↓
Email sent with reset link
    ↓
User clicks link (validates token)
    ↓
New password form
    ↓
Service hashes & updates password
    ↓
Confirmation email sent
```

---

## 🗄️ Database Schema Summary

### Tables (Generated via Doctrine Migrations)
- `user` - User accounts & authentication
- `client` - Client information
- `document` - Document metadata
- `activity_log` - User action audit trail
- `password_reset` - Password reset tokens

### Key Relationships
```
User ──────────────┐
                   ├──→ ActivityLog
Document ─────────┘
                   ├──→ ActivityLog
                   └──→ Client
Client ────────────┘    (OneToMany)
```

---

## 📊 Configuration & Deployment

### Environment Configuration
**Key Variables** (`.env`):
- `DATABASE_URL` - Database connection string
- `MAILER_DSN` - Email service (SMTP/mailgun/etc)
- `MAILER_FROM` - Sender email address
- `DEFAULT_URI` - Application base URL
- `APP_SECRET` - Encryption key for sessions

### Docker Support
- `docker-compose.yaml` - Production setup
- `compose.override.yaml` - Development overrides
- DDEV integration (`ddev` commands)

### Security Configuration
Located in `config/packages/security.yaml`:
- Password hashing: Auto-detection based on interface
- User provider: Database entity (User)
- Firewall rules: Dev tools excluded, main firewall with custom authenticator
- Access denied handler: Custom error pages

---

## 🚀 Technology Highlights

### Modern Symfony Best Practices
✅ Attribute-based routing (`#[Route]`)  
✅ Dependency injection & auto-wiring  
✅ Service layer separation  
✅ Database migrations (Doctrine)  
✅ Validation constraints  
✅ CSRF protection  
✅ Form builder pattern  

### Security Features
✅ Password hashing (Argon2i/bcrypt)  
✅ Rate limiting (password reset)  
✅ Email enumeration prevention  
✅ Soft delete (audit trail)  
✅ Activity logging  
✅ Granular permissions  
✅ CORS support  

### Developer Experience
✅ Symfony Maker Bundle (code generation)  
✅ Symfony Profiler (debugging)  
✅ Automated testing (PHPUnit)  
✅ Asset mapping (modern front-end)  
✅ Hot reload (Stimulus + Turbo)  

---

## 📝 Project Structure

```
project-root/
├── bin/                      # Executable files
│   ├── console              # Symfony CLI
│   └── phpunit              # Test runner
│
├── config/                  # Configuration files
│   ├── bundles.php          # Bundle registration
│   ├── services.yaml        # Dependency injection
│   ├── routes.yaml          # Route definitions
│   ├── packages/            # Package-specific config
│   │   ├── doctrine.yaml    # ORM configuration
│   │   ├── security.yaml    # Authentication/authorization
│   │   ├── mailer.yaml      # Email configuration
│   │   └── ...
│   └── routes/              # Additional routes
│
├── src/                     # Application code
│   ├── Controller/          # Request handlers
│   │   ├── Api/Auth/       # API authentication
│   │   └── Admin/          # Admin controllers
│   ├── Entity/              # Doctrine entities
│   │   ├── User.php
│   │   ├── Client.php
│   │   ├── Document.php
│   │   ├── ActivityLog.php
│   │   └── PasswordReset.php
│   ├── Repository/          # Query builders
│   ├── Service/             # Business logic
│   │   ├── PasswordResetService.php
│   │   └── ActivityLogger.php
│   ├── Security/            # Authenticators & voters
│   ├── Form/                # Form types
│   └── Kernel.php           # Application kernel
│
├── templates/               # Twig templates
│   ├── base.html.twig      # Base layout
│   ├── admin/              # Admin pages
│   ├── client/             # Client pages
│   ├── document/           # Document pages
│   ├── security/           # Auth pages
│   └── emails/             # Email templates
│
├── assets/                  # Frontend code
│   ├── app.js              # Main JS
│   ├── styles/app.css      # Main CSS
│   └── controllers/        # Stimulus controllers
│
├── migrations/              # Database migrations
├── tests/                   # Unit & integration tests
├── public/                  # Web root (index.php)
├── var/                     # Generated files
│   ├── cache/              # Application cache
│   ├── logs/               # Log files
│   └── uploads/            # User-uploaded files
│
├── composer.json            # PHP dependencies
├── docker-compose.yaml      # Docker configuration
└── README.md               # This file
```

---

## 🧪 Testing

**Test Files:**
- `tests/PasswordResetServiceTest.php` - Password reset logic tests
- `tests/PasswordValidationTest.php` - Password validation tests
- `phpunit.dist.xml` - PHPUnit configuration

**Running Tests:**
```bash
php bin/phpunit
# or via DDEV
ddev phpunit
```

---

## 🔐 Security Considerations

### Implemented
✅ CSRF tokens on all forms  
✅ Password hashing with secure algorithms  
✅ SQL injection prevention (Doctrine ORM)  
✅ XSS prevention (Twig auto-escaping)  
✅ Authentication required on protected routes  
✅ Permission-based access control  
✅ Audit logging of all sensitive operations  
✅ Email enumeration prevention on password reset  
✅ Rate limiting on password reset requests  
✅ Soft delete with restore capability  

### Best Practices
- Store only token hashes (not plain tokens)
- Token expiration (30 minutes)
- One-time use tokens
- Sanitized file uploads via Vich
- CORS restrictions configured

---

## 🚀 Deployment & Running

### Requirements
- PHP 8.2+
- MySQL/PostgreSQL
- Composer
- Node.js (for Stimulus/Turbo)

### Development Setup
```bash
# Install dependencies
ddev composer install

# Create database
ddev php bin/console doctrine:database:create
ddev php bin/console doctrine:migrations:migrate

# Start development server
ddev start
ddev open    # Opens in browser

# Watch assets
npm run watch
```

### Production Build
```bash
# Install with optimizations
ddev composer install --no-dev --optimize-autoloader

# Build assets
npm run build

# Run migrations
ddev php bin/console doctrine:migrations:migrate --env=prod

# Clear cache
ddev php bin/console cache:clear --env=prod
```

---

## 📈 Future Enhancement Opportunities

1. **Two-Factor Authentication (2FA)**
   - TOTP-based second factor
   - Backup codes

2. **Advanced Search & Filtering**
   - Full-text search on documents
   - Date range filters
   - Client tags/categories

3. **Document Versioning**
   - Track document changes
   - Rollback capability

4. **Sharing & Collaboration**
   - Share documents with external users
   - Collaborative editing
   - Comments/annotations

5. **Reporting & Analytics**
   - Activity dashboards
   - Document usage statistics
   - User engagement metrics

6. **Integration**
   - Cloud storage (AWS S3, Google Drive)
   - SSO (OAuth2, SAML)
   - Webhook notifications

7. **API Enhancements**
   - GraphQL support
   - OAuth2 authentication
   - Batch operations

---

## 📞 API Endpoints Summary

### Public (No Auth Required)
```
POST   /register                          User registration
GET    /forgot-password                   Password reset request page
POST   /forgot-password                   Submit email for reset
GET    /password-reset/{token}            Reset password form
POST   /password-reset                    Update password
GET    /login                             Login form
POST   /login                             Process login
POST   /api/auth/forgot-password          API password reset request
```

### Protected (Auth + Permissions Required)
```
GET    /dashboard                         Dashboard overview
GET    /profile                           User profile

CLIENTS:
GET    /clients                           List clients (needs: clients.view_list)
GET    /clients/{id}                      View client (needs: clients.view_details)
POST   /clients/new                       Create form (needs: clients.create)
POST   /clients                           Create client (needs: clients.create)
GET    /clients/{id}/edit                 Edit form (needs: clients.edit)
POST   /clients/{id}/edit                 Update client (needs: clients.edit)
DELETE /clients/{id}                      Delete client (needs: clients.delete)

DOCUMENTS:
GET    /documents                         List documents (needs: documents.view_list)
POST   /documents/upload                  Upload form (needs: documents.create_upload)
POST   /documents                         Save document (needs: documents.create_upload)
GET    /documents/{id}/download           Download file (needs: documents.download)
GET    /documents/{id}/edit               Edit form (needs: documents.edit)
POST   /documents/{id}/edit               Update doc (needs: documents.edit)
DELETE /documents/{id}                    Soft delete (needs: documents.delete)
GET    /documents/{id}/restore            Restore doc (needs: documents.edit)

ADMIN:
GET    /admin/users                       List users (needs: users.view)
```

---

## 📄 License

This project is licensed under the **MIT License** - see LICENSE file for details.

---

**Last Updated:** February 26, 2026  
**Version:** 1.0  
**Framework:** Symfony 7.4  
**Status:** Production

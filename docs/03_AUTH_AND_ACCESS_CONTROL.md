# TaxLab — Authentication & Access Control

## Document Information

| Item | Detail |
|------|--------|
| Document | Authentication & Access Control |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Security Reviewers |

---

## 1. Overview

TaxLab implements a multi-domain authentication system supporting three distinct user types with different access patterns, security requirements, and permission models.

### 1.1 Authentication Domains

| Domain | Users | Purpose |
|--------|-------|---------|
| Platform Admin | Internal team | Manage platform, tenants, content |
| Firm | Practitioners | Main application users |
| Client Portal | Tax clients | View reports and tax position |

Each domain operates independently with separate:
- User tables and models
- Authentication guards
- Session management
- Password policies
- Login routes

---

## 2. Platform Admin Authentication

### 2.1 User Model

**Table: admin_users**

| Field | Type | Description |
|-------|------|-------------|
| id | ULID | Primary identifier |
| email | string | Unique login email |
| password | string | Bcrypt hashed password |
| name | string | Display name |
| role | enum | super_admin, admin, support, content_manager |
| status | enum | active, suspended |
| two_factor_secret | string (encrypted) | TOTP secret |
| two_factor_confirmed_at | timestamp | 2FA setup completion |
| two_factor_recovery_codes | text (encrypted) | Backup codes |
| password_changed_at | timestamp | Last password change |
| last_login_at | timestamp | Last successful login |
| last_login_ip | string | IP of last login |
| created_at | timestamp | Account creation |
| updated_at | timestamp | Last modification |

### 2.2 Roles & Capabilities

**Super Admin**
- Full system access without restrictions
- Manage other admin users
- Access all tenant data
- Modify system configuration
- Manage billing and subscriptions
- Publish tax rules
- Impersonate firm users

**Admin**
- Manage tenant accounts
- View tenant data (support purposes)
- Manage CPD content
- View platform analytics
- Cannot modify system configuration
- Cannot manage other admin accounts
- Cannot impersonate users

**Support**
- View tenant data (read-only)
- Cannot modify any data
- Access support tools
- View logs and activity
- All actions logged for audit

**Content Manager**
- Manage CPD courses and modules
- Manage knowledge base articles
- Upload and organize content
- Cannot access tenant data
- Cannot access admin settings

### 2.3 Permission Matrix

| Permission | Super Admin | Admin | Support | Content Mgr |
|------------|-------------|-------|---------|-------------|
| View platform dashboard | ✓ | ✓ | ✓ | ✓ |
| Manage admin users | ✓ | ✗ | ✗ | ✗ |
| View tenant list | ✓ | ✓ | ✓ | ✗ |
| View tenant details | ✓ | ✓ | ✓ | ✗ |
| Modify tenant settings | ✓ | ✓ | ✗ | ✗ |
| Suspend/activate tenant | ✓ | ✓ | ✗ | ✗ |
| Impersonate firm user | ✓ | ✗ | ✗ | ✗ |
| Manage subscription plans | ✓ | ✗ | ✗ | ✗ |
| Configure pricing | ✓ | ✗ | ✗ | ✗ |
| Manage tax legislation | ✓ | ✓ | ✗ | ✗ |
| Publish tax rules | ✓ | ✗ | ✗ | ✗ |
| Manage CPD courses | ✓ | ✓ | ✗ | ✓ |
| Manage knowledge base | ✓ | ✓ | ✗ | ✓ |
| View platform analytics | ✓ | ✓ | ✓ | ✗ |
| Modify system settings | ✓ | ✗ | ✗ | ✗ |
| View audit logs | ✓ | ✓ | ✓ | ✗ |
| Export data | ✓ | ✓ | ✗ | ✗ |

### 2.4 Security Requirements

**Password Policy:**
- Minimum 16 characters
- Must contain: uppercase, lowercase, number, symbol
- Cannot match previous 5 passwords
- Maximum age: 90 days
- Forced change on first login

**Two-Factor Authentication:**
- Mandatory for all admin accounts
- TOTP-based (RFC 6238)
- 8 recovery codes generated at setup
- Recovery codes can be regenerated (invalidates old)
- 2FA required before accessing any protected route

**Session Management:**
- 60-minute session lifetime
- 15-minute idle timeout
- Single session per user (new login invalidates old)
- Sessions invalidated on password change
- Session bound to IP (optional, configurable)

**Login Security:**
- 5 failed attempts triggers 15-minute lockout
- Lockout escalates: 15min → 1hr → 24hr
- Suspicious login notifications
- Login history visible to user
- Concurrent session limit: 1

---

## 3. Firm User Authentication

### 3.1 User Model

**Table: firm_users**

| Field | Type | Description |
|-------|------|-------------|
| id | ULID | Primary identifier |
| firm_id | ULID | Tenant foreign key |
| email | string | Unique login email |
| password | string | Bcrypt hashed password |
| name | string | Display name |
| role | enum | partner, manager, associate, viewer |
| status | enum | invited, active, suspended |
| two_factor_secret | string (encrypted) | TOTP secret (optional) |
| two_factor_confirmed_at | timestamp | 2FA setup completion |
| two_factor_recovery_codes | text (encrypted) | Backup codes |
| email_verified_at | timestamp | Email verification |
| notification_preferences | json | Notification settings |
| last_login_at | timestamp | Last successful login |
| last_login_ip | string | IP of last login |
| invited_by | ULID | User who sent invitation |
| invited_at | timestamp | When invitation sent |
| created_at | timestamp | Account creation |
| updated_at | timestamp | Last modification |

### 3.2 Roles & Capabilities

**Partner / Owner**

The highest authority within a firm. Full control over firm operations and settings.

- Manage all firm settings and configuration
- Manage branding and customization
- Manage subscription and billing
- Invite and manage all users
- Access all tax clients
- Full CRUD on all resources
- Approve/reject reports (if workflow enabled)
- View firm analytics
- Export all data
- Delete firm account

**Manager**

Senior staff responsible for overseeing work and team members.

- Manage assigned team members
- Access all tax clients
- Full CRUD on clients and calculations
- Generate and approve reports
- Create and manage scenarios
- View firm analytics
- Cannot manage firm settings
- Cannot manage billing
- Cannot delete users (only suspend)

**Associate**

Day-to-day practitioners doing analysis work.

- Access assigned clients only
- Full CRUD on assigned clients
- Run calculations and scenarios
- Generate reports (may require approval)
- Cannot access unassigned clients
- Cannot manage users
- Cannot view firm settings
- Limited analytics (own work only)

**Viewer**

Read-only access for review purposes (interns, external auditors).

- Read-only access to assigned clients
- View calculations and reports
- Cannot create, edit, or delete anything
- Cannot run new calculations
- Cannot generate reports
- Useful for compliance review

### 3.3 Permission Matrix

| Permission | Partner | Manager | Associate | Viewer |
|------------|---------|---------|-----------|--------|
| **Client Access** |
| View assigned clients | ✓ | ✓ | ✓ | ✓ |
| View all firm clients | ✓ | ✓ | ✗ | ✗ |
| Create clients | ✓ | ✓ | ✓ | ✗ |
| Edit own clients | ✓ | ✓ | ✓ | ✗ |
| Edit any client | ✓ | ✓ | ✗ | ✗ |
| Archive clients | ✓ | ✓ | Own only | ✗ |
| Delete clients | ✓ | ✓ | ✗ | ✗ |
| Import clients | ✓ | ✓ | ✗ | ✗ |
| Export client data | ✓ | ✓ | Assigned | ✗ |
| **Financial Data** |
| View financials | ✓ | ✓ | Assigned | Assigned |
| Enter financials | ✓ | ✓ | Assigned | ✗ |
| Edit financials | ✓ | ✓ | Assigned | ✗ |
| **Calculations** |
| View calculations | ✓ | ✓ | Assigned | Assigned |
| Run calculations | ✓ | ✓ | Assigned | ✗ |
| **Scenarios** |
| View scenarios | ✓ | ✓ | Assigned | Assigned |
| Create scenarios | ✓ | ✓ | Assigned | ✗ |
| Edit scenarios | ✓ | ✓ | Own only | ✗ |
| Delete scenarios | ✓ | ✓ | Own only | ✗ |
| **Reports** |
| View reports | ✓ | ✓ | Assigned | Assigned |
| Generate reports | ✓ | ✓ | Assigned | ✗ |
| Approve reports | ✓ | ✓ | ✗ | ✗ |
| Delete reports | ✓ | ✓ | ✗ | ✗ |
| **Optimization** |
| View optimizations | ✓ | ✓ | Assigned | Assigned |
| Update optimization status | ✓ | ✓ | Assigned | ✗ |
| **Firm Management** |
| View firm settings | ✓ | ✗ | ✗ | ✗ |
| Edit firm settings | ✓ | ✗ | ✗ | ✗ |
| Manage branding | ✓ | ✗ | ✗ | ✗ |
| **User Management** |
| View all users | ✓ | ✓ | ✗ | ✗ |
| Invite users | ✓ | ✗ | ✗ | ✗ |
| Edit user roles | ✓ | ✗ | ✗ | ✗ |
| Suspend users | ✓ | ✗ | ✗ | ✗ |
| Delete users | ✓ | ✗ | ✗ | ✗ |
| Manage assignments | ✓ | ✓ | ✗ | ✗ |
| **Billing** |
| View billing | ✓ | ✗ | ✗ | ✗ |
| Manage subscription | ✓ | ✗ | ✗ | ✗ |
| Update payment | ✓ | ✗ | ✗ | ✗ |
| **Analytics** |
| View firm analytics | ✓ | ✓ | ✗ | ✗ |
| View personal stats | ✓ | ✓ | ✓ | ✓ |
| **Training** |
| Access CPD courses | ✓ | ✓ | ✓ | ✓ |
| Access knowledge base | ✓ | ✓ | ✓ | ✓ |
| **Client Portal** |
| Enable portal access | ✓ | ✓ | ✗ | ✗ |
| Manage portal users | ✓ | ✓ | ✗ | ✗ |

### 3.4 Client Assignment System

Associates and Viewers only have access to specifically assigned clients.

**Assignment Table: client_user_assignments**

| Field | Type | Description |
|-------|------|-------------|
| id | ULID | Primary identifier |
| firm_user_id | ULID | The user being assigned |
| tax_client_id | ULID | The client being assigned |
| assigned_by | ULID | User who made assignment |
| assigned_at | timestamp | When assignment created |
| notes | text | Optional assignment notes |

**Assignment Rules:**
- Partners and Managers are implicitly assigned to all clients
- Associates and Viewers require explicit assignment
- Assignment can be made by Partners or Managers
- User can be assigned to multiple clients
- Client can have multiple assigned users
- Removing assignment revokes all access immediately
- Assignment history retained for audit

### 3.5 Security Requirements

**Password Policy:**
- Minimum 10 characters
- Must contain: uppercase, lowercase, number
- Cannot match previous 3 passwords
- Maximum age: None (optional firm setting)
- Forced change: Optional (firm configurable)

**Two-Factor Authentication:**
- Optional by default
- Firm can mandate for all users
- Individual users can enable voluntarily
- TOTP-based (RFC 6238)
- 8 recovery codes generated

**Session Management:**
- 120-minute session lifetime (configurable)
- 30-minute idle timeout (configurable)
- Multiple sessions allowed (configurable)
- Sessions invalidated on password change
- Remember me: 30 days (optional)

**Login Security:**
- 5 failed attempts triggers 15-minute lockout
- Lockout notification sent to user
- Login from new device notification
- Login history available to user

---

## 4. Client Portal Authentication

### 4.1 User Model

**Table: client_portal_users**

| Field | Type | Description |
|-------|------|-------------|
| id | ULID | Primary identifier |
| tax_client_id | ULID | Link to tax client |
| firm_id | ULID | Tenant (denormalized for queries) |
| email | string | Login email |
| password | string | Bcrypt hashed password |
| name | string | Display name |
| role | enum | primary, viewer |
| status | enum | invited, active, suspended |
| two_factor_secret | string (encrypted) | TOTP secret (optional) |
| email_verified_at | timestamp | Email verification |
| last_login_at | timestamp | Last successful login |
| invited_by | ULID | Firm user who sent invitation |
| created_at | timestamp | Account creation |
| updated_at | timestamp | Last modification |

### 4.2 Roles & Capabilities

**Primary Contact**
- Main representative for the tax client
- Can view all reports and calculations
- Can invite additional portal viewers
- Receives notifications about new reports
- Can download all documents
- Can send messages to firm

**Viewer**
- Read-only access to tax position
- Can view reports shared with them
- Cannot invite others
- Cannot send messages
- Useful for junior staff at client company

### 4.3 Permission Matrix

| Permission | Primary | Viewer |
|------------|---------|--------|
| View tax position summary | ✓ | ✓ |
| View reports | ✓ | ✓ |
| Download reports | ✓ | ✓ |
| View scenarios (if shared) | ✓ | ✓ |
| View optimization recommendations | ✓ | ✓ |
| Send messages to firm | ✓ | ✗ |
| Invite additional viewers | ✓ | ✗ |
| Manage portal users | ✓ | ✗ |

### 4.4 Portal Access Control

**Access Is Controlled By:**
1. Firm must enable portal for the tax client
2. Portal user must be invited by firm or primary contact
3. Portal user must verify email
4. Tax client must be active (not archived)
5. Firm subscription must be active

**Content Visibility:**
- Only see data for their linked tax_client
- Only see reports marked as "published to portal"
- Only see scenarios explicitly shared
- Cannot see firm-internal notes or comments
- Cannot see other clients' data

### 4.5 Security Requirements

**Password Policy:**
- Minimum 8 characters
- Basic complexity (letter + number)
- No password history requirement
- No maximum age

**Two-Factor Authentication:**
- Optional
- Email OTP available as simpler alternative
- TOTP for security-conscious clients

**Session Management:**
- 60-minute session lifetime
- 20-minute idle timeout
- Single session recommended
- Remember me: 14 days

**Login Security:**
- 5 failed attempts triggers 30-minute lockout
- No escalating lockout (simpler for clients)
- Login notification emails

---

## 5. Authentication Flows

### 5.1 Standard Login Flow

```
User Enters Credentials
         │
         ▼
┌─────────────────┐
│ Validate Email  │
│ & Password      │
└────────┬────────┘
         │
         │ Invalid
         ├────────────────────────────┐
         │                            ▼
         │ Valid               ┌─────────────┐
         │                     │ Increment   │
         ▼                     │ Failed      │
┌─────────────────┐            │ Attempts    │
│ Check Account   │            └──────┬──────┘
│ Status          │                   │
└────────┬────────┘                   ▼
         │                     ┌─────────────┐
         │ Suspended           │ Check Lock  │──── Locked ────▶ Show Lockout
         ├──────────▶ Deny     │ Status      │                  Message
         │                     └──────┬──────┘
         │ Active                     │ Not Locked
         ▼                            ▼
┌─────────────────┐            ┌─────────────┐
│ Check 2FA       │            │ Show Error  │
│ Enabled?        │            │ Message     │
└────────┬────────┘            └─────────────┘
         │
         │ No 2FA
         ├────────────────────────────┐
         │                            │
         │ 2FA Enabled                │
         ▼                            │
┌─────────────────┐                   │
│ Show 2FA        │                   │
│ Challenge       │                   │
└────────┬────────┘                   │
         │                            │
         │ Valid Code                 │
         ▼                            ▼
┌─────────────────────────────────────────────┐
│              Create Session                  │
│  • Generate session token                    │
│  • Store session in Redis                    │
│  • Set session cookie                        │
│  • Log successful login                      │
│  • Reset failed attempt counter              │
└─────────────────────────────────────────────┘
         │
         ▼
    Redirect to Dashboard
```

### 5.2 Invitation & Registration Flow

```
Partner Invites User
         │
         ▼
┌─────────────────┐
│ Create Invite   │
│ Record          │
│ • Email         │
│ • Role          │
│ • Token         │
│ • Expiry (7d)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Send Invite     │
│ Email           │
└────────┬────────┘
         │
         ▼
User Clicks Link
         │
         ▼
┌─────────────────┐
│ Validate Token  │
│ & Expiry        │
└────────┬────────┘
         │
         │ Invalid/Expired
         ├────────────────▶ Show Error, Option to Resend
         │
         │ Valid
         ▼
┌─────────────────┐
│ Show Register   │
│ Form            │
│ • Name          │
│ • Password      │
│ • Confirm Pass  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Create User     │
│ Account         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Mark Invite     │
│ Accepted        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Auto Login      │
│ User            │
└────────┬────────┘
         │
         ▼
    Redirect to Dashboard
```

### 5.3 Password Reset Flow

```
User Requests Reset
         │
         ▼
┌─────────────────┐
│ Validate Email  │
│ Exists          │
└────────┬────────┘
         │
         │ Not Found
         ├────────────────▶ Show Same Success Message
         │                  (prevent email enumeration)
         │ Found
         ▼
┌─────────────────┐
│ Generate Reset  │
│ Token (1hr)     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Send Reset      │
│ Email           │
└────────┬────────┘
         │
         ▼
User Clicks Link
         │
         ▼
┌─────────────────┐
│ Validate Token  │
│ & Expiry        │
└────────┬────────┘
         │
         │ Invalid/Expired
         ├────────────────▶ Show Error, Option to Request New
         │
         │ Valid
         ▼
┌─────────────────┐
│ Show Password   │
│ Form            │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Validate New    │
│ Password        │
│ • Policy check  │
│ • History check │
└────────┬────────┘
         │
         │ Invalid
         ├────────────────▶ Show Errors
         │
         │ Valid
         ▼
┌─────────────────┐
│ Update Password │
│ • Hash password │
│ • Clear token   │
│ • Invalidate    │
│   sessions      │
│ • Log event     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Send Confirm    │
│ Email           │
└────────┬────────┘
         │
         ▼
    Redirect to Login
```

### 5.4 Two-Factor Setup Flow

```
User Enables 2FA
         │
         ▼
┌─────────────────┐
│ Generate TOTP   │
│ Secret          │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Show QR Code    │
│ & Manual Entry  │
│ Key             │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ User Scans &    │
│ Enters Code     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Validate Code   │
│ Against Secret  │
└────────┬────────┘
         │
         │ Invalid
         ├────────────────▶ Show Error, Try Again
         │
         │ Valid
         ▼
┌─────────────────┐
│ Generate 8      │
│ Recovery Codes  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Store Secret    │
│ & Recovery      │
│ Codes           │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Show Recovery   │
│ Codes           │
│ (Download/Copy) │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Require User    │
│ Confirmation    │
│ (Saved codes)   │
└────────┬────────┘
         │
         ▼
    2FA Enabled Successfully
```

---

## 6. Authorization Implementation

### 6.1 Policy-Based Authorization

Each resource type has a corresponding policy class that determines access.

**Policy Structure:**

```
Policies/
├── Admin/
│   ├── AdminUserPolicy
│   ├── TenantPolicy
│   ├── PlanPolicy
│   └── TaxRulePolicy
│
├── Firm/
│   ├── TaxClientPolicy
│   ├── CalculationPolicy
│   ├── ScenarioPolicy
│   ├── ReportPolicy
│   ├── FirmUserPolicy
│   └── FirmSettingsPolicy
│
└── Portal/
    ├── PortalReportPolicy
    └── PortalScenarioPolicy
```

**Policy Methods:**

Each policy implements standard methods:
- `viewAny` — Can user list resources?
- `view` — Can user view specific resource?
- `create` — Can user create new resource?
- `update` — Can user modify resource?
- `delete` — Can user delete resource?
- Plus custom methods as needed

### 6.2 Authorization Checks

**Layer 1: Route Middleware**

Routes grouped by required role:
```
/admin/* → requires admin guard + admin role
/app/* → requires firm guard + active status
/portal/* → requires client guard + portal enabled
```

**Layer 2: Controller Authorization**

Before any action, controller checks policy:
- `$this->authorize('view', $taxClient)`
- `$this->authorize('create', TaxClient::class)`

**Layer 3: Query Scopes**

Automatic filtering based on user context:
- Tenant scope (firm_id)
- Assignment scope (for associates/viewers)
- Status scope (active records only)

### 6.3 Permission Checking Logic

**For Firm Users Accessing Tax Client:**

```
Can User Access Client?
         │
         ▼
┌─────────────────┐
│ Is user in same │  No
│ firm as client? │────────▶ DENY
└────────┬────────┘
         │ Yes
         ▼
┌─────────────────┐
│ Is user Partner │  Yes
│ or Manager?     │────────▶ ALLOW
└────────┬────────┘
         │ No (Associate or Viewer)
         ▼
┌─────────────────┐
│ Is client       │  Yes
│ assigned to     │────────▶ ALLOW
│ user?           │
└────────┬────────┘
         │ No
         ▼
       DENY
```

**For Firm Users Modifying Resource:**

```
Can User Modify Resource?
         │
         ▼
┌─────────────────┐
│ Can user access │  No
│ parent client?  │────────▶ DENY
└────────┬────────┘
         │ Yes
         ▼
┌─────────────────┐
│ Is user Viewer? │  Yes
│                 │────────▶ DENY (read-only)
└────────┬────────┘
         │ No
         ▼
┌─────────────────┐
│ Does action     │  No
│ require higher  │────────▶ ALLOW
│ role?           │
└────────┬────────┘
         │ Yes
         ▼
┌─────────────────┐
│ Does user have  │  Yes
│ required role?  │────────▶ ALLOW
└────────┬────────┘
         │ No
         ▼
       DENY
```

---

## 7. Session Management

### 7.1 Session Storage

**Driver:** Redis

**Session Data Stored:**
- User ID and type
- Firm ID (for firm users)
- Client ID (for portal users)
- Login timestamp
- Last activity timestamp
- IP address
- User agent
- 2FA verification status

### 7.2 Session Lifecycle

**Creation:**
1. User successfully authenticates
2. 2FA verified (if enabled)
3. Session created in Redis
4. Session cookie set
5. Last login updated

**Validation (Every Request):**
1. Session cookie present?
2. Session exists in Redis?
3. Session not expired?
4. Idle timeout not exceeded?
5. User still active?
6. (Optional) IP matches?

**Termination:**
- User logout
- Session expires
- Idle timeout
- Password changed
- Account suspended
- Admin forced logout
- Maximum sessions exceeded (if enforced)

### 7.3 Session Security

**Cookie Settings:**
```
Name: taxlab_{domain}_session
HttpOnly: true
Secure: true (production)
SameSite: Strict
Path: /
Domain: .taxlab.ng
```

**Session Regeneration:**
- On login (prevent fixation)
- On privilege elevation
- On sensitive action confirmation

---

## 8. Audit Logging

### 8.1 Authentication Events Logged

| Event | Data Captured |
|-------|---------------|
| Login attempt | Email, IP, User Agent, Success/Failure |
| Login success | User ID, IP, Session ID, 2FA used |
| Login failure | Email, IP, Reason (invalid password, locked, etc.) |
| Logout | User ID, Session ID, Reason (manual, timeout, forced) |
| Password change | User ID, Changed by (self or admin) |
| Password reset request | Email, IP |
| Password reset complete | User ID, IP |
| 2FA enabled | User ID |
| 2FA disabled | User ID, Disabled by |
| 2FA recovery used | User ID, Code index |
| Session invalidated | User ID, Reason |
| Account locked | User ID, Trigger (failed attempts) |
| Account unlocked | User ID, Unlocked by |

### 8.2 Log Retention

| Log Type | Retention Period |
|----------|-----------------|
| Successful logins | 2 years |
| Failed logins | 1 year |
| Password changes | 2 years |
| 2FA events | 2 years |
| Session events | 90 days |
| Admin actions | 5 years |

### 8.3 Log Access

- Platform admins can view all authentication logs
- Firm partners can view their firm's authentication logs
- Individual users can view their own login history
- Logs are read-only and cannot be modified

---

## 9. Security Considerations

### 9.1 Attack Prevention

**Brute Force:**
- Rate limiting on login endpoints
- Account lockout after failed attempts
- CAPTCHA after multiple failures (future)
- Slow hash algorithm (bcrypt cost 12)

**Credential Stuffing:**
- Monitor for distributed attacks
- Block suspicious IP ranges
- Notify users of login from new device/location

**Session Hijacking:**
- Secure, HttpOnly cookies
- Session bound to IP (optional)
- Session regeneration on sensitive actions
- Short idle timeouts

**Privilege Escalation:**
- Policy checks on every action
- Role changes logged and notified
- No client-side role storage

### 9.2 Data Protection

**Passwords:**
- Bcrypt with cost factor 12
- Never logged or displayed
- Password history encrypted

**2FA Secrets:**
- AES-256 encryption at rest
- Recovery codes hashed
- Never exposed via API

**Sessions:**
- Encrypted session data
- Redis AUTH enabled
- No sensitive data in session cookie

### 9.3 Monitoring & Alerts

**Real-Time Alerts:**
- Multiple failed logins from same IP
- Successful login after multiple failures
- Admin account login
- Password change for any admin
- 2FA disabled for admin account

**Daily Reports:**
- Total login attempts (success/failure ratio)
- New user registrations
- Password resets requested
- Accounts locked
- Unusual activity patterns

---

## 10. Implementation Checklist

### 10.1 Phase 1: Core Authentication

- [ ] Admin user model and migration
- [ ] Firm user model and migration
- [ ] Authentication guards configuration
- [ ] Login/logout controllers per domain
- [ ] Password reset flow
- [ ] Email verification
- [ ] Session management
- [ ] Basic audit logging

### 10.2 Phase 2: Authorization

- [ ] Role enums and constants
- [ ] Policy classes for all resources
- [ ] Route middleware for role checks
- [ ] Client assignment system
- [ ] Query scopes for data filtering
- [ ] Permission checking helpers

### 10.3 Phase 3: Enhanced Security

- [ ] Two-factor authentication
- [ ] Account lockout system
- [ ] Login notifications
- [ ] Session management UI
- [ ] Comprehensive audit logging
- [ ] Security monitoring

### 10.4 Phase 4: Client Portal

- [ ] Portal user model
- [ ] Portal authentication guard
- [ ] Portal-specific policies
- [ ] Invitation flow
- [ ] Content visibility rules

---

*This document should be reviewed by security team before implementation and updated as requirements evolve.*

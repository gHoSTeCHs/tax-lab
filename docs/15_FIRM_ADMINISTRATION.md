# TaxLab — Firm Administration

## Document Information

| Item | Detail |
|------|--------|
| Document | Firm Administration Module |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team |

---

## 1. Overview

The Firm Administration module provides Partners with tools to configure their firm's settings, manage team members, customize branding, and handle billing. These settings affect the entire firm's experience on the platform.

### 1.1 Access

- **URL:** `/app/settings/*`
- **Guard:** firm
- **Roles:** Partner only (some sections Manager read-only)

### 1.2 Administration Areas

| Area | Description |
|------|-------------|
| Firm Profile | Basic firm information |
| Branding | Logo, colors, customization |
| User Management | Team members and roles |
| Billing | Subscription and payments |
| Security | Authentication settings |
| Preferences | Default behaviors |
| Data Management | Export and retention |

---

## 2. Firm Profile

### 2.1 Profile Settings

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Settings > Firm Profile                                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  FIRM INFORMATION                                                        │
│                                                                          │
│  Firm Name *                                                             │
│  [Acme Tax Consultants                                           ]      │
│                                                                          │
│  Trading Name (if different)                                             │
│  [                                                               ]      │
│                                                                          │
│  Registration Number (CAC/Professional)                                  │
│  [RC 123456                                                      ]      │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  CONTACT DETAILS                                                         │
│                                                                          │
│  Primary Email *                                                         │
│  [contact@acmetax.com                                            ]      │
│                                                                          │
│  Phone                                                                   │
│  [+234 1 234 5678                                                ]      │
│                                                                          │
│  Website                                                                 │
│  [www.acmetax.com                                                ]      │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  ADDRESS                                                                 │
│                                                                          │
│  Street Address                                                          │
│  [123 Marina Street                                              ]      │
│                                                                          │
│  City                             State                                  │
│  [Lagos                    ]      [Lagos ▼                       ]      │
│                                                                          │
│  Country                                                                 │
│  [Nigeria                                                        ]      │
│                                                                          │
│                                                     [Cancel] [Save]      │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Profile Fields

| Field | Required | Description |
|-------|----------|-------------|
| Firm Name | Yes | Legal/trading name |
| Trading Name | No | Alternative name if different |
| Registration Number | No | CAC or professional body |
| Primary Email | Yes | Main contact email |
| Phone | No | Contact phone |
| Website | No | Firm website URL |
| Street Address | No | Physical address |
| City | No | City |
| State | No | Nigerian state |

---

## 3. Branding

### 3.1 Branding Settings

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Settings > Branding                                                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  LOGO                                                                    │
│                                                                          │
│  ┌─────────────────────────────────┐                                    │
│  │                                 │                                    │
│  │        [Current Logo]           │                                    │
│  │                                 │                                    │
│  └─────────────────────────────────┘                                    │
│  [Upload New Logo]  [Remove]                                            │
│                                                                          │
│  Recommended: PNG or SVG, minimum 200px wide, max 2MB                   │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  COLORS                                                                  │
│                                                                          │
│  Primary Color                     Secondary Color                       │
│  [#1E40AF    ] [■]                [#3B82F6    ] [■]                     │
│                                                                          │
│  Used in report headers,           Used in charts,                       │
│  accent elements                   highlights                            │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  REPORT CUSTOMIZATION                                                    │
│                                                                          │
│  Footer Text                                                             │
│  [Confidential - Prepared exclusively for the addressee         ]      │
│                                                                          │
│  Include Firm Address in Reports   [✓]                                  │
│  Include Firm Phone in Reports     [✓]                                  │
│  Include Firm Email in Reports     [✓]                                  │
│  Include Firm Website in Reports   [ ]                                  │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  PREVIEW                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ [Preview of report cover page with current branding]                ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│                                                     [Cancel] [Save]      │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Branding Elements

| Element | Type | Usage |
|---------|------|-------|
| Logo | Image (PNG, SVG, JPG) | Reports, emails, portal |
| Primary Color | Hex color | Headers, accents |
| Secondary Color | Hex color | Charts, highlights |
| Footer Text | Text | Report footer |
| Contact Display | Toggles | What to show in reports |

### 3.3 Logo Requirements

- Formats: PNG, SVG, JPG
- Minimum width: 200px
- Maximum size: 2MB
- Recommended: Transparent background
- Aspect ratio: Preserved on upload

---

## 4. User Management

### 4.1 User List

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Settings > Users                                         [+ Invite User]│
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  TEAM MEMBERS (5 of 10 seats used)                                      │
│  ████████████████████░░░░░░░░░░░░░░░░░░░░                               │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ Name             Email                  Role       Status   Actions ││
│  ├─────────────────────────────────────────────────────────────────────┤│
│  │ John Smith       john@acmetax.com       Partner    Active   [⋮]    ││
│  │ Sarah Jones      sarah@acmetax.com      Manager    Active   [⋮]    ││
│  │ Mike Brown       mike@acmetax.com       Associate  Active   [⋮]    ││
│  │ Lisa Chen        lisa@acmetax.com       Associate  Active   [⋮]    ││
│  │ Tom Wilson       tom@acmetax.com        Viewer     Invited  [⋮]    ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  PENDING INVITATIONS (1)                                                │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ tom@acmetax.com    Viewer    Sent Dec 14    [Resend] [Cancel]      ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Invite User Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Invite Team Member                                                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Email Address *                                                         │
│  [newuser@example.com                                            ]      │
│                                                                          │
│  Name                                                                    │
│  [New User                                                       ]      │
│                                                                          │
│  Role *                                                                  │
│  ○ Partner    - Full access to everything including settings            │
│  ○ Manager    - Access to all clients, can manage team work             │
│  ● Associate  - Access to assigned clients only                         │
│  ○ Viewer     - Read-only access to assigned clients                    │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  Initial Client Assignments (optional for Associate/Viewer)              │
│  [Select clients to assign...                                    ▼]    │
│                                                                          │
│  Personal Message (optional)                                             │
│  [Welcome to the team! Please complete your account setup.      ]      │
│                                                                          │
│                                              [Cancel] [Send Invitation] │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 4.3 User Actions

| Action | Description |
|--------|-------------|
| Edit | Change name, role |
| Manage Assignments | Add/remove client assignments |
| Reset Password | Send password reset email |
| Disable 2FA | Remove 2FA (requires confirmation) |
| Suspend | Temporarily disable access |
| Reactivate | Re-enable suspended user |
| Remove | Remove from firm (with confirmation) |

### 4.4 Role Change Rules

- Partner can change any user's role
- Cannot demote last Partner
- Role change takes effect immediately
- User notified of role change

---

## 5. Billing

### 5.1 Billing Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Settings > Billing                                                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  CURRENT PLAN                                                            │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ Professional Plan                           ₦45,000/month           ││
│  │                                                                     ││
│  │ • Up to 100 clients                         Current: 67             ││
│  │ • Up to 3 team members                      Current: 5              ││
│  │ • 100 reports/month                         Used: 34                ││
│  │ • All features included                                             ││
│  │                                                                     ││
│  │ Next billing date: January 15, 2025                                 ││
│  │                                                                     ││
│  │ [Change Plan]                                                       ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  PAYMENT METHOD                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ 💳 Visa ending in 4242                     Expires 12/2026          ││
│  │                                                                     ││
│  │ [Update Payment Method]                                             ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  BILLING HISTORY                                                         │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ Date          Description              Amount    Status   Invoice   ││
│  ├─────────────────────────────────────────────────────────────────────┤│
│  │ Dec 15, 2024  Professional Plan        ₦45,000   Paid     [↓]      ││
│  │ Nov 15, 2024  Professional Plan        ₦45,000   Paid     [↓]      ││
│  │ Oct 15, 2024  Professional Plan        ₦45,000   Paid     [↓]      ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 5.2 Plan Change

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Change Plan                                                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Current Plan: Professional (₦45,000/month)                             │
│                                                                          │
│  SELECT NEW PLAN                                                         │
│                                                                          │
│  ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐            │
│  │ Starter         │ │ Professional    │ │ Business        │            │
│  │ ₦15,000/mo      │ │ ₦45,000/mo      │ │ ₦120,000/mo     │            │
│  │                 │ │ ✓ Current       │ │                 │            │
│  │ 30 clients      │ │ 100 clients     │ │ 300 clients     │            │
│  │ 1 user          │ │ 3 users         │ │ 10 users        │            │
│  │ 20 reports/mo   │ │ 100 reports/mo  │ │ Unlimited       │            │
│  │                 │ │                 │ │                 │            │
│  │ [Select]        │ │ [Current]       │ │ [Select]        │            │
│  └─────────────────┘ └─────────────────┘ └─────────────────┘            │
│                                                                          │
│  ⚠️ Downgrade Warning                                                    │
│  You currently have 67 clients but Starter only allows 30.              │
│  You'll need to archive clients before downgrading.                     │
│                                                                          │
│                                              [Cancel] [Confirm Change]  │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 5.3 Billing Events

| Event | Action |
|-------|--------|
| Payment Success | Receipt emailed, access continues |
| Payment Failed | Retry scheduled, notification sent |
| Payment Failed (Final) | Account suspended after grace period |
| Plan Upgrade | Immediate effect, pro-rated charge |
| Plan Downgrade | Effective next billing cycle |
| Cancellation | Access until end of paid period |

---

## 6. Security Settings

### 6.1 Security Configuration

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Settings > Security                                                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  TWO-FACTOR AUTHENTICATION                                               │
│                                                                          │
│  Require 2FA for all users         [ ]                                  │
│  When enabled, all team members must set up 2FA on their next login.   │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  SESSION SETTINGS                                                        │
│                                                                          │
│  Session Timeout (minutes)         [30      ▼]                          │
│  Users will be logged out after this period of inactivity.             │
│                                                                          │
│  Allow Multiple Sessions           [✓]                                  │
│  Users can be logged in from multiple devices simultaneously.          │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  PASSWORD POLICY                                                         │
│                                                                          │
│  Minimum Password Length           [10     ▼]                           │
│  Require Password Change           [ ] Every [90  ] days                │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  REPORT APPROVAL                                                         │
│                                                                          │
│  Require Approval Before Sharing   [✓]                                  │
│  Reports must be approved by Partner or Manager before being           │
│  shared with clients or published to the portal.                        │
│                                                                          │
│                                                     [Cancel] [Save]      │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Security Options

| Setting | Default | Description |
|---------|---------|-------------|
| Require 2FA | Off | Force 2FA for all users |
| Session Timeout | 30 min | Idle timeout duration |
| Multiple Sessions | On | Allow concurrent logins |
| Min Password Length | 10 | Minimum characters |
| Password Expiry | Off | Force periodic changes |
| Report Approval | Off | Require approval workflow |

---

## 7. Preferences

### 7.1 Firm Preferences

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Settings > Preferences                                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  DEFAULT FISCAL YEAR END                                                 │
│  [December ▼]                                                           │
│  Default for new clients (can be changed per client)                    │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  DEFAULT REPORT FORMAT                                                   │
│  ○ PDF (recommended)                                                    │
│  ○ Word Document                                                        │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  NOTIFICATIONS                                                           │
│                                                                          │
│  Email Notifications                                                     │
│  [✓] New reports pending approval                                       │
│  [✓] Reports approved/rejected                                          │
│  [✓] Team member activity summaries                                     │
│  [ ] Daily activity digest                                              │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  CLIENT PORTAL DEFAULTS                                                  │
│                                                                          │
│  Enable portal by default for new clients    [ ]                        │
│  Auto-publish approved reports to portal     [ ]                        │
│                                                                          │
│                                                     [Cancel] [Save]      │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 8. Data Management

### 8.1 Data Export

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Settings > Data Management                                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  EXPORT DATA                                                             │
│                                                                          │
│  Export all your firm's data for backup or migration purposes.         │
│                                                                          │
│  What to Export:                                                         │
│  [✓] Client list and profiles                                           │
│  [✓] Financial data                                                     │
│  [✓] Calculations and results                                           │
│  [✓] Reports (metadata only)                                            │
│  [ ] Report files (PDFs)                                                │
│  [✓] Scenarios                                                          │
│  [✓] Team member list                                                   │
│                                                                          │
│  Format: [CSV ▼]                                                        │
│                                                                          │
│  [Request Export]                                                       │
│                                                                          │
│  Export will be prepared and download link emailed within 24 hours.    │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  RECENT EXPORTS                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ Dec 1, 2024    All data (CSV)    152 MB    [Download]              ││
│  │ Nov 1, 2024    Clients only      2.3 MB    [Download]              ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 8.2 Data Retention

```
┌─────────────────────────────────────────────────────────────────────────┐
│  DATA RETENTION                                                          │
│                                                                          │
│  Archived Client Data                                                    │
│  Keep archived client data for: [3 years ▼]                             │
│  After this period, archived client data is permanently deleted.        │
│                                                                          │
│  Activity Logs                                                           │
│  Keep activity logs for: [2 years ▼]                                    │
│  Required for audit compliance.                                         │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  DELETE ACCOUNT                                                          │
│                                                                          │
│  ⚠️ Danger Zone                                                          │
│                                                                          │
│  Permanently delete your firm account and all associated data.          │
│  This action cannot be undone.                                          │
│                                                                          │
│  [Delete Account]                                                       │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 9. Data Model

### 9.1 Firm Settings Table

**Table: firm_settings**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| firm_id | ULID | Parent firm |
| key | varchar(100) | Setting key |
| value | jsonb | Setting value |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

### 9.2 Firm Branding Table

**Table: firm_branding**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| firm_id | ULID | Parent firm |
| logo_path | varchar(255) | Logo file path |
| primary_color | varchar(7) | Hex color |
| secondary_color | varchar(7) | Hex color |
| footer_text | text | Report footer |
| contact_display | jsonb | What to show |
| version | integer | Branding version |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

---

## 10. Implementation Checklist

### Phase 1: Core Settings

- [ ] Firm profile CRUD
- [ ] Basic branding (logo, colors)
- [ ] User management (list, invite, roles)
- [ ] User status management

### Phase 2: Billing

- [ ] Plan display and change
- [ ] Payment method management
- [ ] Billing history
- [ ] Invoice download

### Phase 3: Security & Preferences

- [ ] Security settings
- [ ] Notification preferences
- [ ] Default behaviors
- [ ] Report approval toggle

### Phase 4: Data Management

- [ ] Data export
- [ ] Retention settings
- [ ] Account deletion

---

*This document should be updated as firm administration requirements evolve during development.*

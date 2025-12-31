# TaxLab — Client Portal

## Document Information

| Item | Detail |
|------|--------|
| Document | Client Portal Module |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team |

---

## 1. Overview

The Client Portal provides tax clients (the businesses and individuals served by practitioners) with secure access to view their tax position, reports, and scenarios. It enhances the practitioner-client relationship by providing transparency and self-service access.

### 1.1 Access

- **URL:** `/portal/*`
- **Guard:** client
- **Users:** Tax client representatives

### 1.2 Value Proposition

**For Tax Clients:**
- 24/7 access to tax information
- Download reports anytime
- View scenario comparisons
- Transparency into advisory work

**For Practitioners:**
- Reduced "where's my report?" calls
- Professional client experience
- Differentiation from competitors
- Stronger client relationships

---

## 2. Portal Features

### 2.1 Feature Summary

| Feature | Description |
|---------|-------------|
| Tax Position Dashboard | Overview of current tax status |
| Report Library | Access to published reports |
| Scenario Viewer | Review shared scenarios |
| Document Download | Download PDFs and documents |
| Message Center | Communicate with practitioner |
| Profile Management | Update contact details |

### 2.2 What Clients See vs. Don't See

**Visible:**
- Published reports only
- Shared scenarios only
- High-level tax summaries
- Practitioner contact info
- Their own profile

**Not Visible:**
- Draft reports
- Internal notes
- Calculation details
- Optimization tracker
- Other clients
- Firm internal data

---

## 3. User Interface

### 3.1 Portal Dashboard

```
┌─────────────────────────────────────────────────────────────────────────┐
│  [Firm Logo]                                              [User Menu ▼] │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Welcome back, James                                                     │
│  ABC Trading Limited                                                     │
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ TAX POSITION SUMMARY                               Fiscal Year 2024│ │
│  │                                                                     │ │
│  │ ┌─────────────────────┐  ┌─────────────────────┐                   │ │
│  │ │ Total Tax Liability │  │ Change from Old     │                   │ │
│  │ │                     │  │ Regime              │                   │ │
│  │ │ ₦11,595,000         │  │ -₦1,159,433 (↓9.1%)│                   │ │
│  │ │ Under NTA 2025      │  │ You Save!           │                   │ │
│  │ └─────────────────────┘  └─────────────────────┘                   │ │
│  │                                                                     │ │
│  │ Last updated: December 15, 2024                                    │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  ┌──────────────────────────────────┐  ┌────────────────────────────┐   │
│  │ RECENT REPORTS                   │  │ YOUR ADVISOR               │   │
│  │                                  │  │                            │   │
│  │ 📄 Tax Impact Analysis          │  │ Sarah Johnson              │   │
│  │    Dec 15, 2024                 │  │ Senior Tax Manager         │   │
│  │    [View] [Download]            │  │                            │   │
│  │                                  │  │ 📧 sarah@acmetax.com      │   │
│  │ 📄 Advisory Letter              │  │ 📞 +234 1 234 5678        │   │
│  │    Dec 10, 2024                 │  │                            │   │
│  │    [View] [Download]            │  │ [Send Message]             │   │
│  │                                  │  │                            │   │
│  │ [View All Reports →]            │  └────────────────────────────┘   │
│  └──────────────────────────────────┘                                   │
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ SHARED SCENARIOS                                                    │ │
│  │                                                                     │ │
│  │ Your advisor has shared 2 scenarios for your review:               │ │
│  │                                                                     │ │
│  │ 📊 Expansion Scenario (Revenue +30%)                               │ │
│  │ 📊 Cost Reduction Scenario                                         │ │
│  │                                                                     │ │
│  │ [View Scenarios →]                                                  │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Reports Library

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Reports                                                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Filter: [All Types ▼]  Year: [2024 ▼]                                  │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │ 📄 Tax Impact Analysis Report                                        ││
│  │    Fiscal Year 2024 | Published Dec 15, 2024                        ││
│  │    15 pages | PDF                                                   ││
│  │                                                                      ││
│  │    Comprehensive analysis of NTA 2025 impact on your business       ││
│  │    including comparison of old vs. new regime and recommendations.  ││
│  │                                                                      ││
│  │    [View Online]  [Download PDF]                                    ││
│  │                                                                      ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │ 📄 Advisory Letter                                                   ││
│  │    Fiscal Year 2024 | Published Dec 10, 2024                        ││
│  │    3 pages | PDF                                                    ││
│  │                                                                      ││
│  │    Executive summary of key findings and recommended actions.       ││
│  │                                                                      ││
│  │    [View Online]  [Download PDF]                                    ││
│  │                                                                      ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │ 📄 Tax Computation Report                                            ││
│  │    Fiscal Year 2023 | Published Mar 5, 2024                         ││
│  │    12 pages | PDF                                                   ││
│  │                                                                      ││
│  │    [View Online]  [Download PDF]                                    ││
│  │                                                                      ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.3 Scenario Viewer

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Scenarios                                                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Your advisor has shared the following scenarios to help you            │
│  understand different strategic options:                                 │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ Expansion Scenario                                        Shared by ││
│  │ Revenue Growth of 30%                                   Sarah J.    ││
│  │                                                                      ││
│  │ This scenario models the tax impact if your revenue grows by 30%    ││
│  │ in the next fiscal year.                                            ││
│  │                                                                      ││
│  │ ┌─────────────────────┬─────────────────────┬─────────────────────┐ ││
│  │ │                     │ Current             │ Expansion           │ ││
│  │ ├─────────────────────┼─────────────────────┼─────────────────────┤ ││
│  │ │ Revenue             │ ₦150,000,000        │ ₦195,000,000        │ ││
│  │ │ Projected Profit    │ ₦20,000,000         │ ₦32,000,000         │ ││
│  │ │ Total Tax (NTA)     │ ₦11,595,000         │ ₦15,800,000         │ ││
│  │ │ Effective Rate      │ 57.98%              │ 49.38%              │ ││
│  │ └─────────────────────┴─────────────────────┴─────────────────────┘ ││
│  │                                                                      ││
│  │ ADVISOR NOTES:                                                       ││
│  │ "Growth improves your effective tax rate due to economies of scale. ││
│  │ We should discuss timing strategies to optimize the transition."    ││
│  │                                                                      ││
│  │ [Request Meeting to Discuss]                                        ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.4 Message Center

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Messages                                                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │ Conversation with Acme Tax Consultants                              ││
│  │                                                                      ││
│  │ ┌─────────────────────────────────────────────────────────────────┐ ││
│  │ │ Sarah Johnson • Dec 15, 2024 at 2:30 PM                         │ ││
│  │ │                                                                  │ ││
│  │ │ Hi James, I've published your Tax Impact Analysis report. Please│ ││
│  │ │ review at your convenience and let me know if you have any      │ ││
│  │ │ questions. I'd recommend we schedule a call to discuss the      │ ││
│  │ │ optimization opportunities identified.                          │ ││
│  │ └─────────────────────────────────────────────────────────────────┘ ││
│  │                                                                      ││
│  │ ┌─────────────────────────────────────────────────────────────────┐ ││
│  │ │ You • Dec 15, 2024 at 4:15 PM                                   │ ││
│  │ │                                                                  │ ││
│  │ │ Thanks Sarah! I've reviewed the report. The VAT savings look    │ ││
│  │ │ significant. Can we schedule a call for Thursday to discuss?    │ ││
│  │ └─────────────────────────────────────────────────────────────────┘ ││
│  │                                                                      ││
│  │ ┌─────────────────────────────────────────────────────────────────┐ ││
│  │ │ Sarah Johnson • Dec 15, 2024 at 4:45 PM                         │ ││
│  │ │                                                                  │ ││
│  │ │ Thursday works! I'll send a calendar invite for 10 AM.          │ ││
│  │ └─────────────────────────────────────────────────────────────────┘ ││
│  │                                                                      ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ Type your message...                                       [Send]  ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Portal Administration

### 4.1 Enabling Portal Access

From the firm side, practitioners enable portal:

1. Go to client profile
2. Navigate to "Portal" tab
3. Toggle "Enable Client Portal"
4. Invite portal users

### 4.2 Inviting Portal Users

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Invite Portal User                                                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Client: ABC Trading Limited                                             │
│                                                                          │
│  Name: [James Okonkwo                                  ]                │
│                                                                          │
│  Email: [james@abctrading.com                          ]                │
│                                                                          │
│  Role:  ● Primary Contact                                               │
│         ○ Viewer                                                        │
│                                                                          │
│  Primary contacts can invite additional viewers and send messages.      │
│  Viewers have read-only access to shared content.                       │
│                                                                          │
│                                              [Cancel]  [Send Invitation]│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 4.3 Managing Portal Users

From client profile, practitioners can:
- View all portal users
- Change roles
- Revoke access
- Resend invitations
- View last login

### 4.4 Publishing Content

**Publishing Reports:**
1. Report must be "Approved" status
2. Click "Publish to Portal"
3. Select visibility options
4. Confirm publication
5. Client notified

**Sharing Scenarios:**
1. Open scenario
2. Click "Share to Portal"
3. Add advisor notes
4. Select what to show
5. Confirm sharing
6. Client notified

---

## 5. Branding & Customization

### 5.1 Portal Branding

Portal displays firm branding:
- Firm logo (header)
- Firm colors (accents)
- Firm name
- Firm contact details
- Custom footer text

### 5.2 No TaxLab Branding

Portal shows NO TaxLab branding:
- No "Powered by" text
- No TaxLab logo
- Custom domain (future)
- Fully white-labeled

### 5.3 URL Structure

Current: `app.taxlab.ng/portal/`

Future (custom domains):
- `portal.acmetax.com`
- Firm-specific subdomains

---

## 6. Security

### 6.1 Authentication

**Login Process:**
1. Email + password
2. Optional 2FA (email OTP or TOTP)
3. Session created
4. Redirect to dashboard

**Password Requirements:**
- Minimum 8 characters
- Letter + number required
- No password history

### 6.2 Session Management

- 60-minute session lifetime
- 20-minute idle timeout
- Single session recommended
- Remember me: 14 days

### 6.3 Access Control

**Data Isolation:**
- Users only see their linked tax_client
- Cannot access other clients
- Cannot access firm data

**Content Filtering:**
- Only published reports visible
- Only shared scenarios visible
- No access to drafts or internal notes

---

## 7. Data Model

### 7.1 Portal Users Table

**Table: client_portal_users**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| tax_client_id | ULID | Linked tax client |
| firm_id | ULID | Tenant (denormalized) |
| email | varchar(255) | Login email |
| password | varchar(255) | Hashed password |
| name | varchar(255) | Display name |
| role | enum | primary, viewer |
| status | enum | invited, active, suspended |
| two_factor_secret | varchar(255) | 2FA secret (encrypted) |
| email_verified_at | timestamp | Verification date |
| last_login_at | timestamp | Last login |
| last_login_ip | varchar(45) | Last IP |
| invited_by | ULID | Firm user who invited |
| invitation_token | varchar(100) | Invite token |
| invitation_expires_at | timestamp | Token expiry |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

### 7.2 Portal Messages Table

**Table: portal_messages**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| tax_client_id | ULID | Client context |
| sender_type | varchar(50) | portal_user or firm_user |
| sender_id | ULID | Sender ID |
| content | text | Message content |
| read_at | timestamp | When read by recipient |
| created_at | timestamp | Sent date |

### 7.3 Portal Activity Table

**Table: portal_activity**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| portal_user_id | ULID | User |
| action | varchar(50) | Action type |
| resource_type | varchar(50) | Report, Scenario, etc. |
| resource_id | ULID | Resource accessed |
| ip_address | varchar(45) | Client IP |
| user_agent | text | Browser info |
| created_at | timestamp | When occurred |

---

## 8. API Endpoints

### 8.1 Portal Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /portal/login | Login |
| POST | /portal/logout | Logout |
| POST | /portal/password/forgot | Request reset |
| POST | /portal/password/reset | Reset password |

### 8.2 Portal Content

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /portal/api/dashboard | Dashboard data |
| GET | /portal/api/reports | List reports |
| GET | /portal/api/reports/{id} | Report details |
| GET | /portal/api/reports/{id}/download | Download PDF |
| GET | /portal/api/scenarios | List scenarios |
| GET | /portal/api/scenarios/{id} | Scenario details |

### 8.3 Portal Messages

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /portal/api/messages | List messages |
| POST | /portal/api/messages | Send message |
| POST | /portal/api/messages/{id}/read | Mark read |

### 8.4 Firm-Side Portal Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/clients/{id}/portal/enable | Enable portal |
| POST | /api/clients/{id}/portal/disable | Disable portal |
| GET | /api/clients/{id}/portal/users | List portal users |
| POST | /api/clients/{id}/portal/users | Invite user |
| DELETE | /api/clients/{id}/portal/users/{uid} | Revoke access |
| POST | /api/reports/{id}/publish | Publish to portal |
| POST | /api/scenarios/{id}/share | Share to portal |

---

## 9. Implementation Checklist

### Phase 1: Core Portal

- [ ] Portal user model and authentication
- [ ] Portal dashboard
- [ ] Report viewing and download
- [ ] Basic branding

### Phase 2: Enhanced Features

- [ ] Scenario viewer
- [ ] Message center
- [ ] Profile management
- [ ] Email notifications

### Phase 3: Administration

- [ ] Portal enable/disable
- [ ] User invitation flow
- [ ] Content publishing
- [ ] Activity logging

### Phase 4: Polish

- [ ] Custom domains (future)
- [ ] Enhanced security
- [ ] Mobile optimization
- [ ] Accessibility

---

*This document should be updated as client portal requirements evolve during development.*

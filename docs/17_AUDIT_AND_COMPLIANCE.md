# TaxLab — Audit & Compliance

## Document Information

| Item | Detail |
|------|--------|
| Document | Audit & Compliance System |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team, Security |

---

## 1. Overview

The Audit & Compliance system ensures TaxLab maintains comprehensive records of all activities, supports regulatory requirements, and enables investigation of issues. This is critical for a platform handling sensitive tax and financial data.

### 1.1 Compliance Requirements

| Requirement | Description |
|-------------|-------------|
| NDPR | Nigeria Data Protection Regulation |
| Professional Standards | ICAN/CITN requirements for practitioners |
| Tax Authority | NRS audit support requirements |
| Platform Integrity | Internal security and operations |

### 1.2 Audit Objectives

- **Accountability:** Track who did what, when
- **Integrity:** Detect unauthorized changes
- **Compliance:** Meet regulatory requirements
- **Investigation:** Support issue resolution
- **Security:** Detect suspicious activity

---

## 2. Activity Logging

### 2.1 Logged Events

**Authentication Events**

| Event | Data Captured |
|-------|---------------|
| Login Attempt | User, IP, User Agent, Success/Failure, Reason |
| Login Success | User, IP, User Agent, Session ID |
| Logout | User, Session ID, Reason (manual/timeout/forced) |
| Password Change | User, Changed By |
| Password Reset Request | Email, IP |
| Password Reset Complete | User, IP |
| 2FA Enabled | User |
| 2FA Disabled | User, Disabled By |
| 2FA Challenge | User, Success/Failure |

**Data Access Events**

| Event | Data Captured |
|-------|---------------|
| Client Viewed | User, Client ID |
| Financial Data Viewed | User, Client ID, Year |
| Calculation Viewed | User, Calculation ID |
| Report Downloaded | User, Report ID |
| Export Generated | User, Export Type, Data Scope |

**Data Modification Events**

| Event | Data Captured |
|-------|---------------|
| Client Created | User, Client Data |
| Client Updated | User, Client ID, Changes (before/after) |
| Client Archived | User, Client ID, Reason |
| Client Deleted | User, Client ID |
| Financial Data Updated | User, Client ID, Year, Changes |
| Calculation Performed | User, Client ID, Type, Inputs |
| Report Generated | User, Client ID, Report Type |
| Report Approved/Rejected | User, Report ID, Decision |

**Administrative Events**

| Event | Data Captured |
|-------|---------------|
| User Invited | Inviter, Invitee Email, Role |
| User Role Changed | Changer, User ID, Old Role, New Role |
| User Suspended | Admin, User ID, Reason |
| User Removed | Admin, User ID |
| Settings Changed | User, Setting, Old Value, New Value |
| Branding Updated | User, Changes |
| Plan Changed | User, Old Plan, New Plan |

**Platform Admin Events**

| Event | Data Captured |
|-------|---------------|
| Tenant Accessed | Admin, Tenant ID, Reason |
| Impersonation Started | Admin, Target User, Reason |
| Impersonation Ended | Admin, Target User, Duration |
| Tax Rule Published | Admin, Rule ID, Version |
| Plan Configuration Changed | Admin, Plan ID, Changes |

### 2.2 Log Entry Structure

```json
{
  "id": "01HXK5...",
  "timestamp": "2024-12-15T14:30:45.123Z",
  "event_type": "client.updated",
  "actor": {
    "type": "firm_user",
    "id": "01HXK4...",
    "email": "sarah@acmetax.com",
    "role": "associate"
  },
  "tenant": {
    "id": "01HXK3...",
    "name": "Acme Tax Consultants"
  },
  "target": {
    "type": "tax_client",
    "id": "01HXK2...",
    "name": "ABC Trading Ltd"
  },
  "changes": {
    "before": {
      "phone": "+234 1 234 5678"
    },
    "after": {
      "phone": "+234 1 987 6543"
    }
  },
  "context": {
    "ip_address": "102.89.23.45",
    "user_agent": "Mozilla/5.0...",
    "session_id": "sess_01HXK6...",
    "request_id": "req_01HXK7..."
  }
}
```

### 2.3 Log Storage

**Primary Storage:** PostgreSQL table for recent logs (90 days)
**Archive Storage:** Compressed files for older logs (2+ years)
**Immutability:** Logs are append-only, never modified or deleted

---

## 3. Calculation Audit Trail

### 3.1 Purpose

Tax calculations have special audit requirements because:
- Results may be challenged by tax authorities
- Historical calculations must be reproducible
- Changes in rules must not affect past results

### 3.2 Calculation Record Structure

```json
{
  "calculation_id": "01HXK8...",
  "timestamp": "2024-12-15T14:30:45.123Z",
  "performer": {
    "id": "01HXK4...",
    "name": "Sarah Jones"
  },
  "client": {
    "id": "01HXK2...",
    "name": "ABC Trading Ltd",
    "tin": "12345678-0001"
  },
  "fiscal_year": 2024,
  "calculation_type": "full_analysis",
  "tax_rules_version": "v2.1.0",
  "inputs_snapshot": {
    "turnover": 150000000,
    "profit_before_tax": 20000000,
    "... complete input data ..."
  },
  "results_snapshot": {
    "old_regime": { "...": "..." },
    "new_regime": { "...": "..." }
  },
  "processing_time_ms": 2340,
  "checksum": "sha256:a1b2c3..."
}
```

### 3.3 Reproducibility

To reproduce a historical calculation:
1. Load the stored inputs_snapshot
2. Load the tax rules at the stored version
3. Run calculation engine
4. Compare results to stored results_snapshot
5. Verify checksum matches

### 3.4 Versioned Tax Rules

Tax rules are versioned so historical calculations remain accurate:
- Each rule has a version number
- New guidance creates new version
- Calculations reference specific version
- Old versions never deleted

---

## 4. Report Audit Trail

### 4.1 Report Lifecycle Tracking

Every report tracks its full lifecycle:

| Stage | Recorded Data |
|-------|---------------|
| Generated | User, timestamp, config, calculation used |
| Reviewed | Reviewers who viewed |
| Approved | Approver, timestamp, comments |
| Rejected | Rejector, timestamp, reason |
| Published | Publisher, timestamp, channel |
| Downloaded | User, timestamp |
| Shared | Recipient, timestamp, method |

### 4.2 Report Integrity

Reports include integrity verification:
- SHA-256 hash of PDF content
- Hash stored in database
- Verification possible anytime
- Detects any tampering

---

## 5. User Interface

### 5.1 Activity Log View (Partners)

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Activity Log                                                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Filter: User [All ▼]  Action [All ▼]  Date [Last 7 days ▼]  [Search]  │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ Dec 15, 2024                                                        ││
│  ├─────────────────────────────────────────────────────────────────────┤│
│  │ 14:30  Sarah Jones updated client ABC Trading Ltd                   ││
│  │        Changed: phone                                               ││
│  │                                                                     ││
│  │ 14:25  Sarah Jones viewed financial data for ABC Trading Ltd        ││
│  │                                                                     ││
│  │ 14:20  John Smith approved report for XYZ Manufacturing             ││
│  │        Comment: "Looks good, ready to send"                         ││
│  │                                                                     ││
│  │ 13:45  Mike Brown ran calculation for John Doe                      ││
│  │        Type: Full Analysis                                          ││
│  │                                                                     ││
│  │ 11:30  Sarah Jones logged in                                        ││
│  │        IP: 102.89.23.45                                             ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  [Export Log]                                    [Load More]            │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 5.2 Client Activity History

```
┌─────────────────────────────────────────────────────────────────────────┐
│  ABC Trading Ltd > Activity History                                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  All activity related to this client                                    │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ Dec 15  Sarah Jones updated client details                          ││
│  │ Dec 15  Sarah Jones viewed financial data (2024)                    ││
│  │ Dec 14  Sarah Jones ran full analysis                               ││
│  │ Dec 14  Sarah Jones generated Tax Impact Report                     ││
│  │ Dec 14  John Smith approved report                                  ││
│  │ Dec 10  Mike Brown created client                                   ││
│  │ Dec 10  Mike Brown entered financial data (2024)                    ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 5.3 Personal Activity (All Users)

Users can view their own activity:

```
┌─────────────────────────────────────────────────────────────────────────┐
│  My Activity                                                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Your recent activity on the platform                                   │
│                                                                          │
│  TODAY                                                                   │
│  • 14:30 - Updated client ABC Trading Ltd                               │
│  • 14:25 - Viewed financials for ABC Trading Ltd                        │
│  • 11:30 - Logged in from 102.89.23.45                                  │
│                                                                          │
│  YESTERDAY                                                               │
│  • 16:45 - Generated report for XYZ Manufacturing                       │
│  • 15:20 - Ran calculation for John Doe                                 │
│  • ...                                                                  │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 6. Data Protection (NDPR)

### 6.1 Consent Management

**Data Collection Consent:**
- Clear privacy policy at signup
- Consent checkbox required
- Consent timestamp recorded
- Policy version tracked

**Processing Purposes:**
- Tax calculation services
- Report generation
- Training delivery
- Service improvement

### 6.2 Data Subject Rights

**Right to Access:**
- Users can export all their data
- Structured format (JSON/CSV)
- Delivered within 30 days

**Right to Rectification:**
- Users can correct inaccurate data
- Changes logged with audit trail
- Notification of corrections

**Right to Erasure:**
- Account deletion available
- 30-day recovery window
- Permanent deletion after
- Audit logs anonymized (not deleted)

**Right to Portability:**
- Export in machine-readable format
- Include all user-generated content
- Standard structure

### 6.3 Data Retention

| Data Type | Retention Period | After Retention |
|-----------|------------------|-----------------|
| Active account data | Duration of account | See below |
| Archived clients | Configurable (1-5 years) | Deleted |
| Activity logs | 2 years minimum | Archived/Anonymized |
| Calculation records | 7 years | Archived |
| Financial data | 7 years | Archived |
| Reports | 7 years | Archived |
| Deleted accounts | 30 days (recovery) | Permanent deletion |

### 6.4 Data Breach Procedures

**If breach detected:**
1. Immediate containment
2. Assessment of scope
3. Notification to affected users (within 72 hours)
4. Notification to NITDA (if required)
5. Remediation actions
6. Post-incident review

---

## 7. Security Monitoring

### 7.1 Suspicious Activity Detection

**Monitored Patterns:**

| Pattern | Threshold | Action |
|---------|-----------|--------|
| Failed logins | 5 in 15 min | Account lockout |
| Unusual location | New country | Email alert to user |
| Bulk data access | 100+ records/min | Alert admin |
| After-hours access | Outside business hours | Log for review |
| Privilege escalation | Role change | Alert admin |
| Mass deletion | 10+ records | Require confirmation |

### 7.2 Security Alerts

**Real-Time Alerts:**
- Multiple failed login attempts
- Successful login after failures
- Admin account activity
- Impersonation events
- Data export requests

**Daily Security Report:**
- Login summary
- Failed authentication attempts
- Suspicious patterns detected
- Data access anomalies

### 7.3 Security Dashboard (Admins)

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Security Dashboard                                                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  LAST 24 HOURS                                                          │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐   │
│  │ Logins       │ │ Failed Auth  │ │ Data Exports │ │ Alerts       │   │
│  │    247       │ │     12       │ │      3       │ │      0       │   │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘   │
│                                                                          │
│  ACTIVE ALERTS                                                           │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ No active security alerts                                           ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  RECENT SECURITY EVENTS                                                  │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ 14:30  Failed login attempt - user@example.com (3rd attempt)       ││
│  │ 13:45  Data export requested - Acme Tax Consultants                ││
│  │ 11:20  New device login - sarah@acmetax.com                        ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 8. Data Model

### 8.1 Activity Logs Table

**Table: activity_logs**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| event_type | varchar(100) | Type of event |
| actor_type | varchar(100) | Actor model type |
| actor_id | ULID | Actor ID |
| firm_id | ULID | Tenant (nullable for admin) |
| target_type | varchar(100) | Target model type |
| target_id | ULID | Target ID |
| changes | jsonb | Before/after for modifications |
| context | jsonb | IP, user agent, etc. |
| created_at | timestamp | When event occurred |

**Indexes:**
- (firm_id, created_at) - Tenant log queries
- (actor_id, created_at) - User activity
- (target_type, target_id) - Resource history
- (event_type, created_at) - Event type analysis

### 8.2 Calculation Audits Table

**Table: calculation_audits**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| calculation_id | ULID | Parent calculation |
| tax_client_id | ULID | Client |
| firm_id | ULID | Tenant |
| fiscal_year | integer | Year |
| calculation_type | varchar(50) | Type |
| rules_version | varchar(20) | Tax rules version |
| inputs_snapshot | jsonb | Complete inputs |
| results_snapshot | jsonb | Complete results |
| performed_by | ULID | User |
| checksum | varchar(100) | SHA-256 hash |
| created_at | timestamp | When performed |

### 8.3 Consent Records Table

**Table: consent_records**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| user_type | varchar(100) | User model type |
| user_id | ULID | User |
| consent_type | varchar(100) | Type of consent |
| policy_version | varchar(20) | Policy version |
| granted | boolean | Consent given |
| ip_address | varchar(50) | IP at consent |
| created_at | timestamp | When consented |

---

## 9. Compliance Reports

### 9.1 Available Reports

**Activity Summary Report:**
- Total logins by user
- Actions performed by type
- Data access patterns
- Date range configurable

**Access Audit Report:**
- Who accessed what data
- When access occurred
- Exportable for regulators

**Data Inventory Report:**
- What data is stored
- Where it's stored
- Retention status
- NDPR compliance check

**Security Incident Report:**
- Security events
- Response actions
- Resolution status

### 9.2 Report Generation

Partners can generate compliance reports:
- Select report type
- Choose date range
- Generate PDF or CSV
- Download or email

---

## 10. Implementation Checklist

### Phase 1: Core Logging

- [ ] Activity log model and storage
- [ ] Event logging service
- [ ] Authentication event logging
- [ ] Data modification logging
- [ ] Basic log viewer

### Phase 2: Calculation Audit

- [ ] Calculation audit model
- [ ] Input/output snapshots
- [ ] Rule version tracking
- [ ] Reproducibility verification
- [ ] Checksum generation

### Phase 3: Security Monitoring

- [ ] Suspicious pattern detection
- [ ] Security alerts
- [ ] Admin security dashboard
- [ ] Automated responses

### Phase 4: Compliance

- [ ] Consent management
- [ ] Data subject rights tools
- [ ] Compliance reports
- [ ] Data retention automation

---

*This document should be updated as audit and compliance requirements evolve during development.*

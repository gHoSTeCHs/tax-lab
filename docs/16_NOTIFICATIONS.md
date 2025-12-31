# TaxLab — Notifications System

## Document Information

| Item | Detail |
|------|--------|
| Document | Notifications System |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team |

---

## 1. Overview

The Notifications System keeps users informed about relevant events, actions requiring attention, and system updates. It supports multiple channels and user-configurable preferences.

### 1.1 Notification Channels

| Channel | Description | Use Case |
|---------|-------------|----------|
| In-App | Bell icon with dropdown | Real-time awareness |
| Email | Delivered to inbox | Important events, digests |
| Push (Future) | Mobile/browser push | Urgent notifications |

### 1.2 Design Principles

- **Relevant:** Only notify about meaningful events
- **Actionable:** Link directly to relevant content
- **Configurable:** Users control what they receive
- **Timely:** Real-time for urgent, batched for routine

---

## 2. Notification Types

### 2.1 Firm User Notifications

**Reports**

| Event | In-App | Email | Recipients |
|-------|--------|-------|------------|
| Report generated | ✓ | Optional | Creator |
| Report pending approval | ✓ | ✓ | Approvers |
| Report approved | ✓ | ✓ | Creator |
| Report rejected | ✓ | ✓ | Creator |
| Report published to portal | ✓ | Optional | Creator |

**Clients**

| Event | In-App | Email | Recipients |
|-------|--------|-------|------------|
| New client assigned | ✓ | ✓ | Assigned user |
| Client unassigned | ✓ | Optional | Previously assigned |
| Client archived | ✓ | Optional | Assigned users |

**Calculations**

| Event | In-App | Email | Recipients |
|-------|--------|-------|------------|
| Calculation complete | ✓ | Optional | Performer |
| New optimization found | ✓ | Optional | Assigned users |

**Team**

| Event | In-App | Email | Recipients |
|-------|--------|-------|------------|
| New team member joined | ✓ | Optional | All users |
| Role changed | ✓ | ✓ | Affected user |
| Mentioned in note | ✓ | ✓ | Mentioned user |

**Training**

| Event | In-App | Email | Recipients |
|-------|--------|-------|------------|
| Course completed | ✓ | ✓ | Completer |
| Certificate earned | ✓ | ✓ | Earner |
| New course available | ✓ | Optional | All users |

**Account**

| Event | In-App | Email | Recipients |
|-------|--------|-------|------------|
| Password changed | ✓ | ✓ | Account owner |
| 2FA enabled/disabled | ✓ | ✓ | Account owner |
| New device login | — | ✓ | Account owner |
| Session expired | ✓ | — | Account owner |

**Billing (Partners only)**

| Event | In-App | Email | Recipients |
|-------|--------|-------|------------|
| Payment successful | ✓ | ✓ | Partners |
| Payment failed | ✓ | ✓ | Partners |
| Plan limit approaching | ✓ | ✓ | Partners |
| Trial expiring | ✓ | ✓ | Partners |

### 2.2 Portal User Notifications

| Event | In-App | Email | Recipients |
|-------|--------|-------|------------|
| New report available | ✓ | ✓ | Portal users |
| Report updated | ✓ | Optional | Portal users |
| New scenario shared | ✓ | Optional | Portal users |
| Account invitation | — | ✓ | Invitee |
| Password reset | — | ✓ | Account owner |

### 2.3 Admin Notifications

| Event | In-App | Email | Recipients |
|-------|--------|-------|------------|
| New tenant signup | ✓ | ✓ | Admins |
| Trial conversion | ✓ | Optional | Admins |
| Payment failed (tenant) | ✓ | ✓ | Admins |
| Support ticket | ✓ | ✓ | Support staff |
| System alert | ✓ | ✓ | Super Admins |

---

## 3. User Interface

### 3.1 Notification Bell

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Header                                            🔔³  👤              │
└─────────────────────────────────────────────────────────────────────────┘
                                                      │
                                                      ▼
┌─────────────────────────────────────────────────────────────────────────┐
│  Notifications                                      [Mark All Read]     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ 📄 Report Pending Approval                              2 min ago  ││
│  │    Sarah generated "Tax Impact Analysis" for ABC Trading Ltd       ││
│  │    [Review Report]                                                 ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ 👤 New Client Assigned                                 15 min ago  ││
│  │    You've been assigned to XYZ Manufacturing Ltd                   ││
│  │    [View Client]                                                   ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ ✓ Report Approved                                       1 hour ago ││
│  │    Your report for John Doe has been approved                      ││
│  │    [View Report]                                                   ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  [View All Notifications]                                               │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Notifications Page

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Notifications                                                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Filter: [All ▼]  [Unread Only ☐]                  [Mark All Read]     │
│                                                                          │
│  TODAY                                                                   │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ ● 📄 Report Pending Approval                            2:30 PM    ││
│  │      Sarah generated "Tax Impact Analysis" for ABC Trading Ltd     ││
│  │      [Review Report]                                               ││
│  ├─────────────────────────────────────────────────────────────────────┤│
│  │ ● 👤 New Client Assigned                                2:15 PM    ││
│  │      You've been assigned to XYZ Manufacturing Ltd                 ││
│  │      [View Client]                                                 ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  YESTERDAY                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ ○ ✓ Report Approved                                     4:45 PM    ││
│  │      Your report for John Doe has been approved                    ││
│  │      [View Report]                                                 ││
│  ├─────────────────────────────────────────────────────────────────────┤│
│  │ ○ 🎓 Course Completed                                   10:20 AM   ││
│  │      You completed "NTA 2025 Fundamentals"                         ││
│  │      [View Certificate]                                            ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  [Load More]                                                            │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.3 Notification Preferences

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Notification Preferences                                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Configure how and when you receive notifications.                      │
│                                                                          │
│  REPORTS                                          In-App    Email       │
│  ├── Report pending approval                       [✓]       [✓]       │
│  ├── Report approved                               [✓]       [✓]       │
│  ├── Report rejected                               [✓]       [✓]       │
│  └── Report published                              [✓]       [ ]       │
│                                                                          │
│  CLIENTS                                                                 │
│  ├── New client assigned                           [✓]       [✓]       │
│  ├── Client unassigned                             [✓]       [ ]       │
│  └── Client archived                               [✓]       [ ]       │
│                                                                          │
│  CALCULATIONS                                                            │
│  ├── Calculation complete                          [✓]       [ ]       │
│  └── New optimization found                        [✓]       [ ]       │
│                                                                          │
│  TEAM                                                                    │
│  ├── New team member                               [✓]       [ ]       │
│  ├── Role changed                                  [✓]       [✓]       │
│  └── Mentioned in note                             [✓]       [✓]       │
│                                                                          │
│  TRAINING                                                                │
│  ├── Course completed                              [✓]       [✓]       │
│  ├── Certificate earned                            [✓]       [✓]       │
│  └── New course available                          [✓]       [ ]       │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  EMAIL DIGEST                                                            │
│  ○ Send immediately                                                     │
│  ● Daily digest (9:00 AM)                                               │
│  ○ Weekly digest (Monday 9:00 AM)                                       │
│                                                                          │
│                                                     [Cancel] [Save]      │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Email Notifications

### 4.1 Email Templates

**Standard Notification Email:**
```
From: TaxLab <notifications@taxlab.ng>
To: user@example.com
Subject: Report Pending Your Approval

───────────────────────────────────────────────────────────

[FIRM LOGO]

Hi John,

Sarah Jones has generated a report that requires your approval:

    Report: Tax Impact Analysis
    Client: ABC Trading Limited
    Generated: December 15, 2024 at 2:30 PM

[Review Report →]

───────────────────────────────────────────────────────────

You're receiving this because you have approval permissions.
Manage your notification preferences: [Settings]

© 2024 Acme Tax Consultants
```

**Daily Digest Email:**
```
From: TaxLab <digest@taxlab.ng>
To: user@example.com
Subject: Your TaxLab Daily Summary - Dec 15

───────────────────────────────────────────────────────────

[FIRM LOGO]

Good morning, John!

Here's what happened yesterday:

REPORTS
• 2 reports generated
• 1 report pending your approval

CLIENTS
• 1 new client assigned to you

TRAINING
• 45 minutes of learning completed

[View Dashboard →]

───────────────────────────────────────────────────────────

Manage your notification preferences: [Settings]
```

### 4.2 Email Best Practices

- Clear subject lines
- Mobile-responsive design
- Single primary CTA
- Unsubscribe link
- Firm branding (white-label)
- Plain text fallback

---

## 5. Real-Time Delivery

### 5.1 Implementation Options

**Option 1: Polling (MVP)**
- Client polls every 30-60 seconds
- Simple implementation
- Higher server load

**Option 2: WebSockets (Recommended)**
- Real-time delivery
- Lower server load
- More complex setup
- Use Laravel Echo + Pusher or Soketi

### 5.2 Notification Flow

```
Event Occurs (e.g., Report Generated)
              │
              ▼
┌────────────────────────┐
│ NotificationService    │
│ ->notify($users,       │
│          $event)       │
└───────────┬────────────┘
            │
    ┌───────┴───────┐
    ▼               ▼
┌────────┐    ┌────────────┐
│ Store  │    │ Broadcast  │
│ in DB  │    │ (real-time)│
└────────┘    └─────┬──────┘
                    │
            ┌───────┴───────┐
            ▼               ▼
      ┌──────────┐    ┌──────────┐
      │ In-App   │    │ Email    │
      │ (instant)│    │ (queued) │
      └──────────┘    └──────────┘
```

---

## 6. Data Model

### 6.1 Notifications Table

**Table: notifications**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| type | varchar(100) | Notification type |
| notifiable_type | varchar(100) | User model type |
| notifiable_id | ULID | User ID |
| data | jsonb | Notification data |
| read_at | timestamp | When read |
| created_at | timestamp | When created |

### 6.2 Notification Data Structure

```json
{
  "title": "Report Pending Approval",
  "body": "Sarah generated \"Tax Impact Analysis\" for ABC Trading Ltd",
  "icon": "document",
  "action_url": "/app/clients/123/reports/456",
  "action_text": "Review Report",
  "metadata": {
    "report_id": "456",
    "client_id": "123",
    "generator_id": "789",
    "generator_name": "Sarah Jones"
  }
}
```

### 6.3 Notification Preferences Table

**Table: notification_preferences**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| user_id | ULID | User |
| user_type | varchar(100) | User model type |
| channel | enum | in_app, email |
| notification_type | varchar(100) | Notification type |
| enabled | boolean | Is enabled |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

---

## 7. API Endpoints

### 7.1 Notification Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/notifications | List notifications |
| GET | /api/notifications/unread-count | Get unread count |
| POST | /api/notifications/{id}/read | Mark as read |
| POST | /api/notifications/read-all | Mark all as read |
| DELETE | /api/notifications/{id} | Delete notification |

### 7.2 Preferences Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/notifications/preferences | Get preferences |
| PUT | /api/notifications/preferences | Update preferences |

---

## 8. Implementation Checklist

### Phase 1: Core Notifications

- [ ] Notification model and storage
- [ ] Basic notification service
- [ ] In-app notification UI (bell, dropdown)
- [ ] Mark as read functionality
- [ ] Notifications page

### Phase 2: Email Notifications

- [ ] Email notification templates
- [ ] Queue-based email delivery
- [ ] Notification preferences
- [ ] Email digest system

### Phase 3: Real-Time

- [ ] WebSocket integration
- [ ] Real-time notification delivery
- [ ] Unread count updates
- [ ] Sound/visual alerts

### Phase 4: Advanced

- [ ] Push notifications (mobile)
- [ ] Notification grouping
- [ ] Smart notification timing
- [ ] Analytics on notification engagement

---

*This document should be updated as notification requirements evolve during development.*

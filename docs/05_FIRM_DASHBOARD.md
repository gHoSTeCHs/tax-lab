# TaxLab — Firm Dashboard

## Document Information

| Item | Detail |
|------|--------|
| Document | Firm Dashboard Module |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team |

---

## 1. Overview

The Firm Dashboard is the landing page for practitioners after login. It provides an overview of their practice, highlights items requiring attention, and enables quick access to common actions.

### 1.1 Access

- **URL:** `/app/dashboard`
- **Guard:** firm
- **Roles:** All firm roles (content varies by role)
- **Default:** First page after login

### 1.2 Design Principles

- **Actionable:** Every element drives toward an action
- **Personalized:** Content relevant to user's role and assignments
- **Scannable:** Key information visible at a glance
- **Responsive:** Works on desktop, tablet, and mobile

---

## 2. Dashboard Layout

### 2.1 Structure

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           HEADER                                         │
│  Logo (firm branding)    Search    Notifications    Profile Menu        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │                     WELCOME SECTION                                 │ │
│  │  "Good morning, [Name]"     [Quick Actions: New Client, Run Calc]  │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  ┌──────────────────────┐  ┌──────────────────────┐  ┌────────────────┐ │
│  │   KEY METRICS        │  │   KEY METRICS        │  │   KEY METRICS  │ │
│  │   Active Clients     │  │   This Month         │  │   Pending      │ │
│  │        147           │  │   Reports: 34        │  │   Approvals: 5 │ │
│  └──────────────────────┘  └──────────────────────┘  └────────────────┘ │
│                                                                          │
│  ┌────────────────────────────────────┐  ┌──────────────────────────────┐│
│  │       ATTENTION REQUIRED           │  │     RECENT ACTIVITY          ││
│  │                                    │  │                              ││
│  │  • 3 clients need 2024 analysis   │  │  • Report generated for...   ││
│  │  • 2 reports pending approval     │  │  • Calculation run for...    ││
│  │  • Trial expires in 5 days        │  │  • Client created: ...       ││
│  │                                    │  │                              ││
│  └────────────────────────────────────┘  └──────────────────────────────┘│
│                                                                          │
│  ┌────────────────────────────────────┐  ┌──────────────────────────────┐│
│  │       RECENT CLIENTS               │  │     QUICK LINKS              ││
│  │                                    │  │                              ││
│  │  ABC Ltd          Last: 2 days    │  │  📊 Run Calculation          ││
│  │  XYZ Trading      Last: 3 days    │  │  📄 Generate Report          ││
│  │  John Doe         Last: 1 week    │  │  📚 Knowledge Base           ││
│  │                                    │  │  🎓 Continue Training        ││
│  └────────────────────────────────────┘  └──────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Responsive Behavior

**Desktop (1200px+):**
- Full layout as shown above
- Multi-column grid
- All widgets visible

**Tablet (768px - 1199px):**
- Two-column layout
- Stacked widgets
- Collapsible sections

**Mobile (< 768px):**
- Single column
- Key metrics as horizontal scroll
- Collapsed attention items (expandable)
- Bottom navigation bar

---

## 3. Dashboard Components

### 3.1 Welcome Section

**Content:**
- Personalized greeting with time of day
- User's first name
- Current date

**Quick Actions (Buttons):**
- "New Client" (if user can create clients)
- "Run Calculation" (links to client selector)

### 3.2 Key Metrics Cards

Metrics displayed vary by role:

**Partner View:**

| Metric | Description |
|--------|-------------|
| Active Clients | Total active clients in firm |
| Reports This Month | Reports generated current month |
| Pending Approvals | Reports awaiting approval |
| Team Activity | Active users in last 7 days |

**Manager View:**

| Metric | Description |
|--------|-------------|
| Active Clients | All firm clients |
| My Team's Reports | Reports by team members |
| Pending Approvals | Reports awaiting my approval |
| Calculations This Week | Team calculations |

**Associate View:**

| Metric | Description |
|--------|-------------|
| My Clients | Assigned active clients |
| My Reports This Month | Reports I generated |
| Pending Review | My reports awaiting approval |
| Calculations This Week | My calculations |

**Viewer View:**

| Metric | Description |
|--------|-------------|
| Assigned Clients | Clients I can view |
| Recent Reports | Reports available to view |
| — | — |
| — | — |

### 3.3 Attention Required Section

Prioritized list of items needing action:

**High Priority (Red)**
- Subscription payment failed
- Trial expiring within 3 days
- Reports rejected (need revision)

**Medium Priority (Yellow)**
- Clients without current year analysis
- Reports pending approval for 3+ days
- Approaching plan limits

**Low Priority (Blue)**
- Optimization opportunities identified
- New CPD courses available
- Knowledge base updates

**Display Rules:**
- Maximum 5 items shown
- "View all" link if more exist
- Items dismissible (per user)
- Smart prioritization algorithm

### 3.4 Recent Activity Feed

Chronological list of relevant activity:

**For Partners/Managers:**
- All firm activity
- User logins
- Client changes
- Reports generated
- Calculations performed

**For Associates:**
- Activity on assigned clients
- Own actions
- Approvals on own reports

**For Viewers:**
- Reports available on assigned clients
- Changes to assigned clients

**Feed Item Format:**
```
[User Avatar] [User Name] [Action] [Object]
[Relative Time]

Example:
👤 Sarah Jones generated report for ABC Trading Ltd
2 hours ago
```

**Display Rules:**
- Last 10 items shown
- "View all activity" link
- Expandable details on click

### 3.5 Recent Clients Widget

Quick access to recently worked clients:

**Display:**
- Client name
- Entity type icon (company/individual)
- Last activity date
- Quick action icons (view, calculate)

**Logic:**
- Partners/Managers: Firm-wide recent clients
- Associates/Viewers: Recent assigned clients

**Display Rules:**
- 5 most recently accessed
- Click to go to client profile

### 3.6 Quick Links Widget

Contextual shortcuts:

**Always Shown:**
- Knowledge Base
- Help & Support

**Conditional:**
- Continue Training (if course in progress)
- Pending Approvals (if any exist, manager+ only)
- Billing (partner only)
- Firm Settings (partner only)

### 3.7 Training Progress Widget (Optional)

If user has active CPD enrollment:

**Display:**
- Current course name
- Progress bar
- "Continue" button

---

## 4. Role-Based Customization

### 4.1 Partner Dashboard Additions

**Firm Health Widget:**
- Subscription status
- Usage vs. limits
- Days until renewal
- Payment status

**Team Overview Widget:**
- Active users this week
- Top performers (reports generated)
- Users not logged in recently

### 4.2 Manager Dashboard Additions

**Team Workload Widget:**
- Associates' client assignments
- Workload distribution
- Reports pending review

### 4.3 Associate Dashboard

**My Performance Widget:**
- Calculations this month
- Reports generated
- Clients analyzed

### 4.4 Viewer Dashboard

Simplified view:
- Assigned clients list
- Recent reports available
- Minimal metrics

---

## 5. Interactive Features

### 5.1 Global Search

**Trigger:** Search icon in header or `/` keyboard shortcut

**Searchable Items:**
- Clients (name, TIN, CAC number)
- Reports (by client name, date)
- Knowledge base articles
- Help documentation

**Results:**
- Grouped by category
- Keyboard navigable
- Recent searches remembered

### 5.2 Notifications Bell

**Display:**
- Unread count badge
- Dropdown with recent notifications
- Mark as read
- Link to full notifications page

**Notification Types:**
- Report approved/rejected
- New client assigned
- Mention in notes
- System announcements
- Training milestones

### 5.3 Profile Menu

**Dropdown Contents:**
- User name and email
- Role badge
- View Profile
- Notification Settings
- Security Settings
- Sign Out

---

## 6. Empty States

### 6.1 New Firm (No Clients)

**Welcome onboarding flow:**
1. Welcome message
2. Add your first client CTA
3. Explore training courses
4. Set up branding

### 6.2 New User (No Activity)

**Getting started guidance:**
1. View assigned clients (or create first)
2. Run first calculation
3. Generate first report
4. Complete onboarding checklist

### 6.3 No Pending Items

**Positive messaging:**
- "All caught up!"
- Suggestions for proactive actions

---

## 7. Performance Considerations

### 7.1 Data Loading Strategy

**Initial Load:**
- Core layout and navigation
- Key metrics (cached, fast)
- Skeleton loaders for widgets

**Deferred Load:**
- Activity feed (after metrics)
- Recent clients (after metrics)
- Training progress (lowest priority)

### 7.2 Caching

| Data | Cache Duration | Invalidation |
|------|----------------|--------------|
| Key metrics | 5 minutes | On relevant action |
| Recent activity | 2 minutes | On new activity |
| Recent clients | 5 minutes | On client access |
| Attention items | 5 minutes | On relevant action |

### 7.3 Real-Time Updates

**Candidates for WebSocket updates:**
- Notification badge count
- Pending approval count
- Activity feed (optional)

**Implementation:** Start with polling, add WebSockets in Phase 3

---

## 8. Data Requirements

### 8.1 Key Metrics Queries

**Active Clients Count:**
```
Count of tax_clients where:
- firm_id = current firm
- status = 'active'
- (for associate/viewer: client is assigned)
```

**Reports This Month:**
```
Count of reports where:
- firm_id = current firm
- created_at >= start of month
- status = 'completed'
- (for associate: created_by = current user)
```

**Pending Approvals:**
```
Count of reports where:
- firm_id = current firm
- approval_status = 'pending'
- (for manager: created by team members)
```

### 8.2 Attention Items Logic

**Clients Needing Analysis:**
```
Clients where:
- No calculation for current fiscal year
- OR last calculation > 30 days old
- Status = 'active'
```

**Reports Pending Long:**
```
Reports where:
- approval_status = 'pending'
- created_at > 3 days ago
```

### 8.3 Recent Activity Query

```
Activity logs where:
- firm_id = current firm
- created_at > 7 days ago
- action_type in relevant_actions
- (filtered by role visibility)
Order by created_at DESC
Limit 10
```

---

## 9. Navigation Integration

### 9.1 Sidebar Navigation

```
Dashboard (current)
───────────────────
Clients
  └── All Clients
  └── Create Client
Calculations
Reports
  └── All Reports
  └── Pending Approval
Scenarios
───────────────────
Knowledge Base
Training
───────────────────
Settings (Partner only)
  └── Firm Settings
  └── Users
  └── Billing
```

### 9.2 Breadcrumbs

Dashboard doesn't show breadcrumbs (it's the root).

---

## 10. Accessibility

### 10.1 Requirements

- All interactive elements keyboard accessible
- ARIA labels on icons
- Color not sole indicator (use icons/text)
- Focus indicators visible
- Screen reader friendly structure

### 10.2 Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `/` | Open search |
| `g d` | Go to dashboard |
| `g c` | Go to clients |
| `g r` | Go to reports |
| `n` | Notifications panel |

---

## 11. Implementation Checklist

### Phase 1: Core Dashboard

- [ ] Dashboard layout and routing
- [ ] Welcome section with greeting
- [ ] Key metrics cards (static data)
- [ ] Recent clients widget
- [ ] Quick links widget
- [ ] Mobile responsive layout

### Phase 2: Dynamic Content

- [ ] Attention required logic
- [ ] Activity feed implementation
- [ ] Role-based content filtering
- [ ] Notification integration
- [ ] Global search

### Phase 3: Enhancement

- [ ] Performance optimization (caching)
- [ ] Empty states and onboarding
- [ ] Keyboard shortcuts
- [ ] Widget customization
- [ ] Real-time updates

---

*This document should be updated as dashboard requirements evolve during development.*

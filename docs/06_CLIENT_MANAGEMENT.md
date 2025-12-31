# TaxLab — Client Management

## Document Information

| Item | Detail |
|------|--------|
| Document | Client Management Module |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team |

---

## 1. Overview

The Client Management module is the foundation of TaxLab. Tax clients are the entities (companies or individuals) that firms provide tax services to. This module handles the complete lifecycle of managing these clients.

### 1.1 Access

- **URL:** `/app/clients/*`
- **Guard:** firm
- **Roles:** All roles (with varying permissions)

### 1.2 Key Concepts

**Tax Client:** A business entity or individual receiving tax services from the firm. Not to be confused with "client portal users" who are people at those entities.

**Entity Types:**
- Company (Limited Liability)
- Company (Public Limited)
- Partnership
- Sole Proprietorship
- Individual (High Net Worth)
- Trust/Estate

---

## 2. Client List View

### 2.1 Layout

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Clients                                        [+ New Client] [Import] │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ 🔍 Search clients...          [Filters ▼]    [Sort: Name ▼]        ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ Status: [All] [Active: 147] [Archived: 12]                         ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ □  Client Name           Type        Industry     Last Activity     ││
│  ├─────────────────────────────────────────────────────────────────────┤│
│  │ □  ABC Trading Ltd       Company     Retail       2 days ago    ⋮  ││
│  │ □  John Doe              Individual  —            1 week ago    ⋮  ││
│  │ □  XYZ Manufacturing     Company     Manufact.    3 days ago    ⋮  ││
│  │ □  ...                                                              ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  Showing 1-20 of 147 clients                    [< 1 2 3 4 5 ... 8 >]   │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.2 List Features

**Search:**
- Search by client name
- Search by TIN/NIN
- Search by CAC registration number
- Instant results as you type

**Filters:**

| Filter | Options |
|--------|---------|
| Entity Type | Company, Partnership, Sole Prop, Individual, Trust |
| Industry | All industries (multi-select) |
| Status | Active, Archived |
| Assigned To | User selector (Manager+ only) |
| Has Analysis | With/without current year analysis |
| Created Date | Date range picker |

**Sorting:**
- Name (A-Z, Z-A)
- Last activity (newest, oldest)
- Created date (newest, oldest)
- Entity type

**Columns Displayed:**
- Checkbox (for bulk actions)
- Client name (with entity type icon)
- Entity type
- Industry
- Last activity (relative time)
- Actions menu

### 2.3 Bulk Actions

Available when items selected:
- Export selected (CSV)
- Assign to user (Manager+ only)
- Archive selected
- Delete selected (with confirmation)

### 2.4 Row Actions Menu

| Action | Availability |
|--------|--------------|
| View Profile | All roles |
| Edit | Partner, Manager, Associate (own) |
| Run Calculation | Partner, Manager, Associate |
| Generate Report | Partner, Manager, Associate |
| Assign Users | Partner, Manager |
| Archive | Partner, Manager |
| Delete | Partner, Manager |

---

## 3. Client Creation

### 3.1 Creation Flow

```
Select Entity Type
       │
       ▼
┌─────────────────┐
│ Basic Info      │ ──▶ Name, Registration, TIN
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Contact Details │ ──▶ Address, Phone, Email
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Industry &      │ ──▶ Sector, Size, Fiscal Year
│ Classification  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ User Assignment │ ──▶ Assign team members
│ (Optional)      │
└────────┬────────┘
         │
         ▼
    Client Created
```

### 3.2 Form Fields by Entity Type

**All Entity Types:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| Entity Type | select | Yes | From entity types list |
| Client Name | text | Yes | Max 255 chars |
| Trading Name | text | No | Max 255 chars |
| Industry | select | Yes | From industries list |
| Sub-Industry | select | No | Based on industry |
| Email | email | No | Valid email format |
| Phone | text | No | Valid phone format |
| Address | textarea | No | Max 500 chars |
| State | select | No | Nigerian states |
| LGA | select | No | Based on state |
| Notes | textarea | No | Max 2000 chars |

**Company-Specific Fields:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| CAC Registration Number | text | Yes | Valid CAC format |
| Tax Identification Number (TIN) | text | Yes | Valid TIN format |
| Date of Incorporation | date | Yes | Not future date |
| Fiscal Year End | select | Yes | Month selector |
| Company Size | select | Yes | Micro, Small, Medium, Large |

**Individual-Specific Fields:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| National ID Number (NIN) | text | Yes | 11 digits |
| Date of Birth | date | Yes | Age 18+ |
| Marital Status | select | No | Single, Married, etc. |
| Employment Status | select | Yes | Employed, Self-employed, etc. |
| Employer Name | text | Conditional | If employed |

**Partnership-Specific Fields:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| Registration Number | text | Yes | — |
| TIN | text | Yes | Valid format |
| Number of Partners | number | Yes | Minimum 2 |
| Partnership Type | select | Yes | General, Limited, LLP |

### 3.3 Industry Classification

**Sectors:**
- Agriculture, Forestry & Fishing
- Mining & Quarrying
- Manufacturing
- Construction
- Wholesale & Retail Trade
- Transportation & Storage
- Accommodation & Food Services
- Information & Communication
- Financial & Insurance Services
- Real Estate
- Professional, Scientific & Technical
- Education
- Healthcare
- Arts, Entertainment & Recreation
- Other Services

Each sector has sub-industries for more precise classification.

### 3.4 Validation Rules

**TIN Validation:**
- Format check (numeric, correct length)
- Luhn algorithm check (if applicable)
- Uniqueness within firm (warning if duplicate)

**CAC Validation:**
- Format: RC followed by digits, or BN followed by digits
- Length validation

**NIN Validation:**
- Exactly 11 digits
- No special characters

### 3.5 Duplicate Detection

Before saving, system checks for potential duplicates:
- Same TIN/NIN
- Similar name (fuzzy match)
- Same CAC number

If potential duplicate found:
- Show warning with existing client details
- Allow user to proceed anyway
- Or link to existing client

---

## 4. Client Profile View

### 4.1 Profile Layout

```
┌─────────────────────────────────────────────────────────────────────────┐
│  ← Back to Clients                                                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │  🏢 ABC Trading Limited                               [Edit] [⋮] │   │
│  │                                                                   │   │
│  │  Company • Retail Trade • Active                                  │   │
│  │  TIN: 12345678-0001    CAC: RC 123456                            │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ [Overview] [Financials] [Calculations] [Reports] [Scenarios] [Portal]│
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  TAB CONTENT AREA                                                        │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Overview Tab

**Quick Stats:**
- Last calculation date
- Last report generated
- Assigned team members
- Portal access status

**Client Details Card:**
- All registration details
- Contact information
- Industry classification
- Created date and by whom

**Tax Position Summary:**
- Current year calculation summary (if exists)
- Key figures: Total tax liability, change from old regime
- Link to full calculation

**Notes Section:**
- Internal notes (not visible to client)
- Add new note
- Note history with timestamps

### 4.3 Financials Tab

See Document 07: Financial Data Entry

### 4.4 Calculations Tab

**Calculation History Table:**

| Column | Description |
|--------|-------------|
| Date | When calculation performed |
| Fiscal Year | Year calculated |
| Type | Full analysis, PIT only, CIT only, etc. |
| Performed By | User who ran calculation |
| Actions | View, Compare, Regenerate |

**Features:**
- Filter by fiscal year
- Compare two calculations side-by-side
- Export calculation details

### 4.5 Reports Tab

**Reports Table:**

| Column | Description |
|--------|-------------|
| Date | When generated |
| Report Type | Impact Analysis, Advisory Letter, etc. |
| Fiscal Year | Year covered |
| Status | Draft, Pending Approval, Approved, Published |
| Generated By | User who created |
| Actions | View, Download, Publish, Delete |

**Features:**
- Filter by type, status, year
- Quick regenerate
- Approval workflow actions

### 4.6 Scenarios Tab

See Document 09: Scenario Modeling

### 4.7 Portal Tab

**Portal Status:**
- Enabled/Disabled toggle
- Portal URL (if enabled)
- Last client login

**Portal Users Table:**
- Name
- Email
- Role (Primary, Viewer)
- Status
- Last login
- Actions (Edit, Revoke)

**Actions:**
- Enable/disable portal
- Invite portal user
- Manage existing users
- View portal activity

---

## 5. Client Editing

### 5.1 Editable Fields

All fields from creation are editable except:
- Entity Type (requires new client)
- TIN/NIN (requires admin assistance)
- CAC Number (requires verification)

### 5.2 Change Tracking

Changes are logged:
- What field changed
- Old value
- New value
- Who changed
- When changed

### 5.3 Impact Warnings

If changes affect calculations:
- Warning shown before save
- Affected calculations listed
- Option to recalculate

---

## 6. Client Assignment

### 6.1 Assignment Model

```
┌─────────────────┐         ┌─────────────────┐
│   Tax Client    │◄───────▶│   Firm User     │
└─────────────────┘   M:N   └─────────────────┘
                      │
                      ▼
            ┌─────────────────┐
            │   Assignment    │
            │  - assigned_at  │
            │  - assigned_by  │
            │  - notes        │
            └─────────────────┘
```

### 6.2 Assignment Rules

**Partners:** Implicit access to all clients (no assignment needed)

**Managers:** Implicit access to all clients (no assignment needed)

**Associates:** Must be explicitly assigned to access client

**Viewers:** Must be explicitly assigned to access client

### 6.3 Assignment Interface

**From Client Profile:**
- "Manage Assignments" button
- Shows current assignments
- Search/select users to add
- Remove existing assignments

**From User Profile:**
- "Manage Clients" button
- Shows currently assigned clients
- Search/select clients to add
- Remove assignments

**Bulk Assignment:**
- From client list: Select multiple → Assign to user
- From user management: Assign multiple clients at once

### 6.4 Assignment Notifications

When assigned:
- User receives notification
- Email sent (configurable)
- Client appears in their list

When unassigned:
- User receives notification
- Client removed from their view

---

## 7. Client Import

### 7.1 Import Process

```
Upload File
     │
     ▼
┌─────────────────┐
│ File Validation │ ──▶ Check format, size
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Column Mapping  │ ──▶ Map file columns to fields
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Data Preview    │ ──▶ Show first 10 rows
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Validation      │ ──▶ Check all rows for errors
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Import Confirm  │ ──▶ Show summary, confirm
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Background Job  │ ──▶ Process import
└────────┬────────┘
         │
         ▼
   Import Complete
   (notification)
```

### 7.2 Supported Formats

- CSV (comma-separated)
- XLSX (Excel)
- XLS (Legacy Excel)

### 7.3 Template Download

Provide downloadable template with:
- All supported columns
- Sample data rows
- Instructions sheet
- Validation rules noted

### 7.4 Column Mapping

**Required Mappings:**
- Client Name
- Entity Type
- TIN or NIN

**Optional Mappings:**
- All other fields

**Smart Detection:**
- Auto-detect column names
- Suggest mappings based on headers
- Remember previous mappings

### 7.5 Validation & Error Handling

**Row-Level Validation:**
- Required fields present
- Format validation
- Duplicate detection

**Error Report:**
- Row number
- Column
- Error description
- Original value

**Options:**
- Skip invalid rows
- Fix and retry
- Cancel import

### 7.6 Import Limits

Based on plan limits:
- Cannot import more than remaining client slots
- Warning if approaching limit
- Block if would exceed limit

---

## 8. Client Archival & Deletion

### 8.1 Archive vs Delete

**Archive:**
- Client hidden from default views
- All data retained
- Can be restored
- Doesn't free up plan slot

**Delete:**
- Permanent removal
- All associated data deleted
- Cannot be restored
- Frees up plan slot

### 8.2 Archive Process

1. User selects "Archive"
2. Confirmation modal shown
3. Reason required
4. Client status → Archived
5. Notification to assigned users

**Restore:**
1. View archived clients
2. Select "Restore"
3. Client status → Active

### 8.3 Delete Process

1. User selects "Delete"
2. Warning about permanent deletion
3. List of data to be deleted shown
4. Type client name to confirm
5. Soft delete (30-day recovery window)
6. Permanent deletion after 30 days

**Cascade Deletion:**
- Client financials
- Calculations
- Scenarios
- Reports
- Optimizations
- Portal users
- Activity logs (retained but anonymized)

---

## 9. Data Model

### 9.1 Tax Clients Table

**Table: tax_clients**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| firm_id | ULID | Tenant foreign key |
| entity_type | enum | company_llc, company_plc, partnership, sole_prop, individual, trust |
| name | varchar(255) | Legal name |
| trading_name | varchar(255) | Trading/brand name |
| tin | varchar(20) | Tax ID (encrypted) |
| nin | varchar(11) | National ID (encrypted) |
| cac_number | varchar(20) | CAC registration |
| incorporation_date | date | Date incorporated |
| fiscal_year_end | tinyint | Month (1-12) |
| industry_id | ULID | Foreign key to industries |
| company_size | enum | micro, small, medium, large |
| email | varchar(255) | Contact email |
| phone | varchar(50) | Contact phone |
| address | text | Physical address |
| state | varchar(50) | State |
| lga | varchar(100) | Local government area |
| status | enum | active, archived |
| portal_enabled | boolean | Client portal access |
| created_by | ULID | User who created |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |
| deleted_at | timestamp | Soft delete |

### 9.2 Supporting Tables

**Table: industries**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| sector | varchar(100) | Major sector |
| name | varchar(255) | Industry name |
| code | varchar(10) | ISIC code |
| is_active | boolean | Available for selection |

**Table: client_user_assignments**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| tax_client_id | ULID | Client being assigned |
| firm_user_id | ULID | User receiving assignment |
| assigned_by | ULID | User who made assignment |
| assigned_at | timestamp | When assigned |
| notes | text | Assignment notes |

**Table: client_notes**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| tax_client_id | ULID | Parent client |
| firm_user_id | ULID | User who wrote note |
| content | text | Note content |
| created_at | timestamp | When written |

---

## 10. API Endpoints

### 10.1 Client Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/clients | List clients (filtered, paginated) |
| POST | /api/clients | Create client |
| GET | /api/clients/{id} | Get client details |
| PUT | /api/clients/{id} | Update client |
| DELETE | /api/clients/{id} | Delete client |
| POST | /api/clients/{id}/archive | Archive client |
| POST | /api/clients/{id}/restore | Restore archived client |

### 10.2 Assignment Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/clients/{id}/assignments | List assigned users |
| POST | /api/clients/{id}/assignments | Add assignment |
| DELETE | /api/clients/{id}/assignments/{userId} | Remove assignment |

### 10.3 Import Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/clients/import/upload | Upload import file |
| POST | /api/clients/import/validate | Validate import data |
| POST | /api/clients/import/execute | Execute import |
| GET | /api/clients/import/{id}/status | Check import status |

---

## 11. Implementation Checklist

### Phase 1: Core CRUD

- [ ] Client model and migration
- [ ] Client list view with search and filters
- [ ] Client creation form with validation
- [ ] Client profile view (overview tab)
- [ ] Client editing
- [ ] Archive and delete functionality

### Phase 2: Enhanced Features

- [ ] Client assignment system
- [ ] Assignment notifications
- [ ] Notes functionality
- [ ] Bulk operations
- [ ] Advanced filtering

### Phase 3: Import/Export

- [ ] CSV/Excel import
- [ ] Template download
- [ ] Validation and error handling
- [ ] Export functionality
- [ ] Import history

---

*This document should be updated as client management requirements evolve during development.*

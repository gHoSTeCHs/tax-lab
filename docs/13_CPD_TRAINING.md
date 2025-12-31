# TaxLab — CPD Training Module

## Document Information

| Item | Detail |
|------|--------|
| Document | CPD Training Module |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team |

---

## 1. Overview

The CPD (Continuing Professional Development) Training Module provides accredited courses to help practitioners understand and apply NTA 2025. It's a core feature that adds value beyond calculation tools and supports professional development requirements.

### 1.1 Access

- **URL:** `/app/training`
- **Guard:** firm
- **Roles:** All firm roles

### 1.2 Value Proposition

**For Practitioners:**
- Stay current on tax legislation
- Earn CPD hours required for professional memberships
- Learn at their own pace
- Apply knowledge directly in the platform

**For Firms:**
- Ensure team competency
- Track staff development
- Reduce training costs
- Standardize knowledge

---

## 2. Course Structure

### 2.1 Hierarchy

```
Course
├── Overview
│   ├── Description
│   ├── Learning Objectives
│   ├── Prerequisites
│   └── Duration & CPD Hours
│
├── Modules (ordered)
│   ├── Module 1
│   │   ├── Lessons (ordered)
│   │   │   ├── Lesson 1.1
│   │   │   ├── Lesson 1.2
│   │   │   └── Lesson 1.3
│   │   └── Module Quiz
│   │
│   ├── Module 2
│   │   ├── Lessons
│   │   └── Module Quiz
│   │
│   └── Module N...
│
├── Final Assessment
│   ├── Comprehensive Exam
│   └── Passing Requirements
│
└── Certificate
    ├── Template
    └── Verification
```

### 2.2 Course Types

| Type | Duration | CPD Hours | Description |
|------|----------|-----------|-------------|
| Quick Guide | 30-60 min | 0.5-1 | Single-topic overview |
| Standard Course | 2-4 hours | 2-4 | Comprehensive topic coverage |
| Deep Dive | 6-10 hours | 6-10 | Expert-level detail |
| Certification | 15-20 hours | 15-20 | Professional certification |

### 2.3 Initial Course Catalog

**Core Courses:**

| Course | Duration | CPD Hours |
|--------|----------|-----------|
| NTA 2025: Complete Overview | 4 hours | 4 |
| Company Income Tax Under NTA 2025 | 3 hours | 3 |
| Personal Income Tax Changes | 2 hours | 2 |
| VAT Reforms and Input Recovery | 2 hours | 2 |
| Capital Gains Tax Updates | 1.5 hours | 1.5 |
| Development Levy Consolidation | 1 hour | 1 |
| Tax Planning Under NTA 2025 | 3 hours | 3 |
| Compliance and Administration | 2 hours | 2 |

**Platform Courses:**

| Course | Duration | CPD Hours |
|--------|----------|-----------|
| Getting Started with TaxLab | 30 min | — |
| Running Tax Calculations | 45 min | — |
| Scenario Modeling Mastery | 1 hour | — |
| Report Generation Best Practices | 45 min | — |

---

## 3. User Interface

### 3.1 Training Dashboard

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Training                                                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ YOUR PROGRESS                                                       │ │
│  │                                                                     │ │
│  │ Courses Completed: 3/8    CPD Hours Earned: 9.5    Certificates: 2 │ │
│  │                                                                     │ │
│  │ ████████████████████░░░░░░░░░░░░░░░░ 38%                           │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  CONTINUE LEARNING                                                       │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ 📚 VAT Reforms and Input Recovery                                   │ │
│  │    Module 2: Input Recovery Rules                                   │ │
│  │    Progress: 65% complete                                           │ │
│  │    [Continue →]                                                     │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  ALL COURSES                                     Filter: [All Topics ▼] │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │ ┌──────────────┐                                                  │   │
│  │ │   [Image]    │  NTA 2025: Complete Overview                     │   │
│  │ │              │  4 hours | 4 CPD hours                           │   │
│  │ │              │  ★★★★★ (45 reviews)                              │   │
│  │ └──────────────┘  Comprehensive introduction to all NTA changes   │   │
│  │                   [Start Course]                    ✓ Completed   │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │ ┌──────────────┐                                                  │   │
│  │ │   [Image]    │  Company Income Tax Under NTA 2025               │   │
│  │ │              │  3 hours | 3 CPD hours                           │   │
│  │ │              │  ★★★★☆ (32 reviews)                              │   │
│  │ └──────────────┘  Deep dive into CIT rate changes and thresholds  │   │
│  │                   [Continue] 45% complete                         │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                                                                          │
│  [More courses...]                                                       │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Course Overview Page

```
┌─────────────────────────────────────────────────────────────────────────┐
│  ← Back to Training                                                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │                                                                     │ │
│  │  [Course Banner Image]                                              │ │
│  │                                                                     │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  VAT Reforms and Input Recovery                                          │
│  ★★★★★ 4.8 (67 reviews)                                                 │
│                                                                          │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐                        │
│  │ 2 hours │ │ 2 CPD   │ │ 4       │ │ Intermed│                        │
│  │Duration │ │ Hours   │ │ Modules │ │ Level   │                        │
│  └─────────┘ └─────────┘ └─────────┘ └─────────┘                        │
│                                                                          │
│  DESCRIPTION                                                             │
│                                                                          │
│  Learn how NTA 2025 transforms VAT input recovery for Nigerian          │
│  businesses. This course covers the expanded recovery rules for         │
│  services and capital assets, practical implementation steps, and       │
│  documentation requirements.                                            │
│                                                                          │
│  WHAT YOU'LL LEARN                                                       │
│                                                                          │
│  ✓ Understand the new VAT input recovery framework                      │
│  ✓ Apply recovery rules to services and capital assets                  │
│  ✓ Implement proper documentation procedures                            │
│  ✓ Calculate VAT savings for your clients                               │
│  ✓ Avoid common compliance pitfalls                                     │
│                                                                          │
│  COURSE CONTENT                                                          │
│                                                                          │
│  Module 1: VAT Fundamentals Refresher (25 min)                          │
│    ├── 1.1 VAT Basics Review                                            │
│    ├── 1.2 Old Regime Limitations                                       │
│    └── Quiz                                                             │
│                                                                          │
│  Module 2: New Input Recovery Rules (35 min)                            │
│    ├── 2.1 Services Input Recovery                                      │
│    ├── 2.2 Capital Assets Recovery                                      │
│    ├── 2.3 Documentation Requirements                                   │
│    └── Quiz                                                             │
│                                                                          │
│  Module 3: Practical Application (40 min)                               │
│    ├── 3.1 Calculation Examples                                         │
│    ├── 3.2 Client Implementation                                        │
│    ├── 3.3 Case Studies                                                 │
│    └── Quiz                                                             │
│                                                                          │
│  Module 4: Compliance & Best Practices (20 min)                         │
│    ├── 4.1 Filing Requirements                                          │
│    ├── 4.2 Audit Preparation                                            │
│    └── Quiz                                                             │
│                                                                          │
│  Final Assessment                                                        │
│                                                                          │
│                                              [Enroll Now] or [Continue] │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.3 Lesson View

```
┌─────────────────────────────────────────────────────────────────────────┐
│  VAT Reforms > Module 2 > 2.1 Services Input Recovery                   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │  [Video Player or Content Area]                                      ││
│  │                                                                      ││
│  │  Under the old VAT regime, businesses could only recover VAT paid   ││
│  │  on goods purchased for business use. VAT on services was a pure    ││
│  │  cost with no recovery mechanism.                                   ││
│  │                                                                      ││
│  │  NTA 2025 Changes This Fundamentally                                ││
│  │                                                                      ││
│  │  The new law explicitly permits input VAT recovery on:              ││
│  │                                                                      ││
│  │  • Professional services (legal, accounting, consulting)            ││
│  │  • Marketing and advertising services                               ││
│  │  • IT and software services                                         ││
│  │  • Maintenance and repair services                                  ││
│  │  • Any other taxable services used for business                     ││
│  │                                                                      ││
│  │  ┌────────────────────────────────────────────────────────────────┐ ││
│  │  │ 📌 EXAMPLE                                                      │ ││
│  │  │                                                                 │ ││
│  │  │ ABC Ltd pays ₦10,000,000 annually for accounting services.     │ ││
│  │  │ VAT charged: ₦750,000 (7.5%)                                   │ ││
│  │  │                                                                 │ ││
│  │  │ Old Regime: ₦750,000 = pure cost                               │ ││
│  │  │ New Regime: ₦750,000 = recoverable input VAT                   │ ││
│  │  │                                                                 │ ││
│  │  │ Annual Savings: ₦750,000                                       │ ││
│  │  └────────────────────────────────────────────────────────────────┘ ││
│  │                                                                      ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  Progress: ████████████░░░░░░░░ 60%                                     │
│                                                                          │
│  [← Previous]                              [Mark Complete] [Next →]     │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.4 Quiz Interface

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Module 2 Quiz                                                           │
│  VAT Reforms > Input Recovery Rules                                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Question 3 of 5                                                         │
│                                                                          │
│  Under NTA 2025, which of the following is NOW recoverable as           │
│  input VAT that was NOT recoverable before?                             │
│                                                                          │
│  ○ VAT on raw materials purchased                                       │
│  ○ VAT on inventory purchases                                           │
│  ● VAT on legal services                                                │
│  ○ VAT on exported goods                                                │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ ✓ CORRECT!                                                          ││
│  │                                                                      ││
│  │ VAT on services, including legal services, is now recoverable       ││
│  │ under NTA 2025. Previously, only VAT on goods was recoverable.      ││
│  │                                                                      ││
│  │ VAT on raw materials and inventory was already recoverable under    ││
│  │ the old regime. Exports are zero-rated, not a recovery issue.       ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  Progress: ████████████░░░░░░░░ 3/5 correct                             │
│                                                                          │
│                                                         [Next Question] │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Learning Features

### 4.1 Content Types

**Text Lessons:**
- Rich formatted content
- Callout boxes
- Examples
- Calculation demonstrations

**Video Lessons:**
- Embedded video player
- Playback controls
- Transcript available
- Bookmarking

**Interactive Elements:**
- Calculation exercises
- Drag-and-drop activities
- Fill-in-the-blank
- Scenario simulations

### 4.2 Progress Tracking

**Lesson Completion:**
- Automatic: Time spent threshold
- Manual: "Mark Complete" button
- Quiz: Passing score required

**Module Completion:**
- All lessons completed
- Module quiz passed (if present)

**Course Completion:**
- All modules completed
- Final assessment passed

### 4.3 Assessments

**Module Quizzes:**
- 3-10 questions
- Multiple choice / True-False
- Immediate feedback
- Unlimited retakes
- Passing: 70%

**Final Assessment:**
- 15-30 questions
- Comprehensive coverage
- Time limit (optional)
- Limited retakes (3)
- Passing: 80%

---

## 5. Certificates

### 5.1 Certificate Generation

Upon course completion:
1. System generates certificate
2. Unique verification code assigned
3. PDF generated with branding
4. Certificate available for download

### 5.2 Certificate Content

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│                           CERTIFICATE OF COMPLETION                      │
│                                                                          │
│                                                                          │
│                              This certifies that                         │
│                                                                          │
│                              JOHN ADEYEMI                                │
│                                                                          │
│                      has successfully completed the course               │
│                                                                          │
│                     VAT Reforms and Input Recovery                       │
│                                                                          │
│                            2 CPD Hours Awarded                           │
│                                                                          │
│                           December 15, 2024                              │
│                                                                          │
│                                                                          │
│  Verification Code: TXL-2024-VAT-A7B9C3                                 │
│  Verify at: verify.taxlab.ng/TXL-2024-VAT-A7B9C3                        │
│                                                                          │
│                                                                          │
│  ─────────────────                    ─────────────────                  │
│  [Platform Signature]                  [Firm Logo if applicable]         │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 5.3 Verification System

Public verification page:
- Enter certificate code
- View certificate details
- Confirm authenticity
- No login required

---

## 6. Firm Management Features

### 6.1 Team Progress Dashboard

Partners/Managers see team training status:

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Team Training Progress                                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  TEAM OVERVIEW                                                           │
│                                                                          │
│  Total CPD Hours Earned: 45.5    Courses Completed: 18    Active: 6/8   │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ User           Courses Done  CPD Hours  Current Course              ││
│  ├─────────────────────────────────────────────────────────────────────┤│
│  │ Sarah Jones    5/8           12.5       Tax Planning (75%)          ││
│  │ John Adeyemi   4/8           9.0        VAT Reforms (65%)           ││
│  │ Mary Okafor    3/8           7.5        CIT Deep Dive (30%)         ││
│  │ David Eze      2/8           4.0        —                           ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Course Assignment

Managers can assign courses to team members:
- Select user(s)
- Select course(s)
- Set deadline (optional)
- Send notification

### 6.3 Compliance Tracking

Track CPD requirements:
- Annual CPD hour targets
- Progress toward target
- Expiring certifications
- Compliance reports

---

## 7. Data Model

### 7.1 Courses Table

**Table: cpd_courses**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| title | varchar(255) | Course title |
| slug | varchar(100) | URL identifier |
| short_description | text | Brief description |
| full_description | text | Full description |
| learning_objectives | jsonb | List of objectives |
| category | varchar(100) | Course category |
| difficulty | enum | beginner, intermediate, advanced |
| duration_minutes | integer | Total duration |
| cpd_hours | decimal(4,1) | CPD hours awarded |
| featured_image | varchar(255) | Banner image path |
| prerequisites | jsonb | Required courses |
| status | enum | draft, published, archived |
| published_at | timestamp | Publication date |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

### 7.2 Modules Table

**Table: cpd_modules**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| course_id | ULID | Parent course |
| title | varchar(255) | Module title |
| description | text | Module description |
| sort_order | integer | Display order |
| duration_minutes | integer | Module duration |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

### 7.3 Lessons Table

**Table: cpd_lessons**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| module_id | ULID | Parent module |
| title | varchar(255) | Lesson title |
| content_type | enum | text, video, interactive |
| content | text | Lesson content |
| video_url | varchar(255) | Video URL |
| duration_minutes | integer | Lesson duration |
| sort_order | integer | Display order |
| completion_type | enum | time, manual, quiz |
| completion_threshold | integer | Seconds required |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

### 7.4 Quizzes Table

**Table: cpd_quizzes**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| quizzable_type | varchar(50) | Module or Course |
| quizzable_id | ULID | Parent ID |
| title | varchar(255) | Quiz title |
| instructions | text | Quiz instructions |
| passing_score | integer | Percentage to pass |
| time_limit_minutes | integer | Time limit |
| max_attempts | integer | Retake limit |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

### 7.5 Questions Table

**Table: cpd_questions**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| quiz_id | ULID | Parent quiz |
| question_type | enum | multiple_choice, true_false, multi_select |
| question_text | text | Question content |
| options | jsonb | Answer options |
| correct_answer | jsonb | Correct answer(s) |
| explanation | text | Answer explanation |
| points | integer | Point value |
| sort_order | integer | Display order |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

### 7.6 Enrollments Table

**Table: cpd_enrollments**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| firm_user_id | ULID | Enrolled user |
| course_id | ULID | Course enrolled |
| status | enum | enrolled, in_progress, completed, abandoned |
| progress_percentage | integer | Overall progress |
| enrolled_at | timestamp | Enrollment date |
| started_at | timestamp | First activity |
| completed_at | timestamp | Completion date |
| certificate_code | varchar(50) | Certificate ID |
| assigned_by | ULID | Manager who assigned |
| deadline | date | Completion deadline |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

### 7.7 Progress Table

**Table: cpd_progress**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| enrollment_id | ULID | Parent enrollment |
| progressable_type | varchar(50) | Lesson or Quiz |
| progressable_id | ULID | Item ID |
| status | enum | not_started, in_progress, completed |
| time_spent_seconds | integer | Time on item |
| score | integer | Quiz score |
| attempts | integer | Quiz attempts |
| completed_at | timestamp | When completed |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

---

## 8. API Endpoints

### 8.1 Course Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/training/courses | List all courses |
| GET | /api/training/courses/{id} | Course details |
| POST | /api/training/courses/{id}/enroll | Enroll in course |

### 8.2 Progress Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/training/my-courses | User's enrollments |
| GET | /api/training/enrollments/{id} | Enrollment details |
| POST | /api/training/lessons/{id}/complete | Mark lesson complete |
| POST | /api/training/quizzes/{id}/submit | Submit quiz |

### 8.3 Certificate Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/training/certificates | User's certificates |
| GET | /api/training/certificates/{code} | Verify certificate |
| GET | /api/training/certificates/{code}/download | Download PDF |

---

## 9. Implementation Checklist

### Phase 1: Core Training

- [ ] Course, module, lesson models
- [ ] Course listing and detail pages
- [ ] Lesson viewer
- [ ] Basic progress tracking
- [ ] Enrollment system

### Phase 2: Assessments

- [ ] Quiz system
- [ ] Question types
- [ ] Score tracking
- [ ] Retake management

### Phase 3: Certificates

- [ ] Certificate generation
- [ ] PDF creation
- [ ] Verification system
- [ ] Certificate listing

### Phase 4: Management

- [ ] Team progress dashboard
- [ ] Course assignment
- [ ] Compliance tracking
- [ ] Reports

---

*This document should be updated as CPD training requirements evolve during development.*

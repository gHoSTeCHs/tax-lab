# TaxLab — Knowledge Base

## Document Information

| Item | Detail |
|------|--------|
| Document | Knowledge Base Module |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team |

---

## 1. Overview

The Knowledge Base provides searchable reference documentation explaining Nigerian tax legislation in plain language. It helps practitioners quickly find answers and understand the rules behind calculations.

### 1.1 Access

- **URL:** `/app/knowledge-base`
- **Guard:** firm
- **Roles:** All firm roles (read-only)

### 1.2 Purpose

- Explain NTA 2025 provisions clearly
- Provide practical examples
- Answer common questions
- Support self-service learning
- Reduce support requests

---

## 2. Content Structure

### 2.1 Categories

```
Knowledge Base
├── NTA 2025 Overview
│   ├── Introduction to the Reform
│   ├── Key Changes Summary
│   ├── Effective Dates
│   └── Transition Rules
│
├── Company Income Tax
│   ├── Rates and Thresholds
│   ├── Company Classifications
│   ├── Capital Allowances
│   ├── Loss Relief
│   └── Special Industries
│
├── Personal Income Tax
│   ├── Tax Bands
│   ├── Reliefs and Allowances
│   ├── Employment Income
│   ├── Self-Employment
│   └── Investment Income
│
├── Value Added Tax
│   ├── Registration
│   ├── Rates and Exemptions
│   ├── Input Recovery
│   ├── Filing Requirements
│   └── Special Schemes
│
├── Capital Gains Tax
│   ├── Chargeable Gains
│   ├── Rates
│   ├── Exemptions
│   └── Reliefs
│
├── Development Levy
│   ├── Old Levies (Historical)
│   ├── New Unified Levy
│   └── Exemptions
│
├── Compliance
│   ├── Filing Deadlines
│   ├── Payment Requirements
│   ├── Penalties
│   └── E-filing
│
└── Platform Guides
    ├── Getting Started
    ├── Running Calculations
    ├── Generating Reports
    └── FAQs
```

### 2.2 Article Types

| Type | Purpose | Example |
|------|---------|---------|
| Explainer | Explain a concept | "Understanding Small Company Status" |
| How-To | Step-by-step guide | "How to Calculate Capital Allowances" |
| Reference | Detailed specifications | "PIT Tax Bands Reference" |
| FAQ | Common questions | "VAT Input Recovery FAQs" |
| Comparison | Old vs. new | "CIT Rates: Before and After NTA" |

---

## 3. User Interface

### 3.1 Knowledge Base Home

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Knowledge Base                                                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ 🔍 Search articles...                                     [Search] ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  POPULAR ARTICLES                                                        │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ • Understanding the NTA 2025 Changes                                ││
│  │ • New VAT Input Recovery Rules Explained                            ││
│  │ • Small Company Threshold: What You Need to Know                    ││
│  │ • Capital Gains Tax Rate Changes                                    ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  BROWSE BY CATEGORY                                                      │
│                                                                          │
│  ┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐        │
│  │ 📋 NTA Overview   │ │ 🏢 Company Tax    │ │ 👤 Personal Tax   │        │
│  │ 8 articles       │ │ 15 articles      │ │ 12 articles      │        │
│  └──────────────────┘ └──────────────────┘ └──────────────────┘        │
│                                                                          │
│  ┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐        │
│  │ 💰 VAT            │ │ 📈 Capital Gains  │ │ 📅 Compliance     │        │
│  │ 10 articles      │ │ 6 articles       │ │ 8 articles       │        │
│  └──────────────────┘ └──────────────────┘ └──────────────────┘        │
│                                                                          │
│  RECENTLY UPDATED                                                        │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ Dec 14 - New NRS Guidance on E-Invoicing                            ││
│  │ Dec 12 - Updated Capital Allowance Rates                            ││
│  │ Dec 10 - VAT Filing Deadline Clarification                          ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Article View

```
┌─────────────────────────────────────────────────────────────────────────┐
│  ← Back to Knowledge Base                                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Company Income Tax > Rates and Thresholds                              │
│                                                                          │
│  ═══════════════════════════════════════════════════════════════════    │
│  Understanding Small Company Status Under NTA 2025                       │
│  ═══════════════════════════════════════════════════════════════════    │
│                                                                          │
│  Last updated: December 14, 2024                                        │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  OVERVIEW                                                                │
│                                                                          │
│  The Nigeria Tax Act 2025 introduces a significant change to small      │
│  company classification. Under the new regime, companies with annual    │
│  turnover of ₦50 million or less are classified as "small companies"   │
│  and are exempt from company income tax entirely.                       │
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ 💡 KEY CHANGE                                                       │ │
│  │                                                                     │ │
│  │ Old threshold: ₦25 million                                         │ │
│  │ New threshold: ₦50 million                                         │ │
│  │                                                                     │ │
│  │ This means approximately twice as many companies now qualify.       │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  QUALIFICATION CRITERIA                                                  │
│                                                                          │
│  To qualify as a small company under NTA 2025:                          │
│                                                                          │
│  • Annual turnover must not exceed ₦50,000,000                          │
│  • Company must be Nigerian-resident                                    │
│  • Turnover includes all gross revenue                                  │
│  • Assessment based on preceding year's turnover                        │
│                                                                          │
│  [Read more sections...]                                                 │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  RELATED ARTICLES                                                        │
│  • Medium Company Classification                                        │
│  • CIT Rates Comparison Table                                           │
│  • Threshold Management Strategies                                      │
│                                                                          │
│  ───────────────────────────────────────────────────────────────────    │
│                                                                          │
│  Was this article helpful?  [👍 Yes]  [👎 No]                           │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.3 Search Results

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Search Results for "VAT input recovery"                                 │
│  12 results found                                                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ New VAT Input Recovery Rules Under NTA 2025                         ││
│  │ VAT > Input Recovery                                                ││
│  │ The NTA 2025 significantly expands VAT input recovery. Previously, ││
│  │ only VAT on goods was recoverable. Now, VAT on services and        ││
│  │ capital assets is also recoverable...                               ││
│  │ [Read Article →]                                                    ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ How to Claim Input VAT on Services                                  ││
│  │ VAT > Input Recovery                                                ││
│  │ Step-by-step guide to claiming VAT on professional services,       ││
│  │ consulting fees, and other service inputs under the new regime...  ││
│  │ [Read Article →]                                                    ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  [More results...]                                                       │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Content Features

### 4.1 Rich Content Elements

**Callout Boxes:**
- Info (blue): General information
- Warning (yellow): Important caveats
- Alert (red): Critical warnings
- Success (green): Positive outcomes
- Example (purple): Practical examples

**Tables:**
- Rate tables
- Comparison tables
- Threshold tables

**Code Blocks:**
- Formulas
- Calculation examples

**Links:**
- Internal cross-references
- Related articles
- External sources (NRS, legislation)

### 4.2 Legislative References

Articles link to underlying legislation:
- Section numbers
- Act references
- Effective dates
- Amendment history

### 4.3 Calculation Examples

Embedded examples showing:
- Input values
- Step-by-step calculation
- Final result
- Comparison to old regime

---

## 5. Search Functionality

### 5.1 Search Features

- Full-text search across all articles
- Category filtering
- Tag filtering
- Highlighted matches
- Search suggestions
- Recent searches

### 5.2 Search Indexing

Indexed fields (weighted):
- Title (highest weight)
- Summary (high weight)
- Content (normal weight)
- Tags (high weight)
- Category name

### 5.3 Search Implementation

**PostgreSQL Full-Text Search:**
- `to_tsvector` for content indexing
- `ts_rank` for relevance scoring
- `ts_headline` for excerpts

---

## 6. Data Model

### 6.1 Categories Table

**Table: kb_categories**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| name | varchar(100) | Category name |
| slug | varchar(50) | URL identifier |
| description | text | Category description |
| icon | varchar(50) | Icon identifier |
| sort_order | integer | Display order |
| parent_id | ULID | Parent category |
| article_count | integer | Cached count |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

### 6.2 Articles Table

**Table: kb_articles**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| category_id | ULID | Parent category |
| title | varchar(255) | Article title |
| slug | varchar(100) | URL identifier |
| summary | text | Brief summary |
| content | text | Full content (Markdown) |
| content_html | text | Rendered HTML (cached) |
| tags | jsonb | Array of tags |
| related_articles | jsonb | Related article IDs |
| legislation_refs | jsonb | Legislative references |
| status | enum | draft, published, archived |
| author_id | ULID | Admin who created |
| published_at | timestamp | Publication date |
| last_reviewed_at | date | Last review date |
| view_count | integer | Total views |
| helpful_yes | integer | Helpful yes count |
| helpful_no | integer | Helpful no count |
| search_vector | tsvector | Full-text search index |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

---

## 7. Implementation Checklist

### Phase 1: Core Knowledge Base

- [ ] Category and article models
- [ ] Article list and detail views
- [ ] Category browsing
- [ ] Basic search functionality

### Phase 2: Enhanced Features

- [ ] Full-text search with PostgreSQL
- [ ] Rich content rendering
- [ ] Related articles
- [ ] Helpful feedback

### Phase 3: Content

- [ ] NTA 2025 overview articles
- [ ] Tax type deep-dives
- [ ] Practical examples
- [ ] Platform guides

---

*This document should be updated as knowledge base requirements evolve during development.*

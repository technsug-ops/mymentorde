# MentorDE — System Handbook (English)
**Version 1.0 | 2026**

> This document covers all modules, user roles, and workflows of the MentorDE ERP system.
> Prepared for technical staff, administrators, and end users.

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [User Roles and Permissions](#2-user-roles-and-permissions)
3. [Portal Guides](#3-portal-guides)
   - 3.1 Manager Portal
   - 3.2 Senior / Advisor Portal
   - 3.3 Guest Portal
   - 3.4 Student Portal
   - 3.5 Dealer Portal
   - 3.6 Marketing Admin Portal
4. [Module Documentation](#4-module-documentation)
5. [Integrations](#5-integrations)
6. [Admin Management](#6-admin-management)
7. [Security and GDPR](#7-security-and-gdpr)
8. [Frequently Asked Questions](#8-frequently-asked-questions)

---

## 1. System Overview

### What is MentorDE?

MentorDE is a **multi-portal ERP system** designed to manage the university application process in Germany. It provides separately customized workspaces for consulting firms, student applicants, senior advisors, dealers, and marketing teams.

### Core Business Flow

```
Applicant Submits Form (Guest)
           ↓
Senior Advisor Assigned
           ↓
Documents Collected & Reviewed
           ↓
Contract Signed
           ↓
Promoted to Student Status
           ↓
University Applications Tracked
           ↓
Admission & Payment Monitoring
```

### Technology Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 8.4 / Laravel 12 |
| Database | MySQL |
| Payments | Stripe (EUR) |
| Notifications | WhatsApp (Meta Cloud API), Email |
| 2FA | Google Authenticator (TOTP) |
| Storage | Local disk / AWS S3 |

---

## 2. User Roles and Permissions

### Role Hierarchy

```
Manager / System Admin          → Highest authority
├── Operations Admin/Staff      → Operations management
├── Finance Admin/Staff         → Financial management
├── Marketing Admin/Staff       → Marketing & CMS
├── Sales Admin/Staff           → Sales & lead management
Senior / Mentor                 → Advisory services
Dealer                          → Reseller network
Guest                           → Applicant (pre-enrollment)
Student                         → Enrolled student
```

### Role Reference Table

| Role | Code | Portal | Description |
|------|------|--------|-------------|
| Manager | `manager` | Manager | Full system access |
| System Admin | `system_admin` | Manager | Technical management |
| Operations Admin | `operations_admin` | Manager | Process management |
| Finance Admin | `finance_admin` | Manager | Payments & revenue |
| System Staff | `system_staff` | Manager | Limited technical access |
| Operations Staff | `operations_staff` | Manager | Limited operations |
| Finance Staff | `finance_staff` | Manager | Limited finance |
| Senior Advisor | `senior` | Senior | Student tracking |
| Mentor | `mentor` | Senior | Same panel as senior |
| Guest Applicant | `guest` | Guest | Application & documents |
| Student | `student` | Student | Tracking & payments |
| Dealer | `dealer` | Dealer | Referrals & commissions |
| Marketing Admin | `marketing_admin` | Marketing | Campaigns & content |
| Sales Admin | `sales_admin` | Marketing | Leads & sales |
| Marketing Staff | `marketing_staff` | Marketing | Limited marketing |
| Sales Staff | `sales_staff` | Marketing | Limited sales |

### Permission Summary

| Action | Manager | Senior | Guest | Student | Dealer | Marketing |
|--------|---------|--------|-------|---------|--------|-----------|
| View all users | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Assign guests | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Approve documents | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Create contracts | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Create invoices | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| View students | ✅ | ✅* | ❌ | ❌ | ✅* | ❌ |
| View own profile | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Manage content | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| View commissions | ✅ | ❌ | ❌ | ❌ | ✅* | ❌ |

> `*` Own assigned records only

---

## 3. Portal Guides

---

### 3.1 Manager Portal

**URL:** `/manager/dashboard`
**Roles:** manager, system_admin, operations_admin, finance_admin, system_staff, operations_staff, finance_staff

#### Dashboard

Upon login you will see:
- **KPI Cards:** Total guests, active students, monthly revenue, pending documents
- **Recent Activity:** Latest applications, documents, tickets
- **Finance Summary** (finance_admin/finance_staff): EUR collected this month, overdue invoices, pending payments
- **Quick Links:** Add new guest, generate report, GDPR dashboard

#### Student & Guest Management

| Menu | Function |
|------|----------|
| Applications | List, filter, and view all guest applications |
| Students | Manage active students |
| Promote to Student | Initiate Guest → Student transition |
| Assign Senior | Assign an advisor to a guest |

**Guest Search & Filtering:**
- Search by name, email, or tracking code
- Filter by application type (bachelor, master, language course)
- Filter by status (new, under review, accepted, rejected)
- Filter by country, city, or target university

#### Staff Management

`/manager/staff`

- Add new staff (name, email, role, password)
- Edit / deactivate staff
- Assign role template (permission set)
- View permission history

#### Document Management

- **Document Categories:** Create categories, mark as required/optional
- **Document Review:** Approve / reject / comment on uploaded documents
- **Document Templates:** Create standard document sets

#### Contract Management

- Create contract templates (HTML editor)
- Send contracts → student signs digitally
- View / download signed contracts
- Track contract versions

#### Payments & Revenue

`/manager/payments`

- Create invoices (student, amount in EUR, due date)
- Track payment status (pending / paid / overdue)
- Manage revenue milestones
- Calculate dealer commissions
- Monthly / yearly revenue reports

#### Task Management (Task Board)

`/manager/tasks`

- Create tasks, assign, set priority
- Kanban view (To Do / In Progress / Done)
- Recurring task rules
- Time tracking (start / stop)
- Task dependencies

#### Tickets (Support Requests)

`/manager/tickets`

- View all guest and student requests
- Route to department (technical, finance, operations)
- Set priority (low / normal / high / urgent)
- Add internal notes (invisible to customer)
- SLA tracking

#### Reports

- **Snapshot Reports:** Weekly / monthly summaries
- **Audit Report:** Who did what and when
- **Security Anomalies:** Suspicious logins, permission breach attempts
- **GDPR Dashboard:** Data deletion requests, consent records

---

### 3.2 Senior / Advisor Portal

**URL:** `/senior/dashboard`
**Roles:** senior, mentor

#### Dashboard

- Assigned guest list (KPI: total, active, new this month)
- Pipeline status (how many at each stage)
- Today's tasks and reminders
- Unread messages
- Performance targets (monthly acceptance goal vs actual)

#### Student 360° View

9 tabs for each student:

| Tab | Content |
|-----|---------|
| Overview | Personal info, application status |
| Documents | Uploaded documents, approval status |
| Contract | Contract status, signing date |
| Applications | University application tracking |
| Payments | Payment plan, overdue status |
| Messages | Correspondence with student |
| Notes | Internal notes (invisible to student) |
| Timeline | Full activity history |
| Shipments | Document delivery tracking |

#### Pipeline Kanban

`/senior/guest-pipeline`

6-column drag-and-drop Kanban board:
1. **New Application** — Assigned, not yet contacted
2. **Initial Contact** — First contact made
3. **Document Collection** — Documents requested / being collected
4. **Under Review** — Application file being prepared
5. **Application Submitted** — Sent to university
6. **Awaiting Result** — Waiting for acceptance / rejection

Drag a card between columns → status automatically updated.

#### Batch Review

`/senior/batch-review`

Quick review with keyboard shortcuts:
- `A` → Approve
- `R` → Reject
- `N` → Next

#### Messaging

`/senior/messages`

- WhatsApp-style split-panel interface
- Real-time messaging with students / guests
- File sharing
- Message forwarding, reactions, editing
- Auto-refresh every 10 seconds

#### Performance Tracking

- Monthly target vs actual
- Lead score (8 factors: document completion, response time, etc.)
- Historical performance charts

---

### 3.3 Guest Portal

**URL:** `/guest/dashboard`
**Roles:** guest

#### Application Process

1. `/apply` — Fill out the general application form (no account needed)
2. System automatically creates user account, password sent by email
3. Log in → `/guest/dashboard`

#### Dashboard

- **Progress Bar:** Application → Documents → Contract → Student stages
- **Next Step CTA:** Shows what you need to do
- **Assigned Advisor:** Senior's name and contact
- **Active Campaigns:** Current announcements

#### Registration Form

`/guest/registration/form`

- Personal details (name, surname, date of birth, nationality)
- Educational background
- Target university / department / city
- Language certificates
- Motivation statement
- Auto-save (every 30 seconds)

#### Document Upload

`/guest/registration/documents`

- Required document list (defined by advisor)
- Per-document upload (PDF, JPG, PNG — max 10MB)
- Upload status: Pending / Under Review / Approved / Rejected
- If rejected, advisor's comment is shown

#### Contract

`/guest/contract`

- View contract text
- Sign digitally (name, date, confirmation checkbox)
- Download signed PDF

#### Discover (Content Hub)

`/guest/discover`

- Blog posts, videos, podcasts, presentations
- Category filters: Student Life / Careers / Culture / Tips
- City guides (Berlin, Munich, Hamburg...)
- University guide

#### Support Ticket

`/guest/tickets`

- Open a new ticket (subject, description, priority)
- Track ticket status
- Communicate with advisor

#### Cost Calculator

`/guest/cost-calculator`

- Estimated living cost based on target city
- Rent, food, transport, entertainment items
- Displayed in EUR

---

### 3.4 Student Portal

**URL:** `/student/dashboard`
**Roles:** student

> Account automatically transitions to Student status when the guest application is approved and the contract is signed.

#### Dashboard

- Payment plan summary (paid / pending / overdue)
- University application status
- Recent documents
- Advisor messages
- Exchange rate (TRY chip)

#### Payment Tracking

`/student/payments`

- Invoice list (invoice no., amount EUR, due date, status)
- **💳 Pay button** — Redirects to Stripe Checkout
- Payment history
- Milestone tracking (e.g. registration fee, first semester...)

#### Stripe Payment Process

1. Click "💳 Pay" button
2. Redirected to Stripe's secure payment page
3. Enter card details (Visa, Mastercard)
4. Payment confirmed
5. System automatically updates: `status=paid`, payment date recorded

#### Document Center

`/student/documents`

- View / download documents shared by advisor
- Upload own documents
- Track institutional documents (student card, insurance, etc.)

#### University Application Tracking

`/student/university-applications`

- List of applied universities
- Status of each application (submitted / pending / accepted / rejected)
- Application dates and deadlines

#### Shipment Tracking

`/student/shipments`

- Track physical document deliveries
- Cargo tracking number
- Estimated delivery date

#### Appointment Calendar

`/student/appointments`

- View appointments with advisor
- External calendar integration (Google Calendar)
- Reminder notifications

#### Discover (Content Hub)

`/student/discover`

- Same content as guest discover + student-specific
- Career guides, cultural content, city videos

---

### 3.5 Dealer Portal

**URL:** `/dealer/dashboard`
**Roles:** dealer

#### Dashboard

- Number of referred students
- Commission earned this month (EUR)
- Payment milestones
- Active student list

#### Referral Management

- View students registered through you
- Student application status (permitted information)
- Document status tracking

#### Commission Tracking

`/dealer/commissions`

- Commission amount per student
- Payment timing (triggered when student pays)
- Total earnings summary
- Download commission breakdown

#### Milestone Tracking

`/dealer/milestones`

- Revenue milestone targets
- Actual vs target
- Amount remaining to next milestone

#### Training & Resources

- System tutorial videos
- Sales materials
- Campaign announcements

#### Contract

`/dealer/contract`

- View dealer agreement
- Download signed contract

---

### 3.6 Marketing Admin Portal

**URL:** `/mktg-admin/dashboard`
**Roles:** marketing_admin, sales_admin, marketing_staff, sales_staff

#### Panel Mode Selection

**"Panel Mode"** button in sidebar:
- 📣 **Marketing Mode** — Campaigns, content, social media
- 💼 **Sales Mode** — Leads, CRM, sales tracking

All 4 roles can use both panels.

#### Marketing Mode

**Campaigns** (`/mktg-admin/campaigns`)
- Create campaigns (email, WhatsApp, social media)
- Define target audience (country, age, education level)
- Scheduled sending
- Open / click-through rates

**CMS & Content** (`/mktg-admin/content`)
- Add blog post, video, podcast, presentation
- Category: Student Life, Careers, Culture...
- Target audience: Guest / Student / All
- Mark as featured
- Publish / draft / archive

**Email Templates** (`/mktg-admin/email-templates`)
- Create HTML email templates
- Variables: `{first_name}`, `{tracking_code}`, `{university}` etc.
- Preview & test send

**Tracking Links** (`/mktg-admin/tracking-links`)
- Create UTM-parametered links
- Click, conversion tracking

#### Sales Mode

**Lead Management** (`/mktg-admin/leads`)
- List incoming leads (source: web, referral, campaign)
- View lead score (0-100)
- Assign to sales representative
- Update status (new → contacted → qualified → lost)

**Events** (`/mktg-admin/events`)
- Add university fair, webinar, info sessions
- Registration management
- Download attendee list

---

## 4. Module Documentation

---

### 4.1 User Authentication

#### Login

URL: `/login`

- Login with email and password
- Account locked for 30 minutes after 10 failed attempts
- Lockout time displayed on screen

#### Password Reset

1. `/forgot-password` → enter email
2. Click link in email to reset password (`/reset-password/{token}`)
3. Link valid for 60 minutes

#### Two-Factor Authentication (2FA)

**Mandatory** for manager, system_admin, operations_admin, finance_admin roles.

**Initial Setup:**
1. After login, automatically redirected to `/2fa/setup`
2. Open Google Authenticator / Microsoft Authenticator / Authy
3. Scan QR code (or enter secret manually)
4. Enter the 6-digit code shown in the app
5. Setup complete — not asked again (per session)

**Subsequent Logins:**
- After entering password, 6-digit code screen opens
- Enter code from your app → login complete
- Code changes every 30 seconds

#### Email Verification

When new accounts are created, the system auto-verifies (all accounts are created by admins). If manual verification is needed, a link can be resent from `/email/verify`.

---

### 4.2 Document Management

#### Document Categories

Created by manager. Each category has:
- Name and description
- Required / optional flag
- Target audience (guest / student)

#### Document Upload (Guest/Student)

- Supported formats: PDF, JPG, JPEG, PNG
- Maximum file size: 10 MB
- Uploaded per document category
- New file uploaded to same category overwrites the previous one

#### Document Review (Senior/Manager)

| Status | Description |
|--------|-------------|
| Pending | Not yet uploaded |
| Under Review | Uploaded, awaiting approval |
| Approved | ✅ Document accepted |
| Rejected | ❌ Returned with reason |

Automatic notification sent when rejected; reason visible to student.

#### Security

- File type verified server-side with magic byte check
- File name hashed for storage (original name not visible)
- Only authorized users can access documents

---

### 4.3 Contract System

#### Creating a Contract Template (Manager)

`/manager/contract-templates`

1. Enter template name and content (HTML editor)
2. Use variables: `{student_name}`, `{date}`, `{package_name}` etc.
3. Mark as active / inactive
4. Role-based template assignment (which guest type it applies to)

#### Sending a Contract

1. From guest/student profile, click "Send Contract"
2. Select template → preview → send
3. Guest/student receives notification

#### Signing (Guest/Student)

1. Notification / menu link in portal
2. Read contract text
3. Type full name, confirm date, check the box
4. Click "Sign"
5. Signed PDF automatically generated

#### Version Tracking

Every contract change is logged:
- Change date
- User who made the change
- What changed (before → after)

---

### 4.4 Internal Messaging

**Access:** `/im` (all internal roles)

#### Features

- WhatsApp-style split-panel interface
- Conversation list (left) + message area (right)
- Auto-refresh every 10 seconds
- File sharing (document sending)
- Message forwarding
- Emoji reactions
- Message editing (after sending)
- Pinned messages

#### Starting a Conversation

1. Click "+" icon on left panel
2. Search for user (name or email)
3. Start conversation

---

### 4.5 Ticket (Support Request) System

#### Opening a Ticket (Guest / Student)

1. "Support" menu in portal
2. Enter subject (e.g. "Document approval delay")
3. Write description
4. Attach file (optional)
5. Submit

#### Automatic Routing

Tickets are automatically assigned to a department based on topic:
- Technical issues → Technical Support
- Payment issues → Finance
- Document issues → Operations

#### Manager/Senior Side

- Ticket list (filtered by department, priority, status)
- Add internal note (invisible to customer)
- Transfer to another staff member
- Close / reopen

#### Status Flow

```
Open → In Progress → Awaiting Response → Resolved → Closed
```

---

### 4.6 Payment System

#### Creating an Invoice (Manager)

1. `Manager → Payments → New Invoice`
2. Select student
3. Enter amount (EUR), description, due date
4. Save → student receives notification

#### Stripe Payment (Student)

1. Click "💳 Pay" button in student portal
2. Redirected to Stripe's secure page
3. Enter card details (Visa, Mastercard)
4. Payment confirmed
5. System auto-updates: `status=paid`, payment date recorded

#### Webhook Flow

```
Stripe → POST /webhooks/stripe
       → System verifies signature
       → StudentPayment.status = "paid"
       → StudentPayment.paid_at = current timestamp
```

#### Payment Statuses

| Status | Description |
|--------|-------------|
| `pending` | Invoice created, awaiting payment |
| `paid` | Payment received |
| `overdue` | Due date passed, unpaid |
| `cancelled` | Invoice cancelled |

#### Exchange Rate

- EUR/TRY rate updated automatically daily
- TRY equivalent shown informally in student panel
- All transactions processed in EUR

---

### 4.7 Content Hub (Discover)

**Guest access:** `/guest/discover`
**Student access:** `/student/discover`

#### Content Types

| Type | Display |
|------|---------|
| Blog | HTML text, cover image |
| Video | YouTube embed |
| Podcast | Spotify / SoundCloud embed |
| Presentation | Google Slides / Canva iframe |
| Experience | Personal story card |
| Career Guide | Sectioned text |
| Quick Tip | Compact card |

#### Categories

- 🎓 Student Life
- 🎭 Culture & Entertainment
- 💼 Careers & Professions
- 💡 Practical Tips
- 🏙 City Content
- 🏛 University Guide
- ⭐ Success Stories

#### Search

Type in search box → server-side search after 500ms → searches content across all pages.

#### Individual Content Page

`/guest/content/{slug}`

- Cover image (full width with title overlay)
- Meta info (date, category, view count)
- Content (by type: text / video / audio / iframe)
- Related content (same category)

#### Content Management (Marketing Admin)

`/mktg-admin/content`

1. Add new content
2. Select type and category
3. Write content (Turkish)
4. Enter URL for video/podcast
5. Add tags (adding a city slug makes it appear on city page too)
6. Target audience: Guest / Student / All
7. Publish

---

### 4.8 Lead Management

#### Lead Score (0-100)

Each lead is automatically scored on 8 factors:
1. Document completion rate
2. Form response time
3. Communication activity
4. Application quality
5. Target compatibility
6. Referral source
7. Budget suitability
8. Timing

Automatically recalculated daily at 02:30.

#### Lead Sources

- Web form (`/apply`)
- Dealer referral
- Campaign UTM
- Manual entry (sales team)

---

### 4.9 Senior Performance Tracking

Monthly snapshots taken (1st of month, 03:30):

- Number of assigned guests
- Completed applications
- Acceptance rate
- Average response time
- Student satisfaction score

Historical performance displayed as charts.

---

### 4.10 Notification System

#### Channels

| Channel | When Used |
|---------|-----------|
| Email | Important actions (document approval, contract, payment) |
| In-App | Portal notifications (real-time) |
| WhatsApp | Reminders, urgent notifications |

#### Automatic Notifications

| Event | Recipient | Channel |
|-------|-----------|---------|
| New application | Manager, Senior | Email + In-App |
| Document uploaded | Senior | In-App |
| Document approved | Guest/Student | Email + In-App |
| Document rejected | Guest/Student | Email + In-App |
| Contract sent | Guest/Student | Email |
| Contract signed | Manager, Senior | In-App |
| Payment made | Manager | In-App |
| Payment overdue | Student | Email + WhatsApp |
| Ticket opened | Relevant department | In-App |
| Ticket answered | Requester | Email + In-App |

#### Scheduled Notifications

- **Birthday wishes** — 09:00 AM
- **Due date reminder** — 3 days before
- **Inactivity reminder** — After 7 days of no activity
- **Senior reminders** — Weekday mornings at 08:30

---

### 4.11 Scheduled Tasks (Cron)

`php artisan schedule:run` must run every minute on the server.

| Task | Frequency | Function |
|------|-----------|----------|
| `notifications:dispatch` | Every minute | Send pending notifications |
| `gdpr:enforce-retention` | Daily 03:00 | Clean up old data |
| `currency:sync-rates` | Daily 06:00 | Update EUR/TRY rate |
| `leads:recalculate-scores` | Daily 02:30 | Recalculate lead scores |
| `senior:snapshot-performance` | Monthly 1st | Record performance snapshot |
| `security:anomaly-check` | Hourly | Scan for security anomalies |
| `archive:inactive-records` | Daily 01:30 | Archive guests inactive 180+ days |
| `contract:send-reminders` | Daily | Remind about unsigned contracts |
| `email:process-drip` | Daily | Process drip email campaigns |

---

## 5. Integrations

### 5.1 Stripe (Payments)

**Setup:**
```
STRIPE_KEY=pk_live_xxxx
STRIPE_SECRET=sk_live_xxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxx
```

**In Stripe Dashboard:**
1. Add webhook endpoint: `https://yoursite.com/webhooks/stripe`
2. Select event: `checkout.session.completed`
3. Copy webhook secret → paste into `.env`

**Testing:**
With Stripe CLI: `stripe listen --forward-to localhost:8000/webhooks/stripe`

---

### 5.2 WhatsApp (Meta Cloud API)

**Setup:**
```
WHATSAPP_PHONE_NUMBER_ID=xxxx
WHATSAPP_ACCESS_TOKEN=xxxx
WHATSAPP_VERIFY_TOKEN=mentorde_verify
WHATSAPP_API_VERSION=v19.0
```

**In Meta Business Suite:**
1. Create a WhatsApp Business App
2. Get Phone Number ID and Access Token
3. Match the webhook verification token

---

### 5.3 SMTP (Email)

**Setup:**
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org       # or smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=postmaster@...
MAIL_PASSWORD=xxxx
MAIL_FROM_ADDRESS=noreply@mentorde.com
MAIL_FROM_NAME="MentorDE"
```

---

### 5.4 Queue Worker (Notifications)

Queue worker must be running for notifications and WhatsApp messages.

**Supervisor configuration:**
```ini
[program:mentorde-worker]
command=php /var/www/mentorde/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
```

---

### 5.5 2FA (Google Authenticator)

Install one of the following on your phone:
- Google Authenticator (iOS / Android)
- Microsoft Authenticator
- Authy

Scan the QR code or enter the 32-character secret manually.

---

## 6. Admin Management

### Adding New Staff

`Manager → Staff → Add New`

1. Full name, email, select role
2. Set password (user can change after first login)
3. Save → user automatically added to system, email pre-verified

### Adding a New Senior

`Manager → Advisors → New Senior`

1. Personal information
2. Senior code auto-generated (e.g. `SR-2026-0001`)
3. Capacity (maximum number of guests)
4. Auto-assignment on/off

### Adding a New Dealer

`Manager → Dealers → New Dealer`

1. Company name, contact person, email
2. Dealer type (individual / corporate)
3. Commission rate
4. Region / country

### System Settings

`Manager → Settings`

- Company information
- Email templates
- Notification rules
- File upload limits
- GDPR settings

---

## 7. Security and GDPR

### Security Layers

| Layer | Description |
|-------|-------------|
| Account lockout | 10 failed logins → 30-minute lock |
| 2FA | Mandatory TOTP for Manager/Admin roles |
| Rate limiting | Request limit per API endpoint |
| Security headers | CSP, HSTS, X-Frame-Options |
| File validation | Magic byte check |
| Audit trail | Every critical action is logged |

### Security Anomaly Detection

Hourly scan checks for:
- Multiple failed logins from same IP
- Admin access at unusual hours
- Bulk data access

### GDPR

**Dashboard:** `Manager → GDPR Dashboard`

- Data deletion requests
- Data export requests
- Consent records (cookie, KVKK)
- Automatic cleanup (180 days inactive → archive → 365 days → delete)

**User rights:**
- **Data access:** Guest/Student can download their data as JSON (`/guest/gdpr/export`)
- **Data deletion:** User submits request → Manager approves → system cleans up

---

## 8. Frequently Asked Questions

**Q: I forgot my password, what should I do?**
A: Click "Forgot Password" on the login page and enter your email address. A link valid for 60 minutes will be sent.

**Q: I lost my 2FA phone, what should I do?**
A: Contact your Manager. The Manager can reset 2FA on your account.

**Q: Why is document upload failing?**
A: Supported formats: PDF, JPG, PNG. Maximum size: 10 MB. Check the file size and format.

**Q: Why did my Stripe payment fail?**
A: Check your card limit, expiry date, and CVV. If the problem persists, contact your bank or try a different card.

**Q: WhatsApp notifications are not arriving.**
A: Verify the WhatsApp Business account is active and the integration is set up. `WHATSAPP_PHONE_NUMBER_ID` and `WHATSAPP_ACCESS_TOKEN` must be filled in `.env`.

**Q: I'm adding a new user but no email is being sent.**
A: Verify that the mail driver is set to SMTP in production (`.env`: `MAIL_MAILER=smtp`).

**Q: I see an error in the system, what should I do?**
A: Take a screenshot of the error, check the browser console (F12), and share with your developer.

**Q: How do I switch between Marketing and Sales mode?**
A: Use the "Panel Mode" toggle button in the sidebar. Click 📣 Marketing or 💼 Sales.

**Q: Can a dealer see all students?**
A: No. Dealers can only see the students they referred. They cannot access other students' information.

**Q: How is the commission calculated?**
A: Commission is calculated based on each student's payment. When a student makes a payment, the system automatically calculates the dealer's share according to the agreed commission rate.

---

*MentorDE System Handbook — English Version 1.0*
*Last updated: 2026*

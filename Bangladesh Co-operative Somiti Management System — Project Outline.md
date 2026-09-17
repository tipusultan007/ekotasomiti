# Bangladesh Co-operative Somiti Management System

## 1. Project Overview

A web-based Co-operative Somiti Management System for Bangladesh to manage:

- Members
- Areas
- Field Officers
- Savings accounts
- Savings collections
- Loans
- Loan disbursement
- Loan installments
- Interest/service charges
- Daily cash collection
- Receipts
- Member balances
- Field Officer collections
- Area-wise operations
- Reports
- User permissions
- Accounting/cash management

### Technology Stack

- **Backend:** Laravel
- **Frontend:** Blade
- **CSS/UI:** Bootstrap 5.3
- **Database:** MySQL
- **JavaScript:** Vanilla JavaScript / lightweight libraries where necessary
- **Authentication:** Laravel authentication
- **Authorization:** Role/permission based
- **PDF:** Laravel-compatible PDF library
- **Excel/CSV:** Laravel Excel or CSV export

The application should use normal Laravel MVC architecture.

**Do not use Repository Pattern.**

Use:

- Models
- Controllers
- Form Requests
- Services where business logic becomes large
- Policies/permissions
- Jobs only where background processing is actually required

---

# 2. Organizational Structure

The system should support the following hierarchy:

```text
Somiti
│
├── Areas
│   ├── Area 01
│   ├── Area 02
│   └── Area 03
│
├── Field Officers
│   ├── Officer A
│   ├── Officer B
│   └── Officer C
│
└── Members
    ├── Member 001
    ├── Member 002
    └── Member 003
```

A member should belong to:

- One area
- One primary field officer

An area may have:

- Multiple field officers
- Multiple members

---

# 3. User Roles

Initial roles should be simple.

### Super Admin

Full system access.

### Manager/Admin

Can manage:

- Members
- Areas
- Field officers
- Savings
- Loans
- Collections
- Reports

### Field Officer

Can primarily access members assigned to them.

Can:

- View members
- Collect savings
- Collect loan installments
- Record transactions
- Print/generate receipts
- View their collection history

They should not be able to modify system-wide financial settings.

### Cashier

Can manage:

- Cash receiving
- Cash payments
- Cash register
- Field Officer cash submission
- Daily cash closing

### Accountant

Can access:

- Financial transactions
- Income/expense
- Cash/bank
- Reports
- Accounting-related functions

---

# 4. Area Management

Each account/member should be area-based.

### Area fields

- Area ID
- Area Code
- Area Name
- Address/location
- Assigned Field Officer(s)
- Status
- Notes

Example:

```text
Area Code: CTG-01
Area Name: Halishahar
Field Officer: FO-001
Status: Active
```

---

# 5. Field Officer Management

Each field officer should have a unique employee/officer number.

### Information

- Officer ID
- Officer Code
- Name
- Mobile
- NID
- Address
- Joining date
- Assigned areas
- Status
- Login account
- Commission/incentive configuration (future)

A field officer should only see members/accounts they are authorized to handle.

---

# 6. Member Management

The member is the central entity.

### Member information

- Member number
- Membership date
- Name
- Father/Husband name
- Mother name
- Date of birth
- Gender
- Mobile number
- NID number
- Address
- Area
- Field Officer
- Occupation
- Nominee
- Photo
- NID document
- Status

### Member statuses

- Active
- Inactive
- Suspended
- Closed

A member can have multiple accounts.

Example:

```text
Member
  ├── Daily Savings Account
  ├── Weekly Savings Account
  ├── Monthly Savings Account
  ├── Daily Loan Account
  └── Monthly Loan Account
```

---

# 7. Savings Programs

There will initially be exactly three savings programs.

## 7.1 Daily Savings

Example:

```text
Program: Daily Savings
Frequency: Daily
Account Prefix: DS
```

A member can deposit every working/collection day.

---

## 7.2 Weekly Savings

```text
Program: Weekly Savings
Frequency: Weekly
Account Prefix: WS
```

Member deposits once per week.

---

## 7.3 Monthly Savings

```text
Program: Monthly Savings
Frequency: Monthly
Account Prefix: MS
```

Member deposits once per month.

---

# 8. Savings Program Configuration

Do not hard-code these programs inside controllers.

Create a `savings_programs` table.

Example:

| Program | Frequency | Prefix |
|---|---|---|
| Daily Savings | Daily | DS |
| Weekly Savings | Weekly | WS |
| Monthly Savings | Monthly | MS |

This allows you to add future products without rewriting the system.

Possible future programs:

- DPS
- Special Savings
- Fixed Deposit
- Child Savings

---

# 9. Savings Account

A member can open one or more savings accounts depending on business rules.

Each account must have its own account number.

Example:

```text
Member: 000125

Daily Savings:
DS-CTG-00000125

Weekly Savings:
WS-CTG-00000078

Monthly Savings:
MS-CTG-00000043
```

The exact numbering format should be configurable.

### Savings account fields

- Account ID
- Account number
- Member ID
- Savings program ID
- Area ID
- Field Officer ID
- Opening date
- Opening balance
- Current balance
- Minimum deposit
- Expected deposit
- Status
- Closed date
- Closing reason

---

# 10. Savings Transactions

Every deposit should create a transaction.

### Transaction types

- Deposit
- Withdrawal
- Adjustment
- Correction
- Transfer
- Account opening
- Account closing

Each transaction should contain:

- Transaction number
- Account
- Member
- Program
- Amount
- Transaction date
- Collection date
- Payment method
- Field Officer
- Area
- Received by
- Reference
- Notes

### Payment methods

Initially:

- Cash
- Bank
- bKash
- Nagad
- Other

---

# 11. Daily Collection

Because this is a Bangladesh-based Somiti system, daily field collection should be a major feature.

Field Officer opens their collection screen.

Example:

```text
Field Officer: Rahim
Date: 20-08-2026

Member       Account       Expected     Collected
--------------------------------------------------
Karim        DS-0001        ৳100          ৳100
Rahman       DS-0002        ৳100          ৳100
Jamal        WS-0003        ৳500          ৳500
```

The system should show:

- Expected collection
- Actual collection
- Due
- Overdue
- Today's collection
- Total collected by officer

---

# 12. Collection Sheet

Field officers should have a printable/mobile-friendly collection sheet.

It should show:

- Area
- Field Officer
- Date
- Member
- Account number
- Program
- Installment/deposit amount
- Previous balance
- Current balance
- Signature/status

This can later become a mobile application feature.

---

# 13. Loan Programs

There will initially be three loan programs.

## Daily Loan

Installment frequency:

**Daily**

Example:

```text
Loan: ৳50,000
Term: 50 days
Installment: ৳1,100
```

---

## Weekly Loan

Installment frequency:

**Weekly**

Example:

```text
Loan: ৳50,000
Term: 50 weeks
Installment: ৳1,200
```

---

## Monthly Loan

Installment frequency:

**Monthly**

Example:

```text
Loan: ৳100,000
Term: 12 months
Installment: ৳10,000
```

The actual calculation should be configurable.

---

# 14. Loan Products

Create a `loan_products` table rather than hard-coding Daily/Weekly/Monthly loans.

Example:

| Product | Frequency |
|---|---|
| Daily Loan | Daily |
| Weekly Loan | Weekly |
| Monthly Loan | Monthly |

Each product can contain:

- Product name
- Code
- Frequency
- Minimum loan
- Maximum loan
- Interest rate
- Interest type
- Processing fee
- Insurance/other fees
- Minimum term
- Maximum term
- Status

---

# 15. Loan Account

Each loan should receive a unique account number.

Example:

```text
DL-CTG-000001
WL-CTG-000001
ML-CTG-000001
```

Loan account should be connected to:

- Member
- Area
- Field Officer
- Loan product

---

# 16. Loan Application Process

Recommended workflow:

```text
Loan Application
       ↓
Verification
       ↓
Approval
       ↓
Disbursement
       ↓
Active Loan
       ↓
Installment Collection
       ↓
Fully Paid
       ↓
Closed
```

### Loan statuses

- Draft
- Submitted
- Under Review
- Approved
- Rejected
- Disbursed
- Active
- Overdue
- Completed
- Written Off
- Cancelled

---

# 17. Loan Application

Information:

- Application number
- Member
- Loan product
- Requested amount
- Requested term
- Purpose
- Area
- Field Officer
- Application date
- Verification information
- Guarantor
- Nominee
- Documents
- Remarks

---

# 18. Loan Approval

The system should record:

- Requested amount
- Approved amount
- Interest rate
- Term
- Installment amount
- Fees
- Approval date
- Approved by
- Remarks

Approval history should never be silently overwritten.

---

# 19. Loan Disbursement

After approval:

```text
Approved
   ↓
Disbursement
   ↓
Loan Account Created/Activated
```

Disbursement should generate a financial transaction.

Payment methods:

- Cash
- Bank
- Other configured methods

The system should record the actual amount disbursed.

---

# 20. Loan Repayment Schedule

When a loan is disbursed, the system should generate a repayment schedule.

Example:

| Installment | Due Date | Principal | Interest | Total | Paid | Status |
|---|---|---:|---:|---:|---:|---|
| 1 | 21 Aug | ৳800 | ৳200 | ৳1,000 | ৳1,000 | Paid |
| 2 | 22 Aug | ৳800 | ৳200 | ৳1,000 | ৳0 | Due |
| 3 | 23 Aug | ৳800 | ৳200 | ৳1,000 | ৳0 | Due |

The schedule should support:

- Paid
- Partially Paid
- Due
- Overdue
- Waived

---

# 21. Loan Collection

Field Officer should be able to collect:

- Full installment
- Partial installment
- Previous due
- Multiple installments

The system should automatically calculate:

```text
Previous Due
+ Today's Installment
+ Late Amount/Fee
-------------------
Total Collection
```

Business rules for late fees should be configurable.

---

# 22. Account Number System

This is an important part of the design.

Every program should have a separate account number sequence.

Example:

```text
Daily Savings
DS-000001
DS-000002
DS-000003

Weekly Savings
WS-000001
WS-000002
WS-000003

Monthly Savings
MS-000001
MS-000002
MS-000003

Daily Loan
DL-000001
DL-000002

Weekly Loan
WL-000001
WL-000002

Monthly Loan
ML-000001
ML-000002
```

Do not generate account numbers using random IDs.

Use a dedicated numbering system/table so numbers remain unique and sequential.

---

# 23. Area + Field Officer Based Accounts

Every account should maintain:

```text
Member
   ↓
Area
   ↓
Field Officer
   ↓
Program
   ↓
Account
```

For example:

```text
Account: DS-000125

Member: Abdul Karim
Area: Halishahar
Field Officer: FO-005
Program: Daily Savings
```

This allows reports such as:

- Area-wise savings
- Officer-wise savings
- Officer-wise loan collection
- Area-wise loan outstanding
- Officer-wise overdue
- Member-wise transaction history

---

# 24. Cash Management

A cash management module should be included from the beginning.

### Field Officer Cash

Field Officer collects:

```text
Savings Deposit
+
Loan Installment
+
Other Receipts
```

At the end of the day:

```text
Total Collection
- Cash Submitted
= Cash in Hand
```

The officer should submit collected cash to the office/cashier.

---

# 25. Daily Cash Register

The system should maintain a daily cash register.

Example:

```text
Opening Cash
+ Savings Collection
+ Loan Collection
+ Other Income
- Loan Disbursement
- Member Withdrawal
- Expenses
- Cash Deposit to Bank
--------------------------------
Closing Cash
```

Cashier should be able to:

- Open cash register
- Record cash transactions
- Receive field officer cash
- Make payments
- Close daily register

Once closed, normal users should not be able to modify the register without authorization.

---

# 26. Field Officer Settlement

At the end of a collection session/day:

```text
Officer Collection

Savings Collection      ৳25,000
Loan Collection         ৳35,000
Other Collection         ৳2,000
--------------------------------
Total                   ৳62,000

Cash Submitted          ৳60,000
Remaining Cash           ৳2,000
```

The system should record the settlement.

---

# 27. Member Withdrawal

Savings accounts should support withdrawals where permitted.

Withdrawal process:

```text
Withdrawal Request
       ↓
Verification
       ↓
Approval
       ↓
Payment
       ↓
Transaction
       ↓
Receipt
```

For security, withdrawal approval should optionally require an authorized user.

---

# 28. Receipts

Every financial transaction should have a receipt number.

Examples:

```text
Receipt:
REC-2026-000001

Savings Deposit:
SD-2026-000001

Loan Collection:
LC-2026-000001

Withdrawal:
WD-2026-000001
```

Receipt should contain:

- Somiti name
- Address/contact
- Receipt number
- Date
- Member
- Account number
- Program
- Amount
- Payment method
- Field Officer
- Previous balance
- Transaction amount
- New balance
- Authorized signature

PDF/print support should be included.

---

# 29. Dashboard

Dashboard should be role-based.

### Admin Dashboard

Show:

- Total members
- Active members
- Total savings
- Today's savings collection
- Total outstanding loans
- Today's loan collection
- Today's disbursement
- Total overdue
- Today's expenses
- Cash balance

### Field Officer Dashboard

Show:

- Assigned members
- Today's expected collection
- Today's collection
- Savings collection
- Loan collection
- Total overdue
- Pending collection
- Cash in hand

---

# 30. Reports

Reports are one of the most important modules.

## Member Reports

- Member list
- New members
- Active/inactive members
- Member statement

## Savings Reports

- Daily savings collection
- Weekly savings collection
- Monthly savings collection
- Program-wise savings
- Area-wise savings
- Field Officer-wise savings
- Savings balance
- Deposit history
- Withdrawal report

## Loan Reports

- Loan applications
- Approved loans
- Disbursed loans
- Active loans
- Completed loans
- Outstanding loans
- Overdue loans
- Daily loan report
- Weekly loan report
- Monthly loan report
- Officer-wise loan report
- Area-wise loan report
- Collection efficiency

## Cash Reports

- Daily cash register
- Officer cash settlement
- Cash received
- Cash paid
- Cash balance
- Bank deposit
- Expenses

---

# 31. Member Statement

A member should have a complete financial statement.

Example:

```text
Member: Abdul Karim

Date       Account       Type          Debit    Credit   Balance
----------------------------------------------------------------
01 Aug     DS-001        Deposit       -        100      5,000
02 Aug     DS-001        Deposit       -        100      5,100
05 Aug     DL-001        Loan          50,000   -        50,000
06 Aug     DL-001        Repayment     -        1,000    49,000
```

The member profile should show all savings and loan accounts.

---

# 32. Accounting Approach

For version 1, keep accounting simple.

Maintain proper transaction records for:

- Savings deposits
- Savings withdrawals
- Loan disbursements
- Loan repayments
- Interest income
- Fees
- Expenses
- Cash
- Bank

The system should be designed so proper double-entry accounting can be added without redesigning the core modules.

---

# 33. Important Database Tables

A suggested initial database structure:

### Organization

```text
users
roles
permissions
settings
areas
field_officers
```

### Members

```text
members
member_nominees
member_documents
```

### Savings

```text
savings_programs
savings_accounts
savings_transactions
savings_withdrawals
```

### Loans

```text
loan_products
loan_applications
loan_guarantors
loans
loan_schedules
loan_transactions
loan_disbursements
loan_repayments
```

### Cash

```text
cash_registers
cash_transactions
field_officer_settlements
```

### Financial

```text
expenses
income
bank_accounts
bank_transactions
```

### System

```text
account_sequences
transaction_sequences
audit_logs
attachments
notifications
```

---

# 34. Important Relationships

### Member

```text
Member
 ├── belongsTo Area
 ├── belongsTo FieldOfficer
 ├── hasMany SavingsAccounts
 ├── hasMany Loans
 ├── hasMany Nominees
 └── hasMany Documents
```

### Savings Account

```text
SavingsAccount
 ├── belongsTo Member
 ├── belongsTo SavingsProgram
 ├── belongsTo Area
 ├── belongsTo FieldOfficer
 └── hasMany Transactions
```

### Loan

```text
Loan
 ├── belongsTo Member
 ├── belongsTo LoanProduct
 ├── belongsTo Area
 ├── belongsTo FieldOfficer
 ├── hasMany Schedules
 └── hasMany Transactions
```

---

# 35. Audit Log

Because this is a financial application, important actions should be logged.

Log:

- Who created a member
- Who changed member information
- Who opened an account
- Who approved a loan
- Who disbursed a loan
- Who collected money
- Who changed a transaction
- Who cancelled a transaction
- Who approved withdrawal
- Who closed cash register

A financial transaction should preferably **never be physically deleted**.

Use:

```text
Reversal
Correction
Cancellation
```

instead of deleting historical transactions.

---

# 36. Transaction Safety

Financial transactions need special protection.

When recording a transaction:

```text
Validate
   ↓
Database Transaction
   ↓
Create Financial Transaction
   ↓
Update Account Balance
   ↓
Create Receipt
   ↓
Commit
```

If anything fails:

```text
Rollback
```

This prevents situations where money is recorded but the account balance is not updated.

---

# 37. Bangladesh-Specific Considerations

The system should support:

- Bangladeshi Taka (৳)
- Bangladesh date format
- Bangla and English member names
- NID
- Mobile numbers
- bKash
- Nagad
- Bank accounts
- Local areas
- Field-based collection
- Printed receipts
- Member photos/documents
- Optional Bangla interface

The system should **not hard-code Bangladesh-specific regulatory assumptions** unless the actual cooperative's legal/accounting requirements are confirmed.

---

# 38. Security

Important security features:

- Login authentication
- Role-based permissions
- Policy-based authorization
- Password hashing
- CSRF protection
- Login throttling
- Session security
- Audit logging
- Transaction authorization
- Financial transaction protection
- Sensitive document access control

Field officers must not be able to access another officer's members unless explicitly permitted.

---

# 39. Recommended Laravel Module Structure

Keep the Laravel project straightforward.

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Member/
│   │   ├── Savings/
│   │   ├── Loan/
│   │   ├── Collection/
│   │   ├── Cash/
│   │   └── Reports/
│   │
│   └── Requests/
│
├── Models/
│
├── Services/
│   ├── Savings/
│   ├── Loan/
│   ├── Collection/
│   └── Cash/
│
├── Policies/
│
└── Support/
```

Do not over-engineer the application.

Use services only for complicated business operations such as:

```text
SavingsTransactionService
LoanDisbursementService
LoanRepaymentService
CashSettlementService
AccountNumberService
```

---

# 40. Main Navigation

The admin panel could have:

```text
Dashboard

Members
  ├── All Members
  ├── Add Member
  └── Member Statements

Areas
Field Officers

Savings
  ├── Programs
  ├── Accounts
  ├── Deposits
  ├── Withdrawals
  └── Collections

Loans
  ├── Loan Products
  ├── Applications
  ├── Approvals
  ├── Active Loans
  ├── Disbursements
  ├── Repayments
  └── Overdue

Cash
  ├── Cash Register
  ├── Officer Settlement
  ├── Cash Receive
  └── Cash Payment

Reports
  ├── Member
  ├── Savings
  ├── Loans
  ├── Collections
  ├── Field Officers
  ├── Areas
  └── Cash

Settings
  ├── General
  ├── Savings Programs
  ├── Loan Products
  ├── Account Numbering
  ├── Users
  └── Permissions

Audit Logs
```

# 41. Development Phases

## Phase 1 — Foundation

- Laravel setup
- Authentication
- Roles/permissions
- Settings
- Base layout
- Dashboard
- User management

## Phase 2 — Organization

- Areas
- Field Officers
- Member management
- Member documents
- Nominees

## Phase 3 — Savings

- Savings programs
- Account numbering
- Savings accounts
- Deposits
- Withdrawals
- Savings transactions
- Receipts
- Member statements

## Phase 4 — Loans

- Loan products
- Loan applications
- Approval
- Loan accounts
- Disbursement
- Repayment schedule
- Installment collection
- Overdue management

## Phase 5 — Field Collection

- Daily collection sheet
- Officer collection
- Officer settlement
- Area-wise collection
- Expected vs actual collection

## Phase 6 — Cash

- Cash register
- Opening balance
- Cash receive
- Cash payment
- Field Officer cash submission
- Daily closing

## Phase 7 — Reports

- Member reports
- Savings reports
- Loan reports
- Collection reports
- Officer reports
- Area reports
- Cash reports
- Export to PDF/Excel

## Phase 8 — Security & Audit

- Audit logs
- Transaction reversal
- Authorization improvements
- Financial transaction locking
- Security review

# 42. Core Design Principle

The most important architectural decision should be:

```text
PROGRAM
   ↓
ACCOUNT
   ↓
TRANSACTION
```

For savings:

```text
Savings Program
   ↓
Savings Account
   ↓
Savings Transactions
```

For loans:

```text
Loan Product
   ↓
Loan Account
   ↓
Loan Schedule
   ↓
Loan Transactions
```

And every account should retain:

```text
Member
Area
Field Officer
Program/Product
Account Number
```

This structure will make the system easy to operate for a Bangladesh-based Somiti while leaving room for future additions such as DPS, fixed deposits, multiple branches, mobile apps, SMS notifications, bKash/Nagad integration, biometric/member verification, and more advanced accounting.
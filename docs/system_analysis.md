# GSO Borrowing System Documentation

## 1. System Analysis

### 1.1 Overview

The General Services Office Borrowing System is a PHP and MySQL web application used to manage the borrowing, approval, release, return, maintenance, and reporting of university facilities, equipment, and related resources. The system supports two primary user roles:

- `Admin`: manages accounts, inventory, approvals, releases, return reviews, maintenance schedules, reports, notifications, and activity logs.
- `Borrower`: browses resources, submits requests, tracks borrowing status, and submits return proof.

The application currently runs on:

- `PHP 8.2.12`
- `MariaDB 10.4.32`
- `PDO` for database access
- `PHPMailer` for account verification and password reset mail delivery

### 1.2 Functional Scope

The inspected codebase supports the following major functions:

1. User registration, email verification, login, logout, and password reset.
2. Admin review of borrower accounts and account status control.
3. Resource inventory management for items and facilities.
4. Borrower request submission with schedule and availability validation.
5. Admin approval, rejection, and release of borrowing requests.
6. Borrower return submission with photos and condition reporting.
7. Admin return inspection and inventory condition updates.
8. Maintenance scheduling that blocks borrowing during maintenance windows.
9. Notifications and activity logging for important system actions.
10. Reporting and dashboard summaries for administrative monitoring.

### 1.3 Key Processes

#### Login Process

- The user enters an email address or username and password.
- The system checks whether the account exists.
- If no account is found, the system returns `Invalid username or password`.
- If the account exists but the password is incorrect, the system returns `Incorrect password`.
- The system validates email verification, account status, and role-based access before creating a session.
- Successful login redirects the user to the borrower or admin area.

#### Borrowing Process

- The borrower selects a resource and submits the request schedule and details.
- The system validates resource status, stock or facility schedule availability, duplicate requests, and maintenance conflicts.
- A valid request is stored as `Pending`.
- Admin reviews the request and may approve, reject, or release it.
- On release, stock is updated for item resources and the due date is generated.

#### Return Process

- The borrower submits return notes, a reported condition, and up to three proof photos.
- The system stores the return submission as `Pending`.
- Admin reviews the submitted proof and records the inspection result.
- If approved, the request status becomes `Returned` and the resource inventory or condition is updated.
- If rejected, the borrower is asked to resubmit return proof.

## 2. Data Flow Diagram (DFD)

### 2.1 Context Diagram (Level 0)

```mermaid
flowchart LR
    Borrower[Borrower]
    Admin[Admin]
    Applicant[Guest / Applicant]
    Email[Email Service]

    System((0.0 GSO Borrowing System))

    Applicant -->|Signup details, uploaded ID, login input, verification token| System
    System -->|Signup result, verification result, access status| Applicant

    Borrower -->|Login credentials, request details, return proof, profile updates| System
    System -->|Request status, borrowed records, notifications, return results| Borrower

    Admin -->|Account decisions, resource updates, approval or release actions, maintenance schedules, report filters| System
    System -->|Dashboards, request records, inventory data, reports, notifications, logs| Admin

    System -->|Verification email, reset email| Email
    Email -->|Delivery result| System
```

### 2.2 Level 1 DFD

```mermaid
flowchart TD
    Borrower[Borrower]
    Admin[Admin]
    Email[Email Service]

    P1[1.0 Authentication and Accounts]
    P2[2.0 Resources and Inventory]
    P3[3.0 Borrowing Requests]
    P4[4.0 Release and Returns]
    P5[5.0 Maintenance Management]
    P6[6.0 Notifications and Activity Logs]
    P7[7.0 Reports and Monitoring]

    D1[(D1 Users)]
    D2[(D2 Resources)]
    D3[(D3 Resource Requests)]
    D4[(D4 Return Submissions)]
    D5[(D5 Return Submission Photos)]
    D6[(D6 Maintenance Schedules)]
    D7[(D7 Notifications)]
    D8[(D8 Activity Logs)]

    Borrower -->|Credentials, profile updates| P1
    Admin -->|Account review actions| P1
    P1 <--> D1
    P1 --> D8
    P1 -->|Verification or reset mail| Email

    Admin -->|Add, edit, archive resources| P2
    P2 <--> D2
    P2 --> D8

    Borrower -->|Borrow request details| P3
    P3 <--> D2
    P3 <--> D3
    P3 <--> D6
    P3 --> D7
    P3 --> D8

    Admin -->|Approve, reject, release| P4
    Borrower -->|Return notes and photos| P4
    P4 <--> D2
    P4 <--> D3
    P4 <--> D4
    P4 <--> D5
    P4 --> D7
    P4 --> D8

    Admin -->|Maintenance schedules| P5
    P5 <--> D2
    P5 <--> D6
    P5 --> D8

    Borrower -->|Read notifications| P6
    Admin -->|Read notifications and logs| P6
    P6 <--> D7
    P6 <--> D8

    Admin -->|Report filters and export request| P7
    P7 <--> D2
    P7 <--> D3
    P7 <--> D4
    P7 <--> D6
    P7 <--> D8
```

## 3. Flowcharts

### 3.1 Login Process Flowchart

```mermaid
flowchart TD
    A([Start]) --> B[Enter username or email and password]
    B --> C{Credentials submitted?}
    C -->|No| B
    C -->|Yes| D[Find user record]
    D --> E{User exists?}
    E -->|No| F[Show Invalid username or password]
    E -->|Yes| G{Password correct?}
    G -->|No| H[Show Incorrect password]
    H --> I{3 failed attempts reached?}
    I -->|No| B
    I -->|Yes| J[Start 30-second countdown]
    J --> K[Auto reload login page]
    K --> L[Allow only 1 retry]
    L --> M{Retry successful?}
    M -->|No| N[Redirect to Forgot Password]
    M -->|Yes| O[Continue validation]
    G -->|Yes| O[Continue validation]
    O --> P{Email verified?}
    P -->|No| Q[Show verify email message]
    P -->|Yes| R{Account status valid?}
    R -->|No| S[Show status warning]
    R -->|Yes| T{Role = Admin or Borrower?}
    T -->|Borrower| U[Redirect to borrower workspace]
    T -->|Admin| V[Redirect to admin dashboard]
    U --> W([End])
    V --> W
    F --> W
    Q --> W
    S --> W
    N --> W
```

### 3.2 Borrowing Process Flowchart

```mermaid
flowchart TD
    A([Start]) --> B[Borrower selects resource]
    B --> C[Enter quantity, contact number, date, time, and notes]
    C --> D[Validate CSRF token and form inputs]
    D --> E{Schedule valid?}
    E -->|No| F[Display validation error]
    E -->|Yes| G[Check duplicate request]
    G --> H{Duplicate exists?}
    H -->|Yes| I[Display duplicate warning]
    H -->|No| J[Check availability, stock, maintenance, and schedule conflicts]
    J --> K{Resource available?}
    K -->|No| L[Display availability error]
    K -->|Yes| M[Insert Pending request]
    M --> N[Create admin notifications]
    N --> O[Write activity log]
    O --> P[Display request submitted message]
    P --> Q([End])
    F --> Q
    I --> Q
    L --> Q
```

### 3.3 Return Process Flowchart

```mermaid
flowchart TD
    A([Start]) --> B[Borrower opens released request]
    B --> C[Enter condition notes and upload proof photos]
    C --> D[Validate token, photo count, file type, and file size]
    D --> E{Submission valid?}
    E -->|No| F[Display return submission error]
    E -->|Yes| G[Create or update return submission]
    G --> H[Store return photos]
    H --> I[Notify admins]
    I --> J[Admin reviews return proof]
    J --> K{Approve return?}
    K -->|No| L[Mark return submission Rejected and request resubmission]
    K -->|Yes| M[Record inspection result]
    M --> N[Update request status to Returned]
    N --> O[Update stock or condition status]
    O --> P[Notify borrower and log activity]
    P --> Q([End])
    F --> Q
    L --> Q
```

## 4. Entity Relationship Diagram (ERD)

Note: The following ERD reflects the application’s logical relationships used in code. In the inspected schema, `return_submissions.request_id -> resource_requests.request_id` is the only enforced database foreign key; the remaining relationships are application-managed logical links.

```mermaid
erDiagram
    USERS ||--o{ RESOURCE_REQUESTS : submits
    USERS ||--o{ RESOURCE_REQUESTS : approves
    USERS ||--o{ RETURN_SUBMISSIONS : owns
    USERS ||--o{ RETURN_SUBMISSIONS : reviews
    USERS ||--o{ MAINTENANCE_SCHEDULES : creates
    USERS ||--o{ ACTIVITY_LOGS : records
    USERS ||--o{ NOTIFICATIONS : receives

    RESOURCES ||--o{ RESOURCE_REQUESTS : requested_for
    RESOURCES ||--o{ MAINTENANCE_SCHEDULES : scheduled_for

    RESOURCE_REQUESTS ||--o| RETURN_SUBMISSIONS : produces
    RETURN_SUBMISSIONS ||--o{ RETURN_SUBMISSION_PHOTOS : contains

    USERS {
        int user_id PK
        varchar full_name
        varchar email
        enum role
        varchar username
        enum account_status
    }

    RESOURCES {
        int resource_id PK
        varchar resource_name
        enum resource_type
        varchar category
        enum status
        enum condition_status
    }

    RESOURCE_REQUESTS {
        int request_id PK
        int borrower_id FK
        int resource_id FK
        int approved_by FK
        datetime request_date
        enum status
        datetime due_date
        datetime return_date
    }

    RETURN_SUBMISSIONS {
        int return_id PK
        int request_id FK
        int borrower_id FK
        int admin_id FK
        enum status
        enum inspection_condition
    }

    RETURN_SUBMISSION_PHOTOS {
        int photo_id PK
        int return_id FK
        varchar filename
        varchar mime_type
    }

    MAINTENANCE_SCHEDULES {
        int maintenance_id PK
        int resource_id FK
        int created_by FK
        date start_date
        date end_date
        enum status
    }

    ACTIVITY_LOGS {
        int log_id PK
        int user_id FK
        varchar action
        datetime log_date
    }

    NOTIFICATIONS {
        int notification_id PK
        int user_id FK
        varchar type
        varchar title
        tinyint is_read
    }
```

## 5. UML Diagrams

### 5.1 Use Case Diagram

```mermaid
flowchart LR
    Admin[Admin]
    Borrower[Borrower]

    Login([Login])
    RequestResource([Request Resource])
    ApproveRequest([Approve Request])
    ReturnResource([Return Resource])
    ViewHistory([View History])

    Admin --> Login
    Admin --> ApproveRequest
    Admin --> ViewHistory

    Borrower --> Login
    Borrower --> RequestResource
    Borrower --> ReturnResource
    Borrower --> ViewHistory
```

### 5.2 Activity Diagram: Login

```mermaid
flowchart TD
    A([Open login page]) --> B[Enter username or email and password]
    B --> C[Submit form]
    C --> D[Validate token and input]
    D --> E{User exists?}
    E -->|No| F[Show invalid username or password]
    E -->|Yes| G{Password correct?}
    G -->|No| H[Show incorrect password]
    H --> I{Lock threshold reached?}
    I -->|Yes| J[Lock login for 30 seconds]
    I -->|No| B
    J --> B
    G -->|Yes| K{Email verified and account allowed?}
    K -->|No| L[Show status message]
    K -->|Yes| M[Create session]
    M --> N[Redirect by role]
```

### 5.3 Activity Diagram: Borrowing Workflow

```mermaid
flowchart TD
    A([Browse resources]) --> B[Select resource]
    B --> C[Fill request details]
    C --> D[Submit request]
    D --> E[Validate schedule and availability]
    E --> F{Valid request?}
    F -->|No| G[Display error]
    F -->|Yes| H[Save Pending request]
    H --> I[Notify admin]
    I --> J[Admin reviews request]
    J --> K{Approve or Reject?}
    K -->|Reject| L[Mark Rejected]
    K -->|Approve| M[Mark Approved]
    M --> N[Admin releases request]
    N --> O[Generate due date and update stock]
    O --> P[Request becomes Released]
```

### 5.4 Activity Diagram: Return Workflow

```mermaid
flowchart TD
    A([Borrower opens released request]) --> B[Submit return notes and photos]
    B --> C[Validate submission]
    C --> D{Valid?}
    D -->|No| E[Show submission error]
    D -->|Yes| F[Store return submission]
    F --> G[Notify admin]
    G --> H[Admin reviews evidence]
    H --> I{Approve?}
    I -->|No| J[Reject and request resubmission]
    I -->|Yes| K[Set Returned status]
    K --> L[Update inventory and condition]
    L --> M[Notify borrower]
```

### 5.5 Sequence Diagram: Login Process

```mermaid
sequenceDiagram
    actor User
    participant UI as Login Page
    participant Auth as auth/login.php
    participant DB as MySQL Users Table
    participant Session as PHP Session

    User->>UI: Enter username or email and password
    UI->>Auth: POST credentials
    Auth->>Session: Validate CSRF and login guard
    Auth->>DB: Query matching user
    DB-->>Auth: User row or null
    alt user not found
        Auth-->>UI: Invalid username or password
    else wrong password
        Auth-->>UI: Incorrect password
    else verified and allowed
        Auth->>Session: Create authenticated session
        Auth-->>UI: Redirect to admin or borrower area
    end
```

### 5.6 Sequence Diagram: Borrow Request Process

```mermaid
sequenceDiagram
    actor Borrower
    participant Form as Request Form
    participant RequestPHP as borrower/request_resource.php
    participant Helper as request_helper.php
    participant DB as MySQL
    participant Notify as notification_helper.php

    Borrower->>Form: Fill request details
    Form->>RequestPHP: Submit request
    RequestPHP->>Helper: Validate schedule and availability
    Helper->>DB: Check resources, requests, maintenance
    DB-->>Helper: Validation data
    alt request invalid
        Helper-->>RequestPHP: Validation error
        RequestPHP-->>Form: Show error
    else request valid
        RequestPHP->>DB: Insert Pending request
        RequestPHP->>Notify: Create admin notifications
        RequestPHP-->>Form: Show success message
    end
```

### 5.7 Class Diagram

```mermaid
classDiagram
    class Users {
        +int user_id
        +string full_name
        +string department
        +string university_id
        +string email
        +blob uploaded_id
        +string uploaded_id_type
        +blob profile_image
        +string profile_image_type
        +enum role
        +string username
        +string password_hash
        +enum account_status
        +bool email_verified
    }

    class Resources {
        +int resource_id
        +string resource_name
        +enum resource_type
        +string category
        +string description
        +string resource_image
        +string location
        +int total_stock
        +int available_stock
        +int capacity
        +enum status
        +enum condition_status
    }

    class ResourceRequests {
        +int request_id
        +int borrower_id
        +int resource_id
        +int quantity
        +string contact_number
        +date date_needed
        +time start_time
        +time end_time
        +datetime request_date
        +enum status
        +datetime due_date
        +datetime return_date
    }

    class ReturnSubmissions {
        +int return_id
        +int request_id
        +int borrower_id
        +enum reported_condition
        +enum status
        +int admin_id
        +enum inspection_condition
        +datetime submitted_at
        +datetime reviewed_at
    }

    class ReturnSubmissionPhotos {
        +int photo_id
        +int return_id
        +string filename
        +string mime_type
        +datetime created_at
    }

    class MaintenanceSchedules {
        +int maintenance_id
        +int resource_id
        +date start_date
        +date end_date
        +int duration_days
        +string reason
        +enum status
    }

    class ActivityLogs {
        +int log_id
        +int user_id
        +string action
        +string details
        +datetime log_date
    }

    class Notifications {
        +int notification_id
        +int user_id
        +string type
        +string title
        +string message
        +bool is_read
        +datetime created_at
    }

    Users "1" --> "many" ResourceRequests : borrower_id
    Users "1" --> "many" ResourceRequests : approved_by
    Users "1" --> "many" ReturnSubmissions : borrower_id/admin_id
    Users "1" --> "many" MaintenanceSchedules : created_by
    Users "1" --> "many" ActivityLogs : user_id
    Users "1" --> "many" Notifications : user_id
    Resources "1" --> "many" ResourceRequests : resource_id
    Resources "1" --> "many" MaintenanceSchedules : resource_id
    ResourceRequests "1" --> "0..1" ReturnSubmissions : request_id
    ReturnSubmissions "1" --> "many" ReturnSubmissionPhotos : return_id
```

## 6. Data Dictionary

### 6.1 `users`

| Field Name | Data Type | Description | Constraints |
| --- | --- | --- | --- |
| `user_id` | `int(11)` | Unique account identifier. | `PK`, `AUTO_INCREMENT`, `NOT NULL` |
| `full_name` | `varchar(100)` | User’s complete name. | `NOT NULL` |
| `department` | `varchar(100)` | Department or college affiliation. | `NULL` |
| `university_id` | `varchar(30)` | Institutional ID number. | `UNIQUE`, `NULL` |
| `email` | `varchar(100)` | Primary email used for login and notifications. | `UNIQUE`, `NOT NULL` |
| `uploaded_id` | `longblob` | Stored uploaded ID document used during registration. | `NULL` |
| `uploaded_id_type` | `varchar(100)` | MIME type of the uploaded ID file. | `NULL` |
| `profile_image` | `longblob` | Stored profile image for the user account. | `NULL` |
| `profile_image_type` | `varchar(100)` | MIME type of the stored profile image. | `NULL` |
| `role` | `enum('Admin','Borrower')` | Role that determines system access and redirection. | `NOT NULL`, default `Borrower` |
| `username` | `varchar(50)` | Username alternative for login. | `UNIQUE`, `NOT NULL` |
| `password_hash` | `varchar(255)` | Hashed account password. | `NOT NULL` |
| `reset_token_hash` | `varchar(255)` | Hashed password reset token. | `NULL` |
| `reset_token_expires_at` | `datetime` | Expiration date and time of the reset token. | `NULL` |
| `account_status` | `enum('Pending','Approved','Rejected','Disabled')` | Administrative account status. | `NOT NULL`, default `Pending` |
| `approved_by` | `int(11)` | Admin who approved or changed the account status. | `FK (logical) -> users.user_id`, `NULL` |
| `approved_at` | `datetime` | Date and time the account was approved. | `NULL` |
| `created_at` | `datetime` | Account creation timestamp. | `NOT NULL`, default `CURRENT_TIMESTAMP` |
| `password_updated_at` | `datetime` | Last password update timestamp. | `NULL` |
| `email_verified` | `tinyint(1)` | Indicates whether the email has been verified. | default `0`, `NULL allowed in schema` |
| `verification_token` | `varchar(255)` | Email verification token. | `NULL` |

### 6.2 `resources`

| Field Name | Data Type | Description | Constraints |
| --- | --- | --- | --- |
| `resource_id` | `int(11)` | Unique identifier of the resource. | `PK`, `AUTO_INCREMENT`, `NOT NULL` |
| `resource_name` | `varchar(100)` | Name of the resource or facility. | `NOT NULL` |
| `resource_type` | `enum('Item','Facility')` | Distinguishes item borrowing from facility reservation. | `NOT NULL` |
| `category` | `varchar(50)` | Resource classification or grouping. | `NULL` |
| `description` | `varchar(255)` | Short description of the resource. | `NULL` |
| `resource_image` | `varchar(255)` | Stored filename of the resource image. | `NULL` |
| `location` | `varchar(100)` | Physical location of the resource. | `NULL` |
| `total_stock` | `int(11)` | Total stock count for item resources. | `NULL` |
| `available_stock` | `int(11)` | Currently available stock count. | `NULL` |
| `capacity` | `int(11)` | Capacity for facility resources. | `NULL` |
| `status` | `enum('Available','Unavailable','Maintenance')` | Operational availability of the resource. | `NOT NULL`, default `Available` |
| `condition_status` | `enum('Good','Damaged','Missing Parts','Needs Repair','Lost')` | Current physical condition status. | `NOT NULL`, default `Good` |
| `condition_notes` | `varchar(500)` | Notes about condition, inspection, or damage. | `NULL` |
| `is_archived` | `tinyint(1)` | Indicates whether the resource is archived. | `NOT NULL`, default `0` |
| `created_at` | `datetime` | Resource creation timestamp. | `NOT NULL`, default `CURRENT_TIMESTAMP` |

### 6.3 `resource_requests`

| Field Name | Data Type | Description | Constraints |
| --- | --- | --- | --- |
| `request_id` | `int(11)` | Unique identifier of the borrowing request. | `PK`, `AUTO_INCREMENT`, `NOT NULL` |
| `borrower_id` | `int(11)` | Borrower who submitted the request. | `NOT NULL`, `FK (logical) -> users.user_id` |
| `resource_id` | `int(11)` | Resource being requested. | `NOT NULL`, `FK (logical) -> resources.resource_id` |
| `quantity` | `int(11)` | Requested quantity for item borrowing. | `NULL` |
| `contact_number` | `varchar(30)` | Contact number used for urgent coordination or reminders. | `NULL` |
| `date_needed` | `date` | Requested borrowing or reservation date. | `NULL` |
| `start_time` | `time` | Requested start time. | `NULL` |
| `end_time` | `time` | Requested end time. | `NULL` |
| `request_date` | `datetime` | Date and time when the request was created. | `NOT NULL`, default `CURRENT_TIMESTAMP` |
| `status` | `enum('Pending','Under Review','Approved','Rejected','Cancelled','Released','Returned')` | Request lifecycle status. | `NOT NULL`, default `Pending` |
| `approved_by` | `int(11)` | Admin who approved or released the request. | `NULL`, `FK (logical) -> users.user_id` |
| `reviewed_by` | `int(11)` | Reviewer reference reserved for additional review tracking. | `NULL`, `FK (logical) -> users.user_id` |
| `approved_at` | `datetime` | Approval or release timestamp. | `NULL` |
| `reviewed_at` | `datetime` | Review timestamp. | `NULL` |
| `due_date` | `datetime` | Computed due date after release. | `NULL` |
| `return_date` | `datetime` | Final recorded return date. | `NULL` |
| `notes` | `varchar(255)` | Borrower’s request notes. | `NULL` |
| `last_reminded_at` | `datetime` | Timestamp of the last overdue reminder. | `NULL` |
| `reminder_count` | `int(11)` | Number of reminders sent for this request. | `NOT NULL`, default `0` |

### 6.4 `return_submissions`

| Field Name | Data Type | Description | Constraints |
| --- | --- | --- | --- |
| `return_id` | `int(11)` | Unique identifier of the return submission. | `PK`, `AUTO_INCREMENT`, `NOT NULL` |
| `request_id` | `int(11)` | Borrowing request linked to the return submission. | `UNIQUE`, `NOT NULL`, `FK -> resource_requests.request_id` |
| `borrower_id` | `int(11)` | Borrower who submitted the return proof. | `NOT NULL`, `FK (logical) -> users.user_id` |
| `condition_notes` | `varchar(500)` | Borrower notes regarding returned condition. | `NULL` |
| `reported_condition` | `enum('Good','Damaged','Missing Parts','Needs Repair','Lost')` | Borrower-declared return condition. | `NULL` |
| `status` | `enum('Pending','Approved','Rejected')` | Return review status. | `NOT NULL`, default `Pending` |
| `admin_id` | `int(11)` | Admin who reviewed the return submission. | `NULL`, `FK (logical) -> users.user_id` |
| `admin_notes` | `varchar(500)` | Admin response or resubmission note. | `NULL` |
| `inspection_condition` | `enum('Good','Damaged','Missing Parts','Needs Repair','Lost')` | Final admin inspection condition. | `NULL` |
| `inspection_remarks` | `varchar(500)` | Additional admin inspection remarks. | `NULL` |
| `submitted_at` | `datetime` | Submission timestamp. | `NOT NULL`, default `CURRENT_TIMESTAMP` |
| `reviewed_at` | `datetime` | Review timestamp. | `NULL` |

### 6.5 `return_submission_photos`

| Field Name | Data Type | Description | Constraints |
| --- | --- | --- | --- |
| `photo_id` | `int(11)` | Unique identifier of the stored return photo. | `PK`, `AUTO_INCREMENT`, `NOT NULL` |
| `return_id` | `int(11)` | Related return submission. | `NOT NULL`, `FK (logical) -> return_submissions.return_id` |
| `filename` | `varchar(255)` | Stored filename of the uploaded return photo. | `NOT NULL` |
| `mime_type` | `varchar(100)` | MIME type of the uploaded photo. | `NOT NULL` |
| `created_at` | `datetime` | Timestamp when the photo record was created. | `NOT NULL`, default `CURRENT_TIMESTAMP` |

### 6.6 `maintenance_schedules`

| Field Name | Data Type | Description | Constraints |
| --- | --- | --- | --- |
| `maintenance_id` | `int(11)` | Unique identifier of the maintenance schedule. | `PK`, `AUTO_INCREMENT`, `NOT NULL` |
| `resource_id` | `int(11)` | Resource scheduled for maintenance. | `NOT NULL`, `FK (logical) -> resources.resource_id` |
| `start_date` | `date` | Maintenance start date. | `NOT NULL` |
| `end_date` | `date` | Maintenance end date. | `NOT NULL` |
| `duration_days` | `int(11)` | Computed maintenance duration in days. | `NOT NULL`, default `1` |
| `reason` | `varchar(255)` | Reason for maintenance. | `NOT NULL` |
| `remarks` | `varchar(500)` | Additional maintenance details. | `NULL` |
| `status` | `enum('Scheduled','In Progress','Completed','Cancelled')` | Current maintenance state. | `NOT NULL`, default `Scheduled` |
| `created_by` | `int(11)` | Admin who created the maintenance record. | `NOT NULL`, `FK (logical) -> users.user_id` |
| `updated_by` | `int(11)` | Admin who last updated the maintenance record. | `NULL`, `FK (logical) -> users.user_id` |
| `created_at` | `datetime` | Creation timestamp. | `NOT NULL`, default `CURRENT_TIMESTAMP` |
| `updated_at` | `datetime` | Last update timestamp. | `NULL` |

### 6.7 `activity_logs`

| Field Name | Data Type | Description | Constraints |
| --- | --- | --- | --- |
| `log_id` | `int(11)` | Unique identifier of the activity record. | `PK`, `AUTO_INCREMENT`, `NOT NULL` |
| `user_id` | `int(11)` | User associated with the logged activity. | `NULL`, `FK (logical) -> users.user_id` |
| `action` | `varchar(100)` | Short action label. | `NOT NULL` |
| `details` | `varchar(255)` | Additional details describing the activity. | `NULL` |
| `log_date` | `datetime` | Timestamp of the logged event. | `NOT NULL`, default `CURRENT_TIMESTAMP` |

### 6.8 `notifications`

| Field Name | Data Type | Description | Constraints |
| --- | --- | --- | --- |
| `notification_id` | `int(11)` | Unique identifier of the notification. | `PK`, `AUTO_INCREMENT`, `NOT NULL` |
| `user_id` | `int(11)` | Recipient user of the notification. | `NOT NULL`, `FK (logical) -> users.user_id` |
| `type` | `varchar(50)` | Notification category or event type. | `NOT NULL` |
| `title` | `varchar(120)` | Notification title shown in the UI. | `NOT NULL` |
| `message` | `varchar(255)` | Notification body text. | `NOT NULL` |
| `link` | `varchar(255)` | Optional destination link related to the notification. | `NULL` |
| `is_read` | `tinyint(1)` | Indicates whether the user has read the notification. | `NOT NULL`, default `0` |
| `created_at` | `datetime` | Timestamp when the notification was created. | `NOT NULL`, default `CURRENT_TIMESTAMP` |

## 7. Upload and Configuration Findings

The current environment checked during analysis reported:

- `upload_max_filesize = 40M`
- `post_max_size = 40M`
- `memory_limit = 512M`
- `max_allowed_packet = 134217728` bytes (`128 MB`)

Practical interpretation:

1. The server and database configuration are not the primary bottleneck for the reported `>2MB` image issue in this workspace.
2. The more likely bottleneck was application-level validation and BLOB write handling.
3. Profile image uploads were updated to use shared validation and `PDO::PARAM_LOB`, and the application limit was aligned to `5 MB` for profile images.

## 8. Summary

The GSO Borrowing System already contains a complete end-to-end operational workflow for account management, borrowing, release, returns, maintenance, notifications, and reporting. The documentation above translates the inspected implementation into analysis artifacts that can be used for thesis documentation, technical reports, or system presentation materials.

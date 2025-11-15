# Email Management Plugin for Question2Answer
Advanced Email Notification Control for Q2A - Developed using chatgpt

## Overview
The **Email Management Plugin** allows Q2A site administrators and users to fully control which email notifications are delivered.

It includes:
- Admin-controlled email events  
- User-level email event preferences  
- Forced (non-unsubscribe-able) notifications  
- Reset-to-default event list  

## Features

### Admin Features
- Add / Edit / Delete email event types  
- Set user-readable label, email subject, and forced flag  
- Reset all events to default values  
- Dynamic “Add Event” system  


### User Features
- New “Email Preferences” section on the Account page  
- Users can enable/disable notifications individually  
- Mandatory notifications shown separately  
- Select All / Deselect All  
- Automatic defaults for new users  


3. Table `qa_email_events` will be auto-created with default rows.

## 🗂 Database Structure

**Table:** `qa_email_events`

| Column       | Description                         |
|--------------|-------------------------------------|
| eventid      | Auto increment primary key          |
| user_title   | Label shown to users                |
| subject_key  | Email subject lang key              |
| forced       | 1 = cannot unsubscribe              |
| created      | Timestamp                           |

**User preferences:** stored in `qa_usermeta.emailprefs` (CSV).

## Email Sending Logic

The plugin overrides `qa_send_notification()`:

1. Check email subject  
2. If forced/not managed → send
3. Else load user preferences  
4. If user allowed → send  
5. Otherwise → skip email  


## ✏ Custom Events

Add custom plugin notifications by defining:
```
["User readable label", "your_email_subject", 0]
```

## © License
Free to use and modify.

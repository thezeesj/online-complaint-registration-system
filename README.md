# Complaint System Setup Guide

## Database Setup

### Option 1: Automatic Setup (Recommended)
1. Place the entire `complaint-system` folder in `C:\xampp\htdocs\`
2. Start XAMPP (Apache + MySQL)
3. Navigate to: `http://localhost/complaint-system/setup.php`
4. The database will be automatically created with test data

### Option 2: Manual Setup via phpMyAdmin
1. Open `http://localhost/phpmyadmin`
2. Copy and paste the contents of `database_setup.sql`
3. Execute the SQL

### Option 3: Command Line
```bash
mysql -u root -p < database_setup.sql
```

## Test Accounts

After setup, you can login with:

**Student Account:**
- Email: `student@example.com`
- Password: `password`

**Counselor Account:**
- Email: `counselor@example.com`
- Password: `password`

## Database Schema

### Users Table
- `id` - Primary Key
- `name` - User's full name
- `email` - Unique email (used for login)
- `password` - Hashed password
- `role` - 'student' or 'counselor'
- `created_at` - Registration timestamp

### Complaints Table
- `id` - Primary Key
- `user_id` - Reference to users (student who filed)
- `category` - Complaint category
- `subject` - Complaint subject
- `description` - Full complaint text
- `status` - Current status (Submitted, Under Review, Resolved, etc.)
- `proof` - Optional file attachment
- `counselor_viewed` - Flag: Has counselor viewed? (0/1)
- `user_unread` - Flag: Has student seen the latest update? (0/1)
- `created_at` - Submission timestamp

### Complaint Chat Table
- `id` - Primary Key
- `complaint_id` - Reference to complaints
- `sender_id` - Reference to users (who sent message)
- `sender_role` - 'student' or 'counselor'
- `message` - Chat message text
- `is_read` - Message read status (0/1)
- `created_at` - Message timestamp

### Feedback Table
- `id` - Primary Key
- `user_id` - Reference to users (who gave feedback)
- `complaint_id` - Reference to complaints
- `rating` - Numerical rating
- `comment` - Text feedback
- `created_at` - Feedback timestamp

## Features

### For Students/Faculty
- Submit complaints by category
- View complaint status with badges
- Chat with counselor
- See update notifications
- Provide feedback

### For Counselors
- View new complaints with badge count
- Access complaint details
- Chat with students
- Update complaint status
- Track unread messages

## File Structure

```
complaint-system/
├── config/
│   └── db.php              # Database connection
├── auth/
│   ├── login.php           # Login page
│   └── signup.php          # Registration page
├── student/
│   ├── home.php            # Student dashboard
│   ├── complaint_status.php # View complaint status
│   ├── chat.php            # Chat interface
│   ├── feedback.php        # Feedback submission
│   └── *.php               # Other student functions
├── counselor/
│   ├── dashboard.php       # Counselor main dashboard
│   ├── view.php            # View complaint details
│   ├── chat.php            # Chat with students
│   ├── logout.php          # Session logout
│   └── *.php               # Other counselor functions
├── assets/
│   ├── css/style.css       # Stylesheet
│   └── js/script.js        # JavaScript functions
├── setup.php               # AUTO SETUP SCRIPT
└── database_setup.sql      # SQL schema file
```

## Important Notes

1. **Email-based Login**: The system uses email addresses for login (no admission numbers)
2. **Notification Badges**: 
   - Counselor dashboard shows count of new complaints
   - Student complaint status shows count of status updates
3. **Chat System**: Uses `is_read` flag to track unread messages
4. **Status Tracking**: Uses `user_unread` flag to notify students of status changes

## Troubleshooting

### Database connection error
- Ensure MySQL is running in XAMPP
- Check db.php has correct credentials
- Verify database "complaint_system" exists

### Login/Signup not working
- Clear browser cookies/cache
- Ensure email format is valid
- Check user exists in database (phpmyadmin)

### Badges not showing
- Check complaint status and chat counts in database
- Verify `counselor_viewed`, `user_unread`, and `is_read` fields exist

## Next Steps

1. Run `setup.php` to initialize your database
2. Login with test accounts
3. Test basic functionality
4. Customize styling in `assets/css/style.css`
5. Modify categories in `student/home.php` if needed

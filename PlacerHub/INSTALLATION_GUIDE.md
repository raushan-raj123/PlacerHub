# PlacerHub Installation Guide

## 🚀 Quick Start Installation

### Prerequisites
- XAMPP (Apache + MySQL + PHP 7.4+)
- Web browser (Chrome, Firefox, Safari, Edge)
- At least 100MB free disk space

### Step 1: Download XAMPP
1. Download XAMPP from: https://www.apachefriends.org/
2. Install XAMPP with default settings
3. Start Apache and MySQL services from XAMPP Control Panel

### Step 2: Setup PlacerHub
1. Extract/Copy the PlacerHub folder to: `C:\xampp\htdocs\`
2. Open your web browser
3. Navigate to: `http://localhost/PlacerHub/setup/install.php`
4. The installation will automatically create the database and tables

### Step 3: Login
**Default Admin Credentials:**
- URL: `http://localhost/PlacerHub/auth/login.php`
- Email: `admin@placerhub.com`
- Password: `password`

**⚠️ IMPORTANT: Change the default password immediately after first login!**

### Step 4: Start Using PlacerHub
1. **Admin Panel**: Manage students, companies, and placement drives
2. **Student Registration**: Students can register and wait for approval
3. **Company Management**: Add partner companies
4. **Drive Creation**: Create placement drives and notify students
5. **Application Tracking**: Monitor and update application statuses

## 📁 Project Structure Overview

```
PlacerHub/
├── 📁 admin/              # Admin panel (TPO interface)
├── 📁 auth/               # Login/Register/Logout
├── 📁 config/             # Database & configuration
├── 📁 dashboard/          # Student dashboard
├── 📁 database/           # Database schema
├── 📁 includes/           # Utility classes
├── 📁 setup/              # Installation script
├── 📁 uploads/            # File uploads (auto-created)
├── 📁 logs/               # System logs (auto-created)
└── 📄 index.php           # Landing page
```

## 🔧 Configuration (Optional)

### Email Settings
Edit `config/config.php` to enable email notifications:
```php
define('SMTP_HOST', 'your-smtp-server.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@domain.com');
define('SMTP_PASSWORD', 'your-password');
```

### File Upload Limits
Adjust in `config/config.php`:
```php
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
```

## 🎯 Key Features Implemented

### ✅ Student Features
- [x] Registration & Login
- [x] Profile Management
- [x] Resume & Photo Upload
- [x] Job Drive Browsing
- [x] Application Submission
- [x] Application Status Tracking
- [x] Real-time Notifications
- [x] Responsive Mobile Design

### ✅ Admin Features
- [x] Admin Dashboard with Analytics
- [x] Student Management & Approval
- [x] Company Management
- [x] Placement Drive Creation
- [x] Application Status Management
- [x] Comprehensive Reports
- [x] Notification System
- [x] Activity Logging

### ✅ System Features
- [x] Secure Authentication
- [x] Role-based Access Control
- [x] File Upload Management
- [x] Database Activity Logging
- [x] Responsive Design (Mobile/Tablet/Desktop)
- [x] Modern UI with Tailwind CSS

## 🔒 Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
- **SQL Injection Protection**: All queries use prepared statements
- **XSS Prevention**: Input sanitization and output escaping
- **Session Security**: Secure session management
- **File Upload Validation**: Type and size restrictions
- **Activity Logging**: Complete audit trail

## 📊 Database Schema

### Core Tables Created:
- `users` - Student and admin accounts
- `companies` - Company information
- `placement_drives` - Job opportunities
- `applications` - Student applications
- `notifications` - System notifications
- `activity_logs` - System audit trail
- `tickets` - Support system (structure ready)
- `feedback` - User feedback (structure ready)

## 🚨 Troubleshooting

### Common Issues:

**1. Database Connection Error**
- Ensure MySQL is running in XAMPP
- Check database credentials in `config/database.php`

**2. File Upload Not Working**
- Check folder permissions for `uploads/` directory
- Verify PHP upload settings in `php.ini`

**3. Login Issues**
- Clear browser cache and cookies
- Ensure you're using correct credentials
- Check if user status is 'approved' for students

**4. Blank Pages**
- Enable PHP error reporting
- Check Apache error logs in XAMPP

### Getting Help:
1. Check the error logs in `logs/error.log`
2. Verify XAMPP services are running
3. Ensure all files are properly uploaded
4. Check browser console for JavaScript errors

## 🎓 Usage Workflow

### For Students:
1. Register with academic details
2. Wait for admin approval
3. Login and complete profile
4. Upload resume and photo
5. Browse available job drives
6. Apply for suitable positions
7. Track application status
8. Receive notifications for updates

### For Admins/TPOs:
1. Login with admin credentials
2. Approve student registrations
3. Add company partnerships
4. Create placement drives
5. Monitor applications
6. Update application statuses
7. Send notifications to students
8. Generate placement reports

## 📈 Future Enhancements

The system is designed to be extensible. Potential additions:
- Email verification system
- Advanced reporting with more charts
- Resume parsing and keyword matching
- Interview scheduling system
- Mobile app development
- Integration with external job portals
- AI-powered job recommendations

## 🆘 Support

For technical support or questions:
1. Check this documentation first
2. Review the README.md file
3. Check system logs for errors
4. Verify all installation steps were followed

---

**Congratulations! PlacerHub is now ready to streamline your placement management process! 🎉**

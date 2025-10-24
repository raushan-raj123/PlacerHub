# PlacerHub - Placement Management System

A comprehensive web-based placement management system built with PHP, MySQL, and Tailwind CSS. PlacerHub streamlines the entire placement process for educational institutions, students, and companies.

## 🚀 Features

### Student Features
- **User Registration & Authentication** - Secure login/registration with email verification
- **Responsive Dashboard** - Modern, mobile-friendly interface
- **Profile Management** - Complete profile with resume upload
- **Job Drive Listings** - Browse and filter available placement opportunities
- **Application Tracking** - Track application status (Applied, Shortlisted, Selected, Rejected)
- **Real-time Notifications** - Get updates on applications and new opportunities
- **Eligibility Checking** - Automatic eligibility verification based on CGPA and branch

### Admin/TPO Features
- **Admin Dashboard** - Comprehensive analytics and statistics
- **Student Management** - Approve/reject student registrations
- **Company Management** - Add and manage company partnerships
- **Drive Management** - Create and manage placement drives
- **Application Tracking** - Monitor all student applications
- **Notification System** - Send bulk notifications to students
- **Reports & Analytics** - Generate placement reports and statistics
- **Activity Logging** - Track all system activities

### System Features
- **Responsive Design** - Works perfectly on all devices
- **Role-based Access Control** - Separate interfaces for students and admins
- **Secure Authentication** - Password hashing and session management
- **Database Logging** - Complete audit trail of all activities
- **Modern UI/UX** - Built with Tailwind CSS for beautiful interfaces

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **CSS Framework**: Tailwind CSS
- **Icons**: Font Awesome
- **Charts**: Chart.js
- **Server**: Apache (XAMPP recommended)

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- XAMPP (recommended for local development)

## 🚀 Installation

### 1. Clone or Download
```bash
git clone https://github.com/yourusername/PlacerHub.git
# OR download and extract the ZIP file
```

### 2. Setup XAMPP
- Download and install XAMPP from https://www.apachefriends.org/
- Start Apache and MySQL services

### 3. Database Setup
- Copy the PlacerHub folder to `C:\xampp\htdocs\`
- Open your browser and go to `http://localhost/PlacerHub/setup/install.php`
- The installation script will automatically create the database and tables

### 4. Default Admin Credentials
```
Email: admin@placerhub.com
Password: password
```
**⚠️ Important: Change the default password after first login!**

### 5. Configuration (Optional)
Edit `config/config.php` to customize:
- Site name and URL
- File upload limits
- Email settings (SMTP)
- Security settings

## 📁 Project Structure

```
PlacerHub/
├── admin/                  # Admin panel files
│   ├── dashboard.php      # Admin dashboard
│   ├── students.php       # Student management
│   ├── companies.php      # Company management
│   └── ...
├── auth/                  # Authentication files
│   ├── login.php         # Login page
│   ├── register.php      # Registration page
│   └── logout.php        # Logout handler
├── config/               # Configuration files
│   ├── config.php        # Main configuration
│   └── database.php      # Database connection
├── dashboard/            # Student dashboard
│   ├── index.php         # Student dashboard
│   ├── profile.php       # Profile management
│   ├── drives.php        # Job drives listing
│   ├── applications.php  # Application tracking
│   └── notifications.php # Notifications
├── database/             # Database files
│   └── schema.sql        # Database schema
├── setup/               # Installation files
│   └── install.php      # Database installer
├── uploads/             # File uploads directory
│   ├── resumes/         # Student resumes
│   ├── photos/          # Profile photos
│   └── documents/       # Other documents
├── logs/                # System logs
└── index.php           # Landing page
```

## 🗄️ Database Schema

### Core Tables
- **users** - Student and admin accounts
- **companies** - Company information
- **placement_drives** - Job drives and opportunities
- **applications** - Student job applications
- **notifications** - System notifications
- **tickets** - Support ticket system
- **feedback** - User feedback and ratings
- **activity_logs** - System activity tracking

## 🔧 Configuration

### Email Settings
To enable email notifications, configure SMTP settings in `config/config.php`:

```php
define('SMTP_HOST', 'your-smtp-host');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@domain.com');
define('SMTP_PASSWORD', 'your-email-password');
```

### File Upload Settings
Adjust file upload limits in `config/config.php`:

```php
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
```

## 🚀 Usage

### For Students
1. Register with your academic details
2. Wait for admin approval
3. Complete your profile and upload resume
4. Browse available job drives
5. Apply for suitable positions
6. Track application status
7. Receive notifications for updates

### For Admins/TPOs
1. Login with admin credentials
2. Approve student registrations
3. Add company partnerships
4. Create placement drives
5. Monitor applications
6. Send notifications to students
7. Generate placement reports

## 🔒 Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Session-based authentication
- Role-based access control
- Activity logging for audit trails
- File upload validation

## 📱 Mobile Responsiveness

PlacerHub is fully responsive and works seamlessly on:
- Desktop computers
- Tablets
- Mobile phones
- All modern browsers

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

If you encounter any issues or need help:

1. Check the documentation above
2. Look for solutions in the Issues section
3. Create a new issue with detailed information
4. Contact the development team

## 🎯 Future Enhancements

- Email verification system
- Advanced reporting with charts
- Resume parsing and matching
- Interview scheduling system
- Mobile app development
- Integration with job portals
- AI-powered job recommendations

## 👥 Credits

Developed with ❤️ for educational institutions and students worldwide.

---

**Happy Placement Management! 🎓**

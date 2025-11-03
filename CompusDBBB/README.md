# CompusDB - Comprehensive Database Management System

A full-featured database management system built with PHP, MySQL, HTML, CSS (Tailwind), and JavaScript. This system provides complete user and student management with admin controls, authentication, and modern responsive design.

## 🚀 Features

### 🟦 User Side (Frontend Features)
- **Responsive Design**: Clean, animated, and interactive UI built with Tailwind CSS
- **User Authentication**: Secure login/registration with password validation
- **Dashboard**: Overview with statistics, quick actions, and recent activity
- **Student Management**: Full CRUD operations with search, filter, and pagination
- **Data Export**: Export data in CSV, Excel, and PDF formats
- **Profile Management**: Update personal details and profile pictures
- **Notification System**: Real-time notifications with bell icon
- **Support System**: Submit and track support tickets
- **Dark/Light Mode**: Theme toggle functionality
- **Mobile Responsive**: Works perfectly on all devices

### 🟩 Admin Side (Backend Features)
- **Admin Dashboard**: Comprehensive system overview with charts and statistics
- **User Management**: Full control over user accounts and permissions
- **Student Management**: Advanced student data management
- **System Settings**: Configure application settings and preferences
- **Activity Logs**: Track all system activities and user actions
- **Backup & Restore**: Database backup and restoration capabilities
- **Support Management**: Handle user tickets and feedback
- **Reports & Analytics**: Generate detailed system reports

### 🧱 Database Features
- **MySQL Database**: Robust relational database design
- **Data Integrity**: Foreign key constraints and data validation
- **Security**: Password encryption and SQL injection prevention
- **Performance**: Optimized queries with proper indexing
- **Scalability**: Designed to handle large amounts of data

## 📋 Requirements

- **Web Server**: Apache/Nginx with PHP support
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Extensions**: PDO, MySQL, JSON, OpenSSL

## 🛠️ Installation

1. **Clone or Download**
   ```bash
   git clone <repository-url>
   # OR download and extract to your web server directory
   ```

2. **Database Setup**
   - Create a new MySQL database named `compusdb`
   - Import the database schema from `database/schema.sql`
   ```sql
   mysql -u root -p compusdb < database/schema.sql
   ```

3. **Configuration**
   - Update database credentials in `config/database.php`
   - Modify application settings in `config/config.php`

4. **Permissions**
   - Ensure the `uploads/` directory is writable
   - Set appropriate file permissions for security

5. **Access the Application**
   - Navigate to `http://localhost/CompusDB/`
   - Default admin credentials: `admin` / `admin123`

## 📁 Project Structure

```
CompusDB/
├── admin/                  # Admin panel files
│   ├── dashboard.php      # Admin dashboard
│   ├── users.php          # User management
│   └── ...
├── config/                # Configuration files
│   ├── config.php         # Main configuration
│   └── database.php       # Database connection
├── database/              # Database files
│   └── schema.sql         # Database schema
├── includes/              # Common includes
│   ├── auth.php           # Authentication system
│   └── functions.php      # Helper functions
├── students/              # Student management
│   ├── index.php          # Student listing
│   ├── add.php            # Add student
│   ├── edit.php           # Edit student
│   ├── view.php           # View student
│   ├── delete.php         # Delete student
│   └── export.php         # Export students
├── uploads/               # File uploads directory
├── index.php              # Landing page
├── login.php              # Login page
├── register.php           # Registration page
├── dashboard.php          # User dashboard
├── logout.php             # Logout handler
└── README.md              # This file
```

## 🔐 Security Features

- **Password Hashing**: Secure password storage using PHP's password_hash()
- **CSRF Protection**: Cross-site request forgery prevention
- **SQL Injection Prevention**: Prepared statements for all database queries
- **Session Management**: Secure session handling with timeouts
- **Input Validation**: Comprehensive data sanitization
- **Role-Based Access**: Admin and user role separation
- **Activity Logging**: Track all system activities

## 🎨 UI/UX Features

- **Modern Design**: Clean and professional interface
- **Responsive Layout**: Works on desktop, tablet, and mobile
- **Interactive Elements**: Hover effects, animations, and transitions
- **Accessible**: ARIA labels and keyboard navigation support
- **Fast Loading**: Optimized assets and efficient code
- **User Feedback**: Success/error messages and loading states

## 📊 Database Schema

### Core Tables
- `users` - User accounts and authentication
- `students` - Student records and information
- `courses` - Available courses
- `departments` - Academic departments
- `notifications` - System notifications
- `tickets` - Support tickets
- `activity_logs` - System activity tracking
- `user_settings` - User preferences
- `system_settings` - Application configuration

## 🚀 Usage

### For Users
1. **Registration**: Create an account with email verification
2. **Login**: Access your dashboard with username/email and password
3. **Manage Students**: Add, edit, view, and delete student records
4. **Search & Filter**: Find students using various criteria
5. **Export Data**: Download student data in multiple formats
6. **Get Support**: Submit tickets for help and track their status

### For Administrators
1. **Admin Dashboard**: Monitor system health and statistics
2. **User Management**: Control user accounts and permissions
3. **System Settings**: Configure application behavior
4. **View Reports**: Generate and analyze system reports
5. **Manage Backups**: Create and restore database backups
6. **Handle Support**: Respond to user tickets and feedback

## 🔧 Customization

### Themes
- Modify CSS classes in the HTML files
- Update color schemes in Tailwind configuration
- Add custom CSS for additional styling

### Features
- Add new modules by following the existing structure
- Extend database schema as needed
- Implement additional export formats
- Add more notification types

### Configuration
- Update `config/config.php` for application settings
- Modify `database/schema.sql` for database changes
- Adjust security settings and session timeouts

## 🐛 Troubleshooting

### Common Issues
1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists and is accessible

2. **File Upload Issues**
   - Check `uploads/` directory permissions
   - Verify PHP upload settings in `php.ini`
   - Ensure sufficient disk space

3. **Session Problems**
   - Check PHP session configuration
   - Verify session directory permissions
   - Clear browser cookies and cache

### Error Logs
- Check PHP error logs for detailed error information
- Enable error reporting in development environment
- Monitor application logs for security issues

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For support and questions:
- Create an issue in the repository
- Check the documentation
- Review the troubleshooting section

## 🔄 Updates

### Version 1.0.0
- Initial release with core functionality
- User authentication and management
- Student CRUD operations
- Admin dashboard and controls
- Responsive design implementation
- Security features and validation

---

**CompusDB** - Making database management simple and efficient! 🎓✨

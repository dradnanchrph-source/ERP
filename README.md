# AIRMan ERP System

A modern, multi-tenant PHP-based ERP system built with a custom MVC framework. Designed for small to medium businesses requiring robust management of Business Partners, Inventory, Sales, Purchases, HR, and Financial Reporting.

## 🚀 Key Features

### Core Modules
- **Business Partner (BP) Management**: 360° view, credit exposure tracking, compliance monitoring.
- **Inventory Management**: Batch tracking, FEFO (First Expired First Out), moving average valuation, stock reconciliation.
- **Purchase Cycle**: Requisition → RFQ → Purchase Order → GRN → Quality Control → Invoice.
- **Sales Cycle**: Inquiry → Quotation → Sales Order → Dispatch → Invoice.
- **HR & Payroll**: Employee management, leave requests, payroll processing, loan management.
- **Contacts**: Unified contact ledger with transaction history.
- **Reports**: AR/AP Aging, Sales Summary, Profit & Loss, Stock Valuation, Expiry Tracking.

### Technical Highlights
- **Custom MVC Framework**: Lightweight, fast, and tailored for specific business logic.
- **Multi-Tenancy**: Built-in `business_id` scoping for SaaS or multi-company setups.
- **Security**: RBAC (Role-Based Access Control), CSRF protection, session hijacking prevention, account lockout.
- **Database**: PDO-based layer with prepared statements (MySQL/MariaDB).
- **UI/UX**: Bootstrap 5.3, Font Awesome 6, responsive sidebar, dark/light theme support.
- **Audit Logging**: Comprehensive action logging in the `writable/logs` directory.

## 📂 Project Structure

```text
/
├── app/
│   ├── Controllers/      # Application logic (BP, Inventory, Sales, HR, etc.)
│   ├── Views/            # HTML/PHP templates (Bootstrap 5)
│   ├── Core/             # Framework core (Router, Auth, DB, Controller base)
│   └── Helpers/          # Utility functions (Date, String, Formatters)
├── config/
│   └── config.php        # Database credentials & App settings
├── public/               # Web root (assets, index.php entry point)
├── writable/             # Logs, cache, uploads (Must be writable by server)
├── routes.php            # URL routing definitions
└── README.md             # This file
```

## 🛠️ Installation (cPanel / Shared Hosting)

Since this is a standard PHP application, it can be deployed easily via cPanel File Manager.

### 1. Prerequisites
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB
- `pdo_mysql` extension enabled
- `mod_rewrite` enabled (for clean URLs)

### 2. Database Setup
1. Create a new database and user in cPanel **MySQL Databases**.
2. Import the provided SQL dump (usually `database.sql` or similar) via **phpMyAdmin**.
3. Note the database name, username, and password.

### 3. File Upload
1. Download the latest release ZIP from GitHub.
2. Log in to **cPanel** → **File Manager**.
3. Navigate to your web root (e.g., `public_html`).
4. **Upload** the ZIP file.
5. **Extract** the contents. Ensure files like `index.php`, `app/`, and `config/` are directly in the root, not in a subfolder.
6. Delete the ZIP file after extraction.

### 4. Configuration
1. Open `config/config.php` in the File Manager editor.
2. Update the database settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   ```
3. Set the `BASE_URL` to your domain (e.g., `https://yourdomain.com/`).

### 5. Permissions
Ensure the `writable` directory has write permissions:
- Right-click the `writable` folder in File Manager.
- Select **Change Permissions**.
- Set to **755** or **777** (if 755 doesn't work).
- Do the same for `writable/logs` and `writable/cache`.

### 6. .htaccess
Ensure the `.htaccess` file in the root (or public folder) is present. It handles URL rewriting. If you placed files in a subdirectory, update the `RewriteBase` in `.htaccess`.

## 🔧 Recent Fixes & Updates
- **Controller Conflicts**: Resolved method name collisions (`view()` vs `show()`) in BusinessPartner and Contact controllers.
- **Missing Views**: Added 20+ missing view files for HR, Contacts, Settings, and Reports modules.
- **Routing**: Updated routes to match new controller methods.
- **Error Handling**: Fixed syntax errors in exception handling blocks.

## 📝 Default Credentials
*(Check your database seed data or SQL dump for actual defaults)*
- **Username**: `admin`
- **Password**: `admin123` (or as defined in the SQL dump)

## 🐛 Troubleshooting

### 500 Internal Server Error
- Check `writable/logs/error.log` for specific PHP errors.
- Ensure PHP version is 8.0+.
- Verify database credentials in `config/config.php`.

### 404 Not Found on Pages
- Ensure `.htaccess` is uploaded and `mod_rewrite` is enabled.
- Check if the specific View file exists in `app/Views/`.
- Verify the route exists in `routes.php`.

### Permission Denied
- Ensure the `writable` folder and its subfolders have 755/777 permissions.

## 🤝 Contributing
1. Fork the repository.
2. Create a feature branch.
3. Commit your changes.
4. Push to the branch and create a Pull Request.

## 📄 License
[Insert License Name Here]

---
**Built with ❤️ using PHP & Bootstrap**

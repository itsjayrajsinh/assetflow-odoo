# AssetFlow — Enterprise Asset & Resource Management System

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

A full-featured Enterprise Asset & Resource Management System built with PHP, MySQL, and Bootstrap. Track, allocate, and maintain your organization's assets with ease.

## ✨ Features

- **🔐 Authentication** — Login, signup (Employee-only), forgot/reset password, role-based access
- **📊 Dashboard** — KPI cards, Chart.js visualizations, overdue alerts, quick actions
- **🏢 Organization Setup** — Departments (hierarchy), asset categories (custom fields), employee directory with role promotion
- **📦 Asset Directory** — Registration with auto-generated tags (AF-0001), search/filter, lifecycle tracking
- **🔄 Allocation & Transfer** — Double-allocation prevention, transfer workflow, return with condition check-in
- **📅 Resource Booking** — FullCalendar integration, real-time overlap validation
- **🔧 Maintenance** — Approval workflow (Pending → Approved → Assigned → In Progress → Resolved)
- **📋 Asset Audit** — Cycle creation, auditor assignment, verification, discrepancy reports
- **📈 Reports & Analytics** — Utilization trends, maintenance frequency, booking heatmap, CSV export
- **🔔 Notifications** — Real-time bell, notification list, full activity logs
- **🤖 AI Chatbot** — Rule-based assistant for asset queries, how-to guidance, status checks

## 🎨 Design

- **Pastel Color Theme** — Soft indigo, mint green, coral, teal
- **Modern UI** — Glassmorphism, micro-animations, gradient accents
- **Responsive** — Works on desktop, tablet, and mobile
- **Google Fonts** — Inter typeface throughout

## 👥 User Roles

| Role | Permissions |
|------|------------|
| **Admin** | Full system access, org setup, role management |
| **Asset Manager** | Register/allocate assets, approve maintenance/transfers |
| **Department Head** | Department assets, approve dept transfers, book resources |
| **Employee** | View assets, book resources, raise maintenance requests |

## 🚀 Quick Start

### Prerequisites
- PHP 8.0+
- MySQL 8.0+
- Web server (Apache/Nginx) or PHP built-in server

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/your-repo/assetflow-odoo.git
cd assetflow-odoo

# 2. Create the database
mysql -u root < database/schema.sql
mysql -u root assetflow < database/seed.sql

# 3. Configure database (edit if needed)
# File: app/Config/Database.php
# Default: localhost / root / (no password) / assetflow

# 4. Start the development server
php -S localhost:8000 -t public/

# 5. Open in browser
# http://localhost:8000
```

### Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@assetflow.com | password123 |
| Asset Manager | rajesh@assetflow.com | password123 |
| Department Head | priya@assetflow.com | password123 |
| Employee | amit@assetflow.com | password123 |

## 📁 Project Structure

```
assetflow-odoo/
├── public/              # Web root
│   ├── index.php        # Front controller
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript
│   └── uploads/         # Uploaded files
├── app/
│   ├── Config/          # Database configuration
│   ├── Core/            # Router, Controller, Model, Auth, Middleware, Helpers
│   ├── Controllers/     # 13 controllers
│   ├── Models/          # 11 models
│   └── Views/           # All view templates
├── database/
│   ├── schema.sql       # Full database schema (14 tables)
│   └── seed.sql         # Demo data
└── README.md
```

## 🗃️ Database Schema

14 tables: `users`, `departments`, `asset_categories`, `assets`, `allocations`, `transfer_requests`, `bookings`, `maintenance_requests`, `audit_cycles`, `audit_assignments`, `audit_items`, `notifications`, `activity_logs`, `chatbot_rules`

## 📝 License

MIT License — see LICENSE file for details.

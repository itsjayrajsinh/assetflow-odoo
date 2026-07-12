
# 🚀 AssetFlow — Enterprise Asset & Resource Management System

<p align="center">

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap)
![MIT](https://img.shields.io/badge/License-MIT-success?style=for-the-badge)

Enterprise Asset & Resource Management System built for **Odoo Hackathon**.
</p>

---

# ✨ Features

- 🔐 Authentication & Role Based Access
- 📊 Dashboard with KPIs & Charts
- 🏢 Department & Employee Management
- 📦 Asset Registration & Tracking
- 🔄 Allocation & Transfer Workflow
- 📅 Resource Booking
- 🔧 Maintenance Requests
- 📋 Asset Audit
- 📈 Reports & CSV Export
- 🔔 Notifications
- 🤖 AI Chatbot

# 🎨 Design

- Pastel UI
- Glassmorphism
- Responsive
- Google Fonts (Inter)

# 👥 User Roles

| Role | Access |
|------|--------|
| Admin | Full Access |
| Asset Manager | Assets & Maintenance |
| Department Head | Department Assets |
| Employee | Booking & Requests |

# 🚀 Installation

```bash
git clone https://github.com/Mayankkushwah1603/assetflow.git
cd assetflow

mysql -u root < database/schema.sql
mysql -u root assetflow < database/seed.sql

php -S localhost:8000 -t public/
```

Open:
```
http://localhost:8000
```

# 📁 Project Structure

```text
public/
app/
database/
README.md
```

# 🗃 Database

14 Tables

- users
- departments
- asset_categories
- assets
- allocations
- transfer_requests
- bookings
- maintenance_requests
- audit_cycles
- audit_assignments
- audit_items
- notifications
- activity_logs
- chatbot_rules

# 👨‍💻 Team

<table>
<tr>
<td align="center">

### Mayank Kushwah

<a href="https://github.com/Mayankkushwah1603">
<img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/github/github-original.svg" width="35"/>
</a>
&nbsp;
<a href="https://www.linkedin.com/in/mayank-kushwah-36b926367">
<img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/linkedin/linkedin-original.svg" width="35"/>
</a>

</td>

<td align="center">

### Jayraj Rathod

<a href="https://github.com/itsjayrajsinh">
<img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/github/github-original.svg" width="35"/>
</a>
&nbsp;
<a href="https://www.linkedin.com/in/jayrajsinhrathod05">
<img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/linkedin/linkedin-original.svg" width="35"/>
</a>

</td>

<td align="center">

### Shivam Prajapati

<a href="https://github.com/itzshivam72">
<img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/github/github-original.svg" width="35"/>
</a>
&nbsp;
<a href="https://www.linkedin.com/in/shivam-prajapati-1a94953b6">
<img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/linkedin/linkedin-original.svg" width="35"/>
</a>

</td>

<td align="center">

### Aliraza Rahin

<a href="https://github.com/AlirazaRahin2912">
<img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/github/github-original.svg" width="35"/>
</a>
&nbsp;
<a href="https://www.linkedin.com/in/aliraza-rahin-8093a237a/">
<img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/linkedin/linkedin-original.svg" width="35"/>
</a>

</td>
</tr>
</table>

# 📜 License

MIT License

# Kmind — Machine Learning Club Website

A fully custom-built website for the Kmind ML Research Club. Built from scratch with HTML, CSS, and JavaScript on the frontend and PHP + MySQL on the backend.

## 📹 Project Walkthrough

[![Watch the Walkthrough](https://img.shields.io/badge/Watch-Project%20Walkthrough-blue?style=for-the-badge&logo=google-drive)](https://drive.google.com/file/d/1nMRYoUSl8f63J-xTLAYdub3fnazwUQFM/view)

## Tech Stack

- **Frontend:** HTML, CSS, JavaScript (no frameworks)
- **Backend:** PHP 8+
- **Database:** MySQL (PDO)

## Features

### Frontend
- Neural network canvas animation on hero section
- CSS marquee ticker strip (no JavaScript)
- Scroll-reveal animations using Intersection Observer API
- Count-up stat animations
- Active navbar highlight on scroll
- Live filtering on Blog and Research pages (no page reload)
- Fully responsive design
- SVG icons throughout (no emojis)

### Pages
- Homepage
- Projects (with individual project detail pages)
- Research Papers (with BibTeX cite modal)
- Learning Roadmap (5 tracks with phase-by-phase curriculum)
- Blog
- Events

### Backend / Auth
- Member signup with admin approval flow
- Session-based login with PHP + MySQL PDO
- Remember Me (30-day secure cookie with SHA-256 hashed token)
- Role-based access control (member / admin)
- CSRF protection
- Member dashboard with RSVP system
- Admin panel — approve/reject members, create/delete events

## Getting Started

### Requirements
- PHP 8+
- MySQL

### Setup

1. Create a MySQL database called `kminds` and import the schema:
```bash
mysql -u root -p kminds < data/kminds.sql
```

2. Update database credentials in `auth/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'kminds');
```

3. Start the PHP development server:
```bash
php -S localhost:8000
```

4. Open `http://localhost:8000` in your browser.

### Default Admin Login
- **Email:** admin@kmind.com
- **Password:** admin123

> Insert admin user by running:
> ```sql
> INSERT INTO users (first_name, last_name, email, password, role, status)
> VALUES ('Admin', 'Kmind', 'admin@kmind.com', '<bcrypt_hash>', 'admin', 'approved');
> ```
> Generate hash: `php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"`

## Project Structure

```
kminds/
├── index.html              # Homepage
├── style.css               # Global styles
├── script.js               # Animations, observers, interactions
├── auth/
│   ├── db.php              # MySQL PDO singleton
│   └── init.php            # Session, CSRF, Remember Me
├── auth.css                # Auth & dashboard styles
├── dashboard.php           # Member dashboard
├── login.php               # Login page
├── logout.php              # Logout + cookie clear
├── signup.php              # Member application form
├── admin.php               # Admin panel — members
├── admin-events.php        # Admin panel — events
├── data/
│   └── kminds.sql          # Database schema + seed data
├── research.html / .css    # Research papers page
├── roadmap.html / .css     # Learning roadmap page
├── blog.html / .css        # Blog page
├── project-campuslens.html # Project detail page
└── project-detail.css      # Project detail styles
```

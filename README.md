# Campus Event Board

Campus Event Board is a web-based Campus Event Management System developed using PHP and MySQL. It enables students to browse university events, RSVP for participation, and track attendance while providing administrators with tools to create, edit, and manage events through a secure dashboard.

## Features Overview

- Student registration and login
- Secure password hashing with `password_hash()`
- Secure login verification with `password_verify()`
- Role-based student/admin access
- Admin dashboard with analytics
- Full CRUD operations for event management
- Featured events
- Event image uploads
- Student RSVP and attendance management
- RSVP cancellation
- Attendance check-in tracking
- Search and category filtering
- Responsive mobile-friendly user interface
- PDO prepared statements for database queries
- Output escaping with `htmlspecialchars()`

## Tech Stack

- PHP 8+
- MySQL
- PDO
- HTML5
- CSS3
- Vanilla JavaScript
- Apache with XAMPP, LAMP, or similar local server



## Security Features

- Password hashing using PHP's `password_hash()`
- Password verification using `password_verify()`
- PDO prepared statements to prevent SQL injection
- PHP session-based authentication
- Role-based access control
- Output escaping with `htmlspecialchars()`
- Input validation and sanitization


## XAMPP Setup

1. Install XAMPP from the official Apache Friends website.
2. Start XAMPP Control Panel.
3. Start `Apache`.
4. Start `MySQL`.
5. Place this project folder inside:

```text
C:\xampp\htdocs\campus-event-board

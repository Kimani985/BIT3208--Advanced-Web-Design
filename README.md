# Campus Event Board

Campus Event Board is a PHP and MySQL web application for managing university events, student RSVPs, featured events, image uploads, and attendance tracking.

## Features Overview

- Student registration and login
- Secure password hashing with `password_hash()`
- Secure login verification with `password_verify()`
- Role-based student/admin access
- Admin dashboard with analytics
- Event create, read, update, and delete operations
- Featured events
- Event image uploads
- Student RSVP system
- RSVP cancellation
- Attendance check-in tracking
- Search and category filtering
- Responsive mobile-first user interface
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

## XAMPP Setup

1. Install XAMPP from the official Apache Friends website.
2. Start XAMPP Control Panel.
3. Start `Apache`.
4. Start `MySQL`.
5. Place this project folder inside:

```text
C:\xampp\htdocs\campus-event-board
# Campus Event Board

## Overview

Campus Event Board is a web-based Campus Event Management System developed using PHP and MySQL. It enables students to browse university events, RSVP for participation, and track attendance while providing administrators with tools to create, edit, and manage events through a secure dashboard.

## Features

### Student Features

* Register a new account
* Secure login and logout
* Browse upcoming campus events
* Search and filter events by category
* View detailed event information
* RSVP for events
* Cancel RSVPs
* View personal RSVP history
* Track attendance for registered events

### Administrator Features

* Secure administrator login
* Dashboard with event statistics and analytics
* Create new events
* Edit existing events
* Delete events
* Upload event images
* Mark events as featured
* View registered attendees
* Monitor RSVP and attendance statistics

## Technologies Used

* PHP 8+
* MySQL
* PDO
* HTML5
* CSS3
* Vanilla JavaScript
* Apache (XAMPP/LAMP)

## Security Features

* Secure password hashing using `password_hash()`
* Password verification using `password_verify()`
* PDO prepared statements to prevent SQL injection
* PHP session-based authentication
* Role-based access control
* Output escaping using `htmlspecialchars()`
* Input validation and sanitization

## Database

**Database Name**

```text
campus_event_board
```

### Main Tables

* `users`
* `events`
* `rsvps`

## Installation

1. Install XAMPP or another compatible LAMP environment.
2. Start **Apache** and **MySQL** services.
3. Copy the project folder into:

```text
C:\xampp\htdocs\campus-event-board
```

4. Open phpMyAdmin and create a database named:

```text
campus_event_board
```

5. Import the SQL file:

```text
database/campus_event_board.sql
```

6. Open your browser and navigate to:

```text
http://localhost/campus-event-board/
```

## Project Structure

```text
campus-event-board/
├── admin/
├── assets/
│   ├── css/
│   ├── js/
│   └── uploads/
├── config/
├── database/
├── includes/
├── index.php
├── login.php
├── register.php
├── logout.php
├── event.php
├── rsvp.php
├── my_rsvps.php
└── README.md
```

## Key Functionalities

* Event management with full CRUD operations
* Student RSVP and attendance tracking
* Featured event management
* Image upload support
* Live event search and filtering
* Responsive mobile-friendly interface
* Secure authentication and authorization
* MySQL-backed data persistence

## Future Improvements

* Email notifications
* SMS reminders
* QR code event check-in
* Mobile application integration
* Advanced reporting and analytics

## Author

**Stephen Kimani**

Registration Number: BSCCS/2024/57444
Course: Bachelor of Science in Computer Science
BIT3208 – Advanced Web Design
Mount Kenya University


T2-T3 Assessment Management System
A comprehensive web-based application for managing student document submissions and faculty assessments in educational institutions.
Project Overview
The T2-T3 Assessment Management System is designed to streamline the process of document submissions (T2 and T3) by students and their evaluation by faculty members. The application supports three user roles - Admin, Faculty, and Students - each with specific functionalities tailored to their responsibilities in the assessment process.
Key Features
Admin Features
User management (add/edit students and faculty)
Department and subject management
Faculty-subject assignment
Section allocation and management
System-wide data administration

Faculty Features

View assigned subjects, sections, and years
Download student submissions (T2 documents and T3 presentations)
Evaluate and assign marks for submissions
Track assessment progress

Student Features

Submit T2 documents (.docx format) and T3 presentations (.pptx format)
View submission status
Check assessment marks
Access course-related information

Technical Architecture
Database Design

MySQL database with relational schema
Tables for users, departments, subjects, faculty, students, submissions, and marks
Proper constraints and relationships to maintain data integrity

Technology Stack

Backend: PHP
Database: MySQL
Frontend: HTML, CSS, JavaScript
Server: Apache

Installation and Setup
Prerequisites

PHP 8.x
MySQL 5.7+ or MariaDB 10.4+
Apache/Nginx web server
Web browser with JavaScript enabled

Installation Steps

Clone this repository to your local web server directory

Import the database structure from the SQL dump file located in the database folder
Configure database connection in config/db.php
Access the application through your web server
Login with default admin credentials:

Username: admin
Password: admin123



Usage Guide
Admin Login

Access the application URL
Login with admin credentials
Manage students, faculty, subjects, and assignments through the admin dashboard

Faculty Registration and Login

Faculty must register using their assigned faculty ID
After registration, login to access the faculty dashboard
View assigned courses and evaluate student submissions

Student Registration and Login

Students must register using their registration number (which must be pre-added by the admin)
After registration, login to access the student dashboard
Submit T2/T3 documents for enrolled courses and view assessment marks
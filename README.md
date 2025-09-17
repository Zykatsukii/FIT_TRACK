# Fit Track: Gym Management System for RVG Powerbuild

**Fit Track** is a web-based Gym Management System designed specifically for **RVG Powerbuild**. It streamlines daily operations such as member registration, attendance tracking, payment monitoring, and trainer scheduling. The system aims to improve management efficiency, member experience, and record accuracy.

## Features

- Member Registration & Profile Management
- Attendance Tracking
- Subscription & Payment Management
- Trainer Schedules
- Dashboard with Analytics
- Admin Panel for System Control
- Secure Login System

##Technologies Used

- **Frontend:** HTML, CSS, JavaScript, Bootstrap
- **Backend:** PHP
- **Database:** MySQL
- **Server:** XAMPP (Apache + MySQL)
- **Other Tools:** phpMyAdmin

How to Use the System

This section provides step-by-step instructions on how to use the Fit Track: Gym Management System for RVG Powerbuild.

 1. Admin Login

Open your browser and go to:

 http://localhost:8000/admin/index.php?page=login
http://localhost:3000/admin/login.php



Enter the Admin credentials on the login page:

Username: admin@fittrack.com
Password: RVG@12345


Click Login to access the Admin Dashboard.

 2. Access the Admin Dashboard

Once logged in, the Admin can access the following features via the dashboard:

Register New Members

View and Manage Members

Monitor Attendance Records

Manage Trainers and Schedules

Track Payments

Generate Reports

3. Registering a New Member

Navigate to the “Member Management” or “Add Member” section.

Fill out the registration form with the new member’s details:

Full Name

Contact Information

Membership Type

Payment Status

Photo (optional)

Click “Register” to save the new member into the system.

A unique Member ID and QR Code will be generated automatically for attendance tracking.

 4. Member Login and Profile Access

After registration, the member can log in using their assigned credentials (provided by the Admin).

Example:
Username: member001
Password: [initial password set by admin or auto-generated]


After logging in, the member will be redirected to their Profile Dashboard, where they can:

View and update personal information (e.g., contact number, address, profile photo)

View membership details and payment status

Access their QR Code for gym check-in/out

5. Using QR Code for Attendance

Members will use their QR Code displayed on their profile for attendance purposes.

At the gym entrance, a staff member or a QR scanner system will scan the member’s QR Code.

The system logs the attendance date and time in the database.

 This process automates attendance logging and ensures accurate records for both members and staff.

 Summary Flow

Admin
→ Logs into dashboard
→ Registers new members
→ Monitors attendance and manages records



Member
→ Logs in with credentials
→ Edits profile if needed
→ Views QR code
→ Uses QR code for gym attendance

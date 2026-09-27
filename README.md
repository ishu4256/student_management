# Student Management System

A web-based **Student Management System** developed in **PHP** and **MySQL**. This application provides a comprehensive solution for educational institutions to manage students, teachers, departments, attendance, timetables, and academic assessments across three main user roles: **Admin**, **Teacher**, and **Student**.

---

## 🚀 Features

### 👨‍💼 Admin Panel
- **Dashboard Overview:** Monitor system metrics and key statistics.
- **Teacher Management:** Add, edit, delete, search, and manage teacher profiles.
- **Department & Subject Management:** Organize academic departments and assign subjects.
- **Teacher Requests:** Review and approve teacher registration requests.
- **Attendance & Assessment Summaries:** View overall institutional reports for attendance and assessments.

### 👨‍🏫 Teacher Module
- **Class & Student Management:** View enrolled students and manage student records.
- **Attendance Tracking:** Mark daily student attendance and view detailed attendance summaries.
- **Subject & Timetable Management:** Add, edit, or remove class schedules and subject details.
- **Assessment Management:** Create, edit, and evaluate quizzes, assignments, and questions for students.
- **Student Enrollment:** Assign or enroll students into specific classes and subjects.

### 🎓 Student Module
- **Profile Management:** Setup and edit personal profile details.
- **Class Enrollment:** View and enroll in assigned subjects/classes.
- **Attendance Portal:** Check individual attendance logs and reports.
- **Assessments & Quizzes:** View available assessments and submit responses.
- **Timetable:** View weekly class schedules.

---

## 🛠️ Tech Stack

- **Backend:** PHP (Native / Procedural & OOP)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3 (`style.css`), JavaScript
- **Version Control:** Git & GitHub

---

## 📂 Directory Structure

```text
student_management/
├── admin/                  # Admin module (Dashboard, Teacher/Subject/Department management)
├── student/                # Student portal (Assessments, Attendance, Profile, Timetable)
├── teacher/                # Teacher portal (Mark attendance, Assessments, Enrollment)
├── config/                 # Database configuration files (db.php)
├── css/                    # Custom CSS styles (style.css)
├── index.php               # Landing / Home page
├── login.php               # User login authentication
├── register.php            # User registration page
├── logout.php              # Session logout handler
└── README.md               # Project documentation
```

---

## ⚙️ Installation & Setup

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/ishu4256/student-management-system.git
   ```

2. **Move to Web Server Directory:**
   Move the `student_management` folder to your local server directory (e.g., `htdocs` for XAMPP or `www` for WAMP).

3. **Configure Database:**
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Create a new database named `student_management` (or as specified in your configuration).
   - Import your database structure/tables if an `.sql` dump file is provided.
   - Update `config/db.php` with your database connection credentials:
     ```php
     $host = "localhost";
     $user = "root";
     $password = "";
     $dbname = "student_management";
     ```

4. **Run the Project:**
   Open your browser and navigate to:
   ```text
   http://localhost/student_management/
   ```

---

## 🤝 Contributing

Contributions are welcome! If you'd like to improve this project:
1. Fork the repository.
2. Create your feature branch (`git checkout -b feature/NewFeature`).
3. Commit your changes (`git commit -m 'Add NewFeature'`).
4. Push to the branch (`git push origin feature/NewFeature`).
5. Open a Pull Request.

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

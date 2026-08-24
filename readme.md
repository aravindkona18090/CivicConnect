# 🏙️ CivicConnect

**Bridging the gap between citizens and municipal authorities.**

A web-based platform that lets citizens report civic issues directly to local authorities, while admins manage complaints and workers resolve them — end to end, in one system.

<p>
  <img src="https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Bootstrap-7952B3?style=flat&logo=bootstrap&logoColor=white" alt="Bootstrap">
  <img src="https://img.shields.io/badge/Leaflet.js-199900?style=flat&logo=leaflet&logoColor=white" alt="Leaflet.js">
  <img src="https://img.shields.io/badge/status-active-success" alt="status">
</p>

**🔗 Live Demo:** [civicconnect.up.railway.app](https://civicconnect.up.railway.app/)

---

## 📌 Overview

CivicConnect centralizes the reporting and tracking of public issues — potholes, garbage accumulation, streetlight failures, drainage problems, and more — so citizens and municipal staff aren't stuck coordinating over phone calls and paperwork.

The system supports three distinct roles and streamlines the complaint lifecycle from **report → assignment → resolution**.

---

## ✨ Features

### 👥 Citizen Portal
- Registration & login
- Report civic issues with description and photo
- Pin the exact issue location on an interactive OpenStreetMap
- Track complaint status in real time
- Manage profile information
- Multi-language support (English, Telugu, Hindi, Kannada)

### 🧑‍🔧 Worker Portal
- Login and view assigned complaints
- Update resolution progress

### 👨‍💼 Admin Portal
- Admin authentication
- Manage citizens and workers
- Allocate complaints to workers
- Monitor complaint progress end to end
- View issue reports and analytics

---

## 🗺️ Map Integration

- **OpenStreetMap** for base map data
- **Leaflet.js** for interactive rendering
- Location-based issue reporting with pin-drop address selection
- **Haversine distance formula** for duplicate-issue detection near an existing pin

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript, Bootstrap |
| Backend | PHP |
| Database | MySQL |
| Maps | Leaflet.js, OpenStreetMap |
| Email | PHPMailer |

---

## 📂 Project Structure

```
CivicConnect/
│
├── adminlogin/
├── peoplelogin/
├── workerlogin/
├── db/
├── images/
├── lang/
├── PHPMailer/
└── index.php
```

---

## 🚀 Getting Started

1. Install [XAMPP](https://www.apachefriends.org/).
2. Start **Apache** and **MySQL**.
3. Copy the project into:
   ```
   C:\xampp\htdocs\
   ```
4. Create a database named:
   ```
   gov_problems
   ```
5. Import the provided SQL file into phpMyAdmin.
6. Open:
   ```
   http://localhost/CivicConnect
   ```

> Prefer not to set up locally? Try the live demo instead: **[civicconnect.up.railway.app](https://civicconnect.up.railway.app/)**

---

## 🔑 User Roles

| Role | Capabilities |
|---|---|
| **Citizen** | Report issues, track status, manage profile |
| **Worker** | View and resolve assigned complaints |
| **Administrator** | Manage users, assign complaints, monitor progress |

---

## 📸 Screenshots

_Add screenshots here after uploading the project._

```markdown
### Home Page
![Home Page](screenshots/home.png)

### Report Issue
![Report Issue](screenshots/report-issue.png)

### Admin Dashboard
![Admin Dashboard](screenshots/admin-dashboard.png)
```

---

## 🎯 Future Improvements

- Real-time complaint tracking
- AI-based issue categorization
- Mobile application
- Analytics dashboard

---

## 👨‍💻 Author

**Kona Aravind Ranga Reddy**
B.Tech, Artificial Intelligence & Machine Learning

- GitHub: [@aravindkona18090](https://github.com/aravindkona18090)
- LinkedIn: [kona-aravind-ranga-reddy](https://www.linkedin.com/in/kona-aravind-ranga-reddy-1736b0344)

---

## 📄 License

This project is developed for educational and portfolio purposes.

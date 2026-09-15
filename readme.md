# 🏛️ CivicConnect — AI-Powered Smart City Grievance & Municipal Infrastructure Platform

[![Live Demo](https://img.shields.io/badge/🌐_Live_Deployment-civicconnect--qlwi.onrender.com-00C7B7?style=for-the-badge&logo=render&logoColor=white)](https://civicconnect-qlwi.onrender.com/)
[![Database](https://img.shields.io/badge/Database-TiDB_Cloud_Serverless_MySQL-00758F?style=for-the-badge&logo=mysql&logoColor=white)](https://tidbcloud.com/)
[![AI Vision & Chat](https://img.shields.io/badge/AI-Google_Gemini_2.5-4285F4?style=for-the-badge&logo=google&logoColor=white)](https://ai.google.dev/)
[![Auth](https://img.shields.io/badge/Auth-Google_OAuth_2.0_%2B_Brevo_OTP-EA4335?style=for-the-badge&logo=google&logoColor=white)](https://developers.google.com/identity)
[![GIS](https://img.shields.io/badge/GIS_Maps-Leaflet_%2B_OpenStreetMap-199900?style=for-the-badge&logo=openstreetmap&logoColor=white)](https://leafletjs.com/)
[![Status](https://img.shields.io/badge/Uptime-24%2F7_Monitored-brightgreen?style=for-the-badge&logo=uptimerobot&logoColor=white)](https://civicconnect-qlwi.onrender.com/)

---

## 🌐 Live Production URL
👉 **[https://civicconnect-qlwi.onrender.com/](https://civicconnect-qlwi.onrender.com/)**

> **CivicConnect** is an enterprise-grade, full-stack municipal management and civic engagement platform. It empowers citizens to report urban issues, leverages **Google Gemini Multimodal Vision AI** to automatically verify and categorize damage severity, dispatches field officers with **1-click GPS routing**, and enables administrative oversight through an interactive **City-Wide GIS Command Center**.

---

## ✨ Key Platform Features

### 👤 1. Citizen Portal (`/peoplelogin`)
- **Multimodal AI Grievance Reporting**: Citizens capture/upload civic damage (potholes, garbage, broken streetlights, sewage leaks).
- **Google Gemini 2.5 Vision AI**: Instantly classifies problem categories, detects severity (*Low, Medium, High, Critical*), and filters out fake/blurry uploads.
- **🎙️ Multilingual Voice AI Assistant (CivicBot)**:
  - **Speech-to-Text (`SpeechRecognition`)**: Speak complaints naturally in **Telugu (తెలుగు), Hindi (हिंदी), Kannada (ಕನ್ನಡ), or English**.
  - **Text-to-Speech (`SpeechSynthesis`)**: Reads out responses in a natural female Indic voice with continuous sentence queueing and emoji sanitation.
- **🔐 Google One-Tap & Brevo OTP Authentication**: 1-click Google Sign-In and secure 4-digit auto-advancing email verification.
- **🏆 Civic Karma Gamification (`peoplekarma.php`)**: Earn XP points, unlock badges (*Civic Guardian, City Champion*), and compete on city leaderboards.
- **🧾 Printable Municipal Acknowledgment Slip (`print_complaint.php`)**: Generates official government receipts with scan-to-track QR codes.
- **🔍 Instant AJAX Search & Status Filter Toolbar**: Live filter complaints by status (*Pending, In Progress, Resolved*) or search term.
- **⭐ 5-Star Resolution Rating & Reviews**: Citizens rate field officer resolution quality upon completion.

---

### 🛡️ 2. Municipal Admin Command Center (`/adminlogin`)
- **🗺️ Live Interactive GIS Command Map**: Powered by **Leaflet.js & OpenStreetMap** with color-coded status pins, category icons, and inspection popups.
- **📊 Municipal Efficiency & KPI Analytics**: Real-time resolution rate trackers, average response time metrics, and active ward summaries.
- **Dedicated Inspection Deck (`problem_details.php`)**: Full-page inspection view with side-by-side **Before** (Citizen Report) vs **After** (Field Worker Proof) comparison.
- **Field Officer Dispatch Registry (`workers.php` / `add_worker.php`)**: Register field officers with automated welcome emails and credentials.
- **📥 CSV & Excel Municipal Report Exporter (`export_reports.php`)**: Filter and export complete grievance datasets for city council audits.

---

### 👷 3. Field Officer Portal (`/workerlogin`)
- **Assigned Work Orders Dashboard**: Clean task board with citizen details, severity flags, and location markers.
- **📍 1-Click Turn-by-Turn GPS Route Navigation**: Direct Google Maps driving directions to the exact GPS coordinates.
- **Resolution Proof Submission**: Upload verified **After (Fixed) Photos** to notify administration and citizens instantly.

---

## 🤖 AI & Technology Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           CIVICCONNECT ARCHITECTURE                     │
├──────────────────┬───────────────────────┬──────────────────────────────┤
│  Frontend / UI   │    Backend & APIs     │   Cloud & AI Infrastructure  │
│  ─────────────   │    ──────────────     │   ─────────────────────────  │
│  • HTML5 / CSS3  │ • PHP 8.2 (Modular)   │ • Google Gemini AI (Vision)  │
│  • Plus Jakarta  │ • Google OAuth 2.0    │ • TiDB Cloud Serverless (DB) │
│  • Leaflet GIS   │ • Brevo Email API v3  │ • Render Cloud (Web Hosting) │
│  • Web Speech    │ • Prepared Statements │ • UptimeRobot (Zero-Sleep)   │
└──────────────────┴───────────────────────┴──────────────────────────────┘
```

---

## 🛠️ Complete Tech Stack

* **Hosting & Deployment**: [Render](https://render.com/) (CI/CD connected to GitHub `main` branch).
* **Database**: [TiDB Cloud Serverless MySQL](https://tidbcloud.com/) on AWS (SSL encrypted, 5GB storage, 24/7 permanent uptime).
* **Uptime Monitoring**: [UptimeRobot](https://uptimerobot.com/) (Pings every 5 minutes to eliminate cold-start delays).
* **Artificial Intelligence**: Google Gemini 2.5 Flash & Vision REST APIs.
* **Voice & Audio**: Web Speech API (`SpeechRecognition` + `SpeechSynthesis`).
* **GIS & Maps**: Leaflet.js, OpenStreetMap Cartography Tiles, HTML5 Geolocation API.
* **Authentication**: Google Identity Services (OAuth 2.0 / One-Tap) & Brevo Transactional Email API v3.
* **Backend**: PHP 8.x, Composer, PHPMailer, cURL.
* **Frontend**: Vanilla HTML5, CSS3 Glassmorphism tokens, JavaScript (ES6+), FontAwesome 6, Plus Jakarta Sans.

---

## 🚀 Local Installation & Setup

### 1. Clone the Repository
```bash
git clone https://github.com/aravindkona18090/CivicConnect.git
cd CivicConnect
```

### 2. Configure Environment (`.env`)
Create a `.env` file in the project root:
```env
# Google OAuth 2.0 Client ID
GOOGLE_CLIENT_ID=your_google_client_id.apps.googleusercontent.com

# Google Gemini AI Key
GEMINI_API_KEY=your_gemini_api_key

# TiDB Cloud / MySQL Database Configuration
MYSQLHOST=gateway01.ap-southeast-1.prod.aws.tidbcloud.com
MYSQLPORT=4000
MYSQLUSER=your_db_username
MYSQLPASSWORD=your_db_password
MYSQLDATABASE=test

# Brevo Email API v3
BREVO_API_KEY=xkeysib-your_brevo_api_key
BREVO_SENDER_EMAIL=your_email@gmail.com
BREVO_SENDER_NAME=CivicConnect
```

### 3. Run Locally on XAMPP
1. Move the project to `c:/xampp/htdocs/CivicConnect`.
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Open in your browser: `http://localhost/CivicConnect/`.

---

## 🔑 Default Demo Credentials

| Portal | URL | Username / Email | Password |
| :--- | :--- | :--- | :--- |
| **🛡️ Municipal Admin** | [`/adminlogin/login.php`](https://civicconnect-qlwi.onrender.com/adminlogin/login.php) | `admin` | `admin123` |
| **👤 Citizen Portal** | [`/peoplelogin/login.php`](https://civicconnect-qlwi.onrender.com/peoplelogin/login.php) | `konaaravind18@gmail.com` | `password123` |
| **👷 Field Officer** | [`/workerlogin/login.php`](https://civicconnect-qlwi.onrender.com/workerlogin/login.php) | `suresh.sanitation@civicconnect.gov` | `password123` |

---

## 📜 License
Developed for Smart City Infrastructure Redressal & Civic Engagement. Released under the [MIT License](LICENSE).

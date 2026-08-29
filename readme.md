# 🏛️ CivicConnect - AI-Powered Municipal Grievance & Civic Infrastructure Platform

[![PHP](https://img.shields.io/badge/Backend-PHP%208.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![PyTorch](https://img.shields.io/badge/AI-PyTorch%20MobileNetV3-EE4C2C?style=for-the-badge&logo=pytorch&logoColor=white)](https://pytorch.org/)
[![Google Gemini](https://img.shields.io/badge/Cloud%20AI-Google%20Gemma--4--31B-4285F4?style=for-the-badge&logo=googlecloud&logoColor=white)](https://ai.google.dev/)
[![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)

**CivicConnect** is a modern, full-stack municipal management system designed to streamline citizen issue reporting, automated AI damage classification, field officer task assignment, and transparent resolution tracking.

---

## ✨ Key Features

### 👤 1. Citizen Portal (`/peoplelogin`)
- **Smart Problem Reporting**: Citizens upload photos of civic issues (potholes, garbage dumps, broken streetlights).
- **Automated AI Categorization**: Auto-detects category and computes risk severity (*Low, Medium, High, Critical*).
- **GPS Location Pinning**: Automatically captures precise street address and map coordinates.
- **My Complaints Tracker**: Real-time status timeline (*Pending, In Progress, Completed*).

### 🛡️ 2. Admin Command Center (`/adminlogin`)
- **Unified Management Queue**: Combines Pending Verification and Active In-Progress work orders into one interactive data grid.
- **Dedicated Inspection Page (`problem_details.php`)**: Full-page inspection view with side-by-side **Before** (Citizen Report) vs **After** (Field Worker Proof) photo comparison.
- **Interactive Field Officer Assignment**: Select and assign registered field officers with 1-click automated email work orders.
- **Instant Search & Filter Tabs**: Filter active queue by status (*All Active, Pending Only, In Progress Only*) or live text search.
- **Field Officer Registry (`workers.php`)**: Manually verify and add new field workers with auto-generated secure passwords emailed directly to the worker.

### 👷 3. Field Officer Portal (`/workerlogin`)
- **Assigned Task Board**: View assigned work orders, citizen complaint details, and navigation map coordinates.
- **Completion Proof Upload**: Upload an **After (Fixed) Photo** upon resolving the issue to notify administration and citizens.

---

## 🤖 Dual-Engine AI Vision Integration

CivicConnect utilizes a robust **Dual-Engine AI Vision System**:

1. **Cloud Vision AI (Primary)**: Uses **Google Gemma** via Structured JSON Mode (`response_mime_type: application/json`) to analyze image context, determine civic categories, and calculate urgency severity.
2. **Local Neural Network (Fallback)**: Uses **PyTorch MobileNetV3** (`deep_vision.py`) pre-trained on deep convolutional feature layers. Operates 100% offline without requiring external API access.

---

## 🛠️ Technology Stack

- **Frontend**: HTML5, Vanilla CSS3 (Custom Glassmorphism Design Tokens, Micro-animations), JavaScript, FontAwesome 6, Google Fonts (Plus Jakarta Sans).
- **Backend**: PHP 8.x, Composer, Resend Email API.
- **Database**: MySQL / MariaDB (`civicconnect.sql`).
- **AI / Machine Learning**: Python 3.13, PyTorch (`torch`, `torchvision`), Google Generative AI REST API.

---

## 🚀 Quick Setup & Installation

### 1. Prerequisites
- XAMPP / WAMP server with PHP 8.x and MySQL enabled.
- Python 3.10+ with `torch` and `torchvision` installed (`pip install torch torchvision`).

### 2. Database Setup
1. Open phpMyAdmin (`http://localhost/phpmyadmin/`).
2. Create a database named `civicconnect`.
3. Import the database schema from `civicconnect.sql`.

### 3. Environment Configuration
Set the following environment variables on your server or system:
```bash
# Google Cloud Vision / Gemini API Key
setx GEMINI_API_KEY "YOUR_GOOGLE_GEMINI_API_KEY"

# Resend Mailer API Key (Optional for live email notifications)
setx RESEND_API_KEY "YOUR_RESEND_API_KEY"
```

### 4. Default Login Credentials

#### 🛡️ Admin Portal (`http://localhost/CivicConnect/adminlogin/login.php`)
- **Username / Email**: `admin` or `civicconnect24@gmail.com`
- **Password**: `admin123`

#### 👷 Field Officer Portal (`http://localhost/CivicConnect/workerlogin/login.php`)
- Credentials are auto-generated when added by the Administrator.

---

## 📜 License
Developed for Municipal Infrastructure & Civic Community Enhancement.

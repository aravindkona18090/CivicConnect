<?php
session_start();
include('../db/connection.php');

require __DIR__ . '/../vendor/autoload.php';



// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminlogin.php");
    exit();
}

// Function to send email via PHPMailer
function sendWelcomeEmail($to, $name, $password)
{
    try {
        $resend = Resend::client(getenv('RESEND_API_KEY'));

        $resend->emails->send([
            'from' => 'CivicConnect <onboarding@resend.dev>',
            'to' => [$to],
            'subject' => 'Welcome to Worker Portal',
            'html' => "
                <h3>Hi {$name},</h3>
                <p>Your worker account has been created successfully.</p>

                <p>
                    <b>Email:</b> {$to}<br>
                    <b>Password:</b> {$password}
                </p>

                <p>Please change your password after your first login.</p>

                <br>

                <p>Regards,<br>CivicConnect Admin Team</p>
            "
        ]);

        return true;

    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_worker'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $area = trim($_POST['area']);
    $city = trim($_POST['city']);
    $pincode = trim($_POST['pincode']);
    $category = trim($_POST['category']);

    $defaultPassword = 'Worker@123';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

    if ($name && $email && $mobile && $category) {
        $stmt = $conn->prepare("INSERT INTO workers (name,email,mobile,area,city,pincode,category,password,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
        $stmt->bind_param("ssssssss", $name, $email, $mobile, $area, $city, $pincode, $category, $hashedPassword);
        $stmt->execute();
        $stmt->close();

        // Send welcome email using PHPMailer
        sendWelcomeEmail($email, $name, $defaultPassword);

        header("Location: workers.php?added=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Worker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        header {
            background: #28a745;
            color: #fff;
            padding: 15px;
            text-align: center;
        }

        nav a {
            margin: 0 10px;
            padding: 5px 12px;
            background: #1e7e34;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }

        nav a:hover {
            background: #155724;
        }

        main {
            padding: 20px;
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            margin: 5px 0 15px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            padding: 10px 20px;
            background: #28a745;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #218838;
        }
    </style>
</head>

<body>
    <header>
        <h1>Add New Worker</h1>
        <nav>
            <a href="workers.php">Back to Workers</a>
            <a href="admindashboard.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>
    <main>
        <form method="POST">
            <input type="text" name="name" placeholder="Worker Name" required>
            <input type="email" name="email" placeholder="Worker Email" required>
            <input type="text" name="mobile" placeholder="Worker Mobile" required>
            <input type="text" name="area" placeholder="Area">
            <input type="text" name="city" placeholder="City">
            <input type="text" name="pincode" placeholder="Pincode">
            <select name="category" required>
                <option value="">--Select Category--</option>
                <option value="Electricity">Electricity</option>
                <option value="Water Supply">Water Supply</option>
                <option value="Roads">Roads</option>
                <option value="Sanitation">Sanitation</option>
            </select>
            <button type="submit" name="add_worker">Add Worker</button>
        </form>
    </main>
</body>

</html>
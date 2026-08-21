<?php
session_start();
include("../db/connection.php");

require __DIR__ . '/../vendor/autoload.php';

use Resend;

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminlogin.php");
    exit();
}

// Handle completion toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_completed'])) {

    $problem_id = intval($_POST['problem_id']);
    $completed = $_POST['value'] === '1' ? 1 : 0;

    if ($completed) {

        mysqli_query($conn, "UPDATE problems SET status='Completed', completed_at=NOW() WHERE id='$problem_id'");

        $user_query = mysqli_query($conn, "
            SELECT
                p.description,
                p.category,
                p.street,
                u.username,
                u.email
            FROM problems p
            JOIN people u ON p.user_id = u.id
            WHERE p.id='$problem_id'
        ");

        if ($user_query && mysqli_num_rows($user_query) > 0) {

            $row = mysqli_fetch_assoc($user_query);

            $user_name  = $row['username'];
            $user_email = $row['email'];
            $desc       = $row['description'];
            $category   = $row['category'];
            $street     = $row['street'];

            try {

                $resend = Resend::client(getenv('RESEND_API_KEY'));

                $resend->emails->send([
                    'from' => 'CivicConnect <onboarding@resend.dev>',
                    'to' => [$user_email],
                    'subject' => '✅ CivicConnect - Problem Completed',
                    'html' => "
                        <h2>Dear {$user_name},</h2>

                        <p>Your reported problem has been
                        <strong>marked as completed</strong>.</p>

                        <ul>
                            <li><strong>Description:</strong> {$desc}</li>
                            <li><strong>Category:</strong> {$category}</li>
                            <li><strong>Location:</strong> {$street}</li>
                        </ul>

                        <p>Thank you for helping improve your community! 🎉</p>

                        <p>Regards,<br>CivicConnect Team</p>
                    "
                ]);

            } catch (\Exception $e) {
                error_log("Resend Error: " . $e->getMessage());
            }
        }

    } else {

        mysqli_query($conn, "UPDATE problems SET status='In Progress', completed_at=NULL WHERE id='$problem_id'");

    }

    header("Location: pendingcompletions.php");
    exit();
}

// Fetch problems in progress
$problems_query = "SELECT * FROM problems WHERE status='In Progress' ORDER BY allocated_at DESC";
$problems_result = mysqli_query($conn, $problems_query);

$pageTitle = "Pending Problem Completions ✅";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: #e8f0fe;
    color: #0056b3;
}

/* Header & nav */
header {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: rgba(0,0,0,0.7);
    padding: 20px 30px;
    width: 100%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    backdrop-filter: blur(8px);
}

header h1 {
    margin: 0 0 15px 0;
    font-size: 28px;
    color: #fff;
}

nav {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}

nav a {
    padding: 8px 15px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    color: #fff;
    background: #0056b3;
    transition: 0.3s;
}

nav a:hover {
    background: #00408a;
}

/* Main content */
main {
    max-width: 1000px;
    margin: 20px auto;
    padding: 10px;
}

h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #0056b3;
}

/* Cards */
.card {
    background: rgba(255,255,255,0.15);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    backdrop-filter: blur(8px);
    transition: transform 0.3s;
}

.card:hover {
    transform: translateY(-5px);
}

.card img, .clickable-image {
    max-width: 200px;
    border-radius: 5px;
    display: block;
    margin-top: 5px;
    cursor: pointer;
    transition: transform 0.2s;
}

.clickable-image:hover {
    transform: scale(1.05);
}

.status {
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 4px;
    display: inline-block;
    margin-top: 5px;
    background: #ffeaa7;
}

/* Buttons */
button {
    padding: 8px 16px;
    margin-top: 5px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: 0.3s;
}

button:hover { background: #0056b3; }
form { display: inline-block; margin-right: 5px; }
</style>
</head>
<body>

<header>
    <h1><?php echo $pageTitle; ?></h1>
    <nav>
        <a href="admindashboard.php">Dashboard</a>
        <a href="pendingcompletions.php">Pending Completions</a>
        <a href="allproblems.php">All Problems</a>
        <a href="completedproblems.php">Completed Problems</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<main>
    <h2>Problems Awaiting Completion</h2>

    <?php if (mysqli_num_rows($problems_result) > 0): ?>
        <?php while ($problem = mysqli_fetch_assoc($problems_result)): ?>
            <div class="card">
                <p><strong>ID:</strong> <?php echo $problem['id']; ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($problem['description']); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($problem['category']); ?></p>
                <p><strong>Location:</strong>
                    <?php echo htmlspecialchars($problem['street'] . ', ' . $problem['area'] . ', ' . $problem['city'] . ' - ' . $problem['pincode']); ?>
                </p>
                <?php if (!empty($problem['photo'])): ?>
                    <p><strong>Before Photo:</strong><br>
                        <img src="<?php echo $problem['photo']; ?>" alt="Before Photo" class="clickable-image">
                    </p>
                <?php endif; ?>

                <?php if (!empty($problem['after_photo'])): ?>
                    <p><strong>After Photo:</strong><br>
                        <img src="<?php echo $problem['after_photo']; ?>" alt="After Photo" class="clickable-image">
                    </p>
                <?php endif; ?>

                <p class="status"><?php echo $problem['status']; ?></p>

                <form method="POST">
                    <input type="hidden" name="problem_id" value="<?php echo $problem['id']; ?>">
                    <input type="hidden" name="value" value="1">
                    <button type="submit" name="toggle_completed">Mark Completed</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center;">No problems awaiting completion.</p>
    <?php endif; ?>
</main>

</body>
</html>

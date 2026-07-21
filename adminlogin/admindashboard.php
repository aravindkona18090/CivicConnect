<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include("../db/connection.php");
require __DIR__ . '/../vendor/autoload.php';

// Function to send mail
function sendUserMail($to, $name, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'civicconnectmailer@gmail.com';
        $mail->Password = 'frkiicwjfugwpqrp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('civicconnectmailer@gmail.com', 'CivicConnect');
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
    } catch (Exception $e) {
        // Log error
    }
}

// Fetch user info helper
function getUserDetails($conn, $problem_id) {
    $query = "SELECT p.id, p.description, p.category, u.username, u.email 
              FROM problems p 
              JOIN people u ON p.user_id = u.id 
              WHERE p.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $problem_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Handle admin actions (Accept, Complete, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $problem_id = intval($_POST['problem_id']);
    $user = getUserDetails($conn, $problem_id);

    if (isset($_POST['confirm_problem'])) {
        mysqli_query($conn, "UPDATE problems SET status='In Progress', allocated_at=NOW() WHERE id='$problem_id'");
        if ($user) {
            $subject = "CivicConnect - Problem Accepted (ID: {$user['id']})";
            $body = "<p>Dear <strong>{$user['username']}</strong>,</p>
                     <p>Your reported problem <em>({$user['description']})</em> in category <strong>{$user['category']}</strong> has been <b>Accepted</b> and is now <b>In Progress</b>.</p>";
            sendUserMail($user['email'], $user['username'], $subject, $body);
        }
    }

    if (isset($_POST['complete_problem'])) {
        mysqli_query($conn, "UPDATE problems SET status='Completed' WHERE id='$problem_id'");
        if ($user) {
            $subject = "CivicConnect - Problem Completed (ID: {$user['id']})";
            $body = "<p>Dear <strong>{$user['username']}</strong>,</p>
                     <p>Your reported problem <em>({$user['description']})</em> has been <b>Completed</b>.</p>";
            sendUserMail($user['email'], $user['username'], $subject, $body);
        }
    }

    if (isset($_POST['delete_problem'])) {
        mysqli_query($conn, "DELETE FROM problems WHERE id='$problem_id'");
        if ($user) {
            $subject = "CivicConnect - Problem Rejected (ID: {$user['id']})";
            $body = "<p>Dear <strong>{$user['username']}</strong>,</p>
                     <p>Your reported problem <em>({$user['description']})</em> was <b>Rejected/Removed</b>.</p>";
            sendUserMail($user['email'], $user['username'], $subject, $body);
        }
    }

    header("Location: admindashboard.php");
    exit();
}

// Fetch problems
$problems_result = mysqli_query($conn, "SELECT * FROM problems WHERE status IN ('Pending') ORDER BY created_at DESC");
$pageTitle = "Admin Dashboard 👮‍♂️";
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
    font-family: 'Poppins', sans-serif;
    background: #e8f0fe;
    margin: 0;
    color: #0056b3;
}

/* Header & Navigation */
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
    margin: 0;
    padding: 8px 15px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    color: #fff;
    background: rgba(0,123,255,0.7);
    transition: 0.3s;
}

nav a:hover {
    background: rgba(0,123,255,1);
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

/* Card style */
.card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.12); }

.card img {
    max-width: 150px;
    border-radius: 5px;
    margin-top: 5px;
}

.status {
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 4px;
    display: inline-block;
    margin-top: 5px;
}

.status-pending { background: #f1c40f; color: #fff; }
.status-inprogress { background: #3498db; color: #fff; }
.status-completed { background: #2ecc71; color: #fff; }

button {
    padding: 8px 16px;
    margin-top: 5px;
    background: #0056b3;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: 0.3s;
}

button:hover { background: #00408a; }
button.delete-btn { background: #e74c3c; }
button.delete-btn:hover { background: #c0392b; }

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
    <h2>Pending Problems</h2>
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
                    <p><strong>Photo:</strong><br>
                        <img src="<?php echo $problem['photo']; ?>" alt="Problem Photo">
                    </p>
                <?php endif; ?>
                <p><strong>Reported At:</strong> <?php echo $problem['created_at']; ?></p>
                <?php if ($problem['allocated_at']): ?>
                    <p><strong>Allocated At:</strong> <?php echo $problem['allocated_at']; ?></p>
                <?php endif; ?>
                <p class="status <?php echo strtolower(str_replace(' ', '', $problem['status'])); ?>">
                    <?php echo $problem['status']; ?>
                </p>
                <?php if ($problem['status'] === 'Pending'): ?>
                    <form method="POST">
                        <input type="hidden" name="problem_id" value="<?php echo $problem['id']; ?>">
                        <button type="submit" name="confirm_problem">Mark Accepted</button>
                    </form>
                <?php endif; ?>
                <?php if ($problem['status'] === 'In Progress'): ?>
                    <form method="POST">
                        <input type="hidden" name="problem_id" value="<?php echo $problem['id']; ?>">
                        <button type="submit" name="complete_problem">Mark Completed</button>
                    </form>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="problem_id" value="<?php echo $problem['id']; ?>">
                    <button type="submit" name="delete_problem" class="delete-btn" onclick="return confirm('Are you sure?');">Delete</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center;">No problems to manage.</p>
    <?php endif; ?>
</main>

</body>
</html>

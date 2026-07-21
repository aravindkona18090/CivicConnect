<?php
session_start();
include('../db/connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminlogin.php");
    exit();
}

// Fetch all problems
$query = "
    SELECT id, user_id, description, category, street, area, city, pincode, 
           photo, status, created_at
    FROM problems
    ORDER BY created_at DESC
";
$result = mysqli_query($conn, $query);
$pageTitle = "Admin Dashboard - All Problems";
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

/* Header */
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

/* Card-like table rows */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 12px;
}

th {
    text-align: left;
    padding: 12px;
    font-weight: 600;
    color: #fff;
    background: #0056b3;
    border-radius: 8px 8px 0 0;
}

td {
    padding: 12px;
    background: rgba(255,255,255,0.15);
    border-radius: 8px;
}

tr td:first-child {
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
}

tr td:last-child {
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
}

tr:hover td {
    background: rgba(255,255,255,0.25);
}

img {
    max-width: 100px;
    border-radius: 5px;
}

/* Responsive */
@media (max-width: 768px) {
    table, thead, tbody, th, td, tr { display: block; }
    th { position: sticky; top: 0; }
    td { border: none; padding: 10px 5px; }
}
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
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Description</th>
                <th>Category</th>
                <th>Location</th>
                <th>Photo</th>
                <th>Status</th>
                <th>Reported At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td><?php echo htmlspecialchars($row['street'] . ', ' . $row['area'] . ', ' . $row['city'] . ' - ' . $row['pincode']); ?></td>
                        <td>
                            <?php if (!empty($row['photo'])): ?>
                                <?php
                                $photoPath = $row['photo'];
                                if (str_starts_with($photoPath, "../")) {
                                    $photoPath = substr($photoPath, 3);
                                }
                                $photoURL = '../' . $photoPath;
                                ?>
                                <img src="<?php echo htmlspecialchars($photoURL); ?>" alt="Problem Photo">
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?php echo $row['status']; ?></td>
                        <td><?php echo $row['created_at']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center;">No problems reported yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>

</body>
</html>

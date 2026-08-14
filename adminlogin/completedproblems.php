<?php
session_start();
include("../db/connection.php");

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminlogin.php");
    exit();
}

// Fetch completed problems
$completed_query = "
    SELECT * FROM problems 
    WHERE status='Completed'
    ORDER BY completed_at DESC
";
$completed_result = mysqli_query($conn, $completed_query);
$pageTitle = "Completed Problems ✅";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
/* Base styles */
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

/* Card style */
.card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.12);
}

.card img {
    max-width: 150px;
    border-radius: 5px;
    margin-top: 5px;
}

/* Status badge */
.status {
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 4px;
    display: inline-block;
    margin-top: 5px;
    background: #2ecc71;
    color: #fff;
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
    <h2>Completed Problems</h2>

    <?php if(mysqli_num_rows($completed_result) > 0): ?>
        <?php while($problem = mysqli_fetch_assoc($completed_result)): ?>
            <div class="card">
                <p><strong>ID:</strong> <?php echo $problem['id']; ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($problem['description']); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($problem['category']); ?></p>
                <p><strong>Location:</strong>
                    <?php echo htmlspecialchars($problem['street'] . ', ' . $problem['area'] . ', ' . $problem['city'] . ' - ' . $problem['pincode']); ?>
                </p>

                <?php if(!empty($problem['photo'])): ?>
                    <p><strong>Before Photo:</strong><br>
                        <img src="<?php echo $problem['photo']; ?>" alt="Before Photo">
                    </p>
                <?php endif; ?>

                <?php if(!empty($problem['after_photo'])): ?>
                    <p><strong>After Photo:</strong><br>
                        <img src="<?php echo $problem['after_photo']; ?>" alt="After Photo">
                    </p>
                <?php endif; ?>

                <p><strong>Allocated At:</strong> <?php echo $problem['allocated_at']; ?></p>
                <p><strong>Completed At:</strong> <?php echo $problem['completed_at']; ?></p>
                <p class="status"><?php echo $problem['status']; ?></p>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center;">No completed problems yet.</p>
    <?php endif; ?>
</main>

</body>
</html>

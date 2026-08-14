<?php
session_start();
include('../db/connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminlogin.php");
    exit();
}

// Fetch all workers
$result = mysqli_query($conn, "SELECT * FROM workers ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workers Management</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; color: #333; }
        header { background: #28a745; color: #fff; padding: 15px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        header h1 { margin: 0; font-size: 22px; }
        nav { margin-top: 10px; }
        nav a { margin: 0 10px; padding: 5px 12px; background: #1e7e34; color: #fff; text-decoration: none; border-radius: 4px; transition: 0.3s; }
        nav a:hover { background: #155724; }
        main { padding: 20px; }
        h2 { margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        button.add-btn { padding: 8px 16px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button.add-btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.1); border-radius: 5px; overflow: hidden; }
        th { background: #28a745; color: #fff; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; font-size: 14px; }
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #f1f7ff; }
    </style>
</head>
<body>
<header>
    <h1>Workers Management</h1>
    <p>Welcome, <?php echo $_SESSION['adminname']; ?></p>
    <nav>
        <a href="admindashboard.php">Dashboard</a>
        <a href="pendingcompletions.php">Pending Completions</a>
        <a href="allproblems.php">All Problems</a>
        <a href="workers.php">Workers</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<main>
    <h2>
        All Workers
        <a href="add_worker.php"><button class="add-btn">Add New Worker</button></a>
    </h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Category</th>
                <th>Added On</th>
            </tr>
        </thead>
        <tbody>
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td><?php echo $row['created_at']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">No workers found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>

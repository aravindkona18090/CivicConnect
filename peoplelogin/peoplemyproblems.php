<?php
session_start();
include("../db/connection.php");
include("lang.php"); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../peoplelogin.php");
    exit();
}

$selectedLang = $_SESSION['language'] ?? 'en';
$user_id = $_SESSION['user_id'];

$problems_query = "SELECT * FROM problems WHERE user_id='$user_id' ORDER BY id DESC";
$problems_result = mysqli_query($conn, $problems_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $lang[$selectedLang]['my_problems']; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: #fff;
    color: #000;
}

/* Header */
header {
    background: #fff;
    padding: 15px 40px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1000;
}

header h2 {
    margin: 0;
    font-weight: 700;
    font-size: 1.5rem;
    color: #0056b3;
}

nav a {
    color: #555;
    margin-left: 20px;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

nav a:hover {
    color: #0056b3;
    text-decoration: underline;
}

form select {
    padding: 6px 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-weight: 600;
    cursor: pointer;
}

/* Main content */
main {
    max-width: 900px;
    margin: 40px auto;
    background: #fff;
    padding: 35px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

h3 {
    font-size: 2rem;
    font-weight: 700;
    color: #0056b3;
    margin-bottom: 30px;
    padding-bottom: 10px;
    border-bottom: 3px solid #0056b3;
    display: inline-block;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
}

th {
    background: #0056b3;
    color: #fff;
    font-weight: 600;
}

tr:nth-child(even) {
    background: #f9f9f9;
}

p {
    text-align: center;
    font-weight: 600;
    padding: 10px;
}
</style>
</head>
<body>

<header>
    <h2><?php echo $lang[$selectedLang]['welcome']; ?>, <?php echo $_SESSION['username']; ?> 👋</h2>
    <nav>
        <a href="peopledashboard.php"><?php echo $lang[$selectedLang]['dashboard']; ?></a>
        <a href="peoplemyproblems.php"><?php echo $lang[$selectedLang]['my_problems']; ?></a>
        <a href="peopleprofile.php"><?php echo $lang[$selectedLang]['profile']; ?></a>
        <a href="../logout.php"><?php echo $lang[$selectedLang]['logout']; ?></a>
    </nav>
    <form method="POST" style="display:inline;">
        <select name="language" onchange="this.form.submit()">
            <option value="en" <?php if($selectedLang=='en') echo 'selected'; ?>>English</option>
            <option value="te" <?php if($selectedLang=='te') echo 'selected'; ?>>తెలుగు</option>
            <option value="hn" <?php if($selectedLang=='hn') echo 'selected'; ?>>हिंदी</option>
            <option value="kn" <?php if($selectedLang=='kn') echo 'selected'; ?>>ಕನ್ನಡ</option>
        </select>
    </form>
</header>

<main>
    <h3>📋 <?php echo $lang[$selectedLang]['my_problems']; ?></h3>

    <?php if (mysqli_num_rows($problems_result) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo $lang[$selectedLang]['category']; ?></th>
                    <th><?php echo $lang[$selectedLang]['description']; ?></th>
                    <th><?php echo $lang[$selectedLang]['location']; ?></th>
                    <th><?php echo $lang[$selectedLang]['pincode']; ?></th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($problems_result)): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['category']; ?></td>
                        <td><?php echo $row['description']; ?></td>
                        <td><?php echo $row['street'] . ', ' . $row['area'] . ', ' . $row['city']; ?></td>
                        <td><?php echo $row['pincode']; ?></td>
                        <td><?php echo $row['status'] ?? 'Pending'; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No problems reported yet.</p>
    <?php endif; ?>
</main>

</body>
</html>

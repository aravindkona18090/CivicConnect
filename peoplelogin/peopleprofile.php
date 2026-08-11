<?php
session_start();
include("../db/connection.php");
include("lang.php"); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../peoplelogin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_personal'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $dob = mysqli_real_escape_string($conn, $_POST['dob']);
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
        $_SESSION['username'] = $name;

        $pic_sql = "";
        if (!empty($_FILES['profile_pic']['name'])) {
            $profile_pic = "uploads/" . time() . "_" . basename($_FILES['profile_pic']['name']);
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], "../" . $profile_pic);
            $pic_sql = ", profile_pic='$profile_pic'";
        }
        mysqli_query($conn, "UPDATE people SET name='$name', dob='$dob', gender='$gender', mobile='$mobile' $pic_sql WHERE id='$user_id'");
        header("Location: peopleprofile.php");
        exit();
    }

    if (isset($_POST['update_account'])) {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        mysqli_query($conn, "UPDATE people SET email='$email' WHERE id='$user_id'");
        header("Location: peopleprofile.php");
        exit();
    }

    if (isset($_POST['update_address'])) {
        $door = mysqli_real_escape_string($conn, $_POST['door']);
        $street = mysqli_real_escape_string($conn, $_POST['street']);
        $city = mysqli_real_escape_string($conn, $_POST['city']);
        $state = mysqli_real_escape_string($conn, $_POST['state']);
        $pincode = mysqli_real_escape_string($conn, $_POST['pincode']);
        mysqli_query($conn, "UPDATE people SET door='$door', street='$street', city='$city', state='$state', pincode='$pincode' WHERE id='$user_id'");
        header("Location: peopleprofile.php");
        exit();
    }

    if (isset($_POST['update_preferences'])) {
        $language = mysqli_real_escape_string($conn, $_POST['language']);
        mysqli_query($conn, "UPDATE people SET language='$language' WHERE id='$user_id'");
        $_SESSION['language'] = $language;
        header("Location: peopleprofile.php");
        exit();
    }
}

$selectedLang = $_SESSION['language'] ?? 'en';
$user_query = "SELECT * FROM people WHERE id='$user_id'";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);
?>

<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $lang[$selectedLang]['profile']; ?></title>
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

header h1 {
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
    padding: 35px 40px;
}

section {
    background: #fff;
    border-radius: 12px;
    padding: 25px 30px;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

h2 {
    font-size: 1.75rem;
    color: #0056b3;
    margin-bottom: 20px;
    border-bottom: 3px solid #0056b3;
    padding-bottom: 5px;
}

input, select {
    width: 70%;
    padding: 8px;
    margin: 5px 0;
    border-radius: 6px;
    border: 1px solid #ccc;
}

button {
    padding: 10px 20px;
    background: #0056b3;
    color: #fff;
    font-weight: 600;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 10px;
    transition: background 0.3s;
}

button:hover {
    background: #00408a;
}

img { border-radius: 8px; margin-top: 10px; }

p { margin: 5px 0; font-weight: 500; }
</style>
</head>
<body>

<header>
  <a href="../index.php" class="logo-group" style="display:flex; align-items:center; gap:12px; text-decoration:none;">
    <div class="logo-badge" style="width:40px; height:40px; background:linear-gradient(135deg, #10b981 0%, #0284c7 100%); border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:1.1rem; box-shadow:0 4px 14px rgba(16, 185, 129, 0.35);"><i class="fa-solid fa-handshake"></i></div>
    <span class="logo-title" style="font-size:1.3rem; font-weight:800; color:#0f172a;">Civic<span style="color:#0284c7;">Connect</span></span>
  </a>
  <nav>
    <a href="peopledashboard.php"><i class="fa-solid fa-house"></i> <?php echo $lang[$selectedLang]['dashboard'] ?? 'Dashboard'; ?></a>
    <a href="peoplemyproblems.php"><i class="fa-solid fa-list-check"></i> <?php echo $lang[$selectedLang]['my_problems'] ?? 'My Problems'; ?></a>
    <a href="peopleprofile.php"><i class="fa-solid fa-user"></i> <?php echo $lang[$selectedLang]['profile'] ?? 'Profile'; ?></a>
    <a href="../logout.php" style="color:#ef4444;"><i class="fa-solid fa-right-from-bracket"></i> <?php echo $lang[$selectedLang]['logout'] ?? 'Logout'; ?></a>
  </nav>
  <form method="POST" style="display:inline-flex; align-items:center; gap:6px;">
    <select name="language" onchange="this.form.submit()" style="padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1; font-weight:600; font-family:inherit; cursor:pointer;" title="Select Language">
      <option value="en" <?php if ($selectedLang=='en') echo 'selected'; ?>>🌐 English</option>
      <option value="te" <?php if ($selectedLang=='te') echo 'selected'; ?>>🌐 తెలుగు (Telugu)</option>
      <option value="hn" <?php if ($selectedLang=='hn') echo 'selected'; ?>>🌐 हिंदी (Hindi)</option>
      <option value="kn" <?php if ($selectedLang=='kn') echo 'selected'; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
    </select>
  </form>
</header>

<main>

<!-- Personal Info -->
<section>
<h2><?php echo $lang[$selectedLang]['personal_details']; ?></h2>
<?php if (!empty($user['name'])): ?>
<p><strong><?php echo $lang[$selectedLang]['name']; ?>:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
<p><strong><?php echo $lang[$selectedLang]['dob']; ?>:</strong> <?php echo htmlspecialchars($user['dob']); ?></p>
<p><strong><?php echo $lang[$selectedLang]['gender']; ?>:</strong> <?php echo htmlspecialchars($user['gender']); ?></p>
<p><strong><?php echo $lang[$selectedLang]['phone']; ?>:</strong> <?php echo htmlspecialchars($user['mobile']); ?></p>
<?php if (!empty($user['profile_pic'])): ?>
<p><strong><?php echo $lang[$selectedLang]['profile_picture']; ?>:</strong><br>
<img src="../<?php echo $user['profile_pic']; ?>" width="100">
</p>
<?php endif; ?>
<?php else: ?>
<form method="POST" enctype="multipart/form-data">
<label><?php echo $lang[$selectedLang]['name']; ?>:</label><br>
<input type="text" name="name" value="<?php echo $user['name'] ?? ''; ?>" required><br>
<label><?php echo $lang[$selectedLang]['dob']; ?>:</label><br>
<input type="date" name="dob" value="<?php echo $user['dob'] ?? ''; ?>" required><br>
<label><?php echo $lang[$selectedLang]['gender']; ?>:</label><br>
<select name="gender" required>
<option value="">--Select--</option>
<option value="Male" <?php if (($user['gender'] ?? '')=='Male') echo 'selected'; ?>>Male</option>
<option value="Female" <?php if (($user['gender'] ?? '')=='Female') echo 'selected'; ?>>Female</option>
<option value="Other" <?php if (($user['gender'] ?? '')=='Other') echo 'selected'; ?>>Other</option>
</select><br>
<label><?php echo $lang[$selectedLang]['phone']; ?>:</label><br>
<input type="text" name="mobile" value="<?php echo $user['mobile'] ?? ''; ?>" required><br>
<label><?php echo $lang[$selectedLang]['profile_picture']; ?>:</label><br>
<input type="file" name="profile_pic"><br><br>
<button type="submit" name="update_personal"><?php echo $lang[$selectedLang]['save']; ?></button>
</form>
<?php endif; ?>
</section>

<!-- Account Info -->
<section>
<h2><?php echo $lang[$selectedLang]['account_details']; ?></h2>
<?php if (!empty($user['email'])): ?>
<p><strong><?php echo $lang[$selectedLang]['email']; ?>:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
<p><strong>Password:</strong> <a href="changepassword.php">Change Password</a></p>
<?php else: ?>
<form method="POST">
<label><?php echo $lang[$selectedLang]['email']; ?>:</label><br>
<input type="email" name="email" required><br><br>
<button type="submit" name="update_account"><?php echo $lang[$selectedLang]['save']; ?></button>
</form>
<?php endif; ?>
</section>

<!-- Address Info -->
<section>
<h2><?php echo $lang[$selectedLang]['address']; ?></h2>
<?php if (!empty($user['door'])): ?>
<p><strong><?php echo $lang[$selectedLang]['door_number']; ?>:</strong> <?php echo htmlspecialchars($user['door']); ?></p>
<p><strong><?php echo $lang[$selectedLang]['street']; ?>:</strong> <?php echo htmlspecialchars($user['street']); ?></p>
<p><strong><?php echo $lang[$selectedLang]['city']; ?>:</strong> <?php echo htmlspecialchars($user['city']); ?></p>
<p><strong><?php echo $lang[$selectedLang]['state']; ?>:</strong> <?php echo htmlspecialchars($user['state']); ?></p>
<p><strong><?php echo $lang[$selectedLang]['pincode']; ?>:</strong> <?php echo htmlspecialchars($user['pincode']); ?></p>
<?php else: ?>
<form method="POST">
<label><?php echo $lang[$selectedLang]['door_number']; ?>:</label><br>
<input type="text" name="door" value="<?php echo $user['door'] ?? ''; ?>" required><br>
<label><?php echo $lang[$selectedLang]['street']; ?>:</label><br>
<input type="text" name="street" value="<?php echo $user['street'] ?? ''; ?>" required><br>
<label><?php echo $lang[$selectedLang]['city']; ?>:</label><br>
<input type="text" name="city" value="<?php echo $user['city'] ?? ''; ?>" required><br>
<label><?php echo $lang[$selectedLang]['state']; ?>:</label><br>
<input type="text" name="state" value="<?php echo $user['state'] ?? ''; ?>" required><br>
<label><?php echo $lang[$selectedLang]['pincode']; ?>:</label><br>
<input type="text" name="pincode" value="<?php echo $user['pincode'] ?? ''; ?>" required><br><br>
<button type="submit" name="update_address"><?php echo $lang[$selectedLang]['save']; ?></button>
</form>
<?php endif; ?>
</section>

<!-- Preferences -->
<section>
<h2><?php echo $lang[$selectedLang]['preferences'] ?? "Preferences / Settings"; ?></h2>
<form method="POST">
<label><?php echo $lang[$selectedLang]['select_language'] ?? "Select Language"; ?>:</label><br>
<select name="language" required>
<option value="en" <?php if (($user['language'] ?? $_SESSION['language'])=='en') echo 'selected'; ?>>English</option>
<option value="te" <?php if (($user['language'] ?? $_SESSION['language'])=='te') echo 'selected'; ?>>Telugu</option>
<option value="hn" <?php if (($user['language'] ?? $_SESSION['language'])=='hn') echo 'selected'; ?>>Hindi</option>
<option value="kn" <?php if (($user['language'] ?? $_SESSION['language'])=='kn') echo 'selected'; ?>>Kannada</option>
</select><br><br>
<button type="submit" name="update_preferences"><?php echo $lang[$selectedLang]['save']; ?></button>
</form>
</section>

</main>
</body>
</html>

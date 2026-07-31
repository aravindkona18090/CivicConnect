<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . "/../db/connection.php";
require_once __DIR__ . "/lang.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Language switch
if (isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
}
$selectedLang = $_SESSION['language'] ?? 'en';

// Define session variables for use outside the submission block
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'] ?? '';
$user_name = $_SESSION['username'] ?? '';
$msg = "";

if (isset($_POST['submit_problem'])) {
    // Retrieve and sanitize input values
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $lat = (double)($_POST['latitude'] ?? 0);
    $lng = (double)($_POST['longitude'] ?? 0);
    $address = trim($_POST['selectedAddress'] ?? '');

    // --- 1. File upload ---
    $photo = "";
    if (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "" && $_FILES['photo']['error'] == 0) {
        $uploadDir = __DIR__ . "/../uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES['photo']['name']);
        $photo = "../uploads/" . $fileName; // store relative path in DB
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
            $msg = "❌ Failed to upload photo! Check directory permissions.";
            $photo = "";
        }
    }

    // --- 2. Duplicate check within 45 meters (0.045 km) ---
    $radius = 0.045;

    $check_query = "
        SELECT id, description, category,
        (6371 * acos(
            cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) +
            sin(radians(?)) * sin(radians(lat))
        )) AS distance
        FROM problems
        WHERE category = ?
        HAVING distance <= ?
        LIMIT 1
    ";

    $stmt_check = $conn->prepare($check_query);
    if ($stmt_check === false) {
        $msg = "❌ DB error: " . $conn->error;
    } else {
        $stmt_check->bind_param("dddsd", $lat, $lng, $lat, $category, $radius);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result && mysqli_num_rows($result) > 0) {
            $msg = "❌ " . ($lang[$selectedLang]['duplicate_report'] ?? "This problem is already reported!!");
        } else {
            $status = 'Pending';
            $created_at = date('Y-m-d H:i:s');

            // --- 3. Insert problem ---
            $insert_query = "INSERT INTO problems (user_id, description, category, street, lat, lng, photo, status, created_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_query);
            if ($stmt_insert === false) {
                $msg = "❌ DB error: " . $conn->error;
            } else {
                $stmt_insert->bind_param("isssddsss", $user_id, $description, $category, $address, $lat, $lng, $photo, $status, $created_at);

                if ($stmt_insert->execute()) {
                    $problem_id = $stmt_insert->insert_id;
                    $msg = "✅ " . ($lang[$selectedLang]['report_new'] ?? "Problem reported") . " successfully!";

                    // --- 4. Send emails ---
                    try {
                        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = getenv('MAIL_USERNAME');
        $mail->Password = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->Timeout = 10;
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'html';

                        // User Confirmation Email
                        $mail->setFrom('civicconnectmailer@gmail.com', 'CivicConnect');
                        if (!empty($user_email)) {
                            $mail->addAddress($user_email, $user_name);
                            $mail->isHTML(true);
                            $mail->Subject = "CivicConnect - Problem Submission Confirmation";
                            $mail->Body = "<h2>Dear " . htmlspecialchars($user_name) . ",</h2>
                                <p>Your problem has been successfully submitted (ID: <strong>$problem_id</strong>).</p>
                                <ul>
                                    <li><strong>Description:</strong> " . htmlspecialchars($description) . "</li>
                                    <li><strong>Category:</strong> " . htmlspecialchars($category) . "</li>
                                    <li><strong>Location:</strong> " . htmlspecialchars($address) . "</li>
                                </ul>
                                <p>We will notify you once a worker is assigned and when your problem is resolved.</p>
                                <p>Regards,<br/>CivicConnect Team</p>";
                            $mail->send();
                        }

                        // Admin Notification Email
                        $admin = 'civicconnect24@gmail.com';
                        $mail->clearAddresses();
                        $mail->addAddress($admin, 'Admin');
                        $mail->Subject = "CivicConnect - New Problem Submitted (ID: $problem_id)";
                        $mail->Body = "<h2>New Problem Submitted</h2>
                            <p>A new problem has been reported and requires review.</p>
                            <ul>
                                <li><strong>Problem ID:</strong> $problem_id</li>
                                <li><strong>Description:</strong> " . htmlspecialchars($description) . "</li>
                                <li><strong>Category:</strong> " . htmlspecialchars($category) . "</li>
                                <li><strong>Location:</strong> " . htmlspecialchars($address) . "</li>
                                <li><strong>Reported By:</strong> " . htmlspecialchars($user_name) . " (" . htmlspecialchars($user_email) . ")</li>
                            </ul>";
                        $mail->send();
                    } catch (Exception $e) {
                        // Log $e->getMessage() if you have logging; don't break user flow
                    }
                } else {
                    $msg = "❌ Error submitting problem: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            }
        }
        if ($result instanceof mysqli_result) {
            $result->free();
        }
        $stmt_check->close();
    }
}
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $lang[$selectedLang]['dashboard'] ?? 'Dashboard'; ?></title>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
/* --- Professional Light Theme Styling --- */
:root {
  --primary-color: #e8f0fe;  /* Modern Blue */
  --secondary-color: #50E3C2; /* Accent Teal */
  --light-bg: #F0F4F8;
  --card-bg: #FFFFFF;
  --text-dark: #333333;
  --text-light: #555555;
  --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
}

body {
  margin: 0;
  font-family: 'Poppins', sans-serif;
  background: #e8f0fe;
  color: var(--text-dark);
}

/* ---------------- Header ---------------- */
header {
  background: var(--card-bg);
  padding: 15px 40px;
  box-shadow: var(--shadow-light);
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
  color: var(--primary-color);
}

nav a {
  color: var(--text-light);
  margin-left: 20px;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.3s ease;
}

nav a:hover {
  color: var(--primary-color);
  text-decoration: underline;
}

/* ---------------- Main ---------------- */
main {
  max-width: 800px;
  margin: 40px auto;
  background: var(--card-bg);
  padding: 35px 40px;
  border-radius: 12px;
  box-shadow: var(--shadow-light);
}

h3 {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-dark);
  margin-bottom: 30px;
  padding-bottom: 10px;
  border-bottom: 3px solid var(--secondary-color);
  display: inline-block;
}

#map {
  height: 400px;
  margin-bottom: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* ---------------- Form ---------------- */
form label {
  display: block;
  margin-top: 15px;
  margin-bottom: 5px;
  font-weight: 600;
  color: var(--text-light);
}

form input[type="text"],
form select,
form textarea,
form input[type="file"] {
  width: 100%;
  padding: 12px;
  border: 1px solid #D1D9E6;
  border-radius: 8px;
  font-size: 1rem;
  box-sizing: border-box;
  transition: border-color 0.3s ease;
}

form input[type="text"]:focus,
form select:focus,
form textarea:focus {
  border-color: var(--primary-color);
  outline: none;
}

/* Search box + button */
#searchBox {
  border-radius: 8px 0 0 8px;
  width: calc(100% - 100px);
  display: inline-block;
  margin-right: -4px;
}

#searchBtn {
  width: 100px;
  padding: 12px;
  border-radius: 0 8px 8px 0;
  margin: 0;
  vertical-align: top;
}

/* Buttons */
form button[type="submit"],
form button[type="button"] {
  background: var(--primary-color);
  color: white;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: 0.3s;
  margin-top: 20px;
}

form button[type="button"] {
  margin-top: 0;
}

form button:hover {
  background: #0056b3;
  transform: translateY(-1px);
}

/* Messages */
p {
  padding: 10px;
  border-radius: 6px;
  font-weight: 600;
  text-align: center;
  margin-bottom: 20px;
}

p[style*="color:green"] {
  background-color: #e6ffe6;
  color: #008000 !important;
  border: 1px solid #008000;
}

p[style*="color:red"] {
  background-color: #ffe6e6;
  color: #ff0000 !important;
  border: 1px solid #ff0000;
}

</style>
</head>
<body>

<header>
  <h2>
    <?php echo $lang[$selectedLang]['welcome'] ?? 'Welcome'; ?>, 
    <span style="font-weight:600;"><?php echo htmlspecialchars($user_name); ?></span> 👋
  </h2>
  <nav>
    <a href="peopledashboard.php"><?php echo $lang[$selectedLang]['dashboard'] ?? 'Dashboard'; ?></a>
    <a href="peoplemyproblems.php"><?php echo $lang[$selectedLang]['my_problems'] ?? 'My Problems'; ?></a>
    <a href="peopleprofile.php"><?php echo $lang[$selectedLang]['profile'] ?? 'Profile'; ?></a>
    <a href="../logout.php"><?php echo $lang[$selectedLang]['logout'] ?? 'Logout'; ?></a>
  </nav>
  <form method="POST" style="display:inline;">
    <select name="language" onchange="this.form.submit()">
      <option value="en" <?php if ($selectedLang=='en') echo 'selected'; ?>>English</option>
      <option value="te" <?php if ($selectedLang=='te') echo 'selected'; ?>>తెలుగు</option>
      <option value="hn" <?php if ($selectedLang=='hn') echo 'selected'; ?>>हिंदी</option>
      <option value="kn" <?php if ($selectedLang=='kn') echo 'selected'; ?>>ಕನ್ನಡ</option>
    </select>
  </form>
</header>


<main>
<?php 
// Display stylized messages
if(strpos($msg, '✅') !== false) {
    echo "<p style='color:green;'>" . htmlspecialchars($msg) . "</p>";
} elseif (strpos($msg, '❌') !== false) {
    echo "<p style='color:red;'>" . htmlspecialchars($msg) . "</p>";
}
?>

<section>
<h3><?php echo $lang[$selectedLang]['report_new'] ?? 'Report a New Problem'; ?></h3>
<form method="POST" enctype="multipart/form-data">
<label for="category"><?php echo $lang[$selectedLang]['category'] ?? 'Category'; ?>:</label>
<select name="category" id="category" required>
<option value="">-- <?php echo $lang[$selectedLang]['select'] ?? 'Select'; ?> --</option>
<option value="Road">Road</option>
<option value="Water">Water</option>
<option value="Electricity">Electricity</option>
<option value="Sanitation">Sanitation</option>
<option value="Other">Other</option>
</select>

<label for="description"><?php echo $lang[$selectedLang]['description'] ?? 'Description'; ?>:</label>
<textarea name="description" id="description" rows="4" required placeholder="<?php echo $lang[$selectedLang]['desc_placeholder'] ?? 'Provide a brief but detailed description of the problem.'; ?>"></textarea>

<label>Search Location:</label>
<div style="display: flex;">
    <input type="text" id="searchBox" placeholder="<?php echo $lang[$selectedLang]['search_placeholder'] ?? 'Enter address or landmark'; ?>" style="border-radius: 8px 0 0 8px; width: 100%;">
    <button type="button" id="searchBtn" style="border-radius: 0 8px 8px 0; width: 100px; margin-top: 0;">Search</button>
</div>

<label><?php echo $lang[$selectedLang]['select_location'] ?? 'Select Problem Location (Click on Map)'; ?>:</label>
<div id="map"></div>
<input type="hidden" name="latitude" id="latitude" required>
<input type="hidden" name="longitude" id="longitude" required>
<input type="hidden" name="selectedAddress" id="selectedAddress" required>

<label for="photo"><?php echo $lang[$selectedLang]['upload_photo'] ?? 'Upload Photo (Optional)'; ?>:</label>
<input type="file" name="photo" id="photo" accept="image/*">

<button type="submit" name="submit_problem"><?php echo $lang[$selectedLang]['submit'] ?? 'Submit Report'; ?></button>
</form>
</section>
</main>

<script>
$(document).ready(function(){
    var map = L.map('map').setView([13.28510073, 77.59980869], 13);
    var marker;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Geolocation
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position){
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
            map.setView([lat,lng], 15);
        });
    }

    // Map click
    map.on('click', function(e){
        var lat = e.latlng.lat;
        var lng = e.latlng.lng;
        if(marker) map.removeLayer(marker);
        marker = L.marker([lat,lng]).addTo(map);

        $('#latitude').val(lat);
        $('#longitude').val(lng);

        // Reverse geocode
        $.getJSON(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`, function(data){
            $('#selectedAddress').val(data.display_name);
        });
    });

    // Search address
    $('#searchBtn').click(function(){
        var query = $('#searchBox').val();
        if(query){
            $.getJSON(`https://nominatim.openstreetmap.org/search?format=json&q=${query}`, function(data){
                if(data && data.length > 0){
                    var lat = data[0].lat;
                    var lon = data[0].lon;
                    map.setView([lat, lon], 16);
                    if(marker) map.removeLayer(marker);
                    marker = L.marker([lat, lon]).addTo(map);

                    $('#latitude').val(lat);
                    $('#longitude').val(lon);
                    $('#selectedAddress').val(data[0].display_name);
                } else {
                    alert('Location not found');
                }
            });
        }
    });
});
</script>

</body>
</html>
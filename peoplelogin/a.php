<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include("../db/connection.php");
require __DIR__ . '/../vendor/autoload.php';
include("lang.php"); // must be included first

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Language switch
if (isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
}
$selectedLang = $_SESSION['language'] ?? 'en';

// Handle problem submission
$msg = "";
if (isset($_POST['submit_problem'])) {
    $user_id = $_SESSION['user_id'];
    $category = mysqli_real_escape_string($conn, $_POST['category'] ?? '');
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $lat = mysqli_real_escape_string($conn, $_POST['lat'] ?? '');
    $lng = mysqli_real_escape_string($conn, $_POST['lng'] ?? '');
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');

    // File upload
    $photo = "";
    if (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
        $uploadDir = "../uploads/"; 
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $photo = $uploadDir . time() . "_" . basename($_FILES['photo']['name']);
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo)) {
            $msg = "❌ Failed to upload photo!";
            $photo = "";
        }
    }

    // Insert into DB
    $query = "INSERT INTO problems (user_id, description, category, lat, lng, address, photo) 
              VALUES ('$user_id', '$description', '$category', '$lat', '$lng', '$address', '$photo')";
    if (mysqli_query($conn, $query)) {
        $problem_id = mysqli_insert_id($conn);
        $msg = "✅ " . $lang[$selectedLang]['report_new'] . " successfully!";
    } else {
        $msg = "❌ Error: " . mysqli_error($conn);
    }

    // You can keep PHPMailer code for sending emails here (same as before)
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $lang[$selectedLang]['dashboard']; ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol/ol.css">
<script src="https://cdn.jsdelivr.net/npm/ol/ol.js"></script>
<style>
    body { margin:0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    header { background: rgba(0,0,0,0.6); color:white; padding:15px; display:flex; justify-content:space-between; align-items:center;}
    header h2 { margin:0;}
    nav a { color:white; text-decoration:none; margin:0 10px; font-weight:bold;}
    nav a:hover { text-decoration:underline;}
    main { max-width:800px; margin:30px auto; background:rgba(255,255,255,0.9); padding:25px; border-radius:12px;}
    #map { width:100%; height:400px; margin-bottom:15px; border:1px solid #ccc;}
    form input, form select, form textarea, form button { width:100%; padding:10px; margin:8px 0; border-radius:8px; border:1px solid #ccc; font-size:15px;}
    form button { background:#007BFF; color:white; font-weight:bold; border:none; cursor:pointer; transition:0.3s;}
    form button:hover { background:#0056b3;}
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
</header>

<main>
<?php if ($msg) echo "<p style='color:green;'>$msg</p>"; ?>

<h3><?php echo $lang[$selectedLang]['report_new']; ?></h3>

<form method="POST" enctype="multipart/form-data">
    <label><?php echo $lang[$selectedLang]['category']; ?>:</label>
    <select name="category" required>
        <option value="">--Select--</option>
        <option value="Road">Road</option>
        <option value="Water">Water</option>
        <option value="Electricity">Electricity</option>
        <option value="Sanitation">Sanitation</option>
        <option value="Other">Other</option>
    </select>

    <label><?php echo $lang[$selectedLang]['description']; ?>:</label>
    <textarea name="description" rows="4" required></textarea>

    <label>Select Problem Location:</label>
    <div id="map"></div>
    <input type="hidden" name="lat" id="lat">
    <input type="hidden" name="lng" id="lng">
    <input type="text" name="address" id="address" placeholder="Selected Address" readonly required>

    <label><?php echo $lang[$selectedLang]['upload_photo']; ?>:</label>
    <input type="file" name="photo" accept="image/*">

    <button type="submit" name="submit_problem"><?php echo $lang[$selectedLang]['submit']; ?></button>
</form>
</main>

<script>
    // Initialize map
    var map = new ol.Map({
        target: 'map',
        layers: [
            new ol.layer.Tile({ source: new ol.source.OSM() })
        ],
        view: new ol.View({ center: ol.proj.fromLonLat([0,0]), zoom: 2 })
    });

    var markerLayer = new ol.layer.Vector({ source: new ol.source.Vector() });
    map.addLayer(markerLayer);
    var marker;

    function addMarker(lon, lat) {
        markerLayer.getSource().clear();
        marker = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([lon, lat])) });
        markerLayer.getSource().addFeature(marker);
        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lon;

        // Reverse Geocoding using Nominatim
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
        .then(response => response.json())
        .then(data => { document.getElementById('address').value = data.display_name; });
    }

    // Get user location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                var lon = pos.coords.longitude;
                var lat = pos.coords.latitude;
                map.getView().setCenter(ol.proj.fromLonLat([lon, lat]));
                map.getView().setZoom(16);
                addMarker(lon, lat);
            }, 
            function(err) { console.error(err); alert('Could not get your location'); }, 
            { enableHighAccuracy:true }
        );
    }

    // Click on map to move marker
    map.on('click', function(evt) {
        var coord = ol.proj.toLonLat(evt.coordinate);
        addMarker(coord[0], coord[1]);
    });
</script>
</body>
</html>
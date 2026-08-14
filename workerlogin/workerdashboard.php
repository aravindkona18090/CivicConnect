<?php
session_start();
include('../db/connection.php');

// Check if worker is logged in
if (!isset($_SESSION['worker_id'])) {
    header("Location: workerlogin.php");
    exit();
}

$worker_id = $_SESSION['worker_id'];

// Handle after photo upload and submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_completion'])){
    $problem_id = intval($_POST['problem_id']);
    $status = $_POST['status'];

    if(isset($_FILES['after_photo']) && $_FILES['after_photo']['error'] === 0){
        $ext = pathinfo($_FILES['after_photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('after_').'.'.$ext;
        $target_dir = "../uploads/after_photos/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir.$filename;

        if(move_uploaded_file($_FILES['after_photo']['tmp_name'], $target_file)){
            mysqli_query($conn, "UPDATE problems 
                                 SET after_photo='$target_file', status='$status' 
                                 WHERE id='$problem_id' AND worker_id='$worker_id'");
        }
    }
}

// Fetch all problems assigned to this worker
$problems_query = "
    SELECT * FROM problems
    WHERE worker_id='$worker_id'
    ORDER BY created_at DESC
";
$problems_result = mysqli_query($conn, $problems_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Worker Dashboard</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; margin:0; padding:0;}
header { background: #007bff; color: #fff; padding:15px; text-align:center;}
nav a { color:#fff; margin:0 10px; text-decoration:none;}
main { max-width: 1000px; margin:20px auto; padding:10px;}
.card { background:#fff; border-radius:8px; padding:15px; margin-bottom:15px; box-shadow:0 0 5px rgba(0,0,0,0.1);}
.card img { max-width:200px; border-radius:5px; display:block; margin-top:5px;}
.card .status { font-weight:bold; padding:4px 8px; border-radius:4px; display:inline-block;}
.status-pending { background:#f7d794; }
.status-inprogress { background:#ffeaa7; }
.status-completed { background:#55efc4; }
button { padding:6px 12px; margin-top:5px; background:#007bff; color:#fff; border:none; border-radius:4px; cursor:pointer;}
button:hover { opacity:0.9; }
input[type=file] { margin-top:5px; }

/* Modal styles */
.modal { display:none; position:fixed; z-index:1000; padding-top:100px; left:0; top:0; width:100%; height:100%; overflow:auto; background-color: rgba(0,0,0,0.4);}
.modal-content { background:#fff; margin:auto; padding:20px; border-radius:5px; width:80%; max-width:500px; position:relative; }
.close { position:absolute; right:10px; top:10px; font-size:24px; cursor:pointer; }
</style>
</head>
<body>

<header>
    <h1>Worker Dashboard</h1>
    <p>Welcome, <?php echo $_SESSION['workername']; ?> | <a href="../logout.php" style="color:#fff;">Logout</a></p>
</header>

<main>
    <h2>Your Assigned Problems</h2>

    <?php if(mysqli_num_rows($problems_result) > 0): ?>
        <?php while($problem = mysqli_fetch_assoc($problems_result)): ?>
            <div class="card">
                <p><strong>ID:</strong> <?php echo $problem['id']; ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($problem['description']); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($problem['category']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($problem['street'].', '.$problem['area'].', '.$problem['city'].' - '.$problem['pincode']); ?></p>
                
                <?php if(!empty($problem['photo'])): ?>
                    <p><strong>Before Photo:</strong><br><img src="<?php echo $problem['photo']; ?>" alt="Before Photo"></p>
                <?php endif; ?>

                <?php if(!empty($problem['after_photo'])): ?>
                    <p><strong>After Photo:</strong><br><img src="<?php echo $problem['after_photo']; ?>" alt="After Photo"></p>
                <?php endif; ?>

                <p class="status 
                    <?php 
                        echo $problem['status']=='Pending' ? 'status-pending' : 
                            ($problem['status']=='In Progress' ? 'status-inprogress' : 'status-completed'); 
                    ?>">
                    <?php echo $problem['status']; ?>
                </p>

                <?php if($problem['status']=='Pending' || $problem['status']=='In Progress'): ?>
                    <button class="openModal" data-id="<?php echo $problem['id']; ?>">Mark as Completed</button>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No assigned problems.</p>
    <?php endif; ?>
</main>

<!-- Modal -->
<div id="completionModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Submit Work Completion</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" id="modal_problem_id" name="problem_id" value="">
            <label>Upload After Photo:</label><br>
            <input type="file" name="after_photo" accept="image/*" required><br><br>
            <label>Status:</label>
            <select name="status" required>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
            </select><br><br>
            <button type="submit" name="submit_completion">Submit</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function(){
    // Open modal
    $(".openModal").click(function(){
        var problemId = $(this).data("id");
        $("#modal_problem_id").val(problemId);
        $("#completionModal").fadeIn();
    });

    // Close modal
    $(".close").click(function(){
        $("#completionModal").fadeOut();
    });

    // Close when clicking outside modal
    $(window).click(function(e){
        if($(e.target).hasClass("modal")){
            $("#completionModal").fadeOut();
        }
    });
});
</script>

</body>
</html>

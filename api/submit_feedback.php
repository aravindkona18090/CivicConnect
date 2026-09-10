<?php
// api/submit_feedback.php - Citizen Resolution Rating & Feedback API
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../db/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to submit feedback.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$problemId = intval($input['problem_id'] ?? 0);
$rating = intval($input['rating'] ?? 5);
$comment = trim($input['comment'] ?? '');
$userId = intval($_SESSION['user_id']);

if ($problemId <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid feedback parameters.']);
    exit();
}

// Verify this problem belongs to user and is completed
$check = mysqli_query($conn, "SELECT id, status FROM problems WHERE id='$problemId' AND user_id='$userId'");
if (!$check || mysqli_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Grievance not found or unauthorized.']);
    exit();
}

// Check if feedback table exists, create if not
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `citizen_feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `problem_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `rating` INT NOT NULL DEFAULT 5,
    `comment` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_problem_user` (`problem_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$stmt = $conn->prepare("INSERT INTO citizen_feedback (problem_id, user_id, rating, comment) 
                        VALUES (?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)");
$stmt->bind_param("iiis", $problemId, $userId, $rating, $comment);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your feedback has been recorded (+50 Karma XP awarded).',
        'rating' => $rating
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save feedback.']);
}
$stmt->close();

<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || 
    !in_array($_SESSION['role'], ['student', 'faculty'])) {
    header("Location: ../auth/login.php");
    exit;
}

$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $complaint_id = $_POST['complaint_id'] ?? NULL;
    $comment = $_POST['message'] ?? '';
    $rating = $_POST['rating'] ?? 0;

    if (!empty($complaint_id) && !empty($rating)) {
        $sql = "INSERT INTO feedback (user_id, complaint_id, comment, rating) 
                VALUES (?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iisi", $_SESSION['user_id'], $complaint_id, $comment, $rating);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Feedback submitted successfully!";
        } else {
            $success = "Error submitting feedback: " . mysqli_error($conn);
        }
    } else {
        $success = "Please select a complaint and provide a rating.";
    }
}

/* =========================
   GET UNREAD COUNT (For Sidebar)
========================= */
$unread_res = mysqli_query($conn, 
    "SELECT COUNT(*) as cnt 
    FROM complaint_chat 
    JOIN complaints ON complaint_chat.complaint_id = complaints.id 
    WHERE complaints.user_id = {$_SESSION['user_id']} 
    AND sender_role = 'counselor' 
    AND is_read = 0");
$unread_row = mysqli_fetch_assoc($unread_res);
$unread_count = $unread_row['cnt'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Feedback</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/script.js" defer></script>
</head>

<?php
$role = $_SESSION['role'] ?? '';
$theme = '';
if ($role === 'counselor') $theme = 'theme-counselor';
elseif ($role === 'faculty') $theme = 'theme-faculty';
elseif ($role === 'student') $theme = 'theme-student';
?>

<body class="main-page <?= $theme ?>">
<div class="home-container">

    <!-- SIDEBAR -->
    <div class="sidebar">
    <h2>User Dashboard</h2>
    <button class="side-btn" onclick="location.href='home.php'">
      Home
</button>

   

    <button class="side-btn" onclick="location.href='complaint_status.php'">
      Complaint Status
    </button>
     <button class="side-btn" onclick="location.href='chat.php'">
  Chat with Counselor <?= ($unread_count > 0) ? "<span class='badge'>$unread_count</span>" : "" ?>
</button>

   <button class="side-btn feedback-btn active">
  Feedback
</button>

    <div class="dark-mode-toggle">
        <span style="font-size: 14px;">Dark Mode</span>
        <label class="switch">
            <input type="checkbox" id="darkModeToggle" onclick="toggleDarkMode()">
            <span class="slider"></span>
        </label>
    </div>

    <div class="user-box">
      <div class="user-name">
        <?= htmlspecialchars($_SESSION['name']) ?>
      </div>
      <div class="user-id">
        <?= htmlspecialchars($_SESSION['email'] ?? '') ?>
      </div>
      <div class="user-id">
        <?= htmlspecialchars($_SESSION['admission_no'] ?? '') ?>
      </div>
      <button class="logout-btn"
        onclick="location.href='../auth/logout.php'">
        Logout
      </button>
    </div>
  </div>

    <!-- MAIN CONTENT -->
    <div class="content home-layout">
        <div class="home-main">
            <h1>Submit Feedback</h1>

            <?php if ($success) { ?>
                <p style="color:green;"><b><?= $success ?></b></p>
            <?php } ?>

            <form method="POST">

                <label><b>Select Complaint:</b></label><br>
                <select name="complaint_id" required>
                    <option value="">-- Select a complaint --</option>
                    <?php
                    $complaints = mysqli_query($conn, 
                        "SELECT id, category, subject FROM complaints 
                         WHERE user_id = {$_SESSION['user_id']}
                         ORDER BY created_at DESC");
                    
                    while ($complaint = mysqli_fetch_assoc($complaints)) {
                        echo "<option value='{$complaint['id']}'>";
                        echo htmlspecialchars($complaint['category']) . " - " . htmlspecialchars($complaint['subject']);
                        echo "</option>";
                    }
                    ?>
                </select>

                <br><br>

                <label><b>Rating:</b></label><br>
                <select name="rating" required>
                    <option value="">Select</option>
                    <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                    <option value="4">⭐⭐⭐⭐ Good</option>
                    <option value="3">⭐⭐⭐ Average</option>
                    <option value="2">⭐⭐ Poor</option>
                    <option value="1">⭐ Very Poor</option>
                </select>

                <br><br>

                <label><b>Your Feedback:</b></label><br>
                <textarea name="message" rows="5"></textarea>

                <br><br>

                <button type="submit" class="next-btn">
                    Submit Feedback
                </button>

            </form>
        </div>

        <div class="home-info container">
            <h2>Why Feedback?</h2>
            <p>
                Your feedback helps us improve the complaint system and
                provide better service.
            </p>
        </div>
    </div>

</div>
</body>
</html>

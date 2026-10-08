<?php
session_start();
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'], ['student', 'faculty'])
) {

    header("Location: ../auth/login.php");
    exit;
}

include "../config/db.php";
$user_id = $_SESSION['user_id'];

/* =========================
   GET UNREAD COUNT (For Sidebar)
========================= */
$unread_res = mysqli_query($conn, 
    "SELECT COUNT(*) as cnt 
    FROM complaint_chat 
    JOIN complaints ON complaint_chat.complaint_id = complaints.id 
    WHERE complaints.user_id = $user_id 
    AND sender_role = 'counselor' 
    AND is_read = 0");
$unread_row = mysqli_fetch_assoc($unread_res);
$unread_count = $unread_row['cnt'];

/* =========================
   GET STATUS UPDATE COUNT (For Sidebar)
========================= */
$status_update_res = mysqli_query($conn, 
    "SELECT COUNT(*) as cnt 
    FROM complaints 
    WHERE user_id = $user_id 
    AND user_unread = 1");
$status_update_row = mysqli_fetch_assoc($status_update_res);
$status_update_count = $status_update_row['cnt'];
?>

<!DOCTYPE html>
<html>
<head>
  <title>Student Dashboard</title>
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
    <h2>User panel</h2>

    <button class="side-btn active">Home</button>
 

    <button class="side-btn" onclick="location.href='complaint_status.php'">
      Complaint Status
    </button>
      <button class="side-btn" onclick="location.href='chat.php'">
  Chat with Counselor <?= ($unread_count > 0) ? "<span class='badge'>$unread_count</span>" : "" ?>
</button>
    <button class="side-btn" onclick="location.href='feedback.php'">
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
      <h1>Select Complaint Category</h1>

      <button class="complaint-btn"
        onclick="selectCategory(this,'Internal Behavioral Misconduct')">
        Internal Behavioral Misconduct
      </button>

      <button class="complaint-btn"
        onclick="selectCategory(this,'Academic')">
        Academic
      </button>

      <button class="complaint-btn"
        onclick="selectCategory(this,'Administrative')">
        Administrative
      </button>

      <button class="complaint-btn"
        onclick="selectCategory(this,'Others')">
        Others
      </button>

      <button class="next-btn" onclick="goNext()">
        Next
      </button>
    </div>

    <div class="home-info container">
      <h2>About This Platform</h2>
      <p>
        This platform allows students to submit complaints,
        track their status, and ensures secure handling.
      </p>
    </div>
  </div>

</div>
</body>
</html>

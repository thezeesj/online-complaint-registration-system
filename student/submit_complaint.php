<?php
session_start();
if (!isset($_SESSION['user_id']) || 
    !in_array($_SESSION['role'], ['student', 'faculty'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Submit Complaint</title>
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

    <button class="side-btn" onclick="location.href='home.php'">Home</button>
    <button class="side-btn" onclick="location.href='complaint_status.php'">Complaint Status</button>
    <button class="side-btn" onclick="location.href='chat.php'">Chat with Counselor</button>
 <button class="side-btn" onclick="location.href='feedback.php'">
    Feedback
</button>
    <div class="user-box">
      <div class="user-name"><?= htmlspecialchars((string)($_SESSION['name'] ?? '')) ?></div>
      <div class="user-id">
        <?= htmlspecialchars((string)(!empty($_SESSION['email']) ? $_SESSION['email'] : ($_SESSION['admission_no'] ?? ''))) ?>
      </div>
      <button class="logout-btn" onclick="location.href='../auth/logout.php'">Logout</button>
    </div>
  </div>

  <!-- MAIN CONTENT -->
  <div class="content home-layout">

    <div class="home-main">
      <h1>Submit Complaint</h1>

      <p><strong>Category:</strong>
        <span id="selectedCategory">Loading...</span>
      </p>

     <form action="process_complaint.php" method="POST" enctype="multipart/form-data">

  <input type="hidden" id="category" name="category">

  <div id="subjectWrapper">
    <input type="text" id="subjectInput" name="subject" placeholder="Subject" required>
  </div>

  <textarea name="description" placeholder="Describe your complaint" required></textarea>

  <!-- PROOF UPLOAD -->
  <label><b>Upload Proof (Image/PDF):</b></label>
  <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf">

  <button type="submit" class="next-btn">Submit Complaint</button>

</form>

    </div>

    <!-- RIGHT INFO BOX -->
    <div class="home-info container">
      <h2>Guidelines</h2>
      <p>
        Please provide clear subject and detailed explanation
        to help us resolve your issue faster.
      </p>
    </div>

  </div>

</div>

<!-- Email Warning Modal -->
<div id="emailWarningModal" class="modal-overlay">
  <div class="modal-content">
    <h3>⚠️ Important: Email Registration</h3>
    <p>You are currently logged in with your Admission Number. We highly recommend adding an email address for:</p>
    <ul style="text-align: left; margin: 10px 0; padding-left: 20px;">
      <li><b>Status Notifications:</b> Get instant updates when your complaint status changes.</li>
      <li><b>Password Recovery:</b> Securely reset your password if you ever forget it.</li>
    </ul>
    
    <div id="emailFormContainer">
      <input type="email" id="modalEmailInput" placeholder="Enter your active Gmail ID">
      <p style="font-size: 0.85em; color: #666; margin-top: 5px;">This email will be used for <b>notifications and password recovery</b> only.</p>
    </div>

    <div class="modal-btns">
      <button class="modal-btn primary" id="btnEnterEmail">Enter active email ID</button>
      <button class="modal-btn secondary" id="btnNoThanks">No thanks, submit anyway</button>
      <button class="modal-btn primary" id="btnSaveSubmit" style="display:none;">Save & Submit</button>
    </div>
  </div>
</div>

<script>
// Check if user has email
const userHasEmail = <?= json_encode(!empty($_SESSION['email'])) ?>;
const complaintForm = document.querySelector('form[action="process_complaint.php"]');
const warningModal = document.getElementById('emailWarningModal');
const emailFormContainer = document.getElementById('emailFormContainer');
const btnEnterEmail = document.getElementById('btnEnterEmail');
const btnNoThanks = document.getElementById('btnNoThanks');
const btnSaveSubmit = document.getElementById('btnSaveSubmit');
const modalEmailInput = document.getElementById('modalEmailInput');

complaintForm.addEventListener('submit', function(e) {
  if (!userHasEmail) {
    e.preventDefault(); // Intercept submission
    warningModal.style.display = 'flex';
  }
});

btnEnterEmail.onclick = function() {
  btnEnterEmail.style.display = 'none';
  btnNoThanks.style.display = 'none';
  emailFormContainer.style.display = 'block';
  btnSaveSubmit.style.display = 'block';
};

btnNoThanks.onclick = function() {
  // Submit the form anyway
  complaintForm.submit();
};

btnSaveSubmit.onclick = function() {
  const email = modalEmailInput.value.trim();
  if (!email || !email.includes('@')) {
    alert("Please enter a valid email address.");
    return;
  }

  // Update email via AJAX
  fetch('update_email_ajax.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'email=' + encodeURIComponent(email)
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      complaintForm.submit();
    } else {
      alert("Error updating email: " + data.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert("An error occurred. Please try again.");
  });
};

// Extract category from URL parameter
window.onload = function() {
  const urlParams = new URLSearchParams(window.location.search);
  const category = urlParams.get('category');
  
  if (category) {
    const decodedCategory = decodeURIComponent(category);
    document.getElementById('selectedCategory').textContent = decodedCategory;
    document.getElementById('category').value = decodedCategory;
    
    // Toggle Subject Visibility
    const subjectWrapper = document.getElementById('subjectWrapper');
    const subjectInput = document.getElementById('subjectInput');
    
    if (decodedCategory === 'Others') {
      subjectWrapper.style.display = 'block';
      subjectInput.value = '';
      subjectInput.required = true;
    } else {
      subjectWrapper.style.display = 'none';
      subjectInput.value = decodedCategory;
      subjectInput.required = false;
    }
    
  } else {
    // Fallback to session storage
    const sessionCategory = sessionStorage.getItem('selectedCategory');
    if (sessionCategory) {
      document.getElementById('selectedCategory').textContent = sessionCategory;
      document.getElementById('category').value = sessionCategory;
      
      // Toggle logic for session fallback too
      const subjectWrapper = document.getElementById('subjectWrapper');
      const subjectInput = document.getElementById('subjectInput');
      if (sessionCategory === 'Others') {
        subjectWrapper.style.display = 'block';
        subjectInput.value = '';
        subjectInput.required = true;
      } else {
        subjectWrapper.style.display = 'none';
        subjectInput.value = sessionCategory;
        subjectInput.required = false;
      }
    } else {
      document.getElementById('selectedCategory').textContent = 'Not selected';
    }
  }
};
</script>

</body>
</html>

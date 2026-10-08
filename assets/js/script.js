/**
 * Change input type based on selected ID type
 * Switches between Email and Admission Number fields
 */
function changeInput() {
  const idType = document.getElementById("idType").value;
  const idInput = document.getElementById("idInput");

  if (idType === "admission") {
    idInput.type = "text";
    idInput.name = "email";
    idInput.placeholder = "Admission No";
    idInput.value = "";
  } else {
    idInput.type = "email";
    idInput.name = "email";
    idInput.placeholder = "Email ID";
    idInput.value = "";
  }
}

/**
 * Select complaint category and highlight button
 */
function selectCategory(button, category) {
  // Remove active class from all buttons
  const buttons = document.querySelectorAll('.complaint-btn');
  buttons.forEach(btn => btn.classList.remove('active'));

  // Add active class to clicked button
  button.classList.add('active');

  // Store selected category in session storage
  sessionStorage.setItem('selectedCategory', category);
}

/**
 * Go to next page (submit complaint form)
 */
function goNext() {
  const selectedCategory = sessionStorage.getItem('selectedCategory');

  if (!selectedCategory) {
    alert('Please select a complaint category first!');
    return;
  }

  // Navigate to complaint submission page
  window.location.href = 'submit_complaint.php?category=' + encodeURIComponent(selectedCategory);
}

/**
 * Toggle complaint details visibility
 */
function toggleDetails(elementId, complaintId) {
  const element = document.getElementById(elementId);
  if (!element) return;

  if (element.style.display === "none") {
    element.style.display = "block";

    // Mark as read AJAX
    if (complaintId) {
      const badge = document.getElementById('badge_' + complaintId);
      if (badge) {
        fetch('mark_complaint_read.php?id=' + complaintId)
          .then(() => badge.style.display = 'none');
      }
    }
  } else {
    element.style.display = "none";
  }
}

/**
 * Display error message
 */
function showError(message) {
  const errorMsg = document.getElementById("errorMsg");
  if (errorMsg) {
    errorMsg.textContent = message;
    errorMsg.style.display = "block";
  }
}

/**
 * Hide error message
 */
function hideError() {
  const errorMsg = document.getElementById("errorMsg");
  if (errorMsg) {
    errorMsg.style.display = "none";
  }
}

/**
 * Validate email format
 */
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

/**
 * Validate password strength (minimum 6 characters)
 */
function isValidPassword(password) {
  return password.length >= 6;
}

/**
 * Form validation for login
 */
function validateLoginForm() {
  const idInput = document.querySelector('input[name="email"]')?.value.trim();
  const password = document.querySelector('input[name="password"]')?.value;

  if (!idInput || !password) {
    showError("All fields are required.");
    return false;
  }

  if (!isValidEmail(idInput)) {
    showError("Please enter a valid email address.");
    return false;
  }

  return true;
}

/**
 * Form validation for signup
 */
function validateSignupForm() {
  const name = document.querySelector('input[name="name"]')?.value.trim();
  const email = document.querySelector('input[name="email"]')?.value.trim();
  const password = document.querySelector('input[name="password"]')?.value;
  const role = document.querySelector('select[name="role"]')?.value;

  if (!name || !email || !password || !role) {
    showError("All fields are required.");
    return false;
  }

  if (name.length < 2) {
    showError("Name must be at least 2 characters long.");
    return false;
  }

  if (!isValidEmail(email)) {
    showError("Please enter a valid email address.");
    return false;
  }

  if (!isValidPassword(password)) {
    showError("Password must be at least 6 characters long.");
    return false;
  }

  return true;
}

/**
 * DARK MODE TOGGLE
 */
function toggleDarkMode() {
  document.body.classList.toggle('dark-mode');

  // Save preference
  if (document.body.classList.contains('dark-mode')) {
    localStorage.setItem('theme', 'dark');
  } else {
    localStorage.setItem('theme', 'light');
  }
}

// Apply saved theme on load
document.addEventListener('DOMContentLoaded', () => {
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'dark') {
    document.body.classList.add('dark-mode');
    // Ensure switch is checked if it exists
    const toggle = document.getElementById('darkModeToggle');
    if (toggle) toggle.checked = true;
  }
});

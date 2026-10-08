<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$page = $_GET['page'] ?? 'new';

function status_class($status) {
    switch($status) {
        case 'Submitted': return 'text-submitted';
        case 'Under Review': return 'text-under-review';
        case 'Resolved': return 'text-resolved';
        case 'Rejected': return 'text-rejected';
        default: return '';
    }
}

/* =====================================================
   NOTIFICATION COUNTS (For Sidebar Badges)
===================================================== */
// 1. New Complaints (Counselor hasn't viewed)
$new_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM complaints WHERE counselor_viewed = 0");
$new_count = mysqli_fetch_assoc($new_q)['cnt'];

// 2. Delayed Complaints (Pending > 2 days)
$delay_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM complaints WHERE status NOT IN ('Resolved','Rejected') AND DATEDIFF(NOW(), created_at) >= 2");
$delay_count = mysqli_fetch_assoc($delay_q)['cnt'];

// 3. New Feedback (Admin hasn't viewed)
// Safe check for is_read column first
$column_check = mysqli_query($conn, "SHOW COLUMNS FROM feedback LIKE 'is_read'");
if (mysqli_num_rows($column_check) == 0) {
    mysqli_query($conn, "ALTER TABLE feedback ADD COLUMN is_read TINYINT DEFAULT 0");
}
$feed_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM feedback WHERE is_read = 0");
$feed_count = mysqli_fetch_assoc($feed_q)['cnt'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="../assets/js/script.js" defer></script>
    <style>
        .remind-card {
            border-left: 5px solid #ccc;
        }
        .remind-card.urgent { border-left-color: #ef4444; } /* Red > 7 days */
        .remind-card.warning { border-left-color: #f59e0b; } /* Orange < 7 days */
        
        .chart-container-wrapper {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
    </style>
</head>

<body class="main-page">
<div class="home-container">

<div class="sidebar">
    <h2>Admin Panel</h2>

    <button class="side-btn <?= $page=='new'?'active':'' ?>" onclick="location.href='dashboard.php?page=new'">
        New <?= ($new_count > 0) ? "<span class='badge small'>$new_count</span>" : "" ?>
    </button>
    <button class="side-btn <?= $page=='pending'?'active':'' ?>" onclick="location.href='dashboard.php?page=pending'">Pending</button>
    <button class="side-btn <?= $page=='history'?'active':'' ?>" onclick="location.href='dashboard.php?page=history'">History</button>
    <button class="side-btn <?= $page=='remind'?'active':'' ?>" onclick="location.href='dashboard.php?page=remind'">
        Remind Counselor <?= ($delay_count > 0) ? "<span class='badge small yellow'>$delay_count</span>" : "" ?>
    </button>
    <button class="side-btn <?= $page=='charts'?'active':'' ?>" onclick="location.href='dashboard.php?page=charts'">Charts</button>
    <button class="side-btn <?= $page=='feedbacks'?'active':'' ?>" onclick="location.href='dashboard.php?page=feedbacks'">
        Feedbacks <?= ($feed_count > 0) ? "<span class='badge small'>$feed_count</span>" : "" ?>
    </button>
    
    <div class="dark-mode-toggle">
        <span>Dark Mode</span>
        <label class="switch">
            <input type="checkbox" id="darkModeToggle" onclick="toggleDarkMode()">
            <span class="slider"></span>
        </label>
    </div>

    <div class="user-box">
        <div class="user-name">Administrator</div>
        <div class="user-id">
            <?= htmlspecialchars($_SESSION['email'] ?? '') ?>
        </div>
        <button class="logout-btn" onclick="location.href='../auth/logout.php'">Logout</button>
    </div>
</div>

<div class="content">
<h1>Admin Dashboard</h1>

<?php
/* ================= NEW ================= */
if ($page == 'new') {
    echo "<h2>New Complaints</h2>";
    $res = mysqli_query($conn, "SELECT id, created_at, category, status FROM complaints WHERE counselor_viewed=0 ORDER BY created_at DESC");
    while($row = mysqli_fetch_assoc($res)) {
        echo "<div class='complaint-card'>";
        echo "<p><b>ID:</b> {$row['id']}</p>";
        echo "<p><b>Date:</b> " . format_date($row['created_at']) . "</p>";
        echo "<p><b>Category:</b> ".htmlspecialchars($row['category'])."</p>";
        echo "<p><b>Status:</b> <span class='".status_class($row['status'])."'>{$row['status']}</span></p>";
        echo "</div>";
    }
}

/* ================= PENDING ================= */
if ($page == 'pending') {
    echo "<h2>Pending Complaints</h2>";
    $res = mysqli_query($conn, "SELECT id, created_at, category, status FROM complaints WHERE status NOT IN ('Resolved','Rejected') AND counselor_viewed=1 ORDER BY created_at DESC");
    while($row = mysqli_fetch_assoc($res)) {
        echo "<div class='complaint-card'>";
        echo "<p><b>ID:</b> {$row['id']}</p>";
        echo "<p><b>Date:</b> " . format_date($row['created_at']) . "</p>";
        echo "<p><b>Category:</b> ".htmlspecialchars($row['category'])."</p>";
        echo "<p><b>Status:</b> <span class='".status_class($row['status'])."'>{$row['status']}</span></p>";
        echo "</div>";
    }
}

/* ================= HISTORY ================= */
if ($page == 'history') {
    echo "<h2>History (Resolved / Rejected)</h2>";
    $res = mysqli_query($conn, "SELECT id, created_at, category, status FROM complaints WHERE status IN ('Resolved','Rejected') ORDER BY created_at DESC");
    while($row = mysqli_fetch_assoc($res)) {
        echo "<div class='complaint-card'>";
        echo "<p><b>ID:</b> {$row['id']}</p>";
        echo "<p><b>Date:</b> " . format_date($row['created_at']) . "</p>";
        echo "<p><b>Category:</b> ".htmlspecialchars($row['category'])."</p>";
        echo "<p><b>Status:</b> <span class='".status_class($row['status'])."'>{$row['status']}</span></p>";
        echo "</div>";
    }
}

/* ================= REMIND ================= */
if ($page == 'remind') {
    echo "<h2>Remind Counselor (Pending > 2 Days)</h2>";
    
    // Select complaints pending for more than 2 days
    $res = mysqli_query($conn, "SELECT id, created_at, category, status, DATEDIFF(NOW(), created_at) as days_old 
                                FROM complaints 
                                WHERE status NOT IN ('Resolved','Rejected') 
                                ORDER BY days_old DESC");
    
    while($row = mysqli_fetch_assoc($res)) {
        $days = $row['days_old'];
        $class = ($days > 7) ? 'urgent' : 'warning';
        $color = ($days > 7) ? 'red' : 'orange';
        
        echo "<div class='complaint-card remind-card $class'>";
        echo "<p><b>ID:</b> {$row['id']}</p>";
        echo "<p><b>Category:</b> ".htmlspecialchars($row['category'])."</p>";
        echo "<p><b>Pending for:</b> <span style='color:$color; font-weight:bold;'>$days days</span></p>";
        
        echo "<button class='action-btn small' onclick='sendRemind({$row['id']})'>Send Reminder</button>";
        echo "</div>";
    }
}

/* ================= CHARTS ================= */
if ($page == 'charts') {
    echo "<h2>Analytics</h2>";
    
    // Data Fetching
    
    // 1. Total Counts
    $student_count_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM complaints c JOIN users u ON c.user_id=u.id WHERE u.role='student'");
    $student_count = mysqli_fetch_assoc($student_count_res)['c'];
    
    $faculty_count_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM complaints c JOIN users u ON c.user_id=u.id WHERE u.role='faculty'");
    $faculty_count = mysqli_fetch_assoc($faculty_count_res)['c'];
    
    // 2. Student Categories
    $stud_cat_res = mysqli_query($conn, "SELECT category, COUNT(*) as c FROM complaints c JOIN users u ON c.user_id=u.id WHERE u.role='student' GROUP BY category");
    $stud_cats = []; $stud_data = [];
    while($r = mysqli_fetch_assoc($stud_cat_res)) { $stud_cats[] = $r['category']; $stud_data[] = $r['c']; }
    
    // 3. Faculty Categories
    $fac_cat_res = mysqli_query($conn, "SELECT category, COUNT(*) as c FROM complaints c JOIN users u ON c.user_id=u.id WHERE u.role='faculty' GROUP BY category");
    $fac_cats = []; $fac_data = [];
    while($r = mysqli_fetch_assoc($fac_cat_res)) { $fac_cats[] = $r['category']; $fac_data[] = $r['c']; }
    
    // 4. Rating Distribution
    $rating_res = mysqli_query($conn, "SELECT rating, COUNT(*) as c FROM feedback GROUP BY rating");
    $rating_counts = [0, 0, 0, 0, 0]; // 1 to 5 stars
    while($r = mysqli_fetch_assoc($rating_res)) {
        $r_val = (int)$r['rating'];
        if($r_val >= 1 && $r_val <= 5) {
            $rating_counts[$r_val - 1] = $r['c'];
        }
    }

    // 5. User Counts
    $user_counts_res = mysqli_query($conn, "SELECT role, COUNT(*) as c FROM users WHERE role IN ('student', 'faculty') GROUP BY role");
    $user_data = ['student' => 0, 'faculty' => 0];
    while($row = mysqli_fetch_assoc($user_counts_res)) {
        $user_data[$row['role']] = $row['c'];
    }
    ?>
    
    <div class="chart-container-wrapper">
        <canvas id="totalChart"></canvas>
    </div>
    
    <div style="display:flex; gap:20px;">
        <div class="chart-container-wrapper" style="flex:1;">
            <h3>Student Complaints</h3>
            <canvas id="studentChart"></canvas>
        </div>
        <div class="chart-container-wrapper" style="flex:1;">
            <h3>Faculty Complaints</h3>
            <canvas id="facultyChart"></canvas>
        </div>
    </div>

    <div class="chart-container-wrapper" style="margin-top: 20px;">
        <h3>Feedback Ratings</h3>
        <canvas id="ratingChart"></canvas>
    </div>

    <div class="chart-container-wrapper" style="margin-top: 20px;">
        <h3>Registered Users</h3>
        <canvas id="userChart"></canvas>
    </div>

    <script>
    // Register plugins
    Chart.register(ChartDataLabels);

    // Common options for bar charts (datalabels on top)
    const commonOptions = {
        barPercentage: 0.4,
        categoryPercentage: 0.5,
        plugins: {
            datalabels: {
                color: '#888',
                font: {
                    weight: 'bold',
                    size: 16
                },
                formatter: Math.round,
                anchor: 'end',
                align: 'top',
                offset: 5
            }
        }
    };

    new Chart(document.getElementById('totalChart'), {
        type: 'bar',
        data: {
            labels: ['Students', 'Faculty'],
            datasets: [{
                label: 'Total Complaints',
                data: [<?= $student_count ?>, <?= $faculty_count ?>],
                backgroundColor: ['#3b82f6', '#f59e0b']
            }]
        },
        options: commonOptions
    });
    
    new Chart(document.getElementById('studentChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($stud_cats) ?>,
            datasets: [{
                data: <?= json_encode($stud_data) ?>,
                backgroundColor: ['#ef4444', '#f97316', '#eab308', '#22c55e']
            }]
        },
        options: {
            plugins: {
                datalabels: {
                    color: 'white',
                    font: { weight: 'bold' },
                    formatter: (value, ctx) => value
                }
            }
        }
    });
    
    new Chart(document.getElementById('facultyChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($fac_cats) ?>,
            datasets: [{
                data: <?= json_encode($fac_data) ?>,
                backgroundColor: ['#ef4444', '#f97316', '#eab308', '#22c55e']
            }]
        },
        options: {
            plugins: {
                datalabels: {
                    color: 'white',
                    font: { weight: 'bold' },
                    formatter: (value, ctx) => value
                }
            }
        }
    });

    new Chart(document.getElementById('ratingChart'), {
        type: 'bar',
        data: {
            labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
            datasets: [{
                label: 'Count',
                data: <?= json_encode($rating_counts) ?>,
                backgroundColor: [
                    '#ef4444', // Red
                    '#f97316', // Orange 
                    '#eab308', // Yellow
                    '#84cc16', // Lime
                    '#22c55e'  // Green
                ]
            }]
        },
        options: {
            barPercentage: 0.4,
            categoryPercentage: 0.5,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            },
            plugins: {
                datalabels: {
                    color: '#888',
                    anchor: 'end',
                    align: 'top',
                    offset: 5,
                    font: {
                        weight: 'bold',
                        size: 16
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('userChart'), {
        type: 'bar',
        data: {
            labels: ['Students', 'Faculty'],
            datasets: [{
                label: 'Registered Users',
                data: [<?= $user_data['student'] ?>, <?= $user_data['faculty'] ?>],
                backgroundColor: ['#3b82f6', '#f59e0b']
            }]
        },
        options: commonOptions
    });
    </script>
    <?php
}

/* ================= FEEDBACKS ================= */
if ($page == 'feedbacks') {
    echo "<h2>Student Feedback</h2>";

    // Mark all as read when this page is loaded
    mysqli_query($conn, "UPDATE feedback SET is_read = 1 WHERE is_read = 0");

    $feed_res = mysqli_query($conn, 
        "SELECT f.*, u.name as student_name, c.category, c.subject
         FROM feedback f
         JOIN users u ON f.user_id = u.id
         JOIN complaints c ON f.complaint_id = c.id
         ORDER BY f.id DESC");

    if (mysqli_num_rows($feed_res) == 0) {
        echo "<div class='empty-state'><p>No feedback details available.</p></div>";
    }

    while($row = mysqli_fetch_assoc($feed_res)) {
        $stars = str_repeat("⭐", $row['rating']);
        echo "<div class='complaint-card'>";
        echo "<p><b>Student:</b> " . htmlspecialchars($row['student_name']) . "</p>";
        echo "<p><b>Complaint:</b> " . htmlspecialchars($row['category']) . " - " . htmlspecialchars($row['subject']) . "</p>";
        echo "<p><b>Rating:</b> {$stars} ({$row['rating']}/5)</p>";
        if (!empty($row['comment'])) {
            echo "<p><b>Comment:</b> " . nl2br(htmlspecialchars($row['comment'])) . "</p>";
        }
        echo "<p style='color:#888; font-size:0.8em; margin-top:10px;'>Feedback ID: {$row['id']}</p>";
        echo "</div>";
    }
}
?>

</div>
</div>

<script>
function sendRemind(id) {
    if(confirm("Send reminder to counselor for Complaint #" + id + "?")) {
        fetch('remind.php?id=' + id)
        .then(res => res.text())
        .then(res => {
            alert(res);
        });
    }
}
</script>

</body>
</html>

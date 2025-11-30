<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid event ID.";
    exit();
}
$event_id = intval($_GET['id']);

if ($user_id == 0) {
    echo "Access denied. Please login first.";
    exit();
}

$event_sql = "SELECT * FROM events WHERE id = ?";
$stmt = $conn->prepare($event_sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event_result = $stmt->get_result();

if (!$event_result || $event_result->num_rows == 0) {
    echo "Event not found.";
    exit();
}

$event = $event_result->fetch_assoc();

if ($event['host_id'] != $user_id && $role !== 'admin') {
    echo "You do not have permission to view this event.";
    exit();
}

$invitees_sql = "
    SELECT i.name, i.email, i.status
    FROM invitations i
    WHERE i.event_id = ?
";
$stmt2 = $conn->prepare($invitees_sql);
$stmt2->bind_param("i", $event_id);
$stmt2->execute();
$invitees_result = $stmt2->get_result();

$emails_by_response = [
    'accepted' => [],
    'declined' => [],
    'pending' => []
];

$response_counts = [
    'accepted' => 0,
    'declined' => 0,
    'pending' => 0
];

if ($invitees_result) {
    while ($row = $invitees_result->fetch_assoc()) {
        $resp = strtolower($row['status'] ?? 'pending');
        if (!in_array($resp, ['accepted', 'declined'])) {
            $resp = 'pending';
        }
        $response_counts[$resp]++;
        $emails_by_response[$resp][] = $row['email'];
    }
} else {
    $response_counts = ['accepted' => 0, 'declined' => 0, 'pending' => 0];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Event Details - LakWatcha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
body {
    background: #f9f7f1 url('https://www.transparenttextures.com/patterns/paper-fibers.png') repeat;
    font-family: 'Georgia', serif;
    padding: 30px;
    color: #333;
}
.paper-note {
    background: #fffef8;
    max-width: 700px;
    margin: auto;
    padding: 40px 50px 50px 50px;
    border: 1px solid #ddd;
    border-radius: 12px;
    box-shadow:
        0 4px 8px rgba(0,0,0,0.1),
        inset 0 0 10px #f5f3e6;
    position: relative;
}

.pin {
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 30px;
    height: 30px;
    background: radial-gradient(circle at center, #d33 40%, #800 90%);
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    border: 2px solid #660000;
    z-index: 10;
}
.pencil {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 60px;
    opacity: 0.85;
    transform: rotate(-15deg);
    filter: drop-shadow(2px 2px 2px rgba(0,0,0,0.2));
    user-select: none;
    pointer-events: none;
}
h2, h4, h5 {
    font-family: 'Palatino Linotype', 'Book Antiqua', Palatino, serif;
}
table {
    background: #fcfbf7;
    border-collapse: separate;
    border-spacing: 0 8px;
    box-shadow: inset 0 1px 0 #fff;
    font-size: 16px;
    width: 100%;
}
thead tr th {
    background: #eae7df;
    color: #5a5a5a;
    font-weight: 600;
    padding: 10px 15px;
    text-align: left;
    border-radius: 6px 6px 0 0;
    border-bottom: 2px solid #ccc;
}
tbody tr {
    background: #fff;
    border-radius: 6px;
    transition: background 0.3s ease;
}
tbody tr:hover {
    background: #f0f0e9;
}
tbody tr td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
}
    .btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    transition: background-color 0.3s ease;
}
.btn-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
}
ul {
    list-style-type: none;
    padding-left: 0;
}
ul li {
    margin-bottom: 10px;
    font-size: 18px;
}
ul li small {
    font-size: 12px;
    color: #666;
    word-break: break-word;
}
    </style>
</head>
<body>
    <div class="paper-note">
        <div class="pin"></div>
        <img src="https://cdn-icons-png.flaticon.com/512/2563/2563924.png" alt="Pencil" class="pencil" />

        <h2 class="mb-4">Event Details</h2>

        <div class="card mb-4 border-0 shadow-none bg-transparent p-0">
            <div class="card-body p-0">
                <h4 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h4>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($event['date_time']); ?></p>
                <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            </div>
        </div>

        <h5>Response Summary</h5>
        <ul>
            <li>✅ Accepted: <?php echo $response_counts['accepted']; ?>
                <?php if ($response_counts['accepted'] > 0): ?>
                    <br><small><?php echo htmlspecialchars(implode(', ', $emails_by_response['accepted'])); ?></small>
                <?php endif; ?>
            </li>
            <li>❌ Declined: <?php echo $response_counts['declined']; ?>
                <?php if ($response_counts['declined'] > 0): ?>
                    <br><small><?php echo htmlspecialchars(implode(', ', $emails_by_response['declined'])); ?></small>
                <?php endif; ?>
            </li>
            <li>❓ Pending: <?php echo $response_counts['pending']; ?>
                <?php if ($response_counts['pending'] > 0): ?>
                    <br><small><?php echo htmlspecialchars(implode(', ', $emails_by_response['pending'])); ?></small>
                <?php endif; ?>
            </li>
        </ul>

        <h5>Invited Guests and Responses</h5>

        <?php if ($invitees_result && $invitees_result->num_rows > 0): ?>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Response</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 

                    
                    // reset the pointer to start for this loop
                    $invitees_result->data_seek(0);
                    while ($guest = $invitees_result->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($guest['name']); ?></td>
                            <td><?php echo htmlspecialchars($guest['email']); ?></td>
                            <td>
                                <?php
                                    $response = strtolower($guest['status'] ?? '');
                                    switch ($response) {
                                        case 'accepted':
                                            echo '✅ Accepted';
                                            break;
                                        case 'declined':
                                            echo '❌ Declined';
                                            break;
                                        case 'pending':
                                        default:
                                            echo '❓ Pending / No response';
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No guests invited yet.</p>
        <?php endif; ?>

        <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</body>
</html>

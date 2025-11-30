<?php
session_start();
include 'db.php';

$message = '';
$invitation = null;

$token = $_GET['token'] ?? '';
if (empty($token)) {
    die('Invalid invitation token.');
}

$stmt = $conn->prepare("
    SELECT i.id, i.email, i.name, i.status, i.event_id, e.title, e.date_time
    FROM invitations i
    JOIN events e ON e.id = i.event_id
    WHERE i.token = ?
");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die('Invitation not found.');
}
$invitation = $res->fetch_assoc();
$currentStatus = strtolower(trim($invitation['status']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = $_POST['response'] ?? '';
    if (!in_array($response, ['accepted', 'declined'])) {
        $message = "Invalid response.";
    } elseif ($currentStatus !== 'pending') {
        $message = "You have already responded.";
    } else {
        $upd = $conn->prepare("UPDATE invitations SET status = ?, updated_at = NOW() WHERE id = ?");
        $upd->bind_param("si", $response, $invitation['id']);
        if ($upd->execute()) {
            $message = "Thank you! You have " . ($response === 'accepted' ? "accepted" : "declined") . " the invitation.";
            $invitation['status'] = $response;
            $currentStatus = $response;
        } else {
            $message = "Failed to update your response.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Respond Invitation</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>

body {
  background: linear-gradient(180deg, #eef2f5 0%, #f7f8fa 100%);
  font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  padding: 40px 16px;
  color: #222;
}
.wrap {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  gap: 30px;
  flex-wrap: wrap;
}
.note {
  position: relative;
  width: 720px;
  max-width: calc(100% - 40px);
  background: linear-gradient(180deg, #fffefc 0%, #fffdf8 100%);
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(17,24,39,0.12);
  padding: 34px 36px 40px 36px;
  border: 1px solid rgba(30,41,59,0.06);
  overflow: hidden;
  background-image:
    repeating-linear-gradient(
      to bottom,
      rgba(0,0,0,0.03) 0px,
      rgba(0,0,0,0.03) 1px,
      transparent 1px,
      transparent 36px
    );
  background-size: 100% 36px;
  }
.note::before {
  content: "";
  position: absolute;
  inset: 0;
  background-image: url('https://www.transparenttextures.com/patterns/paper-fibers.png');
  opacity: 0.08;
  pointer-events: none;
}
.pin {
  position: absolute;
  top: 4px;
  left: 50%;
  transform: translateX(-50%);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: 
    radial-gradient(circle at 40% 35%, #ff5a5a 0%, #b22222 80%),
    radial-gradient(circle at 60% 25%, rgba(255, 255, 255, 0.7) 0%, transparent 60%), 
    radial-gradient(circle at 50% 50%, rgba(0, 0, 0, 0.2) 85%, transparent 90%); 
  box-shadow:
    0 2px 6px rgba(0,0,0,0.2),
    inset 0 2px 6px rgba(255,255,255,0.3);
  border: 1.5px solid rgba(0,0,0,0.15);
  filter: drop-shadow(0 2px 3px rgba(0,0,0,0.15));
  z-index: 10;
}
.pencil {
  position: absolute;
  top: 8px;
  right: -18px;
  width: 120px;
  transform: rotate(-20deg);
  filter: drop-shadow(2px 3px 6px rgba(0,0,0,0.12));
  opacity: 0.95;
  z-index: 4;
  pointer-events: none;
}
.note-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
}
.note-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.envelope {
  width: 46px;
  height: 36px;
  background: linear-gradient(180deg,#e9eef7,#dfe7f0);
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 3px 8px rgba(14,23,36,0.06);
  border: 1px solid rgba(10,20,40,0.04);
}
.envelope:after {
  content: "";
  width: 76%;
  height: 56%;
  background: linear-gradient(135deg, rgba(0,0,0,0.03) 0%, transparent 70%);
  transform: rotate(25deg) translateY(-2px);
  display: block;
  border-radius: 2px;
}
.note-title {
  font-weight: 700;
  font-size: 20px;
  color: #102a43;
  letter-spacing: -0.2px;
}
.note-sub {
  font-size: 13px;
  color: #4b5563;
}
.note-content {
  background: transparent;
  padding: 8px 0 0 0;
  line-height: 1.6;
  font-size: 15px;
  color: #1f2937;
}
.meta {
  margin-top: 12px;
  color: #374151;
  font-size: 14px;
}
.status {
  display: inline-block;
  padding: 6px 10px;
  border-radius: 999px;
  font-weight: 600;
  font-size: 13px;
  color: #fff;
}
.status.pending { background: #9ca3af; }
.status.accepted { background: #10b981; }
.status.declined { background: #ef4444; }

.btn-accept {
  background: linear-gradient(180deg,#14b8a6,#0f9d8b);
  border: none;
  color: white;
  padding: 10px 16px;
  border-radius: 10px;
  font-weight: 700;
  box-shadow: 0 6px 18px rgba(16,185,129,0.12);
}
.btn-decline {
  background: linear-gradient(180deg,#ff7b7b,#ff5a5a);
  border: none;
  color: white;
  padding: 10px 16px;
  border-radius: 10px;
  font-weight: 700;
  box-shadow: 0 6px 18px rgba(239,68,68,0.12);
}
.btn-accept:hover, .btn-decline:hover {
  transform: translateY(-1px);
  opacity: 0.98;
}
@media (max-width: 768px) {
  .note { padding: 26px; }
  .pencil { width: 90px; right: -12px; }
}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="note" role="article" aria-label="Invitation note">
      <div class="pin" aria-hidden="true"></div>
      <img class="pencil" src="https://cdn-icons-png.flaticon.com/512/2563/2563924.png" alt="" aria-hidden="true">

      <div class="note-header">
        <div class="note-left">
          <div class="envelope" aria-hidden="true"></div>
          <div>
            <div class="note-title">You're Invited</div>
            <div class="note-sub">A personal invitation from <?php echo htmlspecialchars($invitation['name']); ?></div>
          </div>
        </div>

        <div>
          <?php
            $cls = 'pending';
            if ($currentStatus === 'accepted') $cls = 'accepted';
            if ($currentStatus === 'declined') $cls = 'declined';
          ?>
          <div class="status <?php echo $cls; ?>">
            <?php echo ucfirst($currentStatus ?: 'pending'); ?>
          </div>
        </div>
      </div>

      <div class="note-content">
        <h4 style="margin:0 0 8px 0;"><?php echo htmlspecialchars($invitation['title']); ?></h4>

        <p style="margin:0 0 12px 0;"><strong>When:</strong> <?php echo htmlspecialchars($invitation['date_time']); ?></p>

        <p style="margin:0 0 6px 0;"><strong>Invitee:</strong> <?php echo htmlspecialchars($invitation['name']); ?> &mdash; <?php echo htmlspecialchars($invitation['email']); ?></p>

        <?php if ($message): ?>
          <div style="margin-top:10px;">
            <?php echo strip_tags($message, '<a><br><strong><em>'); ?>
          </div>
        <?php endif; ?>

        <div style="margin-top:18px;">
          <?php if ($currentStatus === 'pending'): ?>
            <form method="POST" style="display:flex; gap:12px; align-items:center;">
              <button type="submit" name="response" value="accepted" class="btn-accept">Accept</button>
              <button type="submit" name="response" value="declined" class="btn-decline">Decline</button>
            </form>
          <?php else: ?>
            <p style="margin-top:8px; color:#374151;">You have already responded to this invitation.</p>
          <?php endif; ?>
        </div>

        <div class="meta" aria-hidden="true">
          <small>Tip: Use the buttons above to RSVP. This page is secure and tied to the invitation token.</small>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /FinalProject/public/login');
    exit;
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    include_once __DIR__ . '/../../templates/header.php';
    echo '<div style="margin:2em auto;max-width:600px;padding:2em;background:#fff;border-radius:12px;text-align:center;font-size:1.3em;box-shadow:0 2px 12px #0002;">This user isn\'t an admin.</div>';
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../db.php';

$db = get_db();
$errors = [];
$success = [];

// Handle new election creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_election'])) {
    $name = trim($_POST['election_name'] ?? '');
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    if (!$name || !$start || !$end) {
        $errors[] = 'All election fields are required.';
    } elseif (strtotime($end) <= strtotime($start)) {
        $errors[] = 'End time must be after start time.';
    } else {
        $stmt = $db->prepare('INSERT INTO elections (name, start_time, end_time, status, created_by) VALUES (?, ?, ?, "upcoming", ?)');
        $stmt->bind_param('sssi', $name, $start, $end, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $success[] = 'Election created successfully.';
        } else {
            $errors[] = 'Failed to create election.';
        }
    }
}

// Handle new candidate creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_candidate'])) {
    $election_id = $_POST['election_id'] ?? '';
    $candidate_name = trim($_POST['candidate_name'] ?? '');
    $candidate_info = trim($_POST['candidate_info'] ?? '');
    if (!$election_id || !$candidate_name) {
        $errors[] = 'Election and candidate name are required.';
    } else {
        $stmt = $db->prepare('INSERT INTO candidates (election_id, name, info) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $election_id, $candidate_name, $candidate_info);
        if ($stmt->execute()) {
            $success[] = 'Candidate added successfully.';
        } else {
            $errors[] = 'Failed to add candidate.';
        }
    }
}

// Get all elections
$result = $db->query('SELECT * FROM elections ORDER BY id DESC');
$elections = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
// Get all candidates grouped by election
$result = $db->query('SELECT * FROM candidates ORDER BY election_id, id');
$candidates = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$candidates_by_election = [];
foreach ($candidates as $c) {
    $candidates_by_election[$c['election_id']][] = $c;
}
?>
<main class="dashboard-container" style="min-height: auto; padding: 2em; margin: 2em auto; max-width: 1200px;">
    <div class="dashboard-details" style="width: 100%;">
        <h2><i class="fas fa-user-shield"></i> Admin Panel</h2>
        <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>! Manage your elections and candidates from this dashboard.</p>

        <?php if ($errors): ?>
            <div class="alert alert-error" style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; border-left: 4px solid #dc2626;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; border-left: 4px solid #16a34a;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <?php foreach ($success as $s): ?>
                        <li><?= htmlspecialchars($s) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="admin-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
            <!-- Create Election Card -->
            <div class="admin-card" style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem;">
                <h3 style="margin-top: 0; color: #1f2937; font-size: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-plus-circle" style="color: #2563eb;"></i>
                    Create New Election
                </h3>
                <form method="POST" style="margin-top: 1rem;">
                    <input type="hidden" name="create_election" value="1">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #374151;">Election Name</label>
                        <input type="text" name="election_name" required 
                            style="width: 100%; padding: 0.625rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #374151;">Start Time</label>
                        <input type="datetime-local" name="start_time" required 
                            style="width: 100%; padding: 0.625rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #374151;">End Time</label>
                        <input type="datetime-local" name="end_time" required 
                            style="width: 100%; padding: 0.625rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                    <button type="submit" 
                        style="width: 100%; background: #2563eb; color: white; padding: 0.75rem; border: none; border-radius: 0.5rem; font-weight: 500; cursor: pointer; transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#1d4ed8'" 
                        onmouseout="this.style.backgroundColor='#2563eb'"
                        onmousedown="this.style.backgroundColor='#1e40af'"
                        onmouseup="this.style.backgroundColor='#1d4ed8'">
                        <i class="fas fa-plus-circle"></i> Create Election
                    </button>
                </form>
            </div>

            <!-- Add Candidate Card -->
            <div class="admin-card" style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem;">
                <h3 style="margin-top: 0; color: #1f2937; font-size: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-user-plus" style="color: #7c3aed;"></i>
                    Add New Candidate
                </h3>
                <form method="POST" style="margin-top: 1rem;">
                    <input type="hidden" name="add_candidate" value="1">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #374151;">Election</label>
                        <select name="election_id" required 
                            style="width: 100%; padding: 0.625rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; background-color: white;">
                            <option value="">Select election</option>
                            <?php foreach ($elections as $el): ?>
                                <option value="<?= $el['id'] ?>">
                                    <?= htmlspecialchars($el['name']) ?> (<?= date('M j, Y', strtotime($el['start_time'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #374151;">Candidate Name</label>
                        <input type="text" name="candidate_name" required 
                            style="width: 100%; padding: 0.625rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #374151;">Candidate Information</label>
                        <textarea name="candidate_info" 
                            style="width: 100%; padding: 0.625rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; min-height: 100px;"></textarea>
                    </div>
                    <button type="submit" 
                        style="width: 100%; background: #7c3aed; color: white; padding: 0.75rem; border: none; border-radius: 0.5rem; font-weight: 500; cursor: pointer; transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#6d28d9'" 
                        onmouseout="this.style.backgroundColor='#7c3aed'"
                        onmousedown="this.style.backgroundColor='#5b21b6'"
                        onmouseup="this.style.backgroundColor='#6d28d9'">
                        <i class="fas fa-user-plus"></i> Add Candidate
                    </button>
                </form>
            </div>
        </div>

        <!-- Current Elections Section -->
        <div style="margin-top: 3rem;">
            <h3 style="font-size: 1.5rem; color: #1f2937; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-clipboard-list" style="color: #059669;"></i>
                Current Elections
            </h3>
            
            <?php if ($elections): ?>
                <div class="elections-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($elections as $el): 
                        $election_candidates = $candidates_by_election[$el['id']] ?? [];
                        $status_class = '';
                        $status_icon = '';
                        
                        switch(strtolower($el['status'])) {
                            case 'active':
                                $status_class = 'bg-blue-50 text-blue-700';
                                $status_icon = 'fa-play-circle';
                                break;
                            case 'upcoming':
                                $status_class = 'bg-yellow-50 text-yellow-700';
                                $status_icon = 'fa-clock';
                                break;
                            case 'ended':
                                $status_class = 'bg-green-50 text-green-700';
                                $status_icon = 'fa-check-circle';
                                break;
                            default:
                                $status_class = 'bg-gray-100 text-gray-700';
                                $status_icon = 'fa-circle';
                        }
                    ?>
                        <div class="election-card" style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
                            <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                                    <h4 style="margin: 0; font-size: 1.125rem; color: #1f2937; font-weight: 600;">
                                        <?= htmlspecialchars($el['name']) ?>
                                    </h4>
                                    <span style="font-size: 0.75rem; font-weight: 500; padding: 0.25rem 0.75rem; border-radius: 9999px; <?= $status_class ?>">
                                        <i class="fas <?= $status_icon ?>"></i> 
                                        <?= ucfirst(htmlspecialchars($el['status'])) ?>
                                    </span>
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                                    <div style="display: flex; align-items: center; color: #4b5563; font-size: 0.875rem;">
                                        <i class="fas fa-calendar-day" style="width: 20px; color: #6b7280;"></i>
                                        <span>Starts: <?= date('M j, Y g:i A', strtotime($el['start_time'])) ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; color: #4b5563; font-size: 0.875rem;">
                                        <i class="fas fa-calendar-check" style="width: 20px; color: #6b7280;"></i>
                                        <span>Ends: <?= date('M j, Y g:i A', strtotime($el['end_time'])) ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; color: #4b5563; font-size: 0.875rem;">
                                        <i class="fas fa-users" style="width: 20px; color: #6b7280;"></i>
                                        <span><?= count($election_candidates) ?> candidates</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="padding: 1rem 1.5rem; background-color: #f9fafb; border-top: 1px solid #e5e7eb;">
                                <h5 style="margin: 0 0 0.75rem 0; font-size: 0.875rem; font-weight: 600; color: #4b5563; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-user-friends" style="color: #6b7280;"></i>
                                    Candidates
                                </h5>
                                
                                <?php if (!empty($election_candidates)): ?>
                                    <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 0.5rem;">
                                        <?php foreach ($election_candidates as $c): ?>
                                            <li style="display: flex; align-items: center; padding: 0.5rem; background: white; border-radius: 0.5rem; border: 1px solid #e5e7eb;">
                                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #f3f4f6; display: flex; align-items: center; justify-content: center; margin-right: 0.75rem; flex-shrink: 0;">
                                                    <i class="fas fa-user" style="color: #6b7280; font-size: 0.875rem;"></i>
                                                </div>
                                                <div style="flex: 1; min-width: 0;">
                                                    <div style="font-weight: 500; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        <?= htmlspecialchars($c['name']) ?>
                                                    </div>
                                                    <?php if (!empty($c['info'])): ?>
                                                        <div style="font-size: 0.75rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                            <?= htmlspecialchars($c['info']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 1rem; background: #f9fafb; border-radius: 0.5rem; color: #6b7280; font-size: 0.875rem;">
                                        <i class="fas fa-info-circle"></i> No candidates added yet
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem 2rem; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #9ca3af; margin-bottom: 1rem; opacity: 0.7;"></i>
                    <h4 style="color: #1f2937; margin: 0 0 0.5rem 0; font-size: 1.25rem;">No Elections Found</h4>
                    <p style="color: #6b7280; margin: 0; line-height: 1.5;">
                        You haven't created any elections yet. Use the form above to create your first election.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../../templates/footer.php';

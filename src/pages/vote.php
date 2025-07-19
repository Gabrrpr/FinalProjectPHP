<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Set page title and additional assets
$GLOBALS['page_title'] = 'Cast Your Vote';
$GLOBALS['additional_css'] = [
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    '/FinalProject/public/assets/vote.css'
];
$GLOBALS['additional_js'] = [
    'https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js',
    '/FinalProject/public/assets/vote.js'
];

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../db.php';

$db = get_db();
$errors = [];
$success = [];
$user_id = $_SESSION['user_id'];

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_election_id'], $_POST['candidate_id'])) {
    $election_id = $_POST['vote_election_id'];
    $candidate_id = $_POST['candidate_id'];
    // Check if user already voted in this election
    $stmt = $db->prepare('SELECT id FROM votes WHERE user_id = ? AND election_id = ?');
    $stmt->bind_param('ii', $user_id, $election_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()) {
        $errors[] = 'You have already voted in this election.';
    } else {
        // Check if election is ongoing
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare('SELECT * FROM elections WHERE id = ? AND start_time <= ? AND end_time >= ? AND status = "ongoing"');
        $stmt->bind_param('iss', $election_id, $now, $now);
        $stmt->execute();
        $result = $stmt->get_result();
        $election = $result->fetch_assoc();
        if (!$election) {
            $errors[] = 'Election is not active.';
        } else {
            // Record vote
            $stmt = $db->prepare('INSERT INTO votes (user_id, election_id, candidate_id) VALUES (?, ?, ?)');
            $stmt->bind_param('iii', $user_id, $election_id, $candidate_id);
            if ($stmt->execute()) {
                $success[] = 'Vote submitted successfully!';
            } else {
                $errors[] = 'Failed to submit vote.';
            }
        }
    }
}

// Update election statuses (optional: auto-update based on time)
$now = date('Y-m-d H:i:s');
$stmt = $db->prepare('UPDATE elections SET status = "ongoing" WHERE start_time <= ? AND end_time >= ?');
$stmt->bind_param('ss', $now, $now);
$stmt->execute();
$stmt = $db->prepare('UPDATE elections SET status = "ended" WHERE end_time < ?');
$stmt->bind_param('s', $now);
$stmt->execute();

// Get all ongoing elections
$stmt = $db->prepare('SELECT * FROM elections WHERE status = "ongoing" ORDER BY start_time ASC');
$stmt->execute();
$result = $stmt->get_result();
$elections = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Get all votes by this user
$stmt = $db->prepare('SELECT election_id FROM votes WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$voted_elections = $result ? array_column($result->fetch_all(MYSQLI_ASSOC), 'election_id') : [];

// Get all candidates for ongoing elections
$election_ids = array_column($elections, 'id');
$candidates_by_election = [];
if ($election_ids) {
    $in = implode(',', array_fill(0, count($election_ids), '?'));
    $stmt = $db->prepare('SELECT * FROM candidates WHERE election_id IN (' . $in . ') ORDER BY id');
    $stmt->execute($election_ids);
    $result = $stmt->get_result();
    $candidates = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    foreach ($candidates as $c) {
        $candidates_by_election[$c['election_id']][] = $c;
    }
}
?>
<main class="dashboard-container" style="min-height: auto; padding: 2em; margin: 2em auto; max-width: 1200px;">
    <div class="dashboard-details" style="width: 100%;">
        <h2><i class="fas fa-vote-yea"></i> Cast Your Vote</h2>
        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; border: 1px solid #e9ecef;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <span class="badge" style="background: #3b82f6; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-size: 1rem; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <i class="fas fa-shield-alt" style="margin-right: 0.5rem;"></i>PoliSys
                </span>
                <span class="badge" style="background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-size: 1rem; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <i class="fas fa-robot" style="margin-right: 0.5rem;"></i>Automated
                </span>
            </div>
            <p style="margin: 0; color: #4b5563; font-size: 1.05rem; line-height: 1.6;">
                Participate in the democratic process by casting your vote in the following elections. Your voice matters!
            </p>
        </div>
        
        <div class="voting-stats" style="display: flex; gap: 1.5rem; margin: 1.5rem 0; flex-wrap: wrap;">
            <div class="stat-badge" style="background: #f0f9ff; padding: 0.75rem 1.25rem; border-radius: 10px; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <i class="fas fa-vote-yea" style="color: #0369a1;"></i>
                <span>Votes Cast: <strong><?= count($voted_elections) ?></strong></span>
            </div>
            <div class="stat-badge" style="background: #f0fdf4; padding: 0.75rem 1.25rem; border-radius: 10px; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <i class="fas fa-hourglass-half" style="color: #15803d;"></i>
                <span>Active Elections: <strong><?= count($elections) ?></strong></span>
            </div>
            <div class="stat-badge" style="background: #fffbeb; padding: 0.75rem 1.25rem; border-radius: 10px; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <i class="far fa-clock" style="color: #b45309;"></i>
                <span>Server Time: <span class="current-time"><?= date('M j, Y g:i A', strtotime('now')) ?></span></span>
            </div>
        </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div>
                <?php foreach ($success as $msg): ?>
                    <p><?= htmlspecialchars($msg) ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($elections)): ?>
        <div class="elections-container" style="margin-top: 2rem;">
            <?php foreach ($elections as $election): 
                $has_voted = in_array($election['id'], $voted_elections);
                $current_time = time();
                $start_time = strtotime($election['start_time']);
                $end_time = strtotime($election['end_time']);
                
                if ($current_time > $end_time) {
                    $election_status = 'completed';
                } elseif ($current_time < $start_time) {
                    $election_status = 'upcoming';
                } else {
                    $election_status = 'ongoing';
                }
                $status_colors = [
                    'ongoing' => 'bg-green-100 text-green-800',
                    'upcoming' => 'bg-yellow-100 text-yellow-800',
                    'completed' => 'bg-red-100 text-red-800'
                ];
            ?>
                <div class="election-card" style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem; overflow: hidden;">
                    <div class="election-header" style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; position: relative;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h3 style="margin: 0; font-size: 1.5rem; color: #1f2937;"><?= htmlspecialchars($election['name']) ?></h3>
                            <span class="election-status <?= $status_colors[$election_status] ?>" style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                                <?= ucfirst($election_status) ?>
                            </span>
                        </div>
                        
                        <div class="election-dates" style="display: flex; gap: 1.5rem; color: #4b5563; font-size: 0.9rem;">
                            <div class="date-item" style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="far fa-calendar-alt" style="color: #6b7280;"></i>
                                <span><?= date('M j, Y', strtotime($election['start_time'])) ?> - <?= date('M j, Y', strtotime($election['end_time'])) ?></span>
                            </div>
                            <div class="date-item" style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="far fa-clock" style="color: #6b7280;"></i>
                                <span><?= date('g:i A', strtotime($election['start_time'])) ?> - <?= date('g:i A', strtotime($election['end_time'])) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($election['description'])): ?>
                    <div class="election-description" style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; color: #374151; line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($election['description'])) ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="candidates-list" style="padding: 1.5rem;">
                        <h4 style="margin-top: 0; margin-bottom: 1rem; color: #374151; font-size: 1.1rem;">Candidates</h4>
                        
                        <form method="POST" class="vote-form" id="election-<?= $election['id'] ?>-form" onsubmit="return validateVoteForm(this, <?= $election['id'] ?>)">
                            <input type="hidden" name="vote_election_id" value="<?= $election['id'] ?>">
                            
                            <div class="candidates-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                                <?php foreach ($candidates_by_election[$election['id']] as $candidate): 
                                    $candidate_photo = !empty($candidate['photo']) ? 
                                        '/FinalProject/uploads/candidates/' . $candidate['photo'] : 
                                        '/FinalProject/public/assets/img/OIP.jpg';
                                    $candidate_name = htmlspecialchars($candidate['name']);
                                    $candidate_party = !empty($candidate['party']) ? htmlspecialchars($candidate['party']) : 'Independent';
                                ?>
                                    <div class="candidate-item" style="border: 2px solid #e5e7eb; border-radius: 8px; padding: 1rem; transition: all 0.2s ease; background: white; position: relative; overflow: hidden; cursor: pointer;">
                                        <input type="radio" 
                                               name="candidate_id" 
                                               id="candidate-<?= $candidate['id'] ?>" 
                                               value="<?= $candidate['id'] ?>" 
                                               class="candidate-radio" 
                                               style="position: absolute; opacity: 0; width: 0; height: 0;"
                                               <?= $has_voted ? 'disabled' : '' ?>
                                               data-election-id="<?= $election['id'] ?>"
                                               onchange="handleCandidateSelection(this, <?= $candidate['id'] ?>)"
                                               form="election-<?= $election['id'] ?>-form"
                                               required>
                                        <input type="hidden" name="vote_election_id" value="<?= $election['id'] ?>">
                                        <label for="candidate-<?= $candidate['id'] ?>" style="display: flex; align-items: flex-start; gap: 1rem; margin: 0; width: 100%;">
                                            <div class="candidate-photo" style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; flex-shrink: 0; background: #f3f4f6; border: 2px solid #e5e7eb;">
                                                <img src="<?= $candidate_photo ?>" alt="<?= $candidate_name ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                            <div class="candidate-info" style="flex: 1;">
                                                <h4 style="margin: 0 0 0.25rem 0; color: #1f2937;"><?= $candidate_name ?></h4>
                                                <?php if (!empty($candidate['party'])): ?>
                                                    <p class="party" style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.875rem;">
                                                        <?= $candidate_party ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if (!empty($candidate['description'])): ?>
                                                    <p class="candidate-description" style="margin: 0; color: #4b5563; font-size: 0.875rem; line-height: 1.5;">
                                                        <?= htmlspecialchars($candidate['description']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;">
                                <?php if ($has_voted): ?>
                                    <div class="voted-message" style="color: #15803d; background: #f0fdf4; padding: 0.75rem 1rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-check-circle"></i> 
                                        <span>You've already voted in this election.</span>
                                    </div>
                                <?php else: ?>
                                    <div></div>
                                    <button type="submit" class="vote-btn" style="background: linear-gradient(90deg, #036a5f 0%, #00a293 100%); color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s ease;"
                                            onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                        Submit Vote
                                        <i class="fas fa-arrow-right" style="font-size: 0.9em;"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-elections" style="text-align: center; padding: 3rem 2rem; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 2rem;">
            <i class="fas fa-calendar-times" style="font-size: 3rem; color: #9ca3af; margin-bottom: 1rem; opacity: 0.7;"></i>
            <h3 style="color: #1f2937; margin: 0 0 0.75rem 0; font-size: 1.5rem;">No Active Elections</h3>
            <p style="color: #6b7280; margin: 0 0 1.5rem 0; max-width: 500px; margin-left: auto; margin-right: auto; line-height: 1.6;">
                There are currently no elections available for voting. Please check back later for upcoming elections.
            </p>
            <a href="/FinalProject/public/dashboard" style="display: inline-flex; align-items: center; gap: 0.5rem; background: #036a5f; color: white; padding: 0.75rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 500; transition: all 0.2s ease;"
               onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    <?php endif; ?>
    
    <!-- Success/Error Messages -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div>
                <?php foreach ($success as $msg): ?>
                    <p><?= $msg ?></p>
                <?php endforeach; ?>
            </div>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <?php foreach ($errors as $error): ?>
                    <p><?= $error ?></p>
                <?php endforeach; ?>
            </div>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <div id="confetti-canvas"></div>
    
    <script>
    // Add interactivity to candidate selection
    document.addEventListener('DOMContentLoaded', function() {
        // Handle radio button selection
        document.querySelectorAll('.candidate-radio').forEach(radio => {
            radio.addEventListener('change', function() {
                const candidateItem = this.closest('.candidate-item');
                // Remove selected class from all candidates in this election
                const form = this.closest('.vote-form');
                form.querySelectorAll('.candidate-item').forEach(item => {
                    item.classList.remove('selected');
                });
                // Add selected class to clicked candidate
                if (this.checked) {
                    candidateItem.classList.add('selected');
                }
            });
        });
        
        // Initialize selected candidates if any
        document.querySelectorAll('.candidate-radio:checked').forEach(radio => {
            radio.closest('.candidate-item').classList.add('selected');
        });
    });
    </script>
<script>
// Handle candidate selection with visual feedback and toggle functionality
function handleCandidateSelection(radio, candidateId) {
    // Get all candidate items in the same election
    const electionId = radio.getAttribute('data-election-id');
    const form = radio.closest('form');
    const wasChecked = radio.checked;
    
    // If clicking the already selected candidate, toggle it off
    if (wasChecked && radio.hasAttribute('data-last-selected')) {
        radio.checked = false;
        radio.removeAttribute('data-last-selected');
        
        // Clear selection
        const selectedItem = radio.closest('.candidate-item');
        if (selectedItem) {
            selectedItem.style.borderColor = '#e5e7eb';
            selectedItem.style.backgroundColor = 'white';
            selectedItem.style.boxShadow = 'none';
            
            // Remove checkmark
            const checkmark = selectedItem.querySelector('.selected-checkmark');
            if (checkmark) {
                checkmark.remove();
            }
        }
        return;
    }
    
    // Uncheck all other radio buttons in the same form
    const allRadios = form.querySelectorAll('input[type="radio"]');
    allRadios.forEach(r => {
        r.checked = false;
        r.removeAttribute('data-last-selected');
    });
    
    // Mark this one as checked and selected
    radio.checked = true;
    radio.setAttribute('data-last-selected', 'true');
    
    // Remove selected class from all candidates in this election
    const allCandidates = form.querySelectorAll('.candidate-item');
    allCandidates.forEach(item => {
        item.style.borderColor = '#e5e7eb';
        item.style.backgroundColor = 'white';
        item.style.boxShadow = 'none';
        
        // Remove any existing checkmarks
        const existingCheckmark = item.querySelector('.selected-checkmark');
        if (existingCheckmark) {
            existingCheckmark.remove();
        }
    });
    
    // Add selected styles to the clicked candidate
    const selectedItem = radio.closest('.candidate-item');
    if (selectedItem) {
        selectedItem.style.borderColor = '#2563eb';
        selectedItem.style.backgroundColor = '#eff6ff';
        selectedItem.style.boxShadow = '0 0 0 3px rgba(37, 99, 235, 0.2)';
        
        // Add checkmark
        let checkmark = document.createElement('div');
        checkmark.className = 'selected-checkmark';
        checkmark.innerHTML = '&#10003;';
        checkmark.style.position = 'absolute';
        checkmark.style.top = '8px';
        checkmark.style.right = '8px';
        checkmark.style.width = '20px';
        checkmark.style.height = '20px';
        checkmark.style.backgroundColor = '#2563eb';
        checkmark.style.color = 'white';
        checkmark.style.borderRadius = '50%';
        checkmark.style.display = 'flex';
        checkmark.style.alignItems = 'center';
        checkmark.style.justifyContent = 'center';
        checkmark.style.fontSize = '12px';
        checkmark.style.fontWeight = 'bold';
        selectedItem.style.position = 'relative';
        selectedItem.appendChild(checkmark);
    }
}

// Validate vote form before submission
function validateVoteForm(form, electionId) {
    const selectedCandidate = form.querySelector('input[name="candidate_id"]:checked');
    if (!selectedCandidate) {
        alert('Please select a candidate before voting.');
        return false;
    }
    
    // Check if the selection was toggled off
    if (!selectedCandidate.hasAttribute('data-last-selected')) {
        // If no candidate is selected, prevent form submission
        if (!form.querySelector('input[name="candidate_id"]:checked[data-last-selected]')) {
            alert('Please select a candidate before voting.');
            return false;
        }
    }
    
    return true;
}

// Initialize any previously selected candidates
document.addEventListener('DOMContentLoaded', function() {
    // Add click handler to candidate items
    document.querySelectorAll('.candidate-item').forEach(item => {
        item.addEventListener('click', function(e) {
            const radio = this.querySelector('input[type="radio"]');
            if (radio && !radio.disabled) {
                radio.checked = true;
                handleCandidateSelection(radio, radio.value);
            }
        });
    });
    
    // Add keyboard navigation
    document.querySelectorAll('.candidate-radio').forEach(radio => {
        radio.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.checked = true;
                handleCandidateSelection(this, this.value);
            }
        });
    });
});
</script>

<style>
/* Enhanced candidate selection styles */
.candidate-item {
    transition: all 0.2s ease;
}

.candidate-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-color: #93c5fd !important;
}

/* Ensure the radio button is properly hidden but still accessible */
.candidate-radio:focus + label {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
    border-radius: 4px;
}
</style>
</main>

<?php
require_once __DIR__ . '/../../templates/footer.php';

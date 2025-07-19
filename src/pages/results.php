<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Set page title and styles
$GLOBALS['page_title'] = 'Election Results';
$GLOBALS['additional_css'] = ['/FinalProject/public/assets/results.css'];

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../db.php';

$db = get_db();

// Get all ended elections
$stmt = $db->prepare('SELECT * FROM elections WHERE status = "ended" ORDER BY end_time DESC');
$stmt->execute();
$result = $stmt->get_result();
$elections = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Get all candidates for ended elections
$election_ids = array_column($elections, 'id');
$candidates_by_election = [];
if ($election_ids) {
    $in = implode(',', array_fill(0, count($election_ids), '?'));
    $types = str_repeat('i', count($election_ids));
    $stmt = $db->prepare('SELECT * FROM candidates WHERE election_id IN (' . $in . ') ORDER BY id');
    $stmt->bind_param($types, ...$election_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    foreach ($result ? $result->fetch_all(MYSQLI_ASSOC) : [] as $c) {
        $candidates_by_election[$c['election_id']][] = $c;
    }
}
// Get vote counts for each candidate
$vote_counts = [];
if ($election_ids) {
    $in = implode(',', array_fill(0, count($election_ids), '?'));
    $types = str_repeat('i', count($election_ids));
    $stmt = $db->prepare('SELECT candidate_id, COUNT(*) as votes FROM votes WHERE election_id IN (' . $in . ') GROUP BY candidate_id');
    $stmt->bind_param($types, ...$election_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    foreach ($result ? $result->fetch_all(MYSQLI_ASSOC) : [] as $row) {
        $vote_counts[$row['candidate_id']] = $row['votes'];
    }
}
?>
<main class="dashboard-container" style="min-height: auto; padding: 2em; margin: 2em auto; max-width: 1200px;">
    <div class="dashboard-details" style="width: 100%;">
        <h2><i class="fas fa-chart-bar"></i> Election Results</h2>
        <p>View the latest election results and statistics.</p>

    <?php if ($elections): ?>
        <div class="elections-container" style="margin-top: 2rem;">
            <?php foreach ($elections as $el): 
                $election_candidates = $candidates_by_election[$el['id']] ?? [];
                $total_votes = array_sum(array_intersect_key($vote_counts, array_flip(array_column($election_candidates, 'id'))));
            ?>
                <div class="election-card" style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 2rem; overflow: hidden;">
                    <div class="election-header" style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; position: relative;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                            <h3 style="margin: 0; font-size: 1.5rem; color: #1f2937;"><?= htmlspecialchars($el['name']) ?></h3>
                            <div class="election-meta" style="display: flex; gap: 1rem;">
                                <span style="background: #f0f9ff; color: #0369a1; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-calendar-check" style="font-size: 0.8em;"></i> 
                                    <?= date('M j, Y', strtotime($el['end_time'])) ?>
                                </span>
                                <span style="background: #f0fdf4; color: #15803d; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-users" style="font-size: 0.8em;"></i> 
                                    <?= $total_votes ?> Votes
                                </span>
                            </div>
                        </div>

                    <?php if (!empty($el['description'])): ?>
                        <div class="election-description" style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; color: #374151; line-height: 1.6;">
                            <?= nl2br(htmlspecialchars($el['description'])) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($election_candidates)): ?>
                        <div style="padding: 1.5rem;">
                            <h4 style="margin-top: 0; margin-bottom: 1.25rem; color: #374151; font-size: 1.1rem;">Results</h4>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr style="background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                            <th style="text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #4b5563; font-size: 0.875rem;">Candidate</th>
                                            <th style="text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #4b5563; font-size: 0.875rem;">Party</th>
                                            <th style="text-align: right; padding: 0.75rem 1rem; font-weight: 600; color: #4b5563; font-size: 0.875rem;">Votes</th>
                                            <th style="text-align: right; padding: 0.75rem 1rem; font-weight: 600; color: #4b5563; font-size: 0.875rem;">Percentage</th>
                                            <th style="width: 30%; padding: 0.75rem 1rem;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                <?php 
                                // Sort candidates by vote count (highest first)
                                usort($election_candidates, function($a, $b) use ($vote_counts) {
                                    $votes_a = $vote_counts[$a['id']] ?? 0;
                                    $votes_b = $vote_counts[$b['id']] ?? 0;
                                    return $votes_b - $votes_a;
                                });

                                foreach ($election_candidates as $candidate): 
                                    $candidate_photo = !empty($candidate['photo']) ? 
                                        '/FinalProject/uploads/candidates/' . $candidate['photo'] : 
                                        '/FinalProject/public/assets/img/OIP.jpg';
                                    $votes = $vote_counts[$candidate['id']] ?? 0;
                                    $percentage = $total_votes > 0 ? ($votes / $total_votes) * 100 : 0;
                                    $percentage_formatted = number_format($percentage, 1);
                                    $candidate_name = htmlspecialchars($candidate['name']);
                                    $candidate_party = !empty($candidate['party']) ? htmlspecialchars($candidate['party']) : 'Independent';
                                    
                                    // Color based on position (gold, silver, bronze, or default)
                                    $position_class = '';
                                    if ($votes > 0) {
                                        $position = array_search($candidate['id'], array_column($election_candidates, 'id')) + 1;
                                        if ($position === 1) $position_class = 'bg-yellow-50';
                                        elseif ($position === 2) $position_class = 'bg-gray-50';
                                        elseif ($position === 3) $position_class = 'bg-amber-50';
                                    }
                                ?>
                                    <tr class="result-row <?= $position_class ?>" style="border-bottom: 1px solid #e5e7eb; transition: background-color 0.2s;">
                                        <td style="padding: 1rem; vertical-align: middle;">
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #f3f4f6; border: 2px solid #e5e7eb;">
                                                    <img src="<?= $candidate_photo ?>" alt="<?= $candidate_name ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                </div>
                                                <span style="font-weight: 500; color: #1f2937;"><?= $candidate_name ?></span>
                                            </div>
                                        </td>
                                        <td style="padding: 1rem; vertical-align: middle; color: #6b7280; font-size: 0.875rem;">
                                            <?= $candidate_party ?>
                                        </td>
                                        <td style="padding: 1rem; text-align: right; font-weight: 600; color: #1f2937; vertical-align: middle;">
                                            <?= number_format($votes) ?>
                                        </td>
                                        <td style="padding: 1rem; text-align: right; color: #4b5563; vertical-align: middle;">
                                            <?= $percentage_formatted ?>%
                                        </td>
                                        <td style="padding: 1rem; vertical-align: middle;">
                                            <div style="height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                                                <div style="height: 100%; background: linear-gradient(90deg, #036a5f 0%, #00a293 100%); width: <?= $percentage ?>%;
                                                    <?= $percentage > 0 ? 'min-width: 4px;' : '' ?>">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="results-footer">
                                <div class="total-votes">
                                    <span>Total Votes Cast:</span>
                                    <strong><?= number_format($total_votes) ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="padding: 2rem; text-align: center; color: #6b7280;">
                            <i class="fas fa-info-circle" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
                            <span>No candidates participated in this election.</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem 2rem; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 2rem;">
            <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #9ca3af; margin-bottom: 1rem; opacity: 0.7;"></i>
            <h3 style="color: #1f2937; margin: 0 0 0.75rem 0; font-size: 1.5rem;">No Results Available</h3>
            <p style="color: #6b7280; margin: 0 0 1.5rem 0; max-width: 500px; margin-left: auto; margin-right: auto; line-height: 1.6;">
                There are no completed elections to display results for at this time.
            </p>
        </div>
    <?php endif; ?>
    </div>
</main>
<?php
require_once __DIR__ . '/../../templates/footer.php';

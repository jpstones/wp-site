<?php
/**
 * CCC Client Profile Page
 *
 * Displays notes and core issue history.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get client ID from query string
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
if (!$client_id) {
    echo '<p style="color: red;">Invalid client ID.</p>';
    return;
}

// Fetch core issues
$active_issues = get_user_meta($client_id, '_core_issues', true);
$archived_issues = get_user_meta($client_id, '_core_issues_archive', true);

$active_issues = $active_issues ? json_decode($active_issues, true) : [];
$archived_issues = $archived_issues ? json_decode($archived_issues, true) : [];

// Fetch severity logs
$issue_logs = get_posts([
    'post_type'   => 'core_issue_log',
    'numberposts' => -1,
    'meta_query'  => [
        [
            'key'     => '_client_id',
            'value'   => $client_id,
            'compare' => '='
        ]
    ]
]);

?>

<div class="ccc-client-profile">
    <h2>Client Core Issues</h2>

    <!-- Active Issues -->
    <h3>Active Issues</h3>
    <?php if (!empty($active_issues)) : ?>
        <ul>
            <?php foreach ($active_issues as $issue) : ?>
                <li>
                    <strong><?php echo esc_html($issue['name']); ?></strong> - Severity: <?php echo esc_html($issue['severity']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p>No active core issues.</p>
    <?php endif; ?>

    <!-- Archived Issues -->
    <h3>Archived Issues</h3>
    <?php if (!empty($archived_issues)) : ?>
        <ul>
            <?php foreach ($archived_issues as $issue) : ?>
                <li>
                    <strong><?php echo esc_html($issue['name']); ?></strong> - Archived
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p>No archived core issues.</p>
    <?php endif; ?>

    <!-- Severity Trend Graph -->
    <h3>Severity Trend</h3>
    <canvas id="severityChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('severityChart').getContext('2d');
    
    const chartData = {
        labels: [],
        datasets: []
    };

    <?php foreach ($issue_logs as $log) : 
        $issue_name = get_post_meta($log->ID, '_issue_name', true);
        $severity = get_post_meta($log->ID, '_severity', true);
        $date = get_post_meta($log->ID, '_date', true);
    ?>
        if (!chartData.labels.includes("<?php echo esc_js($date); ?>")) {
            chartData.labels.push("<?php echo esc_js($date); ?>");
        }

        let dataset = chartData.datasets.find(d => d.label === "<?php echo esc_js($issue_name); ?>");
        if (!dataset) {
            dataset = {
                label: "<?php echo esc_js($issue_name); ?>",
                data: [],
                borderColor: 'rgba(75, 192, 192, 1)',
                fill: false
            };
            chartData.datasets.push(dataset);
        }

        dataset.data.push(<?php echo esc_js($severity); ?>);
    <?php endforeach; ?>

    new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5
                }
            }
        }
    });
});
</script>
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

// Debug output
error_log('=== CCC CLIENT PROFILE DEBUG START ===');
error_log('Template loaded at: ' . date('Y-m-d H:i:s'));

// Check if we're in a shortcode context
if (!is_singular()) {
    error_log('Warning: Not in a singular context');
}

// Wrap everything in a try-catch to catch any PHP errors
try {
    // Enqueue required assets
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
    wp_enqueue_style('ccc-styles', plugins_url('assets/css/styles.css', dirname(__FILE__)));

    // Get and validate client ID
    $client_id = filter_input(INPUT_GET, 'client_id', FILTER_VALIDATE_INT);
    
    // Add JavaScript debug output
    ?>
    <script>
        console.log('=== CCC Client Profile Debug ===');
        console.log('Client ID:', <?php echo json_encode($client_id); ?>);
        console.log('URL:', window.location.href);
    </script>
    <?php

    if (!$client_id) {
        throw new Exception('Invalid client ID');
    }

    // Fetch active core issues
    $active_issues = get_posts([
        'post_type' => 'core_issues',
        'numberposts' => -1,
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'client',
                'value' => $client_id,
                'compare' => '='
            ],
            [
                'key' => 'status',
                'value' => 'active',
                'compare' => '='
            ]
        ]
    ]);

    // Debug active issues
    ?>
    <script>
        console.log('Active Issues Found:', <?php echo json_encode(count($active_issues)); ?>);
        console.log('Active Issues:', <?php echo json_encode(array_map(function($issue) {
            return [
                'ID' => $issue->ID,
                'title' => $issue->post_title,
                'client' => get_post_meta($issue->ID, 'client', true),
                'status' => get_post_meta($issue->ID, 'status', true),
                'severity' => get_field('severity', $issue->ID),
                'history' => get_field('history', $issue->ID)
            ];
        }, $active_issues)); ?>);
    </script>
    <?php

    // Debug the raw data first
    $test_issue = $active_issues[0] ?? null;
    if ($test_issue) {
        ?>
        <script>
            console.log('Raw History Data:', <?php echo json_encode(get_field('history', $test_issue->ID)); ?>);
            console.log('Raw First Appearance:', <?php echo json_encode(get_field('first_appearance', $test_issue->ID)); ?>);
        </script>
        <?php
    }

    // Format dates for JavaScript
    $formatted_issues = array_map(function($issue) {
        $history = get_field('history', $issue->ID) ?: [];
        
        // Format each history entry's date
        $formatted_history = array_map(function($entry) {
            if (isset($entry['date'])) {
                $date_parts = explode('/', $entry['date']);
                if (count($date_parts) === 3) {  // Ensure we have day/month/year
                    // Convert dd/mm/yyyy to yyyy-mm-dd for JavaScript
                    $entry['date'] = sprintf('%s-%s-%s', 
                        $date_parts[2],  // Year
                        $date_parts[1],  // Month
                        $date_parts[0]   // Day
                    );
                }
            }
            // Ensure severity is a number
            if (isset($entry['severity'])) {
                $entry['severity'] = intval($entry['severity']);
            }
            return $entry;
        }, $history);

        return [
            'ID' => $issue->ID,
            'title' => $issue->post_title,
            'client' => get_post_meta($issue->ID, 'client', true),
            'status' => get_post_meta($issue->ID, 'status', true),
            'severity' => intval(get_field('severity', $issue->ID)),
            'history' => $formatted_history
        ];
    }, $active_issues);

    // Debug the final formatted data
    ?>
    <script>
        console.log('Final Formatted Issues:', <?php echo json_encode($formatted_issues); ?>);
    </script>
    <?php

    // Fetch archived core issues
    $archived_issues = get_posts([
        'post_type' => 'core_issues',
        'numberposts' => -1,
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'client',
                'value' => $client_id,
                'compare' => '='
            ],
            [
                'key' => 'status',
                'value' => 'archived',
                'compare' => '='
            ]
        ]
    ]);

    // Debug logging
    error_log('Archived Issues Count: ' . count($archived_issues));

    // Add styles to WordPress head
    add_action('wp_head', function() {
        ?>
        <style>
            .ccc-client-profile {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            .issues-section {
                margin-bottom: 40px;
            }
            .issues-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            .issue-card {
                background: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: transform 0.2s;
                height: 300px;
                display: flex;
                flex-direction: column;
            }
            .issue-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            .issue-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }
            .issue-name {
                font-size: 1.1em;
                font-weight: 600;
                color: #2c3e50;
                margin: 0;
            }
            .severity-chart {
                height: 200px !important;
                width: 100% !important;
                margin-top: 10px;
            }
            .section-title {
                color: #2c3e50;
                border-bottom: 2px solid #3498db;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            .no-issues {
                text-align: center;
                color: #7f8c8d;
                font-style: italic;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 10px;
            }
            .tracking-duration {
                background: #e8f4f8;
                padding: 5px 10px;
                border-radius: 15px;
                font-size: 0.9em;
                color: #2980b9;
            }
        </style>
        <?php
    });

    // Add chart initialization to footer
    add_action('wp_footer', function() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const charts = document.querySelectorAll('.severity-chart');
            
            charts.forEach((canvas, index) => {
                canvas.id = `severity-chart-${index}`;
                
                const existingChart = Chart.getChart(canvas);
                if (existingChart) {
                    existingChart.destroy();
                }

                const logs = JSON.parse(canvas.dataset.logs);
                const ctx = canvas.getContext('2d');
                
                if (logs.length > 0) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: logs.map(log => log.date),
                            datasets: [{
                                label: 'Severity',
                                data: logs.map(log => log.severity),
                                borderColor: '#3498db',
                                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            aspectRatio: 2,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 5,
                                    min: 0,
                                    grid: { display: false },
                                    ticks: { display: false }
                                },
                                x: {
                                    display: false,
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                } else {
                    ctx.font = '14px Arial';
                    ctx.fillStyle = '#7f8c8d';
                    ctx.textAlign = 'center';
                    ctx.fillText('No severity data available', canvas.width / 2, canvas.height / 2);
                }
            });
        });
        </script>
        <?php
    });

    // Output the main content
    ?>
    <div class="ccc-client-profile">
        <h2 class="section-title">Client Core Issues Overview</h2>

        <!-- Active Issues -->
        <div class="issues-section">
            <h3>Active Issues</h3>
            <?php if (!empty($formatted_issues)) : ?>
                <div class="issues-grid">
                    <?php foreach ($formatted_issues as $issue) : 
                        $duration_text = 'Just started';
                        if (!empty($issue['history'])) {
                            $first_date = new DateTime($issue['history'][0]['date']);
                            $current_date = new DateTime();
                            $interval = $first_date->diff($current_date);
                            $months = ($interval->y * 12) + $interval->m;
                            if ($interval->d > 15) {
                                $months++;
                            }
                            $month_text = $months == 1 ? 'month' : 'months';
                            $duration_text = "Tracking: {$months} {$month_text}";
                        }
                    ?>
                        <div class="issue-card">
                            <div class="issue-header">
                                <h4 class="issue-name"><?php echo esc_html($issue['title']); ?></h4>
                                <span class="tracking-duration">
                                    <?php echo esc_html($duration_text); ?>
                                </span>
                            </div>
                            <canvas class="severity-chart" 
                                   data-logs='<?php echo esc_attr(json_encode($issue['history'])); ?>'
                                   data-issue="<?php echo esc_attr($issue['title']); ?>"
                                   width="400" 
                                   height="200">
                            </canvas>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="no-issues">No active core issues</div>
            <?php endif; ?>
        </div>

        <!-- Archived Issues -->
        <div class="issues-section">
            <h3>Archived Issues</h3>
            <?php if (!empty($archived_issues)) : ?>
                <div class="issues-grid">
                    <?php foreach ($archived_issues as $issue) : 
                        $severity = get_field('severity', $issue->ID);
                        $history = get_field('history', $issue->ID) ?: [];
                    ?>
                        <div class="issue-card">
                            <div class="issue-header">
                                <h4 class="issue-name"><?php echo esc_html($issue->post_title); ?></h4>
                                <span class="tracking-duration">Archived</span>
                            </div>
                            <canvas class="severity-chart" 
                                   data-issue="<?php echo esc_attr($issue->post_title); ?>"
                                   data-logs="<?php echo esc_attr(json_encode($history)); ?>">
                            </canvas>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="no-issues">No archived core issues</div>
            <?php endif; ?>
        </div>
    </div>
    <?php
} catch (Exception $e) {
    ?>
    <script>
        console.error('CCC Client Profile Error:', <?php echo json_encode($e->getMessage()); ?>);
    </script>
    <?php
    return '<p class="error-message">Error loading client profile: ' . esc_html($e->getMessage()) . '</p>';
}

error_log('=== CCC CLIENT PROFILE DEBUG END ===');
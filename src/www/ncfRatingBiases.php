<?php
// Get year from URL parameter, default to current year
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$requested_year = $year;

// Validate year (assuming data exists from 2014 onwards)
$available_years = range(2014, date('Y'));
if (!in_array($year, $available_years)) {
    $year = date('Y');
}

// Function to read and process bias data
function readBiasData($year) {
    $filename = "/home/neville/cfbPlayoffPredictor/data/{$year}/fullSeasonCommitteeBiasMatrix.txt";
    
    if (!file_exists($filename)) {
        return [];
    }
    
    $data = [];
    $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $parts = explode(":", $line);
        if (count($parts) >= 12) {
            $team_data = [
                'team' => $parts[0],
                'week_9' => (float)$parts[1],
                'week_10' => (float)$parts[2],
                'week_11' => (float)$parts[3],
                'week_12' => (float)$parts[4],
                'week_13' => (float)$parts[5],
                'week_14' => (float)$parts[6],
                'average' => (float)$parts[8],
                'max_bias' => (float)$parts[9],
                'min_bias' => (float)$parts[10],
                'range' => (float)$parts[11]
            ];
            $data[] = $team_data;
        }
    }
    
    // Sort by average bias (descending)
    usort($data, function($a, $b) {
        return $b['average'] <=> $a['average'];
    });
    
    return $data;
}

// Function to get row color class based on average bias
function getRowColorClass($average_bias) {
    if ($average_bias > 0.03) {
        return 'overrated';
    } elseif ($average_bias < -0.03) {
        return 'underrated';
    } else {
        return 'neutral';
    }
}

// Function to format bias values
function formatBias($value) {
    if ($value == 0) return '0';
    return number_format($value, 4);
}

$bias_data = readBiasData($year);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $year; ?> NCAA Playoff Committee Rating Biases</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.4;
            color: #333;
            background-color: #f8f9fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 300;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .controls {
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .year-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
        }
        
        .year-selector select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            font-size: 16px;
        }
        
        .legend {
            padding: 20px 30px;
            background: #f8f9fa;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            border-radius: 3px;
        }
        
        .legend-color.overrated { background-color: #ffebee; border: 2px solid #f44336; }
        .legend-color.underrated { background-color: #e8f5e8; border: 2px solid #4caf50; }
        .legend-color.neutral { background-color: #f5f5f5; border: 2px solid #9e9e9e; }
        
        .table-container {
            overflow-x: auto;
        }
        
        .bias-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .bias-table th {
            padding: 8px 6px;
            text-align: center;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
            border-right: 1px solid #dee2e6;
        }
        
        .bias-table th.team-header {
            text-align: left;
            min-width: 120px;
            background: #495057;
            color: white;
        }
        
        .bias-table th.average-header {
            background: #1976d2;
            color: white;
            border-right: 2px solid #333;
        }
        
        .bias-table th.week-header {
            background: #495057;
            color: white;
        }
        
        .bias-table th.stats-header {
            background: #f57c00;
            color: white;
        }
        
        .bias-table td {
            padding: 4px 6px;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            height: 28px;
            line-height: 20px;
        }
        
        .bias-table td.team-name {
            text-align: left;
            font-weight: 500;
        }
        
        .bias-table td.average-col {
            border-right: 2px solid #333;
        }
        
        .bias-table td.stats-col {
            border-left: 2px solid #333;
        }
        
        .bias-table tr:hover {
            background-color: rgba(0,123,255,0.1);
        }
        
        .bias-table tr.overrated {
            background-color: #ffebee;
        }
        
        .bias-table tr.underrated {
            background-color: #e8f5e8;
        }
        
        .bias-table tr.neutral {
            background-color: #f5f5f5;
        }
        
        .positive-bias {
            color: #d32f2f;
            font-weight: 500;
        }
        
        .negative-bias {
            color: #388e3c;
            font-weight: 500;
        }
        
        .notes {
            padding: 20px 30px;
            background: #f8f9fa;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .notes h3 {
            margin-bottom: 10px;
            color: #495057;
        }
        
        .footer {
            padding: 20px 30px;
            text-align: center;
            background: #495057;
            color: white;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            
            .bias-table {
                font-size: 11px;
            }
            
            .bias-table th,
            .bias-table td {
                padding: 3px 4px;
                height: 24px;
            }
        }
    </style>

<link type="text/css" rel="stylesheet" href="style.css" />


</head>
<body>






	<!--banner and menu-->    
    <?php 
$banner = '/var/www/ppDocs/banner-and-menu.html';
$data = file($banner) or die('Could not read file!');
foreach ($data as $line) {
echo "$line";
}
    ?>  









    <div class="container">
        <div class="header">
            <h1><?php echo $year; ?> NCAA Playoff Committee Rating Biases</h1>
            <p>Comparing computer rankings to committee rankings</p>
        </div>
        
        <div class="controls">
            <div class="year-selector">
                <label for="year-select"><strong>Select Year:</strong></label>
                <select id="year-select" onchange="changeYear(this.value)">
                    <?php foreach (range(date('Y'), 2014) as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color overrated"></div>
                <span><strong>Overrated by Committee:</strong> Positive bias > 0.03 (committee values team more than computer)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color underrated"></div>
                <span><strong>Underrated by Committee:</strong> Negative bias < -0.03 (computer values team more than committee)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color neutral"></div>
                <span><strong>Neutral:</strong> Bias between -0.03 and 0.03 (committee and computer roughly agree)</span>
            </div>
        </div>
        
        <?php if (!empty($bias_data)): ?>
        <div class="table-container">
            <table class="bias-table">
                <thead>
                    <tr>
                        <th class="team-header">Team</th>
                        <th class="average-header">Average<br>All Weeks</th>
                        <th class="week-header">Week 9</th>
                        <th class="week-header">Week 10</th>
                        <th class="week-header">Week 11</th>
                        <th class="week-header">Week 12</th>
                        <th class="week-header">Week 13</th>
                        <th class="week-header">Week 14</th>
                        <th class="stats-header">Max Bias</th>
                        <th class="stats-header">Min Bias</th>
                        <th class="stats-header">Range</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bias_data as $team): ?>
                    <tr class="<?php echo getRowColorClass($team['average']); ?>">
                        <td class="team-name"><?php echo htmlspecialchars($team['team']); ?></td>
                        <td class="average-col <?php echo $team['average'] > 0 ? 'positive-bias' : ($team['average'] < 0 ? 'negative-bias' : ''); ?>">
                            <strong><?php echo formatBias($team['average']); ?></strong>
                        </td>
                        <td class="<?php echo $team['week_9'] > 0 ? 'positive-bias' : ($team['week_9'] < 0 ? 'negative-bias' : ''); ?>">
                            <?php echo formatBias($team['week_9']); ?>
                        </td>
                        <td class="<?php echo $team['week_10'] > 0 ? 'positive-bias' : ($team['week_10'] < 0 ? 'negative-bias' : ''); ?>">
                            <?php echo formatBias($team['week_10']); ?>
                        </td>
                        <td class="<?php echo $team['week_11'] > 0 ? 'positive-bias' : ($team['week_11'] < 0 ? 'negative-bias' : ''); ?>">
                            <?php echo formatBias($team['week_11']); ?>
                        </td>
                        <td class="<?php echo $team['week_12'] > 0 ? 'positive-bias' : ($team['week_12'] < 0 ? 'negative-bias' : ''); ?>">
                            <?php echo formatBias($team['week_12']); ?>
                        </td>
                        <td class="<?php echo $team['week_13'] > 0 ? 'positive-bias' : ($team['week_13'] < 0 ? 'negative-bias' : ''); ?>">
                            <?php echo formatBias($team['week_13']); ?>
                        </td>
                        <td class="<?php echo $team['week_14'] > 0 ? 'positive-bias' : ($team['week_14'] < 0 ? 'negative-bias' : ''); ?>">
                            <?php echo formatBias($team['week_14']); ?>
                        </td>
                        <td class="stats-col <?php echo $team['max_bias'] > 0 ? 'positive-bias' : ($team['max_bias'] < 0 ? 'negative-bias' : ''); ?>">
                            <?php echo formatBias($team['max_bias']); ?>
                        </td>
                        <td class="<?php echo $team['min_bias'] > 0 ? 'positive-bias' : ($team['min_bias'] < 0 ? 'negative-bias' : ''); ?>">
                            <?php echo formatBias($team['min_bias']); ?>
                        </td>
                        <td class="<?php echo $team['range'] > 0 ? 'positive-bias' : ($team['range'] < 0 ? 'negative-bias' : ''); ?>">
                            <?php echo formatBias($team['range']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="padding: 40px; text-align: center;">
            <h3>No data available for <?php echo $requested_year; ?></h3>
            <p>Please select a different year. Bias file for the current year is available after the committee releases initial rankings (late October / early November each year)</p>
        </div>
        <?php endif; ?>
        
        <div class="notes">
            <h3>Notes:</h3>
            <p><strong>Week 13:</strong> Last regular season week (Auburn-Alabama week)<br>
            <strong>Week 14:</strong> Conference championship week<br>
            <strong>Blank cells:</strong> Team is out of top 25 in both committee and computer rankings for that week</p>
        </div>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> PlayoffPredictor.com | 
            <a href="mailto:support@playoffpredictor.com" style="color: #ccc;">Contact</a></p>
        </div>
    </div>

    <script>
        function changeYear(year) {
            const url = new URL(window.location);
            url.searchParams.set('year', year);
            window.location.href = url.toString();
        }
        
        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects for better UX
            const rows = document.querySelectorAll('.bias-table tbody tr');
            rows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.005)';
                    this.style.transition = 'transform 0.1s ease';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>
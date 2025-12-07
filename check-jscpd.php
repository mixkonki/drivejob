<?php
echo "JSCPD Duplication Report:\n";
echo "========================\n";

$reportPath = '_reports/jscpd/jscpd-report.json';
if (file_exists($reportPath)) {
    $report = json_decode(file_get_contents($reportPath), true);
    echo "Total files analyzed: " . ($report['statistics']['total']['files'] ?? 'N/A') . "\n";
    echo "Duplicated lines: " . ($report['statistics']['total']['lines'] ?? 'N/A') . "\n";
    echo "Duplicated tokens: " . ($report['statistics']['total']['tokens'] ?? 'N/A') . "\n";
    echo "Duplication percentage: " . ($report['statistics']['total']['percentage'] ?? 'N/A') . "%\n";

    echo "\nTop duplicated files:\n";
    if (isset($report['duplicates'])) {
        $top = array_slice($report['duplicates'], 0, 5);
        foreach ($top as $i => $dup) {
            echo ($i + 1) . ". {$dup['firstFile']['name']} & {$dup['secondFile']['name']} ({$dup['lines']} lines)\n";
        }
    }
} else {
    echo "JSCPD report not found. Run: npm run jscpd\n";
}

<?php

function getLanguageWeight($filename) {
  $weights = [
    'html' => 0.5,
    'css' => 0.7,
    'js' => 1.0,
    'php' => 1.2,
    'py' => 1.1,
    'java' => 1.3,
    'c' => 1.5,
    'cpp' => 1.6,
    'h' => 1.4,
    'hpp' => 1.5,
    'cs' => 1.3,
    'go' => 1.2,
    'rb' => 1.1,
    'swift' => 1.4,
    'kt' => 1.3,
    'scala' => 1.4,
    'rs' => 1.5,
    'asm' => 2.0,
    'sql' => 0.9,
    'yaml' => 0.6,
    'json' => 0.5,
    'xml' => 0.7,
    'md' => 0.3,
    'txt' => 0.2
  ];

  $extension = pathinfo($filename, PATHINFO_EXTENSION);
  return isset($weights[$extension]) ? $weights[$extension] : 1.0;
}

function analyzeCommitMessage($message) {
  $message = strtolower($message);
  if (strpos($message, 'fix') !== false || strpos($message, 'bug') !== false) {
    return 0.8; // Bug fixes take less time
  } elseif (strpos($message, 'refactor') !== false) {
    return 1.3; // Refactoring takes more time
  }
  return 1.0; // Default multiplier
}

function estimateManHours($repoUrl, $developerExperience = 'mid') {

  $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'repo_' . uniqid();
  mkdir($tempDir);

  try {
    // Clone the repository
    $command = "git clone --bare $repoUrl \"$tempDir\" 2>&1";
    exec($command, $output, $returnVar);
    if ($returnVar !== 0) {
      throw new Exception("Failed to clone repository: " . implode(PHP_EOL, $output));
    }

    // Get git log with commit hash, date, and stats
    $command = "cd /d \"$tempDir\" && git log --pretty=format:\"%H|%ad|%s\" --date=iso --numstat 2>&1";
    exec($command, $logLines, $returnVar);
    if ($returnVar !== 0) {
      throw new Exception("Failed to get git log: " . implode(PHP_EOL, $logLines));
    }

    $totalManHours = 0;
    $previousTimestamp = null;
    $commitInfo = [];
    $totalCommits = 0;
    $totalLinesAdded = 0;
    $totalLinesDeleted = 0;
    $totalWeightedChanges = 0;

    lecho(str_pad("Commit Hash", 40) . " | " .
      str_pad("Timestamp", 20) . " | " .
      str_pad("Lines Added", 12) . " | " .
      str_pad("Lines Deleted", 12) . " | " .
      str_pad("File Weight", 12) . " | " .
      str_pad("Weighted Changes", 16) . " | " .
      str_pad("Message Analysis", 16) . " | " .
      str_pad("Adjusted Time (H)", 16) . " | " .
      str_pad("Cumulative Total (H)", 16));
    lecho(str_repeat("-", 160));

    foreach ($logLines as $line) {
      $weight = 1.0;
      if (strpos($line, '|') !== false) {
        // This is a commit line
        list($hash, $date, $message) = explode('|', $line, 3);
        $timestamp = strtotime($date);
        $commitInfo = [
          'hash' => $hash,
          'timestamp' => $timestamp,
          'message' => $message,
          'linesAdded' => 0,
          'linesDeleted' => 0,
          'weightedChanges' => 0,
          'files' => [] // Track files and their contributions
        ];
        $totalCommits++;
      } elseif (preg_match('/^(\d+)\s+(\d+)\s+(.+)$/', $line, $matches)) {
        // This is a stat line
        $additions = intval($matches[1]);
        $deletions = intval($matches[2]);
        $filename = $matches[3];
        $weight = getLanguageWeight($filename);
        $fileChanges = ($additions + $deletions) * $weight;

        $commitInfo['linesAdded'] += $additions;
        $commitInfo['linesDeleted'] += $deletions;
        $commitInfo['weightedChanges'] += $fileChanges;
        $commitInfo['files'][] = [
          'filename' => $filename,
          'additions' => $additions,
          'deletions' => $deletions,
          'weight' => $weight,
          'weightedChanges' => $fileChanges
        ];

        $totalLinesAdded += $additions;
        $totalLinesDeleted += $deletions;
        $totalWeightedChanges += $fileChanges;
      } elseif (empty($line) && !empty($commitInfo)) {
        // End of a commit block, calculate hours
        $netChanges = $commitInfo['weightedChanges'];
        $baselineTimePerLine = 1 / 60; // 1 minute per line (in hours)
        $weightedTime = $netChanges * $baselineTimePerLine;

        // Adjust based on commit message
        $messageMultiplier = analyzeCommitMessage($commitInfo['message']);
        $weightedTime *= $messageMultiplier;

        // Adjust based on developer experience
        switch ($developerExperience) {
          case 'junior':
            $weightedTime *= 1.5;
            break;
          case 'senior':
            $weightedTime *= 0.8;
            break;
          default:
            // Mid-level developer, no adjustment
            break;
        }

        // Add minimum time threshold (15 minutes)
        if ($weightedTime < 0.25) { // 0.25 hours = 15 minutes
          $weightedTime = 0.25;
        }

        // Add to total man-hours
        $totalManHours += $weightedTime;

        // Output commit statistics
        lecho(str_pad(substr($commitInfo['hash'], 0, 7), 40) . " | " .
          str_pad(date('Y-m-d H:i:s', $commitInfo['timestamp']), 20) . " | " .
          str_pad($commitInfo['linesAdded'], 12) . " | " .
          str_pad($commitInfo['linesDeleted'], 13) . " | " .
          str_pad(number_format($weight, 2), 12) . " | " .
          str_pad(number_format($commitInfo['weightedChanges'], 2), 16) . " | " .
          str_pad(number_format($messageMultiplier, 2), 16) . " | " .
          str_pad(number_format($weightedTime, 2), 16) . " | " .
          str_pad(number_format($totalManHours, 2), 16));

        // Output file details for the commit
        foreach ($commitInfo['files'] as $file) {
          lecho("  -> " . str_pad($file['filename'], 58) . " | " .
            str_pad($file['additions'], 12) . " | " .
            str_pad($file['deletions'], 13) . " | " .
            str_pad(number_format($file['weight'], 2), 12) . " | " .
            str_pad(number_format($file['weightedChanges'], 2), 16));
        }

        // $previousTimestamp = $commitInfo['timestamp'];
        $commitInfo = [];
      }
    }

    // Output summary statistics
    lecho("\nSummary Statistics:");
    lecho(str_repeat("-", 40));
    lecho("Total Commits: " . $totalCommits);
    lecho("Total Lines Added: " . $totalLinesAdded);
    lecho("Total Lines Deleted: " . $totalLinesDeleted);
    lecho("Total Weighted Changes: " . number_format($totalWeightedChanges, 2));
    lecho("Total Estimated Man-Hours: " . number_format($totalManHours, 2));

    return round($totalManHours, 3);
  } finally {
    // Clean up: remove the temporary directory
    $command = (PHP_OS === 'WINNT') ? "rmdir /s /q \"$tempDir\"" : "rm -rf \"$tempDir\"";
    exec($command);
  }
}

// Usage example
$repoUrl = 'https://github.com/andyg2/ant_simulation';
define('LOG_FILE', './logs/' . basename($repoUrl) . '.log');
if (file_exists(LOG_FILE)) {
  unlink(LOG_FILE);
}
$developerExperience = 'junior'; // Options: 'junior', 'mid', 'senior'
try {
  $manHours = estimateManHours($repoUrl, $developerExperience);
  lecho("Estimated man hours: $manHours");
} catch (Exception $e) {
  lecho("Error: " . $e->getMessage());
}
echo file_get_contents(LOG_FILE);

function lecho($message) {
  file_put_contents(LOG_FILE, $message . PHP_EOL, FILE_APPEND);
}

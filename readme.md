# Estimate Repository Hours

A PHP script to estimate the total man-hours required to develop a project based on the commit history of a Git repository. The script analyzes the number of lines added and deleted, weights them by file type, and adjusts the estimate based on commit messages and developer experience.

## Repository

<https://github.com/andyg2/estimate-repo-hours>

## Features

- Lines of Code (LOC) Analysis: Uses additions and deletions to calculate weighted changes.
- File Type Weighting: Different file types (e.g., .php, .js, .html) are assigned weights based on complexity.
- Commit Message Analysis: Adjusts time estimates based on the type of work (e.g., bug fixes, refactoring).
- Developer Experience: Adjusts estimates based on developer skill level (junior, mid, senior).
- Detailed Statistics: Outputs commit-level and file-level details for transparency.
- Minimum Time Threshold: Ensures small commits are assigned a reasonable minimum time (15 minutes).
- CLI Support: Accepts repository URL and developer experience level as command-line arguments.

## Installation

### Prerequisites

- PHP (version 7.4 or higher)
- Git

### Steps

1. Clone the repository:

```bash
git clone https://github.com/andyg2/estimate-repo-hours.git
cd estimate-repo-hours
```

2. Ensure PHP and Git are installed and accessible from the command line.

## Usage

### Basic Usage

Run the script with the repository URL and optional developer experience level:

```bash
php estimate-repo-hours.php <repository-url> [developer-experience]
```

- `<repository-url>`: The URL of the Git repository to analyze.
- `[developer-experience]`: Optional. Developer experience level (junior, mid, or senior). Defaults to mid.

### Example

```bash
php estimate-repo-hours.php https://github.com/andyg2/recamera-sscma-node-protocol-mqtt junior
```

### Output

The script outputs detailed statistics for each commit, including:

- Commit hash
- Timestamp
- Lines added and deleted
- File type weight
- Weighted changes
- Adjusted time (hours)
- Cumulative total hours

It also provides a summary of total commits, lines added/deleted, weighted changes, and estimated man-hours.

## How It Works

### Algorithm

1. Clone Repository: The script clones the repository into a temporary directory.
2. Fetch Git Log: It retrieves the commit history, including additions, deletions, and filenames.
3. Calculate Weighted Changes:
   - Each file type is assigned a weight based on complexity (e.g., .php = 1.2, .js = 1.0).
   - Weighted changes = (additions + deletions) × file type weight.
4. Adjust Time Estimate:
   - Baseline time: 1 minute per line of code.
   - Adjust based on commit message (e.g., bug fixes take less time, refactoring takes more).
   - Adjust based on developer experience (junior developers take longer, senior developers take less time).
5. Output Statistics: Detailed commit and file-level statistics are displayed, along with a summary.

### Example Calculation

For a commit with:

- 100 additions and 50 deletions in a PHP file (weight = 1.2).
- Commit message: "Fix bug in user authentication."
- Developer: Junior.

- Net changes: 100 + 50 = 150 lines.
- Weighted changes: 150 × 1.2 = 180.
- Baseline time: 180 × 1 minute = 180 minutes.
- Adjust for bug fix: 180 × 0.8 = 144 minutes.
- Adjust for junior developer: 144 × 1.5 = 216 minutes (3.6 hours).

## Customization

### File Type Weights

You can modify the getLanguageWeight function in the script to adjust weights for specific file types:

```php
function getLanguageWeight($filename) {
  $weights = [
    'html' => 0.5,
    'css' => 0.7,
    'js' => 1.0,
    'php' => 1.2,
    // Add or modify weights as needed
  ];
  $extension = pathinfo($filename, PATHINFO_EXTENSION);
  return isset($weights[$extension]) ? $weights[$extension] : 1.0;
}
```

### Commit Message Analysis

You can customize the analyzeCommitMessage function to adjust time estimates based on commit messages:

```php
function analyzeCommitMessage($message) {
  $message = strtolower($message);
  if (strpos($message, 'fix') !== false || strpos($message, 'bug') !== false) {
    return 0.8; // Bug fixes take less time
  } elseif (strpos($message, 'refactor') !== false) {
    return 1.3; // Refactoring takes more time
  }
  return 1.0; // Default multiplier
}
```

## Contributing

Contributions are welcome! If you'd like to improve the script, please:

1. Fork the repository.
2. Create a new branch for your feature or bug fix.
3. Submit a pull request with a detailed description of your changes.

## License

This project is licensed under the MIT License. See the LICENSE file for details.

## Acknowledgments

- Inspired by the need to estimate development effort for open-source projects.
- Thanks to the PHP and Git communities for providing the tools to make this possible.

## Support

If you have any questions or issues, please open an issue on the GitHub repository.

<?php
// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the JSON data
$data = file_get_contents('php://input');

// Validate it's valid JSON
$decoded = json_decode($data);
if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Create data directory if it doesn't exist
$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Save to file with timestamp
$filename = 'flowsheet.json';
$filepath = $dataDir . '/' . $filename;

// Also save a backup with timestamp
$backupFilename = 'flowsheet_' . date('Y-m-d_H-i-s') . '.json';
$backupPath = $dataDir . '/' . $backupFilename;

// Save main file
if (file_put_contents($filepath, $data) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save data']);
    exit;
}

// Save backup
file_put_contents($backupPath, $data);

// Clean up old backups (keep last 30)
$backups = glob($dataDir . '/flowsheet_*.json');
if (count($backups) > 30) {
    usort($backups, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    $toDelete = array_slice($backups, 0, count($backups) - 30);
    foreach ($toDelete as $file) {
        unlink($file);
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Data saved successfully',
    'timestamp' => date('Y-m-d H:i:s'),
    'backup' => $backupFilename
]);

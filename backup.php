<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include 'includes/auth.php';
include 'includes/config.php';

// Check if user is admin
checkAccess(['admin']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? null;

try {
    if ($action === 'create_backup') {
        // Disable output buffering to allow streaming response
        @ob_end_clean();
        
        $backupDir = 'backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupName = "kusso_backup_" . $timestamp;
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $backupName;

        // Create temporary directory
        if (!mkdir($tempDir)) {
            throw new Exception("Failed to create temporary directory");
        }

        // Step 1: Export Database
        $dbBackupFile = $tempDir . DIRECTORY_SEPARATOR . 'database_backup.sql';
        exportDatabase($dbBackupFile);

        // Step 2: Copy project files
        $filesDir = $tempDir . DIRECTORY_SEPARATOR . 'files';
        mkdir($filesDir);
        
        $excludeDirs = ['backups', 'node_modules', '.git', '.vscode', 'temp'];
        $excludeFiles = ['.DS_Store', '.gitignore', '*.log'];
        
        copyDirectory(__DIR__, $filesDir, $excludeDirs, $excludeFiles);

        // Step 3: Create ZIP file
        $zipFile = $backupDir . DIRECTORY_SEPARATOR . $backupName . '.zip';
        createZipFile($tempDir, $zipFile);

        // Step 4: Clean up temporary directory
        deleteDirectory($tempDir);

        echo json_encode([
            'success' => true,
            'message' => 'Backup created successfully',
            'filename' => $backupName . '.zip',
            'size' => formatBytes(filesize($zipFile))
        ]);
    } elseif ($action === 'delete_backup') {
        $filename = $_POST['filename'] ?? null;
        
        if (!$filename) {
            throw new Exception("Filename not provided");
        }
        
        $filename = basename($filename);
        $filepath = 'backups' . DIRECTORY_SEPARATOR . $filename;
        $backupDir = realpath('backups');
        $fullPath = realpath($filepath);

        if (!$fullPath || !$backupDir || !file_exists($filepath) || strpos($fullPath, $backupDir) !== 0) {
            throw new Exception("Invalid backup file");
        }

        if (!unlink($filepath)) {
            throw new Exception("Failed to delete backup file");
        }

        echo json_encode([
            'success' => true,
            'message' => 'Backup file deleted successfully',
            'filename' => $filename
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Operation failed: ' . $e->getMessage()
    ]);
}

// Helper Functions
function exportDatabase($outputFile) {
    global $conn;
    
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sql = "-- Kusso Database Backup\n";
    $sql .= "-- Backup Date: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Database: kusso\n\n";

    foreach ($tables as $table) {
        $sql .= "-- ============================================\n";
        $sql .= "-- Table: " . $table . "\n";
        $sql .= "-- ============================================\n\n";

        // Get CREATE TABLE statement
        $createResult = $conn->query("SHOW CREATE TABLE " . $table);
        $createRow = $createResult->fetch(PDO::FETCH_NUM);
        $sql .= $createRow[1] . ";\n\n";

        // Get all data
        $dataResult = $conn->query("SELECT * FROM " . $table);
        $rows = $dataResult->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $columnList = implode(', ', array_map(function($col) { return '`' . $col . '`'; }, $columns));

            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . addslashes($value) . "'";
                    }
                }
                $valueList = implode(', ', $values);
                $sql .= "INSERT INTO " . $table . " (" . $columnList . ") VALUES (" . $valueList . ");\n";
            }
            $sql .= "\n";
        }
    }

    if (file_put_contents($outputFile, $sql) === false) {
        throw new Exception("Failed to write database backup file");
    }
}

function copyDirectory($source, $destination, $excludeDirs = [], $excludeFiles = []) {
    $dir = opendir($source);
    
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        // Check if directory should be excluded
        if (in_array($file, $excludeDirs)) {
            continue;
        }

        $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
        $destPath = $destination . DIRECTORY_SEPARATOR . $file;

        if (is_dir($sourcePath)) {
            copyDirectory($sourcePath, $destPath, $excludeDirs, $excludeFiles);
        } else {
            // Check if file should be excluded
            $skip = false;
            foreach ($excludeFiles as $pattern) {
                if (fnmatch($pattern, $file)) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip) {
                copy($sourcePath, $destPath);
            }
        }
    }

    closedir($dir);
}

function createZipFile($sourceDir, $zipFile) {
    $zip = new ZipArchive();
    
    if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
        throw new Exception("Failed to create ZIP file");
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($sourceDir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    if (!$zip->close()) {
        throw new Exception("Failed to close ZIP file");
    }
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
    }

    return rmdir($dir);
}

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>

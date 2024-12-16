<?php
header('Content-Type: application/json');

define('UPLOAD_DIR', 'uploads/');

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'file_upload_db';

$response = ['status' => 'error', 'message' => ''];

// Function to validate path is within uploads directory
function isPathSafe($path) {
    $realPath = realpath($path);
    $uploadsPath = realpath(UPLOAD_DIR);
    return $realPath !== false && strpos($realPath, $uploadsPath) === 0;
}

// Function to recursively delete a directory and its contents
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Connect to database
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        $response['message'] = 'Database connection failed';
        echo json_encode($response);
        exit;
    }

    if (isset($_POST['type']) && isset($_POST['path'])) {
        $path = $_POST['path'];
        
        if (!isPathSafe($path)) {
            $response['message'] = 'Invalid path';
            echo json_encode($response);
            exit;
        }

        if ($_POST['type'] === 'folder') {
            // Handle folder deletion
            if (is_dir($path)) {
                // Delete all files in the folder from database first
                $stmt = $conn->prepare("DELETE FROM files WHERE filename LIKE ?");
                $pathPattern = basename($path) . '/%';
                $stmt->bind_param("s", $pathPattern);
                $stmt->execute();
                $stmt->close();

                // Now delete the directory and its contents
                if (deleteDirectory($path)) {
                    $response['status'] = 'success';
                    $response['message'] = 'Folder and its contents deleted successfully';
                } else {
                    $response['message'] = 'Error deleting folder';
                }
            } else {
                $response['message'] = 'Folder not found';
            }
        } 
        elseif ($_POST['type'] === 'file') {
            // Handle file deletion
            if (file_exists($path)) {
                // Delete from database first
                $filename = basename($path);
                $stmt = $conn->prepare("DELETE FROM files WHERE filename = ?");
                $stmt->bind_param("s", $filename);
                $stmt->execute();
                $stmt->close();

                // Then delete the file
                if (unlink($path)) {
                    $response['status'] = 'success';
                    $response['message'] = 'File deleted successfully';
                } else {
                    $response['message'] = 'Error deleting file';
                }
            } else {
                $response['message'] = 'File not found';
            }
        } else {
            $response['message'] = 'Invalid type specified';
        }
    } else {
        $response['message'] = 'Missing required parameters';
    }

    $conn->close();
}

echo json_encode($response);
?>
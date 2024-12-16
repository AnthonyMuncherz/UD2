<!-- upload.php -->

<?php
header('Content-Type: application/json');

define('UPLOAD_DIR', 'uploads/');
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $response = ['status' => 'error', 'message' => ''];
    
    $file = $_FILES['file'];
    $folder = isset($_POST['folder']) ? htmlspecialchars($_POST['folder'], ENT_QUOTES, 'UTF-8') : 'root';
    
    if ($file['error'] === 0) {
        $original_name = $file['name'];
        $ext = pathinfo($original_name, PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $upload_path = UPLOAD_DIR . ($folder !== 'root' ? $folder . '/' : '') . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Database connection
            $host = 'localhost';
            $username = 'root';
            $password = '';
            $database = 'file_upload_db';
            
            $conn = new mysqli($host, $username, $password, $database);
            if ($conn->connect_error) {
                $response['message'] = 'Database connection failed';
                echo json_encode($response);
                exit;
            }
            
            $mime_type = mime_content_type($upload_path);
            $stmt = $conn->prepare("INSERT INTO files (filename, original_name, folder, mime_type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $filename, $original_name, $folder, $mime_type);
            
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'File uploaded successfully';
            } else {
                $response['message'] = 'Database error';
            }
            $stmt->close();
            $conn->close();
        } else {
            $response['message'] = 'Upload failed';
        }
    } else {
        $response['message'] = 'File error: ' . $file['error'];
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>
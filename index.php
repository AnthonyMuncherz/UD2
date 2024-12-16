<!-- index.php -->

<?php
// Display current PHP upload settings and set limits
ini_set('upload_max_filesize', '3072M');
ini_set('post_max_size', '3072M');
ini_set('memory_limit', '4096M');
ini_set('max_execution_time', '3600');
ini_set('max_input_time', '3600');

// Configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 3221225472); // 3GB in bytes

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'file_upload_db';

$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create and setup database
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    $conn->select_db($database);
    
    // Create files table
    $sql = "CREATE TABLE IF NOT EXISTS files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        folder VARCHAR(100) DEFAULT 'root',
        upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        mime_type VARCHAR(100)
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating table: " . $conn->error);
    }
} else {
    die("Error creating database: " . $conn->error);
}

// Handle AJAX folder listing request
if (isset($_GET['action']) && $_GET['action'] === 'list_folder') {
    $folderPath = $_GET['path'];
    // Validate the path is within uploads directory
    if (strpos(realpath($folderPath), realpath(UPLOAD_DIR)) === 0) {
        echo listFolderContents($folderPath, $conn);
    }
    exit;
}

// Handle folder creation
if (isset($_POST['create_folder'])) {
    $folder_name = htmlspecialchars($_POST['folder_name'], ENT_QUOTES, 'UTF-8');
    $folder_path = UPLOAD_DIR . $folder_name;
    
    if (!file_exists($folder_path)) {
        mkdir($folder_path, 0777, true);
        header('Location: index.php?msg=Folder created successfully');
    } else {
        header('Location: index.php?error=Folder already exists');
    }
    exit;
}

// Function to list folder contents
function listFolderContents($path, $conn) {
    $items = scandir($path);
    $output = '';
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $fullPath = $path . '/' . $item;
        
        if (is_dir($fullPath)) {
            $output .= "<div class='folder-container'>";
            $output .= "<div class='folder-item' data-path='" . htmlspecialchars($fullPath, ENT_QUOTES, 'UTF-8') . "'>";
            $output .= "<span class='folder-toggle'>►</span>";
            $output .= "<i class='fas fa-folder'></i>";
            $output .= "<span class='folder-name'>" . htmlspecialchars($item) . "</span>";
            $output .= "<div class='folder-actions'>";
            $output .= "<button class='btn btn-sm btn-danger folder-delete-btn me-2' data-path='" . htmlspecialchars($fullPath, ENT_QUOTES, 'UTF-8') . "' data-name='" . htmlspecialchars($item) . "'><i class='fas fa-trash'></i></button>";
            $output .= "</div>";
            $output .= "</div>";
            $output .= "<div class='folder-content' style='display: none;'></div>";
            $output .= "</div>";
        } else {
            $stmt = $conn->prepare("SELECT * FROM files WHERE filename = ?");
            $stmt->bind_param("s", $item);
            $stmt->execute();
            $result = $stmt->get_result();
            $fileInfo = $result->fetch_assoc();
            $stmt->close();
            
            $output .= "<div class='file-item'>";
            $output .= "<i class='fas fa-file'></i>";
            $output .= "<span class='file-name'>" . ($fileInfo ? htmlspecialchars($fileInfo['original_name']) : htmlspecialchars($item)) . "</span>";
            $output .= "<div class='file-actions'>";
            $output .= "<button class='btn btn-sm btn-danger delete-btn me-2' data-file='" . htmlspecialchars($fullPath, ENT_QUOTES, 'UTF-8') . "' data-filename='" . ($fileInfo ? htmlspecialchars($fileInfo['original_name']) : htmlspecialchars($item)) . "'><i class='fas fa-trash'></i></button>";
            $relativePath = str_replace(UPLOAD_DIR, '', $fullPath); // Get path relative to uploads directory
            $output .= "<a href='https://zahar.my/UD2/uploads/" . htmlspecialchars($relativePath, ENT_QUOTES, 'UTF-8') . "' class='btn btn-sm btn-primary' download><i class='fas fa-download'></i></a> ";
            
            // Add preview buttons based on file type
            if ($fileInfo) {
                if (strpos($fileInfo['mime_type'], 'video/') === 0) {
                    $output .= "<button class='btn btn-sm btn-info preview-btn' data-type='video' data-file='" . htmlspecialchars($fullPath, ENT_QUOTES, 'UTF-8') . "'>";
                    $output .= "<i class='fas fa-play'></i>";
                    $output .= "</button>";
                } elseif (strpos($fileInfo['mime_type'], 'image/') === 0) {
                    $output .= "<button class='btn btn-sm btn-info preview-btn' data-type='image' data-file='" . htmlspecialchars($fullPath, ENT_QUOTES, 'UTF-8') . "'>";
                    $output .= "<i class='fas fa-eye'></i>";
                    $output .= "</button>";
                }
            }
            
            $output .= "</div>";
            $output .= "</div>";
        }
    }
    
    return $output;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .file-explorer {
            margin-top: 20px;
        }
        .folder-container {
            margin-bottom: 5px;
        }
        .folder-item, .file-item {
            padding: 8px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            transition: background-color 0.2s;
        }
        .folder-item:hover, .file-item:hover {
            background-color: #f8f9fa;
        }
        .folder-item i, .file-item i {
            margin-right: 10px;
            color: #6c757d;
        }
        .folder-item.active {
            background-color: #e9ecef;
        }
        .folder-content {
            padding-left: 25px;
        }
        .folder-toggle {
            cursor: pointer;
            padding: 0 5px;
            margin-right: 5px;
            user-select: none;
        }
        .folder-name {
            flex-grow: 1;
            cursor: pointer;
        }
        .folder-actions {
    opacity: 0.3;
    transition: opacity 0.2s;
    margin-left: auto;
}
.folder-item:hover .folder-actions {
    opacity: 1;
}
        .file-name {
            flex-grow: 1;
        }
        .file-actions {
            opacity: 0.3;
            transition: opacity 0.2s;
        }
        .file-item:hover .file-actions {
            opacity: 1;
        }
        .preview-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
        }
        .preview-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .preview-content img {
            border-radius: 4px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
        }
        .notification-warning {
    background-color: #ffc107;
    color: black;
}
        .close-preview {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 30px;
            cursor: pointer;
            z-index: 1001;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.5);
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }
        .close-preview:hover {
            background: rgba(0,0,0,0.8);
        }
        .notification-overlay {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 4px;
            z-index: 1050;
            display: none;
            animation: fadeInOut 3s ease-in-out;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .notification-success {
            background-color: #28a745;
            color: white;
        }
        .notification-error {
            background-color: #dc3545;
            color: white;
        }
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(-20px); }
            15% { opacity: 1; transform: translateY(0); }
            85% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-20px); }
        }
        .file-actions .btn {
            margin-right: 5px;
        }
        .delete-btn:hover {
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Notification Overlay -->
    <div id="notificationOverlay" class="notification-overlay"></div>
    
    <!-- Upload Progress Overlay -->
    <div id="uploadProgressOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1100;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; text-align: center; min-width: 300px;">
            <h4>Uploading File...</h4>
            <div class="progress" style="height: 25px; margin: 15px 0;">
                <div id="uploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
            </div>
            <div id="uploadStatus">Preparing upload...</div>
            <button id="cancelUpload" class="btn btn-danger mt-3">Cancel Upload</button>
        </div>
    </div>
    
    <div class="container mt-5">
        <h1 class="mb-4">File Upload System</h1>
        
        <!-- New Folder Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Create New Folder</h5>
                <form action="index.php" method="POST" class="row g-3">
                    <div class="col-auto">
                        <input type="text" class="form-control" name="folder_name" placeholder="Folder Name" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" name="create_folder" class="btn btn-primary">Create Folder</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Upload Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Upload File</h5>
                <form id="uploadForm" class="row g-3">
                    <div class="col-auto">
                        <input type="file" class="form-control" name="file" id="fileInput" required>
                        <small class="text-muted">Maximum file size: 3GB</small>
                    </div>
                    <div class="col-auto">
                        <select name="folder" class="form-select" id="folderSelect">
                            <option value="root">Root Directory</option>
                            <?php
                            $folders = array_filter(glob(UPLOAD_DIR . '*'), 'is_dir');
                            foreach ($folders as $folder) {
                                $folder_name = basename($folder);
                                echo "<option value=\"$folder_name\">$folder_name</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-success">Upload</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- File List -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Files and Folders</h5>
                <div class="file-explorer">
                    <?php
                    if (!isset($_GET['action'])) {
                        echo listFolderContents(UPLOAD_DIR, $conn);
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Overlay -->
    <div class="preview-overlay" id="previewOverlay">
        <div class="close-preview" onclick="closePreview()">&times;</div>
        <div class="preview-content" id="previewContent"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variable for current XHR request
        let currentXHR = null;

        function showPreview(filePath, type) {
            const overlay = document.getElementById('previewOverlay');
            const content = document.getElementById('previewContent');
            
            if (type === 'video') {
                content.innerHTML = `
                    <video controls style="max-width: 100%; max-height: 80vh;">
                        <source src="${filePath}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                `;
            } else if (type === 'image') {
                content.innerHTML = `
                    <img src="${filePath}" style="max-width: 100%; max-height: 80vh; object-fit: contain;" alt="Preview">
                `;
            }
            
            overlay.style.display = 'block';
        }

        function closePreview() {
            const overlay = document.getElementById('previewOverlay');
            const content = document.getElementById('previewContent');
            content.innerHTML = '';
            overlay.style.display = 'none';
        }

        function showNotification(message, type = 'success') {
            const overlay = document.getElementById('notificationOverlay');
            overlay.textContent = message;
            overlay.className = 'notification-overlay notification-' + type;
            overlay.style.display = 'block';

            // Trigger reflow to restart animation
            overlay.offsetHeight;
            
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 3000);
        }

        function loadFolderContents(folderPath, contentDiv) {
    fetch(`index.php?action=list_folder&path=${encodeURIComponent(folderPath)}`)
        .then(response => response.text())
        .then(html => {
            contentDiv.innerHTML = html;
            bindFolderEvents();
            bindPreviewEvents(); 
            bindDeleteEvents(); // Add this line
        })
        .catch(error => console.error('Error loading folder contents:', error));
}

        function bindFolderEvents() {
            document.querySelectorAll('.folder-item').forEach(item => {
                const toggle = item.querySelector('.folder-toggle');
                const folderName = item.querySelector('.folder-name');
                const contentDiv = item.nextElementSibling;
                const path = item.dataset.path;

                const toggleFolder = () => {
                    const isExpanded = toggle.textContent === '▼';
                    toggle.textContent = isExpanded ? '►' : '▼';
                    item.classList.toggle('active');
                    
                    if (!isExpanded && contentDiv.children.length === 0) {
                        loadFolderContents(path, contentDiv);
                    }
                    
                    contentDiv.style.display = isExpanded ? 'none' : 'block';
                };

                toggle.removeEventListener('click', toggleFolder);
                folderName.removeEventListener('click', toggleFolder);
                
                toggle.addEventListener('click', toggleFolder);
                folderName.addEventListener('click', toggleFolder);
            });
        }

        function bindPreviewEvents() {
            document.querySelectorAll('.preview-btn').forEach(button => {
                button.addEventListener('click', () => {
                    showPreview(button.dataset.file, button.dataset.type);
                });
            });
        }

        function handleDelete(type, path, name, elementToRemove) {
    const overlay = document.getElementById('deleteOverlay');
    const confirmBtn = document.getElementById('confirmDelete');
    const cancelBtn = document.getElementById('cancelDelete');
    const deleteMessage = document.getElementById('deleteMessage');
    
    // Set appropriate message based on type
    deleteMessage.textContent = type === 'folder' 
        ? `Warning: This will permanently delete "${name}" and all its contents.`
        : `Are you sure you want to delete "${name}"?`;
    
    // Show the overlay
    overlay.style.display = 'block';
    
    // Handle confirmation
    const handleConfirm = () => {
        const formData = new FormData();
        formData.append('path', path);
        formData.append('type', type);
        if (type === 'folder') {
            formData.append('force', 'true');
        }
        
        fetch('delete.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            overlay.style.display = 'none';
            if (data.status === 'success') {
                elementToRemove.remove();
                showNotification(
                    type === 'folder' 
                        ? 'Folder and its contents deleted successfully'
                        : 'File deleted successfully', 
                    'success'
                );
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.message || `Error deleting ${type}`, 'error');
            }
        })
        .catch(error => {
            overlay.style.display = 'none';
            console.error('Error:', error);
            showNotification(`Error during ${type} deletion`, 'error');
        });
        
        // Remove event listeners
        confirmBtn.removeEventListener('click', handleConfirm);
        cancelBtn.removeEventListener('click', handleCancel);
    };
    
    // Handle cancellation
    const handleCancel = () => {
        overlay.style.display = 'none';
        // Remove event listeners
        confirmBtn.removeEventListener('click', handleConfirm);
        cancelBtn.removeEventListener('click', handleCancel);
    };
    
    // Add event listeners
    confirmBtn.addEventListener('click', handleConfirm);
    cancelBtn.addEventListener('click', handleCancel);
}

function bindDeleteEvents() {
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const filePath = this.dataset.file;
            const fileName = this.dataset.filename;
            const fileItem = this.closest('.file-item');
            
            handleDelete('file', filePath, fileName, fileItem);
        });
    });
}

function bindFolderDeleteEvents() {
    document.querySelectorAll('.folder-delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const folderPath = this.dataset.path;
            const folderName = this.dataset.name;
            const folderContainer = this.closest('.folder-container');
            
            handleDelete('folder', folderPath, folderName, folderContainer);
        });
    });
}

        // Handle file upload with progress
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];
            const folder = document.getElementById('folderSelect').value;
            
            if (!file) {
                showNotification('Please select a file', 'error');
                return;
            }

            // Create FormData
            const formData = new FormData();
            formData.append('file', file);
            formData.append('folder', folder);

            // Create XMLHttpRequest
            const xhr = new XMLHttpRequest();
            currentXHR = xhr;
            
            const progressBar = document.getElementById('uploadProgressBar');
            const uploadStatus = document.getElementById('uploadStatus');
            const overlay = document.getElementById('uploadProgressOverlay');

            // Show progress overlay
            overlay.style.display = 'block';

            // Handle cancel button
            document.getElementById('cancelUpload').onclick = function() {
                if (currentXHR) {
                    currentXHR.abort();
                    showNotification('Upload cancelled', 'error');
                    overlay.style.display = 'none';
                    progressBar.style.width = '0%';
                    progressBar.textContent = '0%';
                    fileInput.value = '';
                    currentXHR = null;
                }
            };

            // Track upload progress
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percentComplete + '%';
                    progressBar.textContent = percentComplete + '%';
                    
                    // Update status text
                    if (percentComplete < 100) {
                        const uploadedMB = (e.loaded / (1024 * 1024)).toFixed(2);
                        const totalMB = (e.total / (1024 * 1024)).toFixed(2);
                        uploadStatus.textContent = `Uploaded ${uploadedMB}MB of ${totalMB}MB`;
                    } else {
                        uploadStatus.textContent = 'Processing...';
                    }
                }
            });

            // Handle completion
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        showNotification(response.message, response.status);
                        if (response.status === 'success') {
                            // Reload the file explorer
                            location.reload();
                        }
                    } catch (e) {
                        showNotification('Upload completed', 'success');
                        location.reload();
                    }
                } else {
                    showNotification('Upload failed', 'error');
                }
                overlay.style.display = 'none';
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                fileInput.value = '';
                currentXHR = null;
            });

            // Handle errors
            xhr.addEventListener('error', function() {
                showNotification('Upload failed', 'error');
                overlay.style.display = 'none';
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                fileInput.value = '';
                currentXHR = null;
            });

            // Configure and send request
            xhr.open('POST', 'upload.php', true);
            xhr.send(formData);
        });

    document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    console.log('DOM loaded, binding events...');
    const msg = urlParams.get('msg');
    const error = urlParams.get('error');
    
    if (msg) {
        showNotification(decodeURIComponent(msg), 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (error) {
        showNotification(decodeURIComponent(error), 'error');
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Initial binding
    bindFolderEvents();
    bindPreviewEvents();
    bindDeleteEvents(); 
    bindFolderDeleteEvents();
});
    </script>

<!-- Delete Confirmation Overlay -->
<div id="deleteOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1100;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; text-align: center; min-width: 300px;">
        <h4>Delete Confirmation</h4>
        <p id="deleteMessage" class="mt-3"></p>
        <div class="mt-4">
            <button id="confirmDelete" class="btn btn-danger me-2">Delete</button>
            <button id="cancelDelete" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

</body>
</html>
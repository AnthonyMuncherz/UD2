# File Upload System

This project implements a file upload system with support for large file uploads (up to 3GB), file management (deletion, folder creation), and a simple database integration for tracking uploaded files. The system uses PHP for server-side logic and MySQL for database operations.

## Features
- **Large File Upload Support**: Supports uploads of files up to 3GB.
- **Folder Management**: Create and delete folders.
- **File Management**: Upload, delete, and preview files.
- **Database Integration**: Stores file metadata (e.g., filename, original name, folder, MIME type) in a MySQL database.
- **Progress Tracking**: Real-time upload progress using AJAX.
- **File Previews**: Supports previews for images and videos.

## Prerequisites
1. **Web Server**: Apache with PHP enabled.
2. **Database**: MySQL.
3. **PHP Configuration**: Adjust PHP settings for large uploads.

### Required PHP Settings
Ensure the following PHP settings are configured in your `php.ini` or `.htaccess` file:

```apache
php_value upload_max_filesize 3072M
php_value post_max_size 3072M
php_value memory_limit 4096M
php_value max_execution_time 3600
php_value max_input_time 3600
```

## Installation
### 1. Setup Database
Create a MySQL database and configure the following settings in your PHP files:

| Parameter     | Default Value |
|---------------|---------------|
| Host          | `localhost`   |
| Username      | `root`        |
| Password      | `''`          |
| Database Name | `file_upload_db` |

Run the provided `index.php` file, which will create the database and `files` table automatically.

### 2. File Structure
Ensure your project has the following directory structure:
```
project-root/
|-- uploads/               # Directory for storing uploaded files
|-- upload.php             # Handles file uploads
|-- delete.php             # Handles file and folder deletion
|-- index.php              # Main interface and database initialization
|-- .htaccess              # PHP and Apache settings
|-- README.md              # Project documentation
```

### 3. Upload Directory Permissions
Ensure the `uploads/` directory has write permissions:
```bash
chmod -R 777 uploads/
```

## Usage
### 1. Run the Application
Access the application via your browser:
```
http://localhost/project-root/index.php
```

### 2. Create Folders
- Use the "Create New Folder" form to add new folders under the `uploads/` directory.

### 3. Upload Files
- Select a file and target folder using the upload form.
- Upload progress will be displayed in real-time.

### 4. Manage Files
- **Delete Files**: Use the delete button next to a file.
- **Delete Folders**: Use the delete button for folders (deletes all contents).
- **Preview Files**: Images and videos can be previewed directly in the browser.

## Security Considerations
1. **Path Validation**: The system validates all file and folder paths to prevent directory traversal attacks.
2. **Input Sanitization**: User inputs are sanitized using `htmlspecialchars` to prevent XSS attacks.
3. **Database Protection**: Prepared statements are used to prevent SQL injection.

## Notes
- The system supports only standard file formats for preview (e.g., images and videos).
- The file size limit is 3GB but can be adjusted based on server capabilities.

## Troubleshooting
- **Uploads Failing**: Ensure PHP and Apache settings match the recommended values.
- **Database Errors**: Verify your MySQL connection settings in the PHP files.
- **Permission Issues**: Check `uploads/` directory permissions.

## License
This project is licensed under the MIT License.

## Acknowledgments
This system uses the following libraries and tools:
- **Bootstrap**: For UI styling.
- **Font Awesome**: For icons.
- **AJAX**: For real-time upload progress.

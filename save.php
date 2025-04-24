<?php   
    // Handle HTML5 canvas direct PNG submission
    if ($_GET['applet'] === 'html5') {
        // Verify we have input data
        $raw_data = file_get_contents('php://input');
        if (!$raw_data) {
            header('Content-Type: text/plain');
            echo "error\nNo image data received";
            exit;
        }

        $save_id = basename($_GET['saveid']);
        $save_dir = "drawings/{$save_id}/";
        $image_path = $save_dir . 'image.png';
        $undo_path = $save_dir . 'undo.png';
        
        // Create directory with proper permissions
        if (!is_dir($save_dir)) {
            if (!@mkdir($save_dir, 0777, true)) {
                header('Content-Type: text/plain');
                echo "error\nFailed to create directory";
                exit;
            }
            chmod($save_dir, 0777);
        }
        
        // Verify directory is writable
        if (!is_writable($save_dir)) {
            header('Content-Type: text/plain');
            echo "error\nCannot write to save directory";
            exit;
        }

        // Backup existing image if it exists
        if (file_exists($image_path)) {
            if (!@copy($image_path, $undo_path)) {
                header('Content-Type: text/plain');
                echo "error\nFailed to backup existing image";
                exit;
            }
        }

        // Save the PNG data directly
        if (file_put_contents($image_path, $raw_data) === false) {
            // Restore from backup if save failed
            if (file_exists($undo_path)) {
                @copy($undo_path, $image_path);
            }
            header('Content-Type: text/plain');
            echo "error\nFailed to save image";
            exit;
        }

        // Validate the saved file
        if (!file_exists($image_path)) {
            header('Content-Type: text/plain');
            echo "error\nSaved file does not exist";
            exit;
        }

        // Verify file size matches input size
        if (strlen($raw_data) !== filesize($image_path)) {
            @unlink($image_path);
            header('Content-Type: text/plain');
            echo "error\nFile size mismatch";
            exit;
        }

        // Verify it's a valid PNG
        $image_info = @getimagesize($image_path);
        if ($image_info === false || $image_info[2] !== IMAGETYPE_PNG) {
            @unlink($image_path);
            header('Content-Type: text/plain');
            echo "error\nInvalid PNG file";
            exit;
        }

        // Success - send ok response
        header('Content-Type: text/plain');
        echo "ok";
        exit;
    }
?>
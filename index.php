<?php
// Force HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
	header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	exit;
}

// Directories
$drawings_dir = './drawings';
$web_dir = './web/';

// Ensure directories exist
if (!is_dir($drawings_dir)) {
	mkdir($drawings_dir, 0755, true);
}
if (!is_dir($web_dir)) {
	exit('<p>Error: Web directory not found!</p>');
}

// Handle new drawing creation
if (isset($_GET['action']) && $_GET['action'] === 'new') {
    $new_id = time();  // Unix timestamp for directory name
    $new_dir = $drawings_dir . '/' . $new_id;
    
    // Create new drawing directory
    if (!is_dir($new_dir) && mkdir($new_dir, 0755, true)) {
        // Create a blank 800x600 PNG
        $blank_image = imagecreatetruecolor(800, 600);
        // Fill with white background
        $white = imagecolorallocate($blank_image, 255, 255, 255);
        imagefill($blank_image, 0, 0, $white);
        // Save as PNG
        imagepng($blank_image, $new_dir . '/image.png');
        imagedestroy($blank_image);
        
        // Store creation timestamp in a file
        file_put_contents($new_dir . '/created', $new_id);
        
        // Redirect to edit the new drawing
        header('Location: ./?drawing=' . $new_id);
        exit;
    }
}

// Get available applets
$available_applets = [];
foreach (scandir($web_dir) as $file) {
	if (pathinfo($file, PATHINFO_EXTENSION) === 'jar') {
		$available_applets[] = $file;
	}
}

// Get list of drawings with correct timestamps
$drawings = [];
foreach (scandir($drawings_dir) as $file) {
    if (is_dir($drawings_dir . '/' . $file) && $file !== '.' && $file !== '..') {
        $dir_path = $drawings_dir . '/' . $file;
        $png_file = $dir_path . '/image.png';
        if (file_exists($png_file)) {
            // Use directory name as timestamp if it's numeric
            $timestamp = is_numeric($file) ? (int)$file : filemtime($dir_path);
            
            $drawings[$file] = [
                'date' => date('Y-m-d H:i:s', $timestamp),
                'file' => $png_file,
                'timestamp' => $timestamp
            ];
        }
    }
}

// Sort by timestamp descending (newest first)
uasort($drawings, function($a, $b) {
    return $b['timestamp'] - $a['timestamp'];
});

// Selected applet and drawing
$selected_applet = isset($_GET['applet']) && in_array($_GET['applet'], $available_applets) ? $_GET['applet'] : $available_applets[0];
$selected_drawing = isset($_GET['drawing']) && isset($drawings[$_GET['drawing']]) ? $_GET['drawing'] : '';
$selected_drawing_file = $selected_drawing ? $drawings[$selected_drawing]['file'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Oekaki Applet</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		html, body {
			height: 100%;
			margin: 0;
			padding: 0;
		}

		.navbar {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			z-index: 1030;
			height: 56px;
		}

		.container-fluid {
			padding: 0;
		}

		.container.mt-4 {
			margin-top: 72px !important; /* 56px navbar + 16px spacing */
			height: calc(100vh - 72px);
			padding: 0 15px;
		}

		.applet-container {
			height: 100%;
			width: 100%;
			overflow: hidden;
		}

		.applet-container iframe {
			width: 100%;
			height: 100%;
			border: none;
			display: block;
		}
	</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
	<div class="container-fluid">
		<a class="navbar-brand" href="./">Oekaki</a>
		<div class="collapse navbar-collapse">
			<ul class="navbar-nav me-auto">
				<?php foreach ($available_applets as $applet): ?>
					<li class="nav-item">
						<a class="nav-link <?= $selected_applet === $applet ? 'active' : '' ?>" 
						   href="?applet=<?= urlencode($applet) ?>">
							<?= htmlspecialchars(ucfirst($applet)) ?>
						</a>
					</li>
				<?php endforeach; ?>
				<li class="nav-item">
					<a class="nav-link btn btn-success text-white ms-2" 
					   href="?applet=javascript&drawing=<?= time() ?>">
						New Drawing
					</a>
				</li>
			</ul>
			<form class="d-flex" method="GET">
				<input type="hidden" name="applet" value="<?= htmlspecialchars($selected_applet) ?>">
				<select name="drawing" class="form-select me-2">
					<option value="">Select a drawing</option>
					<?php foreach ($drawings as $id => $data): ?>
						<option value="<?= htmlspecialchars($id) ?>" <?= $selected_drawing === $id ? 'selected' : '' ?>>
							<?= htmlspecialchars($data['date']) ?>
						</option>
					<?php endforeach; ?>
				</select>
				<button class="btn btn-primary" type="submit">Load Drawing</button>
			</form>
		</div>
	</div>
</nav>
<div class="container mt-4">
	<div class="applet-container">
		<?php if ($selected_applet): ?>
			<applet codebase="./web/" code="<?= htmlspecialchars($selected_applet) ?>" archive="<?= htmlspecialchars($selected_applet) ?>" width="800" height="600">
				<param name="drawing" value="<?= htmlspecialchars($selected_drawing) ?>">
				<h1>Javascript Paint Program</h1>
				<iframe 
					src="paint.html?drawing=<?= urlencode($selected_drawing) ?>" 
					style="width: 100%; height: 100vh; border: none;">
				</iframe>                
			</applet>
		<?php else: ?>
			<h1>Javascript Paint Program</h1>
			<iframe 
				src="paint.html?drawing=<?= urlencode($selected_drawing) ?>" 
				style="width: 100%; height: 100vh; border: none;">
			</iframe>
		<?php endif; ?>
	</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
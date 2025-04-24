<?php
/* 
 * Oekaki Drawing App
 * Requires drawings/ folder with write access
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validate and sanitize input
$applet = isset($_GET['applet']) ? $_GET['applet'] : 'shipainter';
$use_animation = isset($_GET['useanim']) && $_GET['useanim'] == '1';

// Validate applet type
$valid_applets = ['shipainter', 'shipainterpro', 'paintbbs', 'oekakibbs'];
if (!in_array($applet, $valid_applets)) {
    die('Invalid applet type specified');
}

// Check required directories
$required_dirs = [
    'drawings' => 'drawings/',
    'web' => 'web/',
    'shipainter' => 'web/shipainter/'
];

foreach ($required_dirs as $name => $dir) {
    if (!is_dir($dir)) {
        die("Required directory '$name' ($dir) not found");
    }
    if (!is_readable($dir)) {
        die("Directory '$name' ($dir) is not readable");
    }
    if ($name === 'drawings' && !is_writable($dir)) {
        die("Drawings directory is not writable");
    }
}

// Check required JAR files
$required_files = [
    'shipainter' => 'web/spainter_all.jar',
    'paintbbs' => 'web/PaintBBS.jar',
    'oekakibbs' => 'web/oekakibbs.jar'
];

foreach ($required_files as $type => $file) {
    if (!file_exists($file)) {
        die("Required file for $type ($file) not found");
    }
    if (!is_readable($file)) {
        die("Required file for $type ($file) is not readable");
    }
}

// Animation status links
$anim_status = $use_animation 
    ? 'ENABLED (<a href="?applet=' . htmlspecialchars($applet) . '&useanim=0">disable animation</a>)'
    : 'DISABLED (<a href="?applet=' . htmlspecialchars($applet) . '&useanim=1">enable animation</a>)';

$use_animation_query = $_GET['useanim'] ? '1' : '0';

echo <<<EOB
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>

<head>
<title>OekakiApplet Example</title>
</head>

<body>
<p>
    <b>Draw New Drawing:</b> <a href="?applet=shipainter&useanim=$use_animation_query">Shi-Painter</a> &middot; 
    <a href="?applet=shipainterpro&useanim=$use_animation_query">Shi-Painter Pro</a> &middot; 
    <a href="?applet=paintbbs&useanim=$use_animation_query">PaintBBS</a> &middot; 
    <a href="?applet=oekakibbs&useanim=$use_animation_query">OekakiBBS</a>
    <br />
    <b>Drawing NEW with Animation is:</b> $anim_status
</p>
EOB;

$dir = 'drawings/';
$drawings = array();

if( $handle = @opendir( $dir ) ) {
    while( FALSE !== ( $file = readdir( $handle ) ) )
    { 
        if ( $file != '.' && $file != '..' )
        { 
            $filetype = @filetype( $dir . $file );
            if( $filetype == 'dir' ) $drawings[] = $file;
        } 
        echo( ' ' );
        flush();
    }
    closedir( $handle ); 
}
else
{
    if( is_dir( $dir ) )
    {
        exit( '<p>The drawings directory cannot be read!</p></body></html>' );
    }
    else
    {
        exit( '<p>The drawings directory cannot be found or it is not a directory!</p></body></html>' );
    }
}

natsort( $drawings );

$drawings_html = '';

foreach( $drawings as $d )
{
    $drawing_applet = trim( file_get_contents( 'drawings/' . $d . '/appletinfo' ) );
    
    $drawings_html .= '<option value="' . htmlspecialchars( $d ) . '">' . htmlspecialchars( date( 'r', $d ) ) . ' (' . $drawing_applet . ')</option>';
}

echo <<<EOB
<form method="GET" action="?">
    <p>
        <b>Edit Existing Drawing:</b> <select size="1" name="edit">
            $drawings_html
        </select> <input type="submit" value="Edit" />
    </p>
</form>
EOB;

require_once 'OekakiApplet.php';

$OekakiApplet = new OekakiApplet;

if( $_GET['edit'] && is_dir( 'drawings/' . basename( $_GET['edit'] ) ) )
{
    $save_id = basename( $_GET['edit'] );
    
    $applet = trim( file_get_contents( 'drawings/' . $save_id . '/appletinfo' ) );
    
    if( $applet == 'oekakibbs' )
    {
        $animation_ext = 'oeb';
    }
    else
    {
        $animation_ext = 'pch';
    }

    // Set to URL of image to load image
    $OekakiApplet->load_image_url = 'drawings/' . $save_id . '/' . ( file_exists( 'drawings/' . $save_id . '/image.png' ) ? 'image.png' : 'image.jpg' );
    $OekakiApplet->load_animation_url = file_exists( 'drawings/' . $save_id . '/animation.' . $animation_ext ) ? 'drawings/' . $save_id . '/animation.' . $animation_ext : '';
    if( $OekakiApplet->load_animation_url )
    {
        $OekakiApplet->animation = TRUE;
    }
    else
    {
        $OekakiApplet->animation = FALSE;
    }
}
else
{
    $save_id = time() . '-' . rand( 10000, 99999 );
    $OekakiApplet->animation = $use_animation;
}

// Important to applet!
$OekakiApplet->applet_id                        = 'oekaki';

// Applet display
$OekakiApplet->applet_width                     = 700;
$OekakiApplet->applet_height                    = 500;

// Image display
$OekakiApplet->canvas_width                     = 300;
$OekakiApplet->canvas_height                    = 300;

// Saving
$OekakiApplet->url_save                         = 'save.php?applet=' . $applet . '&saveid=' . $save_id;
$OekakiApplet->url_finish                       = 'drawings/' . $save_id . '/';
$OekakiApplet->url_target                       = '_self';

// Format to save
$OekakiApplet->default_format                   = 'png';

switch( $applet )
{
    case 'shipainter':
    {
        echo $OekakiApplet->shipainter( './web/spainter_all.jar', './web/shipainter', FALSE );
        break;
    }
    case 'shipainterpro':
    {
        echo $OekakiApplet->shipainter( './web/spainter_all.jar', './web/shipainter', TRUE );
        break;
    }
    case 'paintbbs':
    {
        echo $OekakiApplet->paintbbs( './web/PaintBBS.jar', './web/shipainter' );
        break;
    }
    case 'oekakibbs':
    {
        echo $OekakiApplet->oekakibbs( './web/oekakibbs.jar' );
        break;
    }
}

echo <<<EOB
</body>
</html>
EOB;
?>
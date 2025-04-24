<?php
/**
 * OekakiApplet
 * Modernized version of sk89q's original OekakiApplet
 */
class OekakiApplet {
    // Properties with modern typing
    private string $applet_id = 'oekaki';
    private int $applet_width = 700;
    private int $applet_height = 500;
    private int $canvas_width = 300;
    private int $canvas_height = 300;
    private bool $animation = false;
    private string $url_save = '';
    private string $url_finish = '';
    private string $url_target = '_self';
    private string $default_format = 'png';
    
    // Setters
    public function setAppletId(string $value): void {
        $this->applet_id = $value;
    }
    
    public function setAppletWidth(int $value): void {
        $this->applet_width = $value;
    }
    
    public function setAppletHeight(int $value): void {
        $this->applet_height = $value;
    }
    
    public function setCanvasWidth(int $value): void {
        $this->canvas_width = $value;
    }
    
    public function setCanvasHeight(int $value): void {
        $this->canvas_height = $value;
    }
    
    public function setAnimation(bool $value): void {
        $this->animation = $value;
    }
    
    public function setUrlSave(string $value): void {
        $this->url_save = $value;
    }
    
    public function setUrlFinish(string $value): void {
        $this->url_finish = $value;
    }
    
    public function setUrlTarget(string $value): void {
        $this->url_target = $value;
    }
    
    public function setDefaultFormat(string $value): void {
        $this->default_format = $value;
    }

    /**
     * Creates OekakiBBS applet
     */
    public function oekakibbs(string $jar_path): string 
    {
        try {
            if (!file_exists($jar_path)) {
                throw new Exception("JAR file not found: $jar_path");
            }

            $params = [
                'MAYSCRIPT' => 'true',
                'scriptable' => 'true',
                'width' => $this->canvas_width,
                'height' => $this->canvas_height,
                'name' => $this->applet_id,
                'id' => $this->applet_id,
                'url_save' => $this->url_save,
                'url_finish' => $this->url_finish,
                'target' => $this->url_target,
                'animation' => $this->animation ? '1' : '0',
                'defaultImage' => '',
                'defaultAnimation' => ''
            ];
            
            return $this->generateAppletHtml('c.oekakibbs.OekakiBBS', $jar_path, $params);
        } catch (Exception $e) {
            error_log("Oekaki Error: " . $e->getMessage());
            return '<div class="alert alert-danger">Error loading OekakiBBS: ' . 
                   htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    /**
     * Generate HTML for Java applet with given parameters
     * @param string $class Java class name
     * @param string $jar_path Path to JAR file
     * @param array $params Applet parameters
     * @return string Generated HTML
     */
    private function generateAppletHtml(string $class, string $jar_path, array $params): string 
    {
        $output = sprintf(
            '<applet id="%s" code="%s" archive="%s" width="%d" height="%d" mayscript="true">',
            htmlspecialchars($this->applet_id),
            htmlspecialchars($class),
            htmlspecialchars($jar_path),
            $this->applet_width,
            $this->applet_height
        );
        
        foreach ($params as $key => $value) {
            if ($value !== '') {
                $output .= sprintf(
                    '<param name="%s" value="%s" />',
                    htmlspecialchars($key),
                    htmlspecialchars($value)
                ) . "\n";
            }
        }
        
        // Add fallback message for browsers without Java
        $output .= '<p>Your browser does not support Java applets or Java is disabled.</p>';
        $output .= '</applet>';
        
        return $output;
    }
}
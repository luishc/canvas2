<?php
include 'imagecreatefrombmp.php';

class canvas{

  private $file, $image, $temp_image;

  private $width, $height, $new_width, $new_height, $html_size;

  private $format, $extension, $size, $basename, $dirname;

  private $rgb = array(255, 255, 255);
  
  private $quality = 100;

  private $crop_coordinates;

  private $error;
  
  private $image_formats = array(
    'jpeg' => 2,
    'jpg' => 2,
    'gif' => 1,
    'png' => 3,
    'bmp' => 6
  );

  public function __construct($file = null){
    if($file){
      $this->file = $file;
      $this->image_info();
    } 
  }

  public function load($file){
    $this->file = $file;
    $this->image_info();
    return $this;
  }
  
  public function load_url($url){
    $this->file = $url;
    $this->file_info();
    if(!$this->format){
      $this->error = "Invalid image URL.";
    }else{
      $this->create_image();
      $this->width = imagesx($this->image);
      $this->height = imagesy($this->image);
    }
    return $this;
  }

  private function image_info(){
    if(is_file($this->file)){
      $this->file_info();
      if(!$this->is_image())
        $this->error = "Invalid file. {$this->file} is not an image file.";
      else
        $this->create_image();
    }else{
      $this->error = "File not accessible/found.";
    }
  }

  private function dimensions(){
    list($this->width, $this->height, $this->html_size, $this->format) = getimagesize($this->file);
    return $this; 
  }

  private function file_info(){
    $pathinfo = pathinfo($this->file);
    $this->extension = strtolower($pathinfo['extension']);
    $this->basename = $pathinfo['basename'];
    $this->dirname = $pathinfo['dirname'];
    $this->format = $this->image_formats[$this->extension];
  }

  private function is_image(){
    $this->dimensions();
    if(!$this->format)
      return false;
    else
      return true;
  }

  public function create_empty_image($width, $height){
    $this->width = $width;
    $this->height = $height;
    $this->image = imagecreatetruecolor($this->width, $this->height);
    $background_color = imagecolorallocate($this->image, $this->rgb[0], $this->rgb[1], $this->rgb[2]);
    imagefill($this->image, 0, 0, $background_color);
    $this->extension = 'jpg';
    return $this;
  }

  private function create_image(){
    $extension = ($this->extension = 'jpg' ? 'jpeg' : $this->extension);
    $function_name = "imagecreatefrom{$extension}";

    if(function_exists($function_name))
      $this->image = $function_name($this->file);
    else
      $this->error = "Invalid image file or imagecreat function not enabled.";

    return $this;
  }

  public function set_rgb($rgb){
    if(is_array($rgb)) $this->rgb = $rgb;
    else $this->hex_to_rgb($rgb);
    return $this;
  }

  private function hex_to_rgb($hex_color){
    $hex_color = str_replace( '#', '', $hex_color );
    if(strlen($hex_color) == 3) // #fff, #000 etc. 
      $hex_color .= $hex_color;
    $this->rgb = array(
      hexdec(substr($hex_color, 0, 2)),
      hexdec(substr($hex_color, 2, 2)),
      hexdec(substr($hex_color, 4, 2))
    );
    return $this;
  }

  public function set_crop_coordinates($x, $y){
    $this->crop_coordinates = array($x, $y, $this->width, $this->height);
    return $this;
  }

  public function resize($new_width = null, $new_height = null, $method = null){
    if(!$new_width && !$new_height){
      $this->error = "Inform a new width and/or a new height.";
      return false;
    }

    $this->new_width = $new_width;
    $this->new_height = $new_height;
    
    $this->calculate_new_dimensions();
    
    if($method) $method = "resize_with_{$method}";
    
    if(!method_exists($this, $method))
      $method = "resize_with_no_method";
    
    $this->$method();
    
    return $this;
  }
  
  private function calculate_new_dimensions(){
    $this->check_for_percentages();
    if(!$this->new_width)
      $this->new_width = $this->width/($this->height/$this->new_height);
    elseif(!$this->new_height)
      $this->new_height = $this->height/($this->width/$this->new_width);
  }
  
  private function check_for_percentages(){
    if(strpos($this->new_width, '%'))
      $this->new_width = round($this->width*(preg_replace('/[^0-9]/', '', $this->new_width)/100)); 
    if(strpos($this->new_height, '%'))
      $this->new_height = round($this->height*(preg_replace('/[^0-9]/', '', $this->new_height)/100));
  }

  private function resize_with_no_method(){
    $this->temp_image = imagecreatetruecolor($this->new_width, $this->new_height);
    imagecopyresampled($this->temp_image, $this->image, 0, 0, 0, 0, 
                       $this->new_width, $this->new_height, $this->width, $this->height);
    $this->image = $this->temp_image;
  }

  private function fill(){
    imagefill($this->temp_image, 0, 0, 
              imagecolorallocate($this->temp_image, $this->rgb[0], $this->rgb[1], $this->rgb[2]));
  }

  private function resize_with_fill(){
    $this->temp_image = imagecreatetruecolor($this->new_width, $this->new_height);
    $this->fill();

    // centers image into the filled image area
    // by width
    if(($this->width/$this->height) >= ($this->new_width/$this->new_height)){
        $dif_w = $this->new_width;
        $dif_h = $this->height*($this->new_width/$this->width);
        $dif_x = 0;
        $dif_y = round(($this->new_height-$dif_h)/2);
    // by height
    }else{
        $dif_w = $this->width*($this->new_height/$this->height);
        $dif_h = $this->new_height;
        $dif_x = round(($this->new_width-$dif_w)/2);
        $dif_y = 0;
    }
    
    imagecopyresampled($this->temp_image, $this->image, $dif_x, $dif_y, 0, 0, $dif_w, $dif_h, $this->width, $this->height);
    $this->image = $this->temp_image;
  }

  private function resize_with_crop(){
    if(!is_array($this->crop_coordinates))
      $this->crop_coordinates = array(0, 0, $this->width, $this->height);
      
    $this->temp_image = imagecreatetruecolor($this->new_width, $this->new_height);

    $this->fill();

    imagecopyresampled($this->temp_image, $this->image, $this->crop_coordinates[0], 
                       $this->crop_coordinates[1], 0, 0, $this->crop_coordinates[2], 
                       $this->crop_coordinates[3], $this->width, $this->height);

    $this->image = $this->temp_image;
  }

  public function flip($orientation = 'horizontal'){
    $w = imagesx($this->image);
    $h = imagesy($this->image);

    $this->temp_image = imagecreatetruecolor($w, $h);
    $method = "flip_{$orientation}";
    $this->$method($w, $h);

    $this->image = $this->temp_image;
    return $this;
  }
  
  private function flip_horizontal($w, $h){
    for($x = 0; $x < $w; $x++)
      imagecopy($this->temp_image, $this->image, $x, 0, $w - $x - 1, 0, 1, $h);
  }
  
  private function flip_vertical($w, $h){
    for($y = 0; $y < $h; $y++)
      imagecopy($this->temp_image, $this->image, 0, $y, 0, ($h - $y - 1), $w, 1);
  }

  public function rotate($degrees){
    $background_color = imagecolorallocate($this->image, $this->rgb[0], $this->rbg[1], $this->rgb[2]);
    
    $this->image = imagerotate($this->image, $degrees, $background_color);

    imagealphablending($this>image, true);
    imagesavealpha($this->image, true);

    $this->width = imagesx($this>image);
    $this->height = imagesy($this->image);

    return $this; 
  }

  public function add_text($text, $options = array()){
    $text_color = imagecolorallocate($this->image, $this->rgb[0], $this->rgb[1], $this->rgb[2]); 

    $dimensions = $this->text_dimensions(strlen($text), $options);

    if(is_string($options['x'] && is_string($options['y'])))
      list($options['x'], $options['y']) = $this->calculate_text_position($options['x'], $options['y'], $dimensions['width'], $dimensions['height']);

    if($options['background_color'])
      $this->text_background_color($dimensions, $options);

    if($options['truetype'])
      $this->add_true_type_text($text, $text_color, $options);
    else
      imagestring($this->image, $options['size'], $options['x'], $options['y'], $text, $text_color);
    

    return $this;
  }
  
  private function text_dimensions($length, $options){
    if($options['truetype']){
      $text_dimensions = imagettfbbox($options['size'], 0, $options['font'], $text);
      return array($text_dimensions[4], $options['size']);
    }else{
      if($options['size'] > 5) $size = 5;
      return array(imagefontwidth($options['size']*$length), imagefontheight($options['size']));
    }
  }

  private function calculate_text_position($x, $y, $width, $height){
    
    switch($y){
      case 'top':
      default:
        $y = 0;
        break;
      case 'bottom':
        $y = $this->height - $height;
        break;
      case 'middle':
          switch($x){
            case 'left':
            case 'right':
              $y = ($this->height/2)-($height/2);
              break;
            case 'center':
              $y = ($this->height-$height)/2;
              break;
          }
        break;
    }
    
    switch($x){
      case 'left':
      default:
        $x = 0;
        break;
      case 'center':
        $x = ($this->width-$width)/2;
        break;
      case 'right':
        $x = $this->width - $width;
        break;
    }
    
    return array($x, $y);
  }
  
  private function text_background_color($dimensions, $options){
    if(is_array($options['background_color']))
      $this->rgb = $options['background_color'];
    elseif(strlen($options['background_color'] > 3))
      $this->hex_to_rgb($options['background_color']);

    $this->temp_image = imagecreatetruecolor($dimensions['width'], $dimensions['height']);
    $background_color = imagecolorallocate($this->temp_image, $this->rgb[0], $this->rgb[1], $this->rgb[2]);
    imagefill($this->temp_image, 0, 0, $background_color);
    imagecopy($this->image, $this->temp_image, $options['x'], $options['y'], 0, 0, $dimensions['width'], $dimensions['height']);
  }

  private function add_truetype_text($text, $text_color, $options){
    $y = $options['y'] + $options['size'];
    imagettftext($this->image, $options['size'], 0, $options['x'], $options['y'], $text_color, $options['font'], $text);
  }

  public function merge($image, $position, $alpha = 100){
  
  }

  public function filter($filter, $ammount = 1, $arguments){
  
  }

  public function set_quality($quality){
    $this->quality = $quality;
    return $this;
  }

  public function save($destination){
    if(!is_dir(dirname($destination)))
      $this->error = "Invalid destination directory.";

    if(!$this->error){
      $this->output_image($destination);  
      return true;
    }else{
      return false;
    }
  }
  
  public function show(){
    header( "Content-type: image/{$this->extension}" );
    $this->output_image();
    imagedestroy($this->image);
    exit;
  }
  
  private function output_image($destination = null){
    $pathinfo = pathinfo($destination);
    $extension = ($pathinfo['extension'] ? strtolower($pathinfo['extension']) : $this->extension);
    if($extension == 'jpg' || $extension =='jpeg' || $extension == 'bmp')
      imagejpeg($this->image, $destination, $this->quality);
    elseif($extension == 'png')
      imagepng($this->image, $destination);
    elseif($extensions == 'gif')
      imagegif($this->image, $destination);
    else
      return false;
  }

  public function error_message(){
    return $this->error;
  }

}

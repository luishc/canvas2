<?php
class EffectsTest extends UnitTestCase {
  
  function __construct(){
    $this->img = new canvas(dirname(__FILE__)."/images/test_image.jpg");
  }
  
  function test_horizontal_flip(){
    $this->assertTrue($this->img->flip() instanceof canvas);
    $this->assertTrue($this->img->flip("horizontal") instanceof canvas);
  }
  
  function test_vertical_flip(){
    $this->assertTrue($this->img->flip("vertical") instanceof canvas);
  }
  
  function test_invalid_flip(){
    $this->assertFalse($this->img->flip("invalid") instanceof canvas);
  }
  
  function test_image_rotate(){
    $this->assertTrue($this->img->rotate(90) instanceof canvas);
  }
  
  function test_merge_image_with_no_alpha(){
    $position = array(0, 0);
    $this->assertTrue($this->img->merge(dirname(__FILE__)."/images/test_image.png", $position) instanceof canvas);
  }

  function test_merge_image_with_alpha(){
    $position = array(0, 0);
    $this->assertTrue($this->img->merge(dirname(__FILE__)."/images/test_image.png", $position, 70) instanceof canvas);
  }

  function test_merge_invalid_image(){
    $position = array(0, 0);
    $this->assertFalse($this->img->merge("invalid", $position, 70) instanceof canvas);
  }
  
  function test_merge_with_position_as_string(){
    $position = array("top", "left");
    $this->assertTrue($this->img->merge(dirname(__FILE__)."/images/test_image.png", $position, 70) instanceof canvas);
   }
  
  // filters
}
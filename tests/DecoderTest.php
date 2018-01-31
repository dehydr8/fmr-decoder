<?php 

class DecoderTest extends PHPUnit_Framework_TestCase {

  public function testIsThereAnySyntaxError() {
    $var = new dehydr8\FMR\Decoder("");
    $this->assertTrue(is_object($var));
    unset($var);
  }
}
<?php

class pageObject
{
  const title = '';
  const head = '';
  const body = '';

  private $head, $body;

  public function __construct(){
//    $this->width  = self::clamp($w);
//    $this->height = self::clamp($h);
  }

  public function __toString(){
    return "Dimension [head=$this->head, body=$this->body]";
  }
  
  public function addHead($string){
    $this->head  .= $string."\n";
  }
  
  
  public function addBody($string){
    $this->head  .= $string."\n";
  }
    
  /*
  protected static function clamp($value){
    if($value < self::MIN) $value = self::MIN;
    if($value > self::MAX) $value = self::MAX;
    return $value;
  }
  */
}



echo (new pageObject()) . '<br>';
echo (new pageObject(1500, 97)) . '<br>';
echo (new pageObject(14, -20)) . '<br>';
echo (new pageObject(240, 80)) . '<br>';

?>
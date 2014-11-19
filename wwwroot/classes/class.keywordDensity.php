<?php
/*
 ======================================================================
 KeywordDensityChecker v1.1.1
 
 Simple yet powerfull PHP class to get the keyword density of
 a website.
 
 by Stephan Schmitz, info@eyecatch-up.de
 
 Latest version, features, manual and examples:
     http://code.eyecatch-up.de/?p=155
 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ======================================================================
*/

/**
* KeywordDensityChecker
* Simple yet powerfull PHP class to get the keyword density of
* a website.
*/
class KD {
// -------------------------------------------------------------------
// @params
// -------------------------------------------------------------------
    var $domain;              // Domain to check
// -------------------------------------------------------------------
// PRIVATE FUNCTIONS
// -------------------------------------------------------------------
    // -------------------------------------------------------------------
    // Private Function cURL
    // -------------------------------------------------------------------
    private function cURL(){
      // -------------------------------------------------------------------
      // Save result page to string using curl
      // -------------------------------------------------------------------
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_URL,$this->domain);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
      $str = curl_exec($ch); 
      return $str;   
    } // End of private function cURL
    // -------------------------------------------------------------------
    // Private Function to return result page as string
    // -------------------------------------------------------------------
    private function plainText(){
      // -------------------------------------------------------------------
      // External classes
      // -------------------------------------------------------------------
      require_once('class.html2text.inc');
      // -------------------------------------------------------------------
      // Save google result page to string using curl
      // -------------------------------------------------------------------
      $str = $this->cURL();
      // -------------------------------------------------------------------
      // Extract the plain text
      // -------------------------------------------------------------------
      $extraction =& new html2text($str, true);
      $extraction->set_base_url($this->domain);
      // -------------------------------------------------------------------
      // Return string
      // -------------------------------------------------------------------
      return strtolower($extraction->get_text());  
    } // End of private function plainText
    // -------------------------------------------------------------------
    // Private Function to clean out the plain text
    // -------------------------------------------------------------------
    private function trim_replace($string) {
      $string = trim($string);
      return (string)str_replace(array("\r", "\r\n", "\n"), '', $string);
    }
    // -------------------------------------------------------------------
    // Private Function to calculate the keyword density from plain text
    // -------------------------------------------------------------------
    private function calcDensity(){   
      // -------------------------------------------------------------------
      // Prepare string
      // -------------------------------------------------------------------
      $words = explode(" ",$this->plainText());   
      $common_words = "i,he,she,it,and,me,my,you,the"; 
      $common_words = strtolower($common_words);
      $common_words = explode(",", $common_words);
      // -------------------------------------------------------------------
      // Get keywords
      // -------------------------------------------------------------------      
      $words_sum = 0;     
      foreach ($words as $value){
        $common = false;
        $value = $this->trim_replace($value);
        if (strlen($value) > 3){
          foreach ($common_words as $common_word){
            if ($common_word == $value){
              $common = true;
            }
          }
          if ($common != true){
            if (!preg_match("/http/i", $value) && !preg_match("/mailto:/i", $value)) {
              $keywords[] = $value;
              $words_sum++;
            }
          }
        }
      }    
      // -------------------------------------------------------------------
      // Do some maths and write array
      // ------------------------------------------------------------------- 
      $keywords = array_count_values($keywords);
      arsort($keywords);
      $results = array();
                  $results []= array(
                          'total words' => $words_sum
      );
      foreach ($keywords as $key => $value){
            $percent = 100 / $words_sum * $value;
                        $results []= array(
                    'keyword' => trim($key),
                                'count' => $value,
                                'percent' => round($percent, 2)
            );
      }
      // -------------------------------------------------------------------
      // Return array
      // -------------------------------------------------------------------
      return $results; 
    } // End of private function calcDensity
// -------------------------------------------------------------------
// PUBLIC FUNCTION
// -------------------------------------------------------------------
    // -------------------------------------------------------------------
    // Public Function to return the keyword density result array
    // -------------------------------------------------------------------
    public function result(){    
      return $this->calcDensity();      
    } // End of function KD
} // End of class KeywordDensityChecker v1.1.1 by Stephan Schmitz @ http://code.eyecatch-up.de/?p=155
?>
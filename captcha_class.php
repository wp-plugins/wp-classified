<?php

/* 
* This class is taken in part from the Code of 
* PhpCaptcha CAPTCHA generation library from Edward Eliot.
* class defaults - change to effect globally
*/
   session_start();
   define('CAPCC_WIDTH', 120); // max 500
   define('CAPCC_HEIGHT', 50); // max 200
   define('CAPCC_NUM_CHARS', 4);
   define('CAPCC_NUM_LINES', 20);
   define('CAPCC_CHAR_SHADOW', false);
   define('CAPCC_CHAR_SET', ''); // defaults to A-Z
   define('CAPCC_CASE_INSENSITIVE', true);
   define('CAPCC_BACKGROUND_IMAGES', '');
   define('CAPCC_MIN_FONT_SIZE', 12);
   define('CAPCC_MAX_FONT_SIZE', 20);
   define('CAPCC_USE_COLOUR', true);
   define('CAPCC_FILE_TYPE', 'png');
   
   /************************ End Default Options **********************/
   
   // don't edit below this line (unless you want to change the class!)
   
   class _captcha {
      var $oImage;
      var $iWidth = CAPCC_WIDTH;
      var $iHeight = CAPCC_HEIGHT;
      var $aFonts;
      var $iNumChars;
      var $iNumLines;
      var $iSpacing;
      var $bCharShadow;
      var $aCharSet;
      var $bCaseInsensitive;
      var $vBackgroundImages;
      var $iMinFontSize;
      var $iMaxFontSize;
      var $bUseColour;
      var $sFileType;
      var $sCode = '';
      
    
      function _captcha($aFonts) {
         // get parameters
         $this->aFonts = $aFonts;
         $this->SetNumChars(CAPCC_NUM_CHARS);
         $this->SetNumLines(CAPCC_NUM_LINES);
         $this->DisplayShadow(CAPCC_CHAR_SHADOW);
         $this->SetCharSet(CAPCC_CHAR_SET);
         $this->CaseInsensitive(CAPCC_CASE_INSENSITIVE);
         $this->SetBackgroundImages(CAPCC_BACKGROUND_IMAGES);
         $this->SetMinFontSize(CAPCC_MIN_FONT_SIZE);
         $this->SetMaxFontSize(CAPCC_MAX_FONT_SIZE);
         $this->UseColour(CAPCC_USE_COLOUR);
         $this->SetFileType(CAPCC_FILE_TYPE);   
         $this->SetWidth(CAPCC_WIDTH);
         $this->SetHeight(CAPCC_HEIGHT);
      }
      
      function CalculateSpacing() {
         $this->iSpacing = (int)($this->iWidth / $this->iNumChars);
      }
      
      function SetWidth($iWidth) {
         $this->iWidth = $iWidth;
         if ($this->iWidth > 500) $this->iWidth = 500; // to prevent perfomance impact
         $this->CalculateSpacing();
      }
      
      function SetHeight($iHeight) {
         $this->iHeight = $iHeight;
         if ($this->iHeight > 200) $this->iHeight = 200; // to prevent performance impact
      }
      
      function SetNumChars($iNumChars) {
         $this->iNumChars = $iNumChars;
         $this->CalculateSpacing();
      }
      
      function SetNumLines($iNumLines) {
         $this->iNumLines = $iNumLines;
      }
      
      function DisplayShadow($bCharShadow) {
         $this->bCharShadow = $bCharShadow;
      }

      
      function SetCharSet($vCharSet) {
         // check for input type
         if (is_array($vCharSet)) {
            $this->aCharSet = $vCharSet;
         } else {
            if ($vCharSet != '') {
               // split items on commas
               $aCharSet = explode(',', $vCharSet);
               // initialise array
               $this->aCharSet = array();
               // loop through items 
               foreach ($aCharSet as $sCurrentItem) {
                  // a range should have 3 characters, otherwise is normal character
                  if (strlen($sCurrentItem) == 3) {
                     // split on range character
                     $aRange = explode('-', $sCurrentItem);
                     // check for valid range
                     if (count($aRange) == 2 && $aRange[0] < $aRange[1]) {
                        // create array of characters from range
                        $aRange = range($aRange[0], $aRange[1]);
                        // add to charset array
                        $this->aCharSet = array_merge($this->aCharSet, $aRange);
                     }
                  } else {
                     $this->aCharSet[] = $sCurrentItem;
                  }
               }
            }
         }
      }
      
      function CaseInsensitive($bCaseInsensitive) {
         $this->bCaseInsensitive = $bCaseInsensitive;
      }
      
      function SetBackgroundImages($vBackgroundImages) {
         $this->vBackgroundImages = $vBackgroundImages;
      }
      
      function SetMinFontSize($iMinFontSize) {
         $this->iMinFontSize = $iMinFontSize;
      }
      
      function SetMaxFontSize($iMaxFontSize) {
         $this->iMaxFontSize = $iMaxFontSize;
      }
      
      function UseColour($bUseColour) {
         $this->bUseColour = $bUseColour;
      }
      
      function SetFileType($sFileType) {
         // check for valid file type
         if (in_array($sFileType, array('gif', 'png', 'jpeg'))) {
            $this->sFileType = $sFileType;
         } else {
            $this->sFileType = 'jpeg';
         }
      }
      
      function DrawLines() {
         for ($i = 0; $i < $this->iNumLines; $i++) {
            // allocate colour
            if ($this->bUseColour) {
               $iLineColour = imagecolorallocate($this->oImage, rand(100, 250), rand(100, 250), rand(100, 250));
            } else {
               $iRandColour = rand(100, 250);
               $iLineColour = imagecolorallocate($this->oImage, $iRandColour, $iRandColour, $iRandColour);
            }
            // draw line
            imageline($this->oImage, rand(0, $this->iWidth), rand(0, $this->iHeight), rand(0, $this->iWidth), rand(0, $this->iHeight), $iLineColour);
         }
      }
      
      function GenerateCode() {
         // reset code
         $this->sCode = '';
         // loop through and generate the code letter by letter
         for ($i = 0; $i < $this->iNumChars; $i++) {
            if (count($this->aCharSet) > 0) {
               // select random character and add to code string
               $this->sCode .= $this->aCharSet[array_rand($this->aCharSet)];
            } else {
               // select random character and add to code string
               $this->sCode .= chr(rand(65, 90));
            }
         }
         // save code in session variable
         if ($this->bCaseInsensitive) {
            $_SESSION[CAPCC_SESSION_ID] = strtoupper($this->sCode);
         } else {
            $_SESSION[CAPCC_SESSION_ID] = $this->sCode;
         }
      }
      
      function DrawCharacters() {
         // loop through and write out selected number of characters
         for ($i = 0; $i < strlen($this->sCode); $i++) {
            // select random font
            $sCurrentFont = $this->aFonts[array_rand($this->aFonts)]; 
	    // select random colour
            if ($this->bUseColour) {
               $iTextColour = imagecolorallocate($this->oImage, rand(0, 100), rand(0, 100), rand(0, 100));
               if ($this->bCharShadow) {
                  // shadow colour
                  $iShadowColour = imagecolorallocate($this->oImage, rand(0, 100), rand(0, 100), rand(0, 100));
               }
            } else {
               $iRandColour = rand(0, 100);
               $iTextColour = imagecolorallocate($this->oImage, $iRandColour, $iRandColour, $iRandColour);
               if ($this->bCharShadow) {
                  // shadow colour
                  $iRandColour = rand(0, 100);
                  $iShadowColour = imagecolorallocate($this->oImage, $iRandColour, $iRandColour, $iRandColour);
               }
            }
            // select random font size
            $iFontSize = rand($this->iMinFontSize, $this->iMaxFontSize);
            // select random angle
            $iAngle = rand(-30, 30);
            // get dimensions of character in selected font and text size
            $aCharDetails = imageftbbox($iFontSize, $iAngle, $sCurrentFont, $this->sCode[$i], array());
            // calculate character starting coordinates
            $iX = $this->iSpacing / 4 + $i * $this->iSpacing;
            $iCharHeight = $aCharDetails[2] - $aCharDetails[5];
            $iY = $this->iHeight / 2 + $iCharHeight / 4; 
            // write text to image
            imagefttext($this->oImage, $iFontSize, $iAngle, $iX, $iY, $iTextColour, $sCurrentFont, $this->sCode[$i], array());
            if ($this->bCharShadow) {
               $iOffsetAngle = rand(-30, 30);
               $iRandOffsetX = rand(-5, 5);
               $iRandOffsetY = rand(-5, 5);
               imagefttext($this->oImage, $iFontSize, $iOffsetAngle, $iX + $iRandOffsetX, $iY + $iRandOffsetY, $iShadowColour, $sCurrentFont, $this->sCode[$i], array());
            }
         }
      }
      
      function create($sFilename = '') {
         // check for required gd functions
         if (!function_exists('imagecreate') || 
			 !function_exists("image$this->sFileType") || ($this->vBackgroundImages != '' && !function_exists('imagecreatetruecolor'))) {
  				return false;
         }
         // get background image if specified and copy to CAPTCHA
         if (is_array($this->vBackgroundImages) || $this->vBackgroundImages != '') {
            // create new image
            $this->oImage = imagecreatetruecolor($this->iWidth, $this->iHeight);
            // create background image
            if (is_array($this->vBackgroundImages)) {
               $iRandImage = array_rand($this->vBackgroundImages);
               $oBackgroundImage = imagecreatefromjpeg($this->vBackgroundImages[$iRandImage]);
            } else {
               $oBackgroundImage = imagecreatefromjpeg($this->vBackgroundImages);
            }
            // copy background image
            imagecopy($this->oImage, $oBackgroundImage, 0, 0, 0, 0, $this->iWidth, $this->iHeight);
            // free memory used to create background image
            imagedestroy($oBackgroundImage);
         } else {
            // create new image
            $this->oImage = imagecreate($this->iWidth, $this->iHeight);
	    //$this->oImage = imagecreatetruecolor($this->iWidth, $this->iHeight);
         }
         // allocate white background colour
         imagecolorallocate($this->oImage, 255, 255, 255);
         // check for background image before drawing lines
         if (!is_array($this->vBackgroundImages) && $this->vBackgroundImages == '') {
            $this->DrawLines();
         }
         $this->GenerateCode();
         $this->DrawCharacters();
         // write out image to file or browser
         $this->WriteFile($sFilename);
         // free memory used in creating image
         imagedestroy($this->oImage);
         return true;
      }

      function WriteFile($sFilename) {
	if ($sFilename == '') {
            // tell browser that data is jpeg
            header("Content-type: image/" . $this->sFileType );
	}
	switch ($this->sFileType) {
            case 'gif':
               $sFilename != '' ? imagegif($this->oImage, $sFilename) : imagegif($this->oImage);
               break;
            case 'png':
               $sFilename != '' ? imagepng($this->oImage, $sFilename) : imagepng($this->oImage);
               break;
            default:
               $sFilename != '' ? imagejpeg($this->oImage, $sFilename) : imagejpeg($this->oImage);
         }
      }

      // call this method statically
      function Validate($sUserCode, $bCaseInsensitive = true) {
         if ($bCaseInsensitive) {
            $sUserCode = strtoupper($sUserCode);
         }
         if (!empty($_SESSION[CAPCC_SESSION_ID]) && $sUserCode == $_SESSION[CAPCC_SESSION_ID]) {
            // clear to prevent re-use
            unset($_SESSION[CAPCC_SESSION_ID]);
            return true;
         }
         return false;
      }
	  
   }
   

   // example sub class
   class _captchaColour extends _captcha {
      function _captchaColour($aFonts) {
         // call parent constructor
         parent::_captcha($aFonts);
         
         // set options
         $this->UseColour(true);
      }
   }
?>

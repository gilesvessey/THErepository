<?php

$callnum = 'AP52 .U5'; #a test call number for debugging

function isLetter($str) {  return strlen($str) == 1 && preg_match("/[a-z]/i", $str); }
function isSpace($str) {  return strlen($str) == 1 && preg_match("/ /i", $str);}
function isPeriod($str) {  return strlen($str) == 1 && preg_match("/\./i",$str);}
function isNumber($str) {  return strlen($str) == 1 && preg_match("/[0-9]/i", $str);}
#padwithZeroes takes a string and inserts as many 0s to the beginning of the string as it takes until string reaches at least length of param: $width. Which means it could also insert no 0s if the string $n is longer than $width.
function padWithZeroes($n, $width) { while(strlen($n) < $width) $n = '0'.$n; return $n;}

$position_counter = 0; #used as sort of an index while going through the call number.
$lc_class = ""; #lc class
$subject_val = ""; #lc subject
$cutter1 = ""; #cutter1
$cutter2 = ""; #cutter2
$pubyear = ""; #publication year
$rest_of_callnum = ""; #garbage


#STEP ONE: GET Class. Uses substrings to find out where the class starts and where the class ends.
$classstart = $position_counter; 
$classend = $position_counter+1;
if (isLetter(substr($callnum,$position_counter+3,1)) && (isLetter(substr($callnum,$position_counter+2,1)) )) { $classend = $position_counter+4;} 
elseif (isLetter(substr($callnum,$position_counter+2,1)) && isLetter(substr($callnum,$position_counter+1,1))) { $classend = $position_counter+3; } 
elseif (isLetter(substr($callnum,$position_counter+1,1))) { $classend = $position_counter+2; } 
else { $classend = $position_counter+1; }; 

$lc_class = substr($callnum, $classstart,$classend - $classstart);
$position_counter = $classend;

#echo $lc_class; #debug 

#INTERMEDIATE STEP: Check to see if there is a SPACE or a PERIOD at current position. And if so jump ahead of it.
if (isSpace(substr($callnum,$position_counter,1))) { $position_counter++;}
if (isPeriod(substr($callnum,$position_counter,1))) { $position_counter++;}

#STEP TWO: Get the subject
$subjectstart = $position_counter; 
$subjectend = $position_counter+1; 

if (isNumber(substr($callnum,$position_counter+3,1)) && (isNumber(substr($callnum,$position_counter+2,1)))) { $subjectend = $position_counter+4;} 
elseif (isNumber(substr($callnum,$position_counter+2,1)) && isNumber(substr($callnum,$position_counter+1,1))) { $subjectend = $position_counter+3;} 
elseif (isNumber(substr($callnum,$position_counter+1,1)) && isNumber(substr($callnum,$position_counter,1))) { $subjectend = $position_counter+2;} 
else {$subjectend = $subjectstart+1; }

$subject_val = padWithZeroes(substr($callnum,$subjectstart,$subjectend - $subjectstart),4); 
$position_counter = $subjectend;

#if there is a SPACE at current position then jump ahead of it.
if (isSpace(substr($callnum,$position_counter,1))) { $position_counter++;}

#check to see if subject has a decimal number
if (isPeriod(substr($callnum,$position_counter,1)) && isNumber(substr($callnum,$position_counter+1,1))) {
    $position_counter++; 
    $subject_val = $subject_val."."; 
    $subject_decimal_start = $position_counter; 
    $subject_decimal_end = $position_counter+1;
    if (isNumber(substr($callnum,$position_counter+3,1)) && isNumber(substr($callnum,$position_counter+2,1))) { $subject_decimal_end = $position_counter+4; } 
    elseif (isNumber(substr($callnum,$position_counter+2,1)) && isNumber(substr($callnum,$position_counter+1,1))) { $subject_decimal_end = $position_counter+3; } 
    elseif (isNumber(substr($callnum,$position_counter+1,1))) { $subject_decimal_end = $position_counter+2; } 
    else {$subject_decimal_end = $position_counter+1; } 
    $subject_val = $subject_val.padWithZeroes(substr($callnum,$subject_decimal_start,$subject_decimal_end - $subject_decimal_start),4);
    $position_counter = $subject_decimal_end; 
}
elseif (isPeriod(substr($callnum,$position_counter,1)) && !isNumber(substr($callnum,$position_counter+1,1))) { $position_counter++; $subject_val = $subject_val.".0";}

#echo $subject_val; #debug

#INTERMEDIATE STEP: Check to see if there is a SPACE or a PERIOD at current position. And if so jump ahead of it.
if (isSpace(substr($callnum,$position_counter,1))) { $position_counter++;}
if (isPeriod(substr($callnum,$position_counter,1))) { $position_counter++;}



#STEP THREE: Get first cutter if it exists. Cutter is always a letter followed by up to 3 digits.
if (isLetter(substr($callnum,$position_counter,1))) { 
    $cutter1 = ".".substr($callnum,$position_counter,1);
    #echo $cutter1;
    $position_counter++; 
    $cutterstart = $position_counter; 
    $cutterend = $position_counter; 
    #now get the whole number that goes with that cutter, we can be sure the next character is a number 
    if (isNumber(substr($callnum,$position_counter+3,1)) && isNumber(substr($callnum,$position_counter+2,1))) { $cutterend = $position_counter+4; } 
    else if (isNumber(substr($callnum,$position_counter+2,1)) && isNumber(substr($callnum,$position_counter+1,1))) { $cutterend = $position_counter+3; } 
    else if (isNumber(substr($callnum,$position_counter+1,1)) && isNumber(substr($callnum,$position_counter,1))) { $cutterend = $position_counter+2; } 
    else { $cutterend = $cutterstart+1; } 

    $cutter1 = $cutter1.padWithZeroes(substr($callnum,$cutterstart,$cutterend-$cutterstart),4); 
    $position_counter = $cutterend; 
    #echo $cutter1;
}

#INTERMEDIATE STEP: Check to see if there is a SPACE or a PERIOD at current position. And if so jump ahead of it.
if (isSpace(substr($callnum,$position_counter,1))) { $position_counter++;}
if (isPeriod(substr($callnum,$position_counter,1))) { $position_counter++;}

#STEP FOUR: Get second cutter if it exists. Cutter is always a letter followed by up to 3 digits.
if (isLetter(substr($callnum,$position_counter,1))) { 
    $cutter2 = substr($callnum,$position_counter,1); #cutter2 = cutter2.toUpperCase(); 
    $position_counter++; 
    $cutter2start = $position_counter; 
    $cutter2end = $position_counter; 
    #now get the whole number that goes with that cutter, we can be sure the next character is a number 
    if (isNumber(substr($callnum,$position_counter+3,1)) && isNumber(substr($callnum,$position_counter+2,1))) { $cutter2end = $position_counter+4; } 
    else if (isNumber(substr($callnum,$position_counter+2,1)) && isNumber(substr($callnum,$position_counter+1,1))) { $cutter2end = $position_counter+3; } 
    else if (isNumber(substr($callnum,$position_counter+1,1)) && isNumber(substr($position_counter,1))) { $cutter2end = $position_counter+2; } 
    else {$cutter2end = $cutter2start+1; } 
    $cutter2 = $cutter2.padWithZeroes(substr($callnum,$cutter2start,$cutter2end - $cutter2start),4); 
    $position_counter = $cutter2end; 
};#end of looking for  cutter2 

#INTERMEDIATE STEP: Check to see if there is a SPACE or a PERIOD at current position. And if so jump ahead of it.
if (isSpace(substr($callnum,$position_counter,1))) { $position_counter++;}
if (isPeriod(substr($callnum,$position_counter,1))) { $position_counter++;}

#STEP FIVE: Look for publication year (an optional field, but if it exists it is exactly 4 digits)
if (isNumber(substr($callnum,$position_counter,$position_counter+1)))
    {
        $pubyear = substr($callnum,$position_counter,$position_counter+4);
        $position_counter = $position_counter + 4;
    }

#INTERMEDIATE STEP: Check to see if there is a SPACE or a PERIOD at current position. And if so jump ahead of it.
if (isSpace(substr($callnum,$position_counter,1))) { $position_counter++;}
if (isPeriod(substr($callnum,$position_counter,1))) { $position_counter++;}

#STEP SIX: Rest of call number is not very consistent, so we just throw it all in at the end
if (strlen($callnum) > $position_counter+1) { 
    $rest_of_callnum = substr($callnum,$position_counter,strlen($callnum));
}

echo $lc_class;
echo $subject_val;
echo $cutter1;
echo $cutter2;
echo $pubyear;
echo $rest_of_callnum;

?>
<?php
session_start();

$chars = 'abcdefghkmnprstuvwxyzABCDEFGHJKLMNPQRSTUV2345689';
$length = 6;
$code = '';
for($i = 0; $i < $length; $i++) {
    $pos = mt_rand(0, strlen($chars)-1);
    $code .= substr($chars, $pos, 1);
}

$_SESSION['captcha'] = $code;

$width = 120;
$height = 30;
$r = mt_rand(160, 255);
$g = mt_rand(160, 255);
$b = mt_rand(160, 255);

$image = imagecreate($width, $height);

$background = imagecolorallocate($image, $r, $g, $b);
$text = imagecolorallocate($image, $r-128, $g-128, $b-128);

imagefill($image, 0, 0, $background);

for($i = 1; $i <= $length; $i++) {
    $counter = mt_rand(0, 1);
    if ($counter == 0) {
        $angle = mt_rand(0, 30);
    }
    if ($counter == 1) {
        $angle = mt_rand(330, 360);
    }
    imagettftext($image, mt_rand(14, 18), $angle, ($i * 18)-8, mt_rand(20, 25), $text, "font/arial.ttf", substr($code, ($i - 1), 1));
}

imageline($image, 0, mt_rand(5, $height-5), $width, mt_rand(5, $height-5), $text);

$gaussian = array(array(1.0, 2.0, 1.0), array(2.0, 4.0, 2.0), array(1.0, 2.0, 1.0));
imageconvolution($image, $gaussian, 16, 0);

imagerectangle($image, 0, 0, $width - 1, $height - 1, $text);

header('Expires: Tue, 08 Oct 1991 00:00:00 GMT');
header('Cache-Control: no-cache, must-revalidate');
header("Content-Type: image/gif");

imagegif($image);
imagedestroy($image); 
?>
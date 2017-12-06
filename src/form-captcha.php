<?
# this is a helper file for displaying the image captcha. Use as the source of an image tag.
# this script will need to be moved to a public accessible dir.
session_start();
require_once '/set/path/to/Welder.php';
$F = new Welder;
$F->createCaptcha();	
?>
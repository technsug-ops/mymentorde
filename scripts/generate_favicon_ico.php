<?php
// One-shot script: 32x32 mor "M" favicon.ico üretir.
// php scripts/generate_favicon_ico.php
$size = 32;
$img = imagecreatetruecolor($size, $size);
imageantialias($img, true);

$purple = imagecolorallocate($img, 126, 88, 191);
$white  = imagecolorallocate($img, 255, 255, 255);
imagefill($img, 0, 0, $purple);

// "M" — 4 vuruş
imagesetthickness($img, 4);
imageline($img, 7, 6, 7, 26, $white);
imageline($img, 7, 6, 16, 18, $white);
imageline($img, 16, 18, 25, 6, $white);
imageline($img, 25, 6, 25, 26, $white);

$pngTmp = tempnam(sys_get_temp_dir(), 'fv') . '.png';
imagepng($img, $pngTmp);
imagedestroy($img);

$pngData = file_get_contents($pngTmp);
$pngLen = strlen($pngData);
unlink($pngTmp);

// ICO container — PNG-embedded entry
$ico  = pack('vvv', 0, 1, 1);
$ico .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, $pngLen, 22);
$ico .= $pngData;

$out = __DIR__ . '/../public/favicon.ico';
file_put_contents($out, $ico);
echo "favicon.ico: " . strlen($ico) . " bytes (PNG: {$pngLen})\n";
echo "Saved to: " . realpath($out) . "\n";

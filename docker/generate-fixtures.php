<?php
// Paints 6 labelled JPEGs per fixture session so the gallery has something
// visually distinct to render. Idempotent: skips files that already exist.

$sessions = [
    '00000000-0000-4000-8000-000000000001' => 'Test Session No Password',
    '00000000-0000-4000-8000-000000000002' => 'Test Session With Password',
];

$palette = [
    [220,  80,  80],  // red
    [220, 140,  60],  // orange
    [200, 180,  60],  // yellow
    [ 80, 160,  80],  // green
    [ 80, 120, 200],  // blue
    [160,  80, 180],  // purple
];

$w = 800;
$h = 600;
$font = 5; // largest built-in font
$cw = imagefontwidth($font);
$ch = imagefontheight($font);

foreach ($sessions as $id => $name) {
    $dir = "/var/www/html/data/$id";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    for ($n = 1; $n <= 6; $n++) {
        $out = "$dir/photo$n.jpg";
        if (file_exists($out)) {
            continue;
        }

        [$r, $g, $b] = $palette[($n - 1) % count($palette)];
        $im = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($im, $r, $g, $b);
        imagefilledrectangle($im, 0, 0, $w, $h, $bg);

        $fg = imagecolorallocate($im, 255, 255, 255);
        $shadow = imagecolorallocate($im, 30, 30, 30);

        $lines = [$name, "Photo #$n", $id];
        $yStart = (int) (($h - count($lines) * $ch * 3) / 2);
        foreach ($lines as $i => $text) {
            $x = (int) (($w - $cw * strlen($text)) / 2);
            $y = $yStart + $i * $ch * 3;
            imagestring($im, $font, $x + 2, $y + 2, $text, $shadow);
            imagestring($im, $font, $x, $y, $text, $fg);
        }

        imagejpeg($im, $out, 85);
        imagedestroy($im);
    }
}

echo "fixtures ready\n";

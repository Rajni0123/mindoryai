<?php
/**
 * Mindory App Icon Generator
 * Run this script to generate new app icon: php public/generate-icon.php
 */

// Create a 512x512 image
$size = 512;
$image = imagecreatetruecolor($size, $size);

// Enable alpha blending
imagesavealpha($image, true);
imagealphablending($image, true);

// Create gradient background (orange to green)
for ($y = 0; $y < $size; $y++) {
    for ($x = 0; $x < $size; $x++) {
        // Orange: #cc785c (204, 120, 92)
        // Green: #0df259 (13, 242, 89)

        $ratio = ($x + $y) / ($size * 2);

        $r = 204 + (13 - 204) * $ratio;
        $g = 120 + (242 - 120) * $ratio;
        $b = 92 + (89 - 92) * $ratio;

        $color = imagecolorallocatealpha($image, $r, $g, $b, 0);
        imagesetpixel($image, $x, $y, $color);
    }
}

// White color for icon
$white = imagecolorallocatealpha($image, 255, 255, 255, 0);

// Scale factor for 512x512 from 24x24
$scale = $size / 24;

// Set line thickness
imagesetthickness($image, round(2 * $scale));

// Draw the bulb icon (exact path from chat.blade.php)
$points = [
    // Base line
    [9.663, 17], [14.336, 17],
    // Top ray
    [12, 3], [12, 4],
    // Top right ray
    [18.364, 4.636], [17.657, 5.343],
    // Right ray
    [21, 12], [20, 12],
    // Left ray
    [4, 12], [3, 12],
    // Bottom left ray
    [6.343, 6.343], [5.636, 5.636],
];

// Draw straight lines
for ($i = 0; $i < count($points); $i += 2) {
    $x1 = $points[$i][0] * $scale;
    $y1 = $points[$i][1] * $scale;
    $x2 = $points[$i + 1][0] * $scale;
    $y2 = $points[$i + 1][1] * $scale;
    imageline($image, $x1, $y1, $x2, $y2, $white);
}

// Draw the bulb arc (approximate with ellipse)
imagefilledarc($image, 12 * $scale, 12 * $scale, 10 * $scale, 10 * $scale,
    0, 360, $white, IMG_ARC_PIE);

// Draw base rectangle
imagefilledrectangle($image,
    10 * $scale,
    18 * $scale,
    14 * $scale,
    19 * $scale,
    $white
);

// Save the image
$outputPath = __DIR__ . '/../mobile-app/assets/icon.png';
imagepng($image, $outputPath);
imagedestroy($image);

echo "✅ New Mindory icon generated at: {$outputPath}\n";
echo "Size: {$size}x{$size}px\n";
echo "Format: PNG with transparency support\n";

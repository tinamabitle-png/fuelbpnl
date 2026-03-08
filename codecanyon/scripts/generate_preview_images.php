<?php

declare(strict_types=1);

$targetDir = __DIR__ . '/../Preview-Images';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0775, true);
}

$images = [
    ['file' => 'main-preview-590x300.png', 'w' => 590, 'h' => 300, 'title' => 'Bwiser Fuel Credit Platform', 'subtitle' => 'Laravel SaaS for driver and merchant fuel finance'],
    ['file' => 'thumbnail-80x80.png', 'w' => 80, 'h' => 80, 'title' => 'BW', 'subtitle' => ''],
    ['file' => 'screenshot-01-dashboard-1365x768.png', 'w' => 1365, 'h' => 768, 'title' => 'Admin Dashboard', 'subtitle' => 'Risk, repayments, vouchers, and compliance visibility'],
    ['file' => 'screenshot-02-driver-onboarding-1365x768.png', 'w' => 1365, 'h' => 768, 'title' => 'Driver Onboarding', 'subtitle' => 'Google geocode + KYC document upload flow'],
    ['file' => 'screenshot-03-merchant-onboarding-1365x768.png', 'w' => 1365, 'h' => 768, 'title' => 'Merchant Onboarding', 'subtitle' => 'Franchise selection + station registration'],
    ['file' => 'screenshot-04-credit-assessment-1365x768.png', 'w' => 1365, 'h' => 768, 'title' => 'AI Credit Assessment', 'subtitle' => 'Bank statement-based recommendation with capped limits'],
    ['file' => 'screenshot-05-voucher-redemption-1365x768.png', 'w' => 1365, 'h' => 768, 'title' => 'Voucher Redemption', 'subtitle' => 'Secure redemption and station settlement controls'],
    ['file' => 'screenshot-06-legal-compliance-1365x768.png', 'w' => 1365, 'h' => 768, 'title' => 'Compliance Suite', 'subtitle' => 'POPIA, PAIA, AML, PCI-DSS and policy pages'],
];

if (!function_exists('imagecreatetruecolor')) {
    fwrite(STDERR, "GD extension is required to generate PNG previews.\n");
    exit(1);
}

foreach ($images as $item) {
    $im = imagecreatetruecolor($item['w'], $item['h']);
    imagealphablending($im, true);
    imagesavealpha($im, false);

    $c1 = [20, 65, 169];
    $c2 = [14, 165, 233];

    for ($x = 0; $x < $item['w']; $x++) {
        $ratio = $x / max(1, $item['w'] - 1);
        $r = (int) round($c1[0] * (1 - $ratio) + $c2[0] * $ratio);
        $g = (int) round($c1[1] * (1 - $ratio) + $c2[1] * $ratio);
        $b = (int) round($c1[2] * (1 - $ratio) + $c2[2] * $ratio);
        $color = imagecolorallocate($im, $r, $g, $b);
        imageline($im, $x, 0, $x, $item['h'], $color);
    }

    $white = imagecolorallocate($im, 255, 255, 255);
    $softWhite = imagecolorallocatealpha($im, 255, 255, 255, 45);

    imagefilledrectangle($im, (int) ($item['w'] * 0.05), (int) ($item['h'] * 0.15), (int) ($item['w'] * 0.95), (int) ($item['h'] * 0.88), $softWhite);

    $title = $item['title'];
    $subtitle = $item['subtitle'];

    $font = 5;
    $titleX = (int) (($item['w'] - imagefontwidth($font) * strlen($title)) / 2);
    $titleY = (int) ($item['h'] * 0.33);
    imagestring($im, $font, max(10, $titleX), $titleY, $title, $white);

    if ($subtitle !== '') {
        $font2 = 3;
        $subX = (int) (($item['w'] - imagefontwidth($font2) * strlen($subtitle)) / 2);
        $subY = (int) ($item['h'] * 0.46);
        imagestring($im, $font2, max(10, $subX), $subY, $subtitle, $white);
    }

    imagestring($im, 2, 14, $item['h'] - 20, 'bwiser.co.za', $white);

    imagepng($im, $targetDir . '/' . $item['file']);
    imagedestroy($im);
}

echo "Preview images generated in {$targetDir}\n";

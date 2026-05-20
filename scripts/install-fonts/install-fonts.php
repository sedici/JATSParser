<?php
// filepath: install-fonts.php

/**
 * Script to install TTF fonts in TCPDF
 */

// Path to the folder with the fonts 
$fontSourceDir = __DIR__ . '/fonts';

// Destination folder for TCPDF fonts
$tcpdfFontDir = __DIR__ . '/../../vendor/tecnickcom/tcpdf/fonts';

echo "Initializing instalation of fonts from $fontSourceDir to $tcpdfFontDir\n\n";

// Verify if the destination folder exists
if (!is_dir($tcpdfFontDir)) {
    echo "TCPDF destiny folder doesn't exists: $tcpdfFontDir\n\n";
    exit(1);
}

// Verify if the source folder exists
if (!is_dir($fontSourceDir)) {
    echo "Fonts folder doens't exists: $fontSourceDir\n\n";
    exit(1);
}

// Include the necessary files from TCPDF library to install the fonts
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/include/tcpdf_fonts.php';

// Search for the fonts in the source folder
$fontsInstalled = 0;
$errors = 0;


$fontDirs = glob($fontSourceDir . '/*.ttf');
foreach ($fontDirs as $fontFile) {
    $fontName = basename($fontFile);

    echo "Installing font: $fontName...\n";
        
    try {
        $result = TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 32);
            
        if ($result) {
            echo "✓ Font installed with name: $result\n\n";
            $fontsInstalled++;
        } else {
            echo "✗ Error installing the font.\n\n";
            $errors++;
        }
    } catch (Exception $e) {
        echo "✗ Installation error: " . $e->getMessage() . "\n";
        $errors++;
    }
}


echo "\nInstallation summary: $fontsInstalled installed fonts, $errors errors\n\n";

if ($fontsInstalled > 0) {
    echo "Fonts installed correctly.\n\n";
} else {
    echo "No fonts were installed.\n\n";
}
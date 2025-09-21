<?php
/**
 * Ezoic ads.txt Redirect
 * Redirects requests to ads.txt to the Ezoic ads.txt manager
 * 
 * Usage: Rename this file to ads.txt and place in root directory
 * Or configure server to serve this file when ads.txt is requested
 */

// Set proper headers for ads.txt
header('Content-Type: text/plain');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

// Redirect to Ezoic ads.txt manager
header('Location: https://srv.adstxtmanager.com/19390/linkzinencurtador.online', true, 301);
exit;
?>

<?php
/**
 * includes/header.php
 * GCM IMS — Authenticated Page Header
 *
 * Outputs the HTML <head> and opens the app shell for all authenticated pages.
 * Must be included at the very top of every authenticated page.
 *
 * Expected variables (set before including this file):
 *   $page_title string  — the <title> suffix (e.g. "Dashboard", "Users")
 *
 * This file handles:
 *   - Loading all required config and includes
 *   - Enforcing authentication (redirects to login if not logged in)
 *   - Opening the HTML document and app shell
 *
 * Requires: config/app.php, config/database.php
 *           includes/auth.php, includes/functions.php, includes/permissions.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/permissions.php';

start_secure_session();
require_login();  // redirect to login if not authenticated

$_ims_page_title = isset($page_title) ? e($page_title) . ' — ' . APP_NAME : APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_ims_page_title ?></title>
    <meta name="robots" content="noindex, nofollow">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= app_base_url() ?>/assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_base_url() ?>/assets/images/favicon.svg">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Google Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0&display=swap" rel="stylesheet">

    <!-- GCM IMS Styles -->
    <link rel="stylesheet" href="<?= app_base_url() ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= app_base_url() ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= app_base_url() ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?= app_base_url() ?>/assets/css/responsive.css">
</head>
<body>
<div class="app-shell">

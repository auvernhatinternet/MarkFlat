<?php

require dirname(__DIR__) . '/vendor/autoload.php';

// Ensure test environment
$_ENV['APP_ENV'] = 'test';
if (!isset($_ENV['MF_CMS_POSTS_DIR'])) {
    $_ENV['MF_CMS_POSTS_DIR'] = '/tests/fixtures/posts';
}
if (!isset($_ENV['MF_CMS_POSTS_PER_PAGE'])) {
    $_ENV['MF_CMS_POSTS_PER_PAGE'] = '5';
}

// Create test directories if they don't exist
$testPostsDir = dirname(__DIR__) . '/tests/fixtures/posts';
if (!is_dir($testPostsDir)) {
    mkdir($testPostsDir, 0o777, true);
}

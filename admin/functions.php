<?php
session_start();

function is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function get_settings() {
    $file = '../data/settings.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return ['bot_token' => '', 'bot_username' => ''];
}

function save_settings($settings) {
    file_put_contents('../data/settings.json', json_encode($settings, JSON_PRETTY_PRINT), LOCK_EX);
}

function get_pages() {
    $file = '../data/pages.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true) ?: [];
    }
    return [];
}

function save_pages($pages) {
    file_put_contents('../data/pages.json', json_encode($pages, JSON_PRETTY_PRINT), LOCK_EX);
}

function get_comments($page_id) {
    $file = '../data/comments/' . $page_id . '.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true) ?: [];
    }
    return [];
}

function save_comments($page_id, $comments) {
    file_put_contents('../data/comments/' . $page_id . '.json', json_encode($comments, JSON_PRETTY_PRINT), LOCK_EX);
}

function count_comments_total($page_id) {
    $comments = get_comments($page_id);
    $count = count($comments);
    foreach ($comments as $comment) {
        if (isset($comment['replies'])) {
            $count += count($comment['replies']);
        }
    }
    return $count;
}

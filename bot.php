<?php
$settings_file = 'data/settings.json';
if (!file_exists($settings_file)) {
    exit;
}
$settings = json_decode(file_get_contents($settings_file), true);
$bot_token = $settings['bot_token'] ?? '';

if (empty($bot_token)) {
    exit;
}

$update = json_decode(file_get_contents('php://input'), true);

if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text = trim($update['message']['text'] ?? '');

    if ($text === '/start') {
        sendMessage($chat_id, "Hello! I am the Comment Box Bot.\n\nUse /pages to see available comment pages.");
    } elseif ($text === '/pages') {
        $pages = json_decode(file_get_contents('data/pages.json'), true) ?: [];
        if (empty($pages)) {
            sendMessage($chat_id, "There are no comment pages available right now.");
        } else {
            $msg = "Here are the available comment pages:\n\n";
            foreach ($pages as $p) {
                $msg .= "• " . $p['title'] . "\n/page_" . $p['id'] . "\n\n";
            }
            sendMessage($chat_id, $msg);
        }
    } elseif (strpos($text, '/page_') === 0) {
        $page_id = str_replace('/page_', '', $text);
        $pages = json_decode(file_get_contents('data/pages.json'), true) ?: [];
        $found = false;
        foreach ($pages as $p) {
            if ($p['id'] === $page_id) {
                // Determine base URL dynamically or fallback
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $base_url = "http://" . $host;
                $link = $base_url . "/index.php?id=" . $page_id;

                $msg = "Here is the link to **" . $p['title'] . "**:\n\n" . $link;
                sendMessage($chat_id, $msg, "Markdown");
                $found = true;
                break;
            }
        }
        if (!$found) {
            sendMessage($chat_id, "Page not found.");
        }
    } else {
        sendMessage($chat_id, "I don't understand that command. Use /pages to see available comment pages.");
    }
}

function sendMessage($chat_id, $text, $parse_mode = null) {
    global $bot_token;
    $url = "https://api.telegram.org/bot$bot_token/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text
    ];
    if ($parse_mode) {
        $data['parse_mode'] = $parse_mode;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

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
    } elseif (strpos($text, '/start login_') === 0) {
        $page_id = str_replace('/start login_', '', $text);

        $user = $update['message']['from'];
        $auth_data = [
            'id' => $user['id'],
            'auth_date' => time(),
        ];
        if (isset($user['first_name'])) $auth_data['first_name'] = $user['first_name'];
        if (isset($user['last_name'])) $auth_data['last_name'] = $user['last_name'];
        if (isset($user['username'])) $auth_data['username'] = $user['username'];

        $data_check_arr = [];
        foreach ($auth_data as $key => $value) {
            $data_check_arr[] = $key . '=' . $value;
        }
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);

        $secret_key = hash('sha256', $bot_token, true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);

        $auth_data['id_tg'] = $auth_data['id'];
        unset($auth_data['id']);
        $auth_data['hash'] = $hash;
        $auth_data['page_id'] = $page_id;
        $auth_data['id'] = $page_id;

        $query = http_build_query($auth_data);

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

        // Since Telegram webhooks require HTTPS, it's highly likely the bot is accessed via HTTPS.
        // If the script is executed via CLI or proxy, HTTP_HOST and HTTPS might not be fully reliable,
        // but this provides a strong default.
        $base_url = $protocol . $host;

        $login_link = $base_url . "/index.php?" . $query;

        $msg = "Click the link below to seamlessly log in to the discussion:\n\n" . $login_link;
        sendMessage($chat_id, $msg);
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

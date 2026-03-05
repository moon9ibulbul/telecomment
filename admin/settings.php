<?php
require_once 'functions.php';
require_login();

$message = '';
$settings = get_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['bot_token'] = $_POST['bot_token'] ?? '';
    $settings['bot_username'] = $_POST['bot_username'] ?? '';
    save_settings($settings);
    $message = "Settings updated successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <?php include 'sidebar.php'; ?>

    <div class="main-content flex-1 bg-gray-100 ml-64 p-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Telegram Bot Settings</h2>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6 max-w-lg">
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="bot_token">
                        Telegram Bot API Token
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="bot_token" type="text" name="bot_token" value="<?php echo htmlspecialchars($settings['bot_token'] ?? ''); ?>" required>
                    <p class="text-sm text-gray-500 mt-2">Get this from <a href="https://t.me/BotFather" target="_blank" class="text-blue-500">@BotFather</a></p>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="bot_username">
                        Telegram Bot Username
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="bot_username" type="text" name="bot_username" value="<?php echo htmlspecialchars($settings['bot_username'] ?? ''); ?>" required>
                    <p class="text-sm text-gray-500 mt-1">Without the @ symbol</p>
                </div>
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

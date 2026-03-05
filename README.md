# Telegram Comment Box Widget

A lightweight, PHP-based flat-file commenting system that authenticates users via Telegram. It features a backend admin panel to generate comment pages and a Telegram bot webhook to distribute the links.

## Project Structure

- `/admin/`: The backend admin panel.
- `/public/`: The public-facing comment pages and API.
- `/data/`: JSON files storing admin credentials, settings, pages, and comments.

## Default Admin Credentials

- **Username:** `admin`
- **Password:** `admin`

## How to Change Admin Credentials

The admin credentials are saved in `data/admin.json` as a JSON object containing the username and a hashed password (using `password_hash()` in PHP).

To change the admin password, you must generate a new password hash. You can do this quickly from your command line:

```bash
# Example: Change password to "my_new_password"
php -r 'echo password_hash("my_new_password", PASSWORD_DEFAULT);'
```

Copy the output hash and replace the password string in `data/admin.json`:

```json
{
    "username": "admin",
    "password": "$2y$10$YourGeneratedHashHere..."
}
```

You can change the `"username"` field to any string you prefer directly in the JSON file.

## Setup & Usage Instructions

1. **Start the servers**
   Since the app is split into an admin panel and a public site, you can host them under different subdomains or directories. For local testing, you can run:

   ```bash
   # Run the Admin Panel on port 8000
   php -S localhost:8000 -t admin

   # Run the Public Site on port 8001
   php -S localhost:8001 -t public
   ```

2. **Configure the Telegram Bot**
   - Go to [@BotFather](https://t.me/BotFather) on Telegram and create a new bot to get your API Token.
   - You MUST link your domain to the bot for the Telegram Login Widget to work. Use `/setdomain` in BotFather and enter the domain where your `/public/` site is hosted (e.g., `localhost` or `yourdomain.com`).
   - Log into the Admin Panel (`http://localhost:8000/login.php`) using the default credentials.
   - Go to **Settings** and enter your Bot API Token and Bot Username (without the `@`).

3. **Manage Comment Pages**
   - In the Admin Panel, go to the **Dashboard** to create a new comment page.
   - You can copy the link and share it directly or let the Telegram Bot handle it.

4. **Telegram Bot Webhook (Optional)**
   - To allow users to message your bot directly to get comment page links, you must set a webhook pointing to the `bot.php` file on your public site.
   - Open your browser and go to:
     `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=https://yourdomain.com/bot.php`
   - Users can now send `/start` or `/pages` to your bot to see the list of active comment pages.

## Features Included

- Admin panel (Create, List, Search, Delete pages).
- Telegram Login verification.
- Flat-file database using JSON with `LOCK_EX` concurrency safety.
- Nested replies up to multiple levels.
- Upvote / Downvote system.
- Edit / Delete capabilities restricted strictly to the comment creator.
- Tailwind CSS modern minimalist design.
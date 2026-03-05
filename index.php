<?php
session_start();

function get_settings() {
    $file = 'data/settings.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true) ?: ['bot_token' => '', 'bot_username' => ''];
    }
    return ['bot_token' => '', 'bot_username' => ''];
}

function verify_telegram_login($auth_data) {
    $settings = get_settings();
    $bot_token = $settings['bot_token'];

    if (empty($bot_token)) {
        return false;
    }

    $check_hash = $auth_data['hash'];
    unset($auth_data['hash']);

    $data_check_arr = [];
    foreach ($auth_data as $key => $value) {
        $data_check_arr[] = $key . '=' . $value;
    }
    sort($data_check_arr);
    $data_check_string = implode("\n", $data_check_arr);

    $secret_key = hash('sha256', $bot_token, true);
    $hash = hash_hmac('sha256', $data_check_string, $secret_key);

    if (strcmp($hash, $check_hash) !== 0) {
        return false;
    }

    if ((time() - $auth_data['auth_date']) > 86400) {
        return false; // Auth data is older than 24 hours
    }

    return true;
}

// Handle login via GET parameters passed by Telegram Widget
if (isset($_GET['id_tg']) && isset($_GET['hash']) && isset($_GET['auth_date'])) {

    $auth_data = [
        'id' => $_GET['id_tg'], // 'id' from Telegram widget, mapped from id_tg
        'auth_date' => $_GET['auth_date'],
        'hash' => $_GET['hash']
    ];

    if (isset($_GET['first_name'])) $auth_data['first_name'] = $_GET['first_name'];
    if (isset($_GET['last_name'])) $auth_data['last_name'] = $_GET['last_name'];
    if (isset($_GET['username'])) $auth_data['username'] = $_GET['username'];
    if (isset($_GET['photo_url'])) $auth_data['photo_url'] = $_GET['photo_url'];

    if (verify_telegram_login($auth_data)) {
        $_SESSION['tg_user'] = $auth_data;
        // Redirect back to page to clear URL parameters
        $redirect_id = $_GET['page_id'] ?? '';
        header('Location: /index.php?id=' . $redirect_id);
        exit;
    } else {
        $login_error = "Telegram authentication failed. Please try again.";
    }
}

// Check if user wants to logout
if (isset($_GET['logout'])) {
    unset($_SESSION['tg_user']);
    header('Location: /index.php?id=' . ($_GET['id'] ?? ''));
    exit;
}

$page_id = $_GET['id'] ?? '';
if (!$page_id) {
    die("Invalid page ID.");
}

// Fetch Page Details
$pages = json_decode(file_get_contents('data/pages.json'), true) ?: [];
$current_page = null;
foreach ($pages as $p) {
    if ($p['id'] === $page_id) {
        $current_page = $p;
        break;
    }
}

if (!$current_page) {
    die("Page not found.");
}

$settings = get_settings();
$bot_username = $settings['bot_username'];
$tg_user = $_SESSION['tg_user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($current_page['title']); ?> - Comments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        function onTelegramAuth(user) {
            // Send user data to verify and start session
            const currentUrl = new URL(window.location.href);
            const pageId = currentUrl.searchParams.get('id');

            const params = new URLSearchParams();
            if (pageId) params.append('page_id', pageId);
            params.append('id_tg', user.id);
            if (user.first_name) params.append('first_name', user.first_name);
            if (user.last_name) params.append('last_name', user.last_name);
            if (user.username) params.append('username', user.username);
            if (user.photo_url) params.append('photo_url', user.photo_url);
            params.append('auth_date', user.auth_date);
            params.append('hash', user.hash);

            let url = `/index.php?${params.toString()}`;

            window.location.href = url;
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold mb-8 pb-4 border-b border-gray-200">
            <?php echo htmlspecialchars($current_page['title']); ?> Discussion
        </h1>

        <?php if (isset($login_error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
                <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>

        <?php if ($tg_user): ?>
            <!-- Logged in state -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8 flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <?php if (!empty($tg_user['photo_url'])): ?>
                        <img src="<?php echo htmlspecialchars($tg_user['photo_url']); ?>" alt="Profile" class="w-12 h-12 rounded-full border border-gray-300">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-blue-500 text-white flex items-center justify-center text-xl font-bold">
                            <?php echo strtoupper(substr($tg_user['first_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="font-semibold text-lg"><?php echo htmlspecialchars($tg_user['first_name'] . ' ' . $tg_user['last_name']); ?></p>
                        <p class="text-sm text-gray-500">@<?php echo htmlspecialchars($tg_user['username']); ?></p>
                    </div>
                </div>
                <a href="?id=<?php echo $page_id; ?>&logout=1" class="text-red-500 hover:text-red-700 font-medium px-4 py-2 border border-red-200 rounded-lg hover:bg-red-50 transition duration-150">Logout</a>
            </div>

            <!-- Comment Form Placeholder -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                 <h3 class="text-lg font-semibold mb-4 text-gray-700">Leave a comment</h3>
                 <textarea id="main-comment-input" class="w-full border border-gray-300 rounded-lg p-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none transition duration-150" rows="3" placeholder="What are your thoughts?"></textarea>
                 <div class="mt-3 flex justify-end">
                     <button id="submit-main-comment" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition duration-150 flex items-center">
                         <i class="fa-solid fa-paper-plane mr-2"></i> Post Comment
                     </button>
                 </div>
            </div>

        <?php else: ?>
            <!-- Logged out state -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 mb-8 text-center">
                <div class="mb-6 flex justify-center">
                    <div class="w-16 h-16 bg-blue-100 text-blue-500 rounded-full flex items-center justify-center text-3xl">
                        <i class="fa-brands fa-telegram"></i>
                    </div>
                </div>
                <h3 class="text-xl font-medium mb-2 text-gray-800">Join the discussion</h3>
                <p class="text-gray-500 mb-8 max-w-md mx-auto">Login with your Telegram account to leave a comment, reply to others, and vote on comments.</p>

                <?php if ($bot_username): ?>
                    <script async src="https://telegram.org/js/telegram-widget.js?22"
                            data-telegram-login="<?php echo htmlspecialchars($bot_username); ?>"
                            data-size="large"
                            data-onauth="onTelegramAuth(user)"
                            data-request-access="write"></script>

                    <div class="mt-4 flex items-center justify-center space-x-2">
                        <span class="text-sm text-gray-400">or</span>
                    </div>

                    <a href="https://t.me/<?php echo htmlspecialchars($bot_username); ?>?start=login_<?php echo htmlspecialchars($page_id); ?>"
                       target="_blank"
                       class="mt-4 inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 transition duration-150">
                        <i class="fa-brands fa-telegram mr-2"></i> Seamless Login via Bot
                    </a>
                <?php else: ?>
                    <p class="text-red-500 font-medium p-4 bg-red-50 rounded-lg border border-red-200 inline-block">Telegram Bot not configured by Admin.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Comments List -->
        <div class="mt-10">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center">
                <i class="fa-regular fa-comments mr-3 text-gray-500"></i> Comments
            </h2>
            <div id="comments-container" class="space-y-6">
                <!-- Comments will be loaded here via JS -->
                <div class="text-center py-12 bg-white rounded-lg border border-gray-200 border-dashed">
                    <i class="fa-solid fa-spinner fa-spin text-3xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 font-medium">Loading comments...</p>
                </div>
            </div>
        </div>
    </div>
    <script>
        const PAGE_ID = '<?php echo htmlspecialchars($page_id); ?>';
        const CURRENT_USER_ID = '<?php echo $tg_user ? $tg_user['id'] : ''; ?>';

        function loadComments() {
            fetch(`/api.php?action=get&page_id=${PAGE_ID}`)
                .then(response => response.json())
                .then(data => renderComments(data))
                .catch(error => console.error('Error fetching comments:', error));
        }

        function renderComments(comments) {
            const container = document.getElementById('comments-container');
            container.innerHTML = '';

            if (comments.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12 bg-white rounded-lg border border-gray-200 border-dashed">
                        <i class="fa-regular fa-comment-dots text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 font-medium">No comments yet. Be the first to share your thoughts!</p>
                    </div>`;
                return;
            }

            // Sort comments chronologically
            comments.sort((a, b) => b.id - a.id);

            comments.forEach(comment => {
                container.appendChild(createCommentElement(comment));
            });
        }

        function createCommentElement(comment, isReply = false) {
            const div = document.createElement('div');
            div.className = isReply
                ? 'flex bg-gray-50 p-4 rounded-lg border border-gray-100 mt-3 ml-8'
                : 'flex bg-white p-5 rounded-lg border border-gray-200 shadow-sm transition-shadow hover:shadow-md mb-4';
            div.id = `comment-${comment.id}`;

            const upvotes = comment.upvotes || [];
            const downvotes = comment.downvotes || [];
            const voteScore = upvotes.length - downvotes.length;

            const hasUpvoted = CURRENT_USER_ID && upvotes.includes(CURRENT_USER_ID);
            const hasDownvoted = CURRENT_USER_ID && downvotes.includes(CURRENT_USER_ID);

            const isCreator = CURRENT_USER_ID && CURRENT_USER_ID == comment.user_id;

            const photoHtml = comment.user_photo
                ? `<img src="${comment.user_photo}" alt="${comment.user_name}" class="w-10 h-10 rounded-full border border-gray-200 object-cover">`
                : `<div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold border border-blue-200 text-lg">${comment.user_name.charAt(0).toUpperCase()}</div>`;

            let repliesHtml = '';
            if (comment.replies && comment.replies.length > 0) {
                const repliesContainer = document.createElement('div');
                repliesContainer.className = 'w-full mt-3 border-l-2 border-gray-200 pl-4 space-y-3';
                comment.replies.forEach(reply => {
                    repliesContainer.appendChild(createCommentElement(reply, true));
                });
                repliesHtml = repliesContainer.outerHTML;
            }

            const actionsHtml = CURRENT_USER_ID ? `
                <div class="flex items-center space-x-4 mt-3 pt-3 border-t border-gray-100">
                    <button onclick="toggleReplyForm('${comment.id}')" class="text-sm font-medium text-gray-500 hover:text-blue-600 flex items-center transition-colors">
                        <i class="fa-solid fa-reply mr-1.5 text-xs"></i> Reply
                    </button>
                    ${isCreator ? `
                    <button onclick="editComment('${comment.id}')" class="text-sm font-medium text-gray-500 hover:text-green-600 flex items-center transition-colors">
                        <i class="fa-solid fa-pen mr-1.5 text-xs"></i> Edit
                    </button>
                    <button onclick="deleteComment('${comment.id}')" class="text-sm font-medium text-gray-500 hover:text-red-600 flex items-center transition-colors">
                        <i class="fa-solid fa-trash-can mr-1.5 text-xs"></i> Delete
                    </button>
                    ` : ''}
                </div>

                <div id="reply-form-${comment.id}" class="hidden mt-4 bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <textarea id="reply-input-${comment.id}" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none transition-shadow" rows="2" placeholder="Write a reply..."></textarea>
                    <div class="mt-2 flex justify-end space-x-2">
                        <button onclick="toggleReplyForm('${comment.id}')" class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-200 rounded-md transition-colors font-medium">Cancel</button>
                        <button onclick="submitReply('${comment.id}')" class="px-4 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm transition-colors flex items-center">
                            <i class="fa-solid fa-paper-plane mr-1.5"></i> Reply
                        </button>
                    </div>
                </div>

                <div id="edit-form-${comment.id}" class="hidden mt-3 bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <textarea id="edit-input-${comment.id}" class="w-full border border-gray-300 rounded-md p-3 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none resize-none transition-shadow" rows="3">${comment.text}</textarea>
                    <div class="mt-2 flex justify-end space-x-2">
                        <button onclick="cancelEdit('${comment.id}')" class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-200 rounded-md transition-colors font-medium">Cancel</button>
                        <button onclick="submitEdit('${comment.id}')" class="px-4 py-1.5 text-sm bg-green-600 hover:bg-green-700 text-white font-medium rounded-md shadow-sm transition-colors flex items-center">
                            <i class="fa-solid fa-check mr-1.5"></i> Save Changes
                        </button>
                    </div>
                </div>
            ` : '';

            const timeAgo = new Date(comment.timestamp * 1000).toLocaleString(undefined, {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });

            div.innerHTML = `
                <!-- Voting Sidebar -->
                <div class="flex flex-col items-center mr-4 mt-1 space-y-1">
                    <button onclick="vote('${comment.id}', 'up')" class="w-8 h-8 rounded-full flex items-center justify-center transition-colors ${hasUpvoted ? 'text-blue-600 bg-blue-50' : 'text-gray-400 hover:bg-gray-100 hover:text-blue-500'}">
                        <i class="fa-solid fa-caret-up text-2xl leading-none"></i>
                    </button>
                    <span class="text-sm font-bold ${voteScore > 0 ? 'text-blue-600' : (voteScore < 0 ? 'text-red-600' : 'text-gray-600')}">${voteScore}</span>
                    <button onclick="vote('${comment.id}', 'down')" class="w-8 h-8 rounded-full flex items-center justify-center transition-colors ${hasDownvoted ? 'text-red-600 bg-red-50' : 'text-gray-400 hover:bg-gray-100 hover:text-red-500'}">
                        <i class="fa-solid fa-caret-down text-2xl leading-none"></i>
                    </button>
                </div>

                <!-- Comment Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-3">
                            ${photoHtml}
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">${comment.user_name}</h4>
                                <div class="flex items-center space-x-2">
                                    <p class="text-xs text-gray-500">${timeAgo}</p>
                                    ${comment.is_edited ? '<span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider bg-gray-100 px-1.5 py-0.5 rounded">Edited</span>' : ''}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="comment-text-${comment.id}" class="mt-3 text-gray-800 text-sm whitespace-pre-wrap leading-relaxed break-words">${escapeHtml(comment.text)}</div>

                    ${actionsHtml}
                    ${repliesHtml}
                </div>
            `;
            return div;
        }

        // Helper functions
        function escapeHtml(unsafe) {
            return unsafe
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }

        function toggleReplyForm(commentId) {
            const form = document.getElementById(`reply-form-${commentId}`);
            form.classList.toggle('hidden');
            if(!form.classList.contains('hidden')) {
                document.getElementById(`reply-input-${commentId}`).focus();
            }
        }

        function editComment(commentId) {
            document.getElementById(`comment-text-${commentId}`).classList.add('hidden');
            document.getElementById(`edit-form-${commentId}`).classList.remove('hidden');
        }

        function cancelEdit(commentId) {
            document.getElementById(`comment-text-${commentId}`).classList.remove('hidden');
            document.getElementById(`edit-form-${commentId}`).classList.add('hidden');
        }

        // API Calls
        document.addEventListener('DOMContentLoaded', () => {
            loadComments();

            const submitMain = document.getElementById('submit-main-comment');
            if (submitMain) {
                submitMain.addEventListener('click', () => {
                    const text = document.getElementById('main-comment-input').value.trim();
                    if (!text) return;

                    submitMain.disabled = true;
                    submitMain.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Posting...';

                    fetch('/api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'add', page_id: PAGE_ID, text: text })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            document.getElementById('main-comment-input').value = '';
                            loadComments();
                        } else {
                            alert(res.error || 'Failed to post comment');
                        }
                    })
                    .finally(() => {
                        submitMain.disabled = false;
                        submitMain.innerHTML = '<i class="fa-solid fa-paper-plane mr-2"></i> Post Comment';
                    });
                });
            }
        });

        window.submitReply = function(parentId) {
            const btn = event.currentTarget;
            const originalText = btn.innerHTML;
            const text = document.getElementById(`reply-input-${parentId}`).value.trim();
            if (!text) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Replying...';

            fetch('/api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add_reply', page_id: PAGE_ID, parent_id: parentId, text: text })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) loadComments();
                else {
                    alert(res.error || 'Failed to post reply');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            });
        };

        window.submitEdit = function(commentId) {
            const btn = event.currentTarget;
            const originalText = btn.innerHTML;
            const text = document.getElementById(`edit-input-${commentId}`).value.trim();
            if (!text) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Saving...';

            fetch('/api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'edit', page_id: PAGE_ID, comment_id: commentId, text: text })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) loadComments();
                else {
                    alert(res.error || 'Failed to edit comment');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            });
        };

        window.deleteComment = function(commentId) {
            if (!confirm('Are you sure you want to delete this comment? This action cannot be undone.')) return;

            fetch('/api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', page_id: PAGE_ID, comment_id: commentId })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) loadComments();
                else alert(res.error || 'Failed to delete comment');
            });
        };

        window.vote = function(commentId, type) {
            if (!CURRENT_USER_ID) {
                alert('Please login to vote.');
                return;
            }
            fetch('/api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'vote', page_id: PAGE_ID, comment_id: commentId, type: type })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) loadComments();
            });
        };
    </script>
</body>
</html>

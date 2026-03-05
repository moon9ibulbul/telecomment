<?php
require_once 'functions.php';
require_login();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $title = trim($_POST['title']);
        if ($title) {
            $pages = get_pages();
            $page_id = uniqid('page_');
            $new_page = [
                'id' => $page_id,
                'title' => htmlspecialchars($title),
                'created_at' => time()
            ];
            array_unshift($pages, $new_page);
            save_pages($pages);

            // Initialize empty comments array for this page
            save_comments($page_id, []);

            $message = "Page created successfully!";
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['page_id'])) {
        $page_id = $_POST['page_id'];
        $pages = get_pages();
        $pages = array_filter($pages, function($p) use ($page_id) {
            return $p['id'] !== $page_id;
        });
        save_pages(array_values($pages));

        $comment_file = '../data/comments/' . $page_id . '.json';
        if (file_exists($comment_file)) {
            unlink($comment_file);
        }
        $message = "Page deleted successfully!";
    }
}

// Pagination & Search
$pages = get_pages();
$search = $_GET['search'] ?? '';
if ($search) {
    $pages = array_filter($pages, function($p) use ($search) {
        return stripos($p['title'], $search) !== false;
    });
}

$per_page = 10;
$total_pages = ceil(count($pages) / $per_page);
$current_page = max(1, min($total_pages, intval($_GET['page'] ?? 1)));
$offset = ($current_page - 1) * $per_page;
$pages_to_show = array_slice($pages, $offset, $per_page);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this page?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
        function copyLink(id) {
            const url = window.location.origin + '/index.php?id=' + id;
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copied to clipboard!');
            });
        }
    </script>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal flex h-screen">
    <?php include 'sidebar.php'; ?>

    <div class="main-content flex-1 bg-gray-100 ml-0 md:ml-64 p-4 md:p-8 overflow-y-auto h-full w-full">
        <div class="flex items-center mb-6">
            <button id="open-sidebar" class="md:hidden mr-4 text-gray-800 hover:text-gray-600 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Comment Pages</h2>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Create New Page -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-xl font-semibold mb-4 text-gray-700">Create New Comment Page</h3>
            <form method="POST" class="flex items-center space-x-4">
                <input type="hidden" name="action" value="create">
                <input type="text" name="title" placeholder="Page Title (e.g., Article 1 Discussion)" required
                       class="flex-1 shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline">
                    Create
                </button>
            </form>
        </div>

        <!-- Pages List -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-700">Manage Pages</h3>
                <form method="GET" class="flex space-x-2">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search pages..."
                           class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Search
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="py-3 px-6 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Comments</th>
                            <th class="py-3 px-6 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($pages_to_show)): ?>
                            <tr><td colspan="4" class="py-4 px-6 text-center text-gray-500">No pages found.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($pages_to_show as $p): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 px-6 text-sm text-gray-900"><?php echo htmlspecialchars($p['title']); ?></td>
                                <td class="py-4 px-6 text-sm text-gray-500"><?php echo date('Y-m-d H:i', $p['created_at']); ?></td>
                                <td class="py-4 px-6 text-sm text-center text-gray-900">
                                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                        <?php echo count_comments_total($p['id']); ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-sm text-right font-medium">
                                    <button onclick="copyLink('<?php echo $p['id']; ?>')" class="text-indigo-600 hover:text-indigo-900 mr-3">Copy Link</button>
                                    <a href="/index.php?id=<?php echo $p['id']; ?>" target="_blank" class="text-green-600 hover:text-green-900 mr-3">View</a>
                                    <button onclick="confirmDelete('<?php echo $p['id']; ?>')" class="text-red-600 hover:text-red-900">Delete</button>

                                    <form id="delete-form-<?php echo $p['id']; ?>" method="POST" style="display:none;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="page_id" value="<?php echo $p['id']; ?>">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-4 flex justify-between items-center">
                    <div class="text-sm text-gray-700">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, count($pages)); ?> of <?php echo count($pages); ?> results
                    </div>
                    <div class="flex space-x-1">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                               class="px-3 py-1 border rounded <?php echo $i === $current_page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

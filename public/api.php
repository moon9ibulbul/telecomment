<?php
session_start();
header('Content-Type: application/json');

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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    $page_id = $_GET['page_id'] ?? '';

    if ($action === 'get' && $page_id) {
        $comments = get_comments($page_id);
        echo json_encode($comments);
        exit;
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $page_id = $data['page_id'] ?? '';

    if (!$page_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid page ID']);
        exit;
    }

    $tg_user = $_SESSION['tg_user'] ?? null;

    if ($action === 'add') {
        if (!$tg_user) { echo json_encode(['success' => false, 'error' => 'Not authenticated']); exit; }

        $text = trim($data['text'] ?? '');
        if (!$text) { echo json_encode(['success' => false, 'error' => 'Empty text']); exit; }

        $comments = get_comments($page_id);
        $new_comment = [
            'id' => time() . rand(100, 999),
            'user_id' => $tg_user['id'],
            'user_name' => trim($tg_user['first_name'] . ' ' . $tg_user['last_name']),
            'user_photo' => $tg_user['photo_url'] ?? '',
            'text' => $text,
            'timestamp' => time(),
            'upvotes' => [],
            'downvotes' => [],
            'replies' => [],
            'is_edited' => false
        ];
        array_unshift($comments, $new_comment);
        save_comments($page_id, $comments);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'add_reply') {
        if (!$tg_user) { echo json_encode(['success' => false, 'error' => 'Not authenticated']); exit; }

        $text = trim($data['text'] ?? '');
        $parent_id = $data['parent_id'] ?? '';
        if (!$text || !$parent_id) { echo json_encode(['success' => false, 'error' => 'Invalid data']); exit; }

        $comments = get_comments($page_id);
        $new_reply = [
            'id' => time() . rand(100, 999),
            'user_id' => $tg_user['id'],
            'user_name' => trim($tg_user['first_name'] . ' ' . $tg_user['last_name']),
            'user_photo' => $tg_user['photo_url'] ?? '',
            'text' => $text,
            'timestamp' => time(),
            'upvotes' => [],
            'downvotes' => [],
            'is_edited' => false
        ];

        $found = false;
        foreach ($comments as &$comment) {
            if ($comment['id'] == $parent_id) {
                $comment['replies'][] = $new_reply;
                $found = true;
                break;
            }
        }

        if ($found) {
            save_comments($page_id, $comments);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Parent comment not found']);
        }
        exit;
    }

    if ($action === 'edit') {
        if (!$tg_user) { echo json_encode(['success' => false, 'error' => 'Not authenticated']); exit; }

        $text = trim($data['text'] ?? '');
        $comment_id = $data['comment_id'] ?? '';
        if (!$text || !$comment_id) { echo json_encode(['success' => false, 'error' => 'Invalid data']); exit; }

        $comments = get_comments($page_id);
        $found = false;

        foreach ($comments as &$comment) {
            if ($comment['id'] == $comment_id) {
                if ($comment['user_id'] != $tg_user['id']) { echo json_encode(['success' => false, 'error' => 'Not authorized']); exit; }
                $comment['text'] = $text;
                $comment['is_edited'] = true;
                $found = true;
                break;
            }
            foreach ($comment['replies'] as &$reply) {
                if ($reply['id'] == $comment_id) {
                    if ($reply['user_id'] != $tg_user['id']) { echo json_encode(['success' => false, 'error' => 'Not authorized']); exit; }
                    $reply['text'] = $text;
                    $reply['is_edited'] = true;
                    $found = true;
                    break 2;
                }
            }
        }

        if ($found) {
            save_comments($page_id, $comments);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Comment not found']);
        }
        exit;
    }

    if ($action === 'delete') {
        if (!$tg_user) { echo json_encode(['success' => false, 'error' => 'Not authenticated']); exit; }

        $comment_id = $data['comment_id'] ?? '';
        if (!$comment_id) { echo json_encode(['success' => false, 'error' => 'Invalid data']); exit; }

        $comments = get_comments($page_id);
        $found = false;

        foreach ($comments as $key => $comment) {
            if ($comment['id'] == $comment_id) {
                if ($comment['user_id'] != $tg_user['id']) { echo json_encode(['success' => false, 'error' => 'Not authorized']); exit; }
                array_splice($comments, $key, 1);
                $found = true;
                break;
            }
            foreach ($comment['replies'] as $rKey => $reply) {
                if ($reply['id'] == $comment_id) {
                    if ($reply['user_id'] != $tg_user['id']) { echo json_encode(['success' => false, 'error' => 'Not authorized']); exit; }
                    array_splice($comments[$key]['replies'], $rKey, 1);
                    $found = true;
                    break 2;
                }
            }
        }

        if ($found) {
            save_comments($page_id, $comments);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Comment not found']);
        }
        exit;
    }

    if ($action === 'vote') {
        if (!$tg_user) { echo json_encode(['success' => false, 'error' => 'Not authenticated']); exit; }

        $comment_id = $data['comment_id'] ?? '';
        $type = $data['type'] ?? ''; // 'up' or 'down'
        if (!$comment_id || !in_array($type, ['up', 'down'])) { echo json_encode(['success' => false, 'error' => 'Invalid data']); exit; }

        $user_id = $tg_user['id'];
        $comments = get_comments($page_id);
        $found = false;

        $found = false;

        foreach ($comments as &$comment) {
            if ($comment['id'] == $comment_id) {
                if ($type === 'up') {
                    if (in_array($user_id, $comment['upvotes'])) {
                        $comment['upvotes'] = array_values(array_diff($comment['upvotes'], [$user_id]));
                    } else {
                        $comment['upvotes'][] = $user_id;
                        $comment['downvotes'] = array_values(array_diff($comment['downvotes'], [$user_id]));
                    }
                } elseif ($type === 'down') {
                    if (in_array($user_id, $comment['downvotes'])) {
                        $comment['downvotes'] = array_values(array_diff($comment['downvotes'], [$user_id]));
                    } else {
                        $comment['downvotes'][] = $user_id;
                        $comment['upvotes'] = array_values(array_diff($comment['upvotes'], [$user_id]));
                    }
                }
                $found = true;
                break;
            }
            if (isset($comment['replies'])) {
                foreach ($comment['replies'] as &$reply) {
                    if ($reply['id'] == $comment_id) {
                        if ($type === 'up') {
                            if (in_array($user_id, $reply['upvotes'])) {
                                $reply['upvotes'] = array_values(array_diff($reply['upvotes'], [$user_id]));
                            } else {
                                $reply['upvotes'][] = $user_id;
                                $reply['downvotes'] = array_values(array_diff($reply['downvotes'], [$user_id]));
                            }
                        } elseif ($type === 'down') {
                            if (in_array($user_id, $reply['downvotes'])) {
                                $reply['downvotes'] = array_values(array_diff($reply['downvotes'], [$user_id]));
                            } else {
                                $reply['downvotes'][] = $user_id;
                                $reply['upvotes'] = array_values(array_diff($reply['upvotes'], [$user_id]));
                            }
                        }
                        $found = true;
                        break 2;
                    }
                }
            }
        }

        if ($found) {
            save_comments($page_id, $comments);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Comment not found']);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);

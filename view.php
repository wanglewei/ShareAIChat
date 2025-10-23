<?php
ini_set('display_errors', 1); // 开发阶段开启错误显示，生产环境请关闭
error_reporting(E_ALL);

require_once 'Parsedown.php';

define('DATA_DIR_VIEW', __DIR__ . '/data/');

$shareId = isset($_GET['id']) ? basename($_GET['id']) : '';

if (empty($shareId) || !preg_match('/^share_[a-zA-Z0-9]+$/', $shareId)) {
    http_response_code(400);
    echo '错误：无效的分享ID。';
    exit;
}

$filename = DATA_DIR_VIEW . $shareId . '.json';

if (!file_exists($filename)) {
    http_response_code(404);
    echo '错误：找不到该分享内容。链接可能已失效或错误。';
    exit;
}

$jsonData = file_get_contents($filename);

if ($jsonData === false) {
    http_response_code(500);
    echo '服务器错误：无法读取分享内容。';
    exit;
}

$data = json_decode($jsonData, true);

if ($data === null || !isset($data['content'])) {
    http_response_code(500);
    echo '错误：分享数据格式无效。';
    exit;
}

$rawContent = $data['content'];
$title = isset($data['title']) ? htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8') : '';
$timestamp = isset($data['timestamp']) ? date('Y-m-d H:i', $data['timestamp']) : '未知时间';
$pageTitle = $title !== '' ? $title . ' - 分享内容' : '分享内容';
$displayTitle = $title !== '' ? $title : 'AI 对话分享';

$parsedown = new Parsedown();
$parsedown->setSafeMode(true);
$parsedown->setBreaksEnabled(true);

function detectRoleFromHeading($heading)
{
    if (function_exists('mb_strtolower')) {
        $normalized = mb_strtolower($heading, 'UTF-8');
    } else {
        $normalized = strtolower($heading);
    }

    if (preg_match('/assistant|assist|bot|模型|回复|回答|🤖|助手|ai/u', $normalized)) {
        return 'assistant';
    }

    if (preg_match('/user|question|提问|访客|用户|问答|🧑|👤|👥|👩|👨|🙋|提问者/u', $normalized)) {
        return 'user';
    }

    if (preg_match('/system|系统|提示|说明|指令|context|📌|⚙|注意/u', $normalized)) {
        return 'system';
    }

    return 'other';
}

function extractHeadingEmoji($heading)
{
    if (preg_match('/[\x{1F300}-\x{1FAFF}]/u', $heading, $matches)) {
        return $matches[0];
    }

    return null;
}

$conversationDetected = false;
$conversationMessages = [];
$additionalMarkdown = '';
$additionalHtml = '';
$formattedContent = '';

$pattern = '/^###\s+(.*?)\s*$(.*?)(?=^###\s+|\z)/ms';

if (preg_match_all($pattern, $rawContent, $blocks, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
    $recognizedCount = 0;
    $cursor = 0;
    $extraPieces = [];

    foreach ($blocks as $block) {
        $fullText = $block[0][0];
        $fullOffset = $block[0][1];

        if ($fullOffset > $cursor) {
            $between = substr($rawContent, $cursor, $fullOffset - $cursor);
            $betweenTrimmed = trim($between);
            $betweenClean = preg_replace('/[\s\r\n]+/', '', $betweenTrimmed);
            if ($betweenTrimmed !== '' && $betweenClean !== '---') {
                $extraPieces[] = $between;
            }
        }

        $heading = trim($block[1][0]);
        $content = $block[2][0];

        $content = preg_replace('/^\s*---\s*(\r?\n)?/', '', $content, 1);
        $content = preg_replace('/(\r?\n)?\s*---\s*$/', '', $content, 1);
        $content = trim($content);

        $role = detectRoleFromHeading($heading);
        if ($role !== 'other') {
            $recognizedCount++;
        }

        if ($content !== '') {
            $conversationMessages[] = [
                'name' => $heading,
                'role' => $role,
                'emoji' => extractHeadingEmoji($heading),
                'html' => $parsedown->text($content),
            ];
        }

        $cursor = $fullOffset + strlen($fullText);
    }

    if ($cursor < strlen($rawContent)) {
        $tail = substr($rawContent, $cursor);
        $tailTrimmed = trim($tail);
        $tailClean = preg_replace('/[\s\r\n]+/', '', $tailTrimmed);
        if ($tailTrimmed !== '' && $tailClean !== '---') {
            $extraPieces[] = $tail;
        }
    }

    if (!empty($conversationMessages)) {
        $totalBlocks = count($conversationMessages);
        if ($recognizedCount >= 2 || ($totalBlocks <= 2 && $recognizedCount >= 1)) {
            $conversationDetected = true;
            if (!empty($extraPieces)) {
                $additionalMarkdown = trim(implode("\n\n", array_map('trim', $extraPieces)));
            }
        } else {
            $conversationMessages = [];
        }
    }
}

if ($conversationDetected) {
    if ($additionalMarkdown !== '') {
        $additionalHtml = $parsedown->text($additionalMarkdown);
    }
} else {
    $formattedContent = $parsedown->text($rawContent);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-okaidia.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif,
            "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            background:
                radial-gradient(120% 120% at 10% 10%, rgba(79, 70, 229, 0.28), transparent 65%),
                radial-gradient(120% 120% at 90% 0%, rgba(14, 165, 233, 0.25), transparent 70%),
                linear-gradient(135deg, #020617 0%, #0f172a 60%, #1f2937 100%);
            color: #e2e8f0;
            min-height: 100vh;
        }

        ::selection {
            background: rgba(165, 180, 252, 0.35);
            color: #f8fafc;
        }

        .prose :where(a):not(:where([class~="not-prose"] *)) {
            text-decoration: none;
            position: relative;
        }

        .prose :where(a):not(:where([class~="not-prose"] *))::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -1px;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, rgba(96, 165, 250, 0.6), rgba(167, 139, 250, 0.4));
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .prose :where(a):not(:where([class~="not-prose"] *)):hover::after {
            opacity: 1;
        }
    </style>
</head>
<body class="relative">
<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-500/20 blur-3xl rounded-full"></div>
    <div class="absolute -bottom-32 -right-24 w-[28rem] h-[28rem] bg-sky-500/20 blur-3xl rounded-full"></div>
</div>

<main class="relative z-10 max-w-5xl mx-auto px-4 md:px-6 py-12 md:py-16 space-y-8 md:space-y-12">
    <header class="bg-slate-900/70 border border-slate-800/70 shadow-2xl backdrop-blur-xl rounded-3xl px-6 py-6 md:px-8 md:py-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-start gap-4">
            <div class="h-12 w-12 md:h-[3.5rem] md:w-[3.5rem] rounded-2xl bg-indigo-500/30 text-indigo-200 border border-indigo-400/40 flex items-center justify-center text-xl md:text-2xl shadow-inner">
                <i class="fa-regular fa-comments"></i>
            </div>
            <div>
                <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-2">AI 对话分享</p>
                <h1 class="text-2xl md:text-3xl font-semibold text-white leading-tight">
                    <?php echo $displayTitle; ?>
                </h1>
                <p class="mt-3 text-sm text-slate-400 flex items-center gap-2">
                    <i class="far fa-clock"></i>
                    <span><?php echo $timestamp; ?></span>
                </p>
            </div>
        </div>
        <div class="flex flex-col md:items-end gap-2 w-full md:w-auto">
            <button onclick="copyLink()" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl bg-gradient-to-r from-indigo-500/90 to-sky-500/80 hover:from-indigo-500 hover:to-sky-500 text-sm font-semibold text-white transition-colors duration-200 shadow-lg shadow-indigo-900/30 w-full md:w-auto">
                <i class="fa-regular fa-copy text-base"></i>
                复制分享链接
            </button>
            <span id="copy-status" class="text-xs text-emerald-400 min-h-[1rem]"></span>
        </div>
    </header>

    <section class="bg-slate-900/80 border border-slate-800/70 backdrop-blur-xl shadow-2xl rounded-[26px] overflow-hidden">
        <div class="relative">
            <div class="absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-white/5 to-transparent pointer-events-none"></div>
            <div class="relative p-6 md:p-10 space-y-10">
                <?php if ($conversationDetected && !empty($conversationMessages)): ?>
                    <?php
                    $rolePresets = [
                        'assistant' => [
                            'bubble' => 'bg-slate-800/80 border border-slate-700/60 text-slate-100 shadow-lg backdrop-blur-md',
                            'avatar' => 'bg-sky-500/20 text-sky-100 border border-sky-400/40',
                            'icon' => '🤖',
                        ],
                        'user' => [
                            'bubble' => 'bg-indigo-500/90 text-indigo-50 shadow-lg backdrop-blur-md',
                            'avatar' => 'bg-indigo-500 text-indigo-50 border border-indigo-300/60',
                            'icon' => '🧑',
                        ],
                        'system' => [
                            'bubble' => 'bg-amber-500/15 border border-amber-300/30 text-amber-100 shadow-lg backdrop-blur-md',
                            'avatar' => 'bg-amber-500/30 text-amber-100 border border-amber-200/40',
                            'icon' => '⚙️',
                        ],
                        'other' => [
                            'bubble' => 'bg-slate-700/80 border border-slate-600/70 text-slate-100 shadow-lg backdrop-blur-md',
                            'avatar' => 'bg-slate-600/60 text-slate-200 border border-slate-500/80',
                            'icon' => '💬',
                        ],
                    ];
                    ?>
                    <div class="space-y-8">
                        <?php foreach ($conversationMessages as $message): ?>
                            <?php
                            $role = $message['role'];
                            $preset = $rolePresets[$role] ?? $rolePresets['other'];
                            $avatarSymbol = $message['emoji'] ?: $preset['icon'];
                            $isUser = $role === 'user';
                            $alignmentClass = $isUser ? 'justify-end' : 'justify-start';
                            $directionClass = $isUser ? 'flex-row-reverse text-right' : '';
                            $nameAlignment = $isUser ? 'text-right' : '';
                            $bubbleWidth = $isUser ? 'md:max-w-2xl' : 'md:max-w-3xl';
                            $displayName = htmlspecialchars($message['name'], ENT_QUOTES, 'UTF-8');
                            ?>
                            <div class="flex <?php echo $alignmentClass; ?>">
                                <div class="flex <?php echo trim('items-start gap-3 md:gap-4 ' . $directionClass); ?> w-full <?php echo $bubbleWidth; ?>">
                                    <div class="flex flex-col items-center gap-2 flex-shrink-0">
                                        <div class="h-11 w-11 md:h-12 md:w-12 rounded-3xl border <?php echo $preset['avatar']; ?> flex items-center justify-center text-lg md:text-xl shadow-lg shadow-black/30">
                                            <?php echo $avatarSymbol; ?>
                                        </div>
                                        <span class="text-[10px] uppercase tracking-widest text-slate-500 <?php echo $nameAlignment; ?>">
                                            <?php echo $displayName; ?>
                                        </span>
                                    </div>
                                    <div class="rounded-3xl px-5 py-4 md:px-6 md:py-5 leading-relaxed <?php echo $preset['bubble']; ?> w-full">
                                        <div class="prose prose-sm md:prose-base prose-invert max-w-none">
                                            <?php echo $message['html']; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($additionalHtml)): ?>
                        <div class="pt-8 border-t border-slate-800/70">
                            <p class="text-xs uppercase tracking-[0.3em] text-slate-500 mb-4">附加内容</p>
                            <article class="prose prose-invert lg:prose-lg max-w-none">
                                <?php echo $additionalHtml; ?>
                            </article>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <article class="prose prose-invert lg:prose-xl max-w-none">
                        <?php echo $formattedContent; ?>
                    </article>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="text-center text-xs text-slate-500/80 pt-2 pb-2">
        <p>内容由用户分享，可能包含来自大模型的自动生成信息。</p>
    </footer>
</main>

<script>
    function copyLink() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            const statusEl = document.getElementById('copy-status');
            if (!statusEl) return;
            statusEl.textContent = '链接已复制';
            statusEl.classList.remove('text-emerald-400', 'text-rose-400');
            statusEl.classList.add('text-emerald-400');
            setTimeout(() => {
                statusEl.textContent = '';
            }, 2200);
        }).catch(err => {
            console.error('无法复制链接: ', err);
            const statusEl = document.getElementById('copy-status');
            if (!statusEl) return;
            statusEl.textContent = '复制失败';
            statusEl.classList.remove('text-emerald-400', 'text-rose-400');
            statusEl.classList.add('text-rose-400');
            setTimeout(() => {
                statusEl.textContent = '';
            }, 2200);
        });
    }
</script>
</body>
</html>

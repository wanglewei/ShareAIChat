<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分享大模型回复</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* 可选: 添加一些自定义基础样式 */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
        }
        /* 增加科技感背景 */
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: #e0e0e0; /* 基础文字颜色 */
        }
        textarea:focus {
            outline: 2px solid rgba(59, 130, 246, 0.5); /* Tailwind blue-500 with opacity */
            outline-offset: 2px;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-[#111827]">
    <div class="bg-[#1f2937] shadow-xl rounded-lg p-8 max-w-2xl w-full border border-gray-700">
        <h1 class="text-3xl font-bold mb-6 text-center text-white flex items-center justify-center">
            <i class="fas fa-share-alt mr-3 text-blue-400"></i> 分享你的 AI 对话
        </h1>
        <p class="text-gray-400 mb-6 text-center">将大模型的回复粘贴到下方，生成一个可分享的链接。</p>
        <form action="submit.php" method="post">
            <div class="mb-6">
                <label for="content" class="block mb-2 text-sm font-medium text-gray-300">回复内容:</label>
                <textarea
                    id="content"
                    name="content"
                    rows="15"
                    required
                    class="block w-full p-4 bg-[#374151] border border-gray-600 rounded-lg text-gray-200 placeholder-gray-500 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out text-sm"
                    placeholder="在此处粘贴大模型的完整回复..."
                ></textarea>
            </div>
             <div class="mb-6">
                 <label for="title" class="block mb-2 text-sm font-medium text-gray-300">可选标题 (用于突出显示):</label>
                 <input
                     type="text"
                     id="title"
                     name="title"
                     class="block w-full p-3 bg-[#374151] border border-gray-600 rounded-lg text-gray-200 placeholder-gray-500 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out text-sm"
                     placeholder="例如：模型关于量子计算的解释"
                 />
             </div>
            <button
                type="submit"
                class="w-full bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-blue-500 transition duration-300 ease-in-out flex items-center justify-center text-lg"
            >
                <i class="fas fa-magic mr-2"></i> 生成分享链接
            </button>
        </form>
    </div>
</body>
</html>

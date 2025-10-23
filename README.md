# AI 对话分享

一个轻量级的 PHP 应用，用于分享大模型的对话内容。只需粘贴对话，即可生成可分享的链接。

## 特色

- **无需数据库**：会话以 JSON 文件形式保存在 `data/` 目录。
- **Markdown 支持**：借助 [Parsedown](https://parsedown.org/) 与 PrismJS 渲染 Markdown 以及代码高亮。
- **聊天气泡布局**：自动识别以 `### User` / `### Assistant` 开头的分段，左右排布成对话气泡。
- **跨平台部署**：只要服务器支持 PHP，直接上传即可运行。

## 快速开始

1. 确保服务器安装了 PHP 7.4 及以上版本，并拥有对项目目录的写入权限。
2. 上传整个项目（包含 `Parsedown.php`）。
3. 确认 `data/` 目录存在，且 Web 服务器用户对其有写入权限。
4. （可选）若无法访问外网，请把 Tailwind、Font Awesome、Prism 等 CDN 资源替换为本地文件。

在浏览器访问 `index.php`，粘贴对话并提交即可生成链接，`view.php` 负责展示页面。

## 项目结构

- `index.php`：提交新对话的表单页面。
- `submit.php`：接收表单并将内容保存为 JSON。
- `view.php`：以聊天形式渲染分享页面。
- `Parsedown.php`：Markdown 解析库。
- `data/`：对话数据存储目录（默认为空）。

## 部署建议

- 正式环境请移除 `ini_set('display_errors', 1)`，避免暴露错误信息。
- 若需长期保留分享内容，请制定备份或清理策略。
- 对公网开放时，建议增加鉴权、防刷或速率限制。

## 许可证

请选择与你开源目标匹配的许可证（如 MIT、Apache-2.0），并把完整文本放入 `LICENSE` 文件。

## 贡献指南

欢迎提交 Issue 和 Pull Request。报告问题时请附上复现步骤，涉及 UI/UX 调整时建议提供修改前后的截图。

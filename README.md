极简音符音乐平台 (Jijian Yinfu Music Platform)
一款轻量级、无干扰的 Web 音乐播放与管理平台，专注于为用户提供纯粹的音乐体验。
✨ 功能特性

🎵 流畅播放：基于 HTML5 Audio 的播放器，支持播放 / 暂停、上一曲 / 下一曲、进度条拖拽、音量调节。

🎶 多样模式：提供顺序播放、随机播放、单曲循环、列表循环四种播放模式。

📋 歌单管理：创建专属歌单，自由添加 / 删除歌曲，构建你的私人音乐库。

🔍 智能搜索：支持按歌曲名、歌手、专辑进行模糊搜索，快速定位心仪曲目。

👤 用户系统：安全的用户注册 / 登录机制，保障你的数据和歌单隐私。

🌸 视觉舒适：樱花背景 + 毛玻璃半透明效果，界面简洁美观，沉浸感十足。
🛠️ 技术栈

后端: PHP 7.4+
前端: HTML5, CSS3, JavaScript (ES6+)
数据库: MySQL 5.7+
服务器: Apache 2.4+ / Nginx
架构: B/S (Browser/Server) 架构
🚀 快速开始
1. 环境准备
确保你的系统已安装以下软件：
PHP 7.4 或更高版本
MySQL 5.7 或更高版本
Apache 或 Nginx 服务器
推荐使用集成环境，如 XAMPP 或 WAMP，可一键部署。
2. 项目部署
克隆项目
git clone https://github.com/[JYqiufeng]/jijian-yinfu.git
移动文件
将项目文件夹移动到你的 Web 服务器根目录下，例如 htdocs 或 www。
配置数据库
登录你的 MySQL 管理工具（如 phpMyAdmin）。
创建一个新的数据库，例如 music_platform，字符集选择 utf8mb4。
导入项目根目录下的 database.sql 文件到该数据库中。
修改配置
编辑 config/db.php 文件，填入你的数据库连接信息：
$host = 'localhost';
$dbname = 'music_platform';
$username = 'root'; // 你的数据库用户名
$password = 'your_password'; // 你的数据库密码
启动服务
启动你的 Apache/Nginx 和 MySQL 服务。
3. 访问平台
打开浏览器，访问 http://localhost/[你的项目文件夹名]/login.php，即可开始使用。
新用户请先点击 “立即注册” 创建账号。
登录成功后，系统会自动跳转到首页，你可以开始探索音乐的世界了！
<img width="713" height="383" alt="image" src="https://github.com/user-attachments/assets/58b67b4f-90f1-489f-8f71-c1d36d305622" />

<img width="1695" height="1302" alt="image" src="https://github.com/user-attachments/assets/00e07d05-6053-487e-a546-6ee47fefb2ad" />


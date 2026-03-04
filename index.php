<?php
session_start();
$host = 'localhost';
$dbname = 'music_platform';
$username = 'root';
$password = 'root'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 处理添加请求
$addError = '';
$addSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['song_id'], $_POST['playlist_id'])) {
    $songId = (int)$_POST['song_id'];
    $playlistId = (int)$_POST['playlist_id'];
    
    try {
        // 检查是否在播放列表中
        $stmt = $pdo->prepare("SELECT * FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
        $stmt->execute([$playlistId, $songId]);
        if ($stmt->rowCount() > 0) {
            $addError = '该歌曲已在播放列表中！';
        } else {
            // 获取最大排序值
            $stmt = $pdo->prepare("SELECT MAX(sort) AS max_sort FROM playlist_songs WHERE playlist_id = ?");
            $stmt->execute([$playlistId]);
            $maxSort = $stmt->fetch(PDO::FETCH_ASSOC)['max_sort'] ?? 0;
            
            // 插入歌曲到播放列表
            $stmt = $pdo->prepare("INSERT INTO playlist_songs (playlist_id, song_id, sort) VALUES (?, ?, ?)");
            $stmt->execute([$playlistId, $songId, $maxSort + 1]);
            $addSuccess = '添加到播放列表成功！';
        }
    } catch (PDOException $e) {
        $addError = '添加失败：' . $e->getMessage();
    }
}

// 搜索
$searchKeyword = '';
$songs = [];
if (isset($_GET['search']) && !empty(trim($_GET['keyword']))) {
    $searchKeyword = trim($_GET['keyword']);
    // 模糊搜索
    $stmt = $pdo->prepare("
        SELECT * FROM songs 
        WHERE title LIKE ? OR artist LIKE ? OR album LIKE ?
        ORDER BY song_id DESC
    ");
    $likeKeyword = "%{$searchKeyword}%";
    $stmt->execute([$likeKeyword, $likeKeyword, $likeKeyword]);
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // 未搜索时显示所有歌曲
    $stmt = $pdo->prepare("SELECT * FROM songs ORDER BY song_id DESC");
    $stmt->execute();
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 获取播放列表
$stmt = $pdo->prepare("SELECT * FROM playlists WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>极简音符 - 首页</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Microsoft Yahei", "PingFang SC", sans-serif;
        }

        body {
            min-height: 100vh;
            background: url('./assets/images/登录背景.jpg') no-repeat center center fixed;
            background-size: cover;
            padding-bottom: 70px; 
        }

        .container {
            width: 92%;
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: rgba(34, 34, 34, 0.9); 
            color: #fff;
            padding: 16px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: transparent; 
            backdrop-filter: none;
            box-shadow: none;
            padding: 0 20px;
            margin: 0 auto;
            width: 92%;
        }

        .header-actions a {
            color: #ccc;
            text-decoration: none;
            margin-left: 24px;
            font-size: 14px;
        }

        .header-actions a:hover {
            color: #fff;
        }

        /* 搜索栏 */
        .search-bar {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 12px 16px;
            font-size: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            outline: none;
            background: rgba(255, 255, 255, 0.8); 
        }

        .search-input:focus {
            border-color: #1db954;
            background: #fff;
        }

        .search-btn {
            padding: 12px 24px;
            background-color: #1db954;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            transition: background-color 0.2s;
        }

        .search-btn:hover {
            background-color: #1ed760;
        }

        /* 页面标题 */
        .page-title {
            margin: 24px 0;
            color: #333;
            font-size: 20px;
            font-weight: 600;
        }

        .search-tip {
            color: #666;
            font-size: 14px;
            margin: -16px 0 20px 0;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin: 16px 0;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* 歌曲列表 */
        .song-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin: 32px 0;
        }

        .song-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .song-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .song-cover {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .song-info {
            padding: 16px;
        }

        .song-title {
            font-size: 18px;
            color: #333;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .song-meta {
            font-size: 14px;
            color: #666;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .song-actions {
            display: flex;
            gap: 10px;
        }

        .play-song-btn {
            flex: 1;
            padding: 8px 0;
            background-color: #1db954;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .play-song-btn:hover {
            background-color: #1ed760;
        }

        .add-to-playlist-btn {
            flex: 1;
            padding: 8px 0;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .add-to-playlist-btn:hover {
            background-color: #444;
        }

        /* 添加到播放列表弹窗 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 24px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
        }

        .modal-title {
            font-size: 18px;
            color: #333;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .modal-close {
            float: right;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }

        .modal-close:hover {
            color: #000;
        }

        .playlist-select {
            width: 100%;
            padding: 10px 16px;
            font-size: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 16px;
            outline: none;
        }

        .playlist-select:focus {
            border-color: #1db954;
        }

        .modal-btn {
            padding: 10px 24px;
            background-color: #1db954;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            transition: background-color 0.2s;
        }

        .modal-btn:hover {
            background-color: #1ed760;
        }

        /* 播放器 */
        .player {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(44, 44, 44, 0.95); 
            color: #e0e0e0;
            padding: 10px 20px;
            border-top: 1px solid #444;
            z-index: 9999;
            height: 60px;
            display: flex;
            align-items: center;
        }

        .player .container {
            display: flex;
            align-items: center;
            gap: 20px;
            width: 100%;
            background: transparent;
            backdrop-filter: none;
            box-shadow: none;
            padding: 0;
        }
        #current-song-title {
            min-width: 180px;
            font-size: 14px;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* 横向排列 */
        .core-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .play-mode {
            position: relative;
        }

        .mode-toggle {
            background-color: #3a3a3a;
            border: none;
            color: #e0e0e0;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            height: 30px;
            transition: background-color 0.2s;
        }

        .mode-toggle::before {
            content: "⊟";
            font-size: 10px;
            color: #888;
        }

        .mode-dropdown {
            position: absolute;
            bottom: 100%;
            left: 0;
            background-color: #3a3a3a;
            border-radius: 4px;
            list-style: none;
            padding: 6px 0;
            margin: 5px 0 0 0;
            width: 120px;
            display: none;
            z-index: 10000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .mode-dropdown li {
            padding: 6px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .mode-dropdown li::before {
            content: "⊠";
            font-size: 10px;
            color: #888;
        }

        .play-mode:hover .mode-dropdown {
            display: block;
        }

        /* 控制按钮组 */
        .play-controls-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .control-btn {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: none;
            background-color: #3a3a3a;
            color: #e0e0e0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            transition: background-color 0.2s;
        }

        .play-btn {
            width: 26px;
            height: 26px;
            background-color: #1db954;
            color: #fff;
            font-size: 12px;
        }

        .control-btn:hover {
            background-color: #484848;
        }

        .play-btn:hover {
            background-color: #1ed760;
        }

        /* 进度条*/
        .progress-area {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .progress-bar {
            flex: 1;
            height: 3px;
            background-color: #444;
            border-radius: 2px;
            cursor: pointer;
            position: relative;
            transition: background-color 0.2s;
        }

        .progress-bar:hover {
            background-color: #555;
        }

        .progress-fill {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background-color: #1db954;
            border-radius: 2px;
            width: 0%;
            transition: width 0.1s linear;
        }

        .time-text {
            font-size: 12px;
            color: #999;
            min-width: 40px;
            text-align: center;
        }

        /* 音量控制 */
        .volume-control {
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 100px;
        }

        .volume-bar {
            width: 50px;
            height: 3px;
            background-color: #444;
            border-radius: 2px;
            cursor: pointer;
            position: relative;
        }

        .volume-fill {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background-color: #1db954;
            border-radius: 2px;
            width: 80%;
        }
    </style>
</head>
<body>
    <!-- 页面头部 -->
    <div class="header">
        <div class="container">
            <h1>极简音符</h1>
            <div class="header-actions">
                <a href="playlist.php">我的播放列表</a>
                <a href="logout.php">登出 (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
            </div>
        </div>
    </div>

    <!-- 主要内容区 -->
    <div class="container">
        <!-- 搜索栏 -->
        <div class="search-bar">
            <form method="GET" action="index.php" style="display: flex; width: 100%; gap: 10px;">
                <input 
                    type="text" 
                    name="keyword" 
                    class="search-input" 
                    placeholder="搜索歌曲、歌手、专辑..." 
                    value="<?php echo htmlspecialchars($searchKeyword); ?>"
                >
                <button type="submit" name="search" class="search-btn">搜索</button>
            </form>
        </div>

        <!-- 搜索提示 -->
        <?php if (!empty($searchKeyword)): ?>
            <div class="search-tip">
                搜索结果：“<?php echo htmlspecialchars($searchKeyword); ?>”（共 <?php echo count($songs); ?> 首歌曲）
            </div>
        <?php endif; ?>

        <h2 class="page-title">全部歌曲</h2>

        <?php if ($addSuccess): ?>
            <div class="alert alert-success"><?php echo $addSuccess; ?></div>
        <?php endif; ?>
        <?php if ($addError): ?>
            <div class="alert alert-error"><?php echo $addError; ?></div>
        <?php endif; ?>

        <!-- 歌曲列表 -->
        <div class="song-list">
            <?php if (empty($songs)): ?>
                <p style="grid-column: 1 / -1; text-align: center; color: #666; font-size: 16px;">
                    <?php echo !empty($searchKeyword) ? '未找到相关歌曲' : '暂无歌曲'; ?>
                </p>
            <?php else: ?>
                <?php foreach ($songs as $song): ?>
                    <div class="song-card">
                        <img 
                            src="<?php echo !empty($song['cover_path']) ? htmlspecialchars($song['cover_path']) : 'assets/images/default-cover.png'; ?>" 
                            alt="<?php echo htmlspecialchars($song['title']); ?>"
                            class="song-cover"
                        >
                        <div class="song-info">
                            <h3 class="song-title"><?php echo htmlspecialchars($song['title']); ?></h3>
                            <div class="song-meta">
                                歌手：<?php echo htmlspecialchars($song['artist']); ?><br>
                                专辑：<?php echo htmlspecialchars($song['album']); ?><br>
                                时长：<?php echo htmlspecialchars($song['duration']); ?>
                            </div>
                            <div class="song-actions">
                                <button 
                                    class="play-song-btn"
                                    data-song-id="<?php echo $song['song_id']; ?>"
                                    data-song-title="<?php echo htmlspecialchars($song['title']); ?>"
                                    data-song-path="<?php echo htmlspecialchars($song['file_path']); ?>"
                                >
                                    播放
                                </button>
                                <button 
                                    class="add-to-playlist-btn"
                                    data-song-id="<?php echo $song['song_id']; ?>"
                                    onclick="openAddModal(<?php echo $song['song_id']; ?>)"
                                >
                                    加入列表
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 添加到播放列表 -->
    <div class="modal" id="add-playlist-modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeAddModal()">&times;</span>
            <h3 class="modal-title">添加到播放列表</h3>
            <form method="POST" action="index.php">
                <input type="hidden" name="song_id" id="modal-song-id">
                <select name="playlist_id" class="playlist-select" required>
                    <option value="">请选择播放列表</option>
                    <?php foreach ($playlists as $pl): ?>
                        <option value="<?php echo $pl['playlist_id']; ?>">
                            <?php echo htmlspecialchars($pl['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="modal-btn">确认添加</button>
            </form>
        </div>
    </div>

    <!-- 底部播放器 -->
    <div class="player">
        <div class="container">
            <!-- 当前播放歌曲 -->
            <div id="current-song-title">未选择歌曲</div>
            <div class="core-controls">
                <div class="play-mode">
                    <button class="mode-toggle" id="current-mode-btn">顺序播放</button>
                    <ul class="mode-dropdown" id="mode-list">
                        <li data-mode="random">随机播放</li>
                        <li data-mode="order">顺序播放</li>
                        <li data-mode="single">单曲循环</li>
                        <li data-mode="list">列表循环</li>
                    </ul>
                </div>

                <!-- 控制按钮 -->
                <div class="play-controls-group">
                    <button class="control-btn" id="prev-btn">◀◀</button>
                    <button class="control-btn play-btn" id="play-btn">▶</button>
                    <button class="control-btn" id="next-btn">▶▶</button>
                </div>

                <!-- 进度条时间 -->
                <div class="progress-area">
                    <span class="time-text" id="current-time">00:00</span>
                    <div class="progress-bar" id="progress-bar">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    <span class="time-text" id="total-time">00:00</span>
                </div>

                <!-- 音量 -->
                <div class="volume-control">
                    <span>🔊</span>
                    <div class="volume-bar" id="volume-bar">
                        <div class="volume-fill" id="volume-fill"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openAddModal(songId) {
            document.getElementById('modal-song-id').value = songId;
            document.getElementById('add-playlist-modal').style.display = 'flex';
        }

        function closeAddModal() {
            document.getElementById('add-playlist-modal').style.display = 'none';
        }
        window.onclick = function(event) {
            const modal = document.getElementById('add-playlist-modal');
            if (event.target === modal) {
                closeAddModal();
            }
        }

        // 播放器核心
        const audio = new Audio();
        let currentPlayMode = 'order';
        let isPlaying = false;
        let playQueue = [];
        let currentQueueIndex = 0;
        let currentSong = null;

        // DOM元素获取
        const dom = {
            currentModeBtn: document.getElementById('current-mode-btn'),
            modeList: document.getElementById('mode-list'),
            playBtn: document.getElementById('play-btn'),
            prevBtn: document.getElementById('prev-btn'),
            nextBtn: document.getElementById('next-btn'),
            progressBar: document.getElementById('progress-bar'),
            progressFill: document.getElementById('progress-fill'),
            currentTimeEl: document.getElementById('current-time'),
            totalTimeEl: document.getElementById('total-time'),
            currentSongTitle: document.getElementById('current-song-title'),
            volumeBar: document.getElementById('volume-bar'),
            volumeFill: document.getElementById('volume-fill'),
            playSongBtns: document.querySelectorAll('.play-song-btn')
        };

        // 初始化播放队列
        function initPlayQueue() {
            playQueue = [];
            dom.playSongBtns.forEach(btn => {
                playQueue.push({
                    id: btn.dataset.songId,
                    title: btn.dataset.songTitle,
                    path: btn.dataset.songPath
                });
            });
        }

        // 初始化播放器
        function initPlayer() {
            initPlayQueue();

            //  模式切换
            dom.modeList.addEventListener('click', (e) => {
                if (e.target.tagName === 'LI') {
                    currentPlayMode = e.target.dataset.mode;
                    dom.currentModeBtn.textContent = e.target.textContent;
                }
            });

            // 播放/暂停切换
            dom.playBtn.addEventListener('click', togglePlay);

            // 上一曲/下一曲
            dom.prevBtn.addEventListener('click', playPrevSong);
            dom.nextBtn.addEventListener('click', playNextSong);

            // 进度条点击调整播放进度
            dom.progressBar.addEventListener('click', setProgress);

            //  音量调节
            dom.volumeBar.addEventListener('click', setVolume);

            // 实时更新进度条和时间
            audio.addEventListener('timeupdate', updateProgress);

            // 加载歌曲
            audio.addEventListener('loadedmetadata', () => {
                dom.totalTimeEl.textContent = formatTime(audio.duration);
            });

            // 歌曲播放结束自动切歌
            audio.addEventListener('ended', handleSongEnd);

            //  绑定歌曲列表播放
            dom.playSongBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const songId = btn.dataset.songId;
                    currentQueueIndex = playQueue.findIndex(song => song.id === songId);
                    loadSong(playQueue[currentQueueIndex]);
                    playSong();
                });
            });
            audio.volume = 0.8;
        }
        function loadSong(song) {
            if (!song) return;
            currentSong = song;
            audio.src = song.path;
            dom.currentSongTitle.textContent = song.title;
            dom.progressFill.style.width = '0%';
            dom.currentTimeEl.textContent = '00:00';
        }

        // 播放
        function playSong() {
            if (!currentSong && playQueue.length > 0) {
                currentQueueIndex = 0;
                loadSong(playQueue[0]);
            }
            audio.play();
            isPlaying = true;
            dom.playBtn.textContent = '❚❚';
        }

        // 暂停
        function pauseSong() {
            audio.pause();
            isPlaying = false;
            dom.playBtn.textContent = '▶';
        }

        // 播放/暂停切换
        function togglePlay() {
            isPlaying ? pauseSong() : playSong();
        }

        // 上一曲
        function playPrevSong() {
            if (playQueue.length === 0) return;
            switch (currentPlayMode) {
                case 'random':
                    currentQueueIndex = Math.floor(Math.random() * playQueue.length);
                    break;
                default:
                    currentQueueIndex = (currentQueueIndex - 1 + playQueue.length) % playQueue.length;
                    break;
            }
            loadSong(playQueue[currentQueueIndex]);
            if (isPlaying) audio.play();
        }

        // 下一曲
        function playNextSong() {
            if (playQueue.length === 0) return;
            switch (currentPlayMode) {
                case 'random':
                    currentQueueIndex = Math.floor(Math.random() * playQueue.length);
                    break;
                case 'single':
                    audio.currentTime = 0;
                    audio.play();
                    return;
                default:
                    currentQueueIndex = (currentQueueIndex + 1) % playQueue.length;
                    break;
            }
            loadSong(playQueue[currentQueueIndex]);
            if (isPlaying) audio.play();
        }

        // 调整播放进度
        function setProgress(e) {
            if (!currentSong) return;
            const barWidth = dom.progressBar.clientWidth;
            const clickX = e.offsetX;
            const progressRatio = clickX / barWidth;
            const targetTime = progressRatio * audio.duration;
            
            audio.currentTime = targetTime;
            dom.progressFill.style.width = `${progressRatio * 100}%`;
            dom.currentTimeEl.textContent = formatTime(targetTime);
        }

        // 实时更新
        function updateProgress() {
            if (isNaN(audio.duration) || !currentSong) return;
            const progressRatio = audio.currentTime / audio.duration;
            dom.progressFill.style.width = `${progressRatio * 100}%`;
            dom.currentTimeEl.textContent = formatTime(audio.currentTime);
        }

        // 调节音量
        function setVolume(e) {
            const barWidth = dom.volumeBar.clientWidth;
            const clickX = e.offsetX;
            const volumeRatio = clickX / barWidth;
            audio.volume = volumeRatio;
            dom.volumeFill.style.width = `${volumeRatio * 100}%`;
        }
        function handleSongEnd() {
            playNextSong();
        }

        // 时间
        function formatTime(seconds) {
            if (isNaN(seconds)) return '00:00';
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        // 页面初始化
        window.addEventListener('DOMContentLoaded', initPlayer);
    </script>
</body>
</html>
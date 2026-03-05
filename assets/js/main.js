// 全局变量
let currentSong = null;
let audio = new Audio();
let isPlaying = false;
let isRandom = false;
let playMode = "order"; // 播放模式：order=顺序播放，loop=循环列表，repeat=重复单首，random=随机播放
let playlist = [];
let currentIndex = 0;

// 初始化播放器
document.addEventListener('DOMContentLoaded', function() {
    // 获取元素
    const playBtn = document.getElementById('play-btn');
    const pauseBtn = document.getElementById('pause-btn');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const randomBtn = document.getElementById('random-btn');
    const modeBtn = document.getElementById('mode-btn');
    const progressBar = document.getElementById('progress-bar');
    const progressFill = document.getElementById('progress-fill');
    const volumeBar = document.getElementById('volume-bar');
    const volumeFill = document.getElementById('volume-fill');

    // 播放/暂停
    playBtn.addEventListener('click', playSong);
    pauseBtn.addEventListener('click', pauseSong);

    // 上一曲/下一曲
    prevBtn.addEventListener('click', playPrevSong);
    nextBtn.addEventListener('click', playNextSong);

    // 随机播放
    randomBtn.addEventListener('click', function() {
        isRandom = !isRandom;
        playMode = isRandom ? "random" : playMode;
        this.style.color = isRandom ? '#61dafb' : '#fff';
    });

    // 播放模式切换：顺序 → 循环列表 → 重复单首
    modeBtn.addEventListener('click', function() {
        if (playMode === "order") {
            playMode = "loop"; // 切换为“循环列表”
            this.innerHTML = "🔄"; // 图标改为循环列表
            this.style.color = "#61dafb"; // 高亮
        } else if (playMode === "loop") {
            playMode = "repeat"; // 切换为“重复单首”
            this.innerHTML = "🔂"; // 图标改为重复单首
        } else if (playMode === "repeat") {
            playMode = "order"; // 切换为“顺序播放”
            this.innerHTML = "▶▶"; // 图标改为顺序播放
            this.style.color = "#fff"; // 取消高亮
        }
        // 若开启了随机播放，切换模式时不影响随机状态
        if (isRandom) {
            playMode = "random";
        }
    });

    // 进度条控制
    progressBar.addEventListener('click', function(e) {
        const pos = (e.pageX - this.offsetLeft) / this.offsetWidth;
        audio.currentTime = pos * audio.duration;
    });

    // 音量控制
    volumeBar.addEventListener('click', function(e) {
        const pos = (e.pageX - this.offsetLeft) / this.offsetWidth;
        audio.volume = pos;
        volumeFill.style.width = pos * 100 + '%';
    });

    // 音频时间更新
    audio.addEventListener('timeupdate', function() {
        const progress = (audio.currentTime / audio.duration) * 100;
        progressFill.style.width = progress + '%';
    });

    // 音频播放结束
    audio.addEventListener('ended', function() {
        switch(playMode) {
            case "repeat":
                // 重复单首：重新播放当前歌曲
                playCurrentSong();
                break;
            case "loop":
                // 循环列表：播放下一首（最后一首则回到第一首）
                currentIndex = (currentIndex + 1) % playlist.length;
                currentSong = playlist[currentIndex];
                playCurrentSong();
                break;
            case "random":
                // 随机播放：随机选下一首
                currentIndex = Math.floor(Math.random() * playlist.length);
                currentSong = playlist[currentIndex];
                playCurrentSong();
                break;
            default: // 顺序播放
                // 播放下一首，若已是最后一首则停止
                if (currentIndex < playlist.length - 1) {
                    currentIndex++;
                    currentSong = playlist[currentIndex];
                    playCurrentSong();
                } else {
                    // 顺序播放到最后一首，停止并重置状态
                    isPlaying = false;
                }
                break;
        }
    });

    // 绑定歌曲播放事件并初始化播放列表
    const playSongBtns = document.querySelectorAll('.play-song-btn');
    playSongBtns.forEach((btn, index) => {
        const songId = btn.getAttribute('data-song-id');
        const songTitle = btn.getAttribute('data-song-title');
        const songPath = btn.getAttribute('data-song-path');
        const lyricPath = btn.getAttribute('data-lyric-path');
        
        // 将歌曲存入playlist数组
        playlist.push({
            id: songId,
            title: songTitle,
            path: songPath,
            lyricPath: lyricPath
        });

        // 播放按钮的点击事件
        btn.addEventListener('click', function() {
            currentSong = playlist[index];
            currentIndex = index;
            playCurrentSong();
        });
    });

    // 添加到播放列表
    const addToPlaylistBtns = document.querySelectorAll('.add-to-playlist-btn');
    addToPlaylistBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const songId = this.getAttribute('data-song-id');
            const playlistId = prompt('请输入播放列表ID：');
            if (playlistId) {
                fetch('add_playlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `playlist_id=${playlistId}&song_id=${songId}`
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                });
            }
        });
    });

    // 初始化拖拽排序（播放列表页面）
    if (document.getElementById('playlist-songs-list')) {
        initSortable();
    }

    // 绑定删除歌曲事件
    const deleteBtns = document.querySelectorAll('.delete-song-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const psId = this.dataset.psId;
            if (confirm('确定删除这首歌曲吗？')) {
                fetch('delete_playlist_song.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ps_id=${psId}`
                }).then(response => response.text())
                  .then(data => {
                      location.reload();
                  });
            }
        });
    });
});

// 初始化拖拽排序
function initSortable() {
    const songList = document.getElementById('playlist-songs-list');
    if (!songList) return;

    const sortable = new Sortable(songList, {
        animation: 150,
        handle: '.song-item',
        onEnd: function(evt) {
            // 拖拽结束后获取新的排序
            const items = evt.target.children;
            const sortData = [];
            for (let i = 0; i < items.length; i++) {
                sortData.push({
                    ps_id: items[i].dataset.psId,
                    sort: i
                });
            }
            // 发送AJAX请求更新排序
            fetch('update_playlist_sort.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(sortData)
            });
        }
    });
}

// 播放当前歌曲
function playCurrentSong() {
    if (!currentSong) return;
    audio.src = currentSong.path;
    audio.play();
    isPlaying = true;
    document.getElementById('current-song-title').textContent = currentSong.title;
    // 加载歌词
    if (currentSong.lyricPath) {
        loadLyric(currentSong.lyricPath);
    } else {
        document.getElementById('lyric-list').innerHTML = '<div class="lyric-item">暂无歌词</div>';
    }
}

// 暂停歌曲
function pauseSong() {
    audio.pause();
    isPlaying = false;
}

// 播放上一曲
function playPrevSong() {
    if (playlist.length === 0) {
        alert('播放列表为空');
        return;
    }
    if (isRandom) {
        currentIndex = Math.floor(Math.random() * playlist.length);
    } else {
        currentIndex = (currentIndex - 1 + playlist.length) % playlist.length;
    }
    currentSong = playlist[currentIndex];
    playCurrentSong();
}

// 播放下一曲
function playNextSong() {
    if (playlist.length === 0) {
        alert('播放列表为空');
        return;
    }
    if (isRandom) {
        currentIndex = Math.floor(Math.random() * playlist.length);
    } else {
        currentIndex = (currentIndex + 1) % playlist.length;
    }
    currentSong = playlist[currentIndex];
    playCurrentSong();
}

// 通用播放函数
function playSong() {
    if (currentSong) {
        audio.play();
        isPlaying = true;
    } else {
        alert('请先选择一首歌曲');
    }
}

// 解析LRC歌词
function parseLrc(lrcText) {
    const lrcRegex = /\[(\d{2}):(\d{2})\.(\d{2})\](.*)/g;
    const lyrics = [];
    let match;
    while ((match = lrcRegex.exec(lrcText)) !== null) {
        const time = parseInt(match[1]) * 60 + parseFloat(match[2] + '.' + match[3]);
        const text = match[4].trim();
        if (text) {
            lyrics.push({ time, text });
        }
    }
    return lyrics;
}

// 加载歌词
function loadLyric(lyricPath) {
    fetch(lyricPath)
        .then(response => {
            if (!response.ok) throw new Error('歌词文件不存在');
            return response.text();
        })
        .then(text => {
            const lyrics = parseLrc(text);
            const lyricList = document.getElementById('lyric-list');
            lyricList.innerHTML = '';
            lyrics.forEach(lyric => {
                const item = document.createElement('div');
                item.className = 'lyric-item';
                item.dataset.time = lyric.time;
                item.textContent = lyric.text;
                lyricList.appendChild(item);
            });
            // 绑定音频时间更新事件
            audio.addEventListener('timeupdate', () => {
                highlightCurrentLyric(lyrics);
            });
        })
        .catch(() => {
            document.getElementById('lyric-list').innerHTML = '<div class="lyric-item">暂无歌词</div>';
        });
}

// 高亮当前歌词
function highlightCurrentLyric(lyrics) {
    const currentTime = audio.currentTime;
    const lyricItems = document.querySelectorAll('.lyric-item');
    let activeIndex = -1;
    for (let i = 0; i < lyrics.length; i++) {
        if (lyrics[i].time <= currentTime) {
            activeIndex = i;
        } else {
            break;
        }
    }
    if (activeIndex >= 0) {
        lyricItems.forEach((item, index) => {
            item.classList.toggle('active', index === activeIndex);
        });
        // 滚动歌词
        const lyricList = document.getElementById('lyric-list');
        lyricList.style.transform = `translateY(-${activeIndex * 25}px)`;
    }
}

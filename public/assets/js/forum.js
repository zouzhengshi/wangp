const mediaInput = document.getElementById('mediaInput');
const mediaPreview = document.getElementById('mediaPreview');
const postForm = document.getElementById('postForm');
const postSubmitButton = document.getElementById('postSubmitButton');
const uploadProgress = document.getElementById('uploadProgress');
const uploadProgressBar = document.getElementById('uploadProgressBar');
const uploadProgressText = document.getElementById('uploadProgressText');
const uploadProgressPercent = document.getElementById('uploadProgressPercent');

const applyMediaRatio = (card, width, height) => {
    if (!card || !width || !height) {
        return;
    }

    const ratio = width / height;
    card.style.setProperty('--media-ratio', `${width} / ${height}`);
    card.classList.remove('is-landscape', 'is-portrait', 'is-square');
    card.classList.add(ratio > 1.05 ? 'is-landscape' : (ratio < 0.95 ? 'is-portrait' : 'is-square'));
};

const bindMediaRatio = (media, card) => {
    const update = () => {
        const width = media.tagName === 'VIDEO' ? media.videoWidth : media.naturalWidth;
        const height = media.tagName === 'VIDEO' ? media.videoHeight : media.naturalHeight;
        applyMediaRatio(card, width, height);
    };

    if (media.tagName === 'VIDEO') {
        media.addEventListener('loadedmetadata', update, { once: true });
        if (media.readyState >= 1) {
            update();
        }
    } else {
        media.addEventListener('load', update, { once: true });
        if (media.complete) {
            update();
        }
    }
};

if (mediaInput && mediaPreview) {
    mediaInput.addEventListener('change', () => {
        mediaPreview.replaceChildren();

        Array.from(mediaInput.files).forEach(file => {
            const previewCard = document.createElement('div');
            previewCard.className = 'media-preview-card';

            const isVideo = file.type.startsWith('video/');
            const isImage = file.type.startsWith('image/');
            if (isImage || isVideo) {
                const objectUrl = URL.createObjectURL(file);
                const media = isVideo ? document.createElement('video') : document.createElement('img');
                media.src = objectUrl;
                media.className = 'media-preview-content';
                media.alt = file.name;
                if (isVideo) {
                    media.controls = true;
                    media.muted = true;
                    media.preload = 'metadata';
                }

                previewCard.appendChild(media);
                bindMediaRatio(media, previewCard);
                const revokeObjectUrl = () => URL.revokeObjectURL(objectUrl);
                media.addEventListener('load', revokeObjectUrl, { once: true });
                media.addEventListener('loadeddata', revokeObjectUrl, { once: true });
            } else {
                const fileSummary = document.createElement('div');
                fileSummary.className = 'media-preview-file';
                fileSummary.innerHTML = '<i class="fa-solid fa-file"></i>';
                const fileName = document.createElement('strong');
                fileName.textContent = file.name;
                const fileSize = document.createElement('small');
                fileSize.textContent = `${(file.size / 1024 / 1024).toFixed(2)} MB`;
                fileSummary.append(fileName, fileSize);
                previewCard.appendChild(fileSummary);
            }
            mediaPreview.appendChild(previewCard);
        });
    });
}

if (postForm && postSubmitButton && uploadProgress && uploadProgressBar && uploadProgressText && uploadProgressPercent) {
    postForm.addEventListener('submit', event => {
        event.preventDefault();
        if (postForm.dataset.uploading === 'true') {
            return;
        }

        postForm.dataset.uploading = 'true';
        postSubmitButton.disabled = true;
        uploadProgress.hidden = false;
        uploadProgress.classList.remove('is-error');
        uploadProgressBar.style.width = '0%';
        uploadProgressText.textContent = '正在上传文件...';
        uploadProgressPercent.textContent = '0%';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', postForm.action, true);
        xhr.upload.addEventListener('progress', progressEvent => {
            if (!progressEvent.lengthComputable) {
                uploadProgressText.textContent = '正在上传文件...';
                return;
            }

            const percent = Math.min(99, Math.round((progressEvent.loaded / progressEvent.total) * 100));
            uploadProgressBar.style.width = `${percent}%`;
            uploadProgressPercent.textContent = `${percent}%`;
        });
        xhr.upload.addEventListener('load', () => {
            uploadProgressBar.style.width = '100%';
            uploadProgressPercent.textContent = '100%';
            uploadProgressText.textContent = '文件上传完成，正在发布帖子...';
        });
        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 400) {
                window.location.href = xhr.responseURL || 'posts.php';
                return;
            }

            uploadProgress.classList.add('is-error');
            uploadProgressText.textContent = '上传失败，请稍后重试。';
            uploadProgressPercent.textContent = '失败';
            postForm.dataset.uploading = 'false';
            postSubmitButton.disabled = false;
        });
        xhr.addEventListener('error', () => {
            uploadProgress.classList.add('is-error');
            uploadProgressText.textContent = '网络异常，上传失败，请重试。';
            uploadProgressPercent.textContent = '失败';
            postForm.dataset.uploading = 'false';
            postSubmitButton.disabled = false;
        });
        xhr.addEventListener('abort', () => {
            uploadProgress.classList.add('is-error');
            uploadProgressText.textContent = '上传已取消。';
            uploadProgressPercent.textContent = '已取消';
            postForm.dataset.uploading = 'false';
            postSubmitButton.disabled = false;
        });
        xhr.send(new FormData(postForm));
    });
}

document.querySelectorAll('.post-media-content').forEach(media => {
    bindMediaRatio(media, media.closest('.post-media-visual'));
});

const formatPlayerTime = seconds => {
    if (!Number.isFinite(seconds) || seconds < 0) {
        return '00:00';
    }
    const totalSeconds = Math.floor(seconds);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const remainder = totalSeconds % 60;
    const base = `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
    return hours > 0 ? `${String(hours).padStart(2, '0')}:${base}` : base;
};

const setPlayerIcon = (button, icon, label) => {
    if (!button) {
        return;
    }
    button.innerHTML = `<i class="fa-solid ${icon}"></i>`;
    button.setAttribute('aria-label', label);
};

document.querySelectorAll('[data-media-player]').forEach(player => {
    const video = player.querySelector('.media-player-video');
    const stage = player.querySelector('.media-player-stage');
    const progress = player.querySelector('[data-player-progress]');
    const volume = player.querySelector('[data-player-volume]');
    const timeLabel = player.querySelector('[data-player-time]');
    const speedLabel = player.querySelector('[data-speed-label]');
    const qualityLabel = player.querySelector('[data-quality-label]');
    const resolutionLabel = player.querySelector('[data-original-resolution]');

    if (!video || !stage || !progress || !volume || !timeLabel) {
        return;
    }

    let controlsTimer = null;
    const showControls = () => {
        player.classList.add('is-controls-visible');
        window.clearTimeout(controlsTimer);
        controlsTimer = window.setTimeout(() => {
            player.classList.remove('is-controls-visible');
        }, 2400);
    };

    player.addEventListener('pointermove', showControls);
    player.addEventListener('pointerdown', showControls);
    player.addEventListener('pointerleave', () => player.classList.remove('is-controls-visible'));
    player.addEventListener('touchstart', showControls, { passive: true });
    player.addEventListener('focusin', showControls);

    const updatePlayState = () => {
        const playing = !video.paused && !video.ended;
        player.classList.toggle('is-playing', playing);
        player.querySelectorAll('[data-player-action="toggle-play"]').forEach(button => {
            setPlayerIcon(button, playing ? 'fa-pause' : 'fa-play', playing ? '暂停' : '播放');
        });
    };

    const updateProgress = () => {
        if (video.duration > 0) {
            progress.value = String((video.currentTime / video.duration) * 100);
        }
        timeLabel.textContent = `${formatPlayerTime(video.currentTime)} / ${formatPlayerTime(video.duration)}`;
    };

    const updateOrientation = () => {
        if (!video.videoWidth || !video.videoHeight) {
            return;
        }
        applyMediaRatio(stage, video.videoWidth, video.videoHeight);
        player.dataset.orientation = video.videoWidth > video.videoHeight * 1.05
            ? 'landscape'
            : (video.videoHeight > video.videoWidth * 1.05 ? 'portrait' : 'square');
        if (resolutionLabel) {
            resolutionLabel.textContent = `${video.videoWidth}×${video.videoHeight}`;
        }
    };

    let orientationLocked = false;
    const lockFullscreenOrientation = async () => {
        if (!window.screen?.orientation?.lock || !player.dataset.orientation) {
            return;
        }
        const target = player.dataset.orientation === 'landscape' ? 'landscape-primary' : 'portrait-primary';
        try {
            await window.screen.orientation.lock(target);
            orientationLocked = true;
        } catch (error) {
            // 部分桌面浏览器不允许锁定方向，保持 CSS 比例即可。
        }
    };

    const unlockFullscreenOrientation = () => {
        if (orientationLocked && window.screen?.orientation?.unlock) {
            window.screen.orientation.unlock();
            orientationLocked = false;
        }
    };

    const syncFullscreenOrientation = () => {
        const fullscreenElement = document.fullscreenElement || document.webkitFullscreenElement;
        if (fullscreenElement === player) {
            lockFullscreenOrientation();
            setPlayerIcon(fullscreenButton, 'fa-compress', '退出全屏');
        } else {
            unlockFullscreenOrientation();
            if (!player.classList.contains('is-mobile-fullscreen')) {
                setPlayerIcon(fullscreenButton, 'fa-expand', '全屏');
            }
        }
    };

    const togglePlay = () => {
        if (video.paused || video.ended) {
            video.play().catch(() => {});
        } else {
            video.pause();
        }
    };

    player.querySelectorAll('[data-player-action="toggle-play"]').forEach(button => {
        button.addEventListener('click', togglePlay);
    });
    video.addEventListener('click', togglePlay);
    video.addEventListener('play', updatePlayState);
    video.addEventListener('pause', updatePlayState);
    video.addEventListener('ended', updatePlayState);
    video.addEventListener('loadedmetadata', updateOrientation);
    video.addEventListener('loadedmetadata', updateProgress);
    video.addEventListener('timeupdate', updateProgress);
    video.addEventListener('durationchange', updateProgress);

    progress.addEventListener('input', () => {
        if (video.duration > 0) {
            video.currentTime = (Number(progress.value) / 100) * video.duration;
        }
    });

    volume.addEventListener('input', () => {
        video.volume = Number(volume.value);
        video.muted = video.volume === 0;
        const muteButton = player.querySelector('[data-player-action="toggle-mute"]');
        setPlayerIcon(muteButton, video.muted ? 'fa-volume-xmark' : 'fa-volume-high', video.muted ? '取消静音' : '静音');
    });

    const muteButton = player.querySelector('[data-player-action="toggle-mute"]');
    muteButton.addEventListener('click', () => {
        video.muted = !video.muted;
        setPlayerIcon(muteButton, video.muted ? 'fa-volume-xmark' : 'fa-volume-high', video.muted ? '取消静音' : '静音');
    });

    player.querySelectorAll('[data-speed]').forEach(button => {
        button.addEventListener('click', () => {
            video.playbackRate = Number(button.dataset.speed);
            speedLabel.textContent = `${video.playbackRate}x`;
            player.querySelectorAll('[data-speed]').forEach(option => option.classList.toggle('is-active', option === button));
            button.closest('[data-player-menu]').classList.remove('is-open');
            button.closest('[data-player-menu]').querySelector('[data-menu-toggle]').setAttribute('aria-expanded', 'false');
        });
    });

    player.querySelectorAll('[data-quality]').forEach(button => {
        button.addEventListener('click', () => {
            // 当前接口只保存原始文件；保留清晰度入口，后续接入转码源时可直接切换 source。
            video.dataset.quality = button.dataset.quality;
            qualityLabel.textContent = button.dataset.quality === 'original' ? '原画' : '自动';
            player.querySelectorAll('[data-quality]').forEach(option => option.classList.toggle('is-active', option === button));
            button.closest('[data-player-menu]').classList.remove('is-open');
            button.closest('[data-player-menu]').querySelector('[data-menu-toggle]').setAttribute('aria-expanded', 'false');
        });
    });

    player.querySelectorAll('[data-menu-toggle]').forEach(button => {
        button.addEventListener('click', event => {
            event.stopPropagation();
            const menu = button.closest('[data-player-menu]');
            player.querySelectorAll('[data-player-menu]').forEach(item => {
                if (item !== menu) {
                    item.classList.remove('is-open');
                    item.querySelector('[data-menu-toggle]').setAttribute('aria-expanded', 'false');
                }
            });
            const isOpen = menu.classList.toggle('is-open');
            button.setAttribute('aria-expanded', String(isOpen));
        });
    });

    const fullscreenButton = player.querySelector('[data-player-action="fullscreen"]');
    const setMobileFullscreenStageSize = () => {
        if (!player.classList.contains('is-mobile-fullscreen')) {
            return;
        }
        stage.style.width = '100vw';
        stage.style.height = '100dvh';
    };

    const enterMobileFullscreen = () => {
        player.classList.add('is-mobile-fullscreen');
        document.body.classList.add('media-player-body-lock');
        setPlayerIcon(fullscreenButton, 'fa-compress', '退出全屏');
        setMobileFullscreenStageSize();
        showControls();
    };

    const exitMobileFullscreen = () => {
        player.classList.remove('is-mobile-fullscreen');
        document.body.classList.remove('media-player-body-lock');
        stage.style.removeProperty('width');
        stage.style.removeProperty('height');
        setPlayerIcon(fullscreenButton, 'fa-expand', '全屏');
    };

    fullscreenButton.addEventListener('click', async () => {
        if (player.classList.contains('is-mobile-fullscreen')) {
            exitMobileFullscreen();
            return;
        }
        if (document.fullscreenElement === player) {
            document.exitFullscreen?.();
            return;
        }

        const isMobile = window.matchMedia('(pointer: coarse)').matches || window.innerWidth <= 640;
        if (isMobile && player.dataset.orientation === 'portrait') {
            enterMobileFullscreen();
            return;
        }

        if (player.requestFullscreen) {
            try {
                await player.requestFullscreen();
                await lockFullscreenOrientation();
            } catch (error) {
                // 全屏被浏览器拒绝时不影响普通播放。
            }
        } else if (video.webkitEnterFullscreen) {
            video.webkitEnterFullscreen();
            await lockFullscreenOrientation();
        }
    });

    window.addEventListener('resize', setMobileFullscreenStageSize);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && player.classList.contains('is-mobile-fullscreen')) {
            exitMobileFullscreen();
        }
    });

    document.addEventListener('fullscreenchange', syncFullscreenOrientation);
    document.addEventListener('webkitfullscreenchange', syncFullscreenOrientation);

    video.volume = 1;
    progress.value = '0';
    updatePlayState();
    updateProgress();
});

document.addEventListener('click', () => {
    document.querySelectorAll('[data-player-menu].is-open').forEach(menu => {
        menu.classList.remove('is-open');
        menu.querySelector('[data-menu-toggle]').setAttribute('aria-expanded', 'false');
    });
});

document.querySelectorAll('.delete-post-form').forEach(form => {
    form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirm || '确定删除这篇帖子吗？')) {
            event.preventDefault();
        }
    });
});

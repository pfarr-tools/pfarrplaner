<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Stories</title>

    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" />

    <style>
        /* Full-viewport container */
        .stories {
            position: relative;
            height: 100vh;
            width: 100%;
            overflow: hidden;
            background: #000;
        }

        /* Horizontal scroller with snap */
        .stories__track {
            height: 100%;
            width: 100%;
            display: flex;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
            touch-action: pan-x;
        }

        /* Hide scrollbar (optional) */
        .stories__track::-webkit-scrollbar { display: none; }
        .stories__track { -ms-overflow-style: none; scrollbar-width: none; }

        /* Each slide is full viewport width */
        .story {
            flex: 0 0 100%;
            height: 100%;
            position: relative;
            scroll-snap-align: start;
            scroll-snap-stop: always;
        }

        /* Image fills slide */
        .story__img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* use 'contain' if you prefer letterboxing */
            display: block;
        }

        /* Top progress / indicator bar (dots + counter) */
        .stories__top {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: .5rem .75rem;
            background: linear-gradient(to bottom, rgba(0,0,0,.55), rgba(0,0,0,0));
            color: #fff;
            pointer-events: none;
            z-index: 10;
        }
        .stories__dots { pointer-events: auto; }
        .stories__dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,.45);
            display: inline-block;
            margin-right: 6px;
        }
        .stories__dot.is-active { background: #fff; }

        /* Bottom overlay */
        .stories__overlay {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            padding: .75rem;
            background-color: #000000cc;
            color: #fff;
            pointer-events: none;
            z-index: 10;
        }

        /* Make buttons clickable */
        .stories__overlay .btn,
        .stories__overlay a,
        .stories__overlay button {
            pointer-events: auto;
        }

        /* Overlay meta text spacing */
        .overlay-meta .title {
            font-weight: 600;
            line-height: 1.15;
        }
        .overlay-meta .description {
            opacity: .8;
            line-height: 1.25;
        }
        .overlay-meta .info {
            line-height: 1.25;
        }
    </style>
</head>

<body class="m-0 p-0">

<div class="stories">

    <!-- Top overlay (dots + counter) -->
    <div class="stories__top d-flex align-items-center justify-content-between">
        <div class="stories__dots" id="storyDots"></div>
        <div class="small" id="storyCounter">1 / 1</div>
    </div>

    <!-- Track -->
    <div class="stories__track" id="storyTrack" aria-label="Stories">
        <?php $index = 0; ?>
        @foreach($events as $event)
            <section class="story" data-index="{{ $index++ }}">
                <img
                    class="story__img"
                    src="{{ $event->service->getImageCutUrl('story') }}"
                    alt="Story"
                    data-title="{{ $event->service->titleText(false) }}"
                    data-description="{{ $event->service->descriptionText() }}"
                    data-start="{{ $event->adStart->isoFormat('dddd, D. MMMM') }}"
                    data-info="{{ $event->start->isoFormat('dddd, D. MMMM') }}, {{ $event->service->timeText() }}, {{ $event->service->locationTextWithCity }}"
                    data-adtext="{{ $event->getAdText('story') }}"
                >
            </section>
        @endforeach
    </div>

    <!-- Bottom overlay (meta + buttons) -->
    <div class="stories__overlay">
        <div class="container-fluid px-0">

            <!-- Meta (current story) -->
            <div class="overlay-meta mb-2">
                <div class="title" id="overlayTitle"></div>
                <div class="small description" id="overlayDescription"></div>
                <div class="small info" id="overlayInfo"></div>
                <div class="small text-white info font-weight-bold" id="overlayStart"></div>
            </div>

            <!-- Buttons -->
            <div class="d-flex align-items-center justify-content-between">
                <div class="btn-group" role="group" aria-label="Story actions">
                    <button class="btn btn-light btn-sm" id="btnShare" type="button">Share</button>
                    <button class="btn btn-light btn-sm" id="btnDownload" type="button">Download</button>
                    <button class="btn btn-light btn-sm" id="btnCopyText" type="button">Text kopieren</button>
                </div>

                <div class="ml-2 d-flex">
                    <button class="btn btn-outline-light btn-sm mr-2" id="btnPrev" type="button">‹</button>
                    <button class="btn btn-outline-light btn-sm" id="btnNext" type="button">›</button>
                </div>
            </div>

            <div style="min-height: 40px;">&nbsp;</div>
        </div>
    </div>
</div>

<script>
    (function () {
        var track = document.getElementById('storyTrack');
        var stories = track.querySelectorAll('.story');
        var dotsEl = document.getElementById('storyDots');
        var counterEl = document.getElementById('storyCounter');

        // Overlay fields
        var overlayTitle = document.getElementById('overlayTitle');
        var overlayDescription = document.getElementById('overlayDescription');
        var overlayInfo = document.getElementById('overlayInfo');
        var overlayStart = document.getElementById('overlayStart');

        // Buttons
        var btnShare = document.getElementById('btnShare');
        var btnDownload = document.getElementById('btnDownload');
        var btnCopyText = document.getElementById('btnCopyText');
        var btnPrev = document.getElementById('btnPrev');
        var btnNext = document.getElementById('btnNext');

        // Build dots
        var dots = [];
        for (var i = 0; i < stories.length; i++) {
            var dot = document.createElement('span');
            dot.className = 'stories__dot' + (i === 0 ? ' is-active' : '');
            dot.setAttribute('role', 'button');
            dot.setAttribute('tabindex', '0');
            dot.dataset.index = i;

            dot.addEventListener('click', function (e) {
                var idx = parseInt(e.currentTarget.dataset.index, 10);
                scrollToIndex(idx);
            });

            dotsEl.appendChild(dot);
            dots.push(dot);
        }

        function clampIndex(idx) {
            return Math.max(0, Math.min(stories.length - 1, idx));
        }

        function getActiveIndex() {
            var w = track.clientWidth || 1;
            return clampIndex(Math.round(track.scrollLeft / w));
        }

        function getStoryImgEl(idx) {
            return stories[idx] ? stories[idx].querySelector('img') : null;
        }

        function getActiveStoryImageUrl(idx) {
            var img = getStoryImgEl(idx);
            return img ? (img.currentSrc || img.src) : null;
        }

        function updateOverlayMeta(idx) {
            var img = getStoryImgEl(idx);
            if (!img) return;

            overlayTitle.textContent = img.dataset.title || '';
            overlayDescription.textContent = img.dataset.description || '';
            overlayInfo.textContent = img.dataset.info || '';

            if (img.dataset.start) {
                overlayStart.textContent = 'Als Story ab ' + img.dataset.start;
            } else {
                overlayStart.textContent = '';
            }
        }

        function setActive(idx) {
            idx = clampIndex(idx);

            for (var i = 0; i < dots.length; i++) {
                dots[i].classList.toggle('is-active', i === idx);
            }
            counterEl.textContent = (idx + 1) + ' / ' + stories.length;

            updateOverlayMeta(idx);
            wireButtons(idx);
        }

        function scrollToIndex(idx) {
            idx = clampIndex(idx);
            var w = track.clientWidth || 1;
            track.scrollTo({ left: idx * w, behavior: 'smooth' });
        }

        // Track scroll -> update active
        var scrollTimer = null;
        track.addEventListener('scroll', function () {
            window.clearTimeout(scrollTimer);
            scrollTimer = window.setTimeout(function () {
                setActive(getActiveIndex());
            }, 80);
        });

        // Prev/Next
        btnPrev.addEventListener('click', function () {
            scrollToIndex(getActiveIndex() - 1);
        });
        btnNext.addEventListener('click', function () {
            scrollToIndex(getActiveIndex() + 1);
        });

        function makeStoryUrl(idx) {
            var u = new URL(window.location.href);
            u.searchParams.set('story', String(idx));
            return u.toString();
        }

        function getSafeFilenameFromUrl(url, fallbackName) {
            try {
                var u = new URL(url, window.location.href);
                var last = (u.pathname.split('/').pop() || '').trim();
                if (!last) return fallbackName;
                last = last.split('?')[0].split('#')[0];
                return last;
            } catch (e) {
                return fallbackName;
            }
        }

        async function tryShareImageLevel2(idx) {
            // Share image file (Web Share API Level 2). Works mainly on Android Chromium.
            if (!navigator.share || !navigator.canShare) return false;

            var imgUrl = getActiveStoryImageUrl(idx);
            if (!imgUrl) return false;

            try {
                // Requires CORS if cross-origin. Same-origin OK.
                var res = await fetch(imgUrl, { mode: 'cors', credentials: 'omit' });
                if (!res.ok) return false;

                var blob = await res.blob();

                var fallbackName = 'story-' + (idx + 1) + '.jpg';
                var filename = getSafeFilenameFromUrl(imgUrl, fallbackName);
                if (filename.indexOf('.') === -1) {
                    filename = filename + (blob.type === 'image/png' ? '.png' : '.jpg');
                }

                var file = new File([blob], filename, { type: blob.type || 'image/jpeg' });

                if (!navigator.canShare({ files: [file] })) return false;

                await navigator.share({
                    files: [file],
                    title: 'Story'
                    // no text, no url
                });

                return true;
            } catch (e) {
                return false;
            }
        }

        async function copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (e) {
                return false;
            }
        }

        function buildCopyTextFromDataset(img) {
            if (!img) return '';

            var parts = [];
            if (img.dataset.title) parts.push(img.dataset.title);
            if (img.dataset.description) parts.push(img.dataset.description);
            if (img.dataset.info) parts.push(img.dataset.info);

            var ad = (img.dataset.adtext || '').trim();

            if (ad) {
                // Ensure exactly one blank line before ad text
                parts.push(''); // creates an extra newline
                parts.push(ad);
            }

            // Join with \n (so we get title\n desc\n info\n\n adtext)
            // If adtext not present: just the available parts with \n
            // Also avoid trailing whitespace-only lines:
            return parts.join('\n').replace(/[ \t]+\n/g, '\n').trim();
        }

        function downloadCurrentImage(idx) {
            var imgUrl = getActiveStoryImageUrl(idx);
            if (!imgUrl) return;

            var fallbackName = 'story-' + (idx + 1) + '.jpg';
            var filename = getSafeFilenameFromUrl(imgUrl, fallbackName);

            var a = document.createElement('a');
            a.href = imgUrl;
            a.download = filename;
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        function wireButtons(idx) {
            btnDownload.onclick = function () {
                downloadCurrentImage(idx);
            };

            btnCopyText.onclick = async function () {
                var img = getStoryImgEl(idx);
                var text = buildCopyTextFromDataset(img);
                if (!text) return;

                var ok = await copyToClipboard(text);
                if (!ok) {
                    window.prompt('Text kopieren:', text);
                } else {
                    var old = btnCopyText.textContent;
                    btnCopyText.textContent = 'Kopiert';
                    setTimeout(function(){ btnCopyText.textContent = old; }, 900);
                }
            };

            btnShare.onclick = async function () {
                // 1) Try Level 2 image share
                var sharedImage = await tryShareImageLevel2(idx);
                if (sharedImage) return;

                // 2) Fallback: classic share (page URL)
                if (navigator.share) {
                    try {
                        await navigator.share({ title: 'Story', url: makeStoryUrl(idx) });
                        return;
                    } catch (e) {}
                }

                // 3) Fallback: copy image URL
                var imgUrl = getActiveStoryImageUrl(idx);
                if (!imgUrl) return;

                var ok = await copyToClipboard(imgUrl);
                if (!ok) {
                    window.prompt('Diese Bild-URL kopieren:', imgUrl);
                } else {
                    var old = btnShare.textContent;
                    btnShare.textContent = 'Kopiert';
                    setTimeout(function(){ btnShare.textContent = old; }, 900);
                }
            };
        }

        // Initialize
        setActive(0);

        // Jump to ?story=
        var initial = new URL(window.location.href).searchParams.get('story');
        if (initial !== null) {
            var idx = clampIndex(parseInt(initial, 10) || 0);
            requestAnimationFrame(function () {
                track.scrollLeft = idx * (track.clientWidth || 1);
                setActive(idx);
            });
        }
    })();
</script>

</body>
</html>

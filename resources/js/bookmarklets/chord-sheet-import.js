/*
 * "Send to ICD" bookmarklet: runs on a chord sheet page (e.g. Cifra Club) opened in the browser,
 * reads the chord sheet <pre>, title, artist, key and YouTube video, and opens the ICD "new song"
 * form with them in the URL fragment (never sent to any server).
 *
 * Only block comments and explicit semicolons: the code is turned into a single-line javascript: URL.
 * __ICD_URL__ and __ICD_VERSION__ are replaced when the bookmarklet page is rendered; the version lets the form
 * warn when an outdated bookmark is used.
 */
(function () {
    var ICD_URL = '__ICD_URL__';
    /* Chord sheet parts, in page order. Cifra Club keeps the sheet inside <article data-chord-container>
       (possibly several), split in <pre> blocks (some with lyrics only), so every <pre> of every container is
       used; a container without <pre> is read as a whole. Other pages: every <pre> with chords, or the longest. */
    var containers = Array.prototype.slice.call(document.querySelectorAll('[data-chord-container]'));
    var allPres = Array.prototype.slice.call(document.querySelectorAll('pre'));
    var pres = [];
    if (containers.length) {
        /* Every container (there may be one per part), without repeating nested elements. */
        containers.forEach(function (container) {
            var inner = Array.prototype.slice.call(container.querySelectorAll('pre'));
            (inner.length ? inner : [container]).forEach(function (element) {
                if (pres.indexOf(element) === -1 && !pres.some(function (added) { return added.contains(element) || element.contains(added); })) {
                    pres.push(element);
                }
            });
        });
        pres.sort(function (a, b) {
            return a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
        });
    } else {
        pres = allPres.filter(function (element) {
            return element.hasAttribute('data-chord-content') || element.querySelector('[data-chord-name], b');
        });
    }
    if (!pres.length && allPres.length) {
        pres = [allPres.reduce(function (longest, element) {
            return element.textContent.length > longest.textContent.length ? element : longest;
        })];
    }

    if (!pres.length) {
        alert('ICD: nenhuma cifra (<pre>) encontrada nesta página.');
        return;
    }

    function escapeHtml(text) {
        return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* Compact copy of the <pre>: plain text plus <b> chord tags, without the site's other markup. */
    function walk(node) {
        var out = '';
        node.childNodes.forEach(function (child) {
            if (child.nodeType === 3) {
                out += escapeHtml(child.nodeValue);
                return;
            }
            if (child.nodeType !== 1) {
                return;
            }
            var tag = child.tagName.toLowerCase();
            if (tag === 'br') {
                out += '\n';
                return;
            }
            if (tag === 'script' || tag === 'style') {
                return;
            }
            var chord = child.getAttribute('data-chord-name');
            if (chord) {
                out += '<b data-chord-name="' + escapeHtml(chord) + '">' + escapeHtml(child.textContent) + '</b>';
                return;
            }
            if (tag === 'b' || tag === 'strong') {
                out += '<b>' + escapeHtml(child.textContent) + '</b>';
                return;
            }
            out += walk(child);
            if ((tag === 'div' || tag === 'p') && !/\n$/.test(out)) {
                out += '\n';
            }
        });
        return out;
    }

    function text(selector) {
        var element = document.querySelector(selector);
        return element ? element.textContent.replace(/\s+/g, ' ').trim() : '';
    }

    /* Title and artist from the page title "Song - Artist - Cifra Club"; page headings as a fallback. */
    var titleParts = document.title.split(' - ');
    var title = titleParts.length > 1 ? titleParts[0].trim() : text('h1');
    var artist = titleParts.length > 1 ? titleParts[1].trim() : (text('h2 a') || text('h2'));

    /* Key: first short element reading "Tom: X" outside the chord sheet (e.g. "Tom: D (forma dos acordes no tom de C)"). */
    var key = '';
    var candidates = document.querySelectorAll('body *');
    for (var i = 0; i < candidates.length && !key; i++) {
        var element = candidates[i];
        if (pres.some(function (sheet) { return sheet.contains(element); }) || element.children.length > 4) {
            continue;
        }
        var content = element.textContent.replace(/\s+/g, ' ').trim();
        var match = content.length < 80 && /^tom\s*:?\s*([A-G][#b]?m?)(?![\w#])/i.exec(content);
        if (match) {
            key = match[1].charAt(0).toUpperCase() + match[1].slice(1);
        }
    }

    /* YouTube video: the player opened on the page (always the current song); otherwise the "youtubeID" of the
       song data embedded in the page, only when the song description next to it matches the current page, since
       after navigating inside the site that data may still belong to the previous song. */
    var youtube = '';
    var player = document.querySelector('iframe[src*="youtube.com/embed/"], iframe[src*="youtube-nocookie.com/embed/"]');
    var videoMatch = player ? /\/embed\/([\w-]{11})/.exec(player.getAttribute('src')) : null;
    if (videoMatch) {
        youtube = 'https://www.youtube.com/watch?v=' + videoMatch[1];
    } else {
        var pageDescription = (document.querySelector('meta[name="description"]') || {}).content || '';
        var html = document.documentElement.innerHTML;
        var idPattern = /\\?"youtubeID\\?"\s*:\s*\\?"([\w-]{11})/g;
        var found;
        while (!youtube && (found = idPattern.exec(html))) {
            var before = html.slice(Math.max(0, found.index - 1500), found.index);
            var description = /^description\\?"\s*:\s*\\?"([^"\\]{8,40})/.exec(before.slice(before.lastIndexOf('description')));
            if (description && pageDescription.indexOf(description[1].trim()) !== -1) {
                youtube = 'https://www.youtube.com/watch?v=' + found[1];
            }
        }
    }

    var data = {
        version: '__ICD_VERSION__',
        title: title,
        artist: artist,
        key: key,
        youtube: youtube,
        source: location.origin + location.pathname,
        sheet: '<pre>' + pres.map(walk).join('\n') + '</pre>',
        /* Chords found on the page, so the form can warn when the interpreted sheet has fewer. */
        chordCount: pres.reduce(function (total, element) {
            return total + element.querySelectorAll('[data-chord-name]').length;
        }, 0)
    };

    window.open(ICD_URL + '/songs/create#icd-import=' + encodeURIComponent(JSON.stringify(data)), '_blank');
})();

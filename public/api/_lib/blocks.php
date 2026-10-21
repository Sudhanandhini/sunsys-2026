<?php
// Page-builder content for blog posts: rows → columns → widgets, stored as JSON in posts.blocks.
// The JSON is cleaned on save and again on render, so only these structures and inline tags can reach the page:
//   heading (h2–h4) · paragraph · list · quote   inline: bold, italic, underline, colour, links, line breaks
//   image (uploaded or https) · button link
declare(strict_types=1);

const BLOCK_MAX_COLS = 4;

/** Links: http(s), mailto, tel, site-relative and #anchors only. */
function block_url(string $u): ?string
{
    $u = trim($u);
    return preg_match('#^(https?://|mailto:|tel:|/(?!/)|\#)#i', $u) ? $u : null;
}

function block_link(string $href, string $inner, bool $newTab = false): string
{
    $ext = $newTab || preg_match('#^https?://#i', $href) ? ' target="_blank" rel="noopener"' : '';
    return '<a href="' . e($href) . '"' . $ext . '>' . $inner . '</a>';
}

/** A CSS colour from the editor (<font color> or style="color:…"), or null. */
function block_color(DOMElement $el): ?string
{
    $c = $el->getAttribute('color') ?: (preg_match('/(?:^|;)\s*color\s*:\s*([^;]+)/i', $el->getAttribute('style'), $m) ? trim($m[1]) : '');
    return preg_match('/^(#[0-9a-f]{3,8}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*[\d.]+\s*)?\))$/i', $c) ? $c : null;
}

/** Inline content of a node with only the allowed tags kept. Block elements become line breaks. */
function block_inline_node(DOMNode $node): string
{
    $out = '';
    foreach ($node->childNodes as $n) {
        if ($n instanceof DOMText) { $out .= e($n->nodeValue); continue; }
        if (!$n instanceof DOMElement) continue;
        $tag = strtolower($n->tagName);
        if (in_array($tag, ['script', 'style', 'template', 'iframe', 'object', 'svg', 'math', 'noscript', 'textarea', 'select'], true)) continue;
        $inner = block_inline_node($n);
        $html = match ($tag) {
            'b', 'strong' => $inner === '' ? '' : "<strong>$inner</strong>",
            'i', 'em' => $inner === '' ? '' : "<em>$inner</em>",
            'u' => $inner === '' ? '' : "<u>$inner</u>",
            'br' => '<br>',
            'a' => ($href = block_url($n->getAttribute('href'))) && $inner !== '' ? block_link($href, $inner, $n->getAttribute('target') === '_blank') : $inner,
            'div', 'p', 'li' => ($out !== '' && $inner !== '' ? '<br>' : '') . $inner,
            default => $inner, // span, font and anything else: unwrapped (colour kept below)
        };
        if ($html !== '' && $tag !== 'br' && ($color = block_color($n))) $html = '<span style="color:' . e($color) . '">' . $html . '</span>';
        $out .= $html;
    }
    return $out;
}

function block_dom(string $html): DOMElement
{
    $doc = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    return $doc->getElementsByTagName('div')->item(0);
}

/** Clean inline rich text from the editor. */
function block_inline(string $html): string
{
    if (trim($html) === '') return '';
    $s = block_inline_node(block_dom(mb_substr($html, 0, 50000)));
    return trim(preg_replace('#^(<br>)+|(<br>)+$#', '', $s));
}

/** Rich text split into paragraphs: each top-level <p>/<div> the editor made is its own paragraph. */
function block_paragraphs(string $html): array
{
    $root = block_dom(mb_substr($html, 0, 50000));
    $paras = [];
    $run = '';
    $flush = function () use (&$run, &$paras) {
        $t = trim(preg_replace('#^(<br>)+|(<br>)+$#', '', $run));
        if (trim(strip_tags($t)) !== '') $paras[] = $t;
        $run = '';
    };
    foreach (iterator_to_array($root->childNodes) as $n) {
        if ($n instanceof DOMElement && in_array(strtolower($n->tagName), ['p', 'div'], true)) {
            $flush();
            $run = block_inline_node($n);
            $flush();
        } else {
            $wrap = $root->ownerDocument->createElement('span');
            $wrap->appendChild($n->cloneNode(true));
            $run .= block_inline_node($wrap);
        }
    }
    $flush();
    return $paras;
}

/** Validate a decoded blocks document; anything unknown is dropped. */
function blocks_clean($doc): array
{
    $rows = [];
    foreach (array_slice(is_array($doc['rows'] ?? null) ? $doc['rows'] : [], 0, 100) as $row) {
        $cols = [];
        foreach (array_slice(is_array($row['cols'] ?? null) ? $row['cols'] : [], 0, BLOCK_MAX_COLS) as $col) {
            $widgets = [];
            foreach (array_slice(is_array($col['widgets'] ?? null) ? $col['widgets'] : [], 0, 300) as $w) {
                if ($w = block_widget_clean(is_array($w) ? $w : [])) $widgets[] = $w;
            }
            $cols[] = ['widgets' => $widgets];
        }
        if ($cols) $rows[] = ['cols' => $cols];
    }
    return ['v' => 1, 'rows' => $rows];
}

function block_widget_clean(array $w): ?array
{
    $str = fn ($k, $max = 500) => mb_substr(trim((string) ($w[$k] ?? '')), 0, $max);
    switch ($w['type'] ?? '') {
        case 'heading':
            $html = block_inline((string) ($w['html'] ?? ''));
            return $html === '' ? null : ['type' => 'heading', 'level' => in_array((int) ($w['level'] ?? 2), [2, 3, 4], true) ? (int) $w['level'] : 2, 'html' => $html];
        case 'paragraph':
        case 'quote':
            $paras = block_paragraphs((string) ($w['html'] ?? ''));
            return $paras ? ['type' => $w['type'], 'html' => '<p>' . implode('</p><p>', $paras) . '</p>'] : null;
        case 'list':
            $items = array_values(array_filter(array_map(fn ($i) => block_inline((string) $i), array_slice((array) ($w['items'] ?? []), 0, 200)), fn ($i) => trim(strip_tags($i)) !== ''));
            return $items ? ['type' => 'list', 'ordered' => !empty($w['ordered']), 'items' => $items] : null;
        case 'image':
            $src = $str('src', 500);
            if (!preg_match('#^(/uploads/blog/[\w.-]+|https://\S+)$#', $src)) return null;
            return ['type' => 'image', 'src' => $src, 'alt' => $str('alt', 300), 'caption' => $str('caption', 300), 'href' => block_url($str('href')) ?? ''];
        case 'button':
            $href = block_url($str('href'));
            if (!$href || $str('text', 120) === '') return null;
            return ['type' => 'button', 'text' => $str('text', 120), 'href' => $href, 'style' => ($w['style'] ?? '') === 'outline' ? 'outline' : 'solid', 'newTab' => !empty($w['newTab'])];
    }
    return null;
}

/** Article HTML for the website. */
function blocks_render(array $doc): string
{
    $out = [];
    foreach (blocks_clean($doc)['rows'] as $row) {
        $cols = array_values(array_filter($row['cols'], fn ($c) => $c['widgets']));
        if (!$cols) continue;
        $html = array_map(fn ($c) => implode("\n", array_map('block_widget_render', $c['widgets'])), $cols);
        $out[] = count($cols) === 1
            ? $html[0]
            : '<div class="pb-row pb-cols-' . count($cols) . '">' . implode('', array_map(fn ($h) => '<div class="pb-col">' . $h . '</div>', $html)) . '</div>';
    }
    return implode("\n", $out);
}

function block_widget_render(array $w): string
{
    switch ($w['type']) {
        case 'heading': return "<h{$w['level']}>{$w['html']}</h{$w['level']}>";
        case 'paragraph': return $w['html'];
        case 'quote': return '<blockquote>' . $w['html'] . '</blockquote>';
        case 'list':
            $t = $w['ordered'] ? 'ol' : 'ul';
            return "<$t>" . implode('', array_map(fn ($i) => "<li>$i</li>", $w['items'])) . "</$t>";
        case 'image':
            $img = '<img src="' . e($w['src']) . '" alt="' . e($w['alt']) . '" loading="lazy" decoding="async">';
            if ($w['href']) $img = block_link($w['href'], $img);
            return '<figure class="pb-img">' . $img . ($w['caption'] !== '' ? '<figcaption>' . e($w['caption']) . '</figcaption>' : '') . '</figure>';
        case 'button':
            $ext = $w['newTab'] ? ' target="_blank" rel="noopener"' : '';
            return '<p class="pb-btn-wrap"><a class="pb-btn pb-btn-' . $w['style'] . '" href="' . e($w['href']) . '"' . $ext . '>' . e($w['text']) . '</a></p>';
    }
    return '';
}

/** Plain text of the post, for search (stored in posts.body) and the automatic summary. */
function blocks_text(array $doc, bool $paragraphsOnly = false): string
{
    $parts = [];
    foreach (blocks_clean($doc)['rows'] as $row) foreach ($row['cols'] as $col) foreach ($col['widgets'] as $w) {
        if ($paragraphsOnly && $w['type'] !== 'paragraph') continue;
        $html = match ($w['type']) {
            'heading', 'paragraph', 'quote' => $w['html'],
            'list' => implode("\n", $w['items']),
            'image' => $w['alt'] . ' ' . $w['caption'],
            'button' => $w['text'],
            default => '',
        };
        $parts[] = trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p><p>'], ["\n", "\n\n"], $html)), ENT_QUOTES, 'UTF-8'));
    }
    return trim(implode("\n\n", array_filter($parts, 'strlen')));
}

/** Uploaded images used in the post, to clean up files that were removed. */
function blocks_images(array $doc): array
{
    $srcs = [];
    foreach (blocks_clean($doc)['rows'] as $row) foreach ($row['cols'] as $col) foreach ($col['widgets'] as $w) {
        if ($w['type'] === 'image' && str_starts_with($w['src'], '/uploads/blog/')) $srcs[] = $w['src'];
    }
    return $srcs;
}

/** Convert an older Markdown post (rendered to HTML) into one row / one column of widgets. */
function blocks_from_html(string $html): array
{
    $widgets = [];
    foreach (block_dom($html)->childNodes as $n) {
        if (!$n instanceof DOMElement) continue;
        $tag = strtolower($n->tagName);
        $inner = block_inline_node($n);
        if (in_array($tag, ['h2', 'h3', 'h4'], true)) $widgets[] = ['type' => 'heading', 'level' => (int) $tag[1], 'html' => $inner];
        elseif ($tag === 'p') $widgets[] = ['type' => 'paragraph', 'html' => "<p>$inner</p>"];
        elseif ($tag === 'blockquote') $widgets[] = ['type' => 'quote', 'html' => "<p>$inner</p>"];
        elseif ($tag === 'ul' || $tag === 'ol') {
            $items = [];
            foreach ($n->childNodes as $li) if ($li instanceof DOMElement) $items[] = block_inline_node($li);
            $widgets[] = ['type' => 'list', 'ordered' => $tag === 'ol', 'items' => $items];
        }
    }
    return blocks_clean(['rows' => [['cols' => [['widgets' => $widgets]]]]]);
}

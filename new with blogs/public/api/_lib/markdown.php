<?php
// Small, safe Markdown subset for blog posts. All text is escaped first; only these constructs become HTML:
// ## / ### headings, paragraphs, - or 1. lists, > quotes, **bold**, *italic*, `code`, [text](https://link)
declare(strict_types=1);

function md_inline(string $s): string
{
    $s = htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $s = preg_replace('/`([^`]+)`/', '<code>$1</code>', $s);
    $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);
    $s = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $s);
    // links: only http(s), mailto and site-relative
    $s = preg_replace_callback('/\[([^\]]+)\]\(((?:https?:\/\/|mailto:|\/)[^\s)]+)\)/', function ($m) {
        $ext = str_starts_with($m[2], 'http') ? ' target="_blank" rel="noopener"' : '';
        return '<a href="' . $m[2] . '"' . $ext . '>' . $m[1] . '</a>';
    }, $s);
    return $s;
}

function markdown(string $md): string
{
    $lines = preg_split('/\r\n|\r|\n/', $md);
    $out = [];
    $para = [];
    $list = null; // 'ul' | 'ol'
    $flushPara = function () use (&$para, &$out) {
        if ($para) { $out[] = '<p>' . md_inline(implode(' ', $para)) . '</p>'; $para = []; }
    };
    $closeList = function () use (&$list, &$out) {
        if ($list) { $out[] = "</$list>"; $list = null; }
    };
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '') { $flushPara(); $closeList(); continue; }
        if (preg_match('/^(#{2,3})\s+(.*)$/', $t, $m)) {
            $flushPara(); $closeList();
            $h = strlen($m[1]);
            $out[] = "<h$h>" . md_inline($m[2]) . "</h$h>";
        } elseif (preg_match('/^[-*]\s+(.*)$/', $t, $m) || preg_match('/^\d+\.\s+(.*)$/', $t, $m2)) {
            $flushPara();
            $type = isset($m[1]) && $m ? 'ul' : 'ol';
            $text = $m[1] ?? $m2[1];
            if ($list !== $type) { $closeList(); $out[] = "<$type>"; $list = $type; }
            $out[] = '<li>' . md_inline($text) . '</li>';
        } elseif (preg_match('/^>\s?(.*)$/', $t, $m)) {
            $flushPara(); $closeList();
            $out[] = '<blockquote>' . md_inline($m[1]) . '</blockquote>';
        } else {
            $closeList();
            $para[] = $t;
        }
    }
    $flushPara(); $closeList();
    return implode("\n", $out);
}

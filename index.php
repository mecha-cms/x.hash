<?php namespace x\hash;

function page__content($content) {
    if (!$content || !\is_string($content)) {
        return $content;
    }
    if (false === \strpos($content, '#')) {
        return $content;
    }
    $out = "";
    $parts = \preg_split('/(<!--[\s\S]*?-->|' . (static function ($parts) {
        foreach ($parts as &$part) {
            $part = '<' . \x($part) . '(?>\s(?>"[^"]*"|\'[^\']*\'|[^\/>])*)?>[\s\S]*?<\/' . \x($part) . '>';
        }
        unset($part);
        return \implode('|', $parts);
    })([
        'pre',
        'code', // Must come after `pre`
        'kbd',
        'script',
        'style',
        'textarea'
    ]) . '|<[^>]+>)/i', $content, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY);
    $key = \State::get('x.hash.key') ?? 'hash';
    foreach ($parts as $v) {
        if (0 === \strpos($v, '<') && '>' === \substr($v, -1)) {
            $out .= $v; // Is a HTML tag
        } else {
            $out .= false !== \strpos($v, '#') ? \preg_replace_callback('/(?<=\W|^)#[a-z\d]+(-[a-z\d]+)*/i', static function ($m) use ($key) {
                if (\is_file($file = \LOT . \D . 'tag' . \D . \substr($m[0], 1) . '.page')) {
                    $tag = new \Tag($file);
                    if ('hash' === $key) {
                        return '<a href="' . $tag->link . '" rel="tag" title="' . ($tag->title ?? '#' . $tag->name) . '">#' . $tag->name . '</a>';
                    }
                    if ('title' === $key) {
                        return '<a href="' . $tag->link . '" rel="tag" title="#' . $tag->name . '">' . $tag->title . '</a>';
                    }
                    if (\is_callable($key)) {
                        // Prioritize `$key` as a property name over as a function name. If `$key` is a function name in
                        // the form of a string, make sure that `$tag->{$key}` is `null` before treating `$key` as a
                        // function name to be called later
                        if (\is_string($key) && false === \strpos($key, "\\") && null !== ($v = $tag->{$key})) {
                            return '<a href="' . $tag->link . '" title="#' . $tag->name . '">' . $v . '</a>';
                        }
                        return \fire($key, [$m[0]], $tag);
                    }
                    return '<a href="' . $tag->link . '" title="#' . $tag->name . '">' . ($tag->{$key} ?? '#' . $tag->name) . '</a>';
                }
                return $m[0];
            }, $v) : $v; // Is a plain text
        }
    }
    return $out;
}

function page__description($description) {
    return \fire(__NAMESPACE__ . "\\page__content", [$description], $this);
}

\Hook::set('page.content', __NAMESPACE__ . "\\page__content", 2.1);
\Hook::set('page.description', __NAMESPACE__ . "\\page__description", 2.1);
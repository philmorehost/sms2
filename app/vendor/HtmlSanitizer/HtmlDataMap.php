<?php

declare(strict_types=1);

namespace MirazMac\HtmlSanitizer;

use function \in_array;

final class HtmlDataMap
{
    const BOOLEAN_ATTRIBUTES = [
        'allowfullscreen', 'allowpaymentrequest', 'async', 'autofocus', 'autoplay',
        'checked', 'controls', 'default', 'disabled', 'formnovalidate', 'hidden',
        'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nomodule', 'novalidate',
        'open', 'playsinline', 'readonly', 'required', 'reversed', 'selected', 'truespeed',
        'download'
    ];

    const URL_ATTRIBUTES = [
        'href', 'background', 'cite', 'action', 'profile', 'longdesc', 'classid',
        'codebase', 'data', 'usemap', 'formaction', 'icon', 'src', 'manifest',
        'formaction', 'poster', 'srcset', 'archive'
    ];

    const MULTI_URL_ATTRIBUTES = ['srcset'];

    public static function isBooleanAttribute(string $attrName)
    {
        return in_array($attrName, static::BOOLEAN_ATTRIBUTES);
    }

    public static function isUrlAttribute(string $attrName)
    {
        return in_array($attrName, static::URL_ATTRIBUTES);
    }
}

<?php

declare(strict_types=1);

namespace MirazMac\HtmlSanitizer;

use function \chr;
use function \html_entity_decode;
use function \htmlspecialchars;
use function \libxml_clear_errors;
use function \libxml_disable_entity_loader;
use function \libxml_use_internal_errors;
use function \mb_strlen;
use function \mb_strpos;
use function \mb_strtolower;
use function \mb_substr;
use function \parse_url;
use function \preg_match;
use function \range;
use function \str_replace;
use function \trim;
use function \version_compare;

class Sanitizer
{
    protected $whitelist;

    public function __construct(Whitelist $whitelist)
    {
        $this->whitelist = $whitelist;
    }

    public function sanitize(string $html) : string
    {
        if (!$this->isValidUtf8($html)) {
            throw new \InvalidArgumentException("Provided HTML must be valid utf-8");
        }

        $html = str_replace(chr(0), '', $html);

        if (mb_strlen($html) < 1) {
            return '';
        }

        $previousState = libxml_use_internal_errors(true);
        libxml_clear_errors();

        if (\PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader(true);
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->strictErrorChecking = false;
        $dom->validateOnParse = false;
        $dom->substituteEntities = false;
        $dom->resolveExternals  = false;
        $dom->recover = true;
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace  = false;
        $dom->encoding = 'UTF-8';

        $dom->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta charset="UTF-8">' . $html,
            \LIBXML_NOERROR | \LIBXML_NOWARNING | \LIBXML_HTML_NODEFDTD
        );

        $dom->encoding = 'UTF-8';
        $html = trim($dom->saveHTML($this->doSanitize($dom)));
        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        return $html;
    }

    protected function doSanitize($html)
    {
        if ($html->hasChildNodes()) {
            foreach (range($html->childNodes->length - 1, 0) as $i) {
                $this->doSanitize($html->childNodes->item($i));
            }
        }

        if (!$this->whitelist->isTagAllowed($html->nodeName)) {
            $fragment = $html->ownerDocument->createDocumentFragment();
            if (!empty($html->childNodes)) {
                while ($html->childNodes->length > 0) {
                    $fragment->appendChild($html->childNodes->item(0));
                }
            }
            return $html->parentNode->replaceChild($fragment, $html);
        }

        if (!$html->hasAttributes()) {
            return $html;
        }

        for ($i = $html->attributes->length; --$i >= 0;) {
            $name = $html->attributes->item($i)->name;
            $value = $html->attributes->item($i)->value;

            if (!$this->whitelist->isAttributeAllowed($html->nodeName, $name)) {
                $html->removeAttribute($name);
                continue;
            }

            if (!$this->whitelist->isValueAllowed($html->nodeName, $name, $value)) {
                $html->removeAttribute($name);
                continue;
            }

            if (HtmlDataMap::isBooleanAttribute($name) || $this->whitelist->isBooleanAttribute($name)) {
                if ($name === $value || mb_strlen($value) === 0) {
                    continue;
                }
                $value = "";
            }

            if (HtmlDataMap::isUrlAttribute($name) || $this->whitelist->isUrlAttribute($name)) {
                $value = $this->filterURL(
                    $html->nodeName,
                    $value
                );
            }

            $html->setAttribute(
                $name,
                $this->escapeAttribute($value)
            );
        }
        return $html;
    }

    public function getWhitelist() : Whitelist
    {
        return $this->whitelist;
    }

    protected function filterURL(string $element, $value) : string
    {
        $host = parse_url($value, PHP_URL_HOST);
        if (empty($host)) {
            return $this->stripDangerousProtocols($value);
        }
        if (!$this->whitelist->isHostAllowed($element, $host)) {
            return '';
        }
        return $this->stripDangerousProtocols($value);
    }

    public function escapeAttribute(string $string) : string
    {
        $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8', true);
    }

    protected function stripDangerousProtocols($uri) : string
    {
        do {
            $before = $uri;
            $colonpos = mb_strpos($uri, ':');
            if ($colonpos > 0) {
                $protocol = mb_substr($uri, 0, $colonpos);
                if (preg_match('![/?#]!', $protocol)) {
                    break;
                }
                if (!$this->whitelist->isProtocolAllowed(mb_strtolower($protocol))) {
                    $uri = mb_substr($uri, $colonpos + 1);
                }
            }
        } while ($before != $uri);
        return $uri;
    }

    protected function isValidUtf8(string $string): bool
    {
        return '' === $string || 1 === preg_match('/^./us', $string);
    }
}

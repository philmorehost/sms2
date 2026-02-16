<?php

declare(strict_types=1);

namespace MirazMac\HtmlSanitizer;

use function \array_merge;
use function \array_reverse;
use function \explode;
use function \in_array;
use function \is_array;

class Whitelist
{
    protected $tags = [];
    protected $protocols = [];
    protected $treatAsURL = [];
    protected $treatAsBoolean = [];
    protected $values = [];
    private $requiredTags = ['#document', '#text'];

    public function __construct(array $tags = [], array $protocols = [])
    {
        $this->setTags($tags);
        $this->setProtocols($protocols);
    }

    public function allowTag(string $tagName, array $attributes = []) : Whitelist
    {
        if ($this->isRequiredTag($tagName)) {
            throw new \InvalidArgumentException("Unable to overwrite required tag: {$tagName}");
        }
        $this->tags[$tagName]['allowed_hosts'] = [];
        foreach ($attributes as $attr) {
            $this->tags[$tagName]['attributes'][$attr] = true;
        }
        return $this;
    }

    public function removeTag($tagName) : Whitelist
    {
        foreach ((array) $tagName as $tag) {
            unset($this->tags[$tag]);
        }
        return $this;
    }

    public function allowAttribute(string $tagName, $attributes) : Whitelist
    {
        if (!$this->isTagAllowed($tagName)) {
            throw new \LogicException("Failed to allow attribute(s) on tag `{$tagName}`, because the tag itself isn't allowed.");
        }
        foreach ((array) $attributes as $attr) {
            $this->tags[$tagName]['attributes'][$attr] = true;
        }
        return $this;
    }

    public function removeAttribute(string $tagName, $attributes) : Whitelist
    {
        if (!$this->isTagAllowed($tagName)) {
            throw new \LogicException("Failed to remove attribute(s) on tag `{$tagName}`, because the tag itself isn't allowed.");
        }
        foreach ((array) $attributes as $attr) {
            unset($this->tags[$tagName]['attributes'][$attr]);
        }
        return $this;
    }

    public function setAllowedValues(string $tagName, string $attribute, array $values)
    {
        if (!$this->isTagAllowed($tagName)) {
            throw new \LogicException("Failed to allow values on attribute `{$attribute}` on tag `{$tagName}`, because the tag itself isn't allowed.");
        }
        $this->values[$tagName][$attribute] = $values;
        return $this;
    }

    public function addProtocol($protocols) : Whitelist
    {
        foreach ((array) $protocols as $protocol) {
            $this->protocols[$protocol] = true;
        }
        return $this;
    }

    public function removeProtocol($protocols) : Whitelist
    {
        foreach ((array) $protocols as $protocol) {
            unset($this->protocols[$protocol]);
        }
        return $this;
    }

    public function setAllowedHosts(string $tagName, array $hosts, bool $merge = false) : Whitelist
    {
        if (!$this->isTagAllowed($tagName)) {
            throw new \LogicException("Failed to allow host(s) on tag `{$tagName}`, because the tag itself isn't allowed.");
        }
        if (!$merge) {
            $this->tags[$tagName]['allowed_hosts'] = $hosts;
            return $this;
        }
        $this->tags[$tagName]['allowed_hosts'] = array_merge($this->tags[$tagName]['allowed_hosts'], $hosts);
        return $this;
    }

    public function setProtocols(array $protocols, bool $merge = false) : Whitelist
    {
        $formattedProtocols = [];
        foreach ($protocols as $protocol) {
            $formattedProtocols[$protocol] = true;
        }
        if (!$merge) {
            $this->protocols = $formattedProtocols;
            return $this;
        }
        $this->protocols = array_merge($this->protocols, $formattedProtocols);
        return $this;
    }

    public function setTags(array $tags, $merge = false) : Whitelist
    {
        $formattedTags = [];
        foreach ($tags as $tag => $attributes) {
            $formattedTags[$tag]['allowed_hosts'] = [];
            $formattedTags[$tag]['attributes'] = [];
            if (is_array($attributes)) {
                foreach ($attributes as $attr) {
                    $formattedTags[$tag]['attributes'][$attr] = true;
                }
            }
        }
        if (!$merge) {
            $this->tags = $formattedTags;
            return $this;
        }
        $this->tags = array_merge($this->tags, $formattedTags);
        return $this;
    }

    public function treatAttributesAsUrl(array $attributes) : Whitelist
    {
        $this->treatAsURL = $attributes;
        return $this;
    }

    public function treatAttributesAsBoolean(array $attributes) : Whitelist
    {
        $this->treatAsBoolean = $attributes;
        return $this;
    }

    public function getAllowedTags() : array
    {
        return $this->tags;
    }

    public function getAllowedAttributes(string $tagName) : array
    {
        if ($this->isTagAllowed($tagName)) {
            return $this->tags[$tagName]['attributes'];
        }
        return [];
    }

    public function getAllowedHosts(string $tagName) : array
    {
        if (empty($this->tags[$tagName]['allowed_hosts'])) {
            return [];
        }
        return $this->tags[$tagName]['allowed_hosts'];
    }

    public function isUrlAttribute(string $attribute) : bool
    {
        return in_array($attribute, $this->treatAsURL);
    }

    public function isBooleanAttribute(string $attribute) : bool
    {
        return in_array($attribute, $this->treatAsBoolean);
    }

    public function isRequiredTag(string $tagName) : bool
    {
        return in_array($tagName, $this->requiredTags);
    }

    public function isTagAllowed(string $tagName) : bool
    {
        if ($this->isRequiredTag($tagName)) {
            return true;
        }
        return isset($this->tags[$tagName]);
    }

    public function isProtocolAllowed(string $protocol) : bool
    {
        return isset($this->protocols[$protocol]);
    }

    public function isAttributeAllowed(string $tagName, string $attribute) : bool
    {
        if (!$this->isTagAllowed($tagName)) {
            return false;
        }
        return isset($this->tags[$tagName]['attributes'][$attribute]);
    }

    public function isHostAllowed(string $tagName, string $host) : bool
    {
        if (!$this->isTagAllowed($tagName)) {
            return false;
        }
        if (empty($this->tags[$tagName]['allowed_hosts'])) {
            return true;
        }
        $parts = array_reverse(explode('.', $host));
        foreach ($this->tags[$tagName]['allowed_hosts'] as $allowedHost) {
            if ($this->matchAllowedHostParts($parts, array_reverse(explode('.', $allowedHost)))) {
                return true;
            }
        }
        return false;
    }

    public function isValueAllowed(string $tagName, string $attribute, $value) : bool
    {
        if (!isset($this->values[$tagName][$attribute])) {
            return true;
        }
        return in_array($value, $this->values[$tagName][$attribute]);
    }

    private function matchAllowedHostParts(array $uriParts, array $trustedParts): bool
    {
        foreach ($trustedParts as $key => $trustedPart) {
            if ($uriParts[$key] !== $trustedPart) {
                return false;
            }
        }
        return true;
    }
}

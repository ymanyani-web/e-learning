<?php

declare(strict_types=1);

namespace App\Core;

use DOMDocument;
use DOMElement;
use DOMNode;

class ContentSanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'blockquote', 'img'];

    public static function sanitize(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->documentElement;
        self::cleanChildren($root);
        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private static function cleanChildren(DOMNode $node): void
    {
        for ($index = $node->childNodes->length - 1; $index >= 0; --$index) {
            $child = $node->childNodes->item($index);
            if ($child instanceof DOMElement) {
                if (!in_array(strtolower($child->tagName), self::ALLOWED_TAGS, true)) {
                    while ($child->firstChild !== null) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }
                if (strtolower($child->tagName) === 'img') {
                    $source = $child->getAttribute('src');
                    $alt = $child->getAttribute('alt');
                    if (preg_match('#(?:^|/)uploads/course-media/[a-f0-9]{32}\.(?:jpg|png|webp|gif)$#', $source) !== 1) {
                        $node->removeChild($child);
                        continue;
                    }
                    while ($child->attributes->length > 0) {
                        $child->removeAttributeNode($child->attributes->item(0));
                    }
                    $child->setAttribute('src', $source);
                    if ($alt !== '') {
                        $child->setAttribute('alt', $alt);
                    }
                } else {
                    while ($child->attributes->length > 0) {
                        $child->removeAttributeNode($child->attributes->item(0));
                    }
                }
            }
            self::cleanChildren($child);
        }
    }
}

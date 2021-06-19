<?php


namespace models;

class Html
{
    static function startElement($tagName, $className, $options = []): string
    {
        $attrs = [];

        foreach ($options as $optionKey => $optionValue) {
            if (!count($attrs)) $attrs[] = ' ';

            $attrs[] = $optionKey . '=' . $optionValue;
        }

        return '<' . $tagName . ' class="' . implode(' ', $className) . '"' . implode(' ', $attrs) . '>';
    }

    static function endElement($tagName): string
    {
        return '</' . $tagName . '>';
    }

    static function createIcon($icon, $additionalClasses = []): string
    {
        return self::startElement('i', array_merge(['bi-' . $icon], $additionalClasses)) . self::endElement('i');
    }

    static function createMenuItem($title, $class, $icon): string
    {
        return self::startElement('div', ['d-flex', 'p-2', $class, 'text-white', 'menu-item', 'rounded-1']) . self::createIcon($icon, ['me-2']) . ' ' . $title . self::endElement('div');
    }

    static function createMaintenanceMenuItem($title, $class, $icon, $additionalData = []): string
    {
        return self::startElement('div', ['d-flex', 'p-2', $class, 'text-danger', 'menu-item', 'rounded-1', 'mt-5'], $additionalData) . self::createIcon($icon, ['me-2']) . ' ' . $title . self::endElement('div');
    }
}
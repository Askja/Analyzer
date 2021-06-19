<?php
namespace engine;

class Render
{
    /*
     * Render page
     */
    static function out($__view, $__data): bool|string
    {
        extract($__data);

        ob_start();
        require $__view;
        return ob_get_clean();
    }
}
<?php

namespace config;

interface ApplicationKeys
{
    /**
     * Default theme css file
     */
    const THEME_DEFAULT = 'style-dark.css';

    const AVAILABLE_THEMES = [
        ['title' => 'Default', 'file' => 'style.css'],
        ['title' => 'Default (Light)', 'file' => 'style-light.css'],
        ['title' => 'Default (Dark)', 'file' => 'style-dark.css'],
        ['title' => 'Glass', 'file' => 'style-glass.css'],
        ['title' => 'Glass (Light)', 'file' => 'style-glass-light.css'],
        ['title' => 'Glass (Dark)', 'file' => 'style-glass-dark.css'],
    ];

    /**
     * Default elements css prefix
     */
    const DEFAULT_PREFIX = 'askja';
}
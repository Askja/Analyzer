<?php


namespace models;


class AssetManager
{
    const defaultRoute = 'web/';

    public function __construct(
        public $assetsCss = [],
        public $assetsJs = [],
    ) {}

    public function getItems($items, $mask, $defRoot): array
    {
        $result = [];

        foreach ($items as $item) {
            $result[] = "\t\t" . StringsHelper::strReplaceAssoc([
                '%url%' => self::defaultRoute . $defRoot . $item[0],
                '%version%' => $item[1],
            ], trim($mask)) . "\n";
        }

        return $result;
    }

    public function getAssetsCss(): string
    {
        return implode("\r",
            $this->getItems($this->assetsCss, '<link rel="stylesheet" href="%url%?v=%version%">', 'css/')
        );
    }

    public function getAssetsJS(): string
    {
        return implode("\r",
            $this->getItems($this->assetsJs, '<script type="text/javascript" src="%url%?v=%version%"></script>', 'js/')
        );
    }
}
<?php


namespace engine;

class Application
{
    const name = 'Complex datalyze';
    const MYSQL_PASS = 'X%CWq7uJ';
    const MYSQL_LOGIN = 'b90007xh_analyze';
    const MYSQL_DB = 'b90007xh_analyze';
    const MYSQL_PROFESSION_TABLE = 'Professions';
    const MYSQL_GROUPS_TABLE = 'Groups';
    const MYSQL_STUDENTS_TABLE = 'Students';
    const MYSQL_LESSONS_TABLE = 'LessonsKeys';

    static function init() {
        self::setIniDirectives([
            ['display_errors', 'On'],
            ['expose_php', 'Off']
        ]);

        header_remove("X-Powered-By");

        error_reporting(E_ALL);
    }

    static function setIniDirectives($args) {
        foreach ($args as $arg) {
            ini_set($arg[0], $arg[1]);
        }
    }

    static function getAssetsCss(): array
    {
        return [
            ['noty.css', '1.0'],
            ['application.css', time()],
        ];
    }

    static function getAssetsJS(): array
    {
        return [
            ['jquery-slim.js', '0.0.1'],
            ['vue.min.js', '0.0.1'],
            ['noty.js', '0.0.1'],
            ['modal.js', '0.0.1'],
            ['application.js', time()],
            ['skin/skin-manager.js', time()],
            ['teskly.viewitle.js', '0.0.1'],
        ];
    }

    static function getAppName(): string
    {
        return self::name;
    }
}
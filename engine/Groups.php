<?php

namespace engine;

use mysqli_result;

class Groups
{
    /**
     * @param string|null $column
     * @return array
     */
    static function getAll(?string $column = null): array
    {
        if (empty($column)) {
            $column = '*';
        }

        return self::getConnect()?->getAll('SELECT ' . $column . ' FROM ?n', Application::MYSQL_GROUPS_TABLE);
    }

    /**
     * @return array
     */
    static function getNames(): array
    {
        $names = [];

        foreach (self::getAll() as $group) {
            $names[$group['id']] = $group['name'];
        }

        return $names;
    }

    /**
     * @param string $name
     * @return int|null
     */
    static function getIDByName(string $name): ?int
    {
        foreach (self::getAll() as $group) {
            if ($group['name'] === $name) {
                return $group['id'];
            }
        }

        return null;
    }

    /**
     * @param int $id
     * @return mysqli_result|bool
     */
    static function deleteById(int $id): mysqli_result|bool
    {
        $connect = self::getConnect();
        $connect?->query('DELETE FROM ?n WHERE id=?i', Application::MYSQL_GROUPS_TABLE, $id);

        return $connect->affectedRows() > 0;
    }

    /**
     * @param string $name
     * @return mysqli_result|bool
     */
    static function deleteByName(string $name): mysqli_result|bool
    {
        $connect = self::getConnect();
        $connect?->query('DELETE FROM ?n WHERE name=?s', Application::MYSQL_GROUPS_TABLE, $name);

        return $connect->affectedRows() > 0;
    }

    /**
     * @param string $name
     * @return bool|array
     */
    static function insert(string $name): bool|array
    {
        $connect = self::getConnect();
        $insert = $connect?->query('INSERT INTO ?n (name) VALUES(?s)', Application::MYSQL_GROUPS_TABLE, $name);

        if ($insert) {
            return $connect->getInd('id', 'SELECT * FROM ?n WHERE name=?s', Application::MYSQL_GROUPS_TABLE, $name);
        }

        return false;
    }

    /**
     * @return MySql
     */
    static function getConnect(): MySql
    {
        return new MySql([
            'user' => Application::MYSQL_LOGIN,
            'pass' => Application::MYSQL_PASS,
            'db' => Application::MYSQL_DB,
        ]);
    }
}
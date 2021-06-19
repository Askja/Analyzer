<?php

namespace engine;

use mysqli_result;

class Students
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

        return self::getConnect()?->getAll('SELECT ' . $column . ' FROM ?n', Application::MYSQL_STUDENTS_TABLE);
    }

    /**
     * @param int|null $gid
     * @return array
     */
    static function getNames(?int $gid = null): array
    {
        $names = [];

        foreach (self::getAll() as $group) {
            if ($gid === null || $gid == $group['group_id']) {
                $names[$group['sid']] = [
                    'name' => $group['name'],
                    'dates' => $group['bdate'],
                    'rates_id' => $group['rates_id'],
                    'group_id' => $group['group_id'],
                ];
            }
        }

        return $names;
    }

    /**
     * @param int $id
     * @return mysqli_result|bool
     */
    static function deleteById(int $id): mysqli_result|bool
    {
        $connect = self::getConnect();
        $connect?->query('DELETE FROM ?n WHERE sid=?i', Application::MYSQL_STUDENTS_TABLE, $id);

        return $connect->affectedRows() > 0;
    }

    /**
     * @param string $name
     * @return mysqli_result|bool
     */
    static function deleteByName(string $name): mysqli_result|bool
    {
        $connect = self::getConnect();
        $connect?->query('DELETE FROM ?n WHERE name=?s', Application::MYSQL_STUDENTS_TABLE, $name);

        return $connect->affectedRows() > 0;
    }

    /**
     * @param string $name
     * @return bool|array
     */
    static function insert(string $name): bool|array
    {
        $connect = self::getConnect();
        $insert = $connect?->query('INSERT INTO ?n (name, rates_id, bdate) VALUES(?s, 0, \'2000-0-0\')', Application::MYSQL_STUDENTS_TABLE, $name);

        if ($insert) {
            return $connect->getInd('sid', 'SELECT * FROM ?n WHERE name=?s', Application::MYSQL_STUDENTS_TABLE, $name);
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
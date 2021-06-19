<?php


namespace engine\api;


class Response
{
    public static function getResponse(string $status, mixed $body)
    {

        $responseModel = [
            'status' => $status,
            'body' => $body,
            'ts' => time(),
        ];

        print_r(self::encode($responseModel));
    }

    public static function encode($res): bool|string
    {
        return json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_LINE_TERMINATORS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
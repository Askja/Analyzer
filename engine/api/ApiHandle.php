<?php


namespace engine\api;


use engine\finalVitae;
use engine\Vitae;
use engine\VitaeCollections;
use JetBrains\PhpStorm\ArrayShape;

class ApiHandle
{
    function __construct(
        public $method,
        public ?array $data = [],
        public ?array $filter = [],
    )
    {
    }

    #[ArrayShape(['status' => "string", 'body' => "array"])]
    public function getStatementsMethod()
    {
        $vitae = new VitaeCollections('../temp/Ведомости', '../temp/templates', '../temp/system');
        return ['status' => 'ok', 'body' => $vitae->getCreated()];
    }

    #[ArrayShape(['status' => "string", 'body' => "bool|string"])]
    public function removeStatementByPathMethod() {
        $file = $this->data['path'];

        if (file_exists($file)) {
            $status = unlink($file);
        } else {
            $status = 'Такого файла не существует!';
        }

        return ['status' => 'ok', 'body' => $status];
    }
}
<?php


namespace engine\api;

use models\RequestHeaders;

class ApiController
{
    public array $availableFields = ['action' => 'is_string', 'ts' => 'is_numeric', 'data' => null, 'filter' => null];
    public array $parsed;

    function __construct(
        public $request
    ) {}

    public function normalizeData(): bool
    {
        $this->parsed = [];

        $this->request = match (RequestHeaders::getMethod()) {
            'GET' => $_GET,
            'POST' => $_POST,
            default => []
        };

        foreach ($this->availableFields as $key => $value) {
            if (isset($this->request[$key]) && ((is_callable($value) && $value($this->request[$key])) || $value === null)) {
                $this->parsed[$key] = $this->request[$key];
            }
        }

        return (count($this->parsed) > 0 && array_key_exists('action', $this->parsed));
    }

    public function getArrayVal($key): array
    {
        if (array_key_exists($key, $this->parsed)) {
            return (!is_array($this->parsed[$key]) ? json_decode($this->parsed[$key], true) : $this->parsed[$key]);
        }

        return [];
    }

    public function handle(): array
    {
        $action = $this->parsed['action'];

        $data = $this->getArrayVal('data');
        $filter = $this->getArrayVal('filter');

        $handler = new ApiHandle($action, $data, $filter);

        $methodName = lcfirst(str_replace('_', '', ucwords(trim($action), '_')) . 'Method');

        if (method_exists($handler, $methodName)) {
            return $handler->$methodName();
        }

        return ['status' => 'error', 'body' => 'Method not exists'];
    }

    public function handleRequest()
    {
        if (RequestHeaders::getMethod() === 'POST') {
            if ($this->normalizeData()) {
                $data = $this->handle();
                Response::getResponse($data['status'], $data['body']);
            }
        }
    }
}
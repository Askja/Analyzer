<?php

require '../vendor/autoload.php';

use engine\api\ApiController;
use engine\Application;

Application::init();

(new ApiController($_SERVER))->handleRequest();
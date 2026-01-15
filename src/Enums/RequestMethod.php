<?php

declare(strict_types=1);

namespace Motomedialab\Connector\Enums;

enum RequestMethod: string
{
    case POST = 'post';
    case GET = 'get';
    case PUT = 'put';
    case DELETE = 'delete';
    case PATCH = 'patch';
}

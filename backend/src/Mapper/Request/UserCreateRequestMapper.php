<?php

namespace App\Mapper\Request;

use App\Mapper\Contracts\RequestMapperInterface;
use Symfony\Component\HttpFoundation\Request;

class UserCreateRequestMapper implements RequestMapperInterface
{

    /**
     * @inheritDoc
     */
    public function fromRequest(Request $request): object
    {
        // TODO: Implement fromRequest() method.
    }
}

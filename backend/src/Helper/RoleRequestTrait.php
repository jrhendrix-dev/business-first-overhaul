<?php

namespace App\Helper;

use Symfony\Component\HttpFoundation\Request;
use App\Enum\UserRoleEnum;

/**
 * Provides functionality to extract and validate role parameters from HTTP requests.
 */
trait RoleRequestTrait
{
    /**
     * Extracts a role parameter from the request and converts it to a UserRoleEnum instance.
     *
     * The method first attempts to retrieve the role from query parameters. If not found,
     * it checks the JSON content of the request body. The role can be provided either as
     * a numeric value (matching UserRoleEnum constants) or as a string representation.
     *
     * @param Request $request The HTTP request object to extract the role from
     *
     * @return UserRoleEnum|null Returns the corresponding UserRoleEnum instance if the role is valid,
     *                           null if the role parameter is missing or invalid
     *
     * @throws \JsonException If the request content contains invalid JSON
     */
    public function getRoleEnumFromRequest(Request $request): ?UserRoleEnum
    {
        $role = $request->query->get('role');

        if(!$role) {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $role = $data['role'] ?? null;
        }

        $roleInt = is_numeric($role) ? (int) $role : null;

        return $roleInt !== null && in_array($roleInt, UserRoleEnum::values(), true)
            ? UserRoleEnum::tryFrom($roleInt)
            : null;
    }
}

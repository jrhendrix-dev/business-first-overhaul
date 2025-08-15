<?php
namespace App\Helper;

use App\Enum\UserRoleEnum;
use Symfony\Component\HttpFoundation\Request;

trait RoleRequestTrait
{
    private function getRoleEnumFromRequest(Request $request): ?UserRoleEnum
    {
        // Prefer ?role=... on query string for GETs
        $roleParam = $request->query->get('role');

        // If not present on query, try JSON body – but only if non-empty
        if ($roleParam === null) {
            $raw = $request->getContent();
            if (is_string($raw) && trim($raw) !== '') {
                try {
                    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                    $roleParam = $data['role'] ?? null;
                } catch (\JsonException) {
                    // ignore: treat as missing/invalid role
                }
            }
        }

        if ($roleParam === null || $roleParam === '') {
            return null;
        }

        // accept integers or strings that can be cast to int
        $int = filter_var($roleParam, FILTER_VALIDATE_INT);
        if ($int === false) {
            return null;
        }

        try {
            return UserRoleEnum::from((int)$int);
        } catch (\ValueError) {
            return null;
        }
    }
}

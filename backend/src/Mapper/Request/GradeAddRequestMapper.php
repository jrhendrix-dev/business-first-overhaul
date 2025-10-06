<?php
// src/Mapper/Request/GradeAddRequestMapper.php
declare(strict_types=1);

namespace App\Mapper\Request;

use App\Dto\Grade\AddGradeDto;
use App\Enum\GradeComponentEnum;
use App\Http\Exception\ValidationException;
use App\Mapper\Contracts\RequestMapperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Maps POST grade requests into AddGradeDTOs.
 */
final class GradeAddRequestMapper implements RequestMapperInterface
{
    public function fromRequest(Request $request): object
    {
        /** @var array<string,mixed> $data */
        $data = (array) json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        $componentRaw = (string) ($data['component'] ?? '');
        $component    = GradeComponentEnum::tryFromMixed($componentRaw);
        if ($component === null) {
            throw new ValidationException([
                'component' => sprintf(
                    'Invalid component. Allowed values: %s',
                    implode(', ', GradeComponentEnum::values())
                ),
            ]);
        }

        return new AddGradeDto(
            component: $component,
            score: isset($data['score']) ? (float) $data['score'] : 0.0,
            maxScore: isset($data['maxScore']) ? (float) $data['maxScore'] : 10.0,
        );
    }
}

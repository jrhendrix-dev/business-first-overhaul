<?php
// src/Mapper/Request/GradeUpdateRequestMapper.php
declare(strict_types=1);

namespace App\Mapper\Request;

use App\Dto\Grade\UpdateGradeDto;
use App\Enum\GradeComponentEnum;
use App\Http\Exception\ValidationException;
use App\Mapper\Contracts\RequestMapperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Maps PUT/PATCH grade requests into UpdateGradeDTOs.
 */
final class GradeUpdateRequestMapper implements RequestMapperInterface
{
    public function fromRequest(Request $request): object
    {
        /** @var array<string,mixed> $data */
        $data = (array) json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        $component = null;
        if (array_key_exists('component', $data)) {
            $componentRaw = (string) $data['component'];
            $component    = GradeComponentEnum::tryFromMixed($componentRaw);
            if ($component === null) {
                throw new ValidationException([
                    'component' => sprintf(
                        'Invalid component. Allowed values: %s',
                        implode(', ', GradeComponentEnum::values())
                    ),
                ]);
            }
        }

        $dto = new UpdateGradeDto(
            score: array_key_exists('score', $data) ? (float) $data['score'] : null,
            maxScore: array_key_exists('maxScore', $data) ? (float) $data['maxScore'] : null,
            component: $component,
        );

        if (!$dto->hasChanges()) {
            throw new ValidationException([
                'body' => 'Provide at least one field: score, maxScore, component.',
            ]);
        }

        return $dto;
    }
}

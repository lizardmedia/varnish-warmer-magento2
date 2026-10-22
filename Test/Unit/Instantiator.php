<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit;

use Closure;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;

final class Instantiator
{
    /**
     * @param array<string, object> $dependenciesByType a Closure value is called only when its type is requested
     */
    public static function create(string $className, array $dependenciesByType): object
    {
        $constructor = (new ReflectionClass($className))->getConstructor();
        $arguments = [];

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;

            if ($typeName !== null && array_key_exists($typeName, $dependenciesByType)) {
                $dependency = $dependenciesByType[$typeName];
                $arguments[] = $dependency instanceof Closure ? $dependency() : $dependency;
                continue;
            }

            if (!$parameter->isDefaultValueAvailable()) {
                throw new LogicException(sprintf(
                    'No test dependency given for %s::__construct() parameter $%s of type %s',
                    $className,
                    $parameter->getName(),
                    $typeName ?? 'mixed'
                ));
            }

            $arguments[] = $parameter->getDefaultValue();
        }

        return new $className(...$arguments);
    }
}

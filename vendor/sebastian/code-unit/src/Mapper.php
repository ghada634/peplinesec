<?php

declare(strict_types=1);

/*
 * This file is part of sebastian/code-unit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeUnit;

use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function explode;
use function function_exists;
use function interface_exists;
use function ksort;
use function method_exists;
use function sort;
use function sprintf;
use function str_replace;
use function strpos;
use function trait_exists;

use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

final class Mapper
{
    /**
     * @psalm-return array<string,list<int>>
     */
    public function codeUnitsToSourceLines(CodeUnitCollection $codeUnits): array
    {
        $result = [];

        foreach ($codeUnits as $codeUnit) {
            $sourceFileName = $codeUnit->sourceFileName();

            if (!isset($result[$sourceFileName])) {
                $result[$sourceFileName] = [];
            }

            $result[$sourceFileName] = array_merge($result[$sourceFileName], $codeUnit->sourceLines());
        }

        foreach (array_keys($result) as $sourceFileName) {
            $result[$sourceFileName] = array_values(array_unique($result[$sourceFileName]));

            sort($result[$sourceFileName]);
        }

        ksort($result);

        return $result;
    }

    /**
     * @throws InvalidCodeUnitException
     * @throws ReflectionException
     */
    public function stringToCodeUnits(string $unit): CodeUnitCollection
    {
        if (strpos($unit, '::') !== false) {
            [$firstPart, $secondPart] = explode('::', $unit);

            if (empty($firstPart) && $this->isUserDefinedFunction($secondPart)) {
                return CodeUnitCollection::fromList(CodeUnit::forFunction($secondPart));
            }

            if ($this->isUserDefinedClass($firstPart)) {
                if ($secondPart === '<public>') {
                    return $this->publicMethodsOfClass($firstPart);
                }

                if ($secondPart === '<!public>') {
                    return $this->protectedAndPrivateMethodsOfClass($firstPart);
                }

                if ($secondPart === '<protected>') {
                    return $this->protectedMethodsOfClass($firstPart);
                }

                if ($secondPart === '<!protected>') {
                    return $this->publicAndPrivateMethodsOfClass($firstPart);
                }

                if ($secondPart === '<private>') {
                    return $this->privateMethodsOfClass($firstPart);
                }

                if ($secondPart === '<!private>') {
                    return $this->publicAndProtectedMethodsOfClass($firstPart);
                }

                if ($this->isUserDefinedMethod($firstPart, $secondPart)) {
                    return CodeUnitCollection::fromList(CodeUnit::forClassMethod($firstPart, $secondPart));
                }
            }

            if ($this->isUserDefinedInterface($firstPart)) {
                return CodeUnitCollection::fromList(CodeUnit::forInterfaceMethod($firstPart, $secondPart));
            }

            if ($this->isUserDefinedTrait($firstPart)) {
                return CodeUnitCollection::fromList(CodeUnit::forTraitMethod($firstPart, $secondPart));
            }
        } else {
            if ($this->isUserDefinedClass($unit)) {
                $units = [CodeUnit::forClass($unit)];

                foreach ($this->reflectorForClass($unit)->getTraits() as $trait) {
                    if (!$trait->isUserDefined()) {
                        // @codeCoverageIgnoreStart
                        continue;
                        // @codeCoverageIgnoreEnd
                    }

                    $units[] = CodeUnit::forTrait($trait->getName());
                }

                return CodeUnitCollection::fromArray($units);
            }

            if ($this->isUserDefinedInterface($unit)) {
                return CodeUnitCollection::fromList(CodeUnit::forInterface($unit));
            }

            if ($this->isUserDefinedTrait($unit)) {
                return CodeUnitCollection::fromList(CodeUnit::forTrait($unit));
            }

            if ($this->isUserDefinedFunction($unit)) {
                return CodeUnitCollection::fromList(CodeUnit::forFunction($unit));
            }

            $unit = str_replace('<extended>', '', $unit);

            if ($this->isUserDefinedClass($unit)) {
                return $this->classAndParentClassesAndTraits($unit);
            }
        }

        throw new InvalidCodeUnitException(
            sprintf(
                '"%s" is not a valid code unit',
                $unit
            )
        );
    }

    /**
     * @psalm-param class-string $className
     *
     * @throws ReflectionException
     */
    private function publicMethodsOfClass(string $className): CodeUnitCollection
    {
        return $this->methodsOfClass($className, ReflectionMethod::IS_PUBLIC);
    }

    /**
     * @psalm-param class-string $className
     *
     * @throws ReflectionException
     */
    private function publicAndProtectedMethodsOfClass(string $className): CodeUnitCollection
    {
        return $this->methodsOfClass($className, ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);
    }

    /**
     * @psalm-param class-string $className
     *
     * @throws ReflectionException
     */
    private function publicAndPrivateMethodsOfClass(string $className): CodeUnitCollection
    {
        return $this->methodsOfClass($className, ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PRIVATE);
    }

    /**
     * @psalm-param class-string $className
     *
     * @throws ReflectionException
     */
    private function protectedMethodsOfClass(string $className): CodeUnitCollection
    {
        return $this->methodsOfClass($className, ReflectionMethod::IS_PROTECTED);
    }

    /**
     * @psalm-param class-string $className
     *
     * @throws ReflectionException
     */
    private function protectedAndPrivateMethodsOfClass(string $className): CodeUnitCollection
    {
        return $this->methodsOfClass($className, ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PRIVATE);
    }

    /**
     * @psalm-param class-string $className
     *
     * @throws ReflectionException
     */
    private function privateMethodsOfClass(string $className): CodeUnitCollection
    {
        return $this->methodsOfClass($className, ReflectionMethod::IS_PRIVATE);
    }

    /**
     * @psalm-param class-string $className
     *
     * @throws ReflectionException
     */
    private function methodsOfClass(string $className, int $filter): CodeUnitCollection
    {
        $units = [];

        foreach ($this->reflectorForClass($className)->getMethods($filter) as $method) {
            if (!$method->isUserDefined()) {
                continue;
            }

            $units[] = CodeUnit::forClassMethod($className, $method->getName());
        }

        return CodeUnitCollection::fromArray($units);
    }

    /**
     * @psalm-param class-string $className
     *
     * @throws ReflectionException
     */
    private function classAndParentClassesAndTraits(string $className): CodeUnitCollection
    {
        $units = [CodeUnit::forClass($className)];

        $reflector = $this->reflectorForClass($className);

        foreach ($this->reflectorForClass($className)->getTraits() as $trait) {
            if (!$trait->isUserDefined()) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }

            $units[] = CodeUnit::forTrait($trait->getName());
        }

        while ($reflector = $reflector->getParentClass()) {
            if (!$reflector->isUserDefined()) {
                break;
            }

            $units[] = CodeUnit::forClass($reflector->getName());

            foreach ($reflector->getTraits() as $trait) {
                if (!$trait->isUserDefined()) {
                    // @codeCoverageIgnoreStart
                    continue;
                    // @codeCoverageIgnoreEnd
                }

                $units[] = CodeUnit::forTrait($trait->getName());
            }
        }

        return CodeUnitCollection::fromArray($units);
    }

    /**
     * @psalm-param class-string $className
     *
     * @throws ReflectionException
     */
    private function reflectorForClass(string $className): ReflectionClass
    {
        try {
            return new ReflectionClass($className);
            // @codeCoverageIgnoreStart
        } catch (\ReflectionException $e) {
            throw new ReflectionException(
                $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws ReflectionException
     */
    private function isUserDefinedFunction(string $functionName): bool
    {
        if (!function_exists($functionName)) {
            return false;
        }

        try {
            return (new ReflectionFunction($functionName))->isUserDefined();
            // @codeCoverageIgnoreStart
        } catch (\ReflectionException $e) {
            throw new ReflectionException(
                $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws ReflectionException
     */
    private function isUserDefinedClass(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        try {
            return (new ReflectionClass($className))->isUserDefined();
            // @codeCoverageIgnoreStart
        } catch (\ReflectionException $e) {
            throw new ReflectionException(
                $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws ReflectionException
     */
    private function isUserDefinedInterface(string $interfaceName): bool
    {
        if (!interface_exists($interfaceName)) {
            return false;
        }

        try {
            return (new ReflectionClass($interfaceName))->isUserDefined();
            // @codeCoverageIgnoreStart
        } catch (\ReflectionException $e) {
            throw new ReflectionException(
                $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws ReflectionException
     */
    private function isUserDefinedTrait(string $traitName): bool
    {
        if (!trait_exists($traitName)) {
            return false;
        }

        try {
            return (new ReflectionClass($traitName))->isUserDefined();
            // @codeCoverageIgnoreStart
        } catch (\ReflectionException $e) {
            throw new ReflectionException(
                $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws ReflectionException
     */
    private function isUserDefinedMethod(string $className, string $methodName): bool
    {
        if (!class_exists($className)) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        if (!method_exists($className, $methodName)) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        try {
            return (new ReflectionMethod($className, $methodName))->isUserDefined();
            // @codeCoverageIgnoreStart
        } catch (\ReflectionException $e) {
            throw new ReflectionException(
                $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
        // @codeCoverageIgnoreEnd
    }
}

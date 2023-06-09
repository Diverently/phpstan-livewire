<?php

declare(strict_types=1);

namespace Diverently\PhpstanLivewire;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Livewire\Component;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;

/**
 * @internal
 */
final class LivewireAccessorExtension implements PropertiesClassReflectionExtension
{
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->isSubclassOf(Component::class)) {
            return false;
        }

        $camelCase = Str::camel($propertyName);

        if ($classReflection->hasNativeMethod($camelCase)) {
            $methodReflection = $classReflection->getNativeMethod($camelCase);

            if ($methodReflection->isPublic() || $methodReflection->isPrivate()) {
                return false;
            }

            $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

            if (! $returnType instanceof GenericObjectType) {
                return false;
            }

            if (! (new ObjectType(Attribute::class))->isSuperTypeOf($returnType)->yes()) {
                return false;
            }

            return true;
        }

        return $classReflection->hasNativeMethod('get'.Str::studly($propertyName).'Property');
    }

    public function getProperty(
        ClassReflection $classReflection,
        string $propertyName
    ): PropertyReflection {
        $studlyName = Str::studly($propertyName);

        if ($classReflection->hasNativeMethod($studlyName)) {
            $methodReflection = $classReflection->getNativeMethod($studlyName);

            /** @var GenericObjectType $returnType */
            $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

            return new LivewireProperty(
                $classReflection,
                $returnType->getTypes()[0],
                $returnType->getTypes()[1]
            );
        }

        $method = $classReflection->getNativeMethod('get'.Str::studly($propertyName).'Property');

        return new LivewireProperty(
            $classReflection,
            $method->getVariants()[0]->getReturnType(),
            $method->getVariants()[0]->getReturnType()
        );
    }
}

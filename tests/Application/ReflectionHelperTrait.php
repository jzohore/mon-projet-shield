<?php

namespace App\Tests\Application;

trait ReflectionHelperTrait
{
    /**
     * Instancie une entité sans appeler son constructeur et force l'hydratation de ses propriétés privées.
     * Idéal pour simuler un état Doctrine en base de données.
     *
     * @template T of object
     * @param class-string<T> $className
     * @param array<string, mixed> $properties
     * @return T
     * @throws \ReflectionException
     */
    private function createEntityState(string $className, array $properties = []): object
    {
        $reflection = new \ReflectionClass($className);
        $entity = $reflection->newInstanceWithoutConstructor();

        foreach ($properties as $propertyName => $value) {
            $property = $reflection->getProperty($propertyName);
            $declaringProperty = $property->getDeclaringClass()->getProperty($propertyName);
            $declaringProperty->setValue($entity, $value);
        }

        return $entity;
    }
}

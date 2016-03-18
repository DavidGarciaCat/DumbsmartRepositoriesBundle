<?php

namespace spec\carlosV2\DumbsmartRepositoriesBundle\Configurer;

use carlosV2\DumbsmartRepositories\Metadata;
use carlosV2\DumbsmartRepositories\Relation\OneToManyRelation;
use carlosV2\DumbsmartRepositories\Relation\OneToOneRelation;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Everzet\PersistedObjects\ObjectIdentifier;
use PhpSpec\ObjectBehavior;

class MetadataFactorySpec extends ObjectBehavior
{
    function it_returns_metadata_configured_from_an_ORM_schema(
        ObjectIdentifier $identifier,
        ClassMetadata $classMetadata
    ) {
        $classMetadata->associationMappings = [
            'toOneField' => ['type' => 1, 'fieldName' => 'toOneField'],
            'toManyField' => ['type' => 4, 'fieldName' => 'toManyField']
        ];

        $this->createMetadata($classMetadata, $identifier)->shouldReturnMetadataWith($identifier, [
            new OneToOneRelation('toOneField'),
            new OneToManyRelation('toManyField')
        ]);
    }

    function it_returns_metadata_configured_from_an_ODM_schema(
        ObjectIdentifier $identifier,
        ClassMetadata $classMetadata
    ) {
        $classMetadata->associationMappings = [
            'toOneField' => ['related' => true, 'type' => 'one', 'fieldName' => 'toOneField'],
            'toManyField' => ['related' => true, 'type' => 'many', 'fieldName' => 'toManyField']
        ];

        $this->createMetadata($classMetadata, $identifier)->shouldReturnMetadataWith($identifier, [
            new OneToOneRelation('toOneField'),
            new OneToManyRelation('toManyField')
        ]);
    }

    function it_does_not_use_embedded_relations_on_ODM_schemas(
        ObjectIdentifier $identifier,
        ClassMetadata $classMetadata
    ) {
        $classMetadata->associationMappings = [
            'toOneField' => ['related' => false, 'type' => 'one', 'fieldName' => 'toOneField'],
            'toManyField' => ['related' => true, 'type' => 'many', 'fieldName' => 'toManyField']
        ];

        $this->createMetadata($classMetadata, $identifier)->shouldReturnMetadataWith($identifier, [
            new OneToManyRelation('toManyField')
        ]);
    }

    function it_returns_a_metadata_even_without_relations(
        ObjectIdentifier $identifier,
        ClassMetadata $classMetadata
    ) {
        $classMetadata->associationMappings = [];

        $this->createMetadata($classMetadata, $identifier)->shouldReturnMetadataWith($identifier, []);
    }

    function it_ignores_invalid_relations(
        ObjectIdentifier $identifier,
        ClassMetadata $classMetadata
    ) {
        $classMetadata->associationMappings = [
            'ormFieldWithoutType' => ['fieldName' => 'toOneField'],
            'ormFieldWithoutFieldName' => ['type' => 1],
            'ormFieldWithWrongType' => ['type' => '4', 'fieldName' => 'toOneField'],
            'ormFieldWithWrongFieldName' => ['type' => 4, 'fieldName' => 1],

            'odmFieldWithoutType' => ['related' => false, 'fieldName' => 'toOneField'],
            'odmFieldWithoutRelated' => ['type' => 'one', 'fieldName' => 'toOneField'],
            'odmFieldWithoutFieldName' => ['related' => false, 'type' => 'one'],
            'odmFieldWithWrongType' => ['related' => false, 'type' => '1', 'fieldName' => 'toOneField'],
            'odmFieldWithWrongRelated' => ['related' => 'false', 'type' => 'one', 'fieldName' => 'toOneField'],
            'odmFieldWitWrongFieldName' => ['related' => false, 'type' => 'one', 'fieldName' => 1]
        ];

        $this->createMetadata($classMetadata, $identifier)->shouldReturnMetadataWith($identifier, []);
    }

    public function getMatchers()
    {
        return [
            'returnMetadataWith' => function ($metadata, $identifier, $relations) {
                if (!$metadata instanceof Metadata) {
                    return false;
                }

                $property = new \ReflectionProperty($metadata, 'identifier');
                $property->setAccessible(true);
                if ($property->getValue($metadata) !== $identifier) {
                    return false;
                }

                $property = new \ReflectionProperty($metadata, 'relations');
                $property->setAccessible(true);
                return $property->getValue($metadata) == $relations;
            }
        ];
    }
}

<?php

namespace carlosV2\DumbsmartRepositoriesBundle\DependencyInjection;

use carlosV2\DumbsmartRepositoriesBundle\Configurer\RepositoryFactory;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('dumbsmart_repositories');

        $rootNode
            ->children()
                ->append($this->getRepositoriesConfiguration())
                ->append($this->getAutoconfigureConfiguration())
                ->append($this->getAliasesConfiguration())
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * @return NodeDefinition
     */
    private function getRepositoriesConfiguration()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('repositories');

        return $node
            ->addDefaultsIfNotSet()
            ->children()
                ->enumNode('type')
                    ->defaultValue(RepositoryFactory::TYPE_IN_MEMORY)
                    ->values([RepositoryFactory::TYPE_IN_MEMORY, RepositoryFactory::TYPE_FILE])
                ->end()
                ->scalarNode('path')
                    ->defaultValue(sys_get_temp_dir())
                    ->beforeNormalization()
                        ->always(function ($path) {
                            $path = ($path ?: '{sys_temp_dir}');
                            $path = str_replace('{sys_temp_dir}', sys_get_temp_dir() . '/', $path);
                            $path = str_replace('//', '/', $path);

                            if (!is_dir($path) && !mkdir($path, 0777, true)) {
                                throw new \InvalidArgumentException('Cannot create folder `' . $path . '`.');
                            }

                            return $path;
                        })
                    ->end()
                    ->validate()
                        ->ifTrue(function ($path) {
                            return !is_dir($path) || !is_writable($path);
                        })
                        ->thenInvalid('Not a valid path or not writable')
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @return NodeDefinition
     */
    private function getAutoconfigureConfiguration()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('autoconfigure');

        return $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('orm')
                    ->defaultFalse()
                ->end()
                ->booleanNode('odm')
                    ->defaultFalse()
                ->end()
            ->end()
        ;
    }

    /**
     * @return NodeDefinition
     */
    private function getAliasesConfiguration()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('aliases');

        return $node
            ->useAttributeAsKey('name')
            ->prototype('variable')
                ->beforeNormalization()
                    ->always(function ($alias) {
                        if (!is_array($alias)) {
                            $alias = ['class' => $alias, 'mapping' => []];
                        }

                        return $alias;
                    })
                ->end()
                ->validate()
                    ->ifTrue(function ($alias) {
                        if (!is_array($alias) || !array_key_exists('class', $alias) || !array_key_exists('mapping', $alias)) {
                            return true;
                        }

                        if (!is_array($alias['mapping'])) {
                            return true;
                        }

                        foreach ($alias['mapping'] as $fieldKey => $field) {
                            if (!is_string($fieldKey) || !is_string($field)) {
                                return true;
                            }
                        }

                        return false;
                    })
                    ->thenInvalid('Wrong alias configuration')
                ->end()
            ->end()
        ;
    }
}

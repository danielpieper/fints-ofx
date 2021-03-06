<?php declare(strict_types=1);

namespace danielpieper\FintsOfx\Config;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class AppConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('app');

        $rootNode
            ->children()
                ->scalarNode('start_date')->isRequired()->cannotBeEmpty()->defaultValue('1 month ago')->end()
                ->scalarNode('end_date')->isRequired()->cannotBeEmpty()->defaultValue('now')->end()
                ->arrayNode('institutions')
                    ->requiresAtLeastOneElement()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('url')->isRequired()->cannotBeEmpty()->end()
                            ->integerNode('port')->isRequired()->end()
                            ->scalarNode('code')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('bic')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('username')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('currency')->isRequired()->cannotBeEmpty()->end()
                            ->arrayNode('accounts')
                                ->requiresAtLeastOneElement()
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                                        ->scalarNode('number')->isRequired()->cannotBeEmpty()->end()
                                        ->scalarNode('iban')->isRequired()->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

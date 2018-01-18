<?php declare(strict_types=1);
namespace FintsOfx\Config;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class AppConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('app');

        $rootNode
            ->fixXmlConfig('institution')
            ->children()
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

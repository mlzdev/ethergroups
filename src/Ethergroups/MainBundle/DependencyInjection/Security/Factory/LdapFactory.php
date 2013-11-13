<?php

namespace Ethergroups\MainBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

class LdapFactory implements SecurityFactoryInterface
{
	public function getPosition() {
    	return 'form';
  	}

  	public function getKey() {
    	return 'ldap';
  	}
	
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
		$providerId = 'security.authentication.provider.ldap.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('ldap.security.authentication.provider'))
            ->replaceArgument(0, new Reference($userProvider))
        ;

        $listenerId = 'security.authentication.listener.ldap.'.$id;
        $listener = $container->setDefinition($listenerId, new DefinitionDecorator('ldap.security.authentication.listener'));

        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    public function addConfiguration(NodeDefinition $node)
    {
    }
}

?>
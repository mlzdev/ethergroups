<?php

namespace Ethergroups\MainBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Ethergroups\MainBundle\DependencyInjection\Security\Factory\LdapFactory;

class EthergroupsMainBundle extends Bundle
{
    public function build(ContainerBuilder $container) {
        parent::build($container);
    
        $extensions = $container->getExtension('security');
        $extensions->addSecurityListenerFactory(new LdapFactory());
    }
}

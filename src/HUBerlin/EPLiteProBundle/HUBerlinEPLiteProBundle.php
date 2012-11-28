<?php

namespace HUBerlin\EPLiteProBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use HUBerlin\EPLiteProBundle\DependencyInjection\Security\Factory\LdapFactory;

class HUBerlinEPLiteProBundle extends Bundle
{
    public function build(ContainerBuilder $container) {
        parent::build($container);
    
        $extensions = $container->getExtension('security');
        $extensions->addSecurityListenerFactory(new LdapFactory());
    }
}

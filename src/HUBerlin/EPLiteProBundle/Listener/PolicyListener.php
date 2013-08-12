<?php
namespace HUBerlin\EPLiteProBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PolicyListener implements EventSubscriberInterface
{
    private $securityContext;

    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        
        $token = $this->securityContext->getToken();
        if (isset($token) && $token->isAuthenticated() == true && $route != "policy") {
            $user = $token->getUser();
            if(!$user->getPolicyagreed()) {
                $response = new RedirectResponse('/policy');
                return $event->setResponse($response);
            }
            return;
        }
    }

    static public function getSubscribedEvents()
    {
        return array(
                // must be registered before the default Locale listener
                KernelEvents::REQUEST => array(array('onKernelRequest')),
        );
    }
}
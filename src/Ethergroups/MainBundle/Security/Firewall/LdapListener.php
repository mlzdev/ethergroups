<?php

namespace Ethergroups\MainBundle\Security\Firewall;

use Symfony\Component\EventDispatcher\EventDispatcherInterface,
    Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\Log\LoggerInterface,
    Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface,
    Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException,
    Symfony\Component\Security\Core\SecurityContextInterface,
    Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface,
    Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface,
    Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener,
	Symfony\Component\Security\Http\Firewall\ListenerInterface,
    Symfony\Component\Security\Http\HttpUtils,
    Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Ethergroups\MainBundle\Security\Authentication\Token\LdapUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

//class LdapListener extends AbstractAuthenticationListener
class LdapListener implements ListenerInterface
{
	public function __construct(SecurityContextInterface $securityContext,
                                AuthenticationManagerInterface $authenticationManager,
                                SessionAuthenticationStrategyInterface $sessionStrategy,
                                HttpUtils $httpUtils,
                                $providerKey,
                                AuthenticationSuccessHandlerInterface $successHandler = null,
                                AuthenticationFailureHandlerInterface $failureHandler = null,
                                array $options = array(),
                                LoggerInterface $logger = null,
                                EventDispatcherInterface $dispatcher = null,
                                CsrfProviderInterface $csrfProvider = null,
                                $cookieDomain)
    {
        /*parent::__construct(
            $securityContext,
            $authenticationManager,
            $sessionStrategy,
            $httpUtils,
            $providerKey,
            $options,
            $successHandler,
            $failureHandler,
            $logger,
            $dispatcher
        );*/
        
		$this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->csrfProvider = $csrfProvider;
        $this->cookieDomain = $cookieDomain;
    }
	
    /*public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager) {
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
    }*/

	public function handle(GetResponseEvent $event)
    {
		$request = $event->getRequest();
		
		$token = $this->securityContext->getToken();
		if (isset($token) && $token->isAuthenticated() == true) {
			return $request;	
		}
		
		try {
			$returnValue = $this->attemptAuthentication($request);
            if($query = $request->getQueryString()) $query = '?'.$query;
			if ($returnValue == null) {
				$response = new RedirectResponse('/login'.$query);
				return $event->setResponse($response);
			} elseif ($returnValue instanceof TokenInterface) {
				$this->securityContext->setToken($returnValue);
				$response = new RedirectResponse('/'.$query);
				return $event->setResponse($response);
        	} elseif ($returnValue instanceof Response) {
				return $event->setResponse($returnValue);
        	}
		} catch (AuthenticationException $e) {
        	// you might log something here
			$request->getSession()->set(SecurityContextInterface::AUTHENTICATION_ERROR, $e);
			$response = new RedirectResponse('/login');
			
			return $event->setResponse($response);
			throw $e;
        }
    }

    public function attemptAuthentication(Request $request)
    {
		if ('post' !== strtolower($request->getMethod())) {
            if(isset($_COOKIE['sessionID']) && isset($this->cookieDomain)) {
                unset($_COOKIE['sessionID']);
                setcookie('sessionID', '', time()-3600, '/', $this->cookieDomain);
            }
			return null;
        }

        $username = trim($request->get('_username', null, true));
        $password = $request->get('_password', null, true);

        $request->getSession()->set(SecurityContextInterface::LAST_USERNAME, $username);

		return $this->authenticationManager->authenticate(new LdapUserToken($username, $password));
    }
}

?>
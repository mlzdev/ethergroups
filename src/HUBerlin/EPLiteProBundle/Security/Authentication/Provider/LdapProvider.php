<?php

namespace HUBerlin\EPLiteProBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use HUBerlin\EPLiteProBundle\Security\LdapDataSource;
use HUBerlin\EPLiteProBundle\Security\Authentication\Token\LdapUserToken;

class LdapProvider implements AuthenticationProviderInterface
{
    private $userProvider;
	private $dataSource;

    public function __construct(UserProviderInterface $userProvider, LdapDataSource $dataSource) {
        $this->userProvider = $userProvider;
		$this->dataSource = $dataSource;
    }

    public function authenticate(TokenInterface $token) {
		// Ask the user provider to fetch the user record from database
		// this will fail with an exception if the user don't exists, so no need to check further	
		$user = $this->userProvider->loadUserByUsername($token->getUsername());
		
		// Ask the ldap data provider to authenticate our user
		$result = $this->dataSource->authenticateUser($user->getUid(), $token->getCredentials());
// 		$result = true;
		
		// Did the authentication succeeded?
		if ($result) {
			// Save the user attributes in the user record
			$user->setAttributes($result);
			
			// Generate a new authentication token that reflects the authenticated state
			$token = new LdapUserToken($user->getUid(), $token->getCredentials(), $user->getRoles());
			$token->setUser($user);
			$token->setAuthenticated(true);
		} else {
			throw new AuthenticationException('Anmeldung fehlgeschlagen.');
		}
		
		return $token;
    }

    public function supports(TokenInterface $token)
    {
		$result = ($token instanceof LdapUserToken);
		return $result;
    }
}

?>
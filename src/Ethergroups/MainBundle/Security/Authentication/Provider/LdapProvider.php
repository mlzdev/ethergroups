<?php

namespace Ethergroups\MainBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Ethergroups\MainBundle\Security\LdapDataSource;
use Ethergroups\MainBundle\Security\Authentication\Token\LdapUserToken;

class LdapProvider implements AuthenticationProviderInterface
{
    private $userProvider;
	private $dataSource;

    public function __construct(UserProviderInterface $userProvider, LdapDataSource $dataSource) {
        $this->userProvider = $userProvider;
		$this->dataSource = $dataSource;
    }

    public function authenticate(TokenInterface $token) {
		// Ask the ldap data provider to authenticate our user
		$result = $this->dataSource->authenticateUser($token->getUsername(), $token->getCredentials());
// 		$result = true;
		
		// Did the authentication succeeded?
		if ($result) {
		    
		    // Ask the user provider to fetch the user record from database
		    $user = $this->userProvider->loadUserByUsername($token->getUsername());
		    
			// Save the user attributes in the user record
			$user->setAttributes($result);
			
			// update the db (and check, if the name from the user has changed)
			$this->userProvider->updateUser($user);
			
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
<?php

namespace HUBerlin\EPLiteProBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class LdapUserToken extends AbstractToken
{
    public function __construct($username, $password, $roles = array())
    {
        parent::__construct($roles);

        // If the user has roles, consider it authenticated
		$this->setUser($username);
		$this->credentials = $password;  
        $this->setAuthenticated(false);
    }
	
	public function getCredentials() {
		return $this->credentials;
	}
}

?>
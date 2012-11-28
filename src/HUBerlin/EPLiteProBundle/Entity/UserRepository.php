<?php

namespace HUBerlin\EPLiteProBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

class UserRepository implements UserProviderInterface {

    private $entityManager;

    public function __construct(EntityManager $em) {
        $this->entityManager = $em;
    }

    public function loadUserByUsername($username) {
        // Build the query to fetch the user
        $q = $this->entityManager
                ->getRepository('HUBerlin\EPLiteProBundle\Entity\User')
                ->createQueryBuilder('u')
                ->where('u.uid = :username')
                ->setParameter('username', $username)
                ->getQuery();

        try {
            // The Query::getSingleResult() method throws an exception
            // if there is no record matching the criteria.
            $user = $q->getSingleResult();
        } catch (NoResultException $e) {
            //throw new UsernameNotFoundException(sprintf('Benutzer "%s" scheint nicht in der lokalen Benutzerdatenbank zu existieren.', $username), null, 0, $e);
            $user = new User();
            $user->setUid($username);
            $user->setIsadmin(false);
        }

        return $user;
    }

    public function refreshUser(UserInterface $user) {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $class));
        }

        $result = $this->loadUserByUsername($user->getUsername());
        $result->setAttributes($user->getAttributes());
        return $result;
    }

    public function supportsClass($class) {
        return 'HUBerlin\EPLiteProBundle\Entity\User' === $class || is_subclass_of($class, 'HUBerlin\EPLiteProBundle\Entity\User');
    }

}

?>
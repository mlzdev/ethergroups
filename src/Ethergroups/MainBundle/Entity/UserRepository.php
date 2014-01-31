<?php

namespace Ethergroups\MainBundle\Entity;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Ethergroups\MainBundle\Helper\EtherpadLiteClient;
use Ethergroups\MainBundle\Entity\Groups;
use Symfony\Component\Translation\Translator;

class UserRepository implements UserProviderInterface {

    private $entityManager;
    private $etherpadlite;
    private $translator;
    private $logger;
    private $logUserData;

    public function __construct(EntityManager $em, EtherpadLiteClient $etherpadlite, Translator $translator, Logger $logger, $logUserData) {
        $this->entityManager = $em;
        $this->etherpadlite = $etherpadlite;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->logUserData = $logUserData;
    }

    public function loadUserByUsername($username, $activate = true) {
        // Build the query to fetch the user
        $q = $this->entityManager
                ->getRepository('Ethergroups\MainBundle\Entity\Users')
                ->createQueryBuilder('u')
                ->where('u.uid = :username')
                ->setParameter('username', $username)
                ->getQuery();

        try {
            // The Query::getSingleResult() method throws an exception
            // if there is no record matching the criteria.
            $user = $q->getSingleResult();
        } catch (NoResultException $e) {
//             throw new UsernameNotFoundException(sprintf('Benutzer "%s" scheint nicht in der lokalen Benutzerdatenbank zu existieren.', $username), null, 0, $e);
            $user = new Users();
            $user->setUid($username);
            $user->setIsadmin(false);
            $user->setIsenabled(true);
            $user->setIsactivated(false);
            $user->setPolicyagreed(false);
            $user->newUser = true;

            $this->logger->info('new user added to system'.(($this->logUserData)?','.$user->getAuthorid():''));
        }

        $authorid = $user->getAuthorid();
        if(empty($authorid)){
            try {
                $authorid = $this->etherpadlite->createAuthorIfNotExistsFor($user->getUid(), $user->getName());
                $user->setAuthorid($authorid->authorID);
            }
            catch (Exception $e) {
                throw new ErrorException(sprintf('Mapping failed with message: %s', $e->getMessage()));
            }
            $this->logger->info('user did get an authorID'.(($this->logUserData)?','.$user->getAuthorid():''));
        }
        
        // Is the user disabled?
        if($user->getIsenabled() === false) {
            throw new DisabledException(sprintf('Benutzer "%s" wurde vom System gesperrt', $username));
        }
        
        // Activate User (it's the first time, the user logs in)
        if($activate && $user->getIsactivated() === false) {
            $newgroupid = $this->etherpadlite->createGroup();
            
            $group = new Groups();
            $group->setName($this->translator->trans('firstgroupname'));
            $group->setGroupid($newgroupid->groupID);
            $group->setCreationDate(new \DateTime());
            $group->addUser($user);
            $this->entityManager->persist($group);
            
            $this->etherpadlite->createGroupPad($newgroupid->groupID, $this->translator->trans('firstpadname'), $this->translator->trans('firstpadtext'));
            
            $user->setIsactivated(true);

            $this->logger->info('user logged in for first time'.(($this->logUserData)?','.$user->getAuthorid():''));
        }

        return $user;
    }
    
    public function updateUser($user) {
        
        $commonName = $user->getCommonName(); 
        if(0 != strcmp($user->getName(), $commonName)) {
            $user->setName($commonName);
        }
        
        $id = $user->getId();
        if(empty($id)) {
            $this->entityManager->persist($user);
        }
        
        $user->setLasttimestamp(new \DateTime());
        
        $this->entityManager->flush();
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
        return 'Ethergroups\MainBundle\Entity\Users' === $class || is_subclass_of($class, 'Ethergroups\MainBundle\Entity\Users');
    }

}

?>
<?php

namespace Ethergroups\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Ethergroups\MainBundle\Entity\Groups;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Ethergroups\MainBundle\Entity\Users
 *
 * @ORM\Table(name="users")
 * @ORM\Entity
 * @UniqueEntity("uid")
 * 
 */
class Users implements UserInterface
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $uid
     *
     * @ORM\Column(name="uid", type="string", length=20, nullable=false, unique = true)
     */
    private $uid;
    
    /**
     * @var string $name
     * 
     * @ORM\Column(name="name", type="string", length=45, nullable=false)
     */
    private $name;
    
    /**
     * @var \DateTime $lasttimestamp
     * 
     * @ORM\Column(name="lasttimestamp", type="datetime", nullable=false)
     */
    private $lasttimestamp;
    
    /**
     * @var string $authorid
     * 
     * @ORM\Column(name="authorid", type="string", length=45, nullable=true)
     */
    private $authorid;
    
    /**
     * @var boolean $isenbaled
     * 
     * @ORM\Column(name="isenabled", type="boolean", nullable=false)
     */
    private $isenabled;
    
    /**
     * @var boolean $isactivated
     *
     * @ORM\Column(name="isactivated", type="boolean", nullable=false)
     */
    private $isactivated;
    
    /**
     * @var boolean $policyagreed
     * 
     * @ORM\Column(name="policyagreed", type="boolean", nullable=false)
     */
    private $policyagreed;
    
    /**
     * @var boolean $isadmin
     * 
     * @ORM\Column(name="isadmin", type="boolean", nullable=false)
     */
    private $isadmin;
    
    /**
     * @var string $language
     * 
     * @ORM\Column(name="language", type="string", length=5, nullable=true)
     */
    private $language;
    
    /**
     * @var Groups $groups
     *
     * @ORM\ManyToMany(targetEntity="Groups", indexBy="id", mappedBy="users")
     * @ORM\OrderBy({"name" = "ASC", "id" = "ASC"})
     */
    private $groups;
    
    /**
     * @var Invitation $invitations
     *
     * @ORM\OneToMany(targetEntity="Invitation", mappedBy="user")
     */
    private $invitations;

    // LDAP Attributes
    protected $attributes;
    
    public function setAttributes($attributes) {
        $this->attributes = $attributes;
        return $this;
    }
    
    public function getAttributes() {
        return $this->attributes;
    }
    
    public function getAttribute($attr) {
        if ($this->attributes && $this->attributes[$attr]) {
            return $this->attributes[$attr];
        } else {
            return NULL;
        }
    }
    
    public function getSingleAttribute($attr) {
        $attr = strtolower($attr);
        if ($this->attributes && array_key_exists($attr, $this->attributes) && array_key_exists("count", $this->attributes[$attr]) && $this->attributes[$attr]["count"] >= 1) {
            return $this->attributes[$attr][0];
        } else {
            return NULL;
        }
    }
    
    public function getCommonName() {
        if ($this->attributes) {
            return $this->getSingleAttribute("givenname") . " " . $this->getSingleAttribute("sn");
        } else {
            return $this->uid;
        }
    }
    
    public function getCommonNameShort() {
        if ($this->getSingleAttribute("givenname")) {
            $givenName = $this->getSingleAttribute("givenname");
            return $givenName[0] . ". " . $this->getSingleAttribute("sn");
        } else {
            return $this->uid;
        }
    }
    
    public function getMail() {
        $mail = $this->getSingleAttribute("mail");
        
        if (!$mail) {
            $mail = $this->getUid();
        }
        
        $mail .= '@hu-berlin.de';
    
        return $mail;
    }
    // end LDAP Attributes


    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return Role[] The user roles
     */
    public function getRoles() {
        if(isset($this->isadmin) && $this->isadmin) {
            return array('ROLE_ADMIN');
        }
        else {
            return array('ROLE_USER');
        }
    }
    
    public function getPassword() {
        return null;
    }
    
    public function getSalt() {
        return null;
    }

    public function getUsername() {
        return $this->uid;
    }
    
    public function eraseCredentials() {
    }
    
    public function equals(UserInterface $user) {
        return $this->uid === $user->getUsername();
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissions = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set uid
     *
     * @param string $uid
     * @return User
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    
        return $this;
    }

    /**
     * Get uid
     *
     * @return string 
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set isadmin
     *
     * @param boolean $isadmin
     * @return User
     */
    public function setIsadmin($isadmin)
    {
        $this->isadmin = $isadmin;
    
        return $this;
    }

    /**
     * Get isadmin
     *
     * @return boolean 
     */
    public function getIsadmin()
    {
        return $this->isadmin;
    }
    

    /**
     * Set name
     *
     * @param string $name
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set lasttimestamp
     *
     * @param \DateTime $lasttimestamp
     * @return User
     */
    public function setLasttimestamp($lasttimestamp)
    {
        $this->lasttimestamp = $lasttimestamp;
    
        return $this;
    }

    /**
     * Get lasttimestamp
     *
     * @return \DateTime 
     */
    public function getLasttimestamp()
    {
        return $this->lasttimestamp;
    }

    /**
     * Set isenabled
     *
     * @param boolean $isenabled
     * @return Users
     */
    public function setIsenabled($isenabled)
    {
        $this->isenabled = $isenabled;
    
        return $this;
    }

    /**
     * Get isenabled
     *
     * @return boolean 
     */
    public function getIsenabled()
    {
        return $this->isenabled;
    }
    
    /**
     * Set isactivated
     * 
     * @param boolean $isactivated
     * @return User
     */
    public function setIsactivated($isactivated)
    {
        $this->isactivated = $isactivated;
        
        return $this;
    }
    
    /**
     * Get isactivated
     *
     * @return boolean
     */
    public function getIsactivated()
    {
        return $this->isactivated;
    }

    /**
     * Add groups
     *
     * @param Groups $groups
     * @return Users
     */
    public function addGroup(Groups $groups)
    {
        $this->groups[] = $groups;
    
        return $this;
    }

    /**
     * Remove groups
     *
     * @param Groups $groups
     */
    public function removeGroup(Groups $groups)
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Set authorid
     *
     * @param string $authorid
     * @return Users
     */
    public function setAuthorid($authorid)
    {
        $this->authorid = $authorid;
    
        return $this;
    }

    /**
     * Get authorid
     *
     * @return string 
     */
    public function getAuthorid()
    {
        return $this->authorid;
    }

    /**
     * Set language
     *
     * @param string $language
     * @return Users
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    
        return $this;
    }

    /**
     * Get language
     *
     * @return string 
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set policyagreed
     *
     * @param boolean $policyagreed
     * @return Users
     */
    public function setPolicyagreed($policyagreed)
    {
        $this->policyagreed = $policyagreed;
    
        return $this;
    }

    /**
     * Get policyagreed
     *
     * @return boolean 
     */
    public function getPolicyagreed()
    {
        return $this->policyagreed;
    }

    /**
     * Add invitations
     *
     * @param \Ethergroups\MainBundle\Entity\Invitation $invitations
     * @return Users
     */
    public function addInvitation(\Ethergroups\MainBundle\Entity\Invitation $invitations)
    {
        $this->invitations[] = $invitations;
    
        return $this;
    }

    /**
     * Remove invitations
     *
     * @param \Ethergroups\MainBundle\Entity\Invitation $invitations
     */
    public function removeInvitation(\Ethergroups\MainBundle\Entity\Invitation $invitations)
    {
        $this->invitations->removeElement($invitations);
    }

    /**
     * Get invitations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInvitations()
    {
        return $this->invitations;
    }
}
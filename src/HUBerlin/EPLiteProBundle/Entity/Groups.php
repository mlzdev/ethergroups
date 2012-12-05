<?php

namespace HUBerlin\EPLiteProBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * HUBerlin\EPLiteProBundle\Entity\Group
 *
 * @ORM\Table(name="Groups")
 * @ORM\Entity
 * 
 */
class Groups
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
     * @var string $name
     * 
     * @ORM\Column(name="name", type="string", length=45, nullable=false)
     */
    private $name;
    
    /**
     * @var string $groupid
     * 
     * @ORM\Column(name="groupid", type="string", length=45, nullable=false)
     */
    private $groupid;
    
    /**
     * @var \DateTime $creationdate
     * 
     * @ORM\Column(name="creationdate", type="datetime", nullable=false)
     */
    private $creationdate;
    
    /**
     * @var User $user
     *
     * @ORM\ManyToMany(targetEntity="User", indexBy="name", inversedBy="groups")
     */
    private $user;
    

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
     * Set name
     *
     * @param string $name
     * @return Group
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
     * Set groupid
     *
     * @param string $groupid
     * @return Group
     */
    public function setGroupid($groupid)
    {
        $this->groupid = $groupid;
    
        return $this;
    }

    /**
     * Get groupid
     *
     * @return string 
     */
    public function getGroupid()
    {
        return $this->groupid;
    }

    /**
     * Set creationdate
     *
     * @param \DateTime $creationdate
     * @return Group
     */
    public function setCreationdate($creationdate)
    {
        $this->creationdate = $creationdate;
    
        return $this;
    }

    /**
     * Get creationdate
     *
     * @return \DateTime 
     */
    public function getCreationdate()
    {
        return $this->creationdate;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->user = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add user
     *
     * @param \HUBerlin\EPLiteProBundle\Entity\User $user
     * @return Groups
     */
    public function addUser(\HUBerlin\EPLiteProBundle\Entity\User $user)
    {
        $this->user[] = $user;
    
        return $this;
    }

    /**
     * Remove user
     *
     * @param \HUBerlin\EPLiteProBundle\Entity\User $user
     */
    public function removeUser(\HUBerlin\EPLiteProBundle\Entity\User $user)
    {
        $this->user->removeElement($user);
    }

    /**
     * Get user
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUser()
    {
        return $this->user;
    }
}
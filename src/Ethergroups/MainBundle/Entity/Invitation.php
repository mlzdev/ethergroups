<?php

namespace Ethergroups\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invitation
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Invitation
{

    /**
     * @var Users $user
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Users", inversedBy="invitations")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $user;

    /**
     * @var Groups $group
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Groups", inversedBy="invitations")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\OrderBy({"name" = "ASC", "id" = "ASC"})
     */
    private $group;

    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer")
     */
    private $created;


    /**
     * Set created
     *
     * @param integer $created
     * @return Invitation
     */
    public function setCreated($created)
    {
        $this->created = $created;
    
        return $this;
    }

    /**
     * Get created
     *
     * @return integer 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set user
     *
     * @param \Ethergroups\MainBundle\Entity\Users $user
     * @return Invitation
     */
    public function setUser(\Ethergroups\MainBundle\Entity\Users $user = null)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \Ethergroups\MainBundle\Entity\Users 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set group
     *
     * @param \Ethergroups\MainBundle\Entity\Groups $group
     * @return Invitation
     */
    public function setGroup(\Ethergroups\MainBundle\Entity\Groups $group = null)
    {
        $this->group = $group;
    
        return $this;
    }

    /**
     * Get group
     *
     * @return \Ethergroups\MainBundle\Entity\Groups 
     */
    public function getGroup()
    {
        return $this->group;
    }
}
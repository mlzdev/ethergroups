<?php

namespace HUBerlin\EPLiteProBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping\Index;

/**
 * HUBerlin\EPLiteProBundle\Entity\Pads
 *
 * @ORM\Table(name="Pads", indexes={@Index(name="padid_idx", columns={"padid"})})
 * @ORM\Entity
 * 
 */
class Pads
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
     * @var string $padid
     * 
     * @ORM\Column(name="padid", type="string", length=45, nullable=false)
     */
    private $padid;
    
    /**
     * @var string $pass
     * 
     * @ORM\Column(name="pass", type="string", length=45, nullable=false)
     */
    private $pass;
    

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
     * Set padid
     *
     * @param string $padid
     * @return Pads
     */
    public function setPadid($padid)
    {
        $this->padid = $padid;
    
        return $this;
    }

    /**
     * Get padid
     *
     * @return string 
     */
    public function getPadid()
    {
        return $this->padid;
    }

    /**
     * Set pass
     *
     * @param string $pass
     * @return Pads
     */
    public function setPass($pass)
    {
        $this->pass = $pass;
    
        return $this;
    }

    /**
     * Get pass
     *
     * @return string 
     */
    public function getPass()
    {
        return $this->pass;
    }
}
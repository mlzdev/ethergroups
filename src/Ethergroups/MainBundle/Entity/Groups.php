<?php

namespace Ethergroups\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping\OneToMany;

/**
 * Ethergroups\MainBundle\Entity\Group
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
     * @ORM\ManyToMany(targetEntity="User", indexBy="uid", inversedBy="groups")
     */
    private $user;
    
    /**
     * @var User $notifiedUser
     * 
     * @ORM\ManyToMany(targetEntity="User", indexBy="uid", mappedBy="notifiedGroups")
     */
    private $notifiedUser;
    
    /**
     * @var Pads $pads
     * 
     * @OneToMany(targetEntity="Pads", mappedBy="group")
     */
    private $pads;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $path;

    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir().'/'.$this->path;
    }

    public function getWebPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir().'/'.$this->path;
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../web'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return '/uploads/grouppix';
    }
    
public function upload()
{
    // the file property can be empty if the field is not required
    if (null === $this->file) {
        return;
    }

    // use the original file name here but you should
    // sanitize it at least to avoid any security issues
    $filename = str_replace(' ','_',$this->file->getClientOriginalName());

    // move takes the target directory and then the
    // target filename to move to
    $this->file->move(
        $this->getUploadRootDir(),
        $filename
    );

    // set the path property to the filename where you've saved the file
    $this->path = $filename;

    // clean up the file property as you won't need it anymore
    $this->file = null;
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
     * @param \Ethergroups\MainBundle\Entity\User $user
     * @return Groups
     */
    public function addUser(\Ethergroups\MainBundle\Entity\User $user)
    {
        if(!$this->user->containsKey($user->getUid())) {
            $this->user[] = $user;
            return $this;
        }
        else {
            return false;            
        }
    }

    /**
     * Remove user
     *
     * @param \Ethergroups\MainBundle\Entity\User $user
     */
    public function removeUser(\Ethergroups\MainBundle\Entity\User $user)
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
    
    public function getActivatedUser() {
        return $this->user->filter(function($elem) {
            if($elem->getIsActivated()) return $elem;
        });
    }

    /**
     * Set path
     *
     * @param string $path
     * @return Groups
     */
    public function setPath($path)
    {
        $this->path = $path;
    
        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Add pads
     *
     * @param \Ethergroups\MainBundle\Entity\Pads $pads
     * @return Groups
     */
    public function addPad(\Ethergroups\MainBundle\Entity\Pads $pads)
    {
        $this->pads[] = $pads;
    
        return $this;
    }

    /**
     * Remove pads
     *
     * @param \Ethergroups\MainBundle\Entity\Pads $pads
     */
    public function removePad(\Ethergroups\MainBundle\Entity\Pads $pads)
    {
        $this->pads->removeElement($pads);
    }

    /**
     * Get pads
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPads()
    {
        return $this->pads;
    }

    /**
     * Add notifiedUser
     *
     * @param \Ethergroups\MainBundle\Entity\User $notifiedUser
     * @return Groups
     */
    public function addNotifiedUser(\Ethergroups\MainBundle\Entity\User $notifiedUser)
    {
        $this->notifiedUser[] = $notifiedUser;
    
        return $this;
    }

    /**
     * Remove notifiedUser
     *
     * @param \Ethergroups\MainBundle\Entity\User $notifiedUser
     */
    public function removeNotifiedUser(\Ethergroups\MainBundle\Entity\User $notifiedUser)
    {
        $this->notifiedUser->removeElement($notifiedUser);
    }

    /**
     * Get notifiedUser
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getNotifiedUser()
    {
        return $this->notifiedUser;
    }
}

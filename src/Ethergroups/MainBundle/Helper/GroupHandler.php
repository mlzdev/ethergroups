<?php

namespace Ethergroups\MainBundle\Helper;

use Doctrine\ORM\EntityManager;

class GroupHandler {
    
    private $em;
    private $etherpadlite;
    
    public function __construct(EntityManager $em, EtherpadLiteClient $etherpadlite) {
        $this->em = $em;
        $this->etherpadlite = $etherpadlite;
    }

    /**
     * @param \Ethergroups\MainBundle\Entity\Groups $group
     * @param \Ethergroups\MainBundle\Entity\Users $user
     * @return string
     */
    public function removeUser($group, $user) {
        $groupUsers = $group->getUsers();
        
        if($groupUsers->containsKey($user->getUid())) { // Is the user in the group
             
            if($groupUsers->count()==1) { // Last user -> remove group
                $notice = $this->deleteGroup($group);
            }
            else { // Just remove user from group
                $group->removeUser($user);
                $notice = 'Sie wurden aus dieser Gruppe ausgetragen!';
            }
             
            $this->em->flush();
        }
        else {
            $notice = 'Sie sind in dieser Gruppe nicht eingetragen!';
        }
        
        return $notice;
    }

    /**
     * @param \Ethergroups\MainBundle\Entity\Groups $group
     * @return string
     */
    public function deleteGroup($group) {
	    
	    try {
	        $this->etherpadlite->deleteGroup($group->getGroupid());
	        $this->em->remove($group);
	         
	        return 'Gruppe gelöscht';
	         
	    }
	    catch (\Exception $e) {
	        if($e instanceof \InvalidArgumentException)
	        {
	            $this->em->remove($group);
	            return 'Gruppe existiert nicht auf dem Etherpad Lite Server. Gruppe gelöscht.';
	        }
	        else {
	            return 'Gruppe konnte nicht gelöscht werden';
	        }
	    }
	}
    
}
<?php

namespace HUBerlin\EPLiteProBundle\Helper;

use Doctrine\ORM\EntityManager;

class GroupHandler {
    
    private $em;
    private $etherpadlite;
    
    public function __construct(EntityManager $em, EtherpadLiteClient $etherpadlite) {
        $this->em = $em;
        $this->etherpadlite = $etherpadlite;
    }
    
    public function removeUser($group, $user) {
        $groupUser = $group->getUser();
        
        $notice = '';
        
        if($groupUser->containsKey($user->getUid())) { // Is the user in the group
             
            if($groupUser->count()==1) { // Last user -> remove group
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
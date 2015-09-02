<?php

namespace Ethergroups\MainBundle\Helper;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

class GroupHandler {
    
    private $em;
    private $etherpadlite;
    private $rootDir;
    private $logger;
    private $logUserData;
    
    public function __construct(EntityManager $em, EtherpadLiteClient $etherpadlite, $rootDir, Logger $logger, $logUserData) {
        $this->em = $em;
        $this->etherpadlite = $etherpadlite;
        $this->rootDir = $rootDir;
        $this->logger = $logger;
        $this->logUserData = $logUserData;
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
                $this->logger->info('remove user from group: is last user,'.$group->getGroupid().(($this->logUserData)?','.$user->getAuthorid():''));
                $notice = $this->deleteGroup($group);
            }
            else { // Just remove user from group
                $group->removeUser($user);
                $notice = 'Sie wurden aus dieser Gruppe ausgetragen!';
                $this->logger->info('user removed from group'.$group->getGroupid().(($this->logUserData)?','.$user->getAuthorid():''));
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
            $fs = new Filesystem();
            $now = date("YmdHis");
            $padIDs = $this->etherpadlite->listPads($group->getGroupid())->padIDs;

            foreach ($padIDs as $padID) {
                $padHTML = $this->etherpadlite->getHTML($padID)->html;
                $fs->dumpFile($this->rootDir.'/backups/'.$now.'$'.$padID.'.html', $padHTML);
            }

	        $this->etherpadlite->deleteGroup($group->getGroupid());
	        $this->em->remove($group);

            $this->logger->info('group deleted,'.$group->getGroupid(). ',"'.$group->getName().'"');
	         
	        return 'Gruppe gelöscht';
	         
	    }
	    catch (\Exception $e) {
	        if($e instanceof \InvalidArgumentException)
	        {
	            $this->em->remove($group);

                $this->logger->info('group doesn\t exist on etherpad server: groupd deleted,'.$group->getGroupid(). ',"'.$group->getName().'"');

                return 'Gruppe existiert nicht auf dem Etherpad Lite Server. Gruppe gelöscht.';
            }
	        else {
                $this->logger->info('group couldn\'t be deleted,'.$group->getGroupid(). ',"'.$group->getName().'"');
	            return 'Gruppe konnte nicht gelöscht werden';
	        }
	    }
	}
    
}
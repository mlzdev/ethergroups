<?php

namespace Ethergroups\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Ethergroups\MainBundle\Entity\Groups;
use Ethergroups\MainBundle\Entity\User;

class GroupController extends Controller {
    
    public function confirmAction(Request $request, $id) {
        if(!$id) {
            $this->get('session')
            ->getFlashBag()->set('notice', 'Bitte geben Sie eine gültige id an.');
            return $this->redirect($this->generateUrl('base'));
        }
        
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('EthergroupsMainBundle:Groups')->find($id);
        
        $user = $this->getUser();
        $groupRequests = $user->getGroupRequests();
        
        if($groupRequests->containsKey($id)) {
            $user->removeGroupRequest($group);
            $group->addUser($user);
            
            $em->flush();
            
            $this->get('session')
            ->getFlashBag()->set('notice', 'Gruppe angenommen.');
        }
        else {
            // TODO Gruppe gehört nicht zum User
        }
        
        return $this->redirect($this->generateUrl('base'));
    }
    
    public function declineAction(Request $request, $id) {
        if(!$id) {
            $this->get('session')
            ->getFlashBag()->set('notice', 'Bitte geben Sie eine gültige id an.');
            return $this->redirect($this->generateUrl('base'));
        }
        
        $em = $this->getDoctrine()->getManager();
        
        $group = $em->getRepository('EthergroupsMainBundle:Groups')->find($id);
        $user = $this->getUser();
        
        $groupRequests = $user->getGroupRequests();
        
        if($groupRequests->containsKey($id)) {
            $user->removeGroupRequest($group);
            $em->flush();
        }
        else {
            // TODO Gruppe gehört nicht zum User
        }

        $this->get('session')
        ->getFlashBag()->set('notice', 'Gruppe abgelehnt.');
        
        return $this->redirect($this->generateUrl('base'));
    }
}
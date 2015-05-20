<?php

namespace Ethergroups\MainBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Ethergroups\MainBundle\Entity\Groups;
use Ethergroups\MainBundle\Entity\Users;

class GroupController extends Controller {
    
    public function confirmAction(Request $request, $id) {
        $translator = $this->get('translator');

        if(!$id) {
            $this->get('session')
                ->getFlashBag()->set('notice', $translator->trans('invalidID', array(), 'notifications'));
            return $this->redirect($this->generateUrl('base'));
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('EthergroupsMainBundle:Groups')->find($id);

        $user = $this->getUser();
        $invitation = $em->find('EthergroupsMainBundle:Invitation', array('user'=>$user->getId(), 'group'=>$group->getId()));

        if(isset($invitation)) {

            $em->remove($invitation);
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
        $translator = $this->get('translator');

        if(!$id) {
            $this->get('session')
                ->getFlashBag()->set('notice', $translator->trans('invalidID', array(), 'notifications'));
            return $this->redirect($this->generateUrl('base'));
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        
        $group = $em->getRepository('EthergroupsMainBundle:Groups')->find($id);
        $user = $this->getUser();
        $invitation = $em->find('EthergroupsMainBundle:Invitation', array('user'=>$user->getId(), 'group'=>$group->getId()));
        
        if(isset($invitation)) {
            $em->remove($invitation);
            $em->flush();
        }
        else {
            // TODO Gruppe gehört nicht zum User
        }

        $this->get('session')
        ->getFlashBag()->set('notice', 'Gruppe abgelehnt.');
        
        return $this->redirect($this->generateUrl('base'));
    }

    public function isLastAction(Request $request, $id) {
        $translator = $this->get('translator');

        if(!$id) {
            $this->get('session')
                ->getFlashBag()->set('notice', $translator->trans('invalidID', array(), 'notifications'));
            return $this->redirect($this->generateUrl('base'));
        }

        $em = $this->getDoctrine()->getManager();

        /** @var $group Groups */
        $group = $em->getRepository('EthergroupsMainBundle:Groups')->find($id);
        $user = $this->getUser();

        $isLast = false;
        if($group->getUsers()->count() == 1) {
            $isLast = true;
        }

        return new JsonResponse(array('last'=>$isLast));
    }
}
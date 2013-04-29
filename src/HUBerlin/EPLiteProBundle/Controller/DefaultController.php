<?php

namespace HUBerlin\EPLiteProBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use HUBerlin\EPLiteProBundle\Entity\Groups;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller {
	public function indexAction(Request $request) {
		$etherpadlite = $this->get('etherpadlite');

		$user = $this->getUser();
		
		// TODO Sadly this is necessary, so that the localisation works
		//$request->setLocale($request->getPreferredLanguage(array('en', 'de')));
		
		$em = $this->getDoctrine()->getManager();

		$group = new Groups();
		$form = $this->createFormBuilder($group)
				->add('name', 'text',
						array('max_length' => 45,
						        'attr' => array('placeholder' => 'Name')))
				->getForm();

		if ($request->isMethod('POST')) {
			$form->bind($request);
			if ($form->isValid()) {

				$groupid = $etherpadlite->createGroup();

				$group->setGroupid($groupid->groupID);
				$group->setCreationDate(new \DateTime());
				$group->addUser($user);

				$em->persist($group);
				$em->flush();

				$this->get('session')->setFlash('notice', 'Gruppe erstellt!');

				return $this->redirect($this->generateUrl('base'));
			}
		}

		$groups = $user->getGroups();

		$this->updateCookie($etherpadlite, $groups, $user);

		return $this
				->render('HUBerlinEPLiteProBundle:Default:index.html.twig',
						array('form' => $form->createView(),
								'groups' => $groups));
	}

	public function deleteGroupAction($id = null) {
	    $eplite = $this->get('etherpadlite');
	    
	    $em = $this->getDoctrine()->getManager();
	    $group = $em->getRepository('HUBerlinEPLiteProBundle:Groups')->find($id);
	    
	    if(!$group) {
	        $this->get('session')
	        ->setFlash('notice', 'Diese Gruppe existiert nicht');
	        
	        return $this->redirect($this->generateUrl('base'));
	    }
	    
	    $grouphandler = $this->get('grouphandler'); 
	    $notice = $grouphandler->removeUser($group, $this->getUser());
	    
	    $this->get('session')
	    ->setFlash('notice', ''.$notice);
	    
	    return $this->redirect($this->generateUrl('base'));
	    
	}
	
	public function renameAction(Request $request, $id=0) {
	    if ($request->isMethod('POST') && $request->isXmlHttpRequest()) {
	        
	        $em = $this->getDoctrine()->getManager();
	        $group = $em->getRepository('HUBerlinEPLiteProBundle:Groups')
	        ->find($id);
	        
	        $newname = $request->request->get('groupname');
	        $group->setName($newname);
	        
	        $em->flush();
	        
	        return new JsonResponse(array('newname'=>$newname));
	    }
	}

	public function groupAction(Request $request, $id = null) {
	    if(!$id) {
	        $this->get('session')
	            ->setFlash('notice', 'Bitte geben Sie eine korrekte ID an');
	        return $this->redirect($this->generateUrl('base'));
	    }
	    
		$etherpadlite = $this->get('etherpadlite');

		$em = $this->getDoctrine()->getManager();
		$group = $em->getRepository('HUBerlinEPLiteProBundle:Groups')
				->find($id);

		$pad = new \stdClass();
		$pad->name = null;
		$form = $this->createFormBuilder($pad)
				->add('name', 'text',
						array('max_length' => 45, 'attr' => array('placeholder' => 'Name')))
				->getForm();

		if ($request->isMethod('POST')) {
			$form->bind($request);
			if ($form->isValid()) {
				try {
					$pad = $etherpadlite->createGroupPad($group->getGroupid(), $pad->name, null);
					$this->get('session')->setFlash('notice', 'Pad erstellt!');
				} catch (\Exception $e) {
					$this->get('session')->setFlash('notice', 'Padname existiert bereits!');
				}

				return $this->redirect($this->generateUrl('group', array('id' => $id)));
			}
		}

		$padIDs = $etherpadlite->listPads($group->getGroupid())->padIDs;

		$i = 0;
		$pads = array();
		foreach ($padIDs as $padID) {
			$pads[$i] = new \stdClass();
			$pads[$i]->id = $padID;
			$pads[$i]->name = explode('$', $padID);
			$pads[$i]->name = $pads[$i]->name[1];
			$pads[$i]->lastEdited = \date('d.m.Y H:i:s' ,substr_replace($etherpadlite->getLastEdited($padID)->lastEdited, "", -3));
			$i++;
		}
		
		$this->updateCookieIfNecessary();

		return $this
				->render('HUBerlinEPLiteProBundle:Default:group.html.twig',
						array('form' => $form->createView(), 'group' => $group,
								'pads' => $pads));
	}
	
	public function addUserAction ($id=0, Request $request) {
	    $em = $this->getDoctrine()->getManager();
	    if(!$id) {
	        $this->get('session')
	        ->setFlash('notice', 'Bitte geben Sie eine gültige id an.');
	        return $this->redirect($this->generateUrl('base'));
	    }
	    
	    $group = $em->getRepository('HUBerlinEPLiteProBundle:Groups')->find($id);
	    
	    if($group) {
	        if($request->isMethod('POST')) {
	            $username = $request->request->get('username');
	            if(!$username) {
	                $this->get('session')
	                ->setFlash('notice', 'Bitte geben Sie eine Benutzernamen an.');
	                
	                return $this->redirect($this->generateUrl('base'));
	            }
	            
	             $ldap = $this->get('ldap.data.provider');
	             $ldapuser = $ldap->getUserRecordExtended($username);
	             
	             if($ldapuser) {
	                 $userProvider = $this->get('ldap_user_provider');
	                 $user = $userProvider->loadUserByUsername($ldapuser['uid'][0], false);
	                 $user->setAttributes($ldapuser);
	                 $userProvider->updateUser($user);
	                 if(!$group->addUser($user)) {
	                     $this->get('session')
	                     ->setFlash('notice', 'Dieser Nutzer existiert bereits in der Gruppe!');
	                 }
	                 else {
                         $em->flush();
                         $this->get('session')
                         ->setFlash('notice', 'Nutzer zu der Gruppe hinzugefügt!');
	                 }
	             }
	             else {
	                 $this->get('session')
	                 ->setFlash('notice', 'Mehrere Nutzer gefunden. Bitte spezifizieren Sie Ihre Angabe.');
  	             }
	        }
	        else {
	            $this->get('session')
	            ->setFlash('notice', 'Diese Url kann nur über ein Formular angesprochen werden');
	        }
	    }
	    else {
	        $this->get('session')
	        ->setFlash('notice', 'Die angegebene Gruppe existiert nicht');
	    }
	    

	    
	    return $this->redirect($this->generateUrl('base'));
	}
	public function addPictureAction (Request $request, $id=0) {
	    $em = $this->getDoctrine()->getManager();
	    $group = $em->getRepository('HUBerlinEPLiteProBundle:Groups')
				->find($id);
	    $group->file = $request->files->get('file');
	    
	    $group->upload();
	    
	    $em->flush();
	    
	    $json = json_encode(array('url'=>$group->getWebPath(), 'success'=>true));
	    
	    return new Response($json);
	}
	
	public function removePictureAction(Request $request, $id=0) {
	    $em = $this->getDoctrine()->getManager();
	    $group = $em->getRepository('HUBerlinEPLiteProBundle:Groups')
	    ->find($id);
	    
	    unset($group->path);
	    
	    $em->flush();
	    
	    return new JsonResponse(array('success'=>true));
	}

	public function padAction($padid = 0, Request $request) {
        $etherpadlite = $this->get('etherpadlite');
		
	    $padsplit = $this->splitPadid($padid);
	    
	    $group = $this->getGroupFromGroupid($padsplit[0]);
	    $padname = $padsplit[1];

		$url = $this->container->getParameter('etherpadlite.url') . '/p/'
				. $padid;
		
		$ispublic = $etherpadlite->getPublicStatus($padid)->publicStatus;
		
		$this->updateCookieIfNecessary();
		
		$pad = new \stdClass();
		$pad->pass = null;
		$form = $this->createFormBuilder($pad)
		->add('pass', 'text',
		        array('max_length' => 20, 'attr' => array('placeholder' => 'Passwort')))
		        ->getForm();
		
		if ($request->isMethod('POST')) {
		    $form->bind($request);
		    if ($form->isValid()) {
		        try {
		            $pad = $etherpadlite->setPassword($padid, $pad->pass);
		            $this->get('session')->setFlash('notice', 'Passwort erstellt!');
		        } catch (\Exception $e) {
		            $this->get('session')->setFlash('notice', 'FEHLER! setPassword');
		        }
		        return $this->redirect($this->generateUrl('pad', array('padid' => $padid)));
		    }
		}
		
		try {
		    $isPasswordProtected = $etherpadlite->isPasswordProtected($padid)->isPasswordProtected;
		}
		catch (\Exception $e) {
		    $this->get('session')->setFlash('notice', 'FEHLER! isPasswordProtected');
		}
		
		return $this->render('HUBerlinEPLiteProBundle:Default:pad.html.twig',
						array('group' => $group, 'padid' => $padid, 'padname' => $padname, 'url' => $url, 'ispublic' => $ispublic, 'form' => $form->createView(), 'isPasswordProtected' => $isPasswordProtected));
	}
	
	public function deletePasswordAction($padid = 0) {
	    $eplite = $this->get('etherpadlite');
	    
	    if(!$padid) {
	        $this->get('session')
	        ->setFlash('notice', 'Bitte geben Sie eine gültige padid an.');
	        return $this->redirect($this->generateUrl('base'));
	    }
	    
	    try {
	        $eplite->setPassword($padid, null);
	        $this->get('session')->setFlash('notice', 'Passwort gelöscht');
	    }
	    catch (\Exception $e) {
	        $this->get('session')->setFlash('notice', 'FEHLER! setPublicStatus');
	    }
	    
	    return $this->redirect($this->generateUrl('pad', array('padid' => $padid)));
	}
	
	public function deletePadAction($padid = 0) {
	    $eplite = $this->get('etherpadlite');
	    
	    if(!$padid) {
	        $this->get('session')
	        ->setFlash('notice', 'Bitte geben Sie eine gültige padid an.');
	        return $this->redirect($this->generateUrl('base'));
	    }
	    
	    try {
	        $eplite->deletePad($padid);
	    }
	    catch (\Exception $e) {
	        $this->get('session')
	        ->setFlash('notice', 'Pad konnte nicht gelöscht werden');
	    }
	    
	    $padsplit = $this->splitPadid($padid);
	    $group = $this->getGroupFromGroupid($padsplit[0]);
	         
	    return $this->redirect($this->generateUrl('group', array('id'=>$group->getId())));
	}
	
	public function switchPublicAction($padid = 0) {
	    if(!$padid) {
	        $this->get('session')
	        ->setFlash('notice', 'Bitte geben Sie eine gültige padid an.');
	    }
	    
	    $etherpadlite = $this->get('etherpadlite');
	    
	    try {
	        $ispublic = $etherpadlite->getPublicStatus($padid)->publicStatus;
	    }
	    catch (\Exception $e) {
	        $this->get('session')
	        ->setFlash('notice', 'FEHLER! getPublicStatus');
	    }
	    
	    try {
	        $etherpadlite->setPublicStatus($padid, !$ispublic);
	    }
	    catch (\Exception $e) {
	        $this->get('session')->setFlash('notice', 'FEHLER! setPublicStatus');
	    }
	    
	    return $this->redirect($this->generateUrl('pad', array('padid' => $padid)));
	    
	    
	}
	
	public function changeLanguageAction(Request $request) {
	    if($request->isMethod('POST')) {
	        if($lang = $request->request->get('lang')) {
	            //$user = $this->get('security.context')->getToken()->getUser();
	            
	            // Is the selected lang available? 
	            $languages = array('de', 'en');
	            if(\in_array($lang, $languages)) {
	                /*
	                $em = $this->getDoctrine()->getManager();
	                $user->setLanguage($lang);
	                $em->flush();
	                */
	                $request->getSession()->set('_locale', $lang);
	            }
	        }
	    }
	    return $this->redirect($request->headers->get('referer'));
	}
	
	private function splitPadid($padid) {
	    return \preg_split('.\$.', $padid);
	}
	
	private function getGroupFromGroupid($groupid) {
	    $em = $this->getDoctrine()->getManager();
	    
	    return $em->getRepository('HUBerlinEPLiteProBundle:Groups')
	    ->findOneByGroupid($groupid);
	}

	private function updateCookie($etherpadlite=null, $groups = null, $user = null) {
	    if(!isset($etherpadlite)) {
	        $etherpadlite = $this->get('etherpadlite');
	    }
		if (!isset($user)) {
			$user = $this->getUser();
		}
		if (!isset($groups)) {
			$groups = $user->getGroups();
		}

		foreach ($groups as $group) {
			$groupIDs[$group->getGroupid()] = 0;
		}
		
		if(!isset($groupIDs)) return;

		$authorID = $user->getAuthorid();

		// TODO Needs a config
		$validUntil = time() + 5400;

		$sessionIDs = "";
		$sessions = $etherpadlite->listSessionsOfAuthor($authorID);
		if(!empty($sessions)) {
		    $sessions = get_object_vars($sessions);
		}
		
		/**
		 * Here we have the possibility to disallow the creation of new sessions by unsetting the sessionIDs, which the etherpad server already knows
		 * If one of the servers sessions isn't valid anymore, it will be deleted and afterwards a session for the group will be created
		 * TODO Maybe a Problem, because we can only set one cookie with one specific time until it is valid
		 * On the other hand, when the session doesn't exist on the server anymore, It won't be included in the cookie anymore
		 * TODO Test this! - Result: even if the sessions are not valid anymore, the server still sends them
		 *     A check, if they are valid? - done
		 */
		if (!empty($sessions)) {
		    $now = time();
			foreach ($sessions as $sessionID => $value) {
			    if($value->validUntil > $now) {
				    $sessionIDs .= $sessionID . ',';
    				if (array_key_exists($value->groupID, $groupIDs)) {
    					unset($groupIDs[$value->groupID]);
    				}
			    }
			    else {
 			        //$etherpadlite->deleteSession($sessionID); // Throws an error on server side o.O?
			    }
			}
		}

		foreach ($groupIDs as $groupID => $value) {
			try {
				$sessionID = $etherpadlite
						->createSession($groupID, $authorID, $validUntil);
			} catch (Exception $e) {
				echo "\n\ncreateSession failed with message: "
						. $e->getMessage();
			}
			$sessionIDs .= $sessionID->sessionID . ',';
		}
		
		$sessionIDs = substr($sessionIDs, 0, -1);

		// if we reach the etherpadlite server over https, then the cookie should only be delivered over ssl 
		//$ssl = (stripos($CFG->etherpadlite_url, 'https://')===0)?true:false;
		$ssl = false;

		// TODO needs a config for the URL
		setcookie("sessionID", $sessionIDs, $validUntil, '/', '.hu-berlin.de',
				$ssl); // Set a cookie
	}
	
	private function updateCookieIfNecessary() {
	    /**
	     * Always update Cookies
	     */
 	    if(empty($_COOKIE['sessionID'])) {
	        $this->updateCookie();
 	    }
	}

}

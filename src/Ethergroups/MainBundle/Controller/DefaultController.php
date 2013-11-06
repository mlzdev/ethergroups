<?php

namespace Ethergroups\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ethergroups\MainBundle\Entity\Groups;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Translation\IdentityTranslator;
use Ethergroups\MainBundle\Entity\Pads;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Filesystem\Filesystem;

class DefaultController extends Controller {
    
    
	/**
	 * Show all groups | Create a new group
	 * 
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction(Request $request) {
		$etherpadlite = $this->get('etherpadlite');
		$translator = $this->get('translator');

		$user = $this->getUser();
		
		// TODO Sadly this is necessary, so that the localisation works
		//$request->setLocale($request->getPreferredLanguage(array('en', 'de')));
		
		$em = $this->getDoctrine()->getManager();

		$group = new Groups();
		$form = $this->createFormBuilder($group)
				->add('name', 'text',
						array('max_length' => 45,
						    'attr' => array('placeholder' => $translator->trans('newgroup'))))
				->getForm();

		// Create new group
		if ($request->isMethod('POST')) {
			$form->bind($request);
			if ($form->isValid()) {

				$groupid = $etherpadlite->createGroup();

				$group->setGroupid($groupid->groupID);
				$group->setCreationDate(new \DateTime());
				$group->addUser($user);

				$em->persist($group);
				$em->flush();

				$this->get('session')->getFlashBag()->set('notice', $translator->trans('groupCreated', array(), 'notifications'));

				return $this->redirect($this->generateUrl('base'));
			}
		}

		$groups = $user->getGroups();
		$groupRequests = $user->getGroupRequests();

		$this->updateCookie($etherpadlite, $groups, $user);

		return $this
				->render('EthergroupsMainBundle:Default:index.html.twig',
						array('form' => $form->createView(),
								'groups' => $groups, 'groupRequests'=>$groupRequests));
	}

	/**
	 * Delete a group
	 * 
	 * @param number $id    group id
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function deleteGroupAction($id = null) {
	    $eplite = $this->get('etherpadlite');
	    $translator = $this->get('translator');
	    
	    $em = $this->getDoctrine()->getManager();
	    $group = $em->getRepository('EthergroupsMainBundle:Groups')->find($id);
	    
	    if(!$group) {
	        $this->get('session')
	        ->getFlashBag()->set('notice', $translator->trans('groupNotExistent', array(), 'notifications'));
	        
	        return $this->redirect($this->generateUrl('base'));
	    }
	    
	    $grouphandler = $this->get('grouphandler'); 
	    $notice = $grouphandler->removeUser($group, $this->getUser());
	    
	    $this->get('session')
	    ->getFlashBag()->set('notice', ''.$notice);
	    
	    return $this->redirect($this->generateUrl('base'));
	    
	}
	
	/**
	 * Rename a group and return Json with the new name
	 * 
	 * @param Request $request
	 * @param number $id    The group id
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function renameAction(Request $request, $id=0) {
	    if ($request->isMethod('POST') && $request->isXmlHttpRequest()) {
	        
	        $em = $this->getDoctrine()->getManager();
	        $group = $em->getRepository('EthergroupsMainBundle:Groups')
	        ->find($id);
	        
	        $newname = $request->request->get('groupname');
	        $group->setName($newname);
	        
	        $em->flush();
	        
	        return new JsonResponse(array('newname'=>$newname));
	    }
	}

	/**
	 * This function..
	 *     ..returns all pads from the group
	 *     ..adds a new pad to the group
	 *     ..can add a new pad via AJAX
	 * 
	 * @param Request $request
	 * @param number $id - The id of the group
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\JsonResponse|number|\Symfony\Component\HttpFoundation\Response
	 */
	public function groupAction(Request $request, $id = null) {
	    $translator = $this->get('translator');
	    if(!$id) {
	        $this->get('session')
	            ->getFlashBag()->set('notice', $translator->trans('invalidID', array(), 'notifications'));
	        return $this->redirect($this->generateUrl('base'));
	    }
	    
		$etherpadlite = $this->get('etherpadlite');
		$translator = $this->get('translator');

		$em = $this->getDoctrine()->getManager();
		$group = $em->getRepository('EthergroupsMainBundle:Groups')
				->find($id);

		$pad = new \stdClass();
		$pad->name = null;
		$form = $this->createFormBuilder($pad)
				->add('name', 'text',
						array('max_length' => 45,
						        'attr' => array('placeholder' => $translator->trans('newpad'))
		))
				->getForm();

		if ($request->isMethod('POST')) {
			$form->bind($request);
			if ($form->isValid()) {
			    $errors = false;
				try {
				    $name = str_replace(array('/', '\\'), '', $pad->name);
					$pad = $etherpadlite->createGroupPad($group->getGroupid(), $name, null);
					$this->get('session')->getFlashBag()->set('notice', $translator->trans('padCreated', array(), 'notifications'));
				} catch (\Exception $e) {
					$this->get('session')->getFlashBag()->set('notice', $translator->trans('padnameExists', array(), 'notifications'));
					$errors = true;
				}
			}

			if($request->isXmlHttpRequest()) {
			    if(!$errors) {
			        $newpad = new \stdClass();
			        $newpad->id = $pad->padID;
			        $newpad->name = explode('$', $newpad->id);
			        $newpad->name = $newpad->name[1];
			        $lastEdited = substr_replace($etherpadlite->getLastEdited($newpad->id)->lastEdited, "", -3);
			        $newpad->lastEdited = $this->getLastEdited($lastEdited);
			
			        return new JsonResponse(array('success'=>true, 'data'=>$this->renderView('EthergroupsMainBundle:Default:newpad.html.twig', array('pad'=>$newpad))));
			    }
			    else {
			        return new JsonResponse(array('success'=>false, 'data'=>$this->renderView('EthergroupsMainBundle::layout.html.twig')));
			    }
			}
			else {
			    return $this->redirect($this->generateUrl('group', array('id' => $id)));
			}
			
		}

		$padIDs = $etherpadlite->listPads($group->getGroupid())->padIDs;

		$i = 0;
		$pads = array();
		$now = new \DateTime();
		foreach ($padIDs as $padID) {
			$pads[$i] = new \stdClass();
			$pads[$i]->id = $padID;
			$pads[$i]->name = explode('$', $padID);
			$pads[$i]->name = $pads[$i]->name[1];
			
			// Different strings depending on when the pad was last edited
			$lastEdited = substr_replace($etherpadlite->getLastEdited($padID)->lastEdited, "", -3);
			$pads[$i]->lastEdited = $this->getLastEdited($lastEdited, $now);
			
			$i++;
		}
		
		// Sortieren
		\usort($pads, function($a, $b) {
	        return \strnatcasecmp($a->name, $b->name);
	    });
		
		$this->updateCookieIfNecessary();

		return $this
				->render('EthergroupsMainBundle:Default:group.html.twig',
						array('form' => $form->createView(), 'group' => $group,
								'pads' => $pads));
	}
	
	
	/**
	 * Calculate a string, depending on when the pad was last edited
	 * 
	 * @param long $lastEdited    The time in long, when the pad was last edited
	 * @param \DateTime $now       Current time
	 * @return string
	 */
	public function getLastEdited($lastEdited, $now=null) {
	    if(!isset($now)) $now = new \DateTime();
	    
	    $translator = $this->get('translator');
	    
	    $diff = $now->diff(new \DateTime('@'.$lastEdited));
	    if($diff->days == 0) { // today
	        $lastEdited = $translator->trans('today').' '.\date('H:i' ,$lastEdited);
	    }
	    else if($diff->days == 1) { // yesterday
	        $lastEdited = $translator->trans('yesterday').' '.\date('H:i' ,$lastEdited);
	    }
	    else if($diff->days <= 7) { // till one week
	        $lastEdited = $translator->trans('daysago', array('%days%'=>$diff->days)).' '.\date('H:i' ,$lastEdited);
	    }
	    else {
	        $lastEdited = \date('d.m.y H:i' ,$lastEdited);
	    }
	    return $lastEdited;
	}
	
	/**
	 * Add a user to a group
	 * 
	 * @param number $id    group id
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function addUserAction ($id=0, Request $request) {
	    $em = $this->getDoctrine()->getManager();
	    $translator = $this->get('translator');
	    if(!$id) {
	        $this->get('session')
	        ->getFlashBag()->set('notice', $translator->trans('invalidID', array(), 'notifications'));
	        return $this->redirect($this->generateUrl('base'));
	    }
	    
	    $group = $em->getRepository('EthergroupsMainBundle:Groups')->find($id);
	    
	    if($group) {
	        if($request->isMethod('POST')) {
	            $username = $request->request->get('username');
	            if(!$username) {
	                $this->get('session')
	                ->getFlashBag()->set('notice', $translator->trans('noUsernameGiven', array(), 'notifications'));
	                
	                return $this->redirect($this->generateUrl('base'));
	            }
	            
	             $ldap = $this->get('ldap.data.provider');
	             $ldapuser = $ldap->getUserRecordExtended($username);
	             
	             if($ldapuser[0]) {
	                 $ldapuser = $ldapuser[1];
	                 $userProvider = $this->get('ldap_user_provider');
	                 $user = $userProvider->loadUserByUsername($ldapuser['uid'][0], false);
	                 $user->setAttributes($ldapuser);
	                 $userProvider->updateUser($user);
	                 
	                 // Is the user already a member of this group?
	                 if($group->getUser()->contains($user)) {
	                     $this->get('session')
	                     ->getFlashBag()->set('notice', $translator->trans('userExistsInGroup', array(), 'notifications'));
	                 }
	                 else {
	                     // Make the request
	                     $user->addGroupRequest($group);
                         $em->flush();
                         
                         //Write a mail to the added user
                         $message = \Swift_Message::newInstance()
                             ->setSubject($translator->trans('requestmailsubject'))
                             ->setFrom($this->container->getParameter('mailer_noreply_address'))
                             ->setTo($user->getMail())
                             ->setBody(
                                 $this->renderView(
                                     'EthergroupsMainBundle:Mails:userrequest.txt.twig',
                                     array('group' => $group, 'user' => $this->getUser())
                                 )
                             );
                         $this->get('mailer')->send($message);
                         
                         $this->get('session')
                         ->getFlashBag()->set('notice', $translator->trans('userAdded', array(), 'notifications'));
	                 }
	             }
	             else {
	                 if($ldapuser[1]==0) {
	                     $this->get('session')
	                     ->getFlashBag()->set('notice', $translator->trans('noUserFound', array(), 'notifications'));
	                 }
	                 else {
	                     $this->get('session')
	                     ->getFlashBag()->set('notice', $translator->trans('multipleUserFound', array(), 'notification'));
	                 }
	                 
  	             }
	        }
	        else {
	            $this->get('session')
	            ->getFlashBag()->set('notice', $translator->trans('POSTOnly', array(), 'notifications'));
	        }
	    }
	    else {
	        $this->get('session')
	        ->getFlashBag()->set('notice', $translator->trans(groupNotExistent));
	    }
	    

	    
	    return $this->redirect($this->generateUrl('base'));
	}
	
	/**
	 * Add a picture to a group and return the url to the picture
	 * 
	 * @param Request $request
	 * @param number $id    group id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function addPictureAction (Request $request, $id=0) {
	    $em = $this->getDoctrine()->getManager();
	    $group = $em->getRepository('EthergroupsMainBundle:Groups')
				->find($id);
	    $group->file = $request->files->get('file');
	    
	    $group->upload();
	    
	    $em->flush();
	    
	    $json = json_encode(array('url'=>$group->getWebPath(), 'success'=>true));
	    
	    return new Response($json);
	}
	
	/**
	 * Remove the picture of a group
	 * 
	 * @param Request $request
	 * @param number $id    group id
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function removePictureAction(Request $request, $id=0) {
	    $em = $this->getDoctrine()->getManager();
	    $group = $em->getRepository('EthergroupsMainBundle:Groups')
	    ->find($id);
	    
	    unset($group->path);
	    
	    $em->flush();
	    
	    return new JsonResponse(array('success'=>true));
	}

	/**
	 * Show the pad | Add Password
	 * 
	 * @param number $padid    pad id
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 */
	public function padAction($padid = 0, Request $request) {
	    $em = $this->getDoctrine()->getManager();
	    $translator = $this->get('translator');
        $etherpadlite = $this->get('etherpadlite');
		
	    $padsplit = $this->splitPadid($padid);
	    
	    $group = $this->getGroupFromGroupid($padsplit[0]);
	    $padname = $padsplit[1];

		$url = $this->container->getParameter('etherpadlite.url') . '/p/'
				. $padid;
		
		$ispublic = $etherpadlite->getPublicStatus($padid)->publicStatus;
		
		$this->updateCookieIfNecessary();
		
		$repository = $em->getRepository('EthergroupsMainBundle:Pads');
		$pad = $repository->findOneBy(array('padid'=>$padid));
		if (!$pad) {
		    $pad = new Pads();
		    $pad->setPadid($padid);
		    $pad->setGroup($group);
		}
		
		$form = $this->createFormBuilder($pad)
		->add('pass', 'text',
		        array('max_length' => 20, 'attr' => array('placeholder' => 'Passwort')))
		        ->getForm();
		
		if ($request->isMethod('POST')) {
		    $form->bind($request);
		    if ($form->isValid()) {
		        try {
		            $padEP = $etherpadlite->setPassword($padid, $pad->getPass());
		            $id = $pad->getId();
   		            if(empty($id)) {
   		                $em->persist($pad);
		            }
		            $em->flush();
		            
		            $this->get('session')->getFlashBag()->set('notice', $translator->trans('passCreated', array(), 'notifications'));
		        } catch (\Exception $e) {
		            $this->get('session')->getFlashBag()->set('notice', $translator->trans('passError', array(), 'notifications'));
		        }
		        return $this->redirect($this->generateUrl('pad', array('padid' => $padid)));
		    }
		}
		
		try {
		    $isPasswordProtected = $etherpadlite->isPasswordProtected($padid)->isPasswordProtected;
		}
		catch (\Exception $e) {
		    $this->get('session')->getFlashBag()->set('notice', $translator->trans('passCheckError', array(), 'notifications'));
		}
		
		return $this->render('EthergroupsMainBundle:Default:pad.html.twig',
						array('group' => $group, 'pad' => $pad, 'padid' => $padid, 'padname' => $padname, 'url' => $url, 'ispublic' => $ispublic, 'form' => $form->createView(), 'isPasswordProtected' => $isPasswordProtected));
	}
	
	/**
	 * Remove the password from the pad
	 * 
	 * @param number $padid     The pad id
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function deletePasswordAction($padid = 0) {
	    $translator = $this->get('translator');
	    $eplite = $this->get('etherpadlite');
	    
	    if(!$padid) {
	        $this->get('session')
	        ->getFlashBag()->set('notice', $translator->trans('invalidID', array(), 'notifications'));
	        return $this->redirect($this->generateUrl('base'));
	    }
	    
	    try {
	        $eplite->setPassword($padid, null);
	        $this->removePasswordFromDatabase($padid);
	        $this->get('session')->getFlashBag()->set('notice', $translator->trans('passRemoved', array(), 'notifications'));
	    }
	    catch (\Exception $e) {
	        $this->get('session')->getFlashBag()->set('notice', $translator->trans('removePassError', array(), 'notifications'));
	    }
	    
	    return $this->redirect($this->generateUrl('pad', array('padid' => $padid)));
	}
	
	private function removePasswordFromDatabase($padid) {
	    $em = $this->getDoctrine()->getManager();
	    $repository = $em->getRepository('EthergroupsMainBundle:Pads');
	    $pad = $repository->findOneBy(array('padid'=>$padid));
	    $em->remove($pad);
	    $em->flush();
	}
	
	/**
	 * Remove a pad
	 * 
	 * @param number $padid    pad id
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function deletePadAction($padid = 0) {
	    $translator = $this->get('translator');
	    $eplite = $this->get('etherpadlite');
	    
	    if(!$padid) {
	        $this->get('session')
	        ->getFlashBag()->set('notice', $translator->trans('invalidID', array(), 'notifications'));
	        return $this->redirect($this->generateUrl('base'));
	    }
	    
	    try {
	        $eplite->deletePad($padid);
	        $this->removePasswordFromDatabase($padid);
	    }
	    catch (\Exception $e) {
	        $this->get('session')
	        ->getFlashBag()->set('notice', $translator->trans('removePadError', array(), 'notifications'));
	    }
	    
	    $padsplit = $this->splitPadid($padid);
	    $group = $this->getGroupFromGroupid($padsplit[0]);
	         
	    return $this->redirect($this->generateUrl('group', array('id'=>$group->getId())));
	}
	
	/**
	 * Switch the public status of a pad
	 * 
	 * @param number $padid    the pad id
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function switchPublicAction($padid = 0) {
	    if(!$padid) {
	        $this->get('session')
	        ->getFlashBag()->set('notice', $translator->trans('invalidID', array(), 'notifications'));
	    }
	    
	    $etherpadlite = $this->get('etherpadlite');
	    
	    try {
	        $ispublic = $etherpadlite->getPublicStatus($padid)->publicStatus;
	    }
	    catch (\Exception $e) {
	        // TODO better error handling
	        $this->get('session')
	        ->getFlashBag()->set('notice', 'FEHLER! getPublicStatus');
	    }
	    
	    try {
	        $etherpadlite->setPublicStatus($padid, !$ispublic);
	    }
	    catch (\Exception $e) {
	        // TODO better error handling
	        $this->get('session')->getFlashBag()->set('notice', 'FEHLER! setPublicStatus');
	    }
	    
	    return $this->redirect($this->generateUrl('pad', array('padid' => $padid)));
	    
	    
	}
	
	/**
	 * Change the Language of the whole site
	 * 
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
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
	
	/**
	 * This function shows the policy and sets the user attribute for it, according to the users decision to aggree, or disagree it.
	 */
	public function policyAction(Request $request) {
	    $em = $this->getDoctrine()->getManager();
	    $translator = $this->get('translator');
	    
	    $user = $this->getUser();
	    
	    $form = $this->createFormBuilder($user)
	        ->add('policyagreed', null, array('required'=> false, 'label'=>$translator->trans('agreepolicy')))
	        ->add('confirm', 'submit', array('label'=>$translator->trans('confirm')))
	        ->add('cancel', 'submit', array('label'=>$translator->trans('cancel')))    
	        ->getForm();
	    
	    $form->handleRequest($request);
	    
	    if ($form->isValid()) {
	        if($form->get('confirm')->isClicked()) { 
	            if($user->getPolicyAgreed()) {
	                $em->flush();
	                return $this->redirect($this->generateUrl('base'));
	            }
	            else {
	                $this->get('session')->getFlashBag()->set('notice', $translator->trans('policynotagreed'));
	                return $this->redirect($this->generateUrl('policy'));
	            }
	        }
	        else {
	            return $this->redirect($this->generateUrl('logout'));
	        }
	    }
	    
	    return $this->render('EthergroupsMainBundle:Default:policy.html.twig', array('form'=>$form->createView()));
	}
        
        /**
         * This function shows the admin area
         */
        public function adminAction(Request $request) {
            $translator = $this->get('translator');
            $fs = new Filesystem();
            
            $texts = new \stdClass();
            $texts->de = $translator->trans('logininfo', array(), 'frontpage', 'de');
            $texts->en = $translator->trans('logininfo', array(), 'frontpage', 'en');
            
            $form = $this->createFormBuilder($texts)
                    ->add('de', 'textarea')
                    ->add('en', 'textarea')
                    ->add('save', 'submit')
                    ->getForm();
            
            $form->handleRequest($request);

            if ($form->isValid()) {
                $textDE = "logininfo: |\n  ".preg_replace("/\n/", "\n  ", $texts->de);
                $textEN = "logininfo: |\n  ".preg_replace("/\n/", "\n  ", $texts->en);
                $dir = dirname(__DIR__)."/Resources/translations/";
                
                $fs->dumpFile($dir."frontpage.de.yml", $textDE);
                $fs->dumpFile($dir."frontpage.en.yml", $textEN);
            }
            
            return $this->render('EthergroupsMainBundle:Default:admin.html.twig', array('form'=>$form->createView()));
        }
	
	/**
	 * Split the pad id into group id and pad name
	 * 
	 * @param string $padid
	 * @return string[] 0: The group id 1: The pad name
	 */
	private function splitPadid($padid) {
	    return \preg_split('.\$.', $padid);
	}
	
	/**
	 * Get the group from database from the group id
	 * 
	 * @param string $groupid
	 */
	private function getGroupFromGroupid($groupid) {
	    $em = $this->getDoctrine()->getManager();
	    
	    return $em->getRepository('EthergroupsMainBundle:Groups')
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

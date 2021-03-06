<?php

namespace Ethergroups\MainBundle\Controller;

use Doctrine\ORM\EntityManager;
use Entities\User;
use Ethergroups\MainBundle\Entity\Invitation;
use Ethergroups\MainBundle\Helper\EtherpadLiteClient;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ethergroups\MainBundle\Entity\Groups;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
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
        $logger = $this->get('statLogger');
        $logUserData = $this->container->getParameter('logUserData');

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

                $logger->info("new group added,".$group->getGroupid().',"'.$group->getName().'"'.(($logUserData)?','.$this->getUser()->getAuthorid():''));

				return $this->redirect($this->generateUrl('base'));
			}
		}

		$groups = $user->getGroups();
        $invitations = $user->getInvitations();

		list($response, $firstExpiration) = $this->updateCookie($etherpadlite, $groups, $user);

		return $this->render(
            'EthergroupsMainBundle:Default:index.html.twig',
            array('form' => $form->createView(), 'groups' => $groups, 'invitations'=>$invitations, 'firstExpiration'=>$firstExpiration),
            $response
        );
	}

    public function renewCookieAction(Request $request) {
        /** @var $response Response */
        list($response, $firstExpiresIn) = $this->updateCookie();

        $response->setContent($firstExpiresIn);

        return $response;
    }

	/**
	 * Remove a user from a group
	 *
	 * @param number $id    group id
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function deleteGroupAction($id = null) {
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
     * @param int|number $id The group id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
	public function renameAction(Request $request, $id=0) {
	    if ($request->isMethod('POST') && $request->isXmlHttpRequest()) {

	        $em = $this->getDoctrine()->getManager();
            $group = $em->getRepository('EthergroupsMainBundle:Groups')
	        ->find($id);

            $oldname = $group->getName();
	        $newname = $request->request->get('groupname');
	        $group->setName($newname);

	        $em->flush();

            $logger = $this->get('statLogger');
            $logUserData = $this->container->getParameter('loguserdata');
            $logger->info('group renamed,'.$group->getGroupid().',"'.$oldname.'->'.$newname.'"'.(($logUserData)?','.$this->getUser()->getAuthorid():''));

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
     * @throws \Exception throws an exception, if an unexpected error occurs with the etherpad server
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\JsonResponse|number|\Symfony\Component\HttpFoundation\Response
     */
	public function groupAction(Request $request, $id = null) {
        $logger = $this->get('statLogger');
        $logUserData = $this->container->getParameter('loguserdata');
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
				} catch (\InvalidArgumentException $e) {
					$this->get('session')->getFlashBag()->set('notice', $translator->trans('padnameExists', array(), 'notifications'));
					$errors = true;
				} catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->set('notice', $translator->trans('error', array(), 'notifications'));
                    throw $e;
                }
			}

            // if ajax, send back a json response w/ all info needed
			if($request->isXmlHttpRequest()) {
			    if(!$errors) {
			        $newpad = new \stdClass();
			        $newpad->id = $pad->padID;
			        $newpad->name = explode('$', $newpad->id);
			        $newpad->name = $newpad->name[1];
			        $lastEdited = substr_replace($etherpadlite->getLastEdited($newpad->id)->lastEdited, "", -3);
			        $newpad->lastEdited = $this->getLastEdited($lastEdited);

                    $logger->info('new pad added,'.$newpad->id.',"'.$newpad->name.'"'.(($logUserData)?','.$this->getUser()->getAuthorid():''));

			        return new JsonResponse(array('success'=>true, 'data'=>$this->renderView('EthergroupsMainBundle:Default:newpad.html.twig', array('pad'=>$newpad))));
			    }
			    else {
                    $logger->info('new pad failed: padname exists already,'.$group->getGroupId().',"'.$name.'"'.(($logUserData)?','.$this->getUser()->getAuthorid():''));

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

		$response = $this->updateCookieIfNecessary();

		return $this->render(
            'EthergroupsMainBundle:Default:group.html.twig',
            array('form' => $form->createView(), 'group' => $group, 'pads' => $pads),
            $response
        );
	}


	/**
	 * Calculate a string, depending on when the pad was last edited
	 *
	 * @param long $lastEdited    The time in long, when the pad was last edited
	 * @param \DateTime $now       Current time
	 * @return string
	 */
	public function getLastEdited($lastEdited, $now=null) {
	    if(!isset($now)) $now = new \DateTime(\date('Y-m-d'));

	    $translator = $this->get('translator');

	    $diff = $now->diff(new \DateTime(\date('Y-m-d',$lastEdited)));
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
     * @param int|number $id group id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
	public function addUserAction ($id=0, Request $request) {
	    $em = $this->getDoctrine()->getManager();
        /** @var $logger \Symfony\Bridge\Monolog\Logger*/
        $logger = $this->get('statLogger');
        $logUserData = $this->container->getParameter('loguserdata');
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

                // Get the user record
	             $ldap = $this->get('ldap.data.provider');
	             $ldapuser = $ldap->getUserRecordExtended($username);

                // Was the user found?
	             if($ldapuser[0]) {
	                 $ldapuser = $ldapuser[1];
	                 $userProvider = $this->get('ldap_user_provider');
	                 $user = $userProvider->loadUserByUsername($ldapuser['uid'][0], false);
	                 $user->setAttributes($ldapuser);
	                 $userProvider->updateUser($user);

	                 // Is the user already a member of this group?
	                 if($group->getUsers()->contains($user) || $group->getInvitations()->contains($user)) {
	                     $this->get('session')
	                     ->getFlashBag()->set('notice', $translator->trans('userExistsInGroup', array(), 'notifications'));
                         $logger->info('add user failed: user already exists in group,'.$group->getGroupid().(($logUserData)?',"'.$username.'",'.$this->getUser()->getAuthorid():''));
	                 }
	                 else {
	                     // Add an Invitation
                         $invitation = new Invitation();
                         $invitation->setUser($user);
                         $invitation->setGroup($group);
                         $invitation->setCreated(time());
                         $em->persist($invitation);
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
                         $logger->info('user added to group,'.$group->getGroupid().(($logUserData)?',"'.$username.'",'.$this->getUser()->getAuthorid():''));
	                 }
	             }
	             else { // User not found, or mutliple users found
	                 if($ldapuser[1]==0) {
	                     $this->get('session')
	                     ->getFlashBag()->set('notice', $translator->trans('noUserFound', array(), 'notifications'));
	                 }
	                 else {
	                     $this->get('session')
	                     ->getFlashBag()->set('notice', $translator->trans('multipleUserFound', array(), 'notification'));
	                 }
                     $logger->info('add user failed: user not found, or multiple users found,'.$group->getGroupid().(($logUserData)?',"'.$username.'",'.$this->getUser()->getAuthorid():''));
  	             }
	        }
	        else { // if request method wasn't POST
	            $this->get('session')
	            ->getFlashBag()->set('notice', $translator->trans('POSTOnly', array(), 'notifications'));
	        }
	    }
	    else {
	        $this->get('session')
	        ->getFlashBag()->set('notice', $translator->trans('groupNotExistent', array(), 'notifications'));
            $logger->info('add user failed: group not existent,'.$id.(($logUserData)?','.$this->getUser()->getAuthorid():''));
	    }



	    return $this->redirect($this->generateUrl('base'));
	}

    /**
     * Remove a user form a group
     * @param Request $request
     * @param int     $id groupid
     * @param null    $username
     * @return RedirectResponse
     */
    public function removeUserAction(Request $request, $id=0, $username=null) {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var $logger \Symfony\Bridge\Monolog\Logger*/
        $logger = $this->get('statLogger');
        $logUserData = $this->container->getParameter('loguserdata');
        $translator = $this->get('translator');

        if(!$id || !$username) {
            // TODO: seperate between id and username
            $this->get('session')
                ->getFlashBag()->set('notice', $translator->trans('invalidID', array(), 'notifications'));
            return $this->redirect($this->generateUrl('base'));
        }

        $group = $em->getRepository('EthergroupsMainBundle:Groups')->find($id);
        $user = $em->getRepository('EthergroupsMainBundle:Users')->findBy(array('uid'=>$username));
        $groupUsers = $group->getUsers();

        if($group && $user) {
            $user = reset($user);
            if($groupUsers->containsKey($username)) {

                $invitation = new Invitation();
                $invitation->setUser($user);
                $invitation->setGroup($group);
                $invitation->setCreated(time());

                $em->persist($invitation);

                $group->removeUser($user);

                $em->flush();

                //Write a mail to the removed user
                $message = \Swift_Message::newInstance()
                    ->setSubject($translator->trans('removemailsubject'))
                    ->setFrom($this->container->getParameter('mailer_noreply_address'))
                    ->setTo($user->getMail())
                    ->setBody(
                        $this->renderView(
                            'EthergroupsMainBundle:Mails:userremove.txt.twig',
                            array('group' => $group, 'user' => $this->getUser())
                        )
                    );
                $this->get('mailer')->send($message);
            }
            else {
                // TODO: User is not in this group
            }

        }
        else {
            // TODO: seperate between group and user
            $this->get('session')->getFlashBag()->set('notice', $translator->trans('groupNotExistent', array(), 'notifications'));
        }

        return $this->redirect($this->generateUrl('base'));
    }

    /**
     * Add a picture to a group and return the url to the picture
     *
     * @param Request $request
     * @param int|number $id group id
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

        $logger = $this->get('statLogger');
        $logUserData = $this->container->getParameter('loguserdata');
        $logger->info('picture added,'.$group->getGroupid().(($logUserData)?','.$this->getUser()->getAuthorid():''));

	    return new Response($json);
	}

    /**
     * Remove the picture of a group
     *
     * @param Request $request
     * @param int|number $id group id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
	public function removePictureAction(Request $request, $id=0) {
	    $em = $this->getDoctrine()->getManager();
	    $group = $em->getRepository('EthergroupsMainBundle:Groups')
	    ->find($id);

	    unset($group->path);

	    $em->flush();

        $logger = $this->get('statLogger');
        $logUserData = $this->container->getParameter('loguserdata');
        $logger->info('picture removed,'.$group->getGroupid().(($logUserData)?','.$this->getUser()->getAuthorid():''));

	    return new JsonResponse(array('success'=>true));
	}
	
	public function searchUserAjaxAction (Request $request) {
	    if($request->isXmlHttpRequest()) {
	        $ldap = $this->get('ldap.data.provider');

            $translator = $this->get('translator');
	        
	        $term = $request->query->get('term');
	        $ldapuser = $ldap->getUserRecordExtended($term, null, true);
	        
	        $user = array();
            $i = 0;
            $maxUser = 8;
	        foreach ($ldapuser as $key => $value) {
	            if($key !== 'count') {
	                $user[$key]['value'] = $value['mail']['0'];
	                $user[$key]['label'] = $value['cn']['0'].' ('.$value['mail']['0'].')';
	            }
                if($i >= $maxUser) break;
                $i++;
	        }

            $count = $ldapuser['count'] - $maxUser;
            if($count > 0) {
                $user[] = array(
                    'value'=>'xxxcount',
                    'label'=>$translator->trans('usersfound', array('%amount%'=>$count))
                );
	        }
	        
	        $json = new JsonResponse($user);
	         
	        return $json; 
	    }
	    else {
	        $this->get('session')
	        ->setFlash('notice', 'Diese Url darf nur über ein Ajax Request aufgerufen werden');
	        return $this->redirect($this->generateUrl('base'));
	    }
	}


    /**
     * Show the pad | Add Password
     *
     * @param int|number $padid pad id
     * @param Request $request
     * @throws \Exception throws exceptions, if etherpad server is not working
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
	public function padAction($padid = 0, Request $request) {
	    $em = $this->getDoctrine()->getManager();
	    $translator = $this->get('translator');
        $etherpadlite = $this->get('etherpadlite');
        $logger = $this->get('statLogger');
        $logUserData = $this->container->getParameter('loguserdata');

	    $padsplit = $this->splitPadid($padid);

	    $group = $this->getGroupFromGroupid($padsplit[0]);
	    $padname = $padsplit[1];

        $url = $this->container->getParameter('etherpadlite.url').'/p/';
        // If readonly, get readonly ID and initialise $url properly
        if (!$this->container->getParameter('readonly')) {
            $url .= $padid;
        }
        else {
            $readonlyid = $etherpadlite->getReadOnlyID($padid)->readOnlyID;
            $url .= $readonlyid;
        }

		$ispublic = $etherpadlite->getPublicStatus($padid)->publicStatus;

		$response = $this->updateCookieIfNecessary();

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

        // if method of request is post, then we want to add a password to the pad
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

                    $logger->info('password added,'.$pad->getPadid().(($logUserData)?','.$this->getUser()->getAuthorid():''));

		        } catch (\Exception $e) {
		            $this->get('session')->getFlashBag()->set('notice', $translator->trans('passError', array(), 'notifications'));
                    $logger->info('add password failure,'.$pad->getPadid().(($logUserData)?','.$this->getUser()->getAuthorid():''));
		        }
		        return $this->redirect($this->generateUrl('pad', array('padid' => $padid)));
		    }
		}

		try {
		    $isPasswordProtected = $etherpadlite->isPasswordProtected($padid)->isPasswordProtected;
		}
		catch (\Exception $e) {
		    $this->get('session')->getFlashBag()->set('notice', $translator->trans('passCheckError', array(), 'notifications'));
            throw $e;
		}

		return $this->render(
            'EthergroupsMainBundle:Default:pad.html.twig',
            array('group' => $group, 'pad' => $pad, 'padid' => $padid, 'padname' => $padname, 'url' => $url, 'ispublic' => $ispublic,
                'form' => $form->createView(), 'isPasswordProtected' => $isPasswordProtected),
            $response
        );
	}

    /**
     * Remove the password from the pad
     *
     * @param int|number $padid The pad id
     * @throws \Exception etherpad server is not working
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
	public function deletePasswordAction($padid = 0) {
	    $translator = $this->get('translator');
	    $eplite = $this->get('etherpadlite');
        $logger = $this->get('statLogger');
        $logUserData = $this->container->getParameter('loguserdata');

	    if(!$padid) {
	        $this->get('session')
	        ->getFlashBag()->set('notice', $translator->trans('invalidID', array(), 'notifications'));
	        return $this->redirect($this->generateUrl('base'));
	    }

	    try {
	        $eplite->setPassword($padid, null);
	        $this->removePasswordFromDatabase($padid);
	        $this->get('session')->getFlashBag()->set('notice', $translator->trans('passRemoved', array(), 'notifications'));
            $logger->info('password removed,'.$padid.(($logUserData)?','.$this->getUser()->getAuthorid():''));
	    }
	    catch (\Exception $e) {
	        $this->get('session')->getFlashBag()->set('notice', $translator->trans('removePassError', array(), 'notifications'));
            throw $e;
	    }

	    return $this->redirect($this->generateUrl('pad', array('padid' => $padid)));
	}

	private function removePasswordFromDatabase($padid) {
	    $em = $this->getDoctrine()->getManager();
	    $repository = $em->getRepository('EthergroupsMainBundle:Pads');
	    $pad = $repository->findOneBy(array('padid'=>$padid));
        if(isset($pad)) $em->remove($pad);
	    $em->flush();
	}

    /**
     * Remove a pad
     *
     * @param int|number $padid pad id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
	public function deletePadAction($padid = 0) {
	    $translator = $this->get('translator');
	    $eplite = $this->get('etherpadlite');
		$fs = new Filesystem();
		$root = $this->get('kernel')->getRootDir();
        $logger = $this->get('statLogger');
        $logUserData = $this->container->getParameter('loguserdata');

	    if(!$padid) {
	        $this->get('session')
	        ->getFlashBag()->set('notice', $translator->trans('invalidID', array(), 'notifications'));
	        return $this->redirect($this->generateUrl('base'));
	    }

	    try {
			$now = date("YmdHis");
			$padHTML = $eplite->getHTML($padid)->html;
			$fs->dumpFile($root.'/backups/'.$now.'$'.$padid.'.html', $padHTML);
	        $eplite->deletePad($padid);
	        $this->removePasswordFromDatabase($padid);
            $logger->info('pad removed,'.$padid.(($logUserData)?','.$this->getUser()->getAuthorid():''));
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
     * @param int|number $padid the pad id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
	public function switchPublicAction($padid = 0) {
        $translator = $this->get('translator');
	    if(!$padid) {
	        $this->get('session')
	        ->getFlashBag()->set('notice', $translator->trans('invalidID', array(), 'notifications'));
	    }

	    $etherpadlite = $this->get('etherpadlite');
        $logger = $this->get('statLogger');
        $logUserData = $this->container->getParameter('loguserdata');

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
            $logger->info('public status to '.((!$ispublic)?'true':'false').','.$padid.(($logUserData)?','.$this->getUser()->getAuthorid():''));
	    }
	    catch (\Exception $e) {
	        // TODO better error handling
	        $this->get('session')->getFlashBag()->set('notice', 'FEHLER! setPublicStatus');
	    }

	    return $this->redirect($this->generateUrl('pad', array('padid' => $padid)));


	}

	/**
	 * Change the Language of the whole site w/o login site
	 *
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function changeLanguageAction(Request $request) {
        $logUserData = $this->container->getParameter('loguserdata');
        return $this->changeLanguage($request, $logUserData);
    }

    /**
     * Change language of the login site
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function changeLanguageLoginAction(Request $request) {
        return $this->changeLanguage($request, false);
    }

    private function changeLanguage(Request $request, $logUserData) {
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

                    $logger = $this->get('statLogger');
                    $logger->info('language changed to '.$lang.(($logUserData)?','.$this->getUser()->getAuthorid():''));
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

            $dir = $this->get('kernel')->getRootDir()."/Resources/translations/";

            $fs->dumpFile($dir."frontpage.de.yml", $textDE);
            $fs->dumpFile($dir."frontpage.en.yml", $textEN);
        }

        return $this->render('EthergroupsMainBundle:Default:admin.html.twig', array('form'=>$form->createView()));
    }

    /**
     * Clears the cache from the admin menu
     *
     * @return RedirectResponse a redirect response to the admin page
     */
    public function clearCacheAction() {
        $this->execute('cache:clear');

        return $this->redirect($this->generateUrl('admin'));
    }

    /**
     * Execute a command line command
     *
     * @param $command the command to be executed
     * @return int an error code
     */
    private function execute($command)
    {
        $app = new \Symfony\Bundle\FrameworkBundle\Console\Application($this->get('kernel'));
        $app->setAutoExit(false);

        $input = new \Symfony\Component\Console\Input\StringInput($command);
        $output = new \Symfony\Component\Console\Output\NullOutput();

        $error = $app->run($input, $output);

        return $error;
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

    /**
     * Updates the sessionID cookie, to authorise against the etherpad server
     * This method tries to create sessions as few as possible
     *
     * @param EtherpadLiteClient $etherpadlite
     * @param Groups             $groups
     * @param User               $user
     * @return array [0] Response new response object with cookie set<br>
     *          [1] int the time in seconds when the first session expires
     */
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

		$validUntil = time() + $this->container->getParameter('cookie_expires');
        $firstExpiration = $validUntil;

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
                    // Add to sessionIDs
				    $sessionIDs .= $sessionID . ',';

                    if($value->validUntil < $firstExpiration) {
                        $firstExpiration = $value->validUntil;
                    }

    				if (array_key_exists($value->groupID, $groupIDs)) {
    					unset($groupIDs[$value->groupID]);
    				}
			    }
			    else {
						$etherpadlite->deleteSession($sessionID);
			    }
			}
            $firstExpiration = $firstExpiration - $now;
            if($firstExpiration<0) $firstExpiration = 0;
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

        $response = new Response();

        // Set cookie
        $cookie = new Cookie("sessionID", $sessionIDs, 0, '/', $this->container->getParameter('cookie_domain'), $ssl, false);
        $response->headers->setCookie($cookie);

        return array($response, $firstExpiration);
	}

	private function updateCookieIfNecessary() {
	    /**
	     * Update only, if cookie is not present
         * This will happen, if the cookie is outdated (or the user removes it manually)
         *
         * TODO: Find a way to check if the presented cookie is valid for this site,
         * because it could conflict with other etherpad instances on the same domain.
         *
	     */
 	    if(empty($_COOKIE['sessionID'])) {
            $response = $this->updateCookie();
	        return $response[0];
 	    }
        return new Response();
	}

}

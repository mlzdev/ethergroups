<?php

namespace HUBerlin\EPLiteProBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use HUBerlin\EPLiteProBundle\Entity\Groups;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller {
	public function indexAction(Request $request) {
		$etherpadlite = $this->get('etherpadlite');

		$user = $this->getUser();

		$em = $this->getDoctrine()->getManager();

		$group = new Groups();
		$form = $this->createFormBuilder($group)
				->add('name', 'text',
						array('attr' => array('placeholder' => 'Name')))
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

				return $this->redirect($this->generateUrl('hu_base'));
			}
		}

		$groups = $user->getGroups();

		$this->updateCookie($etherpadlite, $groups, $user);

		return $this
				->render('HUBerlinEPLiteProBundle:Default:index.html.twig',
						array('form' => $form->createView(),
								'groups' => $groups));
	}

	public function deleteGroupAction($groupID = null) {
	}

	public function groupAction(Request $request, $id = null) {
		$etherpadlite = $this->get('etherpadlite');

		$em = $this->getDoctrine()->getManager();
		$group = $em->getRepository('HUBerlinEPLiteProBundle:Groups')
				->find($id);

		$pad = new \stdClass();
		$pad->name = null;
		$form = $this->createFormBuilder($pad)
				->add('name', 'text',
						array('attr' => array('placeholder' => 'Name')))
				->getForm();

		if ($request->isMethod('POST')) {
			$form->bind($request);
			if ($form->isValid()) {

				try {
					$pad = $etherpadlite
							->createGroupPad($group->getGroupid(), $pad->name,
									null);
					$this->get('session')->setFlash('notice', 'Pad erstellt!');
				} catch (\Exception $e) {
					$this->get('session')
							->setFlash('notice', 'Padname existiert bereits!');
				}

				return $this
						->redirect(
								$this
										->generateUrl('hu_group',
												array('id' => $id)));
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
			$i++;
		}

		return $this
				->render('HUBerlinEPLiteProBundle:Default:group.html.twig',
						array('form' => $form->createView(), 'group' => $group,
								'pads' => $pads));
	}

	public function padAction($padid = 0) {
		//         $etherpadlite = $this->get('etherpadlite');

		$url = $this->container->getParameter('etherpadlite.url') . '/p/'
				. $padid;
		
		$this->updateCookieIfNecessary();

		return $this
				->render('HUBerlinEPLiteProBundle:Default:pad.html.twig',
						array('name' => $padid, 'url' => $url));
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
		$validUntil = time() + 10000;

		$sessionIDs = "";
		$sessions = $etherpadlite->listSessionsOfAuthor($authorID);
		$sessions = get_object_vars($sessions);
		
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
			        $etherpadlite->deleteSession($sessionID);
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
// 	    if(empty($_COOKIE['sessionID'])) {
	        $this->updateCookie();
// 	    }
	}

}

<?php

namespace HUBerlin\EPLiteProBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name = 'Timo')
    {
        return $this->render('HUBerlinEPLiteProBundle:Default:index.html.twig', array('name' => $name));
    }
}

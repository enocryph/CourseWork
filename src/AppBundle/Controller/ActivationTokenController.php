<?php
/**
 * Created by PhpStorm.
 * User: qwerty
 * Date: 13.01.17
 * Time: 0:52
 */

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\ActivationToken;
use AppBundle\Form\ActivationTokenType;
/**
 * ActivationToken controller.
 *
 * @Route("/activation")
 */
class ActivationTokenController extends Controller
{
    /**
     * @Route("/{token}", name="user_activate")
     */
    public function indexAction($token)
    {
        $em = $this->getDoctrine()->getManager();
        $tokenEntry = $em->getRepository('AppBundle:ActivationToken')
            ->findOneBy(array('token' => $token));
        if (!$tokenEntry) {
            return $this->redirectToRoute('homepage');
        }
        $userObject = $em->getRepository('AppBundle:User')
            ->findOneBy(array('email' => $tokenEntry->getEmail()));

        $userObject->setEnabled(true);
        $em->persist($userObject);

        $em->remove($tokenEntry);
        $em->flush();
        $this->addFlash('success', 'Account activated.');
        return $this->redirectToRoute('homepage');
    }
}
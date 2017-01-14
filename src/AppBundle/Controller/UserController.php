<?php
/**
 * Created by PhpStorm.
 * User: qwerty
 * Date: 12.01.17
 * Time: 23:04
 */

namespace AppBundle\Controller;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Entity\ActivationToken;
use AppBundle\Entity\PasswordResetToken;
use AppBundle\Entity\User;
use AppBundle\Form\ResetPasswordType;
use AppBundle\Form\UserType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use AppBundle\Service\TokenGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserController extends Controller
{
    /**
     * @Route("/register", name="user_registration")
     */
    public function newAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // Encode the new users password
            $encoder = $this->get('security.password_encoder');
            $password = $encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            $user->setEnabled(false);
            // Set their role
            $user->setRole('ROLE_USER');

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);


            $token = new TokenGenerator();
            $activationToken = new ActivationToken();
            $activationToken->setToken($token->generateToken());
            $activationToken->setEmail($user->getEmail());

            $em->persist($activationToken);
            $em->flush();

            $message = \Swift_Message::newInstance(null)
                ->setSubject('Welcome')
                ->setFrom('sashka.k777@gmail.com')
                ->setTo('sashka.k777@gmail.com')
                ->setBody(
                    $this->renderView('email_register.html.twig',
                        array(
                            'username' => $user->getUsername(),
                            'email' => $user->getEmail(),
                            'url' => $this->generateUrl('user_activate', array(
                                'token' => $activationToken->getToken()
                            ),
                                UrlGeneratorInterface::ABSOLUTE_URL),
                        )
                    ),
                    'text/html'
                );
            $this->get('mailer')->send($message);
            return $this->redirectToRoute('homepage');
        }

        return $this->render(
            'register.html.twig',
            array('form' => $form->createView())
        );
    }
}
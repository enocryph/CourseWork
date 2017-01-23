<?php

namespace AppBundle\Controller;
use AppBundle\Form\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\ActivationToken;
use AppBundle\Entity\PasswordResetToken;
use AppBundle\Entity\User;
use AppBundle\Form\ResetPasswordType;
use AppBundle\Form\RegisterType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use AppBundle\Service\TokenGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request)
    {
        $helper = $this->get('security.authentication_utils');

        return $this->render(
            'User_login.html.twig',
            array(
                'last_username' => $helper->getLastUsername(),
                'error'         => $helper->getLastAuthenticationError(),
            )
        );
    }

    /**
     * @Route("/login_check", name="security_login_check")
     */
    public function loginCheckAction()
    {

    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {

    }

    /**
     * @Route("/register", name="registration")
     */
    public function registerAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);

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
                ->setFrom('courseworkproductscatalog@gmial.com')
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView('Email_registerMessage.html.twig',
                        array(
                            'username' => $user->getName(),
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
            $this->addFlash('success', 'Success. Check your E-mail for activation link');
            return $this->redirectToRoute('homepage');
        }
        $errors = (string) $form->getErrors(true);
        return $this->render(
            'User_registration.html.twig',
            array('form' => $form->createView(),
                'errors'=>$errors)
        );
    }

    /**
     * @Route("/passwordreset/{token}", name="user_reset_password")
     */
    public function resetPasswordAction(Request $request, $token)
    {

        $resetForm = $this->createForm(ResetPasswordType::class);
        $resetForm->handleRequest($request);

        $em = $this->getDoctrine()->getManager();
        $tokenEntry = $em->getRepository('AppBundle:PasswordResetToken')
            ->findOneBy(array('token' => $token));

        if(!$tokenEntry){
            $this->addFlash('error', 'Provided token is not valid');
            return $this->redirectToRoute('homepage');
        }
        if ($resetForm->isSubmitted() && $resetForm->isValid()) {
            $userObject = $em->getRepository('AppBundle:User')
                ->findOneBy(array('id' => $tokenEntry->getUserId()));

            $em->remove($tokenEntry);

            $plainPassword = $resetForm['plainPassword']['first']->getData();
            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($userObject, $plainPassword);
            $userObject->setPassword($encoded);
            $em->persist($userObject);

            $em->flush();
            $this->addFlash('success', 'Password changed.');
            return $this->redirectToRoute('homepage');
        }
        $errors = (string) $resetForm->getErrors(true);
        return $this->render('User_passwordReset.html.twig', array(
            'token' => $token,
            'resetForm' => $resetForm->createView(),
            'errors'=>$errors
        ));
    }
}
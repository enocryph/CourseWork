<?php
/**
 * Created by PhpStorm.
 * User: qwerty
 * Date: 14.01.17
 * Time: 18:40
 */

namespace AppBundle\Controller;


use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Email;
use AppBundle\Entity\PasswordResetToken;
use AppBundle\Form\EmailResetPasswordType;
use AppBundle\Service\TokenGenerator;
/**
 * PasswordResetToken controller.
 *
 * @Route("passwordreset")
 */
class PasswordResetTokenController extends Controller
{
    /**
     * @Route("/", name="forgotpassword")
     */
    public function forgotPasswordAction(Request $request)
    {
        $form = $this->createForm(EmailResetPasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // check the if the user exists in the User database
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('AppBundle:User')
                ->findOneBy(array('email' => $form->getData()));
            // if user is not registered redirect back to input email form
            if (!$user){
                $this->addFlash('error', 'Provided email not registered');
                return $this->redirectToRoute('forgotpassword');
            }

            $token = new TokenGenerator();
            $passwordResetToken = new PasswordResetToken();
            $passwordResetToken->setToken($token->generateToken());

            $passwordResetToken->setUserId($user->getId());

            $em->persist($passwordResetToken);
            $em->flush();

            $message = \Swift_Message::newInstance(null)
                ->setSubject('Password reset request')
                ->setFrom('courseworkproductscatalog@gmial.com')
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView('Email_passwordReset.html.twig',
                        array(
                            'url' => $this->generateUrl('user_reset_password', array(
                                'token' => $passwordResetToken->getToken()
                            ),
                                UrlGeneratorInterface::ABSOLUTE_URL),
                        )
                    ),
                    'text/html'
                );
            $this->get('mailer')->send($message);

            $this->addFlash('success', 'Please check your email for reset password link.');
            return $this->redirectToRoute('homepage');
        }
        return $this->render('User_forgottenPassword.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{token}", name="user_reset_password")
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
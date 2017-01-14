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
 * @Route("/passwordreset")
 */
class PasswordResetTokenController extends Controller
{
    /**
     * Creates a new PasswordResetToken entity.
     *
     * @Route("/", name="forgotpassword")
     * @Method({"GET", "POST"})
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
                ->setSubject('Password reset request from URLShortener.loc')
                ->setFrom('sashka.k777@gmail.com')
                ->setTo('sashka.k777@gmail.com')
                ->setBody(
                    $this->renderView('email_password_reset.html.twig',
                        array(
                            'url' => $this->generateUrl('user_activate', array(
                                'token' => $passwordResetToken->getToken()
                            ),
                                UrlGeneratorInterface::ABSOLUTE_URL),
                    'text/html'
                );
            $this->get('mailer')->send($message);

            $this->addFlash('success', 'Please check your email for reset password link.');
            return $this->redirectToRoute('homepage');
        }

        return $this->render('forgot_password.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
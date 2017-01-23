<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * User controller.
 *
 * @Route("user")
 */
class UserController extends Controller
{
    /**
     * Lists all user entities.
     *
     * @Route("/", name="user_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('AppBundle:User')->findAll();

        return $this->render('User_index.html.twig', array(
            'users' => $users,
        ));
    }

    /**
     * Lists all users entities.
     *
     * @Route("/ajax", name="user_ajax")
     * @Method("GET")
     */
    public function ajaxUserAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repository=$em->getRepository('AppBundle:User');
        $page=$request->get('page');
        $perpage=$request->get('perpage');
        $count=0;
        if ($request->get('sortbyfield'))
        {
            $users = $repository->createQueryBuilder('u')
                ->orderBy('u.'.$request->get('sortbyfield'),$request->get('order'))
                ->getQuery()->getResult();
        } elseif ($request->get('filterbyfield')) {

            $users = $repository->createQueryBuilder('u')
                ->where('u.' . $request->get('filterbyfield') . ' LIKE :pattern')
                ->setParameter('pattern', '%'. $request->get('pattern') . '%')
                ->getQuery()->getResult();

        } else {
            $users = $repository->findAll();
        }
        $responseusers = array();
        if (isset($users)) {
            $count=count($users);
            $users=array_slice($users,($page-1)*$perpage,$perpage);
            foreach ($users as $product) {
                $responseusers[] = array(
                    'id'=>$product->getId(),
                    'name'=>$product->getName(),
                    'email'=>$product->getEmail(),
                    'enabled'=>$product->getEnabled(),
                    'role'=>$product->getRole(),
                );
            }
        }

        return new JsonResponse(array('users'=>$responseusers,'count'=>$count));
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/{id}", name="user_show")
     * @Method("GET")
     */
    public function showAction(User $user)
    {
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('User_show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/{id}/edit", name="user_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, User $user)
    {
        $deleteForm = $this->createDeleteForm($user);
        $editForm = $this->createForm('AppBundle\Form\UserType', $user);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_index', array('id' => $user->getId()));
        }

        return $this->render('User_edit.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a user entity.
     *
     * @Route("/{id}", name="user_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, User $user)
    {
        $form = $this->createDeleteForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush($user);
        }

        return $this->redirectToRoute('user_index');
    }

    /**
     * Creates a form to delete a user entity.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_delete', array('id' => $user->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


}

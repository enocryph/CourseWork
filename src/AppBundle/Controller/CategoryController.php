<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\RecursiveCategoryIterator;
/**
 * Category controller.
 *
 * @Route("category")
 */
class CategoryController extends Controller
{
    /**
     * Lists all category entities.
     *
     * @Route("/", name="category_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $root_categories = $em->getRepository("AppBundle:Category")->findBy(array('parent' => null));

        $collection = new ArrayCollection($root_categories);
        $category_iterator = new RecursiveCategoryIterator($collection);
        $recursive_iterator = new \RecursiveIteratorIterator($category_iterator, \RecursiveIteratorIterator::SELF_FIRST);
        $categories = $em->getRepository('AppBundle:Category')->findAll();

        return $this->render('Category_index.html.twig', array(
            'categories' => $categories,
        ));
    }
    /**
     * @Route("/ajax/{id}", name="category_ajax")
     * @Method("GET")
     */
    public function categoryAjaxAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        if ($id == 'null') {
            $categories = $em->getRepository("AppBundle:Category")->findBy(array('parent'=>null));
        }
        else {
            $categories = $em->getRepository("AppBundle:Category")->findBy(array('parent'=>$id));
        }

        $responseCategories=array();

        foreach ($categories as $category) {
            $children=$em->getRepository("AppBundle:Category")->findBy(array('parent'=>$category->getId()));
            $responseCategories[]=array(
                'id'=>$category->getId(),
                'title'=>$category->getTitle(),
                'children'=>($children != null),
            );
        }

        return new JsonResponse($responseCategories);
    }
    /**
     * Creates a new category entity.
     *
     * @Route("/new", name="category_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $category = new Category();
        $form = $this->createForm('AppBundle\Form\CategoryType', $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush($category);

            return $this->redirectToRoute('category_index', array('id' => $category->getId()));
        }

        return $this->render('Category_new.html.twig', array(
            'category' => $category,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a category entity.
     *
     * @Route("/{id}", name="category_show")
     * @Method("GET")
     */
    public function showAction(Category $category)
    {
        $deleteForm = $this->createDeleteForm($category);

        return $this->render('Category_show.html.twig', array(
            'category' => $category,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing category entity.
     *
     * @Route("/{id}/edit", name="category_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Category $category)
    {
        $deleteForm = $this->createDeleteForm($category);
        $editForm = $this->createForm('AppBundle\Form\CategoryType', $category);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            if ($category->getParent()!=null) {
                if ($category->getParent()->getId() == $category->getId()) {
                    $category->setParent(null);
                }
            }
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('category_index', array('id' => $category->getId()));
        }

        return $this->render('Category_edit.html.twig', array(
            'category' => $category,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a category entity.
     *
     * @Route("/{id}", name="category_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Category $category)
    {
        $form = $this->createDeleteForm($category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($category);
            $em->flush($category);
        }

        return $this->redirectToRoute('category_index');
    }

    /**
     * Creates a form to delete a category entity.
     *
     * @param Category $category The category entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Category $category)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('category_delete', array('id' => $category->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

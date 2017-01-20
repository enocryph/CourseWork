<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Category;
use AppBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\RecursiveCategoryIterator;

/**
 * @Route("catalog")
 */

class CatalogController extends Controller
{
    /**
     * @Route("/", name="catalog_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $categories = $em->getRepository("AppBundle:Category")->findBy(array('parent' => null));
        $products = $em->getRepository('AppBundle:Product')->findAll();
        return $this->render('catalog_index.html.twig', array(
            'categories' => $categories,
            'products' => $products,
        ));
    }
    /**
     * @Route("/ajax/category/{id}", name="category_ajax")
     * @Method("GET")
     */
    public function categoryAjaxAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        if ($id == 'null') {
            $categories = $em->getRepository("AppBundle:Category")->findBy(array('parent'=>null));
        }
        else {
            $requestCategory = $em->getRepository("AppBundle:Category")->find($id);
            $categories=$requestCategory->getChildren();
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
}
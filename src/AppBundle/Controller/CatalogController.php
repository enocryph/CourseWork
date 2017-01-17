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
 * Category controller.
 *
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
        $requestCategory = $em->getRepository("AppBundle:Category")->find($id);
        $categories=$requestCategory->getChildren();



        $responseCategories=array();

        foreach ($categories as $category) {
            $responseCategories[]=array(
              'id'=>$category->getId(),
              'title'=>$category->getTitle(),
            );
        }
        $em=$em->getRepository("AppBundle:Product");
        $responseProducts=array();
        $collection = new ArrayCollection(array($requestCategory));
        $category_iterator = new RecursiveCategoryIterator($collection);
        $recursive_iterator = new \RecursiveIteratorIterator($category_iterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($recursive_iterator as $index => $child_category)
        {
            $products=$em->findBy(array('category'=>$child_category->getId()));
            foreach ($products as $product){
                $responseProducts[]=array(
                    'id'=>$product->getId(),
                    'name'=>$product->getName(),
                    'description'=>$product->getDescription(),
                    'dateOfCreation'=>$product->getDateOfCreation(),
                    'dateOfLastUpdate'=>$product->getDateOfLastUpdate(),
                    'SKU'=>$product->getUniqueIdentifier(),
                    'image'=>$product->getImage(),
                );
            }
        }
        return new JsonResponse(array('categories' => $responseCategories,
            'products'=>$responseProducts));
    }
}
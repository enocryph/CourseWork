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
        return $this->render('catalog_index.html.twig');
    }
    /**
     * @Route("/ajax/product", name="products_ajax")
     * @Method("GET")
     */
    public function productAjaxAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $page=$request->get('page');
        $perpage=$request->get('perpage');
        $count=0;
        $products=array();
        if ($request->get('category')) {
            $requestCategory = $em->getRepository("AppBundle:Category")->find($request->get('category'));
            $collection = new ArrayCollection(array($requestCategory));
            $category_iterator = new RecursiveCategoryIterator($collection);
            $recursive_iterator = new \RecursiveIteratorIterator($category_iterator, \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($recursive_iterator as $index => $child_category) {
                $products=array_merge($products, $em->getRepository("AppBundle:Product")
                    ->findBy(array('category'=>$child_category->getId(),'isActive'=>true)));
            }

        } else {
            $products=$em->getRepository("AppBundle:Product")->findBy(array('isActive'=>true));
        }

        $responseProducts = array();
        if (isset($products)!=0) {
            $count=count($products);
            $products=array_slice($products,($page-1)*$perpage,$perpage);
            foreach ($products as $product) {
                $responseProducts[] = array(
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'image' => $product->getImage(),
                );
            }
        }

        return new JsonResponse(array('products'=>$responseProducts,'count'=>$count));
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
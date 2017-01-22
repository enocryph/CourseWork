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
    public function indexAction(Request $request)
    {
        if ($request->get('category')){
            $category=$request->get('category');
        } else {
            $category='';
        }
        if ($request->get('page')){
            $page=$request->get('page');
        } else {
            $page=1;
        }
        dump($category);
        dump($page);
        return $this->render('catalog_index.html.twig', array('category'=>$category, 'page'=>$page));
    }
    /**
     * @Route("/product/{id}", name="product_view")
     * @Method("GET")
     */
    public function productAction(Product $product)
    {
        return $this->render('catalog_product.html.twig',array('product' => $product));
    }
    /**
     * @Route("/ajax/products", name="products_ajax")
     * @Method("GET")
     */
    public function productsAjaxAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $page=$request->get('page');
        $perpage=$request->get('perpage');
        $count=0;
        $products=array();
        if ($request->get('category')) {
            $repository=$em->getRepository('AppBundle:Category');
            $rootCategory=$repository->find($request->get('category'));
            $collection = new ArrayCollection(array($rootCategory));
            $category_iterator = new RecursiveCategoryIterator($collection);
            $recursive_iterator = new \RecursiveIteratorIterator($category_iterator, \RecursiveIteratorIterator::SELF_FIRST);
            $categories=array();
            foreach ($recursive_iterator as $index => $child_category) {
                $categories[]=$child_category->getId();
            }
            $repository=$em->getRepository('AppBundle:Product');
            $products = $repository->createQueryBuilder('p')
                ->where('p.isActive = :true')->andWhere('p.category IN (:categories)')
                ->setParameter('true', true)->setParameter('categories', array_values($categories))
                ->getQuery()->getResult();
        } else {
            $repository=$em->getRepository('AppBundle:Product');
            $products = $repository->findBy(array('isActive'=>true));
        }

        $responseProducts = array();
        if (isset($products)) {
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
            $categories = $em->getRepository("AppBundle:Category")->findBy(array('parent'=>null,'isActive'=>true));
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
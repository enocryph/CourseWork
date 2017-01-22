<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\DateTime;
use AppBundle\Form\ProductType;
use AppBundle\Service\ImageDownloader;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Product controller.
 *
 * @Route("product")
 */
class ProductController extends Controller
{
    /**
     * Lists all product entities.
     *
     * @Route("/", name="product_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $products = $em->getRepository('AppBundle:Product')->findAll();

        return $this->render('product/index.html.twig', array(
            'products' => $products,
        ));
    }
    /**
     * Lists all product entities.
     *
     * @Route("/ajax", name="product_ajax")
     * @Method("GET")
     */
    public function ajaxAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repository=$em->getRepository('AppBundle:Product');
        $page=$request->get('page');
        $perpage=$request->get('perpage');
        $count=0;
        if ($request->get('sortbyfield'))
        {
            $products = $repository->createQueryBuilder('p')
                ->orderBy('p.'.$request->get('sortbyfield'),$request->get('order'))
                ->getQuery()->getResult();
        } elseif ($request->get('filterbyfield')) {
            if (($request->get('filterbyfield') == 'dateOfCreation') || ($request->get('filterbyfield') == 'dateOfLastUpdate')){
                $date = date_create($request->get('pattern'));
                $products = $repository->createQueryBuilder('p')
                    ->where('p.' . $request->get('filterbyfield') . ' >= :pattern')
                    ->setParameter('pattern', date_format($date, 'Y-m-d H:i:s'))
                    ->getQuery()->getResult();
            } else {
                $products = $repository->createQueryBuilder('p')
                    ->where('p.' . $request->get('filterbyfield') . ' LIKE :pattern')
                    ->setParameter('pattern', $request->get('pattern') . '%')
                    ->getQuery()->getResult();
            }
        } else {
            $products = $repository->findAll();
        }
        $responseProducts = array();
        if (isset($products)) {
            $count=count($products);
            $products=array_slice($products,($page-1)*$perpage,$perpage);
            foreach ($products as $product) {
                $responseProducts[] = array(
                    'id'=>$product->getId(),
                    'name'=>$product->getName(),
                    'description'=>$product->getDescription(),
                    'dateOfCreation'=>$product->getDateOfCreation()->format( 'd-m-Y H:i:s' ),
                    'dateOfLastUpdate'=>$product->getDateOfLastUpdate()->format( 'd-m-Y H:i:s' ),
                    'isActive'=>$product->getIsActive(),
                    'uniqueIdentifier'=>$product->getUniqueIdentifier(),
                    'image'=>$product->getImage(),
                );
            }
        }

        return new JsonResponse(array('products'=>$responseProducts,'count'=>$count));
    }
    /**
     * Creates a new product entity.
     *
     * @Route("/new", name="product_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $product = new Product();
        $form = $this->createForm('AppBundle\Form\ProductType', $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $file = $product->getImage();
            $image = $this->get('app.image_downloader');
            $imagePath = $image->downloadImage($file);
            $product->setImage($imagePath);
            $currentDateTime = new \DateTime('now');

            $product->setDateOfCreation($currentDateTime);
            $product->setDateOfLastUpdate($currentDateTime);

            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush($product);

            return $this->redirectToRoute('product_show', array('id' => $product->getId()));
        }

        return $this->render('product/new.html.twig', array(
            'product' => $product,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a product entity.
     *
     * @Route("/{id}", name="product_show")
     * @Method("GET")
     */
    public function showAction(Product $product)
    {
        $deleteForm = $this->createDeleteForm($product);

        return $this->render('product/show.html.twig', array(
            'product' => $product,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing product entity.
     *
     * @Route("/{id}/edit", name="product_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Product $product)
    {
        $oldImagePath = $product->getImage();
        $deleteForm = $this->createDeleteForm($product);
        $editForm = $this->createForm('AppBundle\Form\ProductType', $product);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $file = $product->getImage();
            $image = $this->get('app.image_downloader');
            $imagePath = $image->downloadImage($file, $oldImagePath);
            $product->setImage($imagePath);

            $currentDateTime = new \DateTime('now');
            $dateOfCreation = $product->getDateOfCreation();
            $product->setDateOfCreation($dateOfCreation);
            $product->setDateOfLastUpdate($currentDateTime);

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('product_edit', array('id' => $product->getId()));
        }

        return $this->render('product/edit.html.twig', array(
            'product' => $product,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a product entity.
     *
     * @Route("/{id}", name="product_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Product $product)
    {
        $form = $this->createDeleteForm($product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($product);
            $em->flush($product);
        }

        return $this->redirectToRoute('product_index');
    }

    /**
     * Creates a form to delete a product entity.
     *
     * @param Product $product The product entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Product $product)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('product_delete', array('id' => $product->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

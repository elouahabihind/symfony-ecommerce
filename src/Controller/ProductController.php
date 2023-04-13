<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Form\ProductType;
use App\Repository\ProductRepository;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;


class ProductController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(ProductRepository $productRepository): Response
    {

        $products = $productRepository->findAll();

        return $this->render('index.html.twig', [
            'products' => $products,
        ]);
    }
    #[Route('/product/add', name: 'product_add')]
    public function addNewProduct(EntityManagerInterface $entityManager, Request $request, SluggerInterface $slugger)
    {
         //fonction pour afficher formulaire et ajouter une nouvelle produit  
        $product = new Product();

       $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productName = $product->getNom();
            $existingProduct = $entityManager->getRepository(Product::class)->findOneBy(['nom' => $productName]);

            if (!$existingProduct) {
                $imageFile = $form->get('image')->getData();
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                    try {
                        $imageFile->move(
                            'images',
                            $newFilename
                        );
                    } catch (FileException $e) {
                        echo "<script>alert('Error uploading')</script>";
                    }
                    $product->setImage($newFilename);
                }
                $entityManager->persist($product);
                $entityManager->flush();
                return $this->redirect('/', 201);
            } else {
                echo '<script>alert("Le produit existe déjà");</script>';
            }
        }
        return $this->render('product/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    //fonction pour afficher formulaire et ajouter une nouvelle categorie
    #[Route('/category/add', name: 'category_add')]
    public function addNewCategory(EntityManagerInterface $entityManager, Request $request)
    {
        $category = new Category();
        
        $form = $this->createForm(CategoryType::class, $category);


        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $categoryName = $category->getNom();
            $existingCategory = $entityManager->getRepository(Category::class)->findOneBy(['nom' => $categoryName]);
            if (!$existingCategory) {
                $entityManager->persist($category);
                $entityManager->flush();
                return $this->redirect('/', 201);
            } else {
                echo '<script>alert("La catégorie existe déjà");</script>';
            }
        }
        return $this->render('category/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    //fonction pour afficher les categories
    #[Route('/category', name: 'category')]
    public function showCategory(EntityManagerInterface $entityManager, Request $request)
    {
        $categories = $entityManager->getRepository(Category::class) ->findAll();

        if (!$categories) {
            throw $this->createNotFoundException(
                'No category found in category\'s table.'
            );
        } else {
            return $this->render('category/categories.html.twig', [
                'categories' => $categories,
            ]);
        }
    }


    //show product
    #[Route('/product/{id}', name: 'show_product')]
    public function showProduct(ProductRepository $productRepository,int $id):Response{

        $product = $productRepository->findOneBy(['id' => $id]);

        return $this->render('product/show.html.twig',array('product'=>$product));
    }

}

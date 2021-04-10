<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// On a rajouté ça lorsque l'on voulu faire les routes pour le formulaire
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\Session\Session;

class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user")
     */
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }
    /**
     * @Route("/createUser", name="createUser")
     */
    public function createUser(Request $request, EntityManagerInterface $manager): Response
    {
    	// Dans la variable User on y met la valeur qui a pour fonction de nous creer un nouvel utilisateur à l'aide de l'entité du mm nom
        $User = new Utilisateur();
        // Ensuite de cette variable
        // le setNom :  c'est pour dire genre mettre dans la propriété Nom de l'entité Utilisateur
        // ce que l'on met est donc obtenu de get('nom') du formulaire
        $User->setNom($request->request->get('nom'));
        $User->setPrenom($request->request->get('prenom'));
        $User->setCode($request->request->get('code'));
        $User->setSalt($request->request->get('salt'));
        
        // manager sert à envoyer des actions à la base de donnée
        // persist sert à enregistrer l'objet et qu'il ne doit pas etre utiliser pour un nouvel objet ou une màj
        $manager->persist($User);
        // flush sert à mettre à jour la base de donnée en fonction de l'entré
        $manager->flush();
        
        return $this->render('user/index.html.twig', [
            'controller_name' => 'Un utilisateur a été ajouté',
        ]);
    }
}

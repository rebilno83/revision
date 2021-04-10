<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// on rajoute ça
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\Session\Session;

class AuthentificationController extends AbstractController
{
    /**
     * @Route("/authentification", name="authentification")
     */
    public function index(): Response
    {
        return $this->render('authentification/index.html.twig', [
            'controller_name' => 'AuthentificationController',
        ]);
    }
    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function dashboard(): Response
    {
        return $this->render('authentification/dashboard.html.twig', [
            'controller_name' => 'Espace Client',
        ]);
    }
    /**
     * @Route("/connexion", name="connexion")
     */
    public function connexion(Request $request, EntityManagerInterface $manager): Response
    {
        //Récupération des données du controleur
        $identifiant = $request->request->get('identifiant');
		$password    = $request->request->get('password');
		//connexion avec la BD et récupération du couple id/password
		$aUser = $manager->getRepository(Utilisateur::class)->findBy(["nom"=>$identifiant, "code"=>$password]);
        //test de l'existence d'un tel couple

        //premier if/else
				 //    if (sizeof($aUser)>0){
				 //         $reponse = " cool, ce couple id/mdp existe";             
				 //     }else{
				 //         $reponse = " dehors les intrus";
				 //     }
				 //    // dd là sert à obliger à que cette valeur soit renvoyé au lieu de celle dans return
				 //    dd($reponse);
					
		// second if/else
				// if (sizeof($aUser)>0){
			  	//    return $this->redirectToRoute('dashboard');             
			  	// }else{
			  	//    return $this->redirectToRoute('authentification');
			  	// }
        // 3me if/else
        if (sizeof($aUser)>0){
          //Récupération de l'utilisateur 
             $utilisateur = new Utilisateur;
             $utilisateur = $aUser[0];
             //Démarrage d'une session
             $sess = $request->getSession();
             //Créer des variables de session
             $sess->set("idUtilisateur", $utilisateur->getId());
             $sess->set("nomUtilisateur", $utilisateur->getNom());
             $sess->set("prenomUtilisateur", $utilisateur->getPrenom());
             return $this->redirectToRoute('dashboard');
        }else{
             //return $this->redirectToRoute('authentification');
        }
        return new response(1);
    }
    /**
     * @Route("/deconnexion", name="deconnexion")
     */
    public function deconnexion(Request $request, EntityManagerInterface $manager): Response
    {
        $sess = $request->getSession();
        $sess->invalidate();
        //$sess->clear();
        $sess=$request->getSession()->clear();
        return $this->redirectToRoute('authentification');
    }
}

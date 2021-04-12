<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// à ajouter
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Document;
use App\Entity\Genre;
// on rajoute
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use DateTime;
use App\Entity\Autorisation;
use App\Entity\Utilisateur;
use App\Entity\Acces;

class DocumentController extends AbstractController
{
    /**
     * @Route("/uploadDocument", name="uploadDocument")
     */
    public function uploadDocument(Request $request, EntityManagerInterface $manager): Response
    {
        //Requête pour récupérer toute la table genre
		$listeGenre = $manager->getRepository(Genre::class)->findAll();
		$listeAutorisation = $manager->getRepository(Autorisation::class)->findAll();
        return $this->render('document/uploadDocument.html.twig', [
            'controller_name' => "Upload d'un Document",
            'listeGenre' => $listeGenre,
            'listeAutorisation' => $listeAutorisation,
            'listeUsers' => $manager->getRepository(Utilisateur::class)->findAll(),
        ]);
    }
    /**
     * @Route("/insertDocument", name="insertDocument")
     */
    public function insertDocument(Request $request, EntityManagerInterface $manager): Response
    {
        $sess = $request->getSession();
        //création d'un nouveau document
        $Document = new Document();
        //Récupération et transfert du fichier
        $brochureFile = $request->files->get("fichier");
        if ($brochureFile){
            $newFilename = uniqid('', true) . "." . $brochureFile->getClientOriginalExtension();
            $pathImage = "public/upload/";
            $brochureFile->move($this->getParameter('upload'), $newFilename);
            //insertion du document dans la base.
           if($request->request->get('choix') == "on"){
				$actif=1;
			}else{
				$actif=2;
			}
			$Document->setActif($actif);
			$Document->setNom($request->request->get('nom'));
			$Document->setTypeId($manager->getRepository(Genre::class)->findOneById($request->request->get('genre')));
			$Document->setCreatedAt(new \Datetime);	
			$Document->setChemin($newFilename);
            
            $manager->persist($Document);
            $manager->flush();
        }
        if($request->request->get('utilisateur') != -1){
			$user = $manager->getRepository(Utilisateur::class)->findOneById($request->request->get('utilisateur'));
			$autorisation = $manager->getRepository(Autorisation::class)->findOneById($request->request->get('autorisation'));
			$acces = new Acces();
			$acces->setUtilisateurId($user);
			$acces->setAutorisationId($autorisation);
			$acces->setDocumentId($Document);
			$manager->persist($acces);
			$manager->flush();	
		}
		//Création d'un accès pour l'uploadeur (propriétaire)
		$user = $manager->getRepository(Utilisateur::class)->findOneById($sess->get("idUtilisateur"));
			$autorisation = $manager->getRepository(Autorisation::class)->findOneById(1);
			$acces = new Acces();
			$acces->setUtilisateurId($user);
			$acces->setAutorisationId($autorisation);
			$acces->setDocumentId($Document);
			$manager->persist($acces);
			$manager->flush();	
		
		return $this->redirectToRoute('listeDocument');
    }
    /**
     * @Route("/listeDocument", name="listeDocument")
     */

    public function listeDocument(Request $request, EntityManagerInterface $manager): Response
    {
    	$sess = $request->getSession();
        // Requête pour récupérer toute la table genre
        // $listeDocument = $manager->getRepository(Document::class)->findAll();
        $user = $manager->getRepository(Utilisateur::class)->findOneById($sess->get("idUtilisateur"));
		$listeAcces = $manager->getRepository(Acces::class)->findByUtilisateurId($user);
		$listeUsers = $manager->getRepository(Utilisateur::class)->findAll();
		$listeAutorisations = $manager->getRepository(Autorisation::class)->findAll();
		//$listeDocument = $manager->getRepository(Document::class)->findAll();
        return $this->render('document/listeDocument.html.twig', [
            'controller_name' => 'Liste des Documents',
            //'listeDocument' => $listeDocument,
            'listeAcces' => $listeAcces,
            'listeUsers' => $listeUsers,
            'listeAutorisations' => $listeAutorisations,
        ]);
    }
    /**
     * @Route("/deleteDocument/{id}", name="deleteDocument")
     */
    public function deleteDocument(Request $request, EntityManagerInterface $manager, Document $id): Response
    {
        $sess = $request->getSession();
        if($sess->get("idUtilisateur")){
            
        // supprimer le lien avec l'accés
		$recupListeacces = $manager->getRepository(Acces::class)->findByDocumentId($id);
		//dd($recupListeacces);
		foreach($recupListeacces as $doc){
			$manager->remove($doc);
			$manager->flush();
		}	
		//suppression physique du document :
		if(unlink("upload/".$id->getChemin())){
		//suppression du lien dans la base de données
			$manager->remove($id);
			$manager->flush();
		}
		return $this->redirectToRoute('listeDocument');
		}else{
			return $this->redirectToRoute('authentification');	
		}
    }
    /**
     * @Route("/permissionDocument", name="permissionDocument")
     */
    public function permissionDocument(Request $request, EntityManagerInterface $manager, Document $id): Response
    {
        $sess = $request->getSession();
        if($sess->get("idUtilisateur")){
            //Récupération des listes
            $listeDocument = $manager->getRepository(Document::class)->findAll();
            $listeUser = $manager->getRepository(Utilisateur::class)->findAll();
            return $this->render('document/permissionDocument.html.twig', [
            'controller_name' => "Attribution d'une permission",
            'listeDocument' => $listeDocument,
            'listeUser' => $listeUser,
        ]);
        }else{
            return $this->redirectToRoute('authentification');    
        }
    }
    /**
     * @Route("/partageDocument", name="partageDocument")
     */
    public function partageDocument(Request $request, EntityManagerInterface $manager): Response
    {
		$sess = $request->getSession();
		if($sess->get("idUtilisateur")){
			//Requête le user en focntion du formulaire
			$user = $manager->getRepository(Utilisateur::class)->findOneById($request->request->get('utilisateur'));
			$autorisation = $manager->getRepository(Autorisation::class)->findOneById($request->request->get('autorisation'));
			$document = $manager->getRepository(Document::class)->findOneById($request->request->get('doc'));
			$acces = new Acces();
			$acces->setUtilisateurId($user);
			$acces->setAutorisationId($autorisation);
			$acces->setDocumentId($document);
			$manager->persist($acces);
			$manager->flush();
					
			return $this->redirectToRoute('listeDocument');
		}else{
			return $this->redirectToRoute('authentification');
		}
    }
}

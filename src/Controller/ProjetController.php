<?php

namespace App\Controller;


use App\Entity\Membre;
use App\Entity\Note;
use App\Entity\Projet;
use App\Entity\User;
use App\Form\ProjetType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

class ProjetController extends AbstractController
{
    /* ---------------------------------
                CRUD PROJET
    --------------------------------- */
    /**
     * @Route("/autresProjets", name="list_projet")
     *
     */
    public function listProjet()
    {
        //On récupere l'User
        $user = $this->getUser();

        //on récupère tous les projets
        $projet = $this->getDoctrine()->getRepository(Projet::class)->findAll();

        return $this->render('projet/listProjet.html.twig',['donnees'=>$projet]);
    }

    /**
     * @Route("/mesProjets", name="mes_projets")
     */
    public function mesProjets() {
        //On récupere l'User
        $user = $this->getUser();

        $mesProjets = null;
        //Si l'utilisateur est un étudiant
        if($user->getRoles() == ['ROLE_USER']){
            $mesProjets = $this->getDoctrine()->getRepository(Projet::class)->findProjetsForStud($user->getId());
        }
        //Si l'utilisateur est un professeur
        if($user->getRoles() == ['ROLE_PROF','ROLE_USER']){
            $mesProjets = $this->getDoctrine()->getRepository(Projet::class)->findProjetsForProf($user->getId());
        }

        return $this->render('projet/mesProjet.html.twig',['projets'=>$mesProjets]);
    }

    /**
     * @Route("/projet/addProjet", name="add_projet")
     * @param Request $request
     * @param Projet|null $projet
     */
    public function addProjet(Request $request, Projet $projet = null)
    {
        $projet = new Projet();
        //On crée le formulaire
        $form = $this->createForm(ProjetType::class, $projet);
        $form->add('submit', SubmitType::class, [
            'label' => 'Ajouter',
            'attr' => ['class' => 'btn btn-default pull-right'],
        ]);
        $form->handleRequest($request);

        //si le formualaire a été envoyé/validé
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($projet);
            $em->flush();

            return $this->redirectToRoute('list_projet');
        }
        return $this->render('projet/projetForm.html.twig', [
            'form_projet' => $form->createView(),
        ]);
    }

    /**
     * @Route("/projet/editProjet/{id}", name="edit_projet")
     * @param Request $request
     * @param $id
     */
    public function editProjet(Request $request, $id = null)
    {
        //On récupère le projet
        $idProjet = $request->get('id');
        $projet = $this->getDoctrine()->getRepository(Projet::class)->find($idProjet);
        //On verifie que le projet existe
        if (!$projet)  throw $this->createNotFoundException('No projet found for id '.$idProjet);

        //On crée le formulaire
        $form = $this->createForm(ProjetType::class, $projet);
        $form->add('submit', SubmitType::class, [
            'label' => 'Modifier',
            'attr' => ['class' => 'btn btn-default pull-right'],
        ]);
        $form->handleRequest($request);

        //si le formualaire a été envoyé/validé
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($projet);
            $em->flush();

            return $this->redirectToRoute('mes_projets');
        }
        return $this->render('projet/projetForm.html.twig', [
            'form_projet' => $form->createView(),
        ]);
    }

    /**
     * @Route("/projet/delete", name="delete_projet", methods={"DELETE"})
     * @param Request $request
     */
    public function deleteProjet(Request $request)
    {
        if(!$this->isCsrfTokenValid('projet_delete', $request->get('token'))) {
            throw new  InvalidCsrfTokenException('Invalid CSRF token formulaire projet');
        }
        $em = $this->getDoctrine()->getManager();
        $idProjet = $request->request->get('id');
        $projet = $em->getRepository(Projet::class)->find($idProjet);

        //On verifie que le projet existe
        if (!$projet)  throw $this->createNotFoundException('No projet found for id '.$idProjet);

        //On recupère l'ID des membres liés au projet
        $idMembres = $em->getRepository(Membre::class)->findMembreByProjet($idProjet);

        foreach ($idMembres as $idMembre){
            $membre = $em->getRepository(Membre::class)->find($idMembre);
            $em->remove($membre);
        }

        //On recupère l'ID des notes liées au projet
        $idNotes = $em->getRepository(Note::class)->findNoteByProjet($idProjet);
        foreach ($idNotes as $idNote){
            $note = $em->getRepository(Note::class)->find($idNote);
            $em->remove($note);
        }

        $em->remove($projet);
        $em->flush();
        return $this->redirectToRoute('list_projet');
    }
    /* ---------------------------------
            Details
    --------------------------------- */
    /**
     * @Route("/projet/details/{id}", name="details_projet")
     * @param Request $request
     * @param $id
     */
    public function detailsProjet(Request $request, $id = null)
    {
        $idProjet = $request->get('id');
        $donnees = $this->getDetailsProjet($idProjet);

        if($donnees['access'] == false){
            $this->addFlash('danger',"Vous n'avez pas accés à ce projet");
            return $this->redirectToRoute('list_projet');
        }

        return $this->render('projet/detailsProjet.html.twig',['projet'=>$donnees['projet'], 'tuteurs'=>$donnees['prof'], 'isTuteur'=>$donnees['isTuteur'],
            'etudiants'=>$donnees['stud'], 'notePerso'=>$donnees['notePerso'], 'notes'=>$donnees['notes'], 'allStud'=>$donnees['allStud'], 'allProf'=>$donnees['allProf']]);

    }

    public function getDetailsProjet($idProjet){
        $details['access'] = true;
        //On recupère l'iD de l'utilisateur login
        $idUser = $this->getUser()->getId();

        $details['projet'] = $this->getDoctrine()->getRepository(Projet::class)->find($idProjet);
        //On verifie que le projet existe
        if (!$details['projet'])  throw $this->createNotFoundException('No projet found for id '.$idProjet);

        //On cherche tous les étudiants liés au projet
        $details['stud'] = $this->getDoctrine()->getRepository(User::class)->findStudByProjet($idProjet);

        /* On vérifie que l'Utilisateur a le droit de consulter les details du projet
             Droit d'accées: Utilisateur Etudiant - Membre
                             Utilisateur Professeur
                             Utilisateur Admin
         */
        $user = $this->getDoctrine()->getRepository(User::class)->find($idUser);
        if($user->getRoles() == ["ROLE_USER"]){
            if (array_search($user, $details['stud']) === false) {
                $details['access'] = false;

            }
        }

        //On cherche tous les tuteurs liés au projet
        $details['isTuteur'] = empty($this->getDoctrine()->getRepository(Note::class)->findNoteByTuteur($idProjet,$idUser));

        //On cherche tous les professeurs liés au projet
        $details['prof'] = $this->getDoctrine()->getRepository(User::class)->findProfByProjet($idProjet);


        //On cherche les notes mis par l'enseignant sur le projet
        $details['notePerso'] = $this->getDoctrine()->getRepository(Note::class)->findNoteByProjetAndUser($idProjet,$idUser);

        //On cherche la moyenne de toutes les notes
        $details['notes'] = $this->getDoctrine()->getRepository(Note::class)->findNoteMoyenneByProjet($idProjet);

        /*trouver les étudiant qui ne sont pas liées au projet*/
        $details['allStud'] = $this->getDoctrine()->getRepository(User::class)->findAll();
        foreach ($details['allStud'] as $user) {
            //si un user n'est pas un stud ou si il est déjà associer au projet
            if($user->getRoles() != ["ROLE_USER"] or in_array($user,$details['stud'])){
                if (($key = array_search($user, $details['allStud'])) !== false) {
                    unset($details['allStud'][$key]);
                }
            }
        }
        /*trouver les tuteurs qui ne sont pas liées au projet*/
        $details['allProf'] = $this->getDoctrine()->getRepository(User::class)->findAll();
        foreach ($details['allProf'] as $user) {
            //si un user n'est pas un stud ou si il est déjà associer au projet
            if($user->getRoles() != ["ROLE_PROF","ROLE_USER"] or in_array($user,$details['prof'])){
                if (($key = array_search($user, $details['allProf'])) !== false) {
                    unset($details['allProf'][$key]);
                }
            }
        }
        return $details;
    }
    /* ---------------------------------
                TUTEUR
    --------------------------------- */
    /**
     * @Route("/projet/addTuteur", name="add_tuteur")
     * @param Request $request
     */
    public function addTuteur(Request $request)
    {
        //L'ajout d'un tuteur revient a créé une entité note qui lie le projet et l'User
        $em = $this->getDoctrine()->getManager();
        $idProjet = $request->get('id');

        $projet = $this->getDoctrine()->getRepository(Projet::class)->find($idProjet);
        //On verifie que le projet existe
        if (!$projet)  throw $this->createNotFoundException('No projet found for id '.$idProjet);
        //On récupere l'User
        $user = $this->getUser();
        //Création d'une Note
        $note = new Note();
        $note->setProjet($projet)
            ->setUser($user);

        $em->persist($note);
        $em->flush();

        return $this->redirectToRoute('details_projet',['id'=>$idProjet]);
    }
    /**
     * @Route("/projet/addTuteur/{id}", name="add_tuteur_id")
     * @param Request $request
     * @param $id
     */
    public function addTuteurById(Request $request,$id = null)
    {
        //L'ajout d'un tuteur revient a créé une entité note qui lie le projet et l'User
        $em = $this->getDoctrine()->getManager();
        $idProjet = $request->get('idProjet');
        $idUser = $request->get('idUser');

        $projet = $this->getDoctrine()->getRepository(Projet::class)->find($idProjet);
        //On verifie que le projet existe
        if (!$projet)  throw $this->createNotFoundException('No projet found for id '.$idProjet);
        //On récupere l'User
        $user = $this->getDoctrine()->getRepository(User::class)->find($idUser);
        //Création d'une Note
        $note = new Note();
        $note->setProjet($projet)
            ->setUser($user);

        $em->persist($note);
        $em->flush();

        return $this->redirectToRoute('details_projet',['id'=>$idProjet]);
    }
    /**
     * @Route("/projet/deleteTuteur", name="delete_tuteur", methods={"DELETE"})
     * @param Request $request
     */
    public function deleteTuteur(Request $request)
    {
        if(!$this->isCsrfTokenValid('tuteur_delete', $request->get('token'))) {
            throw new  InvalidCsrfTokenException('Invalid CSRF token formulaire tuteur');
        }
        $em = $this->getDoctrine()->getManager();

        //On verifie que le projet existe
        $idProjet = $request->request->get('idProjet');
        $projet = $em->getRepository(Projet::class)->find($idProjet);
        if (!$projet)  throw $this->createNotFoundException('No projet found for id '.$idProjet);

        //On verifie que l'user existe
        $idUser = $request->request->get('idUser');
        $user = $em->getRepository(User::class)->find($idUser);
        if (!$user)  throw $this->createNotFoundException('No user found for id '.$idUser);

        $note = $this->getDoctrine()->getRepository(Note::class)->findNoteByProjetAndUser($idProjet,$idUser);
        $em->remove($note[0]);
        $em->flush();

        return $this->redirectToRoute('details_projet',['id'=>$idProjet]);
    }
    /* ---------------------------------
                MEMBRE
    --------------------------------- */
    /**
     * @Route("/projet/add_membre", name="add_membre")
     * @param Request $request
     */
    public function addmembre(Request $request)
    {
        //L'ajout d'un membre revient a créé une entité Membre qui lie le projet et l'User
        $em = $this->getDoctrine()->getManager();

        $idProjet = $request->get('id');
        $projet = $this->getDoctrine()->getRepository(Projet::class)->find($idProjet);
        //On verifie que le projet existe
        if (!$projet)  throw $this->createNotFoundException('No projet found for id '.$idProjet);

        //On récupere l'User
        $email = $request->get('email');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email'=>$email]);

        //Création d'un membre
        $membre = new Membre();
        $membre->setChefProjet(false)
            ->setProjet($projet)
            ->setUser($user);

        $em->persist($membre);
        $em->flush();

        return $this->redirectToRoute('details_projet',['id'=>$idProjet]);

    }

    /**
     * @Route("/projet/delete_membre", name="delete_membre", methods={"DELETE"})
     * @param Request $request
     */
    public function deleteMembre(Request $request)
    {
        if(!$this->isCsrfTokenValid('membre_delete', $request->get('token'))) {
            throw new  InvalidCsrfTokenException('Invalid CSRF token formulaire membre');
        }
        $em = $this->getDoctrine()->getManager();

        //On verifie que le projet existe
        $idProjet = $request->request->get('idProjet');
        $projet = $em->getRepository(Projet::class)->find($idProjet);
        if (!$projet)  throw $this->createNotFoundException('No projet found for id '.$idProjet);

        //On verifie que l'user existe
        $idUser = $request->request->get('idUser');
        $user = $em->getRepository(User::class)->find($idUser);
        if (!$user)  throw $this->createNotFoundException('No user found for id '.$idUser);

        $membre = $this->getDoctrine()->getRepository(Membre::class)->findMembreByProjetAndUser($idProjet,$idUser);

        $em->remove($membre[0]);
        $em->flush();

        return $this->redirectToRoute('details_projet',['id'=>$idProjet]);
    }
    /* ---------------------------------
                NOTE
    --------------------------------- */
    /**
     * @Route("/projet/editNote", name="edit_note")
     * @param Request $request
     */
    public function editNote(Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        //On récupère les données du formulaire
        $donnees['soutenance'] = $request->get('soutenance');
        $donnees['rapport'] = $request->get('rapport');
        $donnees['technique'] = $request->get('technique');
        $donnees['note'] = $request->get('note');
        $donnees['projet'] = $request->get('projet');

        //On verifie les erreurs
        $erreurs = $this->validatorNote($donnees);

        //Si il n'y a pas d'erreurs
        if(empty($erreurs)){
            $note = $this->getDoctrine()->getRepository(Note::class)->find($donnees['note']);
            //On verifie que la note existe
            if (!$note)  throw $this->createNotFoundException('No note found for id '.$donnees['note']);

            $note->setSoutenance($donnees['soutenance'])
                ->setRapport($donnees['rapport'])
                ->setTechnique($donnees['technique']);

            $em->persist($note);
            $em->flush();

            $result = $this->getDetailsProjet($donnees['projet']);
            return $this->render('projet/detailsProjet.html.twig',['projet'=>$result['projet'], 'tuteurs'=>$result['prof'], 'isTuteur'=>$result['isTuteur'],
                'etudiants'=>$result['stud'], 'notePerso'=>$result['notePerso'], 'notes'=>$result['notes'], 'allStud'=>$result['allStud'], 'allProf'=>$result['allProf']]);
        }
        $result = $this->getDetailsProjet($donnees['projet']);

        return $this->render('projet/detailsProjet.html.twig',['projet'=>$result['projet'], 'tuteurs'=>$result['prof'], 'isTuteur'=>$result['isTuteur'],
            'etudiants'=>$result['stud'], 'notePerso'=>$result['notePerso'], 'notes'=>$result['notes'],'allStud'=>$result['allStud'], 'allProf'=>$result['allProf'], 'erreurs'=>$erreurs]);
    }

    private function validatorNote(array $donnees)
    {
        return null;
    }
}
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
     * @Route("/projet", name="list_projet")
     *
     */
    public function listProjet()
    {
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
        //on récupère tous les projets
        $projet = $this->getDoctrine()->getRepository(Projet::class)->findAll();

        return $this->render('projet/listProjet.html.twig',['donnees'=>$projet,'projets'=>$mesProjets]);
    }

    /**
     * @Route("/projet/addProjet", name="add_projet")
     * @param Request $request
     * @param Projet|null $projet
     * @IsGranted("ROLE_ADMIN")
     * @IsGranted("ROLE_PROF")
     */
    public function addProjet(Request $request, Projet $projet = null)
    {
        //On crée le formulaire
        $form = $this->createForm(ProjetType::class, $projet);
        $form->add('submit', SubmitType::class, [
            'label' => 'Create',
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
        $id=$request->get('id');
        $projet = $this->getDoctrine()->getRepository(Projet::class)->find($id);
        //On verifie que le projet existe
        if (!$projet)  throw $this->createNotFoundException('No projet found for id '.$id);

        //On crée le formulaire
        $form = $this->createForm(ProjetType::class, $projet);
        $form->add('submit', SubmitType::class, [
            'label' => 'Edit',
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
     * @Route("/projet/delete", name="delete_projet", methods={"DELETE"})
     * @param Request $request
     */
    public function deleteProjet(Request $request)
    {
        if(!$this->isCsrfTokenValid('projet_delete', $request->get('token'))) {
            throw new  InvalidCsrfTokenException('Invalid CSRF token formulaire projet');
        }
        $em = $this->getDoctrine()->getManager();
        $id= $request->request->get('id');
        $projet = $em->getRepository(Projet::class)->find($id);

        //On verifie que le projet existe
        if (!$projet)  throw $this->createNotFoundException('No projet found for id '.$id);

        //On recupère l'ID des membres liés au projet
        $idMembres = $em->getRepository(Membre::class)->findMembre($id);

        foreach ($idMembres as $idMembre){
            $membre = $em->getRepository(Membre::class)->find($idMembre);
            $em->remove($membre);
        }

        //On recupère l'ID des notes liées au projet
        $idNotes = $em->getRepository(Note::class)->findNoteByProjet($id);
        foreach ($idNotes as $idNote){
            $note = $em->getRepository(Note::class)->find($idNote);
            $em->remove($note);
        }

        $em->remove($projet);
        $em->flush();
        return $this->redirectToRoute('list_projet');
    }
    /* ---------------------------------
            Details et tuteur
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

        return $this->render('projet/detailsProjet.html.twig',['donnees'=>$donnees['projet'], 'tuteurs'=>$donnees['prof'], 'isTuteur'=>$donnees['isTuteur'],
            'etudiants'=>$donnees['stud'], 'notePerso'=>$donnees['notePerso'], 'notes'=>$donnees['notes']]);

    }

    public function getDetailsProjet($idProjet){
        //On recupère l'iD de l'utilisateur login
        $idUser = $this->getUser()->getId();

        $details['projet'] = $this->getDoctrine()->getRepository(Projet::class)->find($idProjet);
        //On verifie que le projet existe
        if (!$details['projet'])  throw $this->createNotFoundException('No projet found for id '.$idProjet);

        //On cherche tous les tuteurs liés au projet
        $details['isTuteur'] = empty($this->getDoctrine()->getRepository(Note::class)->findNoteByTuteur($idProjet,$idUser));

        //On cherche tous les professeurs liés au projet
        $details['prof'] = $this->getDoctrine()->getRepository(User::class)->findProfByProjet($idProjet);

        //On cherche tous les étudiants liés au projet
        $details['stud'] = $this->getDoctrine()->getRepository(User::class)->findStudByProjet($idProjet);

        //On cherche les notes mis par l'enseignant sur le projet
        $details['notePerso'] = $this->getDoctrine()->getRepository(Note::class)->findNoteByUserAndProject($idProjet,$idUser);

        //On cherche la moyenne de toutes les notes
        $details['notes'] = $this->getDoctrine()->getRepository(Note::class)->findNoteMoyenneByProjet($idProjet);

        return $details;
    }

    /**
     * @Route("/projet/addTuteur", name="add_tuteur")
     * @param Request $request
     */
    public function addTuteur(Request $request)
    {
        //L'ajout d'un tuteur revient a créé une entité note qui lie le projet et l'User
        $em = $this->getDoctrine()->getManager();
        $id = $request->get('id');

        $projet = $this->getDoctrine()->getRepository(Projet::class)->find($id);
        //On verifie que le projet existe
        if (!$projet)  throw $this->createNotFoundException('No projet found for id '.$id);
        //On récupere l'User
        $user = $this->getUser();
        //Création d'une Note
        $note = new Note();
        $note->setProjet($projet)
            ->setUser($user);

        $em->persist($note);
        $em->flush();
        return $this->redirectToRoute('list_projet');

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
            return $this->render('projet/detailsProjet.html.twig',['donnees'=>$result['projet'], 'tuteurs'=>$result['prof'], 'isTuteur'=>$result['isTuteur'],
                'etudiants'=>$result['stud'], 'notePerso'=>$result['notePerso'], 'notes'=>$result['notes']]);
        }
        $result = $this->getDetailsProjet($donnees['projet']);

        return $this->render('projet/detailsProjet.html.twig',['donnees'=>$result['projet'], 'tuteurs'=>$result['prof'], 'isTuteur'=>$result['isTuteur'],
            'etudiants'=>$result['stud'], 'notePerso'=>$result['notePerso'], 'notes'=>$result['notes'], 'erreurs'=>$erreurs]);
    }

    private function validatorNote(array $donnees)
    {
        return null;
    }
}
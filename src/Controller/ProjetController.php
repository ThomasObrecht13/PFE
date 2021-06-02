<?php

namespace App\Controller;


use App\Entity\Note;
use App\Entity\Projet;
use App\Entity\User;
use App\Form\ProjetType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Swift_Mailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class ProjetController extends AbstractController
{
    /**
     * @Route("/projet", name="list_projet")
     *
     */
    public function listProjet()
    {


        $user = $this->getUser();
        if($user->getRoles() == ['ROLE_USER']){

        }
        if($user->getRoles() == ['ROLE_PROF','ROLE_USER']){
            $mesProjets = $this->getDoctrine()->getRepository(Projet::class)->findProjets($user->getId());
        }

        $user = $this->getUser();


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
        $form = $this->createForm(ProjetType::class, $projet);
        $form->add('submit', SubmitType::class, [
            'label' => 'Create',
            'attr' => ['class' => 'btn btn-default pull-right'],
        ]);
        $form->handleRequest($request);

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

        $id=$request->get('id');

        $projet = $this->getDoctrine()->getRepository(Projet::class)->find($id);
        $form = $this->createForm(ProjetType::class, $projet);
        $form->add('submit', SubmitType::class, [
            'label' => 'Edit',
            'attr' => ['class' => 'btn btn-default pull-right'],
        ]);
        $form->handleRequest($request);

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
        if (!$projet)  throw $this->createNotFoundException('No projet found for id '.$id);
        $em->remove($projet);
        $em->flush();
        return $this->redirectToRoute('list_projet');
    }

    /**
     * @Route("/projet/addTuteur", name="add_tuteur")
     * @param Request $request
     */
    public function addTuteur(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

       $projet = $this->getDoctrine()->getRepository(Projet::class)->find($id);
       $user = $this->getUser();

       $note = new Note();

       $note->setProjet($projet)
           ->setUser($user);

       $em->persist($note);
       $em->flush();
       /*
            return $this->redirectToRoute('list_projet');
        }
        return $this->render('projet/projetForm.html.twig', [
            'form_projet' => $form->createView(),
        ]);*/
    }

    /**
     * @Route("/projet/details/{id}", name="details_projet")
     * @param Request $request
     * @param $id
     */
    public function detailsProjet(Request $request, $id = null)
    {
        $id = $request->get('id');
        $projet = $this->getDoctrine()->getRepository(Projet::class)->find($id);
        $prof = $this->getDoctrine()->getRepository(User::class)->findProfByProjet($projet->getId());
        return $this->render('projet/detailsProjet.html.twig',['donnees'=>$projet,'tuteurs'=>$prof]);

    }
}
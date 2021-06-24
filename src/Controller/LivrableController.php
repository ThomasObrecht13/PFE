<?php

namespace App\Controller;


use App\Entity\Livrable;
use App\Entity\Projet;
use App\Form\LivrableType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LivrableController extends AbstractController{

    /**
     * @Route("/projet/{idProjet}/listLivrable", name="projet_list_livrable")
     * @param Request $request
     * @param $idProjet
     */
    public function listLivrable(Request $request, $idProjet = null)
    {
        $idProjet = $request->attributes->get('idProjet');
        $livrables = $this->getDoctrine()->getRepository(Livrable::class)->findBy(['Projet' => $idProjet]);
        return $this->render('livrable/listLivrable.html.twig',[
            'livrables' => $livrables,
            'idProjet' => $idProjet]);
    }
    /**
     * @Route("/projet/{idProjet}/livrable", name="add_livrable")
     * @param Request $request
     * @param $idProjet
     */
    public function addFichier(Request $request, $idProjet = null)
    {
        $livrable = new Livrable();
        $idProjet = $request->attributes->get('idProjet');

        //On crée le formulaire
        $form = $this->createForm(LivrableType::class, $livrable);
        $form->add('submit', SubmitType::class, [
            'label' => 'Créer',
            'attr' => ['class' => 'btn btn-default pull-right'],
        ]);
        $form->handleRequest($request);

        //si le formualaire a été envoyé/validé
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $projet = $this->getDoctrine()->getRepository(Projet::class)->find($idProjet);
            $livrable->setProjet($projet);


            $em->persist($livrable);
            $em->flush();

            return $this->redirectToRoute('projet_list_livrable',['idProjet'=>$idProjet]);
        }
        return $this->render('livrable/livrableForm.html.twig', [
            'form' => $form->createView(),
            'idProjet'=>$idProjet
        ]);
    }
}

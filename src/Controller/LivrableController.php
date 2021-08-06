<?php

namespace App\Controller;

use App\Entity\Livrable;
use App\Entity\Projet;
use App\Form\LivrableType;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile ;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\String\Slugger\SluggerInterface;

class LivrableController extends AbstractController
{
    public function __construct()
    {
        date_default_timezone_set('EUROPE/Paris');
    }
    /**
     * @Route("/projet/{idProjet}/listLivrable", name="projet_list_livrable")
     * @param Request $request
     * @param $idProjet
     */
    public function listLivrable(Request $request, $idProjet = null)
    {
        $idProjet = $request->attributes->get('idProjet');
        $livrables = $this->getDoctrine()->getRepository(Livrable::class)->findBy(['Projet' => $idProjet]);
        return $this->render('livrable/listLivrable.html.twig',['livrables'=>$livrables,'idProjet' => $idProjet]);
    }

    /**
     * @Route("/projet/{idProjet}/livrable", name="add_livrable")
     * @param Request $request
     * @param $idProjet
     * @param SluggerInterface $slugger
     */
    public function addLivrable(Request $request, $idProjet = null)
    {
        $idProjet = $request->attributes->get('idProjet');

        if ($request->getMethod() == 'GET') {
            return $this->render('livrable/addLivrable.html.twig',['idProjet'=>$idProjet]);
        }

        //On récupère les données du formulaire
        $donnees['titre'] = $request->request->get('titre');
        $donnees['libelle'] = $request->request->get('libelle');
        //On verifie les erreurs
        $erreurs = $this->validatorLivrable($donnees);

        //Si il n'y a pas d'erreurs
        if (empty($erreurs)) {
            $livrable = new Livrable();
            $em = $this->getDoctrine()->getManager();

            $livrable->setTitreLivrable($donnees['titre']);
            $livrable->setLibelleLivrable($donnees['libelle']);

            $projet = $this->getDoctrine()->getRepository(Projet::class)->find($idProjet);
            $livrable->setProjet($projet);

            /*
            $literalTime = new DateTime('now');
            $expire_date = $literalTime->format("Y-m-d H-i");
            $livrable->setDateDepot(\DateTime::createFromFormat('Y-m-d  H-i',$expire_date));
*/

            $em->persist($livrable);
            $em->flush();

            return $this->redirectToRoute('projet_list_livrable',['idProjet'=>$idProjet]);
        }
        return $this->render('livrable/livrableForm.html.twig', [
            'donnees' => $donnees,'idProjet'=>$idProjet
        ]);
    }

    /**
     * @Route("/projet/{idProjet}/editLivrable", name="edit_livrable")
     * @param Request $request
     * @param SluggerInterface $slugger
     * @param $idLivrable
     * @param $idProjet
     */
    public function editLivrable(Request $request, SluggerInterface $slugger, $idLivrable = null, $idProjet = null)
    {
        //On récupère le projet
        $idLivrable = $request->get('idLivrable');
        $livrable = $this->getDoctrine()->getRepository(Livrable::class)->find($idLivrable);
        //On verifie que le projet existe
        if (!$livrable)  throw $this->createNotFoundException('No livrable found for id '.$idLivrable);

        $idProjet = $request->attributes->get('idProjet');

        //On crée le formulaire
        $form = $this->createForm(LivrableType::class, $livrable);
        $form->add('submit', SubmitType::class, [
            'label' => 'Modifier',
            'attr' => ['class' => 'btn btn-default pull-right'],
        ]);
        $form->handleRequest($request);

        //si le formualaire a été envoyé/validé
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            /** @var UploadedFile $brochureFile */
            $brochureFile = $form->get('brochure')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $brochureFile->move(
                        $this->getParameter('brochures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $livrable->setBrochureFilename($newFilename);
            }
            $literalTime = new DateTime('now');
            $expire_date = $literalTime->format("Y-m-d H-i");

            $livrable->setDateDepot(\DateTime::createFromFormat('Y-m-d H-i',$expire_date));
            $em->persist($livrable);
            $em->flush();

            return $this->redirectToRoute('projet_list_livrable',['idProjet'=>$idProjet]);
        }
        return $this->render('livrable/livrableForm.html.twig', [
            'form' => $form->createView(),'idProjet'=>$idProjet
        ]);
    }
    /**
     * @Route("/projet/{idProjet}/deleteLivrable", name="delete_livrable", methods={"DELETE"})
     * @param Request $request
     * @param $idProjet
     */
    public function deleteLivrable(Request $request, $idProjet = null)
    {
        if(!$this->isCsrfTokenValid('livrable_delete', $request->get('token'))) {
            throw new  InvalidCsrfTokenException('Invalid CSRF token formulaire livrable');
        }
        $em = $this->getDoctrine()->getManager();
        $idProjet = $request->attributes->get('idProjet');
        $idLivrable = $request->request->get('idLivrable');

        $livrable = $em->getRepository(Livrable::class)->find($idLivrable);
        //On verifie que le projet existe
        if (!$livrable)  throw $this->createNotFoundException('No livrable found for id '.$idLivrable);

        $em->remove($livrable);
        $em->flush();
        return $this->redirectToRoute('projet_list_livrable',['idProjet'=>$idProjet]);
    }

    private function validatorLivrable(array $donnees)
    {
        $erreurs = null;
        return $erreurs;
    }
}

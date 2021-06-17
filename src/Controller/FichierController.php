<?php

namespace App\Controller;

use App\Entity\Fichier;
use App\Entity\Projet;
use App\Form\FichierType;
use App\Repository\FichierRepository;
use Cassandra\Date;
use DateTime;
use DateTimeZone;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\String\Slugger\SluggerInterface;

class FichierController extends AbstractController
{
    public function __construct()
    {
        date_default_timezone_set('EUROPE/Paris');
    }
    /**
     * @Route("/projet/{idProjet}/listDepot", name="projet_list_depot")
     * @param Request $request
     * @param $idProjet
     */
    public function listDepot(Request $request, $idProjet = null)
    {
        $idProjet = $request->attributes->get('idProjet');
        $fichiers = $this->getDoctrine()->getRepository(Fichier::class)->findBy(['Projet' => $idProjet]);
        return $this->render('fichier/listFichier.html.twig',['fichiers'=>$fichiers,'idProjet' => $idProjet]);
    }

    /**
     * @Route("/projet/{idProjet}/depot", name="add_fichier")
     * @param Request $request
     * @param $idProjet
     * @param SluggerInterface $slugger
     */
    public function addFichier(Request $request, SluggerInterface $slugger, $idProjet = null)
    {
        $fichier = new Fichier();
        $idProjet = $request->attributes->get('idProjet');

        //On crée le formulaire
        $form = $this->createForm(FichierType::class, $fichier);
        $form->add('submit', SubmitType::class, [
            'label' => 'Déposer',
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
                $fichier->setBrochureFilename($newFilename);

            }
            $projet = $this->getDoctrine()->getRepository(Projet::class)->find($idProjet);
            $fichier->setProjet($projet);

            $literalTime = new DateTime('now');
            $expire_date = $literalTime->format("Y-m-d H-i");

            $fichier->setDateDepot(\DateTime::createFromFormat('Y-m-d  H-i',$expire_date));

            $em->persist($fichier);
            $em->flush();

            return $this->redirectToRoute('projet_list_depot',['idProjet'=>$idProjet]);
        }
        return $this->render('fichier/fichierForm.html.twig', [
            'form' => $form->createView(),'idProjet'=>$idProjet
        ]);
    }

    /**
     * @Route("/projet/{idProjet}/editFichier", name="edit_fichier")
     * @param Request $request
     * @param SluggerInterface $slugger
     * @param $idFichier
     * @param $idProjet
     */
    public function editFichier(Request $request, SluggerInterface $slugger, $idFichier = null, $idProjet = null)
    {
        //On récupère le projet
        $idFichier = $request->get('idFichier');
        $fichier = $this->getDoctrine()->getRepository(Fichier::class)->find($idFichier);
        //On verifie que le projet existe
        if (!$fichier)  throw $this->createNotFoundException('No fichier found for id '.$idFichier);

        $idProjet = $request->attributes->get('idProjet');

        //On crée le formulaire
        $form = $this->createForm(FichierType::class, $fichier);
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
                $fichier->setBrochureFilename($newFilename);
            }
            $literalTime = new DateTime('now');
            $expire_date = $literalTime->format("Y-m-d H-i");

            $fichier->setDateDepot(\DateTime::createFromFormat('Y-m-d H-i',$expire_date));
            $em->persist($fichier);
            $em->flush();

            return $this->redirectToRoute('projet_list_depot',['idProjet'=>$idProjet]);
        }
        return $this->render('fichier/fichierForm.html.twig', [
            'form' => $form->createView(),'idProjet'=>$idProjet
        ]);
    }
    /**
     * @Route("/projet/{idProjet}/deleteFichier", name="delete_fichier", methods={"DELETE"})
     * @param Request $request
     * @param $idProjet
     */
    public function deleteFichier(Request $request, $idProjet = null)
    {
        if(!$this->isCsrfTokenValid('fichier_delete', $request->get('token'))) {
            throw new  InvalidCsrfTokenException('Invalid CSRF token formulaire fichier');
        }
        $em = $this->getDoctrine()->getManager();
        $idProjet = $request->attributes->get('idProjet');
        $idFichier = $request->request->get('idFichier');

        $fichier = $em->getRepository(Fichier::class)->find($idFichier);
        //On verifie que le projet existe
        if (!$fichier)  throw $this->createNotFoundException('No fichier found for id '.$idFichier);

        $em->remove($fichier);
        $em->flush();
        return $this->redirectToRoute('projet_list_depot',['idProjet'=>$idProjet]);
    }
}

<?php

namespace App\Controller;

use App\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Swift_Mailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

/**
* Require ROLE_ADMIN for *every* controller method in this class.
*
* @IsGranted("ROLE_ADMIN")
*/

class AdminController extends AbstractController
{
    private $passwordEncoder;
    private $token;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, TokenGeneratorInterface $token)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->token = $token;
    }
/* ---------------------------------
      CREATION DE UTILISATEUR
--------------------------------- */
    /**
     * @Route("/admin/creerUtilisateur", name="admin_creer_utilisateur", methods={"GET","POST"})
     * @param Request $request
     * @param Swift_Mailer $mailer
     */
    public function addUser(Request $request,\Swift_Mailer $mailer)
    {
        if ($request->getMethod() == 'GET') {
            return $this->render('admin/addAccount.html.twig');
        }
        if (!$this->isCsrfTokenValid('form_user', $request->get('token'))) {
            throw new  InvalidCsrfTokenException('Invalid CSRF token formulaire produit');
        }

        //On récupère les données du formulaire
        $donnees['email'] = $request->request->get('email');
        $donnees['role'] = $request->request->get('role');
        //On verifie les erreurs
        $erreurs = $this->validatorUser($donnees);
        //Si il n'y a pas d'erreurs
        if (empty($erreurs)) {
            /*
             * On crée un User avec les données
             */
            $user = new User();
            if($donnees['role']=='1'){
                $user->setRoles(['ROLE_PROF']);
            }else{
                $user->setRoles(['ROLE_USER']);
            }

            //génération d'un mote de passe aléatoire
            $characters = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z");
            $password ='';
            for($i=0;$i<10;$i++)
            {
                $password .= ($i%2) ? strtoupper($characters[array_rand($characters)]) : $characters[array_rand($characters)];
            }

            $user->setEmail($donnees['email'])
                ->setPassword($this->passwordEncoder->encodePassword($user, $password))
                ->setIsActive('0')
                ->setTokenMail($this->token->generateToken());

            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();

            /*
             * On envoie un mail pour changer de mot de passe
             */
            //On crée le lien contenu dans le mail
            $link = 'http://127.0.0.1:8000/resetpassword/'.$user->getTokenMail();
            //On crée le mail
            $message = (new \Swift_Message('Nouveau contact'))
                // On attribue l'expéditeur
                ->setFrom('appli@appli.com')

                // On attribue le destinataire
                ->setTo($user->getEmail())

                // On crée le texte avec la vue
                ->setBody(
                    $this->renderView(
                        'email/newUserMail.html.twig', compact('user','link')
                    ),
                    'text/html'
                );
            //On envoie le mail
            $mailer->send($message);
            return $this->redirectToRoute('admin_creer_utilisateur');
        }
        //si erreur, on revient sur le formulaire
        return $this->render('admin/addAccount.html.twig',['donnees'=>$donnees,'erreurs'=>$erreurs]);
    }


    private function validatorUser(array $donnees)
    {
        $erreurs=[];
        if(count($this->getDoctrine()->getRepository(User::class)->getSameEmail($donnees['email']))>=1)
            $erreurs['email']='Un compte utilise déjà cette addresse';

        if(empty($donnees['email']))
            $erreurs['email']='Veuillez indiquer une addresse';

        if(empty($donnees['role']))
            $erreurs['role']='Veuillez sélectionner un role';


        return $erreurs;
    }
/* ---------------------------------
      GESTION DES UTILISATEURS
--------------------------------- */
    /**
     * @Route("/admin/gestionUtilisateur", name="admin_gestion_utilisateur", methods={"GET","POST"})
     * @param Request $request
     */
    public function listUser(Request $request)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findAll();
        return $this->render('admin/listUser.html.twig',['donnees'=>$user]);
    }

    /**
     * @Route("/admin/gestionUtilisateur/delete", name="admin_delete_utilisateur", methods={"DELETE"})
     * @param Request $request
     */
    public function deleteUser(Request $request)
    {
        if(!$this->isCsrfTokenValid('user_delete', $request->get('token'))) {
            throw new  InvalidCsrfTokenException('Invalid CSRF token formulaire user');
        }
        $em = $this->getDoctrine()->getManager();
        $id= $request->request->get('id');
        $user = $em->getRepository(User::class)->find($id);
        if (!$user)  throw $this->createNotFoundException('No user found for id '.$id);
        $em->remove($user);
        $em->flush();
        return $this->redirectToRoute('admin_gestion_utilisateur');
    }

    /**
     * @Route("/admin/gestionUtilisateur/edit/{id}", name="admin_edit_utilisateur", methods={"GET"})
     * @Route("/admin/gestionUtilisateur/edit/form", name="admin_edit_utilisateur_form", methods={"PUT"})
     * @param Request $request
     * @param $id
     */
    public function editUser(Request $request, $id = null)
    {
        $em = $this->getDoctrine()->getManager();
        $id=$request->get('id');
        if ($request->getMethod() == 'GET') {
            $user = $em->getRepository(User::class)->findOneBy(['id'=>$id]);

            if (!$user) throw $this->createNotFoundException('No user found for id ' . $id);

            return $this->render('/admin/editUser.html.twig', ['donnees' => $user]);
        }

        if (!$this->isCsrfTokenValid('form_user', $request->get('token'))) {
            throw new InvalidCsrfTokenException('Invalid CSRF token formulaire user');
        }

        $donnees['email'] =  $request->request->get('email');
        $donnees['role'] = $request->request->get('role');
        $donnees['nom'] = $request->request->get('nom');
        $donnees['prenom'] = $request->request->get('prenom');

        $donnees['id'] = $id;

        $erreurs = $this->validatorEditUser($donnees);

        if (empty($erreurs)) {

            $user = $em->getRepository(User::class)->findOneBy(['id'=>$id]);

            if (!$user) throw $this->createNotFoundException('No user found for id' . $id);

            $user->setNom($donnees['nom']);
            $user->setPrenom($donnees['prenom']);
            if($donnees['role']=='1'){
                $user->setRoles(['ROLE_PROF']);
            }else{
                $user->setRoles(['ROLE_USER']);
            }
            $em->persist($user);
            $em->flush();
            return $this->redirectToRoute('admin_gestion_utilisateur');
        }

        $user = $em->getRepository(User::class)->findOneBy(['email'=>$id]);
        return $this->render('/admin/editUser.html.twig', ['donnees' => $user, 'erreurs'=>$erreurs]);
    }

    private function validatorEditUser(array $donnees)
    {
        $erreurs=[];
        if(count($this->getDoctrine()->getRepository(User::class)->getSameEmailOnChange($donnees['email'],$donnees['id']))>=1)
            $erreurs['email']='Un compte utilise déjà cette addresse';

        if(empty($donnees['email']))
            $erreurs['email']='Veuillez indiquer une addresse';

        return $erreurs;
    }
}
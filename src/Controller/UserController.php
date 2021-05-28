<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class UserController extends AbstractController
{
    /* ---------------------------------
             MODIFICATION PROFIL
     --------------------------------- */
    private $token;

    public function __construct(TokenGeneratorInterface $token)
    {
        $this->token = $token;
    }

    /**
     * @Route("/profil", name="user_profil")
     */
    public function showProfil()
    {
        $user = $this->getUser();
        return $this->render('user/editUser.html.twig', ['donnees' => $user]);
    }

    /**
     * @Route("/profil/edit", name="user_profil_edit", methods={"POST"})
     * @param $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editProfil(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('form_profil', $request->get('token'))) {
            throw new InvalidCsrfTokenException('Invalid CSRF token formulaire user');
        }

        $donnees['nom'] = $request->request->get('nom');
        $donnees['prenom'] = $request->request->get('prenom');
        $donnees['email'] = $request->request->get('email');

        $donnees['id'] = $this->getUser()->getId();

        $erreurs = $this->validatorEditProfil($donnees);

        if (empty($erreurs)) {

            $user->setEmail($donnees['email']);
            $user->setNom($donnees['nom']);
            $user->setPrenom($donnees['prenom']);

            $em->persist($user);
            $em->flush();
            return $this->redirectToRoute('user_profil');
        }
        return $this->render('user/editUser.html.twig', ['donnees' => $user, 'erreurs' => $erreurs]);
    }

    private function validatorEditProfil(array $donnees)
    {
        $erreurs = [];
        if (count($this->getDoctrine()->getRepository(User::class)->getSameEmailOnChange($donnees['email'],$donnees['id'])) >= 1)
            $erreurs['email'] = 'Un compte utilise déjà cette addresse';

        if (empty($donnees['email']))
            $erreurs['email'] = 'Veuillez indiquer une addresse';

        return $erreurs;
    }
    /* ---------------------------------
            RESET PASSWORD
    --------------------------------- */
    /**
     * @Route("/resetPassword", name="reset_password")
     * @param $request
     * @return Response
     */
    public function resetPasswordForm(Request $request, \Swift_Mailer $mailer,UserRepository $userRepository)
    {
        if ($request->getMethod() == 'GET') {
            return $this->render('user/emailResetPassword.html.twig');
        }
        $donnees['email'] = $request->request->get('email');
        $erreurs = $this->validatorEmail($donnees);

        if (empty($erreurs)) {
            $id = $userRepository->findByMail($donnees['email'])[0]['id'];

            $user = $this->getDoctrine()->getRepository(User::class)->find($id);

            $user->setTokenMail($this->token->generateToken());
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();

            $link = 'http://127.0.0.1:8000/resetpassword/' . $user->getTokenMail();

            $message = (new \Swift_Message('Nouveau contact'))
                // On attribue l'expéditeur
                ->setFrom('appli@appli.com')

                // On attribue le destinataire
                ->setTo($donnees['email'])

                // On crée le texte avec la vue
                ->setBody(
                    $this->renderView(
                        'email/newUserMail.html.twig', compact('user', 'link')
                    ),
                    'text/html'
                );
            $mailer->send($message);
            $this->addFlash('notice', 'email envoyé');
            return $this->redirectToRoute('accueil');
        }

        return $this->render('user/emailResetPassword.html.twig', ['erreurs' => $erreurs]);

    }

    private function validatorEmail(array $donnees)
    {
        $erreurs = [];
        if (empty($donnees['email'])) {
            $erreurs['email'] = 'Veuillez indiquer une addresse';
        } else {
            if (count($this->getDoctrine()->getRepository(User::class)->getSameEmail($donnees['email'])) == 0)
                $erreurs['email'] = 'Aucun compte n\'est enregistrer à cette addresse';
        }

        return $erreurs;
    }
}

<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private $passwordEncoder;
    private $token;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, TokenGeneratorInterface $token)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->token = $token;
    }
    /**
     * @Route("/", name="start")
     */
    public function start()
    {
        return $this->redirectToRoute('app_login');
    }
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/login/{token}", name="app_login_token", methods={"GET"})
     * @param UserRepository $userRepository
     * @param String|null $token
     * @return Response
     */
    public function loginWithToken(UserRepository $userRepository, String $token): Response
    {
        if($userRepository->findToken($token) != null) {
            $user = $userRepository->find($userRepository->findToken($token)[0]);
            if($user->getTokenMail() == $token && $user->getIsActive()==0) {
                $user->setIsActive(1);
                $this->getDoctrine()->getManager()->persist($user);
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success_account',"Compte validé avec succès");
            }
        }
        return $this->redirectToRoute('app_login');
    }

    /**
     * @Route("/resetpassword/{token}", name="reset_password_token")
     * @param UserRepository $userRepository
     * @param String|null $token
     * @param Request $request
     * @return Response
     */
    public function resetPasswordToken(UserRepository $userRepository, String $token, Request $request): Response
    {
        if($userRepository->findToken($token) != null) {
            $user = $userRepository->find($userRepository->findToken($token)[0]);
            if($user->getTokenMail() == $token) {
                if ($request->getMethod() == 'GET') {
                    return $this->render('security/resetPassword.html.twig',[
                        'id' => $user->getTokenMail()
                    ]);
                }
            }
        }
        return $this->redirectToRoute('app_login');
    }

    /**
     * @Route("/changepassword/{id}", name="change_password")
     * @param String $id
     * @param UserRepository $userRepository
     * @return Response
     */
    public function resetPassword(String $id, UserRepository $userRepository) {
        if ($userRepository->findToken($id) != null) {
            $user = $userRepository->find($userRepository->findToken($id)[0]);
            if ($_POST['mdp1'] == $_POST['mdp2']) {
                $password = $this->passwordEncoder->encodePassword($user, $_POST['mdp1']);
                $user->setPassword($password)
                    ->setTokenMail($this->token->generateToken());
                $this->getDoctrine()->getManager()->persist($user);
                $this->getDoctrine()->getManager()->flush();
                return $this->redirectToRoute('app_login');
            } else {
                $erreurs = "Les mots de passes ne matchent pas";
                return $this->render('security/resetPassword.html.twig', [
                    'erreurs' => $erreurs,
                    'id' => $id
                ]);
            }
        }
        return $this->render('security/login.html.twig');
    }

}

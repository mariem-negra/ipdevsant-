<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils, 
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        LoggerInterface $logger // Add logger
    ): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        
        // Check if there was a login error
        if ($error && $lastUsername) {
            $user = $utilisateurRepository->findOneBy(['email' => $lastUsername]);
            if ($user) {
                $logger->info('Login attempt for user: ' . $lastUsername); // Log the attempt
                
                // Check if account is locked
                if ($user->isLocked()) {
                    $this->addFlash('warning', 'Votre compte est temporairement bloqué. Veuillez réessayer plus tard ou consultez votre email pour le débloquer.');
                    return $this->render('security/login.html.twig', [
                        'last_username' => $lastUsername,
                        'error' => null
                    ]);
                }
    
                // Begin transaction
                $entityManager->beginTransaction();
                
                try {
                    // Increment login attempts
                    $user->setLoginAttempts($user->getLoginAttempts() + 1);
                    $logger->info('Incremented login attempts to: ' . $user->getLoginAttempts()); // Log the new count
    
                    // If attempts exceed 3, lock the account and send security email
                    if ($user->getLoginAttempts() >= 3) {
                        $logger->info('User has exceeded login attempts. Locking account and sending email.');
    
                        // Lock account for 30 minutes
                        $lockedUntil = new \DateTime('+30 minutes');
                        $user->setLockedUntil($lockedUntil);
                        
                        // Generate verification token
                        $verificationToken = bin2hex(random_bytes(32));
                        $tokenExpiresAt = new \DateTime('+30 minutes');
                        
                        // Generate one-time password (OTP)
                        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                        $logger->info('Before setting values: OTP = ' . $user->getOtp() . ', Token = ' . $user->getVerificationToken());

                        // Save token and OTP to user
                        $user->setVerificationToken($verificationToken);
                        $user->setTokenExpiresAt($tokenExpiresAt);
                        $user->setOtp($otp);
                        
                        // Generate URLs
                        $verifyUrl = $this->generateUrl('security_verify_account', 
                            ['token' => $verificationToken], 
                            UrlGeneratorInterface::ABSOLUTE_URL
                        );
                        
                        $notMeUrl = $this->generateUrl('security_not_me', 
                            ['token' => $verificationToken], 
                            UrlGeneratorInterface::ABSOLUTE_URL
                        );
                        
                        $logger->info('Generated verify URL: ' . $verifyUrl);
                        $logger->info('Generated notMe URL: ' . $notMeUrl);
                        $logger->info('Generated OTP: ' . $otp);
    
                        // Send security alert email
                        try {
                            $email = (new Email())
                                ->from('chronoserena@gmail.com')
                                ->to($user->getEmail())
                                ->subject('Alerte de Sécurité - Tentatives de Connexion Multiples')
                                ->html($this->renderView('emails/security_alert.html.twig', [
                                    'user' => $user,
                                    'verifyUrl' => $verifyUrl,
                                    'notMeUrl' => $notMeUrl,
                                    'otp' => $otp,
                                    'expiresAt' => $tokenExpiresAt
                                ]));
                            
                            $mailer->send($email);
                            $logger->info('Security email sent to: ' . $user->getEmail());
                        } catch (\Exception $e) {
                            $logger->error('Failed to send email: ' . $e->getMessage());
                            // Continue with locking the account even if email fails
                        }
                        
                        $this->addFlash('warning', 'Suite à plusieurs tentatives échouées, votre compte a été temporairement bloqué. Un email de sécurité a été envoyé à votre adresse.');
                    }
                    $logger->info('After setting values: OTP = ' . $user->getOtp() . ', Token = ' . $user->getVerificationToken());

                    
                    // Persist changes
                    try {
                        $entityManager->persist($user);
                        $entityManager->flush();
                        $logger->info('User data successfully flushed to the database');
                    } catch (\Exception $e) {
                        $logger->error('Error flushing user data: ' . $e->getMessage());
                    }
                    

                    // Commit transaction
                    $entityManager->commit();
                } catch (\Exception $e) {
                    // Rollback transaction on error
                    $entityManager->rollback();
                    $logger->error('Error in login process: ' . $e->getMessage());
                }
                
                // Check if user is a doctor with unverified account
                if ($user->getRole()->value === 'Médecin' && !$user->isVerified()) {
                    $this->addFlash('warning', 'Votre compte est en attente de vérification par l\'administrateur.');
                }
            }
        }
        
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error
        ]);
    }

    #[Route(path: '/security/verify/{token}', name: 'security_verify_account')]
    public function verifyAccount(
        string $token, 
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $utilisateurRepository->findOneBy(['verificationToken' => $token]);
        
        if (!$user) {
            $this->addFlash('error', 'Lien de vérification invalide.');
            return $this->redirectToRoute('app_login');
        }
        
        // Check if token has expired
        if ($user->getTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Ce lien de vérification a expiré. Veuillez réessayer de vous connecter.');
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('security/verify_account.html.twig', [
            'user' => $user,
            'token' => $token
        ]);
    }

    #[Route(path: '/security/verify-otp/{token}', name: 'security_verify_otp', methods: ['POST'])]
    public function verifyOtp(
        Request $request,
        string $token,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $utilisateurRepository->findOneBy(['verificationToken' => $token]);
        $submittedOtp = $request->request->get('otp');
        
        if (!$user || $user->getOtp() !== $submittedOtp) {
            $this->addFlash('error', 'Code OTP invalide. Veuillez réessayer.');
            return $this->redirectToRoute('security_verify_account', ['token' => $token]);
        }
        
        // Reset security measures
        $user->setLoginAttempts(0);
        $user->setLockedUntil(null);
        $user->setVerificationToken(null);
        $user->setTokenExpiresAt(null);
        $user->setOtp(null);
        
        $entityManager->persist($user);
        $entityManager->flush();
        
        $this->addFlash('success', 'Votre compte a été débloqué avec succès. Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/security/not-me/{token}', name: 'security_not_me')]
    public function notMe(
        string $token,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $user = $utilisateurRepository->findOneBy(['verificationToken' => $token]);
        
        if (!$user) {
            $this->addFlash('error', 'Lien invalide.');
            return $this->redirectToRoute('app_login');
        }
        
        // Set higher security measures
        $user->setLoginAttempts(0);
        $user->setLockedUntil(new \DateTime('+24 hours')); // Lock for 24 hours
        $user->setVerificationToken(null);
        $user->setTokenExpiresAt(null);
        $user->setOtp(null);
        
        $entityManager->persist($user);
        $entityManager->flush();
        
        return $this->render('security/account_secured.html.twig', [
            'user' => $user
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be intercepted by the logout key on your firewall.');
    }
}
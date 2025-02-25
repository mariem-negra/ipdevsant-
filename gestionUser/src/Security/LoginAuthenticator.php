<?php

namespace App\Security;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        

    ) {
    }
    private function sendLockoutEmail(Utilisateur $user): void
    {
        try {
            $email = (new Email())
                ->from('chronoserena@gmail.com')  // Use your actual Gmail address
                ->to($user->getEmail())
                ->subject('Compte temporairement bloqué')
                ->html('
                    <h2>Votre compte a été temporairement bloqué</h2>
                    <p>Votre compte a été temporairement bloqué suite à plusieurs tentatives de connexion échouées.</p>
                    <p>Vous pourrez réessayer dans 10 minutes.</p>
                    <p>Si vous n\'avez pas tenté de vous connecter, veuillez changer votre mot de passe immédiatement.</p>
                ');
            
            $this->mailer->send($email);
            $this->logger->info('Lockout email sent successfully to ' . $user->getEmail());
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send lockout email: ' . $e->getMessage());
            // Still throw the authentication exception, but log the email failure
            throw new CustomUserMessageAuthenticationException(
                'Votre compte a été temporairement bloqué. (Note: notification email failed to send)'
            );
        }
    }
    public function authenticate(Request $request): Passport
    {
        
            $email = $request->request->get('_username');
            $password = $request->request->get('_password');
            $csrfToken = $request->request->get('_csrf_token'); // Add CSRF token
            $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
            if ($user && $user->isLocked()) {
                throw new CustomUserMessageAuthenticationException(
                    'Votre compte est temporairement bloqué. Veuillez réessayer dans quelques minutes.'
                );
            }
    
            if ($user) {
                // Increment login attempts before authentication
                $user->setLoginAttempts($user->getLoginAttempts() + 1);
                
                if ($user->getLoginAttempts() >= 3) {
                    $lockedUntil = new \DateTime('+10 minutes');
                    $user->setLockedUntil($lockedUntil);
                    
                                        
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                    $this->sendLockoutEmail($user);

                    throw new CustomUserMessageAuthenticationException(
                        'Votre compte a été temporairement bloqué. Un email vous a été envoyé.'
                    );
                }
                
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }
    
            return new Passport(
                new UserBadge($email),
                new PasswordCredentials($password),
                [
                    new CsrfTokenBadge('authenticate', $csrfToken), // Add CSRF token badge
                    new RememberMeBadge(), // Add Remember Me badge if needed
                ]
            );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {

        $user = $token->getUser();
        if ($user instanceof Utilisateur) {
            $user->setLoginAttempts(0);
            $user->setLockedUntil(null);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
        // Redirect based on the user's role
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return new RedirectResponse($this->urlGenerator->generate('app_utilisateur_index'));
        } else {
            // Both PATIENT and MEDECIN go to home
            return new RedirectResponse($this->urlGenerator->generate('app_home'));
        }   
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}

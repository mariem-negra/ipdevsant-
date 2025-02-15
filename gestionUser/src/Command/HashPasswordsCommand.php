<?php
namespace App\Command;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class HashPasswordsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:hash-passwords')
            ->setDescription('Hash all existing plain passwords in the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->entityManager->getRepository(Utilisateur::class)->findAll();

        foreach ($users as $user) {
            $plainPassword = $user->getMotDePasse(); // Get current plain password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setMotDePasse($hashedPassword);
        }

        $this->entityManager->flush();
        $output->writeln('All passwords have been hashed.');

        return Command::SUCCESS;
    }
}
<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test-email',
    description: 'Test email sending functionality with connection check',
)]
class TestEmailCommand extends Command
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            $io->info('Testing SMTP connection...');
            
            // Test direct SMTP connection
            $transport = new EsmtpTransport(
                'smtp.gmail.com',
                465,
                true
            );
            
            $transport->setUsername('chronoserena@gmail.com');
            $transport->setPassword('sanopxqqsckvygqo');

            $io->info('Attempting to connect to SMTP server...');
            $transport->start();
            $io->info('SMTP connection successful!');

            // Now try sending email
            $email = (new Email())
                ->from('chronoserena@gmail.com')
                ->to('issaouiameni28@gmail.com')
                ->subject('Connection Test Email - ' . date('Y-m-d H:i:s'))
                ->text('This is a connection test email.');

            $io->info('Attempting to send test email...');
            $transport->send($email);
            
            $io->success([
                'Connection and sending test completed successfully',
                'Please check your email (including spam folder)'
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error([
                'Connection test failed',
                'Error: ' . $e->getMessage(),
                'This might indicate a firewall or antivirus issue'
            ]);
            
            return Command::FAILURE;
        }
    }
}
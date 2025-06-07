<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Crée un compte admin')]
class CreateSuperAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        if (!$helper instanceof \Symfony\Component\Console\Helper\QuestionHelper) {
            $helper = new \Symfony\Component\Console\Helper\QuestionHelper();
        }

        $questionEmail = new Question('Email du admin : ');
        $email = $helper->ask($input, $output, $questionEmail);

        $questionPassword = new Question('Mot de passe du admin : ');
        $questionPassword->setHidden(true);
        $questionPassword->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $questionPassword);

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setEnabled(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Admin créé avec succès !</info>');
        return Command::SUCCESS;
    }
}

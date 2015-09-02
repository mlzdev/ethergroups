<?php
namespace Ethergroups\MainBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Entities\Users;

class RemoveOldInvitationsCommand extends ContainerAwareCommand {
    
    protected function configure()
    {
        $this
        ->setName('Ethergroups:removeOldInvitations')
        ->setDescription('Removes Invitations, which are older then x days')
        ->addArgument('days', InputArgument::OPTIONAL, 'How old an invitation be? (In days / Default: 21)')
        //->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get all neccessary services
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // Get all invitations
        $invitations = $em->getRepository('EthergroupsMainBundle:Invitation')->findAll();

        $days = $input->getArgument('days');

        $days = (isset($days)) ? $days : $this->getContainer()->getParameter('invitation.maxdays');

        $daysAgo = strtotime("today midnight -$days days");

        $count = 0;
        foreach ($invitations as $invitation) {
            if($invitation->getCreated() < $daysAgo) {
                $output->writeln(
                    'Invitation for user: '.$invitation->getUser()->getUid().' and group: '.$invitation->getGroup()->getGroupid().' removed'
                );
                $em->remove($invitation);
                $count++;
            }
        }
        $em->flush();
        
        $output->writeln('successfuly removed '.$count.' invitations');
    }
}
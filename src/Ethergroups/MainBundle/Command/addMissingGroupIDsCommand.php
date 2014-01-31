<?php
namespace Ethergroups\MainBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Entities\Users;

class addMissingGroupIDsCommand extends ContainerAwareCommand {
    
    protected function configure()
    {
        $this
        ->setName('Ethergroups:addMissingGroupIDs')
        ->setDescription('If a groupID is missing, get a new groupID from the etherpad server and add it')
        //->addArgument('name', InputArgument::OPTIONAL, 'Who do you want to greet?')
        //->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get all neccessary services
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $etherpad = $this->getContainer()->get('etherpadlite');
        
        // Get all local groups
        $localgroups = $em->getRepository('EthergroupsMainBundle:Groups')->findAll();
        
        foreach ($localgroups as $localgroup) {
            $groupID = $localgroup->getGroupid();
            // group has no groupID
            if(!$groupID) {
                $localgroup->setGroupid($etherpad->createGroup()->groupID);
                $output->writeln('Added groupID: '.$localgroup->getGroupid().' to group: '.$localgroup->getId().','.$localgroup->getName());
            }
        }
        $em->flush();
        
        $output->writeln('successful');
    }
}
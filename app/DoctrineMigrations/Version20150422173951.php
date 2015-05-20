<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
use Ethergroups\MainBundle\Entity\Invitation;
use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150422173951 extends AbstractMigration implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE Invitation (user_id INT NOT NULL, group_id INT NOT NULL, created INT NOT NULL, PRIMARY KEY(user_id, group_id))');
        $this->addSql('CREATE INDEX IDX_BE406272A76ED395 ON Invitation (user_id)');
        $this->addSql('CREATE INDEX IDX_BE406272FE54D947 ON Invitation (group_id)');
        $this->addSql('ALTER TABLE Invitation ADD CONSTRAINT FK_BE406272A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE Invitation ADD CONSTRAINT FK_BE406272FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema) {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $query = "SELECT * FROM users_groups";
        $stmt = $this->connection->prepare($query);
        $stmt->execute();

        while($row = $stmt->fetch()) {
            $invitation = new Invitation();
            $user = $em->find('EthergroupsMainBundle:Users', $row['users_id']);
            $invitation->setUser($user);
            $group = $em->find('EthergroupsMainBundle:Groups', $row['groups_id']);
            $invitation->setGroup($group);
            $invitation->setCreated(time());

            $em->persist($invitation);
        }

//        $em->flush();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE pads_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE groups_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('DROP TABLE Invitation');
    }
}

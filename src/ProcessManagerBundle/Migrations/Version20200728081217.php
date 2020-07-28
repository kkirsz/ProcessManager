<?php

namespace ProcessManagerBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200728081217 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $processesTable = $schema->getTable("process_manager_processes");
        $processesTable->addColumn('executable', 'integer', ['notnull' => false]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $processesTable = $schema->getTable("process_manager_processes");
        $processesTable->dropColumn("executable");
    }
}

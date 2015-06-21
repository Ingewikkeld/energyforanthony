<?php

use Phinx\Migration\AbstractMigration;

class AddLineColor extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     */
    public function change()
    {
        $table = $this->table('energy');
        $table->addColumn('line_color', 'string', ['default' => 'DarkBlue'])->update();
    }
}

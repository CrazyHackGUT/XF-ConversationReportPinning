<?php

/**
 * This file is a part of [Report Improvements] Conversation Pinning.
 * All rights reserved.
 *
 * Developed by SourceModders.
 */

namespace SModders\ConversationReportPinning;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1()
    {
        $this->alterTable('xf_conversation_master', function (Alter $table)
        {
            $table->addColumn('smcrp_report_id', 'integer')->nullable()
                ->comment('Refer to the related assigned report');
        });
        
        $this->alterTable('xf_report', function (Alter $table)
        {
            $table->addColumn('smcrp_conversation_id', 'integer')->nullable()
                ->comment('Refer to the related assigned conversation');
        });
    }
    
    public function uninstallStep1()
    {
        $this->alterTable('xf_conversation_master', function(Alter $table)
        {
            $table->dropColumns(['smcrp_report_id']);
        });
        
        $this->alterTable('xf_report', function (Alter $table)
        {
            $table->dropColumns(['smcrp_report_id']);
        });
    }
}
<?php

/**
 * This file is a part of [Report Improvements] Conversation Pinning.
 * All rights reserved.
 *
 * Developed by SourceModders.
 */

namespace SModders\ConversationReportPinning;


use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Manager;
use XF\Mvc\Entity\Structure;

class Listener
{
    /**
     * Appends the new column definition (and relation) to the Conversation Master entity.
     *
     * @param Manager $em
     * @param Structure $structure
     * @return Structure
     */
    public static function onConversationMasterStructure(Manager $em, Structure &$structure)
    {
        $structure->columns += [
            'smcrp_report_id' => ['type' => Entity::UINT, 'nullable' => true]
        ];
        $structure->relations += [
            'Report' => [
                'entity' => 'XF:Report',
                'type' => Entity::TO_ONE,
                'conditions' => [['report_id', '=', '$smcrp_report_id']],
                'primary' => true
            ]
        ];
        
        return $structure;
    }
    
    /**
     * Appends the new column definition (and relation) to the Report entity.
     *
     * @param Manager $em
     * @param Structure $structure
     * @return Structure
     */
    public static function onReportStructure(Manager $em, Structure &$structure)
    {
        $structure->columns += [
            'smcrp_conversation_id' => ['type' => Entity::UINT, 'nullable' => true]
        ];
        $structure->relations += [
            'Conversation' => [
                'entity' => 'XF:ConversationMaster',
                'type' => Entity::TO_ONE,
                'conditions' => [['conversation_id', '=', '$smcrp_conversation_id']],
                'primary' => true
            ]
        ];
        
        return $structure;
    }
}
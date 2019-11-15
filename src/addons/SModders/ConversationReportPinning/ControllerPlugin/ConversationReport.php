<?php

/**
 * This file is a part of [Report Improvements] Conversation Pinning.
 * All rights reserved.
 *
 * Developed by SourceModders.
 */

namespace SModders\ConversationReportPinning\ControllerPlugin;


use XF\ControllerPlugin\AbstractPlugin;
use XF\Phrase;

class ConversationReport extends AbstractPlugin
{
    /**
     * @param $reportId
     * @param $conversationId
     * @throws \XF\Mvc\Reply\Exception
     */
    public function assignReportToConversation($reportId, $conversationId)
    {
        $report = $this->assertReportViewable($reportId);
        $conversation = $this->assertConversationViewable($conversationId);

        $db = $this->app->db();
        $db->beginTransaction();
    
        if ($report) {
            /** @var \XF\Entity\ConversationMaster $oldConversation */
            $oldConversation = $report->Conversation;
            $report->fastUpdate('smcrp_conversation_id', $conversationId);
            
            if ($oldConversation)
            {
                $oldConversation->fastUpdate('smcrp_report_id');
            }
        }
    
        if ($conversation)
        {
            /** @var \XF\Entity\Report $oldReport */
            $oldReport = $conversation->Report;
            $conversation->fastUpdate('smcrp_report_id', $reportId);
            
            if ($oldReport)
            {
                $oldReport->fastUpdate('smcrp_conversation_id');
            }
        }

        $db->commit();
        
        $phraseParams = [
            'conversation' => $conversation ? $conversation->title : \XF::phrase('smcrp.no_conversation'),
            'report' => $report ? $report->getTitle() : \XF::phrase('smcrp.no_report')
        ];
        
        $requestUri = $this->request->get('_xfRequestUri');
        return $this->redirect($requestUri, \XF::phrase('smcrp.assign_conversation_success', $phraseParams));
    }
    
    /**
     * @param $conversationId
     * @return \XF\Entity\ConversationMaster|null
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertConversationViewable(&$conversationId)
    {
        return $this->assertEntityViewable('XF:ConversationMaster', $conversationId, \XF::phrase('requested_conversation_not_found'));
    }
    
    /**
     * @param $reportId
     * @return \XF\Entity\Report|null
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertReportViewable(&$reportId)
    {
        return $this->assertEntityViewable('XF:Report', $reportId, \XF::phrase('requested_report_not_found'));
    }
    
    /**
     * @param $shortName
     * @param $id
     * @param string|Phrase|null $notFound
     * @param string|Phrase|null $noPermission
     *
     * @return \XF\Mvc\Entity\Entity|null
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertEntityViewable($shortName, &$id, $notFound = null, $noPermission = null)
    {
        if ($id == 0)
        {
            $id = null;
            return null;
        }

        $entity = $this->em->find($shortName, $id);
        if (!$entity)
        {
            throw $this->exception($this->notFound($notFound));
        }
        
        if (!$entity->canView())
        {
            throw $this->exception($this->noPermission($noPermission));
        }
        
        return $entity;
    }
}
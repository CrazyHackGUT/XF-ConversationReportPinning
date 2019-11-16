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
        $report = $this->assertReportExists($reportId);
        $conversation = $this->assertConversationExists($conversationId);

        $db = $this->app->db();
        $db->beginTransaction();
    
        if ($report) {
            /** @var \XF\Entity\ConversationMaster $oldConversation */
            $oldConversation = $report->Conversation;
            if ($conversationId != $report->smcrp_conversation_id)
            {
                $this->assertEntityViewable($conversation);
            }

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
            if ($reportId != $conversation->smcrp_report_id)
            {
                $this->assertEntityViewable($conversation);
            }
            
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
    protected function assertConversationExists(&$conversationId)
    {
        return $this->assertEntityExists('XF:ConversationMaster', $conversationId, \XF::phrase('requested_conversation_not_found'));
    }
    
    /**
     * @param $reportId
     * @return \XF\Entity\Report|null
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertReportExists(&$reportId)
    {
        return $this->assertEntityExists('XF:Report', $reportId, \XF::phrase('requested_report_not_found'));
    }
    
    /**
     * @param $shortName
     * @param $id
     * @param string|Phrase|null $notFound
     *
     * @return \XF\Mvc\Entity\Entity|null
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertEntityExists($shortName, &$id, $notFound = null)
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
        
        return $entity;
    }
    
    /**
     * @param Entity $entity
     * @param string|Phrase|null $noPermission
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertEntityViewable($entity, $noPermission = null)
    {
        if ($entity === null)
        {
            return;
        }
        
        if (!$entity->canView())
        {
            throw $this->exception($this->noPermission($noPermission));
        }
    }
}
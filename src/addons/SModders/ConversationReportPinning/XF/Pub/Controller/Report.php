<?php

/**
 * This file is a part of [Report Improvements] Conversation Pinning.
 * All rights reserved.
 *
 * Developed by SourceModders.
 */

namespace SModders\ConversationReportPinning\XF\Pub\Controller;


use XF\Mvc\ParameterBag;

class Report extends XFCP_Report
{
    public function actionAssignConversation(ParameterBag $params)
    {
        $report = $this->assertViewableReport($params->report_id);
    
        if ($this->isPost())
        {
            $targetConversationId = $this->filter('target_conversation_id', 'int');
        
            return $this->plugin('SModders\ConversationReportPinning:ConversationReport')
                ->assignReportToConversation($report->report_id, $targetConversationId);
        }
        
        /** @var \XF\Mvc\Entity\ArrayCollection $conversations */
        $conversations = $this->repository('XF:Conversation')
            ->findUserConversations(\XF::visitor(), true)
            ->with('Master')->fetch();

        $viewParams = [
            'report' => $report,
            'conversations' => $conversations,

            'assigned_conversation' => $report->smcrp_conversation_id ?: 0
        ];
        
        return $this->view('SModders\ConversationReportPinning:Report\AssignConversation', 'smcrp_report_assign', $viewParams);
    }
}
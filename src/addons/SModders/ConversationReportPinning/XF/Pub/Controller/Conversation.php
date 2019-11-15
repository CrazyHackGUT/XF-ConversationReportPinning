<?php

/**
 * This file is a part of [Report Improvements] Conversation Pinning.
 * All rights reserved.
 *
 * Developed by SourceModders.
 */

namespace SModders\ConversationReportPinning\XF\Pub\Controller;


use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;

class Conversation extends XFCP_Conversation
{
    public function actionAssignReport(ParameterBag $params)
    {
        $this->assertHasPermission('general', 'viewWarning');
        $masterConv = $this->assertViewableUserConversation($params->conversation_id)->Master;
        
        if ($this->isPost())
        {
            $targetReportId = $this->filter('target_report_id', 'int');

            return $this->plugin('SModders\ConversationReportPinning:ConversationReport')
                ->assignReportToConversation($targetReportId, $masterConv->conversation_id);
        }
        
        /** @var \XF\Mvc\Entity\ArrayCollection $reports */
        $reports = $this->repository('XF:Report')
            ->findReports()->fetch()->filterViewable();

        $viewParams = [
            'conversation' => $masterConv,
            'reports' => $reports,
    
            'assigned_report' => $masterConv->smcrp_report_id ?: 0
        ];
        
        return $this->view('SModders\ConversationReportPinning:Conversation\AssignReport', 'smcrp_conversation_assign', $viewParams);
    }
    
    /**
     * @param $group
     * @param $id
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertHasPermission($group, $id)
    {
        if (\XF::visitor()->hasPermission($group, $id))
        {
            return;
        }
        
        throw $this->exception($this->noPermission());
    }
}
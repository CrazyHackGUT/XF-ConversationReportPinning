<?php

/**
 * This file is a part of [Report Improvements] Conversation Pinning.
 * All rights reserved.
 *
 * Developed by SourceModders.
 */

namespace SModders\ConversationReportPinning\XF\Pub\Controller;


use XF\Entity\ConversationMaster;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Exception;
use XF\Mvc\Reply\View;

class Conversation extends XFCP_Conversation
{
    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|View
     * @throws Exception
     */
    public function actionView(ParameterBag $params)
    {
        try
        {
            return parent::actionView($params);
        }
        catch (Exception $e)
        {
            $visitor = \XF::visitor();
            if ($visitor->is_moderator)
            {
                $conversation = $this->assertViewableConversation($params->conversation_id);
                if ($this->isPost())
                {
                    /** @var \XF\Repository\Conversation $conversationRepo */
                    $conversationRepo = $this->repository('XF:Conversation');

                    // If user already joined previously, insertRecipients() don't work.
                    // So we handle this case in another way.
                    if (array_key_exists($visitor->user_id, $conversation->recipients))
                    {
                        /** @var \XF\Entity\ConversationRecipient|null $conversationRecipient */
                        $conversationRecipient = $conversationRepo->findRecipientsForList($conversation)
                            ->where('user_id', $visitor->user_id)
                            ->fetchOne();

                        if ($conversationRecipient)
                        {
                            $conversationRecipient->recipient_state = 'active';
                            $conversationRecipient->save();
                        }
    
                        // Rebuild recipient cache.
                        $conversation->rebuildRecipientCache();
                        
                        // ... and if user is really already joined previously, redirect him to conversation.
                        // We're re-added user to conversation, and XF should allow view conversation.
                        if ($conversationRecipient)
                        {
                            return $this->openConversation($conversation);
                        }
    
                        // If code reached to this point, forum administrator tries clean database. So we should
                        // continue by "default way": user neved joined to conversation.
                    }
                    
                    // User never joined to this conversation. Add him by default way.
                    $conversationRepo->insertRecipients($conversation, [$visitor]);
                    return $this->openConversation($conversation);
                }
        
                $viewParams = [
                    'conversation' => $conversation
                ];
        
                return $this->view('SModders\ConversationReportPinning:Conversation\View', 'smcrp_conversation_view', $viewParams);
            }
            
            throw $e;
        }
    }
    
    public function actionAssignReport(ParameterBag $params)
    {
        $this->assertIsModerator();
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
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertIsModerator()
    {
        if (\XF::visitor()->is_moderator)
        {
            return;
        }
        
        throw $this->exception($this->noPermission());
    }
    
    /**
     * @param $conversationId
     * @return \XF\Entity\ConversationMaster
     * @throws Exception
     */
    protected function assertViewableConversation($conversationId)
    {
        $conversation = $this->em()->find('XF:ConversationMaster', $conversationId, ['Report', 'Starter']);
        if (!$conversation || !$conversation->Report || !$conversation->Report->canView())
        {
            throw $this->exception($this->notFound(\XF::phrase('requested_conversation_not_found')));
        }
        
        return $conversation;
    }
    
    protected function openConversation(ConversationMaster $conversation)
    {
        return $this->redirect($this->buildLink('conversations', $conversation));
    }
}
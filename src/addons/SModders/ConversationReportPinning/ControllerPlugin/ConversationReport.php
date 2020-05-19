<?php

/**
 * This file is a part of [Report Improvements] Conversation Pinning.
 * All rights reserved.
 *
 * Developed by SourceModders.
 */

namespace SModders\ConversationReportPinning\ControllerPlugin;


use XF\ControllerPlugin\AbstractPlugin;
use XF\Entity\ConversationMaster;
use XF\Entity\Report;
use XF\Entity\User;
use XF\Phrase;

class ConversationReport extends AbstractPlugin
{
    /**
     * @param User|null $user
     * @return bool
     */
    public function canJoinToConversations(User $user = null)
    {
        $user = $user ?: \XF::visitor();
        return $user->hasPermission('smcrp', 'joinConversations');
    }

    /**
     * @param User|null $user
     * @return bool
     */
    public function canAssignConversations(User $user = null)
    {
        $user = $user ?: \XF::visitor();
        return $user->hasPermission('smcrp', 'assignConversations');
    }

    /**
     * @param User|null $user
     * @throws \XF\Mvc\Reply\Exception
     */
    public function assertCanJoinToConversations(User $user = null)
    {
        $user = $user ?: \XF::visitor();
        if (!$this->canJoinToConversations($user))
        {
            throw $this->exception($this->noPermission());
        }
    }

    /**
     * @param User|null $user
     * @throws \XF\Mvc\Reply\Exception
     */
    public function assertCanAssignConversations(User $user = null)
    {
        $user = $user ?: \XF::visitor();
        if (!$this->canAssignConversations($user))
        {
            throw $this->exception($this->noPermission());
        }
    }

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

            $this->addNewMessage($report, $oldConversation, $conversation);
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

            if ($oldReport)
            {
                $this->addNewMessage($oldReport, $conversation, null);
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
     * @param Report $report
     * @param ConversationMaster|null $oldConversation
     * @param ConversationMaster|null $newConversation
     */
    protected function addNewMessage(Report $report, $oldConversation, $newConversation)
    {
        /** @var \XF\Service\Report\Commenter $commenter */
        $commenter = $this->service('XF:Report\Commenter', $report);
        $commenter->setMessage($this->generateCommentText($oldConversation, $newConversation));

        $commenter->save();
        $commenter->sendNotifications();
        
        $this->session()->reportLastRead = \XF::$time;
    }

    /**
     * @param $oldConversation
     * @param $newConversation
     * @return string
     */
    protected function generateCommentText($oldConversation, $newConversation)
    {
        $template = $this->getTemplateName();
        $params = [
            'old_conversation' => $oldConversation,
            'new_conversation' => $newConversation
        ];
        
        return $this->app->templater()
            ->renderTemplate($template, $params);
    }

    /**
     * @return string
     */
    protected function getTemplateName()
    {
        return 'public:smcrp_report_conversation_message';
    }

    /**
     * @param $conversationId
     * @return \XF\Entity\ConversationMaster
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertConversationExists(&$conversationId)
    {
        return $this->assertEntityExists('XF:ConversationMaster', $conversationId, \XF::phrase('requested_conversation_not_found'));
    }

    /**
     * @param $reportId
     * @return \XF\Entity\Report
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
     * @param \XF\Mvc\Entity\Entity|null $entity
     * @param string|Phrase|null $noPermission
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertEntityViewable($entity, $noPermission = null)
    {
        if ($entity && !$entity->canView())
        {
            throw $this->exception($this->noPermission($noPermission));
        }
    }
}
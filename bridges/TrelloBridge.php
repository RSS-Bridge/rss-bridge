<?php

class TrelloBridge extends BridgeAbstract
{
    const NAME = 'Trello Bridge';
    const URI = 'https://trello.com/';
    const CACHE_TIMEOUT = 300; // 5min
    const DESCRIPTION = 'Returns activity on Trello boards or cards';
    const MAINTAINER = 'Roliga';
    const PARAMETERS = [
        'Board' => [
            'b' => [
                'name' => 'Board ID',
                'required' => true,
                'exampleValue' => 'g9mdhdzg',
                'title' => 'Taken from Trello URL, e.g. trello.com/b/[Board ID]'
            ]
        ],
        'Card' => [
            'c' => [
                'name' => 'Card ID',
                'required' => true,
                'exampleValue' => '8vddc9pE',
                'title' => 'Taken from Trello URL, e.g. trello.com/c/[Card ID]'
            ]
        ]
    ];

    /*
     * This was extracted from webpack on a Trello page, e.g. trello.com/b/g9mdhdzg
     * In the browser's inspector/debugger go to the Debugger (Firefox) or
     * Sources (Chromium) tab, these values can be found at:
     * webpack:///resources/strings/actions/en.json
     */
    const ACTION_TEXTS = [
        'action_accept_enterprise_join_request'
            => '{memberCreator} added team {organization} to the enterprise {enterprise}',
        'action_add_attachment_to_card'
            => '{memberCreator} attached {attachment} to {card} {attachmentPreview}',
        'action_add_attachment_to_card@card'
            => '{memberCreator} attached {attachment} to this card {attachmentPreview}',
        'action_add_checklist_to_card'
            => '{memberCreator} added {checklist} to {card}',
        'action_add_checklist_to_card@card'
            => '{memberCreator} added {checklist} to this card',
        'action_add_label_to_card'
            => '{memberCreator} added the {label} label to {card}',
        'action_add_label_to_card@card'
            => '{memberCreator} added the {label} label to this card',
        'action_add_organization_to_enterprise'
            => '{memberCreator} added team {organization} to the enterprise {enterprise}',
        'action_add_to_organization_board'
            => '{memberCreator} added {board} to {organization}',
        'action_add_to_organization_board@board'
            => '{memberCreator} added this board to {organization}',
        'action_added_a_due_date'
            => '{memberCreator} set {card} to be due {date}',
        'action_added_a_due_date@card'
            => '{memberCreator} set this card to be due {date}',
        'action_added_list_to_board'
            => '{memberCreator} added list {list} to {board}',
        'action_added_list_to_board@board'
            => '{memberCreator} added {list} to this board',
        'action_added_member_to_board'
            => '{memberCreator} added {member} to {board}',
        'action_added_member_to_board@board'
            => '{memberCreator} added {member} to this board',
        'action_added_member_to_board_as_admin'
            => '{memberCreator} added {member} to {board} as an admin',
        'action_added_member_to_board_as_admin@board'
            => '{memberCreator} added {member} to this board as an admin',
        'action_added_member_to_board_as_observer'
            => '{memberCreator} added {member} to {board} as an observer',
        'action_added_member_to_board_as_observer@board'
            => '{memberCreator} added {member} to this board as an observer',
        'action_added_member_to_card'
            => '{memberCreator} added {member} to {card}',
        'action_added_member_to_card@card'
            => '{memberCreator} added {member} to this card',
        'action_added_member_to_organization'
            => '{memberCreator} added {member} to {organization}',
        'action_added_member_to_organization_as_admin'
            => '{memberCreator} added {member} to {organization} as an admin',
        'action_admins_visibility'
            => 'its admins',
        'action_another_board'
            => 'another board',
        'action_archived_card'
            => '{memberCreator} archived {card}',
        'action_archived_card@card'
            => '{memberCreator} archived this card',
        'action_archived_list'
            => '{memberCreator} archived list {list}',
        'action_became_a_normal_user_in_organization'
            => '{memberCreator} became a normal user in {organization}',
        'action_became_a_normal_user_on'
            => '{memberCreator} became a normal user on {board}',
        'action_became_a_normal_user_on@board'
            => '{memberCreator} became a normal user on this board',
        'action_became_an_admin_of_organization'
            => '{memberCreator} became an admin of {organization}',
        'action_board_perm_level'
            => '{memberCreator} made {board} visible to {level}',
        'action_board_perm_level@board'
            => '{memberCreator} made this board visible to {level}',
        'action_calendar'
            => 'calendar',
        'action_cardAging'
            => 'card aging',
        'action_changed_a_due_date'
            => '{memberCreator} changed the due date of {card} to {date}',
        'action_changed_a_due_date@card'
            => '{memberCreator} changed the due date of this card to {date}',
        'action_changed_board_background'
            => '{memberCreator} changed the background of {board}',
        'action_changed_board_background@board'
            => '{memberCreator} changed the background of this board',
        'action_changed_description_of_card'
            => '{memberCreator} changed description of {card}',
        'action_changed_description_of_card@card'
            => '{memberCreator} changed description of this card',
        'action_changed_description_of_organization'
            => '{memberCreator} changed description of {organization}',
        'action_changed_display_name_of_organization'
            => '{memberCreator} changed display name of {organization}',
        'action_changed_name_of_organization'
            => '{memberCreator} changed name of {organization}',
        'action_changed_website_of_organization'
            => '{memberCreator} changed website of {organization}',
        'action_closed_board'
            => '{memberCreator} closed {board}',
        'action_closed_board@board'
            => '{memberCreator} closed this board',
        'action_comment_on_card'
            => '{memberCreator} {contextOn} {card} {comment}',
        'action_comment_on_card@card'
            => '{memberCreator} {comment}',
        'action_completed_checkitem'
            => '{memberCreator} completed {checkitem} on {card}',
        'action_completed_checkitem@card'
            => '{memberCreator} completed {checkitem} on this card',
        'action_convert_to_card_from_checkitem'
            => '{memberCreator} converted {card} from a checklist item on {cardSource}',
        'action_convert_to_card_from_checkitem@card'
            => '{memberCreator} converted this card from a checklist item on {cardSource}',
        'action_convert_to_card_from_checkitem@cardSource'
            => '{memberCreator} converted {card} from a checklist item on this card',
        'action_copy_board'
            => '{memberCreator} copied this board from {board}',
        'action_copy_card'
            => '{memberCreator} copied {card} from {cardSource} in list {list}',
        'action_copy_card@card'
            => '{memberCreator} copied this card from {cardSource} in list {list}',
        'action_copy_comment_from_card'
            => '{memberCreator} copied comment by {member} from card {card} {comment}',
        'action_create_board'
            => '{memberCreator} created {board}',
        'action_create_board@board'
            => '{memberCreator} created this board',
        'action_create_card'
            => '{memberCreator} added {card} to {list}',
        'action_create_card@card'
            => '{memberCreator} added this card to {list}',
        'action_create_custom_field'
            => '{memberCreator} created the {customField} custom field on {board}',
        'action_create_custom_field@board'
            => '{memberCreator} created the {customField} custom field on this board',
        'action_create_enterprise_join_request'
            => '{memberCreator} requested to add team {organization} to the enterprise {enterprise}',
        'action_created_an_invitation_to_board'
            => '{memberCreator} created an invitation to {board}',
        'action_created_an_invitation_to_board@board'
            => '{memberCreator} created an invitation to this board',
        'action_created_an_invitation_to_organization'
            => '{memberCreator} created an invitation to {organization}',
        'action_created_checklist_on_board'
            => '{memberCreator} created {checklist} on {board}',
        'action_created_checklist_on_board@board'
            => '{memberCreator} created {checklist} on this board',
        'action_created_organization'
            => '{memberCreator} created {organization}',
        'action_decline_enterprise_join_request'
            => '{memberCreator} declined the request to add team {organization} to the enterprise {enterprise}',
        'action_delete_attachment_from_card'
            => '{memberCreator} deleted the {attachment} attachment from {card}',
        'action_delete_attachment_from_card@card'
            => '{memberCreator} deleted the {attachment} attachment from this card',
        'action_delete_card'
            => '{memberCreator} deleted card #{idCard} from {list}',
        'action_delete_custom_field'
            => '{memberCreator} deleted the {customField} custom field from {board}',
        'action_delete_custom_field@board'
            => '{memberCreator} deleted the {customField} custom field from this board',
        'action_deleted_account'
            => '[deleted account]',
        'action_deleted_an_invitation_to_board'
            => '{memberCreator} deleted an invitation to {board}',
        'action_deleted_an_invitation_to_board@board'
            => '{memberCreator} deleted an invitation to this board',
        'action_deleted_an_invitation_to_organization'
            => '{memberCreator} deleted an invitation to {organization}',
        'action_deleted_checkitem'
            => '{memberCreator} deleted task {checkitem} on {checklist}',
        'action_disabled_calendar_feed'
            => '{memberCreator} disabled the iCalendar feed on {board}',
        'action_disabled_calendar_feed@board'
            => '{memberCreator} disabled the iCalendar feed on this board',
        'action_disabled_card_covers'
            => '{memberCreator} disabled card cover images on {board}',
        'action_disabled_card_covers@board'
            => '{memberCreator} disabled card cover images on this board',
        'action_disabled_commenting'
            => '{memberCreator} disabled commenting on {board}',
        'action_disabled_commenting@board'
            => '{memberCreator} disabled commenting on this board',
        'action_disabled_inviting'
            => '{memberCreator} disabled inviting on {board}',
        'action_disabled_inviting@board'
            => '{memberCreator} disabled inviting on this board',
        'action_disabled_plugin'
            => '{memberCreator} disabled the {plugin} Power-Up',
        'action_disabled_powerup'
            => '{memberCreator} disabled the {powerup} Power-Up',
        'action_disabled_self_join'
            => '{memberCreator} disabled self join on {board}',
        'action_disabled_self_join@board'
            => '{memberCreator} disabled self join on this board',
        'action_disabled_voting'
            => '{memberCreator} disabled voting on {board}',
        'action_disabled_voting@board'
            => '{memberCreator} disabled voting on this board',
        'action_due_date_change'
            => '{memberCreator}',
        'action_email_card'
            => '{memberCreator} emailed {card} to {list}',
        'action_email_card@card'
            => '{memberCreator} emailed this card to {list}',
        'action_email_card_from'
            => '{memberCreator} emailed {card} to {list} from {from}',
        'action_email_card_from@card'
            => '{memberCreator} emailed this card to {list} from {from}',
        'action_enabled_calendar_feed'
            => '{memberCreator} enabled the iCalendar feed on {board}',
        'action_enabled_calendar_feed@board'
            => '{memberCreator} enabled the iCalendar feed on this board',
        'action_enabled_card_covers'
            => '{memberCreator} enabled card cover images on {board}',
        'action_enabled_card_covers@board'
            => '{memberCreator} enabled card cover images on this board',
        'action_enabled_plugin'
            => '{memberCreator} enabled the {plugin} Power-Up',
        'action_enabled_powerup'
            => '{memberCreator} enabled the {powerup} Power-Up',
        'action_enabled_self_join'
            => '{memberCreator} enabled self join on {board}',
        'action_enabled_self_join@board'
            => '{memberCreator} enabled self join on this board',
        'action_hid_board'
            => '{memberCreator} hid {board}',
        'action_hid_board@board'
            => '{memberCreator} hid this board',
        'action_invited_an_unconfirmed_member_to_board'
            => '{memberCreator} invited an unconfirmed member to {board}',
        'action_invited_an_unconfirmed_member_to_board@board'
            => '{memberCreator} invited an unconfirmed member to this board',
        'action_invited_an_unconfirmed_member_to_organization'
            => '{memberCreator} invited an unconfirmed member to {organization}',
        'action_joined_board'
            => '{memberCreator} joined {board}',
        'action_joined_board@board'
            => '{memberCreator} joined this board',
        'action_joined_board_by_invitation_link'
            => '{memberCreator} joined {board} with an invitation link from {memberInviter}',
        'action_joined_board_by_invitation_link@board'
            => '{memberCreator} joined this board with an invitation link from {memberInviter}',
        'action_joined_organization'
            => '{memberCreator} joined {organization}',
        'action_joined_organization_by_invitation_link'
            => '{memberCreator} joined {organization} with an invitation link from {memberInviter}',
        'action_left_board'
            => '{memberCreator} left {board}',
        'action_left_board@board'
            => '{memberCreator} left this board',
        'action_left_organization'
            => '{memberCreator} left {organization}',
        'action_made_a_normal_user_in_organization'
            => '{memberCreator} made {member} a normal user in {organization}',
        'action_made_a_normal_user_on'
            => '{memberCreator} made {member} a normal user on {board}',
        'action_made_a_normal_user_on@board'
            => '{memberCreator} made {member} a normal user on this board',
        'action_made_admin_of_board'
            => '{memberCreator} made {member} an admin of {board}',
        'action_made_admin_of_board@board'
            => '{memberCreator} made {member} an admin of this board',
        'action_made_an_admin_of_organization'
            => '{memberCreator} made {member} an admin of {organization}',
        'action_made_commenting_on'
            => '{memberCreator} made commenting on {board} available to {level}',
        'action_made_commenting_on@board'
            => '{memberCreator} made commenting on this board available to {level}',
        'action_made_inviting_on'
            => '{memberCreator} made inviting on {board} available to {level}',
        'action_made_inviting_on@board'
            => '{memberCreator} made inviting on this board available to {level}',
        'action_made_observer_of_board'
            => '{memberCreator} made {member} an observer of {board}',
        'action_made_observer_of_board@board'
            => '{memberCreator} made {member} an observer of this board',
        'action_made_self_admin_of_board'
            => '{memberCreator} made themselves an admin of {board}',
        'action_made_self_admin_of_board@board'
            => '{memberCreator} made themselves an admin of this board',
        'action_made_self_observer_of_board'
            => '{memberCreator} became an observer of {board}',
        'action_made_self_observer_of_board@board'
            => '{memberCreator} became an observer of this board',
        'action_made_voting_on'
            => '{memberCreator} made voting on {board} available to {level}',
        'action_made_voting_on@board'
            => '{memberCreator} made voting on this board available to {level}',
        'action_marked_checkitem_incomplete'
            => '{memberCreator} marked {checkitem} incomplete on {card}',
        'action_marked_checkitem_incomplete@card'
            => '{memberCreator} marked {checkitem} incomplete on this card',
        'action_marked_the_due_date_complete'
            => '{memberCreator} marked the due date on {card} complete',
        'action_marked_the_due_date_complete@card'
            => '{memberCreator} marked the due date complete',
        'action_marked_the_due_date_incomplete'
            => '{memberCreator} marked the due date on {card} incomplete',
        'action_marked_the_due_date_incomplete@card'
            => '{memberCreator} marked the due date incomplete',
        'action_member_joined_card'
            => '{memberCreator} joined {card}',
        'action_member_joined_card@card'
            => '{memberCreator} joined this card',
        'action_member_left_card'
            => '{memberCreator} left {card}',
        'action_member_left_card@card'
            => '{memberCreator} left this card',
        'action_members_visibility'
            => 'its members',
        'action_move_card_from_board'
            => '{memberCreator} transferred {card} to {board}',
        'action_move_card_from_board@card'
            => '{memberCreator} transferred this card to {board}',
        'action_move_card_from_list_to_list'
            => '{memberCreator} moved {card} from {listBefore} to {listAfter}',
        'action_move_card_from_list_to_list@card'
            => '{memberCreator} moved this card from {listBefore} to {listAfter}',
        'action_move_card_to_board'
            => '{memberCreator} transferred {card} from {board}',
        'action_move_card_to_board@card'
            => '{memberCreator} transferred this card from {board}',
        'action_move_list_from_board'
            => '{memberCreator} transferred {list} to {board}',
        'action_move_list_to_board'
            => '{memberCreator} transferred {list} from {board}',
        'action_moved_card_higher'
            => '{memberCreator} moved {card} higher',
        'action_moved_card_higher@card'
            => '{memberCreator} moved this card higher',
        'action_moved_card_lower'
            => '{memberCreator} moved {card} lower',
        'action_moved_card_lower@card'
            => '{memberCreator} moved this card lower',
        'action_moved_checkitem_higher'
            => '{memberCreator} moved {checkitem} higher in the checklist {checklist}',
        'action_moved_checkitem_lower'
            => '{memberCreator} moved {checkitem} higher in the checklist {checklist}',
        'action_moved_list_left'
            => '{memberCreator} moved list {list} left on {board}',
        'action_moved_list_left@board'
            => '{memberCreator} moved {list} left on this board',
        'action_moved_list_right'
            => '{memberCreator} moved list {list} right on {board}',
        'action_moved_list_right@board'
            => '{memberCreator} moved {list} right on this board',
        'action_observers_visibility'
            => 'members and observers',
        'action_on'
            => 'on',
        'action_org_visibility'
            => 'members of its team',
        'action_public_visibility'
            => 'the public',
        'action_remove_checklist_from_card'
            => '{memberCreator} removed {checklist} from {card}',
        'action_remove_checklist_from_card@card'
            => '{memberCreator} removed {checklist} from this card',
        'action_remove_from_organization_board'
            => '{memberCreator} removed {board} from {organization}',
        'action_remove_from_organization_board@board'
            => '{memberCreator} removed this board from {organization}',
        'action_remove_label_from_card'
            => '{memberCreator} removed the {label} label from {card}',
        'action_remove_label_from_card@card'
            => '{memberCreator} removed the {label} label from this card',
        'action_remove_organization_from_enterprise'
            => '{memberCreator} removed team {organization} from the enterprise {enterprise}',
        'action_removed_a_due_date'
            => '{memberCreator} removed the due date from {card}',
        'action_removed_a_due_date@card'
            => '{memberCreator} removed the due date from this card',
        'action_removed_from_board'
            => '{memberCreator} removed {member} from {board}',
        'action_removed_from_board@board'
            => '{memberCreator} removed {member} from this board',
        'action_removed_member_from_card'
            => '{memberCreator} removed {member} from {card}',
        'action_removed_member_from_card@card'
            => '{memberCreator} removed {member} from this card',
        'action_removed_member_from_organization'
            => '{memberCreator} removed {member} from {organization}',
        'action_removed_vote_for_card'
            => '{memberCreator} removed vote for {card}',
        'action_removed_vote_for_card@card'
            => '{memberCreator} removed vote for this card',
        'action_rename_custom_field'
            => '{memberCreator} renamed the {customField} custom field on {board} (from {name})',
        'action_rename_custom_field@board'
            => '{memberCreator} renamed the {customField} custom field on this board (from {name})',
        'action_renamed_card'
            => '{memberCreator} renamed {card} (from {name})',
        'action_renamed_card@card'
            => '{memberCreator} renamed this card (from {name})',
        'action_renamed_checkitem'
            => '{memberCreator} renamed {checkitem} (from {name})',
        'action_renamed_checklist'
            => '{memberCreator} renamed {checklist} (from {name})',
        'action_renamed_list'
            => '{memberCreator} renamed list {list} (from {name})',
        'action_reopened_board'
            => '{memberCreator} re-opened {board}',
        'action_reopened_board@board'
            => '{memberCreator} re-opened this board',
        'action_sent_card_to_board'
            => '{memberCreator} sent {card} to the board',
        'action_sent_card_to_board@card'
            => '{memberCreator} sent this card to the board',
        'action_sent_list_to_board'
            => '{memberCreator} sent list {list} to the board',
        'action_set_card_aging_mode_pirate'
            => '{memberCreator} changed card aging to pirate mode',
        'action_set_card_aging_mode_regular'
            => '{memberCreator} changed card aging to regular mode',
        'action_update_board_desc'
            => '{memberCreator} changed description of {board}',
        'action_update_board_desc@board'
            => '{memberCreator} changed description of this board',
        'action_update_board_name'
            => '{memberCreator} renamed {board} (from {name})',
        'action_update_board_name@board'
            => '{memberCreator} renamed this board (from {name})',
        'action_update_custom_field'
            => '{memberCreator} updated the {customField} custom field on {board}',
        'action_update_custom_field@board'
            => '{memberCreator} updated the {customField} custom field on this board',
        'action_update_custom_field_item'
            => '{memberCreator} updated the value for the {customFieldItem} custom field on {card}',
        'action_update_custom_field_item@card'
            => '{memberCreator} updated the value for the {customFieldItem} custom field on this card',
        'action_updated_their_bio'
            => '{memberCreator} updated their bio',
        'action_updated_their_display_name'
            => '{memberCreator} updated their display name',
        'action_updated_their_initials'
            => '{memberCreator} updated their initials',
        'action_updated_their_username'
            => '{memberCreator} updated their username',
        'action_vote_on_card'
            => '{memberCreator} voted for {card}',
        'action_vote_on_card@card'
            => '{memberCreator} voted for this card',
        'action_voting'
            => 'voting',
        'action_withdraw_enterprise_join_request'
            => '{memberCreator} withdrew a request to add team {organization} to the enterprise {enterprise}'
        ];

    const REQUEST_ACTIONS_BOARDS = [
        'addAttachmentToCard',
        'addChecklistToCard',
        'addMemberToCard',
        'commentCard',
        'copyCommentCard',
        'convertToCardFromCheckItem',
        'createCard',
        'copyCard',
        'deleteAttachmentFromCard',
        'emailCard',
        'moveCardFromBoard',
        'moveCardToBoard',
        'removeChecklistFromCard',
        'removeMemberFromCard',
        'updateCard:idList',
        'updateCard:closed',
        'updateCard:due',
        'updateCard:dueComplete',
        'updateCheckItemStateOnCard',
        'updateCustomFieldItem',
        'addMemberToBoard',
        'addToOrganizationBoard',
        'copyBoard',
        'createBoard',
        'createCustomField',
        'createList',
        'deleteCard',
        'deleteCustomField',
        'disablePlugin',
        'disablePowerUp',
        'enablePlugin',
        'enablePowerUp',
        'makeAdminOfBoard',
        'makeNormalMemberOfBoard',
        'makeObserverOfBoard',
        'moveListFromBoard',
        'moveListToBoard',
        'removeFromOrganizationBoard',
        'unconfirmedBoardInvitation',
        'unconfirmedOrganizationInvitation',
        'updateBoard',
        'updateCustomField',
        'updateList:closed'
    ];

    const REQUEST_ACTIONS_CARDS = [
        'addAttachmentToCard',
        'addChecklistToCard',
        'addMemberToCard',
        'commentCard',
        'copyCommentCard',
        'convertToCardFromCheckItem',
        'createCard',
        'copyCard',
        'deleteAttachmentFromCard',
        'emailCard',
        'moveCardFromBoard',
        'moveCardToBoard',
        'removeChecklistFromCard',
        'removeMemberFromCard',
        'updateCard:idList',
        'updateCard:closed',
        'updateCard:due',
        'updateCard:dueComplete',
        'updateCheckItemStateOnCard',
        'updateCustomFieldItem'
    ];

    private $feedName = '';
    private $feedURI = '';

    private function queryAPI($path, $params = [])
    {
        $url = 'https://trello.com/1/' . $path . '?' . http_build_query($params);
        $data = json_decode(getContents($url));
        return $data;
    }

    private function renderAction($action, $textOnly = false)
    {
        if (!array_key_exists($action->display->translationKey, self::ACTION_TEXTS)) {
            return '';
        }

        $strings = [];
        $entities = (array)$action->display->entities;

        foreach ($entities as $entity_name => $entity) {
            $type = $entity->type;
            if (
                $type === 'attachmentPreview'
                && !$textOnly
                && isset($entity->originalUrl)
            ) {
                $string = sprintf(
                    '<p><a href="%s"><img src="%s"></a></p>',
                    $entity->originalUrl,
                    $entity->previewUrl ?? ''
                );
            } elseif ($type === 'card' && !$textOnly) {
                $string = sprintf('<a href="https://trello.com/c/%s">%s</a>', $entity->shortLink, $entity->text);
            } elseif ($type === 'member' && !$textOnly) {
                $string = sprintf('<a href="https://trello.com/%s">%s</a>', $entity->username, $entity->text);
            } elseif ($type === 'date') {
                $string = gmdate('M j, Y \a\t g:i A T', strtotime($entity->date));
            } elseif ($type === 'translatable') {
                $string = self::ACTION_TEXTS[$entity->translationKey];
            } else {
                $string = $entity->text ?? '';
            }
            $strings['{' . $entity_name . '}'] = $string;
        }

        return str_replace(
            array_keys($strings),
            array_values($strings),
            self::ACTION_TEXTS[$action->display->translationKey]
        );
    }

    public function collectData()
    {
        $apiParams = [
            'actions_display' => 'true',
            'fields' => 'name,url'
        ];
        switch ($this->queriedContext) {
            case 'Board':
                $apiParams['actions'] = implode(',', self::REQUEST_ACTIONS_BOARDS);
                $data = $this->queryAPI('boards/' . $this->getInput('b'), $apiParams);
                break;
            case 'Card':
                $apiParams['actions'] = implode(',', self::REQUEST_ACTIONS_CARDS);
                $data = $this->queryAPI('cards/' . $this->getInput('c'), $apiParams);
                break;
            default:
                returnClientError('Invalid context');
        }

        $this->feedName = $data->name;
        $this->feedURI = $data->url;

        foreach ($data->actions as $action) {
            $item = [];

            $item['title'] = $this->renderAction($action, true);
            $item['timestamp'] = strtotime($action->date);
            $item['author'] = $action->memberCreator->fullName;
            $item['categories'] = [
                'trello',
                $action->data->board->name,
                $action->type
            ];
            if (isset($action->data->card)) {
                $item['categories'][] = $action->data->card->name ?? $action->data->card->id;
                $item['uri'] = 'https://trello.com/c/'
                    . $action->data->card->shortLink
                    . '#action-'
                    . $action->id;
            } else {
                $item['uri'] = 'https://trello.com/b/'
                    . $action->data->board->shortLink;
            }
            $item['content'] = $this->renderAction($action, false);
            if (isset($action->data->attachment->url)) {
                $item['enclosures'] = [$action->data->attachment->url];
            }

            $this->items[] = $item;
        }
    }

    public function detectParameters($url)
    {
        $regex = '/^(https?:\/\/)?trello\.com\/([bc])\/([^\/?\n]+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            if ($matches[2] == 'b') {
                $context = 'Board';
            } else if ($matches[2] == 'c') {
                $context = 'Card';
            }
            return [
                'context' => $context,
                $matches[2] => $matches[3]
            ];
        } else {
            return null;
        }
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'Board':
            case 'Card':
                return $this->feedURI;
            default:
                return parent::getURI();
        }
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Board':
            case 'Card':
                return $this->feedName;
            default:
                return parent::getName();
        }
    }
}

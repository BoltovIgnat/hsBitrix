<?php
namespace Dbbo\Jivo\Api;

use Dbbo\Jivo\Api\Lead,
    Dbbo\Jivo\Api\Contact,
    Dbbo\Jivo\Api\Deal,
    Dbbo\Jivo\Api\User;

class Main {
    var $data = [];
    var $params = [];
    var $action = '';
    var $contactId = '';
    var $assignedBy = '';

    function __construct($data) {
		\Bitrix\Main\Diag\Debug::dumpToFile($data, '$data', '/local/logs/jivo_'.date('d_m_Y').'.log');
		\Bitrix\Main\Loader::IncludeModule("crm");
		\Bitrix\Main\Loader::IncludeModule("bizproc");
        $this->data = $data;
        $this->GetAssignedBy();
    }

    public function Action($action) {
        switch ($action) {
            case 'chat_accepted':
                $this->ChatAccepted();
                break;
            case 'chat_updated':
                $this->ChatUpdated();
                break;
            case 'chat_finished':
                $this->ChatFinished();
                break;
            default:
                break;
        }
    }

    public function SetDataField($key, $value) {
        $this->data[$key] = $value;
    }

    private function GetParams() {
        
        return [
            'name' => $this->data['visitor']['name'],
            'email' => $this->data['visitor']['email'],
            'phone' => $this->data['visitor']['phone'],
            'chatId' => $this->data['chat_id'],
            'assignedEmail' => $this->data['agent']['email'],
            'leadFieldChatId' => $this->data['leadFieldChatId'],
            'leadFieldYmId' => $this->data['leadFieldYmId'],
            'leadFieldGaId' => $this->data['leadFieldGaId'],
            'leadFieldUrl' => $this->data['leadFieldUrl'],
            'dealFieldChatId' => $this->data['dealFieldChatId'],
            'assignedDefault' => $this->data['assignedDefault'],
            'chat' => $this->data['chat'],
            'sourceId' => $this->data['sourceId'],
            'session' => $this->data['session'],
            'analytics' => $this->data['analytics'],
            'page' => $this->data['page'],
            'nameTitle' => ($this->data['visitor']['name']) ?: 'Посетитель',
            'operator' => ($this->data['agents']) ?: $this->data['agent']
        ];
    }

    private function ChatAccepted() {
        $this->params = $this->GetParams();
        $this->ProccessLead();
    }

    private function ChatUpdated() {
        $this->params = $this->GetParams();
        $this->ProccessLeadUpdate();
    }

    private function ChatFinished() {
        $this->params = $this->GetParams();
        $this->ProccessLeadUpdate();
        $this->ProcessAddMessages();
    }

    private function ProccessLead() {        
        if($this->params['email'] && !$this->params['phone']) {

            $this->ProccessContactEmail($this->params['email']);

        } elseif(!$this->params['email'] && $this->params['phone']) {

            $this->ProccessContactPhone($this->params['phone']);

        } elseif(!$this->params['email'] && !$this->params['phone']) {

            $this->ProccessLeadJivoId();

        } elseif($this->params['email'] && $this->params['phone']) {

            $this->ProccessContactFull($this->params['email'], $this->params['phone']);

        }
    }

    private function ProccessContactEmail($email) {
        $search = $this->SearchContactEmail($email);

        if($search) {
            $contactId = $search[0]['ELEMENT_ID'];
            $contact = Contact::getList([
                'ID' => 'DESC'
            ], [
                'ID' => $contactId,
                'CHECK_PERMISSIONS' => 'N'
            ]);

            $phone = $this->SearchPhoneContact($contactId);
            $name = ($contact[0]['FULL_NAME']) ?: $this->params['name'];
            
            if(is_array($phone)) {
                $phone = implode(', ', $phone);
            }

            $text = 'Менеджер начал диалог с клиентом по чату ' . $this->params['chatId'] . "\r\n";
            $text .= ($name) ? 'Посетитель - ' . $name . "\r\n" : '';
            $text .= ($phone) ? 'Телефон - '. $phone . "\r\n" : '';
            $text .= 'Email - ' . $email;
            
            $this->ProcessAddMessage($contactId, 'contact', $text);

            $deals = $this->SearchDealContact($contactId);

            if($deals) {
                $dealId = $deals[0]['ID'];
                $this->ProcessUpdateDeal($dealId, [
                    $this->params['dealFieldChatId'] => $this->params['chatId']
                ]);

                $this->ProcessAddMessage($dealId, 'deal', $text);
            } else {
                $leads = $this->SearchLeadContact($contactId);

                if($leads) {
                    $leadId = $leads[0]['ID'];
                    $this->ProcessUpdateLead($leadId, [
                        $this->params['leadFieldChatId'] => $this->params['chatId']
                    ]);
                    $this->ProcessAddMessage($leadId, 'lead', $text);
                } else {
                    $this->ProcessLeadAdd($contactId);
                }
            }
        } else {
            $leadIds = $this->SearchLeadByEmail($email);

            if($leadIds) {
                $leads = Lead::GetList([
                    'ID' => 'DESC'
                ], [
                    'ID' => $leadIds,
                    'STATUS_SEMANTIC_ID' => 'P',
                    'CHECK_PERMISSIONS' => 'N'
                ]);

                if($leads) {
                    $leadId = $leads[0]['ID'];
                    $this->ProcessUpdateLead($leadId, [
                        $this->params['leadFieldChatId'] => $this->params['chatId']
                    ]);

                    $text = 'Менеджер начал диалог с клиентом по чату ' . $this->params['chatId'] . "\r\n";
                    $text .= ($this->params['name']) ? 'Посетитель - ' . $this->params['name'] . "\r\n" : '';
                    $text .= ($this->params['phone']) ? 'Телефон - '. $this->params['phone'] . "\r\n" : '';
                    $text .= 'Email - ' . $email;

                    $this->ProcessAddMessage($leadId, 'lead', $text);
                }
            } else {
                $this->ProcessLeadAdd();
            }
        }
    }

    private function ProccessContactPhone($phone) {
        $search = $this->SearchContactPhone($phone);

        if($search) {
            $contactId = $search[0]['ELEMENT_ID'];
            $contact = Contact::getList([
                'ID' => 'DESC'
            ], [
                'ID' => $contactId,
                'CHECK_PERMISSIONS' => 'N'
            ]);

            $email = $this->SearchPhoneEmail($contactId);
            $name = ($contact[0]['FULL_NAME']) ?: $this->params['name'];
            
            if(is_array($email)) {
                $email = implode(', ', $email);
            }

            $text = 'Менеджер начал диалог с клиентом по чату ' . $this->params['chatId'] . "\r\n";
            $text .= ($name) ? 'Посетитель - ' . $name . "\r\n" : '';
            $text .= 'Телефон - '. $phone . "\r\n";
            $text .= ($email) ? 'Email - ' . $email . "\r\n" : '';
            
            $this->ProcessAddMessage($contactId, 'contact', $text);

            $deals = $this->SearchDealContact($contactId);

            if($deals) {
                $dealId = $deals[0]['ID'];
                $this->ProcessUpdateDeal($dealId, [
                    $this->params['dealFieldChatId'] => $this->params['chatId']
                ]);

                $this->ProcessAddMessage($dealId, 'deal', $text);
            } else {
                $leads = $this->SearchLeadContact($contactId);

                if($leads) {
                    $leadId = $leads[0]['ID'];
                    $this->ProcessUpdateLead($leadId, [
                        $this->params['leadFieldChatId'] => $this->params['chatId']
                    ]);
                    $this->ProcessAddMessage($leadId, 'lead', $text);
                } else {
                    $this->ProcessLeadAdd($contactId);
                }
            }
        } else {
            $leadIds = $this->SearchLeadByPhone($phone);

            if($leadIds) {
                $leads = Lead::GetList([], [
                    'ID' => $leadIds,
                    'STATUS_SEMANTIC_ID' => 'P',
                    'CHECK_PERMISSIONS' => 'N'
                ]);

                if($leads) {
                    $leadId = $leads[0]['ID'];
                    $this->ProcessUpdateLead($leadId, [
                        $this->params['leadFieldChatId'] => $this->params['chatId']
                    ]);

                    $text = 'Менеджер начал диалог с клиентом по чату ' . $this->params['chatId'] . "\r\n";
                    $text .= ($this->params['name']) ? 'Посетитель - ' . $this->params['name'] . "\r\n" : '';
                    $text .= ($this->params['phone']) ? 'Телефон - '. $this->params['phone'] : '' . "\r\n";
                    $text .= ($this->params['email']) ? 'Email - '. $this->params['email'] : '' . "\r\n";

                    $this->ProcessAddMessage($leadId, 'lead', $text);
                }
            } else {
                $this->ProcessLeadAdd();
            }
        }
    }

    private function ProccessContactFull($email, $phone) {
        $search = $this->SearchContactPhone($phone);

        if(!$search) {
            $search = $this->SearchContactEmail($email);
        }
        
        if($search) {
            $contactId = $search[0]['ELEMENT_ID'];
            
            $contact = Contact::getList([
                'ID' => 'DESC'
            ], [
                'ID' => $contactId,
                'CHECK_PERMISSIONS' => 'N'
            ]);
            
            $name = ($contact[0]['FULL_NAME']) ?: $this->params['name'];

            $text = 'Менеджер начал диалог с клиентом по чату ' . $this->params['chatId'] . "\r\n";
            $text .= ($name) ? 'Посетитель - ' . $name . "\r\n" : '';
            $text .= 'Телефон - '. $phone . "\r\n";
            $text .= ($email) ? 'Email - ' . $email : '';

            $this->ProcessAddMessage($contactId, 'contact', $text);
            
            $deals = $this->SearchDealContact($contactId);

            if($deals) {
                $dealId = $deals[0]['ID'];

                $this->ProcessUpdateDeal($dealId, [
                    $this->params['dealFieldChatId'] => $this->params['chatId']
                ]);

                $this->ProcessAddMessage($dealId, 'deal', $text);
            } else {
                $leads = $this->SearchLeadContact($contactId);

                if($leads) {
                    $leadId = $leads[0]['ID'];
                    
                    $this->ProcessUpdateLead($leadId, [
                        $this->params['leadFieldChatId'] => $this->params['chatId']
                    ]);
                    $this->ProcessAddMessage($leadId, 'lead', $text);
                } else {
                    $this->ProcessLeadAdd($contactId);
                }
            }
        } else {
            $leadIds = $this->SearchLeadByPhone($phone);

            if(!$leadIds) {
                $this->SearchLeadByEmail($email);
            }

            if($leadIds) {
                $leads = Lead::GetList([], [
                    'ID' => $leadIds,
                    'STATUS_SEMANTIC_ID' => 'P',
                    'CHECK_PERMISSIONS' => 'N'
                ]);

                if($leads) {
                    $leadId = $leads[0]['ID'];
                    $this->ProcessUpdateLead($leadId, [
                        $this->params['leadFieldChatId'] => $this->params['chatId']
                    ]);

                    $text = 'Менеджер начал диалог с клиентом по чату ' . $this->params['chatId'] . "\r\n";
                    $text .= ($this->params['name']) ? 'Посетитель - ' .  $this->params['name'] . "\r\n" : '';
                    $text .= ($this->params['phone']) ? 'Телефон - '. $this->params['phone'] . "\r\n" : '';
                    $text .= ($this->params['email']) ? 'Email - '. $this->params['email'] . "\r\n" : '';

                    $this->ProcessAddMessage($leadId, 'lead', $text);
                }
            } else {
                $this->ProcessLeadAdd();
            }
        }
    }

    private function ProcessAddMessage($entityId, $entityType, $text) {
        $type = '';

        switch ($entityType) {
            case 'deal':
                $type = \CCrmOwnerType::Deal;
                break;
            case 'lead':
                $type = \CCrmOwnerType::Lead;
                break;
            case 'contact':
                $type = \CCrmOwnerType::Contact;
                break;
            default:
                break;
        }

        if($type) {
            \Bitrix\Crm\Timeline\CommentEntry::create(
                array(
                'TEXT' => $text,
                'SETTINGS' => array(),
                'AUTHOR_ID' => ($this->assignedBy) ?: $params['assignedDefault'],
                'BINDINGS' => array(array('ENTITY_TYPE_ID' => $type, 'ENTITY_ID' => $entityId))
            ));
        }
    }

    private function ProcessUpdateDeal($dealId, $fields) {
        return Deal::Update($dealId, $fields);
    }

    private function ProcessUpdateLead($leadId, $fields) {
        return Lead::Update($leadId, $fields);
    }

    private function GetAssignedBy() {
        if($this->data['agent']['email']) {
            $user = User::GetList(
                [
                    'EMAIL' => $this->data['agent']['email']
                ],
                [
                    'ID'
                ]
            );

            if($user) {
                $this->assignedBy = $user['ID'];
            }
        }
    }

    private function SearchLeadByEmail($email) {
        return Lead::SearchLeadByEmail($email);
    }

    private function SearchLeadByPhone($phone) {
        return Lead::SearchLeadByPhone($phone);
    }

    private function ProcessLeadAdd($contactId = '') {
		$add_title = $params['phone'] ? ' - '. $params['phone'] : '';
		$add_title .= ' - ' . $params['chatId']; 
        $fields = [
			'TITLE' => ($this->params['name']) ?: 'Запрос Jivo' . $add_title,
            'NAME' => ($this->params['name']) ?: '',
            'OPENED' => 'Y',
            $this->params['leadFieldChatId'] => $this->params['chatId'],
            'ASSIGNED_BY_ID' => ($this->assignedBy) ? $this->assignedBy : $this->params['assignedDefault'],
            'SOURCE_ID' => $this->params['sourceId']
        ];
        
        if($contactId) {
            $fields['CONTACT_ID'] = $contactId;
        } else {
            if($this->params['email']) {
                $fields['FM']['EMAIL'] = [
                    'n0' => [
                        'VALUE_TYPE' => 'WORK',
                        'VALUE' => $this->params['email']
                    ]
                ];
            }

            if($this->params['phone']) {
                $fields['FM']['PHONE'] = [
                    'n0' => [
                        'VALUE_TYPE' => 'WORK',
                        'VALUE' => $this->params['phone']
                    ]
                ];
            }
        }

        if($this->params['session']['utm_json']) {
            $fields['UTM_CAMPAIGN'] = $this->params['session']['utm_json']['campaign'];
            $fields['UTM_CONTENT'] = $this->params['session']['utm_json']['content'];
            $fields['UTM_MEDIUM'] = $this->params['session']['utm_json']['medium'];
            $fields['UTM_SOURCE'] = $this->params['session']['utm_json']['source'];
            $fields['UTM_TERM'] = $this->params['session']['utm_json']['term'];
        }

        if($this->params['analytics']['ym'] && $this->params['leadFieldYmId']) {
            $fields[$this->params['leadFieldYmId']] = $this->params['analytics']['ym'];
        }

        if($this->params['analytics']['ga'] && $this->params['leadFieldGaId']) {
            $fields[$this->params['leadFieldGaId']] = $this->params['analytics']['ga'];
        }

        if($this->params['page']['url'] && $this->params['leadFieldUrl']) {
            $fields[$this->params['leadFieldUrl']] = $this->params['page']['url'];
        }

        $leadId = Lead::Add($fields);

        if($leadId) {
            $text = '';
            
            $text = 'Менеджер начал диалог с клиентом по чату ' . $this->params['chatId'] . '.' . "\r\n";
            $text .= ($this->params['name']) ? 'Посетитель - ' .  $this->params['name'] . "\r\n" : '';
            $text .= ($this->params['phone']) ? 'Телефон - '. $this->params['phone'] . "\r\n" : '';
            $text .= ($this->params['email']) ? 'Email - '. $this->params['email'] . "\r\n" : '';
            
            if($this->params['session']['geoip']) {
                $text .= ' Страна - ' . $this->params['session']['geoip']['country'] . "\r\n";
                $text .= 'Регион - ' . $this->params['session']['geoip']['region'] . "\r\n";
                $text .= 'Город - ' . $this->params['session']['geoip']['city'] . "\r\n";
                $this->ProcessAddMessage($leadId, 'lead', $text);
            }

			\CCrmBizProcHelper::AutoStartWorkflows(
				\CCrmOwnerType::Lead,
				$leadId,
				\CCrmBizProcEventType::Create,
				$errors
			);

			\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Lead, $leadId);
        }
    }

    private function ProcessAddMessages() {
        if(!$this->params['chat']['messages']) {
            return true;
        }

        $chat = $this->params['chat']['messages'];
        $addText = 'Закончен диалог: ' . "\r\n";
        $contactId = 0;
        $dataOperator = [];
        
        foreach($this->params['operator'] as $itemOperator) {
            $dataOperator[$itemOperator['id']] = $itemOperator['name'];
        }

        foreach($chat as $item) {
            $addText .= ($item['type'] == 'visitor' ? $this->params['nameTitle'] .': ' : $dataOperator[$item['agent_id']] .': ') . $item['message'] . "\r\n";
        }
        
        $deals = Deal::getList([], [
            $this->params['dealFieldChatId'] => $this->params['chatId'],
            'CHECK_PERMISSIONS' => 'N',
            'STAGE_SEMANTIC_ID' => 'P'
        ]);
        
        if($deals) {
            $deal = $deals[0];
            
            \Bitrix\Crm\Timeline\CommentEntry::create(
                array(
                'TEXT' => $addText,
                'SETTINGS' => array(),
                'AUTHOR_ID' => ($this->assignedBy) ?: $this->params['assignedDefault'],
                'BINDINGS' => array(array('ENTITY_TYPE_ID' => \CCrmOwnerType::Deal, 'ENTITY_ID' => $deal['ID']))
            ));
            
            if($deal['LEAD_ID']) {
                \Bitrix\Crm\Timeline\CommentEntry::create(
                    array(
                    'TEXT' => $addText,
                    'SETTINGS' => array(),
                    'AUTHOR_ID' => ($this->assignedBy) ?: $this->params['assignedDefault'],
                    'BINDINGS' => array(array('ENTITY_TYPE_ID' => \CCrmOwnerType::Lead, 'ENTITY_ID' => $deal['LEAD_ID']))
                ));
            }
            
            $contactId = $deal['CONTACT_ID'];
            
        } else {
            $leads = Lead::GetList([], [
                $this->params['leadFieldChatId'] => $this->params['chatId'],
                'CHECK_PERMISSIONS' => 'N'
            ]);

            if($leads) {
                $lead = $leads[0];

                if($lead['STATUS_SEMANTIC_ID'] == 'P') {
                    \Bitrix\Crm\Timeline\CommentEntry::create(
                        array(
                        'TEXT' => $addText,
                        'SETTINGS' => array(),
                        'AUTHOR_ID' => ($this->assignedBy) ?: $this->params['assignedDefault'],
                        'BINDINGS' => array(array('ENTITY_TYPE_ID' => \CCrmOwnerType::Lead, 'ENTITY_ID' => $lead['ID']))
                    ));
                    
                    $contactId = $lead['CONTACT_ID'];
                } else {
                    $deals = Deal::getList([], [
                        'LEAD_ID' => $lead['ID'],
                        'CHECK_PERMISSIONS' => 'N'
                    ]);

                    if($deals) {
                        $deal = $deals[0];

                        \Bitrix\Crm\Timeline\CommentEntry::create(
                            array(
                            'TEXT' => $addText,
                            'SETTINGS' => array(),
                            'AUTHOR_ID' => ($this->assignedBy) ?: $this->params['assignedDefault'],
                            'BINDINGS' => array(array('ENTITY_TYPE_ID' => \CCrmOwnerType::Deal, 'ENTITY_ID' => $deal['ID']))
                        ));
                        
                        $contactId = $deal['CONTACT_ID'];
                    }
                }
            }
        }
        
        if($contactId) {
            \Bitrix\Crm\Timeline\CommentEntry::create(
                array(
                'TEXT' => $addText,
                'SETTINGS' => array(),
                'AUTHOR_ID' => ($this->assignedBy) ?: $this->params['assignedDefault'],
                'BINDINGS' => array(array('ENTITY_TYPE_ID' => \CCrmOwnerType::Contact, 'ENTITY_ID' => $contactId))
            ));
        }
    }

    private function SearchContactEmail($email) {
        if(!$email) {
            return false;
        }

        return Contact::Search([], [
            'VALUE' => $email,
            'ENTITY_ID' => 'CONTACT'
        ]);
    }

    private function SearchContactPhone($phone) {
        if(!$phone) {
            return false;
        }

        return Contact::Search([], [
            '%VALUE' => \NormalizePhone($phone),
            'ENTITY_ID' => 'CONTACT'
        ]);
    }

    private function SearchDealContact($contactId) {
        return Deal::getList([
            "ID" => "DESC"
        ], [
            'CONTACT_ID' => $contactId,
            'STAGE_SEMANTIC_ID' => 'P',
            'CHECK_PERMISSIONS' => 'N'
        ]);
    }

    private function SearchPhoneContact($contactId) {
        $result = [];

        $search = Contact::Search([], [
            'ENTITY_ID' => 'CONTACT',
            'TYPE_ID' => 'PHONE',
            'ELEMENT_ID' => $contactId
        ]);

        if($search) {
            foreach($search as $item) {
                $result[] = $item['VALUE'];
            }
        }

        return $result;
    }

    private function SearchPhoneEmail($contactId) {
        $result = [];

        $search = Contact::Search([], [
            'ENTITY_ID' => 'CONTACT',
            'TYPE_ID' => 'EMAIL',
            'ELEMENT_ID' => $contactId
        ]);

        if($search) {
            foreach($search as $item) {
                $result[] = $item['VALUE'];
            }
        }

        return $result;
    }

    private function SearchLeadContact($contactId) {
        return Lead::getList(
            [
                'ID' => 'DESC'
            ],
            [
            'CONTACT_ID' => $contactId,
            'STATUS_SEMANTIC_ID' => 'P',
            'CHECK_PERMISSIONS' => 'N'
        ]);
    }

    private function ProcessContactAdd($params) {
        $contactId = false;

        $filter = [
            'ENTITY_ID'  => 'CONTACT',
            'TYPE_ID'    => 'EMAIL',
            'VALUE_TYPE' => 'WORK',
            'VALUE' => $params['email']
        ];

        $search = Contact::Search([], $filter);

        if(!$search) {
            if($phone) {
                $filter = [
                    'ENTITY_ID'  => 'CONTACT',
                    'TYPE_ID'    => 'PHONE',
                    'VALUE_TYPE' => 'WORK',
                    '%VALUE' => \NormalizePhone($params['phone'])
                ];
                $search = Contact::Search([], $filter);
            }
        }

        if($search) {
            $contactId = $search[0]['ELEMENT_ID'];
        } else {
            if($params['phone']) {
                $addPhone = [
                    'PHONE' => [
                        'n0' => [
                            'VALUE_TYPE' => 'WORK',
                            'VALUE' => $params['phone']
                        ]
                    ]
                ];
            }

            $add = [
                'HAS_PHONE' => ($params['phone']) ? 'Y' : 'N',
                'FULL_NAME' => '',
                'LAST_NAME' => $params['name'],
                'HAS_EMAIL' => 'Y',
                'TYPE_ID' => 'CLIENT',
                'SOURCE_ID' => 'WEB',
                'FM' => [
                    $addPhone,
                    'EMAIL' => [
                        'n0' => [
                            'VALUE_TYPE' => 'WORK',
                            'VALUE' => $params['email']
                        ]
                    ]
                ]
            ];

            $contactId = Contact::Add($add);
        }

        return $contactId;
    }

    private function ProcessContactUpdate($contactId, $params, $entityType, $entityId) {
        $contacts = Contact::getList([
            'ID' => 'DESC'
        ], [
            'ID' => $contactId,
            'CHECK_PERMISSIONS' => 'N'
        ]);

        if($contacts) {
            $contact = $contacts[0]['ID'];

            $search = Contact::Search([], [
                'ENTITY_ID' => 'CONTACT',
                'ELEMENT_ID' => $contact
            ]);

            if($search) {
                $update = [];

                $findEmail = false;
                $findPhone = false;

                foreach($search as $item) {
                    if($item['TYPE_ID'] == 'EMAIL' && $item['VALUE'] == $params['email']) {
                        $findEmail = true;
                    }

                    if($item['TYPE_ID'] == 'PHONE' && \NormalizePhone($item['VALUE']) == \NormalizePhone($params['phone'])) {
                        $findPhone = true;
                    }
                }

                if(!$findEmail) {
                    $update['EMAIL'][] = $params['email'];
                }
                if(!$findPhone) {
                    $update['PHONE'][] = \NormalizePhone($params['phone']);
                }

                $fields = [];

                if($update) {
                    if($update['PHONE']) {
                        foreach($update['PHONE'] as $key => $value) {
                            $fields['FM']['PHONE']['n'.$key] = [
                                'VALUE_TYPE' => 'WORK',
                                'VALUE' => $value
                            ];
                        }
                    }

                    if($update['EMAIL']) {
                        foreach($update['EMAIL'] as $key => $value) {
                            $fields['FM']['EMAIL']['n'.$key] = [
                                'VALUE_TYPE' => 'WORK',
                                'VALUE' => $value
                            ];
                        }
                    }
                }

                if(!$contacts[0]['NAME'] && $params['name']) {
                    $fields['NAME'] = $params['name'];
				} else {
					$fields['NAME'] = $contacts[0]['NAME'];
				}

                if($fields) {
                    $text = 'Обновлены контактные данные ' . "\r\n";
                    $text .= ($update['PHONE']) ? 'номер телефона - '. $this->params['phone'] . "\r\n" : '';
                    $text .= ($update['EMAIL']) ? 'почта - '. $this->params['email'] . "\r\n" : '';
                    $text .= ($fields['NAME']) ? 'название - '. $fields['NAME'] : '';

                    $this->ProcessAddMessage($contactId, 'contact', $text);
                    $this->ProcessAddMessage($entityId, $entityType, $text);
                    
                    Contact::Update($contactId, $fields);
                }
            }
        }
    }

    private function ProccessLeadJivoId() {
        $leads = Lead::GetList([], [
            $this->params['leadFieldChatId'] => $this->params['chatId'],
            'STATUS_SEMANTIC_ID' => 'P',
            'CHECK_PERMISSIONS' => 'N'
        ]);
        
        $text = 'Менеджер начал диалог с клиентом по чату ' . $this->params['chatId'] . "\r\n";
        $text .= ($this->params['name']) ? 'Посетитель - ' . $this->params['name'] . "\r\n" : '';
        $text .= ($this->params['phone']) ? 'Телефон - '. $this->params['phone'] . "\r\n" : '';
        $text .= ($this->params['email']) ? 'Email - ' . $this->params['email'] . "\r\n" : '';

        if($leads) {
            $leadId = $leads[0]['ID'];
            $this->ProcessAddMessage($leadId, 'lead', $text);
        } else {
            $this->ProcessLeadAdd();
        }
    }

    private function ProccessLeadUpdate() {
        $deals = Deal::getList([
            'ID' => 'DESC'
        ], [
            $this->params['dealFieldChatId'] => $this->params['chatId'],
            'CHECK_PERMISSIONS' => 'N',
            'STAGE_SEMANTIC_ID' => 'P'
        ]);
        
        if($deals) {
            $deal = $deals[0];
            
            if($deal['CONTACT_ID']) {
                $this->ProcessContactUpdate($deal['CONTACT_ID'], $this->params, 'deal', $deal['ID']);
            }
        } else {
            $leads = Lead::GetList([], [
                $this->params['leadFieldChatId'] => $this->params['chatId'],
                'STATUS_SEMANTIC_ID' => 'P',
                'CHECK_PERMISSIONS' => 'N'
            ]);

            if($leads) {
                $lead = $leads[0];

                if($lead['CONTACT_ID']) {
                    $this->ProcessContactUpdate($lead['CONTACT_ID'], $this->params, 'lead', $lead['ID']);
                } else {
                    if($this->params['phone']) {
                        $search = $this->SearchContactPhone($this->params['phone']);
                    }

                    if(!$search && $this->params['email']) {
                        $search = $this->SearchContactEmail($this->params['email']);
                    }
                    
                    if($search) {
                        $contactId = $search[0]['ELEMENT_ID'];
                        $fields = [
                            'CONTACT_ID' => $contactId
                        ];
                        Lead::Update($lead['ID'], $fields);
                    } else {
                        $data = Contact::Search([], [
                            'ENTITY_ID'  => 'LEAD',
                            'ELEMENT_ID' => $lead['ID']
                        ]);

                        if($data) {
                            $update = [];
                            $add = [];
                            $existsPhone = false;
                            $existsEmail = false;

                            foreach($data as $item) {
                                if($item['TYPE_ID'] == 'EMAIL' && $this->params['email']) {
                                    if($item['VALUE'] != $this->params['email']) {
                                        $update['EMAIL'][$item['ID']] = $item['VALUE'];
                                    }
                                }

                                if($item['TYPE_ID'] == 'PHONE' && $this->params['phone']) {
                                    if($item['VALUE'] != $this->params['phone']) {
                                        $update['PHONE'][$item['ID']] = $item['VALUE'];
                                    }
                                }

                                if($item['TYPE_ID'] == 'PHONE') {
                                    $existsPhone = true;
                                }

                                if($item['TYPE_ID'] == 'EMAIL') {
                                    $existsEmail = true;
                                }
                            }

                            if(!$existsPhone && $this->params['phone']) {
                                $add['PHONE'] = $this->params['phone'];
                            }

                            if(!$existsEmail && $this->params['email']) {
                                $add['EMAIL'] = $this->params['email'];
                            }

                            if($update) {
                                $multi = new \CCrmFieldMulti();
                                $multi->SetFields('LEAD', $lead['ID'], $update);

                                $text = 'Клиент предоставил';
                                $text .= ($update['PHONE']) ? ' номер телефона - '. $this->params['phone'] : '';
                                $text .= ($update['EMAIL']) ? ', почту - '. $this->params['email'] : '';

                                $this->ProcessAddMessage($lead['ID'], 'lead', $text);
                            }

                            if($add) {
                                $fields = [];

                                foreach($add as $key => $item) {
                                    $fields['FM'][$key]['n0'] = [
                                        'VALUE_TYPE' => 'WORK',
                                        'VALUE' => $item
                                    ];
                                }

                                $text = 'Клиент предоставил';
                                $text .= ($add['PHONE']) ? ' номер телефона - '. $this->params['phone'] : '';
                                $text .= ($add['EMAIL']) ? ', почту - '. $this->params['email'] : '';

                                $this->ProcessAddMessage($lead['ID'], 'lead', $text);

                                Lead::Update($lead['ID'], $fields);
                            }
                        } else {
                            $fields = [
                                'FM' => [
                                    'PHONE' => [
                                        'n0' => [
                                            'VALUE_TYPE' => 'WORK',
                                            'VALUE' => $this->params['phone']
                                        ]
                                    ],
                                    'EMAIL' => [
                                        'n0' => [
                                            'VALUE_TYPE' => 'WORK',
                                            'VALUE' => $this->params['email']
                                        ]
                                    ]
                                ]
                            ];

                            $this->ProcessUpdateLead($lead['ID'], $fields);

                            $text = 'Клиент предоставил ' . "\r\n";
                            $text .= ($this->params['email']) ? ' почту - '. $this->params['email'] . "\r\n" : '';
                            $text .= ($this->params['phone']) ? ' номер телефона - '. $this->params['phone'] . "\r\n" : '';

                            $this->ProcessAddMessage($lead['ID'], 'lead', $text);
                        }
                    }
                }
            }
        }
    }
}
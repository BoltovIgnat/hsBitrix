<?php

defined('B_PROLOG_INCLUDED') || die;

use Dbbo\Crm\Smart;
use Dbbo\Debug\Dump;

class ClientOrdersComponent extends CBitrixComponent
{
    /**
     * @return void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function executeComponent(): void
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        $entity = new Smart();

        $entity->SetEntityId(178);
        $data = [];

        $grid_options = new Bitrix\Main\Grid\Options('report_list');
        $sort = $grid_options->GetSorting(['sort' => ['CREATED_TIME' => 'ASC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
        $orderBy = $request->get('by') ?? 'CREATED_TIME';
        $orderOrder = $request->get('order') ?? 'DESC';
        $nav_params = $grid_options->GetNavParams();
        $showAll = !$request->get('report_list') || $request->get('report_list') && $request->get('report_list') == 'page-all' ? true : false;

        $nav = new Bitrix\Main\UI\PageNavigation('report_list');
        $nav->allowAllRecords(true)
            ->setPageSize($nav_params['nPageSize'])
            ->initFromUri();

        $date_to = new \Bitrix\Main\Type\DateTime();
        $date_from = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime("-30 day"));      

        $items = $entity->GetItems([
            'order' => [
                $orderBy => $orderOrder
            ],
            'filter' => [
                'STAGE_ID' => 'DT178_6:SUCCESS',
                '>=CREATED_TIME' => $date_from,
                '<=CREATED_TIME' => $date_to,
            ],
            'select' => [
                'ID',
                'CREATED_TIME',
                'UF_CRM_4_1678782578',
                'UF_CRM_4_1678782523'
            ]
        ]);
        foreach($items as $item) {
            $list[] = [
                'data' => [
                    'ID' => $item['ID'],
                    'CREATED_TIME' => $item['CREATED_TIME']->format('d.m.Y'),
                    'DESCRIPTION' => $item['UF_CRM_4_1678782578'],
                    'STATUS' => $item['UF_CRM_4_1678782523']
                ]
            ];
        }
        $headers = array(
            array(
                'id' => 'ID',
                'name' => "ID Заявки",
                'sort' => 'ID',
                'first_order' => 'desc',
                'type' => 'int',
                'default' => true,
              ),
              array(
                'id' => 'CREATED_TIME',
                'name' => "Дата создания",
                'sort' => 'CREATED_TIME',
                'default' => true,
              ),
              array(
                'id' => 'DESCRIPTION',
                'name' => "Описание",
                'type' => 'textarea',
                'default' => true,
              ),
              array(
                'id' => 'STATUS',
                'name' => "Результат",
                'type' => 'textarea',
                'default' => true,
              ),
          );
        $this->arResult['HEADERS'] = $headers;
        $this->arResult['LIST'] = $list;
        $this->arResult['NAV'] = $nav->setRecordCount(count($items));

        $this->includeComponentTemplate();
    }
}

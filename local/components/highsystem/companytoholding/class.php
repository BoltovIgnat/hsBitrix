<?php
defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UserTable;
use Bitrix\Main\Grid;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;


class CompanyToHoldingLink extends CBitrixComponent
{
    const GRID_ID = 'COMPANY_LINK_HOLDING';
    const SORTABLE_FIELDS = array('ID', 'TITLE', 'COMPANYINN', 'COMPANYKPP', 'REQINN', 'REQKPP');
    const FILTERABLE_FIELDS = array('ID', 'TITLE', 'COMPANYINN', 'REQINN');
    const SUPPORTED_ACTIONS = array([]);
    const SUPPORTED_SERVICE_ACTIONS = array('GET_ROW_COUNT');

    private static $headers;
    private static $filterFields;
    private static $filterPresets;

    public function __construct(CBitrixComponent $component = null)
    {
        global $USER;

        parent::__construct($component);

        self::$headers = array(
            array(
                'id' => 'ID',
                'name' => "ID",
                'sort' => 'ID',
                'first_order' => 'desc',
                'type' => 'int',
            ),
            array(
                'id' => 'TITLE',
                'name' => "Название",
                'sort' => 'TITLE',
                'default' => true,
            ),
            array(
                'id' => 'COMPANYINN',
                'name' => "ИНН Компании",
                'sort' => 'COMPANYINN',
                'default' => true,
            ),
            array(
                'id' => 'COMPANYKPP',
                'name' => "КПП Компании",
                'sort' => 'COMPANYKPP',
                'default' => true,
            ),
            array(
                'id' => 'REQINN',
                'name' => "ИНН Реквизита",
                'sort' => 'REQINN',
                'default' => true,
            ),
            array(
                'id' => 'REQKPP',
                'name' => "КПП Реквизита",
                'sort' => 'REQKPP',
                'default' => true,
            ),
            array(
                'id' => 'LINK',
                'name' => "Привязать",
                'sort' => 'LINK',
                'default' => true,
            ),
            array(
                'id' => 'UNLINK',
                'name' => "Отвязать",
                'sort' => 'UNLINK',
                'default' => true,
            ),
        );

        self::$filterFields = array(
            array(
                'id' => 'ID',
                'name' => "ID"
            ),
            array(
                'id' => 'TITLE',
                'name' => "Название",
            ),
            array(
                'id' => 'COMPANYINN',
                'name' => "Название",
                'default' => true,
            ),
            array(
                'id' => 'REQINN',
                'name' => "Название",
            ),
/*             array(
                'id' => 'ASSIGNED_BY_ID',
                'name' => Loc::getMessage(''),
                'type' => 'custom_entity',
                'params' => array(
                    'multiple' => 'Y'
                ),
                'selector' => array(
                    'TYPE' => 'user',
                    'DATA' => array(
                        'ID' => 'ASSIGNED_BY',
                        'FIELD_ID' => 'ASSIGNED_BY_ID'
                    )
                ),
                'default' => true,
            ),
            array(
                'id' => 'ADDRESS',
                'name' => Loc::getMessage(''),
                'default' => true,
            ), */
        );
        self::$filterPresets = [];
/*         self::$filterPresets = array(
            'my_stores' => array(
                'name' => Loc::getMessage(''),
                'fields' => array(
                    'ASSIGNED_BY_ID' => $USER->GetID(),
                    'ASSIGNED_BY_ID_name' => $USER->GetFullName(),
                )
            )
        ); */
    }

    /**
     * @param array $params
     * @return array
     */
    public function onPrepareComponentParams($params)
    {
        $arParams = parent::onPrepareComponentParams($params);

        return $arParams;
    }

    public function executeComponent()
    {
        $context = Context::getCurrent();
        $request = $context->getRequest();

        $grid = new Grid\Options(self::GRID_ID);

        //region Sort
        $gridSort = $grid->getSorting();
        $sort = array_filter(
            $gridSort['sort'],
            function ($field) {
                return in_array($field, self::SORTABLE_FIELDS);
            },
            ARRAY_FILTER_USE_KEY
        );
        if (empty($sort)) {
            $sort = array('ID' => 'asc');
        }
        //endregion

        //region Filter
        $gridFilter = new Filter\Options(self::GRID_ID, self::$filterPresets);
        $gridFilterValues = $gridFilter->getFilter(self::$filterFields);
        $gridFilterValues = array_filter(
            $gridFilterValues,
            function ($fieldName) {
                return in_array($fieldName, self::FILTERABLE_FIELDS);
            },
            ARRAY_FILTER_USE_KEY
        );
        //endregion

        $this->processGridActions($gridFilterValues);
        $this->processServiceActions($gridFilterValues);

        //region Pagination
        $gridNav = $grid->GetNavParams();
        $pager = new PageNavigation('ctoh_nav');
        $pager->setPageSize($gridNav['nPageSize']);
        $pager->setRecordCount($this->getCompaniesCount());

        if ($request->offsetExists('page')) {
            $currentPage = $request->get('page');
            $pager->setCurrentPage($currentPage > 0 ? $currentPage : $pager->getPageCount());
        } else {
            $pager->setCurrentPage(1);
        }
        //endregion

        $companies = $this->getCompanies(array(
            'filter' => $gridFilterValues,
            'limit' => $pager->getLimit(),
            'offset' => $pager->getOffset(),
            'order' => $sort
        ));

        $curCompanies = $this->getCurCompanies();

        $requestUri = new Uri($request->getRequestedPage());
        $requestUri->addParams(array('sessid' => bitrix_sessid()));

        $this->arResult = array(
            'GRID_ID' => self::GRID_ID,
            'COMPANIES' => $companies,
            'CURRENT_COMPANIES' => $curCompanies,
            'HEADERS' => self::$headers,
            'PAGINATION' => array(
                'PAGE_NUM' => $pager->getCurrentPage(),
                'ENABLE_NEXT_PAGE' => $pager->getCurrentPage() < $pager->getPageCount(),
                'URL' => $request->getRequestedPage(),
            ),
            'SORT' => $sort,
            'FILTER' => self::$filterFields,
            'FILTER_PRESETS' => self::$filterPresets,
            'ENABLE_LIVE_SEARCH' => false,
            'DISABLE_SEARCH' => true,
            'SERVICE_URL' => $requestUri->getUri(),
        );

        $this->includeComponentTemplate();
    }

    private function getCompaniesCount()
    {
        
        if ($_REQUEST['data']['search']) {
            $res = \Bitrix\Crm\CompanyTable::query()
                ->setSelect(['ID', 'TITLE',"UF_CRM_639719B8E38A2","UF_CRM_639719B9949D5"])
                ->where(\Bitrix\Main\Entity\Query::filter()
                    ->logic('or')
                    ->where([
                        ['TITLE', 'like', '%'.$_REQUEST['data']['search'].'%' ],
                        ['UF_CRM_639719B8E38A2', '=', $_REQUEST['data']['search']]
                    ])
                )
                /*                ->setLimit($params["limit"])
                                ->setOffset($params["offset"])
                                ->setOrder($params["order"])*/
                ->exec();
            $count = $res->getSelectedRowsCount();
            return $count;
        }
        return 0;
    }
    private function getCurCompanies(){

        $hid = $_REQUEST["parentEntityId"];
        $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
        ->addSelect("DST_ENTITY_ID")
        ->setFilter([
            "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
            "SRC_ENTITY_ID" => $hid,
            "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
        ])
        ->exec();
        $exist = $query->fetchAll();
        
        foreach ($exist as $ex) {
            $curCompanies[] = $ex["DST_ENTITY_ID"];
        }

        return $curCompanies;
    }

    private function getCompanies($params = array())
    {
        if ($_REQUEST['data']['search']) {
            $companies = [];
            $res = \Bitrix\Crm\CompanyTable::query()
                ->setSelect(['ID', 'TITLE',"UF_CRM_639719B8E38A2","UF_CRM_639719B9949D5"])
                ->where(\Bitrix\Main\Entity\Query::filter()
                    ->logic('or')
                    ->where([
                        ['TITLE', 'like', '%'.$_REQUEST['data']['search'].'%' ],
                        ['UF_CRM_639719B8E38A2', '=', $_REQUEST['data']['search']]
                    ])
                )
/*              ->setLimit($params["limit"])
                ->setOffset($params["offset"])
                ->setOrder($params["order"])*/
                ->exec();
            while ($companiesRes = $res->fetch()){
                $companies[$companiesRes["ID"]] = $companiesRes;
            }

            foreach ($companies as &$company) {
                $company["LINK"] = $company["ID"].",".$_REQUEST['data']["hid"];
                $company["UNLINK"] = $company["ID"].",".$_REQUEST['data']["hid"];
                $creqinn = "";
                $creqkpp = "";

                $query = \Bitrix\Crm\RequisiteTable::query()
                ->setSelect(["ENTITY_TYPE_ID","ENTITY_ID","RQ_INN","RQ_KPP","NAME","RQ_COMPANY_NAME","ENTITY_ID"])
                ->setFilter(["ENTITY_ID" => $company["ID"], "ENTITY_TYPE_ID" => 4 ])
                ->exec();
                $reqs = $query->fetchAll();

                foreach ($reqs as $key => $req) {
                    $creqinn .= $req["RQ_INN"]."<br>";
                    $creqkpp .= $req["RQ_KPP"]."<br>";
                }
                $company["REQINN"] = $creqinn;
                $company["REQKPP"] = $creqkpp;
            }

            $reqs = [];
            $query = \Bitrix\Crm\RequisiteTable::query()
                ->setSelect(["ENTITY_TYPE_ID","ENTITY_ID","RQ_INN","RQ_KPP","NAME","RQ_COMPANY_NAME","ENTITY_ID"])
                ->setFilter([ "RQ_INN" => $_REQUEST['data']['search'], "ENTITY_TYPE_ID" => 4 ])
                ->exec();
            $reqs = $query->fetchAll();

            foreach ($reqs as $key => &$req) {
                $req["TITLE"] = $req["RQ_COMPANY_NAME"];
                $req["ID"] = $req["ENTITY_ID"];
                $req["LINK"] = $req["ENTITY_ID"].",".$_REQUEST['data']["hid"];
                $req["UNLINK"] = $req["ENTITY_ID"].",".$_REQUEST['data']["hid"];
                if (array_key_exists($req["ENTITY_ID"],$companies)) {
                    if (empty($companies[$req["ENTITY_ID"]]["REQINN"]) || empty($companies[$req["ENTITY_ID"]]["REQKPP"])) {
                        $companies[$req["ENTITY_ID"]]["REQINN"] .= $req["RQ_INN"]."<br>";
                        $companies[$req["ENTITY_ID"]]["REQKPP"] .= $req["RQ_KPP"]."<br>";
                    }
                    unset($reqs[$key]);
                }
            }

            $companies = array_merge($companies,$reqs);
        }

        return $companies;
    }

    private function processGridActions($currentFilter)
    {
        if (!check_bitrix_sessid()) {
            return;
        }

        $context = Context::getCurrent();
        $request = $context->getRequest();

        $action = $request->get('action_button_' . self::GRID_ID);

        if (!in_array($action, self::SUPPORTED_ACTIONS)) {
            return;
        }

        $allRows = $request->get('action_all_rows_' . self::GRID_ID) == 'Y';
        if ($allRows) {
            $companies = $this->getCompanies(array(
                'filter' => $currentFilter,
                'select' => array('ID'),
            ));
            $compIDS = array();
            foreach ($companies as $cID) {
                $compIDS[] = $cID['ID'];
            }
        } else {
            $compIDS = $request->get('ID');
            if (!is_array($compIDS)) {
                $compIDS = array();
            }
        }

        if (empty($compIDS)) {
            return;
        }

        switch ($action) {
            case 'delete':
                foreach ($compIDS as $cID) {
                    \Bitrix\Crm\CompanyTable::delete($cID);
                }
            break;

            default:
            break;
        }
    }

    private function processServiceActions($currentFilter)
    {
        global $APPLICATION;

        if (!check_bitrix_sessid()) {
            return;
        }

        $context = Context::getCurrent();
        $request = $context->getRequest();

        $params = $request->get('PARAMS');

        if (empty($params['GRID_ID']) || $params['GRID_ID'] != self::GRID_ID) {
            return;
        }

        $action = $request->get('ACTION');

        if (!in_array($action, self::SUPPORTED_SERVICE_ACTIONS)) {
            return;
        }

        $APPLICATION->RestartBuffer();
        header('Content-Type: application/json');

        switch ($action) {
            case 'GET_ROW_COUNT':
                $count = $this->getCompaniesCount();

                echo Json::encode(array(
                    'DATA' => array(
                        'TEXT' => Loc::getMessage('GRID_ROW_COUNT', array('#COUNT#' => $count))
                    )
                ));
            break;

            default:
            break;
        }

        die;
    }
}
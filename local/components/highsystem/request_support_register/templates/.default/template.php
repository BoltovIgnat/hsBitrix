<?php
//$arResult['LIST'] = array_slice($arResult['LIST'], 0, 250);
/*
$APPLICATION->IncludeComponent(
    'bitrix:crm.interface.grid',
    'titleflex_nperm',
    array(
      'GRID_ID' => 'smart_support_list',
      'AJAX_ID' => '',
      'AJAX_OPTION_JUMP' => 'N',
      'AJAX_OPTION_HISTORY' => 'N',
      'AJAX_LOADER' => null,
      'SHOW_NAVIGATION_PANEL' => false,
      'HIDE_FILTER' => true,
      'HEADERS' => $arResult['HEADERS'],
      'ROWS' => $arResult['LIST']
    ),
    $this->getComponent(),
    array('HIDE_ICONS' => 'Y',)
  );

?><style>
    .main-grid-cell-content{
        max-width: 300px;
        word-break: break-all;
    }
</style> */ ?>

<table class="reestr">
    <thead>
    <tr>
        <td class="center" width="5%">ID Заявки</td>
        <td class="center" width="5%">Дата создания</td>
        <td width="200px">Описание</td>
        <td width="200px">Результат</td>
    </tr>
    </thead>
    <?foreach ($arResult["LIST"] as $key => $value):?>
    <tr>
        <td class="center up"><?=$value['data']['ID'];?></td>
        <td class="center up"><?=$value['data']['CREATED_TIME'];?></td>
        <td class="pre up" width="200px"><?=$value['data']['DESCRIPTION'];?></td>
        <td class="pre up" width="200px"><?=$value['data']['STATUS'];?></td>
    </tr>
    <?endforeach;?>
</table>

<style>
    .up {
        text-align: start;
        vertical-align: top;
    }
    .reestr td {
        padding: 5px;
    }
    .reestr td,tr {
        border: 1px solid black;
    }
    .reestr {
        border-collapse: collapse;
    }
    .reestr td.pre {
        white-space:pre;
        word-break: break-all;
        text-wrap:wrap;
        max-width:200px;
    }
    .reestr .center {
        text-align:center;
    }
</style>
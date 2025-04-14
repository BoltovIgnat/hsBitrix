<?
namespace Dbbo\Jivo\Api;

use Bitrix\Main\Application,
    Bitrix\Main\Config\Option,
    Bitrix\Main\UserTable;

class User {
    public static function GetUserGroup() {
        $GLOBALS['USER']->GetUserGroupArray();
    }
    
    /**
     * 
     * @param type $filter
     * @param type $select
     * @return type
     */
    public static function GetList($filter, $select = []) {
        return UserTable::getRow(
            array(
            'filter' => $filter,
            'select' => $select
        ));
    }
    
    /**
     * 
     * @param type $fields
     * @return type
     */
    public static function Add($fields) {
        $result = [];
        $user = new \CUser;
        $addRes = $user->Add($fields);
        if(!intval($addRes)) {
            $result['ERRORS'] = $user->LAST_ERROR;
        }
        return $result;
    }
    
    /**
     * 
     * @param type $user_id
     * @param type $fields
     * @return type
     */
    public static function Update($user_id, $fields) {
        $result = [];
        $user = new \CUser;
        $addRes = $user->Update($user_id, $fields);
        if(!intval($addRes)) {
            $result['ERRORS'] = $user->LAST_ERROR;
        }
        return $result;
    }
    
    /**
     * 
     * @return type
     */
    public static function IsAuth() {
        return $GLOBALS['USER']->isAuthorized();
    }
    
    /**
     * 
     * @return type
     */
    public static function GetCurrentUserFields() {
        return self::GetList(
            ['=ID' => $GLOBALS['USER']->GetID()],
            ['*', 'UF_*']
        );
    }
    
    /**
     * 
     * @return type
     */
    public static function GetFullName() {
        $user = self::GetList(
            [
                '=ID' => $GLOBALS['USER']->GetID()
            ],
            [
                'NAME',
                'LAST_NAME'
            ]
        );
        return $user['NAME'] . ' ' . $user['LAST_NAME'];
    }
    
    /**
     * 
     * @return type
     */
    public static function GetInfo() {
        $user = self::GetList(
            [
                '=ID' => $GLOBALS['USER']->GetID()
            ],
            [
                'NAME',
                'LAST_NAME',
                'EMAIL'
            ]
        );
        return $user;
    }
}
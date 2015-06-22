<?php

namespace admin\apis;

use Yii;

/**
 * @todo rename from auth to permission
 *
 * @author nadar
 */
class MenuController extends \admin\base\RestController
{
    /**
     * @todo: changing the auser auth method, add user component
     */
    public function init()
    {
        parent::init();
        Yii::$app->menu->userId = $this->getUser()->id;
    }

    public function actionIndex()
    {
        return Yii::$app->menu->getModules();
    }

    public function actionItems($nodeId)
    {
        return Yii::$app->menu->getModuleItems($nodeId);
    }

    public function actionDashboard($nodeId)
    {
        $data = Yii::$app->menu->getNodeData($nodeId);
        $accessList = [];
        
        foreach ($data['groups'] as $groupkey => $groupvalue) {
            foreach ($groupvalue['items'] as $row) {
                if ($row['permissionIsApi']) {
                    // @todo check if the user can access this api, otherwise hide this log informations?
                    $accessList[] = $row;
                }
            }
        }
        
        $log = [];
        foreach($accessList as $access) {
            $data = (new \yii\db\Query())->select(['timestamp_create', 'admin_ngrest_log.id', 'is_update', 'is_insert', 'admin_user.firstname', 'admin_user.lastname'])->from('admin_ngrest_log')->leftJoin('admin_user', 'admin_ngrest_log.user_id = admin_user.id')->orderBy('timestamp_create DESC')->where('api=:api and user_id!=0', [':api' => $access['permssionApiEndpoint']])->all();
            foreach($data as $row) {
                $date = mktime(0,0,0, date("n", $row['timestamp_create']), date("j", $row['timestamp_create']), date("Y", $row['timestamp_create']));
                $log[$date][$row['id']] = [
                    'name' => $row['firstname'] . " " . $row['lastname'],
                    'is_update' => $row['is_update'],
                    'is_insert' => $row['is_insert'],
                    'timestamp' => $row['timestamp_create'],
                    'alias' => $access['alias'],
                    'icon' => $access['icon'],
                ];
            }
        }
        
        
        $array = [];
        
        foreach($log as $day => $values) {
            $array[] = [
                'day' => $day,
                'items' => $values,
            ];
        }
        
        return $array;
    }
}

<?php
/**
 * @copyright Copyright (c) 2016. Wubbleyou Ltd
 */

namespace wubbleyou\ordermodel;

use yii\base\Action;
use yii\web\NotFoundHttpException;

/**
 * Action which is attached to a controller which allows ordering of models within a grid view table.
 * This action will use the controller's 'findModel' function, making this public will be required
 *
 * Attach via:
 *
 * 'action_name' =>
 * [
 *      'class' => OrderModelAction::className(),
 *      'columns' => ['order']
 * ],
 *
 * Class OrderModelAction
 * @package wubbleyou\ordermodel
 */
class OrderModelAction extends Action
{

    /**
     * @var array of columns which can be ordered
     */
    public $columns = ['order'];

    /**
     * @param $direction string either up or down
     * @param $attribute string the name of the attribute within the model to order by
     * @param $id mixed ID of the model to order
     * @throws NotFoundHttpException
     */
    public function run($direction, $attribute, $id)
    {

        if(!in_array($attribute, $this->columns) || !in_array($direction, ['up', 'down'])){

            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $model = $this->controller->findModel($id);

        if($direction === 'up'){

            $model->orderUp($attribute);
        }
        else{

            $model->orderDown($attribute);
        }
    }

}

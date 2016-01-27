<?php
/**
 * @copyright Copyright (c) 2016. Wubbleyou Ltd
 */

namespace wubbleyou\ordermodel;

use yii\base\Event;
use yii\db\ActiveRecord;
use yii\behaviors\AttributeBehavior;

/**
 * Behaviour which can be attached to an ActiveRecord which adds functions and abilities to order a record set within a whole table
 * or by a subset, for example a parent category
 *
 * Attach via:
 *
 * [
 *      'class' => OrderBehavior::className(),
 *      'sortField => 'sort_attribute_name',
 *      'restrictBy' => ['parent_category_name'] //optional
 * ],
 *
 * Class OrderBehavior
 * @package wubbleyou\ordermodel
 */
class OrderBehavior extends AttributeBehavior
{

    /**
     * @var $sortField STRING the field which is sorted by
     */
    public $sortField = 'order';

    /**
     * @var $restrictBy array of fields which restrict the search for sort, for example a category id
     */
    public $restrictBy = [];


    const DIRECTION_UP = 'up';
    const DIRECTION_DOWN = 'down';

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }


    /**
     * Adds incremented order field
     *
     * @var $event Event
     */
    public function beforeInsert($event)
    {
        if($this->owner->getIsNewRecord()){

            if($highestOrderModel = $this->getHighestOrderModel()){

                $this->owner->{$this->sortField} = $highestOrderModel->{$this->sortField} + 1;
            }
            else{
                $this->owner->{$this->sortField} = 0;
            }
        }
    }

    /**
     * Reorder other models after deletion
     */
    public function afterDelete($event){

        $where = ['and', ['>', 'order', $this->owner->{$this->sortField}]];

        if(!empty($this->restrictBy)){

            foreach($this->restrictBy as $rb){

                $where[] = ['=', $rb, $this->owner->$rb];
            }
        }

        (new \yii\db\Query())
            ->createCommand()
            ->update(
                $this->owner->tableName(),
                array($this->sortField => new \yii\db\Expression("`{$this->sortField}` - 1")),
                $where)
            ->execute();
    }

    /**
     * @return STRING containing the sort field
     */
    public function getSortFieldName(){

        return $this->sortField;
    }

    /**
     * Sets this model to be list in the list
     * @param bool $adjustOldRecord
     */
    public function orderLast($adjustOldRecord = true){


        if($highestOrderModel = $this->getHighestOrderModel()){

            $order = $this->owner->{$this->sortField};

            $this->owner->{$this->sortField} = $highestOrderModel->{$this->sortField} + 1;

            $this->owner->save(true, array($this->sortField));

            //Resort the middle records
            if($adjustOldRecord){

                $whereSql = "`{$this->sortField}` >= :order ";
                $params = array(':order' => $order);
                if(!empty($this->restrictBy)){

                    foreach($this->restrictBy as $rb){

                        $whereSql .= " AND `$rb` = :$rb";
                        $params[':'.$rb] = $this->owner->$rb;
                    }
                }

                (new \yii\db\Query())
                    ->createCommand()
                    ->update(
                        $this->owner->tableName(),
                        array($this->sortField => new \yii\db\Expression("`{$this->sortField}` - 1")),
                        $whereSql,
                        $params)
                    ->execute();
            }

        }
    }

    /**
     * @return ActiveRecord containing the highest ordered model for this order
     */
    protected function getHighestOrderModel(){

        $whereArr = [];

        if(!empty($this->restrictBy)){

            foreach($this->restrictBy as $rb){

                $whereArr[$rb] = $this->owner->$rb;
            }
        }

        return $this->getSearchModel()
            ->orderBy("`{$this->sortField}` DESC")
            ->one();
    }

    /**
     * @return ActiveRecord
     */
    protected function getSearchModel(){

        $className = $this->owner->className();
        $model = $className::find();

        $whereArr = [];

        if(!empty($this->restrictBy)){

            foreach($this->restrictBy as $rb){

                $whereArr[$rb] = $this->owner->$rb;
            }
        }

        return $model->andFilterWhere($whereArr);
    }

    /**
     * Orders a model upwards
     */
    public function orderUp(){

        $this->_orderDirection(self::DIRECTION_UP);
    }

    /**
     * orders a model downwards
     */
    public function orderDown(){

        $this->_orderDirection(self::DIRECTION_DOWN);
    }

    /**
     * @param $direction string self::DIRECTION_*
     */
    private function _orderDirection($direction){

        $order = $this->owner->{$this->sortField};

        $highestOrder = NULL;
        if($highestOrderModel = $this->getHighestOrderModel()){

            $highestOrder = $highestOrderModel->{$this->sortField};
        }

        if ($direction === self::DIRECTION_UP && $order > 0)
            $order -= 1;
        else if ($direction === self::DIRECTION_DOWN && $order < $highestOrder)
            $order += 1;

        $oldModel = $this->getSearchModel()->andWhere(['order' => $order])->one();

        if($oldModel){

            $oldModel->{$this->sortField} = $this->owner->{$this->sortField};
            $oldModel->save(false);

            $this->owner->{$this->sortField} = $order;
            $this->owner->save(false);
        }
    }
}

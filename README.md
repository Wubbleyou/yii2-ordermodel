# yii2-ordermodel
Allows users to order records in a grid view via a custom column.  This requires 3 minor additions:

- Attach a custom behavior to a ActiveRecord
- Attach a custom action to a Controller
- Attach a custom column to a GridView


# Installation

Either run

```
php composer.phar require --prefer-dist wubbleyou/yii2-ordermodel "*"
```

or add

```
"wubbleyou/yii2-ordermodel": "*"
```

to the require section of your `composer.json` file.

# Usage

After installing the extension the following is required.

Adding the custom behavior to a ActiveRecord:

```
    public function behaviors()
    {
        return [
            [
                'class' => OrderBehavior::className(),
                'sortField => 'sort_attribute_name',
                'restrictBy' => ['parent_category_name'] //optional
           ],
        ];
    }
```

Adding the custom column to a GridView:

```
    public function actions()
    {
        return [
            'order' => array(
                'class' => OrderModelAction::className(),
                'columns' => ['order']
            ),
        ];
    }
    
```

Adding the custom column to a GridView

```

  GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            'name',
            [
              'class' => OrderModelColumn::className(),
              'attribute' => 'order'],
            
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]);
```

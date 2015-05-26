<?php

namespace app\modules\shop\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * This is the model class for table "order_transaction".
 *
 * @property integer $id
 * @property integer $order_id
 * @property integer $payment_type_id
 * @property string $start_date
 * @property string $end_date
 * @property integer $status
 * @property float $total_sum
 * @property string $params
 * @property string $result_data
 * Relations:
 * @property Order $order
 * @property PaymentType $paymentType
 */
class OrderTransaction extends ActiveRecord
{
    const TRANSACTION_START = 1;
    const TRANSACTION_CHECKING = 2;
    const TRANSACTION_TIMEOUT = 3;
    const TRANSACTION_ROLLBACK = 4;
    const TRANSACTION_SUCCESS = 5;
    const TRANSACTION_ERROR = 6;

    private $statusTransaction = [
        OrderTransaction::TRANSACTION_START => 'Transaction start',
        OrderTransaction::TRANSACTION_CHECKING => 'Transaction checking',
        OrderTransaction::TRANSACTION_TIMEOUT => 'Transaction timeout',
        OrderTransaction::TRANSACTION_ROLLBACK => 'Transaction rollback',
        OrderTransaction::TRANSACTION_SUCCESS => 'Transaction success',
        OrderTransaction::TRANSACTION_ERROR => 'Transaction error',
    ];

    private static $lastByOrder = [];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_transaction}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'payment_type_id', 'status', 'total_sum'], 'required'],
            [['order_id', 'payment_type_id', 'status'], 'integer'],
            [['start_date', 'end_date'], 'safe'],
            [['total_sum'], 'number'],
            [['params', 'result_data'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'order_id' => Yii::t('app', 'Order ID'),
            'payment_type_id' => Yii::t('app', 'Payment Type ID'),
            'start_date' => Yii::t('app', 'Start Date'),
            'end_date' => Yii::t('app', 'End Date'),
            'status' => Yii::t('app', 'Status'),
            'total_sum' => Yii::t('app', 'Total Sum'),
            'params' => Yii::t('app', 'Params'),
            'result_data' => Yii::t('app', 'Result Data'),
        ];
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }

    /**
     * @return PaymentType
     */
    public function getPaymentType()
    {
        return $this->hasOne(PaymentType::className(), ['id' => 'payment_type_id']);
    }

    /**
     * @param integer $status
     * @return bool
     */
    public function updateStatus($status)
    {
        $this->status = $status;
        return $this->save(true, ['status']);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->end_date = new Expression('NOW()');
        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if (isset($changedAttributes['status']) && $changedAttributes['status'] == 1
            && $this->status == self::TRANSACTION_SUCCESS
        ) {
            $this->order->order_status_id = 3;
            $this->order->save(true, ['order_status_id']);
        }
    }

    public static function findLastByOrder(Order $order)
    {
        if (isset(static::$lastByOrder[$order->id])) {
            $model = static::$lastByOrder[$order->id];
        } else {
            $model = static::find()->where([
                'order_id' => $order->id,
                'payment_type_id' => $order->payment_type_id,

            ])
                ->andWhere(['not in', 'status', [static::TRANSACTION_ROLLBACK, static::TRANSACTION_ERROR, static::TRANSACTION_SUCCESS]])
                ->orderBy(['id' => SORT_DESC])->one();

            static::$lastByOrder[$order->id] = $model;
        }

        return $model;
    }

    public static function createForOrder(Order $order)
    {
        $order->calculate();
        $model = new static();
            $model->order_id = $order->id;
            $model->payment_type_id = $order->payment_type_id;
            $model->status = static::TRANSACTION_START;
            $model->total_sum = $order->total_price;

        return $model->save() ? $model : null;
    }

    public function getTransactionStatus($asLiteral = true)
    {
        return !isset($this->statusTransaction[$this->status]) ? '' :
            ($asLiteral ? Yii::t('app', $this->statusTransaction[$this->status]) : $this->status);
    }
}
?>
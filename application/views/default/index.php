<?php

/**
 * @var yii\web\View $this
 */

use app\modules\image\widgets\ObjectImageWidget;
use kartik\helpers\Html;
use yii\helpers\Url;

$this->title = 'DotPlant demo site';
$this->context->layout = 'main-page';
$featuredProducts = \app\modules\shop\models\Product::find()->orderBy('`sort_order` DESC')->limit(8)->all();
$latestProducts = \app\modules\shop\models\Product::find()->orderBy('`id` DESC')->limit(6)->all();
$mainCurrency = \app\modules\shop\models\Currency::getMainCurrency();

?>
<?php if (count($featuredProducts) > 0): ?>
    <div class="well well-small">
        <h4><?= Yii::t('app', 'Featured products') ?> <small class="pull-right">200+ featured products</small></h4>
        <div class="row-fluid">
            <div id="featured" class="carousel slide">
                <div class="carousel-inner">
                    <?php for ($j = 0; $j < 2; $j++): ?>
                        <div class="item <?= $j == 0 ? 'active' : '' ?>">
                            <div class="row">
                                <?php for ($i = 0; $i < count($featuredProducts) && $i < 4; $i++): ?>
                                    <?php
                                        $product = $featuredProducts[$j * 4 + $i];
                                        $url = Url::toRoute(
                                            [
                                                '/shop/product/show',
                                                'model' => $product,
                                            ]
                                        );
                                    ?>
                                    <div class="col-md-3">
                                        <div class="thumbnail">
                                            <i class="tag"></i>
                                            <a href="<?= $url ?>">
                                                <?=
                                                    ObjectImageWidget::widget(
                                                        [
                                                            'limit' => 1,
                                                            'model' => $product
                                                        ]
                                                    )
                                                ?>
                                            </a>
                                            <div class="caption">
                                                <h5><a href="<?= $url ?>"><?= Html::encode($product->name) ?></a></h5>
                                                <h4><a class="btn btn-mini" href="#" data-action="add-to-cart" data-id="<?= $product->id ?>"><?= Yii::t('app', 'Add to') ?> <i class="icon-shopping-cart"></i></a> <span class="pull-right"><?= $mainCurrency->format($product->price) ?> <?= Yii::$app->params['currency'] ?></span></h4>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <a class="left carousel-control" href="#featured" data-slide="prev">‹</a>
                <a class="right carousel-control" href="#featured" data-slide="next">›</a>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if (count($latestProducts) > 0): ?>
    <h4><?= Yii::t('app', 'New products') ?></h4>
    <div id="blockView">
        <div class="row">
            <?php
                foreach ($latestProducts as $product) {
                    $url = Url::to(
                        [
                            '/shop/product/show',
                            'model' => $product,
                        ]
                    );
                    echo $this->render('@app/modules/shop/views/product/item', ['product' => $product, 'url' => $url]);
                }
            ?>
        </div>
    </div>
<?php endif; ?>

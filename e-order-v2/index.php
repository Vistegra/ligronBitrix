<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("ЛИГРОН | Электронный заказ V2");
?>

<div class="">
  <?php $APPLICATION->IncludeComponent(
    "vistegra:e.order.page.v2",
   ".default",
    array(),
    false
); ?>



</div>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
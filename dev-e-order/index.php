<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("ЛИГРОН | Электронный заказ");
?>

<div class="">
  <?php $APPLICATION->IncludeComponent(
    "vistegra:e.order.page.dev",
   ".default",
    array(),
    false
); ?>



</div>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>